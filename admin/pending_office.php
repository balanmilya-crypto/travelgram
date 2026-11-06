<?php
session_start();
include 'connection.php';
include 'auth_check.php'; // التأكد من أن المستخدم سوبر أدمن

// ====== الحصول على معرف السوبر أدمن ======
$admin_user_id = 0;
$admin_query = mysqli_query($conn, "SELECT id FROM users WHERE role = 'super_admin' LIMIT 1");
if ($admin_query && $admin_row = mysqli_fetch_assoc($admin_query)) {
    $admin_user_id = $admin_row['id'];
    $_SESSION['user_id'] = $admin_user_id;
} else {
    $admin_user_id = 1;
    $_SESSION['user_id'] = 1;
}

// معالجة الموافقة
$message = '';
$error = '';

if (isset($_GET['approve'])) {
    $office_id = intval($_GET['approve']);
    mysqli_begin_transaction($conn);
    try {
        $update_office = "UPDATE offices SET approved = 1 WHERE id = $office_id";
        if (!mysqli_query($conn, $update_office)) {
            throw new Exception("Error updating office: " . mysqli_error($conn));
        }

        $update_user = "UPDATE users SET is_active = 1 WHERE office_id = $office_id AND role = 'office_admin'";
        if (!mysqli_query($conn, $update_user)) {
            throw new Exception("Error updating user: " . mysqli_error($conn));
        }

        $admin_sql = "SELECT name, email FROM users WHERE office_id = $office_id AND role = 'office_admin'";
        $admin_res = mysqli_query($conn, $admin_sql);
        if ($admin_row = mysqli_fetch_assoc($admin_res)) {
            $admin_name = $admin_row['name'];
            $admin_email = $admin_row['email'];

            $title = "Office Approved";
            $notif_message = "Your office registration has been approved. You can now login to your account.";
            $type = "office_approved";
            $data = json_encode(['office_id' => $office_id]);
            $insert_notif = "INSERT INTO notifications (user_id, type, title, message, data, is_read, created_at) 
                             VALUES ((SELECT id FROM users WHERE email = '$admin_email'), '$type', '$title', '$notif_message', '$data', 0, NOW())";
            if (!mysqli_query($conn, $insert_notif)) {
                throw new Exception("Error inserting notification: " . mysqli_error($conn));
            }
        } else {
            throw new Exception("Admin user not found for office ID $office_id");
        }

        mysqli_commit($conn);
        $message = "Office approved successfully.";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Error: " . $e->getMessage();
    }
}

// معالجة الرفض
if (isset($_GET['reject'])) {
    $office_id = intval($_GET['reject']);
    mysqli_begin_transaction($conn);
    try {
        $delete_user = "DELETE FROM users WHERE office_id = $office_id AND role = 'office_admin'";
        if (!mysqli_query($conn, $delete_user)) {
            throw new Exception("Error deleting user: " . mysqli_error($conn));
        }
        $delete_office = "DELETE FROM offices WHERE id = $office_id";
        if (!mysqli_query($conn, $delete_office)) {
            throw new Exception("Error deleting office: " . mysqli_error($conn));
        }
        mysqli_commit($conn);
        $message = "Office request rejected and deleted.";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Error: " . $e->getMessage();
    }
}

// جلب جميع الطلبات المعلقة
$sql = "SELECT o.*, u.id as user_id, u.name as admin_name, u.email as admin_email, u.phone as admin_phone 
        FROM offices o 
        JOIN users u ON o.id = u.office_id 
        WHERE o.approved = 0 AND u.role = 'office_admin' AND u.is_active = 0
        ORDER BY o.created_at DESC";
$result = mysqli_query($conn, $sql);

// ✅ جلب عدد الإشعارات غير المقروءة للسوبر أدمن
$notifications_list = [];
$notif_query = "SELECT id, is_read FROM notifications WHERE user_id = $admin_user_id ORDER BY created_at DESC LIMIT 10";
$notif_res = mysqli_query($conn, $notif_query);
$unread_notifications = 0;
if ($notif_res) {
    while ($row = mysqli_fetch_assoc($notif_res)) {
        $notifications_list[] = $row;
        if (!$row['is_read']) $unread_notifications++;
    }
}

// جلب عدد الرسائل غير المقروءة للسوبر أدمن
$messages_count = 0;
$msg_query = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM messages WHERE recipient_id = $admin_user_id AND is_read = 0");
if ($msg_query && $msg_row = mysqli_fetch_assoc($msg_query)) {
    $messages_count = $msg_row['cnt'];
}

// ✅ جلب عدد الرحلات المعلقة
$pending_tours_count = 0;
$pending_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM tours WHERE status = 'pending'");
if ($pending_row = mysqli_fetch_assoc($pending_query)) {
    $pending_tours_count = $pending_row['count'];
}

// ✅ جلب عدد طلبات المكاتب المعلقة
$pending_offices_count = 0;
$offices_pending_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM offices WHERE approved = 0");
if ($offices_pending_row = mysqli_fetch_assoc($offices_pending_query)) {
    $pending_offices_count = $offices_pending_row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Office Approvals | TravelGram</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Cairo', 'Poppins', sans-serif;
            background: #eff1ee;
            color: #1b1c27;
            display: flex;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        .bg-bubble {
            position: fixed;
            border-radius: 50%;
            background: rgba(101, 64, 58, 0.05);
            z-index: 0;
            pointer-events: none;
            animation: float 12s infinite alternate ease-in-out;
        }
        .bg-bubble:nth-child(1) { width: 300px; height: 300px; top: -50px; left: -50px; background: rgba(158, 193, 215, 0.08); animation-duration: 15s; }
        .bg-bubble:nth-child(2) { width: 500px; height: 500px; bottom: -100px; right: -100px; background: rgba(101, 64, 58, 0.06); animation-duration: 18s; }
        .bg-bubble:nth-child(3) { width: 200px; height: 200px; top: 40%; left: 10%; background: rgba(115, 121, 59, 0.05); animation-duration: 10s; }
        .bg-bubble:nth-child(4) { width: 400px; height: 400px; bottom: 20%; left: -150px; background: rgba(158, 193, 215, 0.07); animation-duration: 20s; }
        .bg-bubble:nth-child(5) { width: 250px; height: 250px; top: 15%; right: 5%; background: rgba(101, 64, 58, 0.04); animation-duration: 14s; }

        .bg-wave {
            position: fixed;
            width: 100%; height: 100%; top: 0; left: 0; z-index: 0; pointer-events: none;
            background: url('data:image/svg+xml;utf8,<svg width="100%" height="100%" viewBox="0 0 1200 800" xmlns="http://www.w3.org/2000/svg"><path d="M0,400 Q300,200 600,400 T1200,300 L1200,800 L0,800 Z" fill="rgba(158,193,215,0.05)"/><path d="M0,600 Q400,450 800,600 T1200,500 L1200,800 L0,800 Z" fill="rgba(101,64,58,0.03)"/></svg>');
            background-size: cover;
            opacity: 0.5;
        }

        @keyframes float {
            0% { transform: translateY(0px) scale(1); }
            100% { transform: translateY(-30px) scale(1.05); }
        }

        /* الشريط الجانبي */
        .sidebar {
            width: 270px;
            background: #1b1c27;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            z-index: 10;
        }
        /* تم إضافة الأنماط الناقصة لـ sidebar-header */
        .sidebar-header {
            padding: 25px 20px;
            background: rgba(101, 64, 58, 0.15);
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            border-bottom: 1px solid rgba(158, 193, 215, 0.2);
        }
        .sidebar-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #9ec1d7;
        }
        .sidebar-header h3 {
            color: white;
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
        }
        .sidebar-menu { padding: 20px 0; }
        .sidebar-menu ul { list-style: none; }
        .sidebar-menu li { margin-bottom: 8px; }
        .sidebar-menu a {
            display: flex; align-items: center; padding: 12px 20px;
            color: rgba(239, 241, 238, 0.8); text-decoration: none; border-radius: 12px;
            margin: 0 12px; transition: 0.3s; font-weight: 500;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: #65403a; color: white; box-shadow: 0 5px 15px rgba(101, 64, 58, 0.3);
        }
        .sidebar-menu i { margin-right: 15px; width: 24px; text-align: center; font-size: 1.2rem; }
        .msg-badge {
            background: #ed4956; color: white; border-radius: 30px; padding: 2px 10px;
            font-size: 0.75rem; font-weight: bold; margin-left: 10px;
        }

        /* المحتوى الرئيسي */
        .main-content {
            flex: 1;
            margin-left: 270px;
            padding: 30px;
            position: relative;
            z-index: 5;
        }

        /* الهيدر */
        .header {
            display: flex; justify-content: space-between; align-items: center;
            background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(15px);
            padding: 18px 30px; border-radius: 40px; margin-bottom: 35px;
            border: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 10px 25px rgba(27, 28, 39, 0.05);
        }
        .header h1 {
            display: flex; align-items: center; gap: 12px;
            font-size: 1.8rem; color: #1b1c27; font-weight: 700;
        }
        .header-logo { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid #9ec1d7; }
        .user-info { display: flex; align-items: center; gap: 25px; }
        .notification-icon { position: relative; cursor: pointer; font-size: 1.5rem; color: #65403a; }
        .notification-badge {
            position: absolute; top: -8px; right: -12px; background: #ed4956; color: white;
            border-radius: 50%; padding: 3px 7px; font-size: 11px; font-weight: bold; min-width: 20px; text-align: center;
        }
        @keyframes shake {
            0% { transform: translate(1px,1px) rotate(0deg); }
            10% { transform: translate(-1px,-2px) rotate(-1deg); }
            20% { transform: translate(-3px,0px) rotate(1deg); }
            30% { transform: translate(3px,2px) rotate(0deg); }
            40% { transform: translate(1px,-1px) rotate(1deg); }
            50% { transform: translate(-1px,2px) rotate(-1deg); }
            60% { transform: translate(-3px,1px) rotate(0deg); }
            70% { transform: translate(3px,1px) rotate(-1deg); }
            80% { transform: translate(-1px,-1px) rotate(1deg); }
            90% { transform: translate(1px,2px) rotate(0deg); }
            100% { transform: translate(1px,-2px) rotate(-1deg); }
        }
        .bell-shake { animation: shake 0.5s ease-in-out infinite; display: inline-block; }
        .avatar {
            width: 48px; height: 48px; border-radius: 50%; background: #65403a;
            display: flex; align-items: center; justify-content: center; color: white;
            font-weight: bold; font-size: 1.3rem; overflow: hidden; border: 2px solid #9ec1d7;
        }

        /* أنماط الجدول والأزرار */
        .table-container { background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(15px); border-radius: 30px; padding: 30px 35px; box-shadow: 0 20px 40px rgba(27,28,39,0.08); border: 1px solid rgba(255,255,255,0.5); overflow-x: auto; }
        h2 { font-size: 1.8rem; font-weight: 700; color: #1b1c27; margin-bottom: 25px; }
        .message, .error { padding: 15px 20px; border-radius: 40px; margin-bottom: 25px; font-size: 0.95rem; backdrop-filter: blur(8px); }
        .message { background: rgba(40,167,69,0.1); color: #155724; border-left: 5px solid #28a745; }
        .error { background: rgba(220,53,69,0.1); color: #b91c1c; border-left: 5px solid #b91c1c; }
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        th, td { padding: 14px 12px; text-align: left; border-bottom: 1px solid rgba(158,193,215,0.3); }
        th { background: rgba(101,64,58,0.05); color: #1b1c27; font-weight: 700; border-bottom: 2px solid #9ec1d7; }
        tr:hover td { background: rgba(239,241,238,0.5); }
        .btn-sm { display: inline-block; padding: 6px 14px; font-size: 0.8rem; font-weight: 600; border-radius: 40px; text-decoration: none; color: white; margin: 2px; transition: 0.3s; border: none; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .btn-sm i { margin-right: 5px; }
        .btn-approve { background: #28a745; box-shadow: 0 4px 10px rgba(40,167,69,0.2); }
        .btn-approve:hover { background: #218838; transform: translateY(-2px); box-shadow: 0 8px 15px rgba(40,167,69,0.25); }
        .btn-reject { background: #dc3545; box-shadow: 0 4px 10px rgba(220,53,69,0.2); }
        .btn-reject:hover { background: #c82333; transform: translateY(-2px); box-shadow: 0 8px 15px rgba(220,53,69,0.25); }
        .back-link { display: inline-flex; align-items: center; gap: 8px; margin-top: 25px; color: #65403a; text-decoration: none; font-weight: 600; transition: 0.3s; }
        .back-link:hover { color: #4f322c; transform: translateX(-5px); }

        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h3, .sidebar-menu span { display: none; }
            .sidebar-menu i { margin-right: 0; }
            .main-content { margin-left: 80px; }
            .table-container { padding: 20px 18px; }
        }
    </style>
</head>
<body>
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<div class="sidebar">
    <div class="sidebar-header">
        <img src="../img/logo.jpg" alt="Logo">
        <h3>TravelGram</h3>
    </div>
    <div class="sidebar-menu">
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="all_tours.php"><i class="fas fa-plane"></i> <span>All Tours</span></a></li>
            <li><a href="pending_tours.php"><i class="fas fa-clock"></i> <span>Pending Tours</span>
                <?php if ($pending_tours_count > 0): ?>
                    <span class="msg-badge"><?php echo $pending_tours_count; ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="pending_office.php" class="active"><i class="fas fa-building"></i> <span>Office Requests</span>
                <?php if ($pending_offices_count > 0): ?>
                    <span class="msg-badge"><?php echo $pending_offices_count; ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="all_offices.php"><i class="fas fa-store"></i> <span>Registered Offices</span></a></li>
            <li><a href="all_users.php"><i class="fas fa-users"></i> <span>Regular Users</span></a></li>
            <li><a href="../user/messenger.php"><i class="fas fa-comment-dots"></i> <span>Messenger</span>
                <?php if ($messages_count > 0): ?>
                    <span class="msg-badge"><?php echo $messages_count; ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
</div>

<div class="main-content">
    <div class="header">
        <h1>
            <img src="../img/logo.jpg" alt="Logo" class="header-logo">
            TravelGram
        </h1>
        <div class="user-info">
            <!-- جرس الإشعارات -->
            <div class="notification-icon" id="bellIcon">
                <i class="fas fa-bell <?php echo ($unread_notifications > 0) ? 'bell-shake' : ''; ?>" id="bellIconInner"></i>
                <?php if ($unread_notifications > 0): ?>
                    <span class="notification-badge" id="notificationBadge"><?php echo $unread_notifications; ?></span>
                <?php endif; ?>
            </div>
            <div class="avatar"><?php echo substr($_SESSION['user_name'] ?? 'A', 0, 1); ?></div>
            <div>
                <strong style="color: #1b1c27;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></strong>
                <div style="font-size:0.8rem; color: #5a6a7a;">Super Admin</div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h2><i class="fas fa-building" style="color: #65403a; margin-right: 10px;"></i>Pending Office Approval Requests</h2>

        <?php if ($message): ?>
            <div class="message"><i class="fas fa-check-circle mr-2"></i><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Office Name</th>
                        <th>Type</th>
                        <th>Contact Email</th>
                        <th>Address</th>
                        <th>Admin Name</th>
                        <th>Admin Email</th>
                        <th>Admin Phone</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo ucfirst($row['type']); ?></td>
                        <td><?php echo htmlspecialchars($row['contact_email']); ?></td>
                        <td><?php echo htmlspecialchars($row['address']); ?></td>
                        <td><?php echo htmlspecialchars($row['admin_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['admin_email']); ?></td>
                        <td><?php echo htmlspecialchars($row['admin_phone']); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                        <td>
                            <a href="?approve=<?php echo $row['id']; ?>" class="btn-sm btn-approve" onclick="return confirm('Approve this office?')"><i class="fas fa-check"></i> Approve</a>
                            <a href="?reject=<?php echo $row['id']; ?>" class="btn-sm btn-reject" onclick="return confirm('Reject and delete this request?')"><i class="fas fa-times"></i> Reject</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: #5a6a7a; text-align: center; padding: 20px;">No pending requests.</p>
        <?php endif; ?>
        
        <div>
            <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
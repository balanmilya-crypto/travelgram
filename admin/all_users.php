<?php
session_start();
include 'connection.php';
include 'auth_check.php'; // التأكد من أن المستخدم سوبر أدمن

// ========== الحصول على معرف السوبر أدمن ==========
$admin_user_id = 0;
$admin_query = mysqli_query($conn, "SELECT id FROM users WHERE role = 'super_admin' LIMIT 1");
if ($admin_query && $admin_row = mysqli_fetch_assoc($admin_query)) {
    $admin_user_id = $admin_row['id'];
    $_SESSION['user_id'] = $admin_user_id;
} else {
    $admin_user_id = 1;
    $_SESSION['user_id'] = 1;
}

$message = '';
$error = '';

// 1. حذف مستخدم
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    $check = "SELECT role FROM users WHERE id = $user_id";
    $res = mysqli_query($conn, $check);
    if ($row = mysqli_fetch_assoc($res)) {
        if ($row['role'] !== 'user') {
            $error = "Only regular users can be deleted from this page.";
        } else {
            $del = "DELETE FROM users WHERE id = $user_id";
            if (mysqli_query($conn, $del)) {
                $message = "User deleted successfully.";
            } else {
                $error = "Error deleting user: " . mysqli_error($conn);
            }
        }
    } else {
        $error = "User not found.";
    }
}

// 2. إرسال تحذير
if (isset($_GET['send_warning'])) {
    $user_id = intval($_GET['send_warning']);
    $check = "SELECT role FROM users WHERE id = $user_id";
    $res = mysqli_query($conn, $check);
    if ($row = mysqli_fetch_assoc($res)) {
        if ($row['role'] !== 'user') {
            $error = "Only regular users can receive warnings.";
        } else {
            $update = "UPDATE users SET warning_count = warning_count + 1 WHERE id = $user_id";
            if (mysqli_query($conn, $update)) {
                $cnt_res = mysqli_query($conn, "SELECT warning_count FROM users WHERE id = $user_id");
                $cnt_row = mysqli_fetch_assoc($cnt_res);
                $new_count = $cnt_row['warning_count'];

                $notif_title = "Warning Received";
                $notif_msg = "You have received a warning from the administrator. Current warnings: $new_count/3. If it reaches 3, your account will be deleted.";
                $ins = "INSERT INTO notifications (user_id, type, title, message, is_read, created_at)
                        VALUES ($user_id, 'user_warning', '$notif_title', '$notif_msg', 0, NOW())";
                mysqli_query($conn, $ins);

                if ($new_count >= 3) {
                    $del_title = "Account Deleted";
                    $del_msg = "Your account has been automatically deleted because you reached 3 warnings.";
                    $ins_del = "INSERT INTO notifications (user_id, type, title, message, is_read, created_at)
                                VALUES ($user_id, 'user_deleted', '$del_title', '$del_msg', 0, NOW())";
                    mysqli_query($conn, $ins_del);
                    if (mysqli_query($conn, "DELETE FROM users WHERE id = $user_id")) {
                        $message = "User deleted automatically after reaching 3 warnings.";
                    } else {
                        $error = "Failed to delete user after warnings: " . mysqli_error($conn);
                    }
                } else {
                    $message = "Warning sent to user. ($new_count/3 warnings)";
                }
            } else {
                $error = "Error updating warning count: " . mysqli_error($conn);
            }
        }
    } else {
        $error = "User not found.";
    }
}

// 3. جلب جميع المستخدمين العاديين (مع الصورة الشخصية)
$sql = "SELECT id, name, email, phone, avatar, warning_count, created_at
        FROM users
        WHERE role = 'user'
        ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

// ========== جلب عدد الإشعارات غير المقروءة (للجرس) ==========
$unread_notifications = 0;
if ($admin_user_id > 0) {
    $notif_query = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = $admin_user_id AND is_read = 0");
    if ($notif_query && $notif_row = mysqli_fetch_assoc($notif_query)) {
        $unread_notifications = $notif_row['cnt'];
    }
}

// ========== جلب عدد الرسائل غير المقروءة (للماسنجر) ==========
$messages_count = 0;
if ($admin_user_id > 0) {
    $msg_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM messages WHERE recipient_id = $admin_user_id AND is_read = 0");
    if ($msg_res && $msg_row = mysqli_fetch_assoc($msg_res)) {
        $messages_count = $msg_row['cnt'];
    }
}

// جلب عدد الرحلات المعلقة
$pending_tours_count = 0;
$pending_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM tours WHERE status = 'pending'");
if ($pending_row = mysqli_fetch_assoc($pending_query)) {
    $pending_tours_count = $pending_row['count'];
}

// جلب عدد طلبات المكاتب المعلقة
$pending_offices_count = 0;
$pending_offices_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM offices WHERE approved = 0");
if ($pending_offices_row = mysqli_fetch_assoc($pending_offices_query)) {
    $pending_offices_count = $pending_offices_row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regular Users - Super Admin | TravelGram</title>
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

        /* خلفية متحركة */
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

        /* أنيميشن اهتزاز الجرس */
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
            width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #9ec1d7;
        }
        .sidebar-header h3 {
            color: white; margin: 0; font-size: 1.5rem; font-weight: 700;
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
        .header-logo {
            width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid #9ec1d7;
        }
        .user-info { display: flex; align-items: center; gap: 25px; }
        .notification-icon { position: relative; cursor: pointer; font-size: 1.5rem; color: #65403a; }
        .notification-badge {
            position: absolute; top: -8px; right: -12px; background: #ed4956; color: white;
            border-radius: 50%; padding: 3px 7px; font-size: 11px; font-weight: bold;
            min-width: 20px; text-align: center;
        }
        .avatar {
            width: 48px; height: 48px; border-radius: 50%; background: #65403a;
            display: flex; align-items: center; justify-content: center; color: white;
            font-weight: bold; font-size: 1.3rem; overflow: hidden; border: 2px solid #9ec1d7;
        }

        /* حاوية البطاقات */
        .cards-wrapper {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(15px);
            border-radius: 30px;
            padding: 30px 35px;
            box-shadow: 0 20px 40px rgba(27, 28, 39, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        h2 {
            font-size: 1.8rem; font-weight: 700; color: #1b1c27; margin-bottom: 25px;
        }

        .message, .error {
            padding: 15px 20px; border-radius: 40px; margin-bottom: 25px; font-size: 0.95rem;
            backdrop-filter: blur(8px);
        }
        .message {
            background: rgba(40, 167, 69, 0.1); color: #155724; border-left: 5px solid #28a745;
        }
        .error {
            background: rgba(220, 53, 69, 0.1); color: #b91c1c; border-left: 5px solid #b91c1c;
        }

        /* شبكة البطاقات */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .user-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            overflow: hidden;
            border: 1px solid rgba(158, 193, 215, 0.3);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .user-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 40px rgba(101, 64, 58, 0.15);
            border-color: #9ec1d7;
            background: rgba(255, 255, 255, 0.85);
        }

        .card-header {
            padding: 20px 20px 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-avatar {
            width: 64px; height: 64px; border-radius: 50%;
            background: #65403a; color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; font-weight: 700;
            border: 2px solid #9ec1d7;
            text-transform: uppercase;
            flex-shrink: 0;
            overflow: hidden;
        }
        .user-avatar img {
            width: 100%; height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        .user-name {
            font-size: 1.2rem; font-weight: 700; color: #1b1c27; line-height: 1.3;
        }
        .user-email {
            font-size: 0.85rem; color: #5a6a7a;
        }

        .card-body {
            padding: 0 20px 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .info-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #5a6a7a;
        }
        .info-row i {
            width: 18px; color: #65403a; text-align: center;
        }

        .status-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 5px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-warning {
            background: #f0ad4e; color: #1b1c27;
        }
        .badge-warning.critical {
            background: #dc3545; color: white;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            margin-top: auto;
            padding-top: 10px;
            border-top: 1px solid rgba(158,193,215,0.2);
        }
        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px; height: 36px; border-radius: 50%;
            text-decoration: none; color: white; transition: 0.2s; font-size: 0.9rem;
            border: none; cursor: pointer;
        }
        .btn-icon:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .btn-warning { background: #fd7e14; }
        .btn-delete { background: #dc3545; }

        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h3, .sidebar-menu span { display: none; }
            .sidebar-menu i { margin-right: 0; }
            .main-content { margin-left: 80px; }
            .cards-wrapper { padding: 20px; }
            .cards-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<!-- Sidebar -->
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
            <li><a href="pending_office.php"><i class="fas fa-building"></i> <span>Office Requests</span>
                <?php if ($pending_offices_count > 0): ?>
                    <span class="msg-badge"><?php echo $pending_offices_count; ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="all_offices.php"><i class="fas fa-store"></i> <span>Registered Offices</span></a></li>
            <li><a href="all_users.php" class="active"><i class="fas fa-users"></i> <span>Regular Users</span></a></li>
            <li><a href="../user/messenger.php"><i class="fas fa-comment-dots"></i> <span>Messenger</span>
                <?php if ($messages_count > 0): ?>
                    <span class="msg-badge"><?php echo $messages_count; ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <h1>
            <img src="../img/logo.jpg" alt="Logo" class="header-logo">
            TravelGram
        </h1>
        <div class="user-info">
            <!-- جرس الإشعارات -->
            <div class="notification-icon">
                <i class="fas fa-bell <?php echo ($unread_notifications > 0) ? 'bell-shake' : ''; ?>"></i>
                <?php if ($unread_notifications > 0): ?>
                    <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                <?php endif; ?>
            </div>
            <div class="avatar"><?php echo substr($_SESSION['user_name'] ?? 'A', 0, 1); ?></div>
            <div>
                <strong style="color: #1b1c27;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></strong>
                <div style="font-size:0.8rem; color: #5a6a7a;">Super Admin</div>
            </div>
        </div>
    </div>

    <div class="cards-wrapper">
        <h2><i class="fas fa-users" style="color: #65403a; margin-right: 10px;"></i>Regular Users</h2>

        <?php if ($message): ?>
            <div class="message"><i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="cards-grid">
                <?php while ($row = mysqli_fetch_assoc($result)): 
                    $initial = mb_substr($row['name'], 0, 1, 'UTF-8');
                    $avatar_url = !empty($row['avatar']) ?  $row['avatar'] : ''; // افتراض أن المسار من مجلد المشروع
                ?>
                    <div class="user-card">
                        <div class="card-header">
                            <div class="user-avatar" title="<?php echo htmlspecialchars($row['name']); ?>">
                                <?php if ($avatar_url): ?>
                                    <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="User Avatar">
                                <?php else: ?>
                                    <?php echo strtoupper($initial); ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="user-name"><?php echo htmlspecialchars($row['name']); ?></div>
                                <div class="user-email"><?php echo htmlspecialchars($row['email']); ?></div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="info-row">
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['phone'] ?: '—'); ?>
                            </div>
                            <div class="info-row">
                                <i class="far fa-calendar-alt"></i> Joined: <?php echo date('Y-m-d', strtotime($row['created_at'])); ?>
                            </div>

                            <div class="status-bar">
                                <span class="badge badge-warning <?php echo ($row['warning_count'] >= 2) ? 'critical' : ''; ?>">
                                    <i class="fas fa-exclamation-triangle"></i> <?php echo $row['warning_count']; ?>/3
                                </span>
                            </div>

                            <div class="action-buttons">
                                <a href="?send_warning=<?php echo $row['id']; ?>" class="btn-icon btn-warning" title="Send Warning" onclick="return confirm('Send a warning to this user?')">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </a>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn-icon btn-delete" title="Delete User" onclick="return confirm('Delete this user? This cannot be undone.')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="color: #5a6a7a; text-align: center; padding: 20px;">No regular users found.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
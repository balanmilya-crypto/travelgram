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

// 1. تبديل حالة الموافقة (approved)
if (isset($_GET['toggle_approve'])) {
    $office_id = intval($_GET['toggle_approve']);
    $sql = "UPDATE offices SET approved = NOT approved WHERE id = $office_id";
    if (mysqli_query($conn, $sql)) {
        $message = "Office approval status updated.";
    } else {
        $error = "Error updating status: " . mysqli_error($conn);
    }
}

// 2. تبديل حالة التمييز (featured)
if (isset($_GET['toggle_featured'])) {
    $office_id = intval($_GET['toggle_featured']);
    $sql = "UPDATE offices SET featured = NOT featured WHERE id = $office_id";
    if (mysqli_query($conn, $sql)) {
        $message = "Office featured status updated.";
    } else {
        $error = "Error updating featured status: " . mysqli_error($conn);
    }
}

// 3. حذف مكتب يدوي
if (isset($_GET['delete'])) {
    $office_id = intval($_GET['delete']);
    $admin_sql = "SELECT id FROM users WHERE office_id = $office_id AND role = 'office_admin'";
    $admin_res = mysqli_query($conn, $admin_sql);
    if ($admin_row = mysqli_fetch_assoc($admin_res)) {
        $admin_id = $admin_row['id'];
        $notif_title = "Office Deleted";
        $notif_msg = "Your office has been deleted by the administrator.";
        $insert = "INSERT INTO notifications (user_id, type, title, message, is_read, created_at)
                   VALUES ($admin_id, 'office_deleted', '$notif_title', '$notif_msg', 0, NOW())";
        mysqli_query($conn, $insert);
    }
    $delete = "DELETE FROM offices WHERE id = $office_id";
    if (mysqli_query($conn, $delete)) {
        $message = "Office deleted successfully.";
    } else {
        $error = "Error deleting office: " . mysqli_error($conn);
    }
}

// 4. إرسال تحذير (زيادة العداد)
if (isset($_GET['send_warning'])) {
    $office_id = intval($_GET['send_warning']);
    $update = "UPDATE offices SET warning_count = warning_count + 1 WHERE id = $office_id";
    if (mysqli_query($conn, $update)) {
        $res = mysqli_query($conn, "SELECT warning_count FROM offices WHERE id = $office_id");
        $row = mysqli_fetch_assoc($res);
        $new_count = $row['warning_count'];

        $admin_sql = "SELECT id FROM users WHERE office_id = $office_id AND role = 'office_admin'";
        $admin_res = mysqli_query($conn, $admin_sql);
        if ($admin_row = mysqli_fetch_assoc($admin_res)) {
            $admin_id = $admin_row['id'];
            $notif_title = "Warning Received";
            $notif_msg = "Your office has received a warning. Current warnings: $new_count/3. If it reaches 3, your office will be deleted.";
            $insert = "INSERT INTO notifications (user_id, type, title, message, is_read, created_at)
                       VALUES ($admin_id, 'office_warning', '$notif_title', '$notif_msg', 0, NOW())";
            mysqli_query($conn, $insert);
        }

        if ($new_count >= 3) {
            if ($admin_row) {
                $notif_title2 = "Office Deleted (Auto)";
                $notif_msg2 = "Your office has been automatically deleted because it reached 3 warnings.";
                $insert2 = "INSERT INTO notifications (user_id, type, title, message, is_read, created_at)
                            VALUES ($admin_id, 'office_auto_deleted', '$notif_title2', '$notif_msg2', 0, NOW())";
                mysqli_query($conn, $insert2);
            }
            mysqli_query($conn, "DELETE FROM offices WHERE id = $office_id");
            $message = "Office deleted automatically (3 warnings reached).";
        } else {
            $message = "Warning sent. Warnings now: $new_count/3.";
        }
    } else {
        $error = "Error sending warning: " . mysqli_error($conn);
    }
}

// حذف أي مكاتب وصلت لـ 3 تحذيرات
mysqli_query($conn, "DELETE FROM offices WHERE warning_count >= 3");

// استعلام لجلب جميع المكاتب مع التقييمات
$sql = "SELECT 
            o.*,
            u.name AS admin_name,
            u.email AS admin_email,
            u.phone AS admin_phone,
            AVG(r.rating) AS avg_rating,
            COUNT(r.id) AS rating_count
        FROM offices o
        LEFT JOIN users u ON o.id = u.office_id AND u.role = 'office_admin'
        LEFT JOIN ratings r ON r.rateable_type = 'office' AND r.rateable_id = o.id
        GROUP BY o.id
        ORDER BY o.created_at DESC";
$result = mysqli_query($conn, $sql);

// ========== جلب عدد الإشعارات غير المقروءة (للجرس) ==========
$unread_notifications = 0;
if ($admin_user_id > 0) {
    $notif_query = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = $admin_user_id AND is_read = 0");
    if ($notif_query && $notif_row = mysqli_fetch_assoc($notif_query)) {
        $unread_notifications = $notif_row['cnt'];
    }
}

// ========== ✅ تصحيح: جلب عدد الرسائل غير المقروءة (للماسنجر) ==========
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
$offices_pending_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM offices WHERE approved = 0");
if ($offices_row = mysqli_fetch_assoc($offices_pending_query)) {
    $pending_offices_count = $offices_row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Offices - Super Admin | TravelGram</title>
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
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 25px;
        }

        .office-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            overflow: hidden;
            border: 1px solid rgba(158, 193, 215, 0.3);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .office-card:hover {
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
        .office-logo {
            width: 64px; height: 64px; border-radius: 50%; object-fit: cover;
            border: 2px solid #9ec1d7; background: rgba(239,241,238,0.5);
            display: flex; align-items: center; justify-content: center; color: #65403a;
            font-size: 1.8rem; flex-shrink: 0;
        }
        .office-logo img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .office-name {
            font-size: 1.2rem; font-weight: 700; color: #1b1c27; line-height: 1.3;
        }
        .office-type {
            font-size: 0.8rem; color: #65403a; background: rgba(101,64,58,0.1);
            padding: 2px 10px; border-radius: 20px; display: inline-block; margin-top: 4px;
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
        .badge-approved { background: rgba(40,167,69,0.1); color: #155724; border: 1px solid #28a745; }
        .badge-pending { background: rgba(255,193,7,0.1); color: #856404; border: 1px solid #ffc107; }
        .badge-featured { background: rgba(23,162,184,0.1); color: #0c5460; border: 1px solid #17a2b8; }
        .badge-warning {
            background: #f0ad4e; color: #1b1c27;
        }
        .badge-warning.critical { background: #dc3545; color: white; }

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
            width: 32px; height: 32px; border-radius: 50%;
            text-decoration: none; color: white; transition: 0.2s; font-size: 0.85rem;
            background: #65403a; /* fallback */
        }
        .btn-icon:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .btn-approve { background: #28a745; }
        .btn-disapprove { background: #ffc107; color: #1b1c27; }
        .btn-featured { background: #17a2b8; }
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
            <li><a href="all_offices.php" class="active"><i class="fas fa-store"></i> <span>Registered Offices</span></a></li>
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
        <h2><i class="fas fa-store" style="color: #65403a; margin-right: 10px;"></i>All Registered Offices</h2>

        <?php if ($message): ?>
            <div class="message"><i class="fas fa-check-circle mr-2"></i><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="cards-grid">
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="office-card">
                        <div class="card-header">
                            <div class="office-logo">
                                <?php if (!empty($row['logo'])): ?>
                                    <img src="../<?php echo htmlspecialchars($row['logo']); ?>" alt="logo">
                                <?php else: ?>
                                    <i class="fas fa-building"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="office-name"><?php echo htmlspecialchars($row['name']); ?></div>
                                <span class="office-type"><?php echo ucfirst($row['type']); ?></span>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($row['contact_email'])): ?>
                                <div class="info-row"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($row['contact_email']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($row['admin_phone'])): ?>
                                <div class="info-row"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['admin_phone']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($row['address'])): ?>
                                <div class="info-row"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($row['address']); ?></div>
                            <?php endif; ?>
                            <div class="info-row"><i class="fas fa-user"></i> <?php echo htmlspecialchars($row['admin_name']); ?></div>
                            <div class="info-row"><i class="fas fa-at"></i> <?php echo htmlspecialchars($row['admin_email']); ?></div>

                            <div class="status-bar">
                                <?php if ($row['approved']): ?>
                                    <span class="badge badge-approved"><i class="fas fa-check"></i> Approved</span>
                                <?php else: ?>
                                    <span class="badge badge-pending"><i class="fas fa-clock"></i> Pending</span>
                                <?php endif; ?>

                                <?php if ($row['featured']): ?>
                                    <span class="badge badge-featured"><i class="fas fa-star"></i> Featured</span>
                                <?php endif; ?>

                                <span class="badge badge-warning <?php echo ($row['warning_count'] >= 2) ? 'critical' : ''; ?>">
                                    <i class="fas fa-exclamation-triangle"></i> <?php echo $row['warning_count']; ?>/3
                                </span>
                            </div>

                            <?php if (!empty($row['avg_rating'])): ?>
                                <div class="info-row" style="margin-top:5px;">
                                    <i class="fas fa-star" style="color:#f0ad4e;"></i>
                                    <?php echo number_format($row['avg_rating'], 1); ?>/5 (<?php echo $row['rating_count']; ?>)
                                </div>
                            <?php endif; ?>

                            <div class="action-buttons">
                                <a href="?toggle_approve=<?php echo $row['id']; ?>" class="btn-icon <?php echo $row['approved'] ? 'btn-disapprove' : 'btn-approve'; ?>" title="<?php echo $row['approved'] ? 'Disapprove' : 'Approve'; ?>" onclick="return confirm('Toggle approval status?')">
                                    <i class="fas <?php echo $row['approved'] ? 'fa-times' : 'fa-check'; ?>"></i>
                                </a>
                                <a href="?toggle_featured=<?php echo $row['id']; ?>" class="btn-icon btn-featured" title="<?php echo $row['featured'] ? 'Remove Featured' : 'Mark Featured'; ?>" onclick="return confirm('Toggle featured status?')">
                                    <i class="fas fa-star"></i>
                                </a>
                                <a href="?send_warning=<?php echo $row['id']; ?>" class="btn-icon btn-warning" title="Send Warning" onclick="return confirm('Send a warning?')">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </a>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn-icon btn-delete" title="Delete Office" onclick="return confirm('Delete this office permanently?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="color: #5a6a7a; text-align: center; padding: 20px;">No offices found.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
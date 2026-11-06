<?php
session_start();
include 'auth_check.php';
include 'connection.php';

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

// ====== إحصائيات أساسية ======
$offices_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM offices"))['count'];
$tours_count   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM tours WHERE status = 'approved'"))['count'];
$users_count   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'user'"))['count'];

// ====== عدد الرحلات المعلقة ======
$pending_tours_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM tours WHERE status = 'pending'"))['count'];

// ====== عدد الرسائل غير المقروءة ======
$messages_count = 0;
if ($admin_user_id > 0) {
    $msg_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM messages WHERE recipient_id = $admin_user_id AND is_read = 0");
    if ($msg_res && $msg_row = mysqli_fetch_assoc($msg_res)) {
        $messages_count = $msg_row['cnt'];
    }
}

// ====== المكاتب المعلقة ======
$pending_offices_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM offices WHERE approved = 0"))['count'];
$pending_admins_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'office_admin' AND is_active = 0"))['count'];

// ====== إنشاء إشعار فوري للمكاتب المعلقة ======
if ($pending_offices_count > 0 && $admin_user_id > 0) {
    $check_existing = mysqli_query($conn, "SELECT id FROM notifications 
                                           WHERE user_id = $admin_user_id 
                                           AND type = 'pending_offices' 
                                           AND is_read = 0
                                           LIMIT 1");
    if (mysqli_num_rows($check_existing) == 0) {
        $title = "⚠️ Pending Office Requests";
        $message = "There are $pending_offices_count office(s) waiting for your approval.";
        $insert = "INSERT INTO notifications (user_id, type, title, message, is_read, created_at) 
                   VALUES ($admin_user_id, 'pending_offices', '$title', '$message', 0, NOW())";
        mysqli_query($conn, $insert);
    }
}

// ====== جلب آخر 10 إشعارات ======
$notifications_list = [];
$notif_query = "SELECT id, title, message, created_at, is_read, type FROM notifications 
                WHERE user_id = $admin_user_id ORDER BY created_at DESC LIMIT 10";
$notif_res = mysqli_query($conn, $notif_query);
if ($notif_res) {
    while ($row = mysqli_fetch_assoc($notif_res)) {
        $notifications_list[] = $row;
    }
}

// ====== عدد الإشعارات غير المقروءة ======
$unread_notifications = 0;
foreach ($notifications_list as $n) {
    if (!$n['is_read']) $unread_notifications++;
}

// ====== أحدث 5 جولات ======
$recent_tours_sql = "SELECT t.id, t.title, t.main_image, t.price, t.currency, t.created_at, o.name AS office_name
                     FROM tours t LEFT JOIN offices o ON t.office_id = o.id
                     WHERE t.status = 'approved'
                     ORDER BY t.created_at DESC LIMIT 5";
$recent_tours_result = mysqli_query($conn, $recent_tours_sql);

// ====== أفضل المكاتب تقييمًا ======
$top_offices_sql = "SELECT o.id, o.name, o.logo, AVG(r.rating) AS avg_rating, COUNT(r.id) AS rating_count
                    FROM offices o 
                    LEFT JOIN ratings r ON r.rateable_type = 'office' AND r.rateable_id = o.id
                    WHERE o.approved = 1
                    GROUP BY o.id 
                    HAVING avg_rating IS NOT NULL 
                    ORDER BY avg_rating DESC LIMIT 5";
$top_offices_result = mysqli_query($conn, $top_offices_sql);

// ====== أفضل الرحلات تقييمًا ======
$top_tours_sql = "SELECT t.id, t.title, t.main_image, t.price, t.currency, 
                         AVG(r.rating) AS avg_rating, COUNT(r.id) AS rating_count,
                         o.name AS office_name
                  FROM tours t
                  LEFT JOIN ratings r ON r.rateable_type = 'tour' AND r.rateable_id = t.id
                  LEFT JOIN offices o ON t.office_id = o.id
                  WHERE t.status = 'approved'
                  GROUP BY t.id
                  HAVING avg_rating IS NOT NULL
                  ORDER BY avg_rating DESC, rating_count DESC
                  LIMIT 5";
$top_tours_result = mysqli_query($conn, $top_tours_sql);

// ====== معالجة طلب AJAX (Mark as Read / Delete) ======
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    if (isset($_POST['mark_read_id'])) {
        $notif_id = intval($_POST['mark_read_id']);
        if ($admin_user_id > 0) {
            $update = "UPDATE notifications SET is_read = 1 WHERE id = $notif_id AND user_id = $admin_user_id";
            if (mysqli_query($conn, $update)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        }
        exit();
    }
    
    if (isset($_POST['delete_id'])) {
        $notif_id = intval($_POST['delete_id']);
        if ($admin_user_id > 0) {
            $delete = "DELETE FROM notifications WHERE id = $notif_id AND user_id = $admin_user_id";
            if (mysqli_query($conn, $delete)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        }
        exit();
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit();
}

// دالة رابط الإشعار
function getNotificationLink($type) {
    switch ($type) {
        case 'new_tour_pending': return 'pending_tours.php';
        case 'pending_offices':
        case 'new_office_pending': return 'pending_office.php';
        default: return '#';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Dashboard - TravelGram</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
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
        .sidebar-header img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #9ec1d7; }
        .sidebar-header h3 { color: white; margin: 0; font-size: 1.5rem; font-weight: 700; }
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

        .main-content { flex: 1; margin-left: 270px; padding: 25px 30px; position: relative; z-index: 5; }

        .header {
            display: flex; justify-content: space-between; align-items: center;
            background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(15px);
            padding: 15px 30px; border-radius: 40px; margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 10px 25px rgba(27, 28, 39, 0.05);
        }
        .header h1 { display: flex; align-items: center; gap: 12px; font-size: 1.8rem; color: #1b1c27; font-weight: 700; }
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

        .stats-container {
            display: grid; grid-template-columns: repeat(5,1fr); gap: 20px; margin-bottom: 35px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(10px);
            padding: 20px; border-radius: 25px; text-align: center;
            border: 1px solid rgba(158, 193, 215, 0.3); transition: 0.3s;
            position: relative; overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-8px); box-shadow: 0 20px 35px rgba(101, 64, 58, 0.15); }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 5px; background: linear-gradient(90deg, #65403a, #9ec1d7); }
        .stat-icon { font-size: 2rem; margin-bottom: 10px; color: #65403a; }
        .stat-number { font-size: 1.8rem; font-weight: 800; display: block; margin-bottom: 5px; color: #1b1c27; }
        .stat-label { font-size: 0.85rem; color: #5a6a7a; font-weight: 600; }

        .dashboard-sections { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .left-column, .right-column { display: flex; flex-direction: column; gap: 25px; }

        .section {
            background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px);
            padding: 25px; border-radius: 30px; border: 1px solid rgba(158, 193, 215, 0.3);
        }
        .section h2 {
            font-size: 1.3rem; border-bottom: 2px solid rgba(158, 193, 215, 0.3);
            padding-bottom: 12px; margin-bottom: 20px; display: flex; align-items: center;
            gap: 10px; color: #1b1c27; font-weight: 700;
        }
        .section h2 i { color: #65403a; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(158, 193, 215, 0.2); }
        th { background: rgba(101, 64, 58, 0.05); color: #1b1c27; font-weight: 700; }
        .tour-image { width: 55px; height: 40px; object-fit: cover; border-radius: 8px; }

        .btn {
            display: inline-block; padding: 10px 20px; background: #65403a; color: white;
            border-radius: 40px; text-decoration: none; font-weight: 600; transition: 0.3s;
            box-shadow: 0 5px 15px rgba(101, 64, 58, 0.2); border: none; cursor: pointer;
        }
        .btn-block { display: block; width: 100%; margin-top: 20px; text-align: center; }
        .btn:hover { background: #4f322c; transform: translateY(-2px); }

        .pending-stats {
            display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;
        }
        .pending-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 8px 0; border-bottom: 1px dashed rgba(158,193,215,0.3);
        }
        .pending-item span:first-child { color: #1b1c27; }
        .pending-item span:last-child { font-size: 1.5rem; font-weight: 700; color: #65403a; }

        .notification-dropdown {
            position: fixed; width: 380px; max-width: 90vw; background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px); border-radius: 25px; box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            z-index: 99999; display: none; overflow: hidden; border: 1px solid rgba(158, 193, 215, 0.5);
        }
        .notification-dropdown.show { display: block; }
        .dropdown-header {
            padding: 18px 20px; background: #65403a; color: white;
            font-weight: 700; font-size: 1.1rem; display: flex; justify-content: space-between;
        }
        .dropdown-list { max-height: 450px; overflow-y: auto; }
        .notification-item {
            padding: 16px 20px; border-bottom: 1px solid rgba(158, 193, 215, 0.2);
            cursor: pointer; transition: 0.2s;
        }
        .notification-item.unread { background: rgba(101, 64, 58, 0.05); border-left: 5px solid #65403a; }
        .notification-title { font-weight: 700; font-size: 1rem; color: #1b1c27; margin-bottom: 4px; }
        .notification-message { font-size: 0.9rem; color: #5a6a7a; margin-bottom: 8px; }
        .notification-date { font-size: 0.75rem; color: #7a8a9a; margin-bottom: 10px; }
        .notification-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .mark-read-btn, .delete-notif-btn, .review-link {
            font-size: 0.75rem; padding: 5px 12px; background: #65403a; color: white;
            border: none; border-radius: 30px; cursor: pointer; text-decoration: none;
            font-weight: 600; display: inline-flex; align-items: center; gap: 4px; transition: 0.2s;
        }
        .delete-notif-btn { background: #dc3545; }
        .review-link { background: #9ec1d7; color: #1b1c27; }
        .mark-read-btn:hover { background: #4f322c; }
        .delete-notif-btn:hover { background: #b02a37; }
        .review-link:hover { background: #7baec7; }

        @media (max-width:768px) {
            .sidebar { width: 80px; }
            .sidebar-header h3, .sidebar-menu span { display: none; }
            .sidebar-menu i { margin-right: 0; }
            .main-content { margin-left: 80px; padding: 20px; }
            .stats-container { grid-template-columns: 1fr; }
            .dashboard-sections { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<div class="sidebar">
    <div class="sidebar-header"><img src="../img/logo.jpg" alt="Logo"><h3>TravelGram</h3></div>
    <div class="sidebar-menu">
        <ul>
            <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="all_tours.php"><i class="fas fa-plane"></i> <span>All Tours</span></a></li>
            <li><a href="pending_tours.php"><i class="fas fa-clock"></i> <span>Pending Tours</span>
                <?php if ($pending_tours_count > 0): ?><span class="msg-badge"><?php echo $pending_tours_count; ?></span><?php endif; ?>
            </a></li>
            <li><a href="pending_office.php"><i class="fas fa-building"></i> <span>Office Requests</span>
                <?php if ($pending_offices_count > 0): ?><span class="msg-badge"><?php echo $pending_offices_count; ?></span><?php endif; ?>
            </a></li>
            <li><a href="all_offices.php"><i class="fas fa-store"></i> <span>Registered Offices</span></a></li>
            <li><a href="all_users.php"><i class="fas fa-users"></i> <span>Regular Users</span></a></li>
            <li><a href="../user/messenger.php"><i class="fas fa-comment-dots"></i> <span>Messenger</span>
                <?php if ($messages_count > 0): ?><span class="msg-badge"><?php echo $messages_count; ?></span><?php endif; ?>
            </a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
</div>

<div class="main-content">
    <div class="header">
        <h1><img src="../img/logo.jpg" class="header-logo"> TravelGram</h1>
        <div class="user-info">
            <div class="notification-icon" id="bellIcon">
                <i class="fas fa-bell <?php echo ($unread_notifications > 0) ? 'bell-shake' : ''; ?>" id="bellIconInner"></i>
                <?php if ($unread_notifications > 0): ?>
                    <span class="notification-badge" id="notificationBadge"><?php echo $unread_notifications; ?></span>
                <?php endif; ?>
            </div>
            <div class="avatar"><?php echo substr($_SESSION['user_name'] ?? 'A', 0, 1); ?></div>
            <div>
                <strong style="color: #1b1c27;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></strong>
                <div style="font-size:0.8rem; color:#5a6a7a;">Super Admin</div>
            </div>
        </div>
    </div>

    <div class="stats-container">
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-store"></i></div><span class="stat-number"><?php echo $offices_count; ?></span><span class="stat-label">Registered Offices</span></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-plane"></i></div><span class="stat-number"><?php echo $tours_count; ?></span><span class="stat-label">Active Tours</span></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-clock"></i></div><span class="stat-number"><?php echo $pending_tours_count; ?></span><span class="stat-label">Pending Tours</span></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-users"></i></div><span class="stat-number"><?php echo $users_count; ?></span><span class="stat-label">Regular Users</span></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-envelope"></i></div><span class="stat-number"><?php echo $messages_count; ?></span><span class="stat-label">Unread Messages</span></div>
    </div>

    <div class="dashboard-sections">
        <!-- العمود الأيسر -->
        <div class="left-column">
            <div class="section">
                <h2><i class="fas fa-clock"></i> Latest 5 Tours</h2>
                <?php if (mysqli_num_rows($recent_tours_result) > 0): ?>
                <table>
                    <thead><tr><th>Image</th><th>Tour</th><th>Office</th><th>Price</th></tr></thead>
                    <tbody>
                    <?php while($tour = mysqli_fetch_assoc($recent_tours_result)): ?>
                    <tr>
                        <td><?php if(!empty($tour['main_image'])): ?><img src="../<?php echo $tour['main_image']; ?>" class="tour-image"><?php else: ?><div style="width:55px;height:40px;background:rgba(239,241,238,0.5);border-radius:8px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-image" style="color:#65403a;"></i></div><?php endif; ?></td>
                        <td><?php echo htmlspecialchars($tour['title']); ?></td>
                        <td><?php echo htmlspecialchars($tour['office_name']); ?></td>
                        <td><?php echo number_format($tour['price'],0); ?> <?php echo $tour['currency']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?><p style="color:#5a6a7a;">No tours yet.</p><?php endif; ?>
                <a href="all_tours.php" class="btn btn-block">View All Tours</a>
            </div>
        </div>

        <!-- العمود الأيمن -->
        <div class="right-column">
            <div class="section">
                <h2><i class="fas fa-trophy"></i> Top Rated Tours</h2>
                <?php if (mysqli_num_rows($top_tours_result) > 0): ?>
                <ul style="list-style:none;">
                    <?php while($tour = mysqli_fetch_assoc($top_tours_result)): ?>
                    <li style="display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-bottom:1px solid rgba(158,193,215,0.2);">
                        <span style="display:flex; gap:10px; align-items:center;">
                            <?php if(!empty($tour['main_image'])): ?><img src="../<?php echo $tour['main_image']; ?>" style="width:35px;height:35px;border-radius:8px;object-fit:cover;border:2px solid #9ec1d7;"><?php else: ?><div style="width:35px;height:35px;background:#65403a;border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;"><i class="fas fa-image"></i></div><?php endif; ?>
                            <div>
                                <strong style="color:#1b1c27;"><?php echo htmlspecialchars($tour['title']); ?></strong>
                                <div style="font-size:0.8rem; color:#5a6a7a;"><?php echo htmlspecialchars($tour['office_name']); ?></div>
                            </div>
                        </span>
                        <span style="color:#f0ad4e; font-weight:600;"><?php echo number_format($tour['avg_rating'],1); ?>/5 <small>(<?php echo $tour['rating_count']; ?>)</small></span>
                    </li>
                    <?php endwhile; ?>
                </ul>
                <?php else: ?><p style="color:#5a6a7a;">No ratings yet.</p><?php endif; ?>
            </div>

            <div class="section">
                <h2><i class="fas fa-hourglass-half"></i> Pending Requests</h2>
                <div class="pending-stats">
                    <div class="pending-item"><span>Offices awaiting approval:</span><span><?php echo $pending_offices_count; ?></span></div>
                    <div class="pending-item"><span>Inactive office admins:</span><span><?php echo $pending_admins_count; ?></span></div>
                </div>
                <a href="pending_office.php" class="btn btn-block">Review Requests</a>
            </div>
        </div>
    </div>
</div>

<!-- قائمة الإشعارات المنسدلة -->
<div class="notification-dropdown" id="notificationDropdown">
    <div class="dropdown-header">
        <span>Notifications</span>
        <?php if (count($notifications_list) > 0): ?>
            <span style="font-size:0.8rem; opacity:0.9;"><?php echo count($notifications_list); ?> items</span>
        <?php endif; ?>
    </div>
    <div class="dropdown-list">
        <?php if (count($notifications_list) > 0): ?>
            <?php foreach ($notifications_list as $notif): 
                $link = getNotificationLink($notif['type']);
            ?>
                <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo $notif['id']; ?>" data-link="<?php echo $link; ?>">
                    <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                    <div class="notification-message"><?php echo nl2br(htmlspecialchars($notif['message'])); ?></div>
                    <div class="notification-date"><i class="far fa-clock"></i> <?php echo date('Y-m-d H:i', strtotime($notif['created_at'])); ?></div>
                    <div class="notification-actions">
                        <?php if (!$notif['is_read']): ?>
                            <button type="button" class="mark-read-btn" data-id="<?php echo $notif['id']; ?>"><i class="fas fa-check"></i> Read</button>
                        <?php endif; ?>
                        <button type="button" class="delete-notif-btn" data-id="<?php echo $notif['id']; ?>"><i class="fas fa-trash-alt"></i> Delete</button>
                        <?php if ($link != '#'): ?>
                            <a href="<?php echo $link; ?>" class="review-link"><i class="fas fa-arrow-right"></i> Go</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="padding:25px; text-align:center; color:#5a6a7a;">
                <i class="fas fa-bell-slash" style="font-size:2rem; opacity:0.5; margin-bottom:10px;"></i>
                <p>No notifications</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    var $dropdown = $('#notificationDropdown');
    var $bell = $('#bellIcon');
    
    function positionDropdown() {
        var bellOffset = $bell.offset();
        var bellHeight = $bell.outerHeight();
        $dropdown.css({
            top: (bellOffset.top + bellHeight + 10) + 'px',
            right: ($(window).width() - (bellOffset.left + $bell.outerWidth())) + 'px'
        });
    }
    
    $bell.click(function(e) {
        e.stopPropagation();
        positionDropdown();
        $dropdown.toggleClass('show');
    });
    
    $(window).resize(function() {
        if ($dropdown.hasClass('show')) positionDropdown();
    });
    
    $(document).click(function(e) {
        if (!$(e.target).closest('#bellIcon').length && !$(e.target).closest('#notificationDropdown').length) {
            $dropdown.removeClass('show');
        }
    });
    
    // تعليم كمقروء
    $('.mark-read-btn').click(function(e) {
        e.preventDefault();
        var $btn = $(this);
        var notifId = $btn.data('id');
        var $item = $btn.closest('.notification-item');
        
        $.ajax({
            url: 'dashboard.php',
            type: 'POST',
            data: { mark_read_id: notifId },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $item.removeClass('unread');
                    $btn.remove();
                    updateBadge(-1);
                } else {
                    alert('Error: ' + (res.error || 'Unknown error'));
                }
            },
            error: function() {
                alert('Failed to communicate with server.');
            }
        });
    });
    
    // حذف إشعار
    $('.delete-notif-btn').click(function(e) {
        e.preventDefault();
        if (!confirm('Delete this notification permanently?')) return;
        var $btn = $(this);
        var notifId = $btn.data('id');
        var $item = $btn.closest('.notification-item');
        var wasUnread = $item.hasClass('unread');
        
        $.ajax({
            url: 'dashboard.php',
            type: 'POST',
            data: { delete_id: notifId },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $item.fadeOut(300, function() { $(this).remove(); });
                    if (wasUnread) updateBadge(-1);
                } else {
                    alert('Error: ' + (res.error || 'Unknown error'));
                }
            },
            error: function() {
                alert('Failed to communicate with server.');
            }
        });
    });
    
    function updateBadge(change) {
        var $badge = $('#notificationBadge');
        if (!$badge.length) return;
        var cur = parseInt($badge.text()) || 0;
        cur += change;
        if (cur > 0) {
            $badge.text(cur);
        } else {
            $badge.remove();
            $('#bellIconInner').removeClass('bell-shake');
        }
    }
    
    // النقر على الإشعار للذهاب إلى الرابط
    $('.notification-item').click(function(e) {
        if ($(e.target).closest('button, a').length) return;
        var link = $(this).data('link');
        if (link && link != '#') {
            window.location.href = link;
        }
    });
});
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
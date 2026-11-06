<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'office_admin') {
    header('Location: ../login.php');
    exit();
}

$office_id = isset($_SESSION['user_office_id']) ? intval($_SESSION['user_office_id']) : 0;
if ($office_id == 0) die("Office ID missing.");

$office = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name, type, logo FROM offices WHERE id = $office_id"));
if (!$office) die("Office not found.");
$office_name = $office['name'];
$office_type = $office['type'];
$office_logo = $office['logo'] ?? '';
$type_label = match($office_type) {
    'tourism' => 'Tourism',
    'scholarship' => 'Scholarship',
    'both' => 'Tourism & Scholarship',
    default => 'Office'
};
$admin_name = $_SESSION['user_name'] ?? 'Admin';

$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id == 0) {
    $u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE office_id = $office_id AND role = 'office_admin'"));
    if ($u) {
        $user_id = $u['id'];
        $_SESSION['user_id'] = $user_id;
    } else {
        die("User not found.");
    }
}

// ========== إنشاء جدول متابعة آخر مشاهدة (للتعليقات والتقييمات) ==========
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS office_last_viewed (
    user_id INT PRIMARY KEY,
    comments_last_viewed DATETIME NULL,
    ratings_last_viewed DATETIME NULL
)");

// ========== معالجة AJAX ==========
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    if ($user_id == 0) {
        echo json_encode(['success' => false, 'error' => 'User not identified']);
        exit();
    }
    if (isset($_POST['mark_read_id'])) {
        $notif_id = intval($_POST['mark_read_id']);
        $update = "UPDATE notifications SET is_read = 1 WHERE id = $notif_id AND user_id = $user_id";
        if (mysqli_query($conn, $update)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
        }
        exit();
    }
    if (isset($_POST['delete_id'])) {
        $notif_id = intval($_POST['delete_id']);
        $delete = "DELETE FROM notifications WHERE id = $notif_id AND user_id = $user_id";
        if (mysqli_query($conn, $delete)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
        }
        exit();
    }
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit();
}

// ========== إحصائيات عامة ==========
$tours_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM tours WHERE office_id=$office_id"))['cnt'];
$stations_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM tour_stations ts JOIN tours t ON ts.tour_id=t.id WHERE t.office_id=$office_id"))['cnt'];
$bookings_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM bookings WHERE office_id=$office_id"))['cnt'];
$revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_price) as total FROM bookings WHERE office_id=$office_id AND status='confirmed'"))['total'] ?? 0;

// ========== عدد الرحلات المعلقة (بدون بادج في القائمة) ==========
$pending_tours_count = 0;
$p_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM tours WHERE office_id=$office_id AND status='pending'");
if ($p_res && $p_row = mysqli_fetch_assoc($p_res)) $pending_tours_count = $p_row['cnt'];

// ========== التعليقات الجديدة غير المقروءة ==========
$last_comment_view = '1970-01-01 00:00:00';
$lv = mysqli_query($conn, "SELECT comments_last_viewed FROM office_last_viewed WHERE user_id = $user_id");
if ($lv && $lv_row = mysqli_fetch_assoc($lv)) {
    if ($lv_row['comments_last_viewed']) $last_comment_view = $lv_row['comments_last_viewed'];
}
$comments_count = 0;
$c_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM post_comments pc JOIN tours t ON pc.commentable_id = t.id AND pc.commentable_type = 'tour' WHERE t.office_id = $office_id AND pc.created_at > '$last_comment_view'");
if ($c_res && $c_row = mysqli_fetch_assoc($c_res)) $comments_count = $c_row['cnt'];

// ========== التقييمات الجديدة غير المقروءة ==========
$last_rating_view = '1970-01-01 00:00:00';
$lv2 = mysqli_query($conn, "SELECT ratings_last_viewed FROM office_last_viewed WHERE user_id = $user_id");
if ($lv2 && $lv2_row = mysqli_fetch_assoc($lv2)) {
    if ($lv2_row['ratings_last_viewed']) $last_rating_view = $lv2_row['ratings_last_viewed'];
}
$ratings_count = 0;
$r_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM ratings r JOIN tours t ON r.rateable_type='tour' AND r.rateable_id=t.id WHERE t.office_id=$office_id AND r.created_at > '$last_rating_view'");
if ($r_res && $r_row = mysqli_fetch_assoc($r_res)) $ratings_count = $r_row['cnt'];

// ========== الحجوزات المعلقة (عداد ثابت) ==========
$pending_bookings_count = 0;
$pb_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM bookings WHERE office_id=$office_id AND status='pending'");
if ($pb_res && $pb_row = mysqli_fetch_assoc($pb_res)) $pending_bookings_count = $pb_row['cnt'];

// ========== إشعارات غير مقروءة ==========
$unread_notifications = 0;
$q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = $user_id AND is_read = 0");
if ($q && $row = mysqli_fetch_assoc($q)) $unread_notifications = $row['cnt'];

// ========== رسائل غير مقروءة ==========
$unread_messages = 0;
$msg_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM messages WHERE recipient_id = $user_id AND is_read = 0");
if ($msg_res && $msg_row = mysqli_fetch_assoc($msg_res)) $unread_messages = $msg_row['cnt'];

// ========== توليد إشعار للحجوزات الجديدة (إذا لم يوجد إشعار غير مقروء) ==========
$new_bookings_count = 0;
$last_notif_res = mysqli_query($conn, "SELECT created_at FROM notifications WHERE user_id=$user_id AND type='new_booking' ORDER BY created_at DESC LIMIT 1");
if ($last_notif_res && mysqli_num_rows($last_notif_res) > 0) {
    $last_row = mysqli_fetch_assoc($last_notif_res);
    $last_time = strtotime($last_row['created_at']);
} else {
    $last_time = strtotime('-1 hour');
}
$nb_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM bookings WHERE office_id=$office_id AND created_at > FROM_UNIXTIME($last_time)");
if ($nb_res && $nb_row = mysqli_fetch_assoc($nb_res)) $new_bookings_count = $nb_row['cnt'];
if ($new_bookings_count > 0) {
    $check_unread = mysqli_query($conn, "SELECT id FROM notifications WHERE user_id=$user_id AND type='new_booking' AND is_read=0 LIMIT 1");
    if (mysqli_num_rows($check_unread) == 0) {
        $title = "📅 New Booking(s) Received";
        $message = "You have $new_bookings_count new booking(s).";
        mysqli_query($conn, "INSERT INTO notifications (user_id, type, title, message, is_read, created_at) 
                   VALUES ($user_id, 'new_booking', '$title', '$message', 0, NOW())");
        $unread_notifications++;
    }
}

// ========== أحدث الرحلات المعتمدة ==========
$recent_tours = mysqli_query($conn, "SELECT id, title, price, currency, created_at, main_image FROM tours WHERE office_id=$office_id AND status='approved' ORDER BY created_at DESC LIMIT 5");

// ========== أفضل الرحلات تقييماً ==========
$top_tours = mysqli_query($conn, "SELECT t.id, t.title, t.main_image, AVG(r.rating) AS avg_rating, COUNT(r.id) AS rating_count FROM tours t LEFT JOIN ratings r ON r.rateable_type='tour' AND r.rateable_id=t.id WHERE t.office_id=$office_id AND t.status='approved' GROUP BY t.id HAVING avg_rating IS NOT NULL ORDER BY avg_rating DESC LIMIT 5");

// ========== قائمة الإشعارات (آخر 10) ==========
$notifications_list = mysqli_query($conn, "SELECT id, title, message, created_at, is_read, type, data FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 10");

function getNotificationLink($type, $data) {
    $json = json_decode($data, true);
    switch ($type) {
        case 'new_booking': return 'view_bookings.php';
        case 'tour_approved': return isset($json['tour_id']) ? 'view_tour.php?id=' . intval($json['tour_id']) : '#';
        case 'tour_rejected': return 'add_tour.php';
        default: return '#';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TravelGram - Office Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Cairo', 'Poppins', sans-serif;
            background: #eff1ee;
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

        .main-content {
            flex: 1;
            margin-left: 270px;
            padding: 30px;
            position: relative;
            z-index: 5;
        }

        .header {
            display: flex; justify-content: space-between; align-items: center;
            background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(15px);
            padding: 18px 30px; border-radius: 40px; margin-bottom: 35px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 10px 25px rgba(27, 28, 39, 0.05);
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
        .avatar img { width: 100%; height: 100%; object-fit: cover; }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(5,1fr);
            gap: 25px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 30px;
            text-align: center;
            border: 1px solid rgba(158, 193, 215, 0.3);
            transition: 0.3s;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 35px rgba(101, 64, 58, 0.15);
            background: rgba(255, 255, 255, 0.85);
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #65403a, #9ec1d7);
        }
        .stat-icon { font-size: 2.8rem; margin-bottom: 15px; color: #65403a; }
        .stat-number { font-size: 2.4rem; font-weight: 800; display: block; margin-bottom: 5px; color: #1b1c27; }
        .stat-label { font-size: 0.95rem; color: #5a6a7a; font-weight: 600; }

        .dashboard-sections {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        .section {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 30px;
            border: 1px solid rgba(158, 193, 215, 0.3);
        }
        .section h2 {
            font-size: 1.4rem;
            border-bottom: 2px solid rgba(158, 193, 215, 0.3);
            padding-bottom: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #1b1c27;
            font-weight: 700;
        }
        .section h2 i { color: #65403a; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(158, 193, 215, 0.2); }
        th { background: rgba(101, 64, 58, 0.05); color: #1b1c27; font-weight: 700; }
        tr:hover td { background: rgba(239, 241, 238, 0.5); }
        .tour-thumb { width: 55px; height: 40px; object-fit: cover; border-radius: 10px; }
        .no-image {
            width: 55px; height: 40px;
            background: rgba(239, 241, 238, 0.5);
            display: flex; align-items: center; justify-content: center;
            border-radius: 10px; color: #65403a;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #65403a;
            color: white;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            box-shadow: 0 5px 15px rgba(101, 64, 58, 0.2);
            border: none;
            cursor: pointer;
        }
        .btn-block { display: block; width: 100%; text-align: center; margin-top: 20px; }
        .btn-sm { padding: 5px 10px; font-size: 0.75rem; }
        .btn:hover {
            background: #4f322c;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(101, 64, 58, 0.25);
        }
        .rating-stars { color: #f0ad4e; font-weight: 600; }

        @media (max-width:768px) {
            .sidebar { width: 80px; }
            .sidebar-header h3, .sidebar-menu span { display: none; }
            .sidebar-menu i { margin-right: 0; }
            .main-content { margin-left: 80px; }
            .stats-container { grid-template-columns: 1fr; }
            .dashboard-sections { grid-template-columns: 1fr; }
        }

        .notification-dropdown {
            position: fixed;
            width: 380px;
            max-width: 90vw;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            z-index: 99999;
            display: none;
            overflow: hidden;
            border: 1px solid rgba(158, 193, 215, 0.5);
        }
        .notification-dropdown.show { display: block; }
        .dropdown-header {
            padding: 18px 20px;
            background: #65403a;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dropdown-list { max-height: 450px; overflow-y: auto; }
        .notification-item {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(158, 193, 215, 0.2);
            cursor: pointer;
            transition: 0.2s;
        }
        .notification-item.unread { background: rgba(101, 64, 58, 0.05); border-left: 5px solid #65403a; }
        .notification-title { font-weight: 700; font-size: 1rem; color: #1b1c27; margin-bottom: 4px; }
        .notification-message { font-size: 0.9rem; color: #5a6a7a; margin-bottom: 8px; }
        .notification-date { font-size: 0.75rem; color: #7a8a9a; margin-bottom: 10px; }
        .notification-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .mark-read-btn, .delete-notif-btn, .review-link {
            font-size: 0.8rem;
            padding: 5px 12px;
            background: #65403a;
            color: white;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: 0.2s;
        }
        .delete-notif-btn { background: #dc3545; }
        .review-link { background: #9ec1d7; color: #1b1c27; }
        .mark-read-btn:hover { background: #4f322c; }
        .delete-notif-btn:hover { background: #b02a37; }
        .review-link:hover { background: #7baec7; }
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
            <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="add_tour.php"><i class="fas fa-plus-circle"></i> <span>Add Tour</span></a></li>
            <li><a href="view_tour.php"><i class="fas fa-list"></i> <span>Manage Tours</span></a></li>
            <!-- رابط الرحلات المعلقة بدون بادج -->
            <li><a href="pending_tours.php"><i class="fas fa-clock"></i> <span>Pending Tours</span></a></li>
            <!-- التعليقات مع عداد الجديد غير المقروء -->
            <li><a href="view_comments.php?tour_id=0"><i class="fas fa-comments"></i> <span>Comments</span>
                
            </a></li>
            <!-- الحجوزات مع عداد المعلقة -->
            <li><a href="view_bookings.php"><i class="fas fa-shopping-cart"></i> <span>Bookings</span>
                <?php if ($pending_bookings_count > 0): ?><span class="msg-badge"><?php echo $pending_bookings_count; ?></span><?php endif; ?>
            </a></li>
            <!-- التقييمات مع عداد الجديد غير المقروء -->
            <li><a href="view_ratings.php"><i class="fas fa-star"></i> <span>Ratings</span>
              
            </a></li>
            <li><a href="../user/messenger.php"><i class="fas fa-envelope"></i> <span>Messenger</span>
                <?php if ($unread_messages > 0): ?><span class="msg-badge"><?php echo $unread_messages; ?></span><?php endif; ?>
            </a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
</div>

<!-- Main Content -->
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
            <div class="avatar">
                <?php if (!empty($office_logo)): ?>
                    <img src="../<?php echo htmlspecialchars($office_logo); ?>" alt="Office Logo">
                <?php else: ?>
                    <?php echo substr($admin_name,0,1); ?>
                <?php endif; ?>
            </div>
            <div>
                <strong style="color:#1b1c27;"><?php echo htmlspecialchars($admin_name); ?></strong>
                <div style="font-size:0.8rem; color:#5a6a7a;">Office Admin – <?php echo $type_label; ?></div>
                <div style="font-size:0.75rem; color:#7a8a9a;"><?php echo htmlspecialchars($office_name); ?></div>
            </div>
        </div>
    </div>

    <div class="stats-container">
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-plane"></i></div><span class="stat-number"><?php echo $tours_count; ?></span><span class="stat-label">My Tours</span></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-clock"></i></div><span class="stat-number"><?php echo $pending_tours_count; ?></span><span class="stat-label">Pending Tours</span></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-map-marker-alt"></i></div><span class="stat-number"><?php echo $stations_count; ?></span><span class="stat-label">Stations</span></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-shopping-cart"></i></div><span class="stat-number"><?php echo $bookings_count; ?></span><span class="stat-label">Bookings</span></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-dollar-sign"></i></div><span class="stat-number"><?php echo number_format($revenue,2); ?>$</span><span class="stat-label">Confirmed Revenue</span></div>
    </div>

    <div class="dashboard-sections">
        <div class="section">
            <h2><i class="fas fa-clock"></i> Recent Approved Tours</h2>
            <?php if (mysqli_num_rows($recent_tours) > 0): ?>
            <table>
                <thead><tr><th>Image</th><th>Title</th><th>Price</th><th>Currency</th><th>Added</th><th></th></tr></thead>
                <tbody>
                <?php while($tour = mysqli_fetch_assoc($recent_tours)): ?>
                <tr>
                    <td><?php if(!empty($tour['main_image'])): ?><img src="../<?php echo $tour['main_image']; ?>" class="tour-thumb"><?php else: ?><div class="no-image"><i class="fas fa-image"></i></div><?php endif; ?></td>
                    <td><?php echo htmlspecialchars($tour['title']); ?></td>
                    <td><?php echo number_format($tour['price'],2); ?></td>
                    <td><?php echo $tour['currency']; ?></td>
                    <td><?php echo date('Y-m-d',strtotime($tour['created_at'])); ?></td>
                    <td><a href="view_tour.php?id=<?php echo $tour['id']; ?>" class="btn btn-sm">View</a></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?><p style="color:#5a6a7a;">No approved tours yet.</p><?php endif; ?>
            <a href="add_tour.php" class="btn btn-block">+ Add New Tour</a>
        </div>

        <div>
            <div class="section">
                <h2><i class="fas fa-trophy"></i> Top Rated Tours</h2>
                <?php if(mysqli_num_rows($top_tours)>0): ?>
                <ul style="list-style:none;">
                    <?php while($tour = mysqli_fetch_assoc($top_tours)): ?>
                    <li style="display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-bottom:1px solid rgba(158,193,215,0.2);">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <?php if(!empty($tour['main_image'])): ?><img src="../<?php echo $tour['main_image']; ?>" style="width:45px; height:40px; object-fit:cover; border-radius:10px;"><?php else: ?><div style="width:45px; height:40px; background:rgba(239,241,238,0.5); display:flex; align-items:center; justify-content:center; border-radius:10px;"><i class="fas fa-image" style="color:#65403a;"></i></div><?php endif; ?>
                            <a href="view_tour.php?id=<?php echo $tour['id']; ?>" style="color:#1b1c27; text-decoration:none; font-weight:600;"><?php echo htmlspecialchars($tour['title']); ?></a>
                        </div>
                        <span class="rating-stars"><?php echo round($tour['avg_rating'],1); ?>/5 <i class="fas fa-star"></i> <small>(<?php echo $tour['rating_count']; ?>)</small></span>
                    </li>
                    <?php endwhile; ?>
                </ul>
                <?php else: ?><p style="color:#5a6a7a;">No ratings yet.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- قائمة الإشعارات المنسدلة -->
<div class="notification-dropdown" id="notificationDropdown">
    <div class="dropdown-header">
        <span>Notifications</span>
        <?php if (mysqli_num_rows($notifications_list) > 0): ?>
            <span style="font-size:0.8rem; opacity:0.9;"><?php echo mysqli_num_rows($notifications_list); ?> items</span>
        <?php endif; ?>
    </div>
    <div class="dropdown-list">
        <?php if (mysqli_num_rows($notifications_list) > 0): ?>
            <?php while($notif = mysqli_fetch_assoc($notifications_list)):
                $link = getNotificationLink($notif['type'], $notif['data'] ?? '');
            ?>
                <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo $notif['id']; ?>" data-link="<?php echo $link; ?>">
                    <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                    <div class="notification-message"><?php echo nl2br(htmlspecialchars($notif['message'])); ?></div>
                    <div class="notification-date"><i class="far fa-clock"></i> <?php echo date('Y-m-d H:i', strtotime($notif['created_at'])); ?></div>
                    <div class="notification-actions">
                        <?php if (!$notif['is_read']): ?>
                            <button class="mark-read-btn" data-id="<?php echo $notif['id']; ?>"><i class="fas fa-check"></i> Read</button>
                        <?php endif; ?>
                        <button class="delete-notif-btn" data-id="<?php echo $notif['id']; ?>"><i class="fas fa-trash-alt"></i> Delete</button>
                        <?php if ($link != '#'): ?>
                            <a href="<?php echo $link; ?>" class="review-link"><i class="fas fa-arrow-right"></i> Go</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
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
                } else alert('Error: ' + (res.error || 'Unknown'));
            },
            error: function() { alert('Failed.'); }
        });
    });

    $('.delete-notif-btn').click(function(e) {
        e.preventDefault();
        if (!confirm('Delete this notification?')) return;
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
                } else alert('Error: ' + (res.error || 'Unknown'));
            },
            error: function() { alert('Failed.'); }
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
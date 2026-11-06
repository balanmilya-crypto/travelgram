<?php
session_start();
include 'connection.php';

// صلاحية مدير المكتب
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'office_admin') {
    header('Location: ../login.php');
    exit();
}

$office_id = isset($_SESSION['user_office_id']) ? intval($_SESSION['user_office_id']) : 0;
if ($office_id == 0) {
    die("Office ID not found. Please contact the administrator.");
}

// معرف المستخدم (مدير المكتب)
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

// معلومات المكتب
$office_logo = '';
$office_name = '';
$office_info = mysqli_query($conn, "SELECT name, logo FROM offices WHERE id = $office_id");
if ($office_row = mysqli_fetch_assoc($office_info)) {
    $office_name = $office_row['name'];
    $office_logo = $office_row['logo'];
}
$admin_name = $_SESSION['user_name'] ?? 'Admin';

// ========== العدادات ==========
// الحجوزات المعلقة
$pending_bookings_count = 0;
$pb_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM bookings WHERE office_id = $office_id AND status = 'pending'");
if ($pb_res && $pb_row = mysqli_fetch_assoc($pb_res)) $pending_bookings_count = $pb_row['cnt'];

// رسائل غير مقروءة
$unread_messages = 0;
$msg_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM messages WHERE recipient_id = $user_id AND is_read = 0");
if ($msg_res && $msg_row = mysqli_fetch_assoc($msg_res)) $unread_messages = $msg_row['cnt'];

// إشعارات غير مقروءة
$unread_notifications = 0;
$un = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = $user_id AND is_read = 0");
if ($un && $unrow = mysqli_fetch_assoc($un)) $unread_notifications = $unrow['cnt'];

// قائمة الإشعارات للـ dropdown
$notifications_list = [];
$notif_query = mysqli_query($conn, "SELECT id, title, message, created_at, is_read, type, data FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 10");
if ($notif_query) {
    while ($row = mysqli_fetch_assoc($notif_query)) {
        $notifications_list[] = $row;
    }
}

// دالة رابط الإشعار
function getNotificationLink($type, $data) {
    $json = json_decode($data, true);
    switch ($type) {
        case 'new_booking': return 'view_bookings.php';
        case 'tour_approved': return isset($json['tour_id']) ? 'view_tour.php?id=' . intval($json['tour_id']) : '#';
        case 'tour_rejected': return 'add_tour.php';
        default: return '#';
    }
}

// جلب جميع رحلات المكتب مع متوسط التقييمات
$tours_sql = "SELECT t.id, t.title, t.main_image,
                     AVG(r.rating) AS avg_rating,
                     COUNT(r.id) AS rating_count
              FROM tours t
              LEFT JOIN ratings r ON r.rateable_type = 'tour' AND r.rateable_id = t.id
              WHERE t.office_id = $office_id
              GROUP BY t.id
              ORDER BY t.created_at DESC";
$tours_result = mysqli_query($conn, $tours_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Ratings | TravelGram</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        /* ... same background bubble/wave styles ... */
        .bg-bubble {
            position: fixed; border-radius:50%; background:rgba(101,64,58,0.05);
            z-index:0; pointer-events:none; animation:float 12s infinite alternate ease-in-out;
        }
        .bg-bubble:nth-child(1) { width:300px; height:300px; top:-50px; left:-50px; background:rgba(158,193,215,0.08); }
        .bg-bubble:nth-child(2) { width:500px; height:500px; bottom:-100px; right:-100px; background:rgba(101,64,58,0.06); }
        .bg-bubble:nth-child(3) { width:200px; height:200px; top:40%; left:10%; background:rgba(115,121,59,0.05); }
        .bg-bubble:nth-child(4) { width:400px; height:400px; bottom:20%; left:-150px; background:rgba(158,193,215,0.07); }
        .bg-bubble:nth-child(5) { width:250px; height:250px; top:15%; right:5%; background:rgba(101,64,58,0.04); }
        .bg-wave {
            position:fixed; width:100%; height:100%; top:0; left:0; z-index:0; pointer-events:none;
            background:url('data:image/svg+xml;utf8,<svg width="100%" height="100%" viewBox="0 0 1200 800" xmlns="http://www.w3.org/2000/svg"><path d="M0,400 Q300,200 600,400 T1200,300 L1200,800 L0,800 Z" fill="rgba(158,193,215,0.05)"/><path d="M0,600 Q400,450 800,600 T1200,500 L1200,800 L0,800 Z" fill="rgba(101,64,58,0.03)"/></svg>');
            background-size:cover; opacity:0.5;
        }
        @keyframes float {
            0% { transform: translateY(0px) scale(1); }
            100% { transform: translateY(-30px) scale(1.05); }
        }

        .sidebar {
            width:270px; background:#1b1c27; color:white; position:fixed; height:100vh;
            overflow-y:auto; box-shadow:2px 0 15px rgba(0,0,0,0.1); z-index:10;
        }
        .sidebar-header {
            padding:25px 20px; background:rgba(101,64,58,0.15); text-align:center;
            display:flex; align-items:center; justify-content:center; gap:12px;
            border-bottom:1px solid rgba(158,193,215,0.2);
        }
        .sidebar-header img { width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid #9ec1d7; }
        .sidebar-header h3 { color:white; margin:0; font-size:1.5rem; font-weight:700; }
        .sidebar-menu { padding:20px 0; }
        .sidebar-menu ul { list-style:none; }
        .sidebar-menu li { margin-bottom:8px; }
        .sidebar-menu a {
            display:flex; align-items:center; padding:12px 20px;
            color:rgba(239,241,238,0.8); text-decoration:none; border-radius:12px;
            margin:0 12px; transition:0.3s; font-weight:500;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background:#65403a; color:white; box-shadow:0 5px 15px rgba(101,64,58,0.3);
        }
        .sidebar-menu i { margin-right:15px; width:24px; text-align:center; font-size:1.2rem; }
        .msg-badge {
            background:#ed4956; color:white; border-radius:30px; padding:2px 10px;
            font-size:0.75rem; font-weight:bold; margin-left:10px;
        }

        .main-content { flex:1; margin-left:270px; padding:30px; position:relative; z-index:5; }
        .header {
            display:flex; justify-content:space-between; align-items:center;
            background:rgba(255,255,255,0.75); backdrop-filter:blur(15px);
            padding:18px 30px; border-radius:40px; margin-bottom:35px;
            border:1px solid rgba(255,255,255,0.5);
            box-shadow:0 10px 25px rgba(27,28,39,0.05);
        }
        .header h1 { display:flex; align-items:center; gap:12px; font-size:1.8rem; color:#1b1c27; font-weight:700; }
        .header-logo { width:42px; height:42px; border-radius:50%; object-fit:cover; border:2px solid #9ec1d7; }
        .user-info { display:flex; align-items:center; gap:25px; }
        .notification-icon { position:relative; cursor:pointer; font-size:1.5rem; color:#65403a; }
        .notification-badge {
            position:absolute; top:-8px; right:-12px; background:#ed4956; color:white;
            border-radius:50%; padding:3px 7px; font-size:11px; font-weight:bold; min-width:20px; text-align:center;
        }
        @keyframes shake {
            0%{transform:translate(1px,1px) rotate(0deg);} 10%{transform:translate(-1px,-2px) rotate(-1deg);}
            20%{transform:translate(-3px,0px) rotate(1deg);} 30%{transform:translate(3px,2px) rotate(0deg);}
            40%{transform:translate(1px,-1px) rotate(1deg);} 50%{transform:translate(-1px,2px) rotate(-1deg);}
            60%{transform:translate(-3px,1px) rotate(0deg);} 70%{transform:translate(3px,1px) rotate(-1deg);}
            80%{transform:translate(-1px,-1px) rotate(1deg);} 90%{transform:translate(1px,2px) rotate(0deg);}
            100%{transform:translate(1px,-2px) rotate(-1deg);}
        }
        .bell-shake { animation:shake 0.5s ease-in-out infinite; display:inline-block; }
        .avatar {
            width:48px; height:48px; border-radius:50%; background:#65403a; display:flex;
            align-items:center; justify-content:center; color:white; font-weight:bold; font-size:1.3rem;
            overflow:hidden; border:2px solid #9ec1d7;
        }
        .avatar img { width:100%; height:100%; object-fit:cover; }

        /* Ratings Container */
        .ratings-container {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(15px);
            border-radius: 30px;
            padding: 30px 35px;
            box-shadow: 0 20px 40px rgba(27, 28, 39, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        h2 { font-size:1.8rem; font-weight:700; color:#1b1c27; margin-bottom:25px; }

        .message, .error {
            padding:15px 20px; border-radius:40px; margin-bottom:25px; font-size:0.95rem;
            backdrop-filter:blur(8px);
        }
        .message { background:rgba(40,167,69,0.1); color:#155724; border-left:5px solid #28a745; }
        .error { background:rgba(220,53,69,0.1); color:#b91c1c; border-left:5px solid #b91c1c; }

        .tour-card {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(5px);
            border-radius: 25px;
            margin-bottom: 30px;
            overflow: hidden;
            border: 1px solid rgba(158, 193, 215, 0.3);
            transition: 0.2s;
        }
        .tour-card:hover {
            background: rgba(255, 255, 255, 0.8);
            border-color: #9ec1d7;
            box-shadow: 0 10px 25px rgba(101, 64, 58, 0.1);
        }
        .tour-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 25px;
            background: rgba(239, 241, 238, 0.5);
            border-bottom: 1px solid rgba(158, 193, 215, 0.3);
        }
        .tour-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1b1c27;
        }
        .tour-stats { display: flex; gap: 20px; align-items: center; }
        .stars { color: #f0ad4e; font-size: 0.95rem; font-weight: 600; }
        .stars i { margin-right: 3px; }

        .ratings-list { padding: 20px 25px; }
        .rating-item {
            border-bottom: 1px solid rgba(158, 193, 215, 0.2);
            padding: 18px 0;
        }
        .rating-item:last-child { border-bottom: none; }
        .rating-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .user-info-small { display: flex; align-items: center; gap: 12px; }
        .user-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: #65403a; color: white;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; overflow: hidden; border: 2px solid #9ec1d7;
        }
        .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-name { font-weight: 700; color: #1b1c27; }
        .rating-date { font-size: 0.8rem; color: #5a6a7a; }
        .rating-stars { color: #f0ad4e; margin-bottom: 10px; font-size: 1.1rem; }
        .rating-review {
            color: #2d3a4a; line-height: 1.7; margin-top: 8px;
            padding: 10px 15px; background: rgba(239, 241, 238, 0.4);
            border-radius: 15px;
        }
        .no-ratings { color: #5a6a7a; font-style: italic; padding: 20px; text-align: center; }

        /* Notification dropdown */
        .notification-dropdown {
            position: fixed; width: 380px; max-width: 90vw; background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px); border-radius: 25px; box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            z-index: 99999; display: none; overflow: hidden; border: 1px solid rgba(158, 193, 215, 0.5);
        }
        .notification-dropdown.show { display: block; }
        .dropdown-header {
            padding: 18px 20px; background: #65403a; color: white; font-weight: 700; font-size: 1.1rem;
            display: flex; justify-content: space-between; align-items: center;
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
        .notification-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .mark-read-btn, .delete-notif-btn, .go-link {
            font-size: 0.75rem; padding: 5px 12px; border: none; border-radius: 30px;
            cursor: pointer; text-decoration: none; font-weight: 600;
            display: inline-flex; align-items: center; gap: 4px; transition: 0.2s;
        }
        .mark-read-btn { background: #65403a; color: white; }
        .delete-notif-btn { background: #dc3545; color: white; }
        .go-link { background: #9ec1d7; color: #1b1c27; }
        .mark-read-btn:hover { background: #4f322c; }
        .delete-notif-btn:hover { background: #b02a37; }
        .go-link:hover { background: #7baec7; }

        @media (max-width:768px) {
            .sidebar { width:80px; }
            .sidebar-header h3, .sidebar-menu span { display:none; }
            .sidebar-menu i { margin-right:0; }
            .main-content { margin-left:80px; }
            .tour-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .ratings-container { padding: 20px 18px; }
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
            <li><a href="add_tour.php"><i class="fas fa-plus-circle"></i> <span>Add Tour</span></a></li>
            <li><a href="view_tour.php"><i class="fas fa-list"></i> <span>Manage Tours</span></a></li>
            <li><a href="pending_tours.php"><i class="fas fa-clock"></i> <span>Pending Tours</span></a></li>
            <li><a href="view_comments.php?tour_id=0"><i class="fas fa-comments"></i> <span>Comments</span></a></li>
            <li><a href="view_bookings.php"><i class="fas fa-shopping-cart"></i> <span>Bookings</span>
                <?php if ($pending_bookings_count > 0): ?>
                    <span class="msg-badge"><?php echo $pending_bookings_count; ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="view_ratings.php" class="active"><i class="fas fa-star"></i> <span>Ratings</span></a></li>
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
                    <img src="../<?php echo htmlspecialchars($office_logo); ?>" alt="Logo">
                <?php else: ?>
                    <?php echo substr($admin_name, 0, 1); ?>
                <?php endif; ?>
            </div>
            <div>
                <strong style="color:#1b1c27;"><?php echo htmlspecialchars($admin_name); ?></strong>
                <div style="font-size:0.8rem; color:#5a6a7a;">Office Admin</div>
                <div style="font-size:0.75rem; color:#7a8a9a;"><?php echo htmlspecialchars($office_name); ?></div>
            </div>
        </div>
    </div>

    <div class="ratings-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
            <h2><i class="fas fa-star" style="color:#65403a; margin-right:10px;"></i>Tour Ratings</h2>
            <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if (mysqli_num_rows($tours_result) > 0): ?>
            <?php while ($tour = mysqli_fetch_assoc($tours_result)): ?>
                <div class="tour-card">
                    <div class="tour-header">
                        <div class="tour-title"><?php echo htmlspecialchars($tour['title']); ?></div>
                        <div class="tour-stats">
                            <div class="stars">
                                <?php if ($tour['avg_rating']): ?>
                                    <i class="fas fa-star"></i> <?php echo number_format($tour['avg_rating'], 1); ?> / 5
                                    (<?php echo $tour['rating_count']; ?> ratings)
                                <?php else: ?>
                                    <span style="color: #5a6a7a; font-weight: 400;">No ratings yet</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="ratings-list">
                        <?php
                        $ratings_sql = "SELECT r.*, u.name AS user_name, u.avatar AS user_avatar
                                        FROM ratings r
                                        JOIN users u ON r.user_id = u.id
                                        WHERE r.rateable_type = 'tour' AND r.rateable_id = " . $tour['id'] . "
                                        ORDER BY r.created_at DESC";
                        $ratings_result = mysqli_query($conn, $ratings_sql);
                        if (mysqli_num_rows($ratings_result) > 0):
                            while ($rating = mysqli_fetch_assoc($ratings_result)):
                                $initial = strtoupper(mb_substr($rating['user_name'], 0, 1, 'UTF-8'));
                        ?>
                            <div class="rating-item">
                                <div class="rating-header">
                                    <div class="user-info-small">
                                        <div class="user-avatar">
                                            <?php if (!empty($rating['user_avatar'])): ?>
                                                <img src="<?php echo htmlspecialchars($rating['user_avatar']); ?>" alt="avatar">
                                            <?php else: ?>
                                                <?php echo $initial; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="user-name"><?php echo htmlspecialchars($rating['user_name']); ?></div>
                                    </div>
                                    <div class="rating-date"><?php echo date('Y-m-d', strtotime($rating['created_at'])); ?></div>
                                </div>
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i <= $rating['rating'] ? '' : '-o'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <?php if (!empty($rating['review'])): ?>
                                    <div class="rating-review"><?php echo nl2br(htmlspecialchars($rating['review'])); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php
                            endwhile;
                        else:
                        ?>
                            <div class="no-ratings">No ratings for this tour yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color: #5a6a7a; text-align: center; padding: 20px;">No tours added for this office yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Notification dropdown -->
<div class="notification-dropdown" id="notificationDropdown">
    <div class="dropdown-header">
        <span>Notifications</span>
        <?php if (count($notifications_list) > 0): ?>
            <span style="font-size:0.8rem;"><?php echo count($notifications_list); ?> items</span>
        <?php endif; ?>
    </div>
    <div class="dropdown-list">
        <?php if (count($notifications_list) > 0): ?>
            <?php foreach ($notifications_list as $notif):
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
                            <a href="<?php echo $link; ?>" class="go-link"><i class="fas fa-arrow-right"></i> Go</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="padding:25px; text-align:center; color:#5a6a7a;">No notifications</div>
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
            url: 'view_ratings.php',
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
        if (!confirm('Delete notification?')) return;
        var $btn = $(this);
        var notifId = $btn.data('id');
        var $item = $btn.closest('.notification-item');
        var wasUnread = $item.hasClass('unread');
        $.ajax({
            url: 'view_ratings.php',
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
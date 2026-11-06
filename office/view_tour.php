<?php
session_start();
include 'connection.php';

// صلاحية مدير المكتب
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'office_admin') {
    header('Location: ../login.php');
    exit();
}

$office_id = $_SESSION['user_office_id'];
$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id == 0) {
    $u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE office_id = $office_id AND role = 'office_admin'"));
    if ($u) {
        $user_id = $u['id'];
        $_SESSION['user_id'] = $user_id;
    }
}

// معلومات المكتب
$office_name = '';
$office_logo = '';
$office_info = mysqli_query($conn, "SELECT name, logo FROM offices WHERE id = $office_id");
if ($office_row = mysqli_fetch_assoc($office_info)) {
    $office_name = $office_row['name'];
    $office_logo = $office_row['logo'];
}

$admin_name = $_SESSION['user_name'] ?? 'Admin';

// ====== معالجة AJAX للإشعارات ======
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    if ($user_id == 0) {
        echo json_encode(['success' => false]);
        exit();
    }
    if (isset($_POST['mark_read_id'])) {
        $id = intval($_POST['mark_read_id']);
        mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE id=$id AND user_id=$user_id");
        echo json_encode(['success' => true]);
        exit();
    }
    if (isset($_POST['delete_id'])) {
        $id = intval($_POST['delete_id']);
        mysqli_query($conn, "DELETE FROM notifications WHERE id=$id AND user_id=$user_id");
        echo json_encode(['success' => true]);
        exit();
    }
    echo json_encode(['success' => false]);
    exit();
}

// ====== حذف رحلة ======
$message = '';
$error = '';
if (isset($_GET['delete'])) {
    $tour_id = intval($_GET['delete']);
    $check = mysqli_query($conn, "SELECT id FROM tours WHERE id=$tour_id AND office_id=$office_id");
    if (mysqli_num_rows($check) == 1) {
        mysqli_query($conn, "DELETE FROM tours WHERE id=$tour_id");
        $message = "Tour deleted successfully.";
    } else {
        $error = "You don't have permission to delete this tour.";
    }
}

// ====== جلب جميع رحلات هذا المكتب ======
$tours_sql = "
    SELECT t.*,
           c.name AS city_name,
           co.name AS country_name
    FROM tours t
    LEFT JOIN tour_city tc ON t.id = tc.tour_id
    LEFT JOIN cities c ON tc.city_id = c.id
    LEFT JOIN countries co ON c.country_id = co.id
    WHERE t.office_id = $office_id
    ORDER BY t.id DESC
";
$tours_result = mysqli_query($conn, $tours_sql);

// ====== العدادات ======
// التعليقات
$comments_count = 0;
$cres = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM post_comments pc JOIN tours t ON pc.commentable_id=t.id AND pc.commentable_type='tour' WHERE t.office_id=$office_id");
if ($cres && $crow = mysqli_fetch_assoc($cres)) $comments_count = $crow['cnt'];

// التقييمات
$ratings_count = 0;
$rres = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM ratings r JOIN tours t ON r.rateable_type='tour' AND r.rateable_id=t.id WHERE t.office_id=$office_id");
if ($rres && $rrow = mysqli_fetch_assoc($rres)) $ratings_count = $rrow['cnt'];

// الحجوزات المعلقة
$pending_bookings = 0;
$pb = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM bookings WHERE office_id=$office_id AND status='pending'");
if ($pb && $pbrow = mysqli_fetch_assoc($pb)) $pending_bookings = $pbrow['cnt'];

// الرحلات المعلقة (بيدينغ من الأدمن)
$pending_tours = 0;
$pt = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM tours WHERE office_id=$office_id AND status='pending'");
if ($pt && $ptrow = mysqli_fetch_assoc($pt)) $pending_tours = $ptrow['cnt'];

// الرسائل غير المقروءة
$unread_messages = 0;
$umsg = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM messages WHERE recipient_id=$user_id AND recipient_type='user' AND is_read=0");
if ($umsg && $urow = mysqli_fetch_assoc($umsg)) $unread_messages = $urow['cnt'];

// الإشعارات غير المقروءة
$unread_notifications = 0;
$un = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$user_id AND is_read=0");
if ($un && $unrow = mysqli_fetch_assoc($un)) $unread_notifications = $unrow['cnt'];

// قائمة الإشعارات للقائمة المنسدلة
$notifications_list = mysqli_query($conn, "SELECT id, title, message, created_at, is_read, type, data FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tours | TravelGram</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* الشريط الجانبي */
        .sidebar {
            width:270px; background:#1b1c27; color:white; position:fixed; height:100vh;
            overflow-y:auto; box-shadow:2px 0 15px rgba(0,0,0,0.1); z-index:10;
        }
        .sidebar-header {
            padding:25px 20px; background:rgba(101,64,58,0.15); text-align:center;
            display:flex; align-items:center; justify-content:center; gap:12px;
            border-bottom:1px solid rgba(158,193,215,0.2);
        }
        .sidebar-header img {
            width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid #9ec1d7;
        }
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

        .main-content {
            flex:1; margin-left:270px; padding:30px; position:relative; z-index:5;
        }

        .header {
            display:flex; justify-content:space-between; align-items:center;
            background:rgba(255,255,255,0.75); backdrop-filter:blur(15px);
            padding:18px 30px; border-radius:40px; margin-bottom:35px;
            border:1px solid rgba(255,255,255,0.5); box-shadow:0 10px 25px rgba(27,28,39,0.05);
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

        .cards-wrapper {
            background:rgba(255,255,255,0.7); backdrop-filter:blur(10px);
            border-radius:30px; padding:30px 35px; border:1px solid rgba(255,255,255,0.5);
            box-shadow:0 20px 40px rgba(27,28,39,0.08);
        }
        .cards-header {
            display:flex; justify-content:space-between; align-items:center;
            margin-bottom:25px; flex-wrap:wrap; gap:15px;
        }
        .cards-header h2 { font-size:1.8rem; font-weight:700; color:#1b1c27; }

        .btn {
            display:inline-block; padding:8px 18px; border:none; border-radius:30px;
            cursor:pointer; text-decoration:none; font-weight:600; font-size:0.9rem;
            transition:0.3s; box-shadow:0 4px 10px rgba(0,0,0,0.1);
        }
        .btn i { margin-right:6px; }
        .btn-add { background:#65403a; color:white; }
        .btn-back { background:#5a6a7a; color:white; }
        .btn-edit { background:#9ec1d7; color:#1b1c27; padding:5px 12px; font-size:0.8rem; }
        .btn-delete { background:#f8d7da; color:#721c24; padding:5px 12px; font-size:0.8rem; }
        .btn-stations { background:#d4edda; color:#155724; padding:5px 12px; font-size:0.8rem; }
        .btn:hover { transform:translateY(-2px); opacity:0.9; }

        .message, .error {
            padding:15px 20px; border-radius:40px; margin-bottom:25px; backdrop-filter:blur(8px);
        }
        .message { background:rgba(40,167,69,0.1); color:#155724; border-left:5px solid #28a745; }
        .error { background:rgba(220,53,69,0.1); color:#b91c1c; border-left:5px solid #b91c1c; }

        .tours-grid {
            display:grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap:25px;
        }
        .tour-card {
            background:rgba(255,255,255,0.8); backdrop-filter:blur(10px);
            border-radius:30px; overflow:hidden; border:1px solid rgba(158,193,215,0.3);
            transition:0.3s; display:flex; flex-direction:column;
        }
        .tour-card:hover {
            transform:translateY(-8px); box-shadow:0 25px 40px rgba(101,64,58,0.15);
            border-color:#9ec1d7; background:rgba(255,255,255,0.9);
        }
        .tour-img {
            width:100%; height:180px; background:rgba(239,241,238,0.5);
            display:flex; align-items:center; justify-content:center;
            overflow:hidden;
        }
        .tour-img img { width:100%; height:100%; object-fit:cover; }
        .tour-img .no-img {
            font-size:3rem; color:#65403a; opacity:0.4;
        }
        .tour-body {
            padding:20px; flex:1; display:flex; flex-direction:column; gap:12px;
        }
        .tour-title { font-size:1.3rem; font-weight:700; color:#1b1c27; }
        .tour-meta { display:flex; flex-wrap:wrap; gap:15px; font-size:0.85rem; color:#5a6a7a; margin-bottom:5px; }
        .tour-meta i { width:16px; color:#65403a; margin-right:4px; }
        .tour-price { font-size:1.4rem; font-weight:700; color:#1b1c27; }
        .status-badge {
            display:inline-block; padding:4px 12px; border-radius:20px; font-size:0.75rem; font-weight:600;
        }
        .status-pending { background:rgba(255,193,7,0.15); color:#856404; }
        .status-approved { background:rgba(40,167,69,0.1); color:#155724; }
        .action-row {
            display:flex; gap:8px; margin-top:auto; padding-top:15px;
            border-top:1px solid rgba(158,193,215,0.2);
        }

        /* القائمة المنسدلة */
        .notification-dropdown {
            position:fixed; width:380px; max-width:90vw; background:rgba(255,255,255,0.98);
            backdrop-filter:blur(20px); border-radius:25px; box-shadow:0 25px 50px rgba(0,0,0,0.25);
            z-index:99999; display:none; overflow:hidden; border:1px solid rgba(158,193,215,0.5);
        }
        .notification-dropdown.show { display:block; }
        .dropdown-header { padding:18px 20px; background:#65403a; color:white; font-weight:700; }
        .dropdown-list { max-height:450px; overflow-y:auto; }
        .notification-item {
            padding:16px 20px; border-bottom:1px solid rgba(158,193,215,0.2); cursor:pointer;
        }
        .notification-item.unread { background:rgba(101,64,58,0.05); border-left:5px solid #65403a; }
        .notification-title { font-weight:700; font-size:1rem; color:#1b1c27; }
        .notification-message { font-size:0.9rem; color:#5a6a7a; margin:6px 0; }
        .notification-date { font-size:0.75rem; color:#7a8a9a; margin-bottom:10px; }
        .notification-actions { display:flex; gap:8px; }
        .mark-read-btn, .delete-notif-btn, .go-link {
            font-size:0.75rem; padding:5px 12px; border-radius:30px; border:none;
            cursor:pointer; text-decoration:none; font-weight:600; display:inline-flex; align-items:center; gap:4px;
        }
        .mark-read-btn { background:#65403a; color:white; }
        .delete-notif-btn { background:#dc3545; color:white; }
        .go-link { background:#9ec1d7; color:#1b1c27; }

        @media (max-width:768px) {
            .sidebar { width:80px; }
            .sidebar-header h3, .sidebar-menu span { display:none; }
            .sidebar-menu i { margin-right:0; }
            .main-content { margin-left:80px; }
            .tours-grid { grid-template-columns:1fr; }
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
            <li><a href="view_tour.php" class="active"><i class="fas fa-list"></i> <span>Manage Tours</span></a></li>
            <!-- رابط الرحلات المعلقة مع عداد -->
            <li><a href="pending_tours.php"><i class="fas fa-clock"></i> <span>Pending Tours</span>
                
            </a></li>
            <li><a href="view_comments.php?tour_id=0"><i class="fas fa-comments"></i> <span>Comments</span>
               
            </a></li>
            <li><a href="view_bookings.php"><i class="fas fa-shopping-cart"></i> <span>Bookings</span>
                <?php if($pending_bookings > 0): ?><span class="msg-badge"><?php echo $pending_bookings; ?></span><?php endif; ?>
            </a></li>
            <li><a href="view_ratings.php"><i class="fas fa-star"></i> <span>Ratings</span>
               
            </a></li>
            <li><a href="../user/messenger.php"><i class="fas fa-envelope"></i> <span>Messenger</span>
                <?php if($unread_messages > 0): ?><span class="msg-badge"><?php echo $unread_messages; ?></span><?php endif; ?>
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
                <i class="fas fa-bell <?php echo ($unread_notifications>0)?'bell-shake':''; ?>" id="bellIconInner"></i>
                <?php if($unread_notifications>0): ?>
                    <span class="notification-badge" id="notificationBadge"><?php echo $unread_notifications; ?></span>
                <?php endif; ?>
            </div>
            <div class="avatar">
                <?php if(!empty($office_logo)): ?>
                    <img src="../<?php echo htmlspecialchars($office_logo); ?>" alt="Logo">
                <?php else: ?>
                    <?php echo substr($admin_name,0,1); ?>
                <?php endif; ?>
            </div>
            <div>
                <strong style="color:#1b1c27;"><?php echo htmlspecialchars($admin_name); ?></strong>
                <div style="font-size:0.8rem; color:#5a6a7a;">Office Admin</div>
                <div style="font-size:0.75rem; color:#7a8a9a;"><?php echo htmlspecialchars($office_name); ?></div>
            </div>
        </div>
    </div>

    <div class="cards-wrapper">
        <div class="cards-header">
            <h2><i class="fas fa-list" style="color:#65403a; margin-right:10px;"></i>My Tours</h2>
            <div>
                <a href="add_tour.php" class="btn btn-add"><i class="fas fa-plus"></i> Add New Tour</a>
                <a href="dashboard.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Dashboard</a>
            </div>
        </div>

        <?php if($message): ?><div class="message"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div><?php endif; ?>
        <?php if($error): ?><div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>

        <?php if(mysqli_num_rows($tours_result) > 0): ?>
            <div class="tours-grid">
                <?php while($row = mysqli_fetch_assoc($tours_result)): ?>
                    <div class="tour-card">
                        <div class="tour-img">
                            <?php if(!empty($row['main_image'])): ?>
                                <img src="../<?php echo htmlspecialchars($row['main_image']); ?>" alt="Tour">
                            <?php else: ?>
                                <i class="fas fa-image no-img"></i>
                            <?php endif; ?>
                        </div>
                        <div class="tour-body">
                            <div class="tour-title"><?php echo htmlspecialchars($row['title']); ?></div>
                            <div class="tour-meta">
                                <?php if($row['duration_days']): ?><span><i class="far fa-clock"></i> <?php echo $row['duration_days']; ?> days</span><?php endif; ?>
                                <?php if($row['start_date']): ?><span><i class="far fa-calendar-alt"></i> <?php echo date('Y-m-d', strtotime($row['start_date'])); ?></span><?php endif; ?>
                                <?php if($row['end_date']): ?><span><i class="far fa-calendar-check"></i> <?php echo date('Y-m-d', strtotime($row['end_date'])); ?></span><?php endif; ?>
                            </div>
                            <?php if($row['city_name'] || $row['country_name']): ?>
                                <div style="font-size:0.9rem;"><i class="fas fa-map-marker-alt" style="color:#65403a;"></i> 
                                    <?php echo implode(', ', array_filter([$row['city_name'], $row['country_name']])); ?>
                                </div>
                            <?php endif; ?>
                            <div class="tour-price">
                                <?php echo number_format($row['price'], 2); ?> <?php echo $row['currency']; ?>
                            </div>
                            <span class="status-badge status-<?php echo $row['status']; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                            <div class="action-row">
                                <a href="edit_tour.php?id=<?php echo $row['id']; ?>" class="btn btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this tour?')"><i class="fas fa-trash-alt"></i> Delete</a>
                                <a href="view_station.php?tour_id=<?php echo $row['id']; ?>" class="btn btn-stations"><i class="fas fa-map-marker-alt"></i> Stations</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="color:#5a6a7a; text-align:center; padding:40px;">No tours found. Click "Add New Tour" to create one.</p>
        <?php endif; ?>
    </div>
</div>

<!-- قائمة الإشعارات المنسدلة -->
<div class="notification-dropdown" id="notificationDropdown">
    <div class="dropdown-header">
        <span>Notifications</span>
        <?php if(mysqli_num_rows($notifications_list) > 0): ?>
            <span style="font-size:0.8rem;"><?php echo mysqli_num_rows($notifications_list); ?> items</span>
        <?php endif; ?>
    </div>
    <div class="dropdown-list">
        <?php if(mysqli_num_rows($notifications_list) > 0): ?>
            <?php while($notif = mysqli_fetch_assoc($notifications_list)):
                $data = json_decode($notif['data'] ?? '{}', true);
                $link = '#';
                if($notif['type'] == 'tour_approved' && isset($data['tour_id'])) $link = 'view_tour.php?id='.$data['tour_id'];
                if($notif['type'] == 'new_booking') $link = 'view_bookings.php';
            ?>
                <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo $notif['id']; ?>" data-link="<?php echo $link; ?>">
                    <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                    <div class="notification-message"><?php echo nl2br(htmlspecialchars($notif['message'])); ?></div>
                    <div class="notification-date"><i class="far fa-clock"></i> <?php echo date('Y-m-d H:i', strtotime($notif['created_at'])); ?></div>
                    <div class="notification-actions">
                        <?php if(!$notif['is_read']): ?>
                            <button class="mark-read-btn" data-id="<?php echo $notif['id']; ?>"><i class="fas fa-check"></i> Read</button>
                        <?php endif; ?>
                        <button class="delete-notif-btn" data-id="<?php echo $notif['id']; ?>"><i class="fas fa-trash-alt"></i> Delete</button>
                        <?php if($link != '#'): ?>
                            <a href="<?php echo $link; ?>" class="go-link"><i class="fas fa-arrow-right"></i> Go</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="padding:25px; text-align:center; color:#5a6a7a;">No notifications</div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function() {
    var $dropdown = $('#notificationDropdown');
    var $bell = $('#bellIcon');
    
    function pos() {
        var off = $bell.offset();
        $dropdown.css({
            top: off.top + $bell.outerHeight() + 10,
            right: $(window).width() - (off.left + $bell.outerWidth())
        });
    }
    
    $bell.click(function(e) {
        e.stopPropagation();
        pos();
        $dropdown.toggleClass('show');
    });
    
    $(window).resize(function() { if($dropdown.hasClass('show')) pos(); });
    
    $(document).click(function(e) {
        if(!$(e.target).closest('#bellIcon').length && !$(e.target).closest('#notificationDropdown').length)
            $dropdown.removeClass('show');
    });
    
    $('.mark-read-btn').click(function(e) {
        e.preventDefault();
        var $btn = $(this), id = $btn.data('id'), $item = $btn.closest('.notification-item');
        $.post('view_tour.php', {mark_read_id: id}, function(r) {
            if(r.success) { $item.removeClass('unread'); $btn.remove(); updateBadge(-1); }
        }, 'json');
    });
    
    $('.delete-notif-btn').click(function(e) {
        e.preventDefault();
        if(!confirm('Delete notification?')) return;
        var $btn = $(this), id = $btn.data('id'), $item = $btn.closest('.notification-item');
        var wasUnread = $item.hasClass('unread');
        $.post('view_tour.php', {delete_id: id}, function(r) {
            if(r.success) {
                $item.fadeOut(300, function(){ $(this).remove(); });
                if(wasUnread) updateBadge(-1);
            }
        }, 'json');
    });
    
    function updateBadge(change) {
        var $b = $('#notificationBadge');
        if(!$b.length) return;
        var cur = parseInt($b.text()) || 0;
        cur += change;
        if(cur > 0) $b.text(cur);
        else { $b.remove(); $('#bellIconInner').removeClass('bell-shake'); }
    }
    
    $(document).on('click', '.notification-item', function(e) {
        if($(e.target).closest('button, a').length) return;
        var link = $(this).data('link');
        if(link && link != '#') window.location.href = link;
    });
});
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
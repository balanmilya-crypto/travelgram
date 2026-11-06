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
$office_logo = '';
$office_name = '';
$office_info = mysqli_query($conn, "SELECT name, logo FROM offices WHERE id = $office_id");
if ($office_row = mysqli_fetch_assoc($office_info)) {
    $office_name = $office_row['name'];
    $office_logo = $office_row['logo'];
}
$admin_name = $_SESSION['user_name'] ?? 'Admin';

// ========== عدادات الشريط الجانبي والجرس ==========
// الحجوزات المعلقة
$pending_bookings_count = 0;
$pb_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM bookings WHERE office_id = $office_id AND status = 'pending'");
if ($pb_res && $pb_row = mysqli_fetch_assoc($pb_res)) $pending_bookings_count = $pb_row['cnt'];

// الرسائل غير المقروءة
$unread_messages = 0;
$msg_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM messages WHERE recipient_id = $user_id AND is_read = 0");
if ($msg_res && $msg_row = mysqli_fetch_assoc($msg_res)) $unread_messages = $msg_row['cnt'];

// الإشعارات غير المقروءة (للجرس)
$unread_notifications = 0;
$q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = $user_id AND is_read = 0");
if ($q && $row = mysqli_fetch_assoc($q)) $unread_notifications = $row['cnt'];

// ========== جلب الرحلات المعلقة لهذا المكتب ==========
$tours_sql = "SELECT 
                t.id,
                t.title,
                t.price,
                t.currency,
                t.duration_days,
                t.start_date,
                t.end_date,
                t.main_image,
                t.created_at,
                o.name AS office_name
            FROM tours t
            LEFT JOIN offices o ON t.office_id = o.id
            WHERE t.office_id = $office_id AND t.status = 'pending'
            ORDER BY t.created_at DESC";
$tours_result = mysqli_query($conn, $tours_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Tours - Office Admin | TravelGram</title>
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
        .notification-icon { position:relative; font-size:1.5rem; color:#65403a; }
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
            background:rgba(255,255,255,0.75); backdrop-filter:blur(15px);
            border-radius:30px; padding:30px 35px;
            box-shadow:0 20px 40px rgba(27,28,39,0.08);
            border:1px solid rgba(255,255,255,0.5);
        }
        h2 { font-size:1.8rem; font-weight:700; color:#1b1c27; margin-bottom:25px; }

        .cards-grid {
            display:grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap:25px;
        }
        .tour-card {
            background:rgba(255,255,255,0.7); backdrop-filter:blur(10px);
            border-radius:30px; overflow:hidden;
            border:1px solid rgba(158,193,215,0.3);
            transition:0.3s; display:flex; flex-direction:column;
        }
        .tour-card:hover {
            transform:translateY(-8px);
            box-shadow:0 25px 40px rgba(101,64,58,0.15);
            border-color:#9ec1d7; background:rgba(255,255,255,0.85);
        }
        .tour-card-image {
            width:100%; height:200px;
            background:rgba(239,241,238,0.5);
            display:flex; align-items:center; justify-content:center; overflow:hidden;
        }
        .tour-card-image img { width:100%; height:100%; object-fit:cover; }
        .tour-card-image .no-image { font-size:3rem; color:#65403a; opacity:0.5; }

        .tour-card-body {
            padding:20px; flex:1;
            display:flex; flex-direction:column;
        }
        .tour-name { font-size:1.3rem; font-weight:700; color:#1b1c27; margin-bottom:8px; }
        .price-tag { font-size:1.5rem; font-weight:700; color:#1b1c27; margin-bottom:12px; }
        .meta-row {
            display:flex; justify-content:space-between; align-items:center;
            margin-bottom:15px; font-size:0.85rem; color:#5a6a7a;
        }
        .status-badge {
            display:inline-block; padding:4px 12px; border-radius:20px;
            font-size:0.75rem; font-weight:600; background:rgba(255,193,7,0.15); color:#856404;
        }
        .btn-back {
            display:inline-flex; align-items:center; gap:8px;
            margin-top:25px; color:#65403a; text-decoration:none; font-weight:600;
        }
        .btn-back:hover { color:#4f322c; }

        @media (max-width:768px) {
            .sidebar { width:80px; }
            .sidebar-header h3, .sidebar-menu span { display:none; }
            .sidebar-menu i { margin-right:0; }
            .main-content { margin-left:80px; }
            .cards-wrapper { padding:20px; }
            .cards-grid { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<!-- الشريط الجانبي (مطابق للداشبورد) -->
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
            <li><a href="pending_tours.php" class="active"><i class="fas fa-clock"></i> <span>Pending Tours</span></a></li>
            <li><a href="view_comments.php?tour_id=0"><i class="fas fa-comments"></i> <span>Comments</span></a></li>
            <li><a href="view_bookings.php"><i class="fas fa-shopping-cart"></i> <span>Bookings</span>
                <?php if ($pending_bookings_count > 0): ?><span class="msg-badge"><?php echo $pending_bookings_count; ?></span><?php endif; ?>
            </a></li>
            <li><a href="view_ratings.php"><i class="fas fa-star"></i> <span>Ratings</span></a></li>
            <li><a href="../user/messenger.php"><i class="fas fa-envelope"></i> <span>Messenger</span>
                <?php if ($unread_messages > 0): ?><span class="msg-badge"><?php echo $unread_messages; ?></span><?php endif; ?>
            </a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
</div>

<!-- المحتوى الرئيسي -->
<div class="main-content">
    <div class="header">
        <h1><img src="../img/logo.jpg" class="header-logo"> TravelGram</h1>
        <div class="user-info">
            <div class="notification-icon">
                <i class="fas fa-bell <?php echo ($unread_notifications > 0) ? 'bell-shake' : ''; ?>"></i>
                <?php if ($unread_notifications > 0): ?>
                    <span class="notification-badge"><?php echo $unread_notifications; ?></span>
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

    <div class="cards-wrapper">
        <h2><i class="fas fa-clock" style="color:#65403a; margin-right:10px;"></i>My Pending Tours</h2>

        <?php if (mysqli_num_rows($tours_result) > 0): ?>
            <div class="cards-grid">
                <?php while ($tour = mysqli_fetch_assoc($tours_result)): ?>
                    <div class="tour-card">
                        <div class="tour-card-image">
                            <?php if (!empty($tour['main_image'])): ?>
                                <img src="../<?php echo htmlspecialchars($tour['main_image']); ?>" alt="Tour">
                            <?php else: ?>
                                <i class="fas fa-image no-image"></i>
                            <?php endif; ?>
                        </div>
                        <div class="tour-card-body">
                            <div class="tour-name"><?php echo htmlspecialchars($tour['title']); ?></div>
                            <div class="price-tag">
                                <?php echo number_format($tour['price'], 2); ?> <?php echo $tour['currency']; ?>
                            </div>
                            <div class="meta-row">
                                <span><i class="far fa-clock"></i> <?php echo $tour['duration_days']; ?> days</span>
                                <span><i class="far fa-calendar-alt"></i> <?php echo date('Y-m-d', strtotime($tour['created_at'])); ?></span>
                            </div>
                            <span class="status-badge"><i class="fas fa-hourglass-half"></i> Pending</span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="color:#5a6a7a; text-align:center; padding:40px;">
                <i class="far fa-smile" style="font-size:2rem; opacity:0.3;"></i><br>
                No pending tours. All your tours have been reviewed!
            </p>
        <?php endif; ?>
        
        <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
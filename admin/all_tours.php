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

// معالجة حذف رحلة
$message = '';
$error = '';
if (isset($_GET['delete'])) {
    $tour_id = intval($_GET['delete']);
    $delete_sql = "DELETE FROM tours WHERE id = $tour_id";
    if (mysqli_query($conn, $delete_sql)) {
        $message = "Tour deleted successfully.";
    } else {
        $error = "Error deleting tour: " . mysqli_error($conn);
    }
}

// استعلام لجلب جميع الرحلات
$sql = "SELECT 
            t.id,
            t.title,
            t.price,
            t.currency,
            t.duration_days,
            t.start_date,
            t.end_date,
            t.status,
            t.created_at,
            t.main_image,
            o.name AS office_name,
            c.name AS city_name,
            co.name AS country_name
        FROM tours t
        LEFT JOIN offices o ON t.office_id = o.id
        LEFT JOIN tour_city tc ON t.id = tc.tour_id
        LEFT JOIN cities c ON tc.city_id = c.id
        LEFT JOIN countries co ON c.country_id = co.id
        ORDER BY t.id DESC";
$result = mysqli_query($conn, $sql);

// جلب عدد الرسائل غير المقروءة للسوبر أدمن (باستخدام recipient_id)
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

// جلب عدد المكاتب المعلقة
$pending_offices_count = 0;
$offices_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM offices WHERE approved = 0");
if ($offices_row = mysqli_fetch_assoc($offices_query)) {
    $pending_offices_count = $offices_row['count'];
}

// ====== جلب عدد الإشعارات غير المقروءة (للجرس) ======
$unread_notifications = 0;
if ($admin_user_id > 0) {
    $notif_query = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = $admin_user_id AND is_read = 0");
    if ($notif_query && $notif_row = mysqli_fetch_assoc($notif_query)) {
        $unread_notifications = $notif_row['cnt'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Tours - Super Admin | TravelGram</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
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
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(239, 241, 238, 0.8);
            text-decoration: none;
            border-radius: 12px;
            margin: 0 12px;
            transition: 0.3s;
            font-weight: 500;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: #65403a;
            color: white;
            box-shadow: 0 5px 15px rgba(101, 64, 58, 0.3);
        }
        .sidebar-menu i { margin-right: 15px; width: 24px; text-align: center; font-size: 1.2rem; }
        .msg-badge {
            background: #ed4956;
            color: white;
            border-radius: 30px;
            padding: 2px 10px;
            font-size: 0.75rem;
            font-weight: bold;
            margin-left: 10px;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(15px);
            padding: 18px 30px;
            border-radius: 40px;
            margin-bottom: 35px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 10px 25px rgba(27, 28, 39, 0.05);
        }
        .header h1 {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.8rem;
            color: #1b1c27;
            font-weight: 700;
        }
        .header-logo {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #9ec1d7;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 25px;
        }
        .notification-icon {
            position: relative;
            cursor: pointer;
            font-size: 1.5rem;
            color: #65403a;
        }
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -12px;
            background: #ed4956;
            color: white;
            border-radius: 50%;
            padding: 3px 7px;
            font-size: 11px;
            font-weight: bold;
            min-width: 20px;
            text-align: center;
        }
        .avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #65403a;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.3rem;
            overflow: hidden;
            border: 2px solid #9ec1d7;
        }

        /* حاوية البطاقات */
        .tours-grid {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(15px);
            border-radius: 30px;
            padding: 30px 35px;
            box-shadow: 0 20px 40px rgba(27, 28, 39, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1b1c27;
            margin-bottom: 25px;
        }

        .message, .error {
            padding: 15px 20px;
            border-radius: 40px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            backdrop-filter: blur(8px);
        }
        .message {
            background: rgba(40, 167, 69, 0.1);
            color: #155724;
            border-left: 5px solid #28a745;
        }
        .error {
            background: rgba(220, 53, 69, 0.1);
            color: #b91c1c;
            border-left: 5px solid #b91c1c;
        }

        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .tour-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            overflow: hidden;
            border: 1px solid rgba(158, 193, 215, 0.3);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .tour-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 40px rgba(101, 64, 58, 0.15);
            border-color: #9ec1d7;
            background: rgba(255, 255, 255, 0.85);
        }

        .tour-card-image {
            width: 100%;
            height: 200px;
            background: rgba(239, 241, 238, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .tour-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .tour-card-image .no-image {
            font-size: 3rem;
            color: #65403a;
            opacity: 0.5;
        }

        .tour-card-body {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .tour-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1b1c27;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .office-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(101, 64, 58, 0.1);
            color: #65403a;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 12px;
            align-self: flex-start;
        }

        .location-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #5a6a7a;
            font-size: 0.85rem;
            margin-bottom: 12px;
        }

        .price-tag {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1b1c27;
            margin-bottom: 12px;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 0.85rem;
            color: #5a6a7a;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending { background: rgba(255, 193, 7, 0.15); color: #856404; border: 1px solid #ffc107; }
        .status-approved { background: rgba(40, 167, 69, 0.1); color: #155724; border: 1px solid #28a745; }
        .status-rejected { background: rgba(220, 53, 69, 0.1); color: #721c24; border: 1px solid #dc3545; }

        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 40px;
            padding: 8px 16px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.3s;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
        }
        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(220, 53, 69, 0.25);
        }

        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h3, .sidebar-menu span { display: none; }
            .sidebar-menu i { margin-right: 0; }
            .main-content { margin-left: 80px; }
            .tours-grid { padding: 20px 18px; }
            .cards-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<!-- خلفية -->
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
            <li><a href="all_tours.php" class="active"><i class="fas fa-plane"></i> <span>All Tours</span></a></li>
            <!-- رابط الرحلات المعلقة مع العداد -->
            <li><a href="pending_tours.php"><i class="fas fa-clock"></i> <span>Pending Tours</span>
                <?php if ($pending_tours_count > 0): ?>
                    <span class="msg-badge"><?php echo $pending_tours_count; ?></span>
                <?php endif; ?>
            </a></li>
            <!-- رابط طلبات المكاتب مع العداد -->
            <li><a href="pending_office.php"><i class="fas fa-building"></i> <span>Office Requests</span>
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

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <h1>
            <img src="../img/logo.jpg" alt="Logo" class="header-logo">
            TravelGram
        </h1>
        <div class="user-info">
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

    <div class="tours-grid">
        <h2><i class="fas fa-plane" style="color: #65403a; margin-right: 10px;"></i>All Tours (Platform Wide)</h2>

        <?php if ($message): ?>
            <div class="message"><i class="fas fa-check-circle mr-2"></i><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="cards-container">
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="tour-card">
                        <div class="tour-card-image">
                            <?php if (!empty($row['main_image'])): ?>
                                <img src="../<?php echo htmlspecialchars($row['main_image']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
                            <?php else: ?>
                                <i class="fas fa-image no-image"></i>
                            <?php endif; ?>
                        </div>
                        <div class="tour-card-body">
                            <div class="tour-name"><?php echo htmlspecialchars($row['title']); ?></div>
                            <div class="office-badge">
                                <i class="fas fa-building"></i> <?php echo htmlspecialchars($row['office_name']); ?>
                            </div>
                            <?php if ($row['city_name'] || $row['country_name']): ?>
                                <div class="location-badge">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php 
                                        $location = [];
                                        if ($row['city_name']) $location[] = $row['city_name'];
                                        if ($row['country_name']) $location[] = $row['country_name'];
                                        echo htmlspecialchars(implode(', ', $location));
                                    ?>
                                </div>
                            <?php endif; ?>
                            <div class="price-tag">
                                <?php echo number_format($row['price'], 2); ?> <?php echo $row['currency']; ?>
                            </div>
                            <div class="meta-row">
                                <span><i class="far fa-clock"></i> <?php echo date('Y-m-d', strtotime($row['created_at'])); ?></span>
                                <span class="status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span>
                            </div>
                            <a href="?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Delete this tour? This will also delete all related data.')">
                                <i class="fas fa-trash-alt"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="color: #5a6a7a; text-align: center; padding: 20px;">No tours found.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
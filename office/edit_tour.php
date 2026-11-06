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

// ========== العدادات المطلوبة فقط ==========
// جرس الإشعارات (غير مقروءة) - يشمل إشعارات الحجوزات الجديدة والموافقات
$unread_notifications = 0;
$un = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = $user_id AND is_read = 0");
if ($un && $unrow = mysqli_fetch_assoc($un)) $unread_notifications = $unrow['cnt'];

// رسائل غير مقروءة للماسنجر
$unread_messages = 0;
$msg_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM messages WHERE recipient_id = $user_id AND is_read = 0");
if ($msg_res && $msg_row = mysqli_fetch_assoc($msg_res)) $unread_messages = $msg_row['cnt'];

// الحجوزات المعلقة
$pending_bookings_count = 0;
$pb_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM bookings WHERE office_id = $office_id AND status = 'pending'");
if ($pb_res && $pb_row = mysqli_fetch_assoc($pb_res)) $pending_bookings_count = $pb_row['cnt'];

// (اختياري) توليد إشعارات الحجوزات الجديدة إذا لم تكن موجودة لتحديث عداد الجرس
$new_bookings_count = 0;
$last_notif_res = mysqli_query($conn, "SELECT created_at FROM notifications WHERE user_id = $user_id AND type = 'new_booking' ORDER BY created_at DESC LIMIT 1");
if ($last_notif_res && mysqli_num_rows($last_notif_res) > 0) {
    $last_row = mysqli_fetch_assoc($last_notif_res);
    $last_time = strtotime($last_row['created_at']);
} else {
    $last_time = strtotime('-1 hour');
}
$nb_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM bookings WHERE office_id = $office_id AND created_at > FROM_UNIXTIME($last_time)");
if ($nb_res && $nb_row = mysqli_fetch_assoc($nb_res)) $new_bookings_count = $nb_row['cnt'];
if ($new_bookings_count > 0) {
    $check_unread = mysqli_query($conn, "SELECT id FROM notifications WHERE user_id = $user_id AND type = 'new_booking' AND is_read = 0 LIMIT 1");
    if (mysqli_num_rows($check_unread) == 0) {
        $title = "📅 New Booking(s) Received";
        $message = "You have $new_bookings_count new booking(s).";
        mysqli_query($conn, "INSERT INTO notifications (user_id, type, title, message, is_read, created_at) 
                   VALUES ($user_id, 'new_booking', '$title', '$message', 0, NOW())");
        $unread_notifications++; // تحديث العداد بعد الإدراج
    }
}

// ========== جلب بيانات الرحلة للتعديل ==========
$tour_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sql_tour = "SELECT * FROM tours WHERE id = $tour_id AND office_id = $office_id";
$result = mysqli_query($conn, $sql_tour);
if (mysqli_num_rows($result) == 0) {
    die("Tour not found or you don't have permission.");
}
$tour = mysqli_fetch_assoc($result);

// جلب المدينة والأيام
$city_sql = "SELECT c.id, c.name, c.country_id, tc.days 
             FROM tour_city tc 
             JOIN cities c ON tc.city_id = c.id 
             WHERE tc.tour_id = $tour_id";
$city_res = mysqli_query($conn, $city_sql);
$city_data = mysqli_fetch_assoc($city_res);
$selected_city_id = $city_data['id'];
$city_days = $city_data['days'];
$city_name = $city_data['name'];
$selected_country_id = $city_data['country_id'];

$country_sql = "SELECT name FROM countries WHERE id = $selected_country_id";
$country_res = mysqli_query($conn, $country_sql);
$country_row = mysqli_fetch_assoc($country_res);
$country_name = $country_row['name'];

// الفنادق والمطاعم والمعالم المحددة مسبقاً
$selected_hotels = [];
$hotels_sel = mysqli_query($conn, "SELECT hotel_id FROM tour_hotel WHERE tour_id = $tour_id");
while ($row = mysqli_fetch_assoc($hotels_sel)) $selected_hotels[] = $row['hotel_id'];

$selected_restaurants = [];
$rest_sel = mysqli_query($conn, "SELECT restaurant_id FROM tour_restaurant WHERE tour_id = $tour_id");
while ($row = mysqli_fetch_assoc($rest_sel)) $selected_restaurants[] = $row['restaurant_id'];

$selected_landmarks = [];
$land_sel = mysqli_query($conn, "SELECT landmark_id FROM tour_landmark WHERE tour_id = $tour_id");
while ($row = mysqli_fetch_assoc($land_sel)) $selected_landmarks[] = $row['landmark_id'];

// العناصر المتاحة في المدينة
$hotels_available = mysqli_query($conn, "SELECT id, name FROM hotels WHERE city_id = $selected_city_id ORDER BY name");
$restaurants_available = mysqli_query($conn, "SELECT id, name FROM restaurants WHERE city_id = $selected_city_id ORDER BY name");
$landmarks_available = mysqli_query($conn, "SELECT id, name FROM landmarks WHERE city_id = $selected_city_id ORDER BY name");

// معالجة تحديث الرحلة
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_tour'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $price = floatval($_POST['price']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $currency = !empty($_POST['currency']) ? "'" . mysqli_real_escape_string($conn, $_POST['currency']) . "'" : "'USD'";
    $duration_days = !empty($_POST['duration_days']) ? intval($_POST['duration_days']) : 'NULL';
    $start_date = !empty($_POST['start_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['start_date']) . "'" : 'NULL';
    $end_date = !empty($_POST['end_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['end_date']) . "'" : 'NULL';
    $booking_deadline = !empty($_POST['booking_deadline']) ? "'" . mysqli_real_escape_string($conn, $_POST['booking_deadline']) . "'" : 'NULL';
    $payment_type = !empty($_POST['payment_type']) ? "'" . mysqli_real_escape_string($conn, $_POST['payment_type']) . "'" : 'NULL';
    $city_days = isset($_POST['city_days']) ? intval($_POST['city_days']) : 1;
    if ($city_days < 1) $city_days = 1;

    // رفع الصورة الرئيسية
    $main_image = $tour['main_image'];
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
        $target_dir = "../uploads/tours/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $filename = time() . '_' . uniqid() . '.' . $ext;
            $target_file = $target_dir . $filename;
            if (move_uploaded_file($_FILES['main_image']['tmp_name'], $target_file)) {
                if (!empty($tour['main_image']) && file_exists("../" . $tour['main_image'])) {
                    unlink("../" . $tour['main_image']);
                }
                $main_image = 'uploads/tours/' . $filename;
            } else {
                $error = "Failed to upload main image.";
            }
        } else {
            $error = "Only JPG, JPEG, PNG, GIF are allowed.";
        }
    }

    if (empty($error)) {
        mysqli_begin_transaction($conn);
        try {
            // تحديث جدول tours
            $update_tour_sql = "UPDATE tours SET 
                title = '$title',
                price = $price,
                currency = $currency,
                description = '$description',
                main_image = '$main_image',
                duration_days = $duration_days,
                start_date = $start_date,
                end_date = $end_date,
                booking_deadline = $booking_deadline,
                payment_type = $payment_type,
                updated_at = NOW()
                WHERE id = $tour_id";
            if (!mysqli_query($conn, $update_tour_sql)) throw new Exception("Error updating tour: " . mysqli_error($conn));

            // تحديث عدد الأيام في المدينة
            mysqli_query($conn, "UPDATE tour_city SET days = $city_days WHERE tour_id = $tour_id");

            // إعادة تعيين العلاقات
            mysqli_query($conn, "DELETE FROM tour_hotel WHERE tour_id = $tour_id");
            mysqli_query($conn, "DELETE FROM tour_restaurant WHERE tour_id = $tour_id");
            mysqli_query($conn, "DELETE FROM tour_landmark WHERE tour_id = $tour_id");

            $cc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT country_id FROM cities WHERE id = $selected_city_id"));
            $city_country_id = $cc['country_id'];

            // ربط الفنادق
            if (isset($_POST['hotels']) && is_array($_POST['hotels'])) {
                foreach ($_POST['hotels'] as $hotel_id) {
                    $hotel_id = intval($hotel_id);
                    mysqli_query($conn, "INSERT INTO tour_hotel (tour_id, hotel_id) VALUES ($tour_id, $hotel_id)");
                }
            }
            if (!empty($_POST['new_hotel'])) {
                $new_hotel = mysqli_real_escape_string($conn, $_POST['new_hotel']);
                mysqli_query($conn, "INSERT INTO hotels (name, city_id, country_id) VALUES ('$new_hotel', $selected_city_id, $city_country_id)");
                $new_id = mysqli_insert_id($conn);
                mysqli_query($conn, "INSERT INTO tour_hotel (tour_id, hotel_id) VALUES ($tour_id, $new_id)");
            }
            // ربط المطاعم
            if (isset($_POST['restaurants']) && is_array($_POST['restaurants'])) {
                foreach ($_POST['restaurants'] as $rest_id) {
                    $rest_id = intval($rest_id);
                    mysqli_query($conn, "INSERT INTO tour_restaurant (tour_id, restaurant_id) VALUES ($tour_id, $rest_id)");
                }
            }
            if (!empty($_POST['new_restaurant'])) {
                $new_rest = mysqli_real_escape_string($conn, $_POST['new_restaurant']);
                mysqli_query($conn, "INSERT INTO restaurants (name, city_id, country_id) VALUES ('$new_rest', $selected_city_id, $city_country_id)");
                $new_id = mysqli_insert_id($conn);
                mysqli_query($conn, "INSERT INTO tour_restaurant (tour_id, restaurant_id) VALUES ($tour_id, $new_id)");
            }
            // ربط المعالم
            if (isset($_POST['landmarks']) && is_array($_POST['landmarks'])) {
                foreach ($_POST['landmarks'] as $land_id) {
                    $land_id = intval($land_id);
                    mysqli_query($conn, "INSERT INTO tour_landmark (tour_id, landmark_id) VALUES ($tour_id, $land_id)");
                }
            }
            if (!empty($_POST['new_landmark'])) {
                $new_land = mysqli_real_escape_string($conn, $_POST['new_landmark']);
                mysqli_query($conn, "INSERT INTO landmarks (name, city_id, country_id) VALUES ('$new_land', $selected_city_id, $city_country_id)");
                $new_id = mysqli_insert_id($conn);
                mysqli_query($conn, "INSERT INTO tour_landmark (tour_id, landmark_id) VALUES ($tour_id, $new_id)");
            }

            mysqli_commit($conn);
            header("Location: view_tour.php?msg=updated");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Tour | TravelGram</title>
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

        .main-content { flex:1; margin-left:270px; padding:30px; position:relative; z-index:5; }
        .header {
            display:flex; justify-content:space-between; align-items:center;
            background:rgba(255,255,255,0.75); backdrop-filter:blur(15px);
            padding:18px 30px; border-radius:40px; margin-bottom:35px;
            border:1px solid rgba(255,255,255,0.5); box-shadow:0 10px 25px rgba(27,28,39,0.05);
        }
        .header h1 { display:flex; align-items:center; gap:12px; font-size:1.8rem; color:#1b1c27; font-weight:700; }
        .header-logo { width:42px; height:42px; border-radius:50%; object-fit:cover; border:2px solid #9ec1d7; }
        .user-info { display:flex; align-items:center; gap:25px; }
        .notification-icon { position:relative; font-size:1.5rem; color:#65403a; }
        .notification-badge {
            position:absolute; top:-8px; right:-12px; background:#ed4956; color:white;
            border-radius:50%; padding:3px 7px; font-size:11px; font-weight:bold; min-width:20px; text-align:center;
        }
        .avatar {
            width:48px; height:48px; border-radius:50%; background:#65403a; display:flex;
            align-items:center; justify-content:center; color:white; font-weight:bold; font-size:1.3rem;
            overflow:hidden; border:2px solid #9ec1d7;
        }
        .avatar img { width:100%; height:100%; object-fit:cover; }

        .form-container {
            background:rgba(255,255,255,0.75); backdrop-filter:blur(15px);
            border-radius:30px; padding:30px 35px; border:1px solid rgba(255,255,255,0.5);
            box-shadow:0 15px 30px rgba(27,28,39,0.05); max-width:950px; margin:0 auto;
        }
        h2 { color:#1b1c27; margin-bottom:25px; text-align:center; font-size:1.8rem; font-weight:700; }
        .top-links { margin-bottom:25px; display:flex; gap:20px; }
        .top-links a {
            color:#65403a; text-decoration:none; font-weight:600; transition:0.3s;
            padding:6px 16px; border-radius:40px; background:rgba(101,64,58,0.08);
        }
        .top-links a i { margin-right:6px; }
        .top-links a:hover { background:#65403a; color:white; }

        .form-group { margin-bottom:22px; }
        label { display:block; font-weight:600; margin-bottom:8px; color:#1b1c27; font-size:0.95rem; }
        input[type="text"], input[type="number"], input[type="date"], select, textarea {
            width:100%; padding:12px 18px; border:1.5px solid rgba(158,193,215,0.4);
            border-radius:40px; font-size:0.95rem; background:white; transition:0.3s;
            outline:none; font-family:inherit; color:#1b1c27;
        }
        input:focus, select:focus, textarea:focus {
            border-color:#65403a; box-shadow:0 0 0 4px rgba(101,64,58,0.1);
        }
        textarea { border-radius:30px; resize:vertical; }
        .readonly-field {
            background:rgba(239,241,238,0.6); padding:12px 18px;
            border:1.5px solid rgba(158,193,215,0.3); border-radius:40px; color:#5a6a7a;
        }
        .checkbox-group {
            border:1.5px solid rgba(158,193,215,0.4); border-radius:30px; padding:20px;
            max-height:220px; overflow-y:auto; background:rgba(239,241,238,0.4);
            display:flex; flex-wrap:wrap; gap:15px;
        }
        .checkbox-group label {
            display:inline-flex; align-items:center; gap:8px; margin-bottom:0;
            cursor:pointer; padding:5px 12px; background:rgba(255,255,255,0.5);
            border-radius:30px; transition:0.2s; font-weight:500;
        }
        .checkbox-group label:hover { background:rgba(158,193,215,0.2); }
        .checkbox-group input[type="checkbox"] { width:auto; margin:0; }
        .new-item { margin-top:15px; }

        .btn {
            display:inline-block; padding:8px 20px; border:none; border-radius:40px;
            cursor:pointer; text-decoration:none; font-weight:600; font-size:0.9rem;
            transition:0.2s; margin-right:10px; box-shadow:0 4px 10px rgba(0,0,0,0.05);
        }
        .btn-primary { background:#65403a; color:white; }
        .btn-cancel { background:#5a6a7a; color:white; }
        .btn:hover { transform:translateY(-2px); box-shadow:0 8px 15px rgba(0,0,0,0.1); opacity:0.9; }

        .error {
            background:rgba(220,53,69,0.1); backdrop-filter:blur(8px); color:#b91c1c;
            border-left:5px solid #b91c1c; padding:15px 20px; border-radius:40px; margin-bottom:25px;
        }
        .note { font-size:0.75rem; color:#7a8a9a; margin-top:5px; }
        .current-image { margin-top:10px; }
        .current-image img { max-width:180px; border-radius:20px; border:2px solid #9ec1d7; }
        h3 { color:#1b1c27; margin:25px 0 15px; font-size:1.3rem; font-weight:700; }

        @media (max-width:768px) {
            .sidebar { width:80px; }
            .sidebar-header h3, .sidebar-menu span { display:none; }
            .sidebar-menu i { margin-right:0; }
            .main-content { margin-left:80px; }
            .form-container { padding:20px 18px; }
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
                <?php if($pending_bookings_count > 0): ?><span class="msg-badge"><?php echo $pending_bookings_count; ?></span><?php endif; ?>
            </a></li>
            <li><a href="view_ratings.php"><i class="fas fa-star"></i> <span>Ratings</span></a></li>
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
            <div class="notification-icon">
                <i class="fas fa-bell"></i>
                <?php if($unread_notifications > 0): ?>
                    <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                <?php endif; ?>
            </div>
            <div class="avatar">
                <?php if(!empty($office_logo)): ?>
                    <img src="../<?php echo htmlspecialchars($office_logo); ?>" alt="Office Logo">
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

    <div class="form-container">
        <div class="top-links">
            <a href="view_tour.php"><i class="fas fa-arrow-left"></i> Back to Tours</a>
            <a href="view_station.php?tour_id=<?php echo $tour_id; ?>"><i class="fas fa-map-marker-alt"></i> View Stations</a>
        </div>

        <h2><i class="fas fa-edit" style="color:#65403a; margin-right:8px;"></i>Edit Tour: <?php echo htmlspecialchars($tour['title']); ?></h2>

        <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <!-- جميع الحقول كما هي في الكود السابق (اختصار للتوفير) -->
            <div class="form-group">
                <label>Tour Title:</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($tour['title']); ?>" required>
            </div>
            <div class="form-group">
                <label>Price:</label>
                <input type="number" step="0.01" name="price" value="<?php echo $tour['price']; ?>" required>
            </div>
            <div class="form-group">
                <label>Currency:</label>
                <select name="currency" required>
                    <option value="USD" <?php if($tour['currency']=='USD') echo 'selected'; ?>>USD</option>
                    <option value="EUR" <?php if($tour['currency']=='EUR') echo 'selected'; ?>>EUR</option>
                    <option value="SAR" <?php if($tour['currency']=='SAR') echo 'selected'; ?>>SAR</option>
                    <option value="EGP" <?php if($tour['currency']=='EGP') echo 'selected'; ?>>EGP</option>
                    <option value="TRY" <?php if($tour['currency']=='TRY') echo 'selected'; ?>>TRY</option>
                    <option value="AED" <?php if($tour['currency']=='AED') echo 'selected'; ?>>AED</option>
                </select>
            </div>
            <div class="form-group">
                <label>Duration (days):</label>
                <input type="number" name="duration_days" value="<?php echo $tour['duration_days']; ?>">
            </div>
            <div class="form-group">
                <label>Start Date:</label>
                <input type="date" name="start_date" value="<?php echo $tour['start_date']; ?>">
            </div>
            <div class="form-group">
                <label>End Date:</label>
                <input type="date" name="end_date" value="<?php echo $tour['end_date']; ?>">
            </div>
            <div class="form-group">
                <label>Booking Deadline:</label>
                <input type="date" name="booking_deadline" value="<?php echo $tour['booking_deadline']; ?>">
            </div>
            <div class="form-group">
                <label>Payment Type:</label>
                <select name="payment_type">
                    <option value="">-- Select --</option>
                    <option value="cash" <?php if($tour['payment_type']=='cash') echo 'selected'; ?>>Cash</option>
                    <option value="card" <?php if($tour['payment_type']=='card') echo 'selected'; ?>>Credit/Debit Card</option>
                    <option value="bank" <?php if($tour['payment_type']=='bank') echo 'selected'; ?>>Bank Transfer</option>
                    <option value="online" <?php if($tour['payment_type']=='online') echo 'selected'; ?>>Online Payment</option>
                </select>
            </div>
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" rows="5"><?php echo htmlspecialchars($tour['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label>Main Image:</label>
                <input type="file" name="main_image" accept="image/*">
                <?php if($tour['main_image']): ?>
                <div class="current-image">
                    <p>Current image:</p>
                    <img src="../<?php echo $tour['main_image']; ?>" alt="Tour image">
                </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Country:</label>
                <div class="readonly-field"><?php echo htmlspecialchars($country_name); ?></div>
                <div class="note">To change destination, please create a new tour.</div>
            </div>
            <div class="form-group">
                <label>City:</label>
                <div class="readonly-field"><?php echo htmlspecialchars($city_name); ?></div>
            </div>
            <div class="form-group">
                <label>Days in this city:</label>
                <input type="number" name="city_days" value="<?php echo $city_days; ?>" min="1">
            </div>

            <h3>Hotels</h3>
            <div class="checkbox-group">
                <?php while($h=mysqli_fetch_assoc($hotels_available)): ?>
                <label><input type="checkbox" name="hotels[]" value="<?php echo $h['id']; ?>" <?php if(in_array($h['id'],$selected_hotels)) echo 'checked'; ?>> <?php echo $h['name']; ?></label>
                <?php endwhile; ?>
            </div>
            <div class="new-item"><input type="text" name="new_hotel" placeholder="Or add new hotel name"></div>

            <h3>Restaurants</h3>
            <div class="checkbox-group">
                <?php while($r=mysqli_fetch_assoc($restaurants_available)): ?>
                <label><input type="checkbox" name="restaurants[]" value="<?php echo $r['id']; ?>" <?php if(in_array($r['id'],$selected_restaurants)) echo 'checked'; ?>> <?php echo $r['name']; ?></label>
                <?php endwhile; ?>
            </div>
            <div class="new-item"><input type="text" name="new_restaurant" placeholder="Or add new restaurant name"></div>

            <h3>Landmarks</h3>
            <div class="checkbox-group">
                <?php while($l=mysqli_fetch_assoc($landmarks_available)): ?>
                <label><input type="checkbox" name="landmarks[]" value="<?php echo $l['id']; ?>" <?php if(in_array($l['id'],$selected_landmarks)) echo 'checked'; ?>> <?php echo $l['name']; ?></label>
                <?php endwhile; ?>
            </div>
            <div class="new-item"><input type="text" name="new_landmark" placeholder="Or add new landmark name"></div>

            <div style="margin-top:30px;">
                <button type="submit" name="update_tour" class="btn btn-primary">Update Tour</button>
                <a href="view_tour.php" class="btn btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
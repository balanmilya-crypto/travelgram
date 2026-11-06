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

// ========== العدادات المطلوبة ==========
// جرس الإشعارات (غير مقروءة)
$unread_notifications = 0;
$un = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = $user_id AND is_read = 0");
if ($un && $unrow = mysqli_fetch_assoc($un)) $unread_notifications = $unrow['cnt'];

// رسائل غير مقروءة للماسنجر
$unread_messages = 0;
$msg_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM messages WHERE recipient_id = $user_id AND is_read = 0");
if ($msg_res && $msg_row = mysqli_fetch_assoc($msg_res)) $unread_messages = $msg_row['cnt'];

// الحجوزات المعلقة
$pending_bookings_count = 0;
$pb_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM bookings WHERE office_id=$office_id AND status='pending'");
if ($pb_res && $pb_row = mysqli_fetch_assoc($pb_res)) $pending_bookings_count = $pb_row['cnt'];

// الرحلة المحددة
$tour_id = isset($_GET['tour_id']) ? intval($_GET['tour_id']) : 0;
$check_tour = "SELECT id, title FROM tours WHERE id = $tour_id AND office_id = $office_id";
$tour_res = mysqli_query($conn, $check_tour);
if (mysqli_num_rows($tour_res) == 0) {
    die("Invalid tour or you don't have permission.");
}
$tour = mysqli_fetch_assoc($tour_res);
$tour_title = $tour['title'];

// معالجة إضافة محطة
$message = '';
$error = '';
if (isset($_POST['add_station'])) {
    $station_type = mysqli_real_escape_string($conn, $_POST['station_type']);
    $custom_name = mysqli_real_escape_string($conn, trim($_POST['custom_name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $station_order = intval($_POST['station_order']);
    $extra_info = mysqli_real_escape_string($conn, trim($_POST['extra_info']));

    if (empty($custom_name)) {
        $error = "Station name is required.";
    } else {
        $image_path = '';
        if (isset($_FILES['station_image']) && $_FILES['station_image']['error'] == 0) {
            $target_dir = "../uploads/stations/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $ext = strtolower(pathinfo($_FILES['station_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($ext, $allowed)) {
                $filename = time() . '_' . uniqid() . '.' . $ext;
                $target_file = $target_dir . $filename;
                if (move_uploaded_file($_FILES['station_image']['tmp_name'], $target_file)) {
                    $image_path = 'uploads/stations/' . $filename;
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Only JPG, JPEG, PNG, GIF are allowed.";
            }
        }

        if (empty($error)) {
            $insert = "INSERT INTO tour_stations (tour_id, station_type, custom_name, image_path, description, station_order, extra_info) 
                       VALUES ($tour_id, '$station_type', '$custom_name', '$image_path', '$description', $station_order, '$extra_info')";
            if (mysqli_query($conn, $insert)) {
                $message = "Station added successfully.";
            } else {
                $error = "Database error: " . mysqli_error($conn);
            }
        }
    }
}

// حذف محطة
if (isset($_GET['delete_station'])) {
    $station_id = intval($_GET['delete_station']);
    $get_image = mysqli_query($conn, "SELECT image_path FROM tour_stations WHERE id = $station_id AND tour_id = $tour_id");
    if ($row = mysqli_fetch_assoc($get_image)) {
        if (!empty($row['image_path']) && file_exists("../" . $row['image_path'])) {
            unlink("../" . $row['image_path']);
        }
    }
    $del = mysqli_query($conn, "DELETE FROM tour_stations WHERE id = $station_id AND tour_id = $tour_id");
    if ($del) {
        $message = "Station deleted successfully.";
    } else {
        $error = "Error deleting station: " . mysqli_error($conn);
    }
}

// جلب المحطات
$stations_sql = "SELECT * FROM tour_stations WHERE tour_id = $tour_id ORDER BY station_order";
$stations_result = mysqli_query($conn, $stations_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stations for "<?php echo htmlspecialchars($tour_title); ?>" | TravelGram</title>
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

        .data-container {
            background:rgba(255,255,255,0.75); backdrop-filter:blur(15px);
            border-radius:30px; padding:30px; border:1px solid rgba(255,255,255,0.5);
            box-shadow:0 15px 30px rgba(27,28,39,0.05); margin-bottom:30px;
        }
        h2, h3 { color:#1b1c27; margin-bottom:20px; font-weight:700; }
        h2 { font-size:1.6rem; }
        h3 { font-size:1.3rem; }

        .form-group { margin-bottom:20px; display:flex; flex-wrap:wrap; align-items:center; }
        .form-group label { width:130px; font-weight:600; color:#1b1c27; }
        .form-group input, .form-group select, .form-group textarea {
            flex:1; max-width:450px; padding:10px 16px; border:1.5px solid rgba(158,193,215,0.4);
            border-radius:40px; font-size:0.95rem; transition:0.3s; outline:none; background:white; font-family:inherit;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color:#65403a; box-shadow:0 0 0 3px rgba(101,64,58,0.1);
        }
        textarea { border-radius:25px; resize:vertical; }

        .btn {
            display:inline-block; padding:6px 14px; border:none; border-radius:30px;
            cursor:pointer; text-decoration:none; font-weight:600; font-size:0.8rem;
            transition:0.2s; margin-right:8px; box-shadow:0 2px 6px rgba(0,0,0,0.05);
        }
        .btn i { margin-right:5px; }
        .btn-primary { background:#65403a; color:white; }
        .btn-back { background:#5a6a7a; color:white; }
        .btn-edit { background:#9ec1d7; color:#1b1c27; }
        .btn-delete { background:#f8d7da; color:#721c24; }
        .btn:hover { transform:translateY(-1px); box-shadow:0 4px 10px rgba(0,0,0,0.1); opacity:0.9; }

        .message, .error {
            padding:15px 20px; border-radius:40px; margin-bottom:25px; backdrop-filter:blur(8px);
        }
        .message { background:rgba(40,167,69,0.1); color:#155724; border-left:5px solid #28a745; }
        .error { background:rgba(220,53,69,0.1); color:#b91c1c; border-left:5px solid #b91c1c; }

        /* بطاقات المحطات */
        .stations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }
        .station-card {
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            overflow: hidden;
            border: 1px solid rgba(158,193,215,0.3);
            transition: 0.3s;
            display: flex;
            flex-direction: column;
        }
        .station-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 35px rgba(101,64,58,0.15);
            border-color: #9ec1d7;
        }
        .station-img {
            width: 100%;
            height: 160px;
            background: rgba(239,241,238,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .station-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .station-img .no-image {
            font-size: 3rem;
            color: #65403a;
            opacity: 0.4;
        }
        .station-body {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .station-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1b1c27;
        }
        .station-type {
            display: inline-block;
            background: rgba(101,64,58,0.1);
            color: #65403a;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            align-self: flex-start;
        }
        .station-desc {
            font-size: 0.85rem;
            color: #5a6a7a;
            line-height: 1.5;
        }
        .station-meta {
            display: flex;
            gap: 15px;
            font-size: 0.8rem;
            color: #7a8a9a;
        }
        .station-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid rgba(158,193,215,0.2);
        }

        @media (max-width:768px) {
            .sidebar { width:80px; }
            .sidebar-header h3, .sidebar-menu span { display:none; }
            .sidebar-menu i { margin-right:0; }
            .main-content { margin-left:80px; }
            .form-group { flex-direction:column; align-items:flex-start; }
            .form-group label { width:auto; margin-bottom:8px; }
            .form-group input, .form-group select, .form-group textarea { max-width:100%; width:100%; }
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

    <div class="data-container">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:25px;">
            <h2><i class="fas fa-map-signs" style="color:#65403a; margin-right:10px;"></i>Stations for: <?php echo htmlspecialchars($tour_title); ?></h2>
            <div>
                <a href="view_tour.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Back to Tours</a>
                <a href="edit_tour.php?id=<?php echo $tour_id; ?>" class="btn btn-edit"><i class="fas fa-edit"></i> Edit Tour</a>
            </div>
        </div>

        <?php if($message): ?><div class="message"><?php echo $message; ?></div><?php endif; ?>
        <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>

        <!-- Add Station Form -->
        <div style="background:rgba(239,241,238,0.5); backdrop-filter:blur(5px); border-radius:25px; padding:25px; margin-bottom:35px; border:1px solid rgba(158,193,215,0.3);">
            <h3><i class="fas fa-plus-circle" style="color:#65403a;"></i> Add New Station</h3>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Type:</label>
                    <select name="station_type">
                        <option value="hotel">Hotel</option>
                        <option value="restaurant">Restaurant</option>
                        <option value="landmark">Landmark</option>
                        <option value="custom">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Name *:</label>
                    <input type="text" name="custom_name" required>
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Order:</label>
                    <input type="number" name="station_order" value="1" min="1">
                </div>
                <div class="form-group">
                    <label>Extra Info:</label>
                    <input type="text" name="extra_info" placeholder="e.g., 2 nights">
                </div>
                <div class="form-group">
                    <label>Image:</label>
                    <input type="file" name="station_image" accept="image/*">
                </div>
                <div class="form-group">
                    <button type="submit" name="add_station" class="btn btn-primary"><i class="fas fa-plus"></i> Add Station</button>
                </div>
            </form>
        </div>

        <!-- عرض المحطات كبطاقات -->
        <h3><i class="fas fa-list-ul" style="color:#65403a;"></i> Existing Stations</h3>
        <?php if(mysqli_num_rows($stations_result) > 0): ?>
            <div class="stations-grid">
                <?php while($station = mysqli_fetch_assoc($stations_result)): ?>
                    <div class="station-card">
                        <div class="station-img">
                            <?php if(!empty($station['image_path'])): ?>
                                <img src="../<?php echo htmlspecialchars($station['image_path']); ?>" alt="Station">
                            <?php else: ?>
                                <i class="fas fa-image no-image"></i>
                            <?php endif; ?>
                        </div>
                        <div class="station-body">
                            <div class="station-name"><?php echo htmlspecialchars($station['custom_name']); ?></div>
                            <span class="station-type"><?php echo ucfirst($station['station_type']); ?></span>
                            <?php if(!empty($station['description'])): ?>
                                <div class="station-desc"><?php echo nl2br(htmlspecialchars($station['description'])); ?></div>
                            <?php endif; ?>
                            <div class="station-meta">
                                <span><i class="fas fa-sort-numeric-down"></i> Order: <?php echo $station['station_order']; ?></span>
                                <?php if(!empty($station['extra_info'])): ?>
                                    <span><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($station['extra_info']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="station-actions">
                                <a href="edit_station.php?id=<?php echo $station['id']; ?>&tour_id=<?php echo $tour_id; ?>" class="btn btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                <a href="?delete_station=<?php echo $station['id']; ?>&tour_id=<?php echo $tour_id; ?>" class="btn btn-delete" onclick="return confirm('Delete this station?')"><i class="fas fa-trash-alt"></i> Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="color:#5a6a7a; text-align:center; padding:30px;">No stations added for this tour yet.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
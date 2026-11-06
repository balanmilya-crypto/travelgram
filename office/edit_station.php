<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'office_admin') {
    header('Location: ../login.php');
    exit();
}

$office_id = $_SESSION['user_office_id'];
$office_logo = '';
$office_name = '';
$office_info = mysqli_query($conn, "SELECT name, logo FROM offices WHERE id = $office_id");
if ($office_row = mysqli_fetch_assoc($office_info)) {
    $office_name = $office_row['name'];
    $office_logo = $office_row['logo'];
}
$admin_name = $_SESSION['user_name'] ?? 'Admin';

$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id == 0) {
    $u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE office_id = $office_id AND role = 'office_admin'"));
    if ($u) {
        $user_id = $u['id'];
        $_SESSION['user_id'] = $user_id;
    }
}

// ========== العدادات ==========
$unread_notifications = 0;
$un = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = $user_id AND is_read = 0");
if ($un && $unrow = mysqli_fetch_assoc($un)) $unread_notifications = $unrow['cnt'];

$unread_messages = 0;
$msg_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM messages WHERE recipient_id = $user_id AND is_read = 0");
if ($msg_res && $msg_row = mysqli_fetch_assoc($msg_res)) $unread_messages = $msg_row['cnt'];

$pending_bookings_count = 0;
$pb_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM bookings WHERE office_id = $office_id AND status = 'pending'");
if ($pb_res && $pb_row = mysqli_fetch_assoc($pb_res)) $pending_bookings_count = $pb_row['cnt'];

// باقي المتغيرات
$station_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tour_id = isset($_GET['tour_id']) ? intval($_GET['tour_id']) : 0;

$check = "SELECT ts.* FROM tour_stations ts
          JOIN tours t ON ts.tour_id = t.id
          WHERE ts.id = $station_id AND t.office_id = $office_id";
$check_res = mysqli_query($conn, $check);
if (mysqli_num_rows($check_res) == 0) {
    die("Invalid station or you don't have permission.");
}
$station = mysqli_fetch_assoc($check_res);

$city_sql = "SELECT city_id FROM tour_city WHERE tour_id = $tour_id";
$city_res = mysqli_query($conn, $city_sql);
if (mysqli_num_rows($city_res) == 0) {
    die("Tour has no city assigned.");
}
$city_row = mysqli_fetch_assoc($city_res);
$city_id = $city_row['city_id'];

$hotels_list = mysqli_query($conn, "SELECT id, name FROM hotels WHERE city_id = $city_id ORDER BY name");
$restaurants_list = mysqli_query($conn, "SELECT id, name FROM restaurants WHERE city_id = $city_id ORDER BY name");
$landmarks_list = mysqli_query($conn, "SELECT id, name FROM landmarks WHERE city_id = $city_id ORDER BY name");

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_station'])) {
    $station_type = mysqli_real_escape_string($conn, $_POST['station_type']);
    $reference_id = null;
    $custom_name = '';

    if ($station_type == 'hotel' && isset($_POST['existing_hotel']) && !empty($_POST['existing_hotel'])) {
        $reference_id = intval($_POST['existing_hotel']);
        $name_q = mysqli_query($conn, "SELECT name FROM hotels WHERE id = $reference_id");
        if ($row = mysqli_fetch_assoc($name_q)) {
            $custom_name = $row['name'];
        }
    } elseif ($station_type == 'restaurant' && isset($_POST['existing_restaurant']) && !empty($_POST['existing_restaurant'])) {
        $reference_id = intval($_POST['existing_restaurant']);
        $name_q = mysqli_query($conn, "SELECT name FROM restaurants WHERE id = $reference_id");
        if ($row = mysqli_fetch_assoc($name_q)) {
            $custom_name = $row['name'];
        }
    } elseif ($station_type == 'landmark' && isset($_POST['existing_landmark']) && !empty($_POST['existing_landmark'])) {
        $reference_id = intval($_POST['existing_landmark']);
        $name_q = mysqli_query($conn, "SELECT name FROM landmarks WHERE id = $reference_id");
        if ($row = mysqli_fetch_assoc($name_q)) {
            $custom_name = $row['name'];
        }
    } elseif ($station_type == 'custom' && isset($_POST['custom_name']) && !empty(trim($_POST['custom_name']))) {
        $custom_name = mysqli_real_escape_string($conn, trim($_POST['custom_name']));
        $reference_id = null;
    } else {
        $error = "Please select or enter a valid station name.";
    }

    if (empty($error)) {
        $description = mysqli_real_escape_string($conn, trim($_POST['description']));
        $station_order = intval($_POST['station_order']);
        $extra_info = mysqli_real_escape_string($conn, trim($_POST['extra_info']));

        $image_path = $station['image_path'];
        if (isset($_FILES['station_image']) && $_FILES['station_image']['error'] == 0) {
            $target_dir = "../uploads/stations/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $ext = strtolower(pathinfo($_FILES['station_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($ext, $allowed)) {
                $filename = time() . '_' . uniqid() . '.' . $ext;
                $target_file = $target_dir . $filename;
                if (move_uploaded_file($_FILES['station_image']['tmp_name'], $target_file)) {
                    if (!empty($station['image_path']) && file_exists("../" . $station['image_path'])) {
                        unlink("../" . $station['image_path']);
                    }
                    $image_path = 'uploads/stations/' . $filename;
                } else {
                    $error = "Failed to upload new image.";
                }
            } else {
                $error = "Only JPG, JPEG, PNG, GIF are allowed.";
            }
        }

        if (empty($error)) {
            $update = "UPDATE tour_stations SET 
                        station_type = '$station_type',
                        reference_id = " . ($reference_id ? $reference_id : "NULL") . ",
                        custom_name = '$custom_name',
                        description = '$description',
                        station_order = $station_order,
                        extra_info = '$extra_info',
                        image_path = '$image_path'
                      WHERE id = $station_id";
            if (mysqli_query($conn, $update)) {
                header("Location: view_station.php?tour_id=$tour_id&msg=updated");
                exit();
            } else {
                $error = "Database error: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Station | TravelGram</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            border-bottom: 1px solid rgba(158, 193, 215, 0.2);
        }
        .sidebar-header img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #9ec1d7; }
        .sidebar-header h3 { color: white; font-size: 1.5rem; font-weight: 700; }
        .sidebar-menu { padding: 20px 0; }
        .sidebar-menu ul { list-style: none; }
        .sidebar-menu li { margin-bottom: 8px; }
        .sidebar-menu a {
            display: flex; align-items: center; padding: 12px 20px;
            color: rgba(239, 241, 238, 0.8); text-decoration: none;
            border-radius: 12px; margin: 0 12px; transition: 0.3s; font-weight: 500;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: #65403a; color: white; box-shadow: 0 5px 15px rgba(101, 64, 58, 0.3);
        }
        .sidebar-menu i { margin-right: 15px; width: 24px; text-align: center; font-size: 1.2rem; }
        .msg-badge { background: #ed4956; color: white; border-radius: 30px; padding: 2px 10px; font-size: 0.75rem; font-weight: bold; margin-left: 10px; }
        .main-content { flex: 1; margin-left: 270px; padding: 30px; position: relative; z-index: 5; }
        .header {
            display: flex; justify-content: space-between; align-items: center;
            background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(15px);
            padding: 18px 30px; border-radius: 40px; margin-bottom: 35px;
            border: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 10px 25px rgba(27, 28, 39, 0.05);
        }
        .header h1 { display: flex; align-items: center; gap: 12px; font-size: 1.8rem; color: #1b1c27; font-weight: 700; }
        .header-logo { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid #9ec1d7; }
        .user-info { display: flex; align-items: center; gap: 25px; }
        .notification-icon { position: relative; font-size: 1.5rem; color: #65403a; }
        .notification-badge {
            position: absolute; top: -8px; right: -12px; background: #ed4956; color: white;
            border-radius: 50%; padding: 3px 7px; font-size: 11px; font-weight: bold; min-width: 20px; text-align: center;
        }
        .avatar {
            width: 48px; height: 48px; border-radius: 50%; background: #65403a;
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: bold; font-size: 1.3rem; overflow: hidden; border: 2px solid #9ec1d7;
        }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .form-container {
            background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(15px);
            border-radius: 30px; padding: 30px 35px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 15px 30px rgba(27, 28, 39, 0.05);
            max-width: 750px; margin: 0 auto;
        }
        h2 { color: #1b1c27; margin-bottom: 25px; font-size: 1.8rem; font-weight: 700; text-align: center; }
        .top-links { margin-bottom: 25px; display: flex; gap: 20px; }
        .top-links a {
            color: #65403a; text-decoration: none; font-weight: 600;
            padding: 6px 16px; border-radius: 40px; background: rgba(101, 64, 58, 0.08);
        }
        .top-links a i { margin-right: 6px; }
        .top-links a:hover { background: #65403a; color: white; }
        .form-group { margin-bottom: 22px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; color: #1b1c27; font-size: 0.95rem; }
        input, select, textarea {
            width: 100%; padding: 12px 18px; border: 1.5px solid rgba(158, 193, 215, 0.4);
            border-radius: 40px; font-size: 0.95rem; background: white;
            transition: all 0.3s; outline: none; font-family: inherit; color: #1b1c27;
        }
        input:focus, select:focus, textarea:focus {
            border-color: #65403a; box-shadow: 0 0 0 4px rgba(101, 64, 58, 0.1);
        }
        textarea { border-radius: 30px; resize: vertical; }
        .btn {
            display: inline-block; padding: 8px 20px; border: none; border-radius: 40px;
            cursor: pointer; text-decoration: none; font-weight: 600; font-size: 0.9rem;
            transition: all 0.2s; margin-right: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .btn-primary { background: #65403a; color: white; }
        .btn-cancel { background: #5a6a7a; color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); opacity: 0.9; }
        .error {
            background: rgba(220, 53, 69, 0.1); backdrop-filter: blur(8px);
            color: #b91c1c; border-left: 5px solid #b91c1c;
            padding: 15px 20px; border-radius: 40px; margin-bottom: 25px;
        }
        .current-image { margin-top: 10px; }
        .current-image img { max-width: 180px; border-radius: 20px; border: 2px solid #9ec1d7; }
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h3, .sidebar-menu span { display: none; }
            .sidebar-menu i { margin-right: 0; font-size: 1.4rem; }
            .main-content { margin-left: 80px; }
            .form-container { padding: 20px 18px; }
        }
    </style>
</head>
<body>
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>
<div class="sidebar">
    <div class="sidebar-header">
        <img src="../img/logo.jpg" alt="Logo"><h3>TravelGram</h3>
    </div>
    <div class="sidebar-menu">
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="add_tour.php"><i class="fas fa-plus-circle"></i> <span>Add Tour</span></a></li>
            <li><a href="view_tour.php"><i class="fas fa-list"></i> <span>Manage Tours</span></a></li>
            <li><a href="pending_tours.php"><i class="fas fa-clock"></i> <span>Pending Tours</span></a></li>
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
<div class="main-content">
    <div class="header">
        <h1><img src="../img/logo.jpg" class="header-logo"> TravelGram</h1>
        <div class="user-info">
            <div class="notification-icon">
                <i class="fas fa-bell"></i>
                <?php if ($unread_notifications > 0): ?>
                    <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                <?php endif; ?>
            </div>
            <div class="avatar">
                <?php if (!empty($office_logo)): ?><img src="../<?php echo htmlspecialchars($office_logo); ?>">
                <?php else: ?><?php echo substr($admin_name,0,1); ?><?php endif; ?>
            </div>
            <div>
                <strong style="color: #1b1c27;"><?php echo htmlspecialchars($admin_name); ?></strong>
                <div style="font-size:0.8rem; color:#5a6a7a;">Office Admin</div>
                <div style="font-size:0.75rem; color:#7a8a9a;"><?php echo htmlspecialchars($office_name); ?></div>
            </div>
        </div>
    </div>
    <div class="form-container">
        <div class="top-links">
            <a href="view_station.php?tour_id=<?php echo $tour_id; ?>"><i class="fas fa-arrow-left"></i> Back to Stations</a>
            <a href="view_tour.php"><i class="fas fa-list"></i> All Tours</a>
        </div>
        <h2><i class="fas fa-edit" style="color:#65403a; margin-right:8px;"></i>Edit Station</h2>
        <?php if ($error): ?><div class="error"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label><i class="fas fa-tag" style="color:#9ec1d7; margin-right:6px;"></i>Type:</label>
                <select name="station_type" id="station_type">
                    <option value="hotel" <?php if ($station['station_type'] == 'hotel') echo 'selected'; ?>>Hotel</option>
                    <option value="restaurant" <?php if ($station['station_type'] == 'restaurant') echo 'selected'; ?>>Restaurant</option>
                    <option value="landmark" <?php if ($station['station_type'] == 'landmark') echo 'selected'; ?>>Landmark</option>
                    <option value="custom" <?php if ($station['station_type'] == 'custom') echo 'selected'; ?>>Other (Custom)</option>
                </select>
            </div>
            <div class="form-group" id="existing_select_container"></div>
            <div class="form-group" id="custom_name_container" style="display:none;">
                <label><i class="fas fa-pencil-alt" style="color:#9ec1d7; margin-right:6px;"></i>Custom Name:</label>
                <input type="text" name="custom_name" value="<?php echo htmlspecialchars($station['custom_name']); ?>" placeholder="Enter custom station name">
            </div>
            <div class="form-group">
                <label><i class="fas fa-align-left" style="color:#9ec1d7; margin-right:6px;"></i>Description:</label>
                <textarea name="description" rows="4"><?php echo htmlspecialchars($station['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label><i class="fas fa-sort-numeric-up" style="color:#9ec1d7; margin-right:6px;"></i>Order:</label>
                <input type="number" name="station_order" value="<?php echo $station['station_order']; ?>" min="1">
            </div>
            <div class="form-group">
                <label><i class="fas fa-info-circle" style="color:#9ec1d7; margin-right:6px;"></i>Extra Info:</label>
                <input type="text" name="extra_info" value="<?php echo htmlspecialchars($station['extra_info']); ?>" placeholder="e.g., 2 nights">
            </div>
            <div class="form-group">
                <label><i class="fas fa-image" style="color:#9ec1d7; margin-right:6px;"></i>Image:</label>
                <input type="file" name="station_image" accept="image/*">
                <?php if ($station['image_path']): ?>
                    <div class="current-image">
                        <p style="margin-bottom:8px; color:#5a6a7a;">Current image:</p>
                        <img src="../<?php echo $station['image_path']; ?>" alt="Station image">
                    </div>
                <?php endif; ?>
            </div>
            <div style="margin-top:30px;">
                <button type="submit" name="update_station" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Update Station</button>
                <a href="view_station.php?tour_id=<?php echo $tour_id; ?>" class="btn btn-cancel"><i class="fas fa-times mr-2"></i>Cancel</a>
            </div>
        </form>
    </div>
</div>
<script>
    var hotels = <?php $hotels_arr = []; mysqli_data_seek($hotels_list,0); while($h=mysqli_fetch_assoc($hotels_list)) $hotels_arr[]=$h; echo json_encode($hotels_arr); ?>;
    var restaurants = <?php $rest_arr = []; mysqli_data_seek($restaurants_list,0); while($r=mysqli_fetch_assoc($restaurants_list)) $rest_arr[]=$r; echo json_encode($rest_arr); ?>;
    var landmarks = <?php $land_arr = []; mysqli_data_seek($landmarks_list,0); while($l=mysqli_fetch_assoc($landmarks_list)) $land_arr[]=$l; echo json_encode($land_arr); ?>;
    var current_type = "<?php echo $station['station_type']; ?>";
    var current_ref = <?php echo $station['reference_id'] ? $station['reference_id'] : 'null'; ?>;
    var current_custom = "<?php echo htmlspecialchars($station['custom_name']); ?>";

    function updateSelectField(type) {
        var container = document.getElementById('existing_select_container');
        var customContainer = document.getElementById('custom_name_container');
        container.innerHTML = '';
        if (type === 'custom') {
            customContainer.style.display = 'block';
            return;
        } else {
            customContainer.style.display = 'none';
        }
        var options = [];
        var selectName = '';
        if (type === 'hotel') { options = hotels; selectName = 'existing_hotel'; }
        else if (type === 'restaurant') { options = restaurants; selectName = 'existing_restaurant'; }
        else if (type === 'landmark') { options = landmarks; selectName = 'existing_landmark'; }
        if (options.length === 0) {
            container.innerHTML = '<p class="error" style="background:transparent; padding:0;">No items available for this city.</p>';
            return;
        }
        var select = document.createElement('select');
        select.name = selectName;
        select.id = selectName;
        select.style.width = '100%';
        var emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.text = '-- Select --';
        select.appendChild(emptyOption);
        for (var i = 0; i < options.length; i++) {
            var opt = document.createElement('option');
            opt.value = options[i].id;
            opt.text = options[i].name;
            if (current_ref !== null && current_ref == options[i].id) opt.selected = true;
            select.appendChild(opt);
        }
        container.appendChild(select);
    }
    document.getElementById('station_type').addEventListener('change', function() {
        updateSelectField(this.value);
    });
    updateSelectField(current_type);
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
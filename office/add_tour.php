<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'office_admin') {
    header('Location: ../login.php');
    exit();
}
$office_id = $_SESSION['user_office_id'];

// متغيرات الحالة
$step = 1;
$selected_country = 0;
$selected_city = 0;
$tour_id = 0;
$error = '';
$success = '';

// استقبال المتغيرات من النموذج
if (isset($_POST['country_id'])) {
    $selected_country = intval($_POST['country_id']);
}
if (isset($_POST['city_id'])) {
    $selected_city = intval($_POST['city_id']);
}
if (isset($_POST['tour_id'])) {
    $tour_id = intval($_POST['tour_id']);
}

// معالجة التنقل بين الخطوات
if (isset($_POST['next1'])) {
    $step = 2;
    $selected_country = intval($_POST['country_id']);
}
if (isset($_POST['next2'])) {
    $step = 3;
    $selected_country = intval($_POST['country_id']);
    $selected_city = intval($_POST['city_id']);
}
if (isset($_POST['back1'])) {
    $step = 1;
}
if (isset($_POST['back2'])) {
    $step = 2;
}
if (isset($_POST['back3'])) {
    $step = 3;
}

// معالجة حفظ الرحلة الأساسية (الخطوة 3)
if (isset($_POST['save_basic_tour'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $price = floatval($_POST['price']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $selected_city = intval($_POST['city_id']);
    
    $currency = !empty($_POST['currency']) ? "'" . mysqli_real_escape_string($conn, $_POST['currency']) . "'" : "'USD'";
    $duration_days = !empty($_POST['duration_days']) ? intval($_POST['duration_days']) : 'NULL';
    $start_date = !empty($_POST['start_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['start_date']) . "'" : 'NULL';
    $end_date = !empty($_POST['end_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['end_date']) . "'" : 'NULL';
    $booking_deadline = !empty($_POST['booking_deadline']) ? "'" . mysqli_real_escape_string($conn, $_POST['booking_deadline']) . "'" : 'NULL';
    $payment_type = !empty($_POST['payment_type']) ? "'" . mysqli_real_escape_string($conn, $_POST['payment_type']) . "'" : 'NULL';
    $city_days = isset($_POST['city_days']) ? intval($_POST['city_days']) : 1;
    if ($city_days < 1) $city_days = 1;
    $max_participants = !empty($_POST['max_participants']) ? intval($_POST['max_participants']) : 'NULL';

    $main_image = '';
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
        $target_dir = "../uploads/tours/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $extension = pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . uniqid() . '.' . $extension;
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES['main_image']['tmp_name'], $target_file)) {
            $main_image = 'uploads/tours/' . $filename;
        } else {
            $error = "Failed to upload main image.";
        }
    }

    if (empty($error)) {
        mysqli_begin_transaction($conn);
        try {
            // ========== تم تضمين status = 'pending' بشكل صريح ==========
            $sql_tour = "INSERT INTO tours (office_id, title, description, price, max_participants, currency, main_image, duration_days, start_date, end_date, booking_deadline, payment_type, status, created_at) 
                         VALUES ($office_id, '$title', '$description', $price, $max_participants, $currency, '$main_image', $duration_days, $start_date, $end_date, $booking_deadline, $payment_type, 'pending', NOW())";
            if (!mysqli_query($conn, $sql_tour)) {
                throw new Exception("Error inserting tour: " . mysqli_error($conn));
            }
            $tour_id = mysqli_insert_id($conn);

            $sql_tour_city = "INSERT INTO tour_city (tour_id, city_id, days) VALUES ($tour_id, $selected_city, $city_days)";
            if (!mysqli_query($conn, $sql_tour_city)) {
                throw new Exception("Error linking city: " . mysqli_error($conn));
            }

            $city_check = mysqli_query($conn, "SELECT country_id FROM cities WHERE id = $selected_city");
            if (mysqli_num_rows($city_check) == 0) {
                throw new Exception("Invalid city selected.");
            }
            $city_data = mysqli_fetch_assoc($city_check);
            $city_country_id = $city_data['country_id'];

            if (isset($_POST['hotels']) && is_array($_POST['hotels'])) {
                foreach ($_POST['hotels'] as $hotel_id) {
                    $hotel_id = intval($hotel_id);
                    $sql = "INSERT INTO tour_hotel (tour_id, hotel_id) VALUES ($tour_id, $hotel_id)";
                    if (!mysqli_query($conn, $sql)) {
                        throw new Exception("Error linking hotel ID $hotel_id: " . mysqli_error($conn));
                    }
                }
            }

            if (!empty($_POST['new_hotel'])) {
                $new_hotel = mysqli_real_escape_string($conn, $_POST['new_hotel']);
                $sql_new = "INSERT INTO hotels (name, city_id, country_id) VALUES ('$new_hotel', $selected_city, $city_country_id)";
                if (!mysqli_query($conn, $sql_new)) {
                    throw new Exception("Error inserting new hotel: " . mysqli_error($conn));
                }
                $new_id = mysqli_insert_id($conn);
                $sql_link = "INSERT INTO tour_hotel (tour_id, hotel_id) VALUES ($tour_id, $new_id)";
                if (!mysqli_query($conn, $sql_link)) {
                    throw new Exception("Error linking new hotel: " . mysqli_error($conn));
                }
            }

            if (isset($_POST['restaurants']) && is_array($_POST['restaurants'])) {
                foreach ($_POST['restaurants'] as $rest_id) {
                    $rest_id = intval($rest_id);
                    $sql = "INSERT INTO tour_restaurant (tour_id, restaurant_id) VALUES ($tour_id, $rest_id)";
                    if (!mysqli_query($conn, $sql)) {
                        throw new Exception("Error linking restaurant ID $rest_id: " . mysqli_error($conn));
                    }
                }
            }

            if (!empty($_POST['new_restaurant'])) {
                $new_rest = mysqli_real_escape_string($conn, $_POST['new_restaurant']);
                $sql_new = "INSERT INTO restaurants (name, city_id, country_id) VALUES ('$new_rest', $selected_city, $city_country_id)";
                if (!mysqli_query($conn, $sql_new)) {
                    throw new Exception("Error inserting new restaurant: " . mysqli_error($conn));
                }
                $new_id = mysqli_insert_id($conn);
                $sql_link = "INSERT INTO tour_restaurant (tour_id, restaurant_id) VALUES ($tour_id, $new_id)";
                if (!mysqli_query($conn, $sql_link)) {
                    throw new Exception("Error linking new restaurant: " . mysqli_error($conn));
                }
            }

            if (isset($_POST['landmarks']) && is_array($_POST['landmarks'])) {
                foreach ($_POST['landmarks'] as $land_id) {
                    $land_id = intval($land_id);
                    $sql = "INSERT INTO tour_landmark (tour_id, landmark_id) VALUES ($tour_id, $land_id)";
                    if (!mysqli_query($conn, $sql)) {
                        throw new Exception("Error linking landmark ID $land_id: " . mysqli_error($conn));
                    }
                }
            }

            if (!empty($_POST['new_landmark'])) {
                $new_land = mysqli_real_escape_string($conn, $_POST['new_landmark']);
                $sql_new = "INSERT INTO landmarks (name, city_id, country_id) VALUES ('$new_land', $selected_city, $city_country_id)";
                if (!mysqli_query($conn, $sql_new)) {
                    throw new Exception("Error inserting new landmark: " . mysqli_error($conn));
                }
                $new_id = mysqli_insert_id($conn);
                $sql_link = "INSERT INTO tour_landmark (tour_id, landmark_id) VALUES ($tour_id, $new_id)";
                if (!mysqli_query($conn, $sql_link)) {
                    throw new Exception("Error linking new landmark: " . mysqli_error($conn));
                }
            }

            mysqli_commit($conn);
            
            // ========== إرسال إشعار للسوبر أدمن ==========
            $super_admin_query = mysqli_query($conn, "SELECT id FROM users WHERE role = 'super_admin' LIMIT 1");
            if ($super_admin_query && $super_admin_row = mysqli_fetch_assoc($super_admin_query)) {
                $super_admin_id = $super_admin_row['id'];
                
                $office_name_query = mysqli_query($conn, "SELECT name FROM offices WHERE id = $office_id");
                $office_name = mysqli_fetch_assoc($office_name_query)['name'];
                
                $notif_title = "🆕 New Tour Pending Approval";
                $notif_message = "Office '{$office_name}' has added a new tour: '{$title}'. It is waiting for your approval.";
                
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, is_read, created_at) VALUES (?, 'new_tour_pending', ?, ?, 0, NOW())");
                $stmt->bind_param("iss", $super_admin_id, $notif_title, $notif_message);
                $stmt->execute();
                $stmt->close();
            }
            
            $step = 4;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Error saving basic tour: " . $e->getMessage();
        }
    }
}

// معالجة حفظ المحطات (الخطوة 4)
if (isset($_POST['save_stations'])) {
    $tour_id = intval($_POST['tour_id']);

    mysqli_begin_transaction($conn);
    try {
        for ($i = 1; $i <= 7; $i++) {
            if (isset($_POST['station_type'][$i]) && !empty($_POST['station_type'][$i])) {
                $station_type = mysqli_real_escape_string($conn, $_POST['station_type'][$i]);
                $reference_id = null;
                $custom_name = '';

                if ($station_type == 'hotel' && isset($_POST['existing_hotel'][$i]) && !empty($_POST['existing_hotel'][$i])) {
                    $reference_id = intval($_POST['existing_hotel'][$i]);
                } elseif ($station_type == 'restaurant' && isset($_POST['existing_restaurant'][$i]) && !empty($_POST['existing_restaurant'][$i])) {
                    $reference_id = intval($_POST['existing_restaurant'][$i]);
                } elseif ($station_type == 'landmark' && isset($_POST['existing_landmark'][$i]) && !empty($_POST['existing_landmark'][$i])) {
                    $reference_id = intval($_POST['existing_landmark'][$i]);
                } elseif ($station_type == 'custom' && isset($_POST['custom_name'][$i]) && !empty($_POST['custom_name'][$i])) {
                    $custom_name = mysqli_real_escape_string($conn, $_POST['custom_name'][$i]);
                } else {
                    continue;
                }

                if ($reference_id) {
                    if ($station_type == 'hotel') {
                        $res = mysqli_query($conn, "SELECT name FROM hotels WHERE id = $reference_id");
                        if ($row = mysqli_fetch_assoc($res)) {
                            $custom_name = $row['name'];
                        }
                    } elseif ($station_type == 'restaurant') {
                        $res = mysqli_query($conn, "SELECT name FROM restaurants WHERE id = $reference_id");
                        if ($row = mysqli_fetch_assoc($res)) {
                            $custom_name = $row['name'];
                        }
                    } elseif ($station_type == 'landmark') {
                        $res = mysqli_query($conn, "SELECT name FROM landmarks WHERE id = $reference_id");
                        if ($row = mysqli_fetch_assoc($res)) {
                            $custom_name = $row['name'];
                        }
                    }
                }

                $station_image = '';
                if (isset($_FILES['station_image']['name'][$i]) && $_FILES['station_image']['error'][$i] == 0) {
                    $target_dir = "../uploads/stations/";
                    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                    $ext = pathinfo($_FILES['station_image']['name'][$i], PATHINFO_EXTENSION);
                    $filename = time() . '_' . uniqid() . '.' . $ext;
                    $target_file = $target_dir . $filename;
                    if (move_uploaded_file($_FILES['station_image']['tmp_name'][$i], $target_file)) {
                        $station_image = 'uploads/stations/' . $filename;
                    } else {
                        throw new Exception("Failed to upload image for station $i");
                    }
                }

                $station_desc = mysqli_real_escape_string($conn, $_POST['station_desc'][$i] ?? '');
                $station_order = intval($_POST['station_order'][$i] ?? $i);
                $extra_info = mysqli_real_escape_string($conn, $_POST['extra_info'][$i] ?? '');

                $sql_station = "INSERT INTO tour_stations (tour_id, station_type, reference_id, custom_name, image_path, description, station_order, extra_info) 
                                VALUES ($tour_id, '$station_type', " . ($reference_id ? $reference_id : "NULL") . ", '$custom_name', '$station_image', '$station_desc', $station_order, '$extra_info')";
                if (!mysqli_query($conn, $sql_station)) {
                    throw new Exception("Error inserting station: " . mysqli_error($conn));
                }
            }
        }
        mysqli_commit($conn);
        $success = "Tour added successfully! It is now pending approval by the administrator.";
        $step = 1;
        $selected_country = 0;
        $selected_city = 0;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Error saving stations: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add New Tour | TravelGram</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Cairo', 'Poppins', sans-serif;
            background: #eff1ee;
            color: #1b1c27;
            padding: 20px;
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

        .container {
            max-width: 950px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(15px);
            border-radius: 40px;
            padding: 35px 40px;
            box-shadow: 0 30px 50px rgba(27, 28, 39, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            position: relative;
            z-index: 10;
        }

        h2 {
            text-align: center;
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #65403a, #9ec1d7);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 25px;
            letter-spacing: -0.02em;
        }

        .back-link {
            display: inline-block;
            background: #65403a;
            color: white;
            padding: 10px 22px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 25px;
            transition: 0.3s;
            box-shadow: 0 8px 20px rgba(101, 64, 58, 0.2);
        }
        .back-link i { margin-right: 8px; }
        .back-link:hover {
            background: #4f322c;
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(101, 64, 58, 0.3);
        }

        .form-group { margin-bottom: 25px; }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1b1c27;
            font-size: 0.95rem;
        }

        input, select, textarea {
            width: 100%;
            padding: 14px 18px;
            border: 1.5px solid rgba(158, 193, 215, 0.4);
            border-radius: 40px;
            font-size: 1rem;
            background: white;
            transition: all 0.3s;
            outline: none;
            font-family: inherit;
            color: #1b1c27;
        }
        input:focus, select:focus, textarea:focus {
            border-color: #65403a;
            box-shadow: 0 0 0 4px rgba(101, 64, 58, 0.1);
        }
        textarea { border-radius: 30px; resize: vertical; }

        .checkbox-group {
            border: 1.5px solid rgba(158, 193, 215, 0.4);
            border-radius: 30px;
            padding: 20px;
            max-height: 250px;
            overflow-y: auto;
            background: rgba(239, 241, 238, 0.4);
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }
        .checkbox-group label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 0;
            white-space: nowrap;
            cursor: pointer;
            padding: 5px 10px;
            background: rgba(255,255,255,0.5);
            border-radius: 30px;
            transition: 0.2s;
        }
        .checkbox-group label:hover {
            background: rgba(158, 193, 215, 0.2);
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .add-item-row {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            align-items: center;
        }
        .add-item-row input {
            flex: 1;
        }
        .add-btn {
            background: #9ec1d7;
            color: #1b1c27;
            border: none;
            border-radius: 40px;
            padding: 12px 20px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            white-space: nowrap;
        }
        .add-btn:hover {
            background: #65403a;
            color: white;
        }

        .station-box {
            border: 1.5px solid rgba(158, 193, 215, 0.4);
            border-radius: 30px;
            padding: 25px;
            margin-bottom: 25px;
            background: rgba(239, 241, 238, 0.4);
        }
        .station-box h4 {
            margin-bottom: 20px;
            color: #1b1c27;
            font-size: 1.3rem;
            border-right: 5px solid #9ec1d7;
            padding-right: 15px;
        }

        .btn {
            background: #65403a;
            color: white;
            padding: 12px 28px;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 15px;
            margin-top: 15px;
            box-shadow: 0 8px 20px rgba(101, 64, 58, 0.2);
            display: inline-block;
            text-decoration: none;
        }
        .btn-back {
            background: transparent;
            color: #65403a;
            border: 2px solid #9ec1d7;
            box-shadow: none;
        }
        .btn-back:hover {
            background: #9ec1d7;
            color: #1b1c27;
            border-color: #9ec1d7;
            transform: translateY(-2px);
        }
        .btn:hover {
            background: #4f322c;
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(101, 64, 58, 0.3);
        }

        .error {
            background: rgba(220, 53, 69, 0.1);
            backdrop-filter: blur(8px);
            color: #b91c1c;
            border-left: 5px solid #b91c1c;
            padding: 15px 20px;
            border-radius: 40px;
            margin-bottom: 25px;
        }

        .success {
            background: rgba(40, 167, 69, 0.1);
            backdrop-filter: blur(8px);
            color: #155724;
            border-left: 5px solid #28a745;
            padding: 15px 20px;
            border-radius: 40px;
            margin-bottom: 25px;
        }

        .select2-container--default .select2-selection--single {
            height: 50px;
            padding: 4px;
            border: 1.5px solid rgba(158, 193, 215, 0.4);
            border-radius: 40px;
            background: white;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 40px;
            padding-left: 18px;
            color: #1b1c27;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 48px;
            right: 15px;
        }
        .select2-dropdown {
            border-radius: 30px;
            border: 1.5px solid #9ec1d7;
            overflow: hidden;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background: #65403a;
        }

        @media (max-width: 768px) {
            .container { padding: 25px 20px; }
            h2 { font-size: 1.8rem; }
            .add-item-row { flex-direction: column; }
            .add-btn { width: 100%; }
        }
    </style>
</head>
<body>
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<div class="container">
    <h2><i class="fas fa-plus-circle" style="color: #65403a; margin-right: 10px;"></i>Add New Tour</h2>
    <div>
        <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php if ($error): ?>
        <div class="error"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><i class="fas fa-check-circle mr-2"></i><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" id="tourForm">
        <input type="hidden" name="country_id" value="<?php echo $selected_country; ?>">
        <input type="hidden" name="city_id" value="<?php echo $selected_city; ?>">
        <input type="hidden" name="tour_id" value="<?php echo $tour_id; ?>">

        <?php if ($step == 1): ?>
            <div class="form-group">
                <label><i class="fas fa-globe-asia" style="color: #9ec1d7; margin-right: 8px;"></i>Select Country:</label>
                <select name="country_id" id="countrySelect" required style="width: 100%;">
                    <option value="">-- Choose Country --</option>
                    <?php
                    $countries = mysqli_query($conn, "SELECT id, name, flag FROM countries ORDER BY name");
                    while ($row = mysqli_fetch_assoc($countries)) {
                        $selected = ($selected_country == $row['id']) ? 'selected' : '';
                        $flag_path = !empty($row['flag']) ? '../uploads/flags/' . $row['flag'] : '';
                        echo "<option value='{$row['id']}' data-flag='{$flag_path}' $selected>{$row['name']}</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit" name="next1" class="btn"><i class="fas fa-arrow-right mr-2"></i>Next</button>

        <?php elseif ($step == 2): ?>
            <input type="hidden" name="country_id" value="<?php echo $selected_country; ?>">
            <div class="form-group">
                <label><i class="fas fa-city" style="color: #9ec1d7; margin-right: 8px;"></i>Select City:</label>
                <select name="city_id" required>
                    <option value="">-- Choose City --</option>
                    <?php
                    $cities = mysqli_query($conn, "SELECT id, name FROM cities WHERE country_id = $selected_country ORDER BY name");
                    while ($row = mysqli_fetch_assoc($cities)) {
                        $selected = ($selected_city == $row['id']) ? 'selected' : '';
                        echo "<option value='{$row['id']}' $selected>{$row['name']}</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit" name="next2" class="btn"><i class="fas fa-arrow-right mr-2"></i>Next</button>
            <button type="submit" name="back1" class="btn btn-back"><i class="fas fa-arrow-left mr-2"></i>Back</button>

        <?php elseif ($step == 3): ?>
            <input type="hidden" name="country_id" value="<?php echo $selected_country; ?>">
            <input type="hidden" name="city_id" value="<?php echo $selected_city; ?>">

            <div class="form-group"><label><i class="fas fa-heading" style="color: #9ec1d7; margin-right: 8px;"></i>Tour Title:</label><input type="text" name="title" placeholder="e.g., Amazing Paris Getaway" required></div>
            <div class="form-group"><label><i class="fas fa-dollar-sign" style="color: #9ec1d7; margin-right: 8px;"></i>Price:</label><input type="number" step="0.01" name="price" placeholder="0.00" required></div>
            <div class="form-group"><label><i class="fas fa-users" style="color: #9ec1d7; margin-right: 8px;"></i>Max Participants:</label><input type="number" name="max_participants" min="1" placeholder="Leave empty for unlimited"></div>
            <div class="form-group"><label><i class="fas fa-money-bill-wave" style="color: #9ec1d7; margin-right: 8px;"></i>Currency:</label><select name="currency" required><option value="">-- Select currency --</option><option value="USD">USD</option><option value="EUR">EUR</option><option value="SAR">SAR</option><option value="EGP">EGP</option><option value="TRY">TRY</option><option value="AED">AED</option></select></div>
            <div class="form-group"><label><i class="fas fa-calendar-alt" style="color: #9ec1d7; margin-right: 8px;"></i>Duration (days):</label><input type="number" name="duration_days" min="1" placeholder="e.g., 5"></div>
            <div class="form-group"><label><i class="fas fa-play-circle" style="color: #9ec1d7; margin-right: 8px;"></i>Start Date:</label><input type="date" name="start_date"></div>
            <div class="form-group"><label><i class="fas fa-stop-circle" style="color: #9ec1d7; margin-right: 8px;"></i>End Date:</label><input type="date" name="end_date"></div>
            <div class="form-group"><label><i class="fas fa-hourglass-end" style="color: #9ec1d7; margin-right: 8px;"></i>Booking Deadline:</label><input type="date" name="booking_deadline"></div>
            <div class="form-group"><label><i class="fas fa-credit-card" style="color: #9ec1d7; margin-right: 8px;"></i>Payment Type:</label><select name="payment_type"><option value="">-- Select payment type --</option><option value="cash">Cash</option><option value="card">Credit/Debit Card</option><option value="bank">Bank Transfer</option><option value="online">Online Payment</option></select></div>
            <div class="form-group"><label><i class="fas fa-align-left" style="color: #9ec1d7; margin-right: 8px;"></i>Description:</label><textarea name="description" rows="4" placeholder="Describe the tour..."></textarea></div>
            <div class="form-group"><label><i class="fas fa-image" style="color: #9ec1d7; margin-right: 8px;"></i>Main Image:</label><input type="file" name="main_image" accept="image/*"></div>

            <h3 style="color: #1b1c27; margin: 30px 0 20px;"><i class="fas fa-hotel" style="color: #9ec1d7; margin-right: 10px;"></i>Hotels (optional)</h3>
            <div class="checkbox-group" id="hotelsCheckboxGroup">
                <?php
                $hotels = mysqli_query($conn, "SELECT id, name FROM hotels WHERE city_id = $selected_city ORDER BY name");
                if (mysqli_num_rows($hotels) > 0) {
                    while ($h = mysqli_fetch_assoc($hotels)) {
                        echo "<label><input type='checkbox' name='hotels[]' value='{$h['id']}'> {$h['name']}</label>";
                    }
                } else {
                    echo "<p style='color: #5a6a7a;'>No hotels available for this city.</p>";
                }
                ?>
            </div>
            <div class="add-item-row">
                <input type="text" id="newHotelName" placeholder="Add new hotel name">
                <button type="button" class="add-btn" id="addHotelBtn"><i class="fas fa-plus mr-1"></i>Add Hotel</button>
            </div>

            <h3 style="color: #1b1c27; margin: 30px 0 20px;"><i class="fas fa-utensils" style="color: #9ec1d7; margin-right: 10px;"></i>Restaurants (optional)</h3>
            <div class="checkbox-group" id="restaurantsCheckboxGroup">
                <?php
                $restaurants = mysqli_query($conn, "SELECT id, name FROM restaurants WHERE city_id = $selected_city ORDER BY name");
                if (mysqli_num_rows($restaurants) > 0) {
                    while ($r = mysqli_fetch_assoc($restaurants)) {
                        echo "<label><input type='checkbox' name='restaurants[]' value='{$r['id']}'> {$r['name']}</label>";
                    }
                } else {
                    echo "<p style='color: #5a6a7a;'>No restaurants available.</p>";
                }
                ?>
            </div>
            <div class="add-item-row">
                <input type="text" id="newRestaurantName" placeholder="Add new restaurant name">
                <button type="button" class="add-btn" id="addRestaurantBtn"><i class="fas fa-plus mr-1"></i>Add Restaurant</button>
            </div>

            <h3 style="color: #1b1c27; margin: 30px 0 20px;"><i class="fas fa-monument" style="color: #9ec1d7; margin-right: 10px;"></i>Landmarks (optional)</h3>
            <div class="checkbox-group" id="landmarksCheckboxGroup">
                <?php
                $landmarks = mysqli_query($conn, "SELECT id, name FROM landmarks WHERE city_id = $selected_city ORDER BY name");
                if (mysqli_num_rows($landmarks) > 0) {
                    while ($l = mysqli_fetch_assoc($landmarks)) {
                        echo "<label><input type='checkbox' name='landmarks[]' value='{$l['id']}'> {$l['name']}</label>";
                    }
                } else {
                    echo "<p style='color: #5a6a7a;'>No landmarks available.</p>";
                }
                ?>
            </div>
            <div class="add-item-row">
                <input type="text" id="newLandmarkName" placeholder="Add new landmark name">
                <button type="button" class="add-btn" id="addLandmarkBtn"><i class="fas fa-plus mr-1"></i>Add Landmark</button>
            </div>

            <button type="submit" name="save_basic_tour" class="btn"><i class="fas fa-save mr-2"></i>Save Basic Info & Next</button>
            <button type="submit" name="back2" class="btn btn-back"><i class="fas fa-arrow-left mr-2"></i>Back</button>

        <?php elseif ($step == 4): ?>
            <h3 style="color: #1b1c27; margin: 20px 0;"><i class="fas fa-map-signs" style="color: #9ec1d7; margin-right: 10px;"></i>Add Tour Stations (up to 7)</h3>
            <p style="color: #5a6a7a; margin-bottom: 25px;">You can either select an existing item or enter a custom name for each station.</p>

            <?php for ($i = 1; $i <= 7; $i++): ?>
            <div class="station-box" id="station_<?php echo $i; ?>">
                <h4><i class="fas fa-map-pin" style="color: #65403a; margin-right: 8px;"></i>Station <?php echo $i; ?></h4>
                <div class="form-group">
                    <label>Type:</label>
                    <select name="station_type[<?php echo $i; ?>]" class="station-type" data-index="<?php echo $i; ?>">
                        <option value="">-- Select type --</option>
                        <option value="hotel">Hotel</option>
                        <option value="restaurant">Restaurant</option>
                        <option value="landmark">Landmark</option>
                        <option value="custom">Other (custom name)</option>
                    </select>
                </div>
                <div class="form-group existing-item" id="existing_hotel_<?php echo $i; ?>" style="display:none;">
                    <label>Select existing hotel:</label>
                    <select name="existing_hotel[<?php echo $i; ?>]"><option value="">-- Choose hotel --</option>
                        <?php $hotels_q = mysqli_query($conn, "SELECT id, name FROM hotels WHERE city_id = $selected_city ORDER BY name"); while ($h = mysqli_fetch_assoc($hotels_q)) echo "<option value='{$h['id']}'>{$h['name']}</option>"; ?>
                    </select>
                </div>
                <div class="form-group existing-item" id="existing_restaurant_<?php echo $i; ?>" style="display:none;">
                    <label>Select existing restaurant:</label>
                    <select name="existing_restaurant[<?php echo $i; ?>]"><option value="">-- Choose restaurant --</option>
                        <?php $rest_q = mysqli_query($conn, "SELECT id, name FROM restaurants WHERE city_id = $selected_city ORDER BY name"); while ($r = mysqli_fetch_assoc($rest_q)) echo "<option value='{$r['id']}'>{$r['name']}</option>"; ?>
                    </select>
                </div>
                <div class="form-group existing-item" id="existing_landmark_<?php echo $i; ?>" style="display:none;">
                    <label>Select existing landmark:</label>
                    <select name="existing_landmark[<?php echo $i; ?>]"><option value="">-- Choose landmark --</option>
                        <?php $land_q = mysqli_query($conn, "SELECT id, name FROM landmarks WHERE city_id = $selected_city ORDER BY name"); while ($l = mysqli_fetch_assoc($land_q)) echo "<option value='{$l['id']}'>{$l['name']}</option>"; ?>
                    </select>
                </div>
                <div class="form-group custom-name" id="custom_name_<?php echo $i; ?>" style="display:none;">
                    <label>Custom name:</label>
                    <input type="text" name="custom_name[<?php echo $i; ?>]" placeholder="Enter station name">
                </div>
                <div class="form-group"><label>Image:</label><input type="file" name="station_image[<?php echo $i; ?>]" accept="image/*"></div>
                <div class="form-group"><label>Description (e.g., '1 night', '2 hours'):</label><textarea name="station_desc[<?php echo $i; ?>]" rows="2" placeholder="e.g., Overnight stay with breakfast"></textarea></div>
                <div class="form-group"><label>Order (1-7):</label><input type="number" name="station_order[<?php echo $i; ?>]" min="1" max="7" value="<?php echo $i; ?>"></div>
                <div class="form-group"><label>Extra info (e.g., nights):</label><input type="text" name="extra_info[<?php echo $i; ?>]" placeholder="e.g., 2 nights"></div>
            </div>
            <?php endfor; ?>

            <script>
            document.querySelectorAll('.station-type').forEach(select => {
                select.addEventListener('change', function() {
                    let index = this.dataset.index;
                    let type = this.value;
                    document.querySelectorAll(`#station_${index} .existing-item`).forEach(div => div.style.display = 'none');
                    document.getElementById(`custom_name_${index}`).style.display = 'none';
                    if (type === 'hotel') document.getElementById(`existing_hotel_${index}`).style.display = 'block';
                    else if (type === 'restaurant') document.getElementById(`existing_restaurant_${index}`).style.display = 'block';
                    else if (type === 'landmark') document.getElementById(`existing_landmark_${index}`).style.display = 'block';
                    else if (type === 'custom') document.getElementById(`custom_name_${index}`).style.display = 'block';
                });
            });
            </script>
            <button type="submit" name="save_stations" class="btn"><i class="fas fa-check-double mr-2"></i>Save Tour & Stations</button>
            <button type="submit" name="back3" class="btn btn-back"><i class="fas fa-arrow-left mr-2"></i>Back</button>
        <?php endif; ?>
    </form>
</div>

<script>
    $(document).ready(function() {
        $('#countrySelect').select2({
            templateResult: function(state) {
                if (!state.id) return state.text;
                var $state = $('<span><img src="' + $(state.element).data('flag') + '" style="width:20px; margin-left:5px;" /> ' + state.text + '</span>');
                return $state;
            },
            templateSelection: function(state) {
                if (!state.id) return state.text;
                var $state = $('<span><img src="' + $(state.element).data('flag') + '" style="width:20px; margin-left:5px;" /> ' + state.text + '</span>');
                return $state;
            },
            dir: "ltr"
        });

        function addNewItem(type, inputId, cityId, checkboxGroupId) {
            var name = $('#' + inputId).val().trim();
            if (!name) { alert('Please enter a name.'); return; }
            if (!cityId || cityId == 0) { alert('Please select a city first.'); return; }

            var btn = $('#' + inputId).next('button');
            var originalText = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin"></i> Adding...').prop('disabled', true);

            $.ajax({
                url: 'add_item_ajax.php',
                type: 'POST',
                data: { type: type, name: name, city_id: cityId },
                dataType: 'json',
                success: function(response) {
                    btn.html(originalText).prop('disabled', false);
                    if (response.success) {
                        var newCheckbox = $('<label style="display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,0.6); padding:5px 12px; border-radius:30px;">' +
                            '<input type="checkbox" name="' + type + 's[]" value="' + response.id + '" checked> ' + response.name +
                            '</label>');
                        $('#' + checkboxGroupId).append(newCheckbox);
                        $('#' + inputId).val('');
                    } else {
                        alert('Error: ' + (response.message || 'Could not add item.'));
                    }
                },
                error: function(xhr, status, error) {
                    btn.html(originalText).prop('disabled', false);
                    alert('AJAX request failed.\nStatus: ' + status + '\nError: ' + error + '\n' + (xhr.responseText || ''));
                }
            });
        }

        $('#addHotelBtn').click(function() {
            addNewItem('hotel', 'newHotelName', <?php echo $selected_city; ?>, 'hotelsCheckboxGroup');
        });
        $('#addRestaurantBtn').click(function() {
            addNewItem('restaurant', 'newRestaurantName', <?php echo $selected_city; ?>, 'restaurantsCheckboxGroup');
        });
        $('#addLandmarkBtn').click(function() {
            addNewItem('landmark', 'newLandmarkName', <?php echo $selected_city; ?>, 'landmarksCheckboxGroup');
        });

        $('#newHotelName, #newRestaurantName, #newLandmarkName').keypress(function(e) {
            if (e.which == 13) {
                e.preventDefault();
                $(this).next('button').click();
            }
        });
    });
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
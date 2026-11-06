<?php
session_start();
include 'connection.php';

$tour_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($tour_id == 0) {
    die("Invalid tour ID.");
}

// Fetch tour details and remaining seats
$tour_sql = "SELECT t.*, o.name AS office_name, o.id AS office_id,
                    (SELECT COALESCE(SUM(participants), 0) FROM bookings 
                     WHERE item_id = t.id AND item_type = 'tour' AND status IN ('pending','confirmed')) AS booked_participants
             FROM tours t
             LEFT JOIN offices o ON t.office_id = o.id
             WHERE t.id = $tour_id";
$tour_res = mysqli_query($conn, $tour_sql);
if (mysqli_num_rows($tour_res) == 0) {
    die("Tour not found.");
}
$tour = mysqli_fetch_assoc($tour_res);

$max = $tour['max_participants'];
$booked = $tour['booked_participants'];
$seats_remaining = ($max === null) ? null : ($max - $booked);
$deadline = $tour['booking_deadline'];

// Check if booking is still possible
$booking_disabled = false;
$disable_reason = '';
if ($max !== null && $seats_remaining <= 0) {
    $booking_disabled = true;
    $disable_reason = "Sorry, no seats available for this tour.";
} elseif (!empty($deadline) && strtotime($deadline) < time()) {
    $booking_disabled = true;
    $disable_reason = "Booking deadline has passed (" . date('Y-m-d', strtotime($deadline)) . ").";
}

// If user is logged in, pre‑fill their data
$user_name = '';
$user_email = '';
$user_phone = '';
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_sql = "SELECT name, email, phone FROM users WHERE id = $user_id";
    $user_res = mysqli_query($conn, $user_sql);
    if ($user_row = mysqli_fetch_assoc($user_res)) {
        $user_name = $user_row['name'];
        $user_email = $user_row['email'];
        $user_phone = $user_row['phone'];
    }
}

$error = '';
$success = '';
$booking_number = '';
$user_phone_for_whatsapp = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book'])) {
    if ($booking_disabled) {
        $error = $disable_reason;
    } elseif (!isset($_SESSION['user_id'])) {
        $error = "You must log in to complete the booking.";
    } else {
        $user_id = $_SESSION['user_id'];
        $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));
        $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
        $participants = intval($_POST['participants']);
        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
        $special_requests = mysqli_real_escape_string($conn, trim($_POST['special_requests']));

        if (empty($full_name) || empty($email) || empty($phone)) {
            $error = "Please fill in your full name, email, and phone number.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif ($participants < 1) {
            $error = "Number of participants must be at least 1.";
        } elseif ($max !== null && $participants > $seats_remaining) {
            $error = "Requested participants ($participants) exceeds remaining seats ($seats_remaining).";
        } else {
            // Update user info in database
            $update_user = "UPDATE users SET name = '$full_name', email = '$email', phone = '$phone' WHERE id = $user_id";
            mysqli_query($conn, $update_user);

            // Generate unique booking number
            $booking_number = 'BKG-' . date('Ymd') . '-' . strtoupper(uniqid());
            $total_price = $participants * $tour['price'];

            // Insert booking
            $insert = "INSERT INTO bookings (booking_number, user_id, item_type, item_id, office_id, booking_date, participants, total_price, status, payment_status, payment_method, special_requests, created_at) 
                       VALUES ('$booking_number', $user_id, 'tour', $tour_id, {$tour['office_id']}, CURDATE(), $participants, $total_price, 'pending', 'unpaid', '$payment_method', '$special_requests', NOW())";
            if (mysqli_query($conn, $insert)) {
                $user_phone_for_whatsapp = $phone;
                $success = "Booking request sent successfully. Booking number: $booking_number";
            } else {
                $error = "Database error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Tour - <?php echo htmlspecialchars($tour['title']); ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing: border-box; }
        body {
            font-family: 'Cairo', 'Poppins', sans-serif;
            background: #eff1ee;
            color: #1b1c27;
            padding: 20px;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* ==== خلفية متحركة ==== */
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

        /* ==== الحاوية الرئيسية زجاجية ==== */
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(15px);
            border-radius: 40px;
            padding: 35px 35px;
            box-shadow: 0 30px 50px rgba(27, 28, 39, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            position: relative;
            z-index: 10;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        h2 {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #65403a, #9ec1d7);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin: 0;
        }

        .back-button {
            background: #65403a;
            color: white;
            padding: 10px 22px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
            box-shadow: 0 8px 20px rgba(101, 64, 58, 0.2);
        }
        .back-button:hover {
            background: #4f322c;
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(101, 64, 58, 0.3);
        }

        .tour-info {
            background: rgba(239, 241, 238, 0.6);
            backdrop-filter: blur(5px);
            padding: 25px;
            border-radius: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(158, 193, 215, 0.3);
        }

        .tour-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1b1c27;
            margin-bottom: 15px;
        }

        .tour-info p {
            margin: 8px 0;
            color: #2d3a4a;
            font-size: 1rem;
        }

        .tour-info strong {
            color: #65403a;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1b1c27;
            font-size: 0.95rem;
        }

        .required:after {
            content: " *";
            color: #ed4956;
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
        }

        input:focus, select:focus, textarea:focus {
            border-color: #65403a;
            box-shadow: 0 0 0 4px rgba(101, 64, 58, 0.1);
        }

        textarea {
            border-radius: 30px;
            resize: vertical;
        }

        .btn {
            background: #65403a;
            color: white;
            padding: 14px 25px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            width: 100%;
            font-size: 1.1rem;
            font-weight: 700;
            transition: 0.3s;
            margin-top: 15px;
            box-shadow: 0 8px 20px rgba(101, 64, 58, 0.25);
        }

        .btn:hover {
            background: #4f322c;
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(101, 64, 58, 0.3);
        }

        .message, .error {
            padding: 16px 20px;
            border-radius: 40px;
            margin-bottom: 25px;
            font-size: 1rem;
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

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #65403a;
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s;
        }

        .back-link:hover {
            color: #4f322c;
            transform: translateX(-5px);
        }

        .whatsapp-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #25D366;
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            text-decoration: none;
            margin-top: 15px;
            font-weight: 700;
            transition: 0.3s;
            box-shadow: 0 8px 20px rgba(37, 211, 102, 0.2);
        }

        .whatsapp-link i { font-size: 1.3rem; }
        .whatsapp-link:hover {
            background: #128C7E;
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(37, 211, 102, 0.3);
        }

        small {
            display: block;
            margin-top: 6px;
            color: #5a6a7a;
            font-size: 0.8rem;
        }

        .back-buttons-wrapper {
            display: flex;
            gap: 20px;
            margin-bottom: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn-back-outline {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 25px;
            background: transparent;
            color: #65403a;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            transition: 0.3s;
            border: 2px solid #9ec1d7;
        }

        .btn-back-outline i { font-size: 1rem; }
        .btn-back-outline:hover {
            background: #9ec1d7;
            color: #1b1c27;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(158, 193, 215, 0.2);
        }

        @media (max-width: 480px) {
            .container { padding: 25px 20px; }
            h2 { font-size: 1.8rem; }
            .header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
<!-- خلفية -->
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<div class="container">
    <div class="header">
        <h2><i class="fas fa-ticket-alt mr-2" style="color: #65403a;"></i>Book Your Tour</h2>
        <a href="tour_details.php?id=<?php echo $tour_id; ?>" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Tour Details
        </a>
    </div>

    <div class="tour-info">
        <div class="tour-title"><?php echo htmlspecialchars($tour['title']); ?></div>
        <p><strong>Price per person:</strong> <?php echo number_format($tour['price'], 2); ?> <?php echo $tour['currency']; ?></p>
        <p><strong>Remaining seats:</strong> <?php echo ($max === null) ? 'Unlimited' : $seats_remaining; ?></p>
        <p><strong>Booking deadline:</strong> <?php echo $tour['booking_deadline'] ?? 'Not specified'; ?></p>
    </div>

    <?php if ($success): ?>
        <div class="message">
            <i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?><br>
            <?php if (!empty($user_phone_for_whatsapp)): ?>
                <strong>We will contact you via WhatsApp at:</strong><br>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $user_phone_for_whatsapp); ?>" target="_blank" class="whatsapp-link">
                    <i class="fab fa-whatsapp"></i> <?php echo htmlspecialchars($user_phone_for_whatsapp); ?>
                </a>
                <br><br>
                <em>You will receive a confirmation message on this number.</em>
            <?php else: ?>
                <strong>We will contact you using the phone number you provided.</strong>
            <?php endif; ?>
        </div>
        <a href="tour_details.php?id=<?php echo $tour_id; ?>" class="back-link"><i class="fas fa-arrow-left mr-2"></i>Back to tour details</a>
    <?php elseif ($booking_disabled): ?>
        <div class="error"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $disable_reason; ?></div>
        <a href="tour_details.php?id=<?php echo $tour_id; ?>" class="back-link"><i class="fas fa-arrow-left mr-2"></i>Back to tour details</a>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="full_name" class="required">Full Name</label>
                <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user_name); ?>" required>
            </div>
            <div class="form-group">
                <label for="email" class="required">Email Address</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_email); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone" class="required">Phone Number (WhatsApp preferred)</label>
                <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($user_phone); ?>" required>
                <small>We will contact you on this number via WhatsApp.</small>
            </div>
            <div class="form-group">
                <label for="participants" class="required">Number of Participants</label>
                <input type="number" name="participants" id="participants" min="1" max="<?php echo ($max !== null) ? $seats_remaining : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="payment_method" class="required">Payment Method</label>
                <select name="payment_method" id="payment_method" required>
                    <option value="cash">Cash (on arrival)</option>
                    <option value="card">Credit Card</option>
                    <option value="bank">Bank Transfer</option>
                    <option value="online">Online Payment</option>
                </select>
            </div>
            <div class="form-group">
                <label for="special_requests">Special Requests (optional)</label>
                <textarea name="special_requests" id="special_requests" rows="3"></textarea>
            </div>
            <button type="submit" name="book" class="btn"><i class="fas fa-check mr-2"></i>Confirm Booking</button>
        </form>

        <div class="back-buttons-wrapper">
            <a href="../blog.php?id=<?php echo $tour_id; ?>" class="btn-back-outline">
                <i class="fas fa-star"></i> Back to Top Rated Tours
            </a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
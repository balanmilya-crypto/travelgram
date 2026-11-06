<?php
session_start();
include 'connection.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}
$user_id = $_SESSION['user_id'];

// معالجة حذف الحجز
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $booking_id = intval($_GET['delete']);
    // التأكد من أن الحجز يخص المستخدم
    $check = mysqli_query($conn, "SELECT id FROM bookings WHERE id = $booking_id AND user_id = $user_id");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "DELETE FROM bookings WHERE id = $booking_id");
    }
    header("Location: my_bookings.php");
    exit();
}

$bookings = mysqli_query($conn, "
    SELECT b.*, t.title AS tour_title, o.name AS office_name, t.currency
    FROM bookings b
    JOIN tours t ON b.item_id = t.id
    LEFT JOIN offices o ON t.office_id = o.id
    WHERE b.user_id = $user_id AND b.item_type = 'tour'
    ORDER BY b.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - TravelGram</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
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

        /* الحاوية الرئيسية زجاجية */
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(15px);
            border-radius: 40px;
            padding: 30px 35px;
            box-shadow: 0 30px 50px rgba(27, 28, 39, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            position: relative;
            z-index: 10;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 25px;
            color: #65403a;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            padding: 8px 18px;
            border-radius: 40px;
            background: rgba(101, 64, 58, 0.08);
        }
        .back-link i { margin-right: 8px; }
        .back-link:hover {
            background: #65403a;
            color: white;
            transform: translateX(-5px);
        }

        h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #1b1c27;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        h2 i { color: #65403a; }

        .booking-item {
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(5px);
            border-radius: 25px;
            padding: 20px 25px;
            margin-bottom: 15px;
            border: 1px solid rgba(158, 193, 215, 0.3);
            transition: all 0.2s ease;
        }
        .booking-item:hover {
            background: rgba(255, 255, 255, 0.8);
            border-color: #9ec1d7;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(101, 64, 58, 0.1);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .booking-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1b1c27;
        }

        .booking-number {
            font-size: 0.85rem;
            color: #5a6a7a;
            background: rgba(158, 193, 215, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
        }

        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin: 15px 0;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #2d3a4a;
        }
        .detail-item i {
            color: #65403a;
            width: 20px;
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-pending { background: rgba(255, 193, 7, 0.15); color: #856404; border: 1px solid #ffc107; }
        .status-confirmed { background: rgba(40, 167, 69, 0.1); color: #155724; border: 1px solid #28a745; }
        .status-cancelled { background: rgba(220, 53, 69, 0.1); color: #721c24; border: 1px solid #dc3545; }
        .status-completed { background: rgba(23, 162, 184, 0.1); color: #0c5460; border: 1px solid #17a2b8; }

        .booking-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(158, 193, 215, 0.3);
        }

        .booking-date {
            font-size: 0.85rem;
            color: #5a6a7a;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .delete-btn {
            background: none;
            border: 1px solid #dc3545;
            color: #dc3545;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .delete-btn:hover {
            background: #dc3545;
            color: white;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #5a6a7a;
        }
        .no-data i {
            font-size: 60px;
            color: #65403a;
            opacity: 0.5;
            margin-bottom: 20px;
        }
        .no-data p { font-size: 1.1rem; }

        @media (max-width: 600px) {
            .container { padding: 20px 18px; }
            h2 { font-size: 1.6rem; }
            .booking-header { flex-direction: column; gap: 8px; }
            .booking-details { grid-template-columns: 1fr; }
            .booking-footer { flex-direction: column; gap: 10px; align-items: flex-start; }
        }
    </style>
</head>
<body>
<!-- خلفية -->
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<div class="container">
    <a href="profile.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Profile</a>
    <h2><i class="fas fa-ticket-alt"></i> My Bookings</h2>

    <?php if (mysqli_num_rows($bookings) == 0): ?>
        <div class="no-data">
            <i class="fas fa-ticket-alt"></i>
            <p>No bookings yet.</p>
        </div>
    <?php else: ?>
        <?php while ($b = mysqli_fetch_assoc($bookings)): 
            $status_class = 'status-' . $b['status'];
            $status_labels = [
                'pending' => 'Pending',
                'confirmed' => 'Confirmed',
                'cancelled' => 'Cancelled',
                'completed' => 'Completed'
            ];
            $status_text = $status_labels[$b['status']] ?? $b['status'];
        ?>
            <div class="booking-item">
                <div class="booking-header">
                    <div class="booking-title"><?php echo htmlspecialchars($b['tour_title']); ?></div>
                    <div class="booking-number">#<?php echo htmlspecialchars($b['booking_number']); ?></div>
                </div>
                
                <div class="booking-details">
                    <div class="detail-item">
                        <i class="fas fa-users"></i>
                        <span><strong><?php echo $b['participants']; ?></strong> Participant(s)</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-tag"></i>
                        <span><strong><?php echo number_format($b['total_price'], 2); ?> <?php echo $b['currency']; ?></strong></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-credit-card"></i>
                        <span><?php echo ucfirst($b['payment_method']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-building"></i>
                        <span><?php echo htmlspecialchars($b['office_name'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <div class="booking-footer">
                    <div class="booking-date">
                        <i class="fas fa-calendar-alt"></i>
                        Booked on <?php echo date('Y-m-d', strtotime($b['created_at'])); ?>
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span class="status-badge <?php echo $status_class; ?>">
                            <i class="fas fa-circle" style="font-size: 8px; margin-right: 5px;"></i>
                            <?php echo $status_text; ?>
                        </span>
                        <a href="my_bookings.php?delete=<?php echo $b['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to permanently delete this booking?');">
                            <i class="fas fa-trash-alt"></i> Delete
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>
</body>
</html>
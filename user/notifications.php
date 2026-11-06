<?php
session_start();
include 'connection.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}
$user_id = $_SESSION['user_id'];

// حذف إشعار فردي
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notif_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM notifications WHERE id = $notif_id AND user_id = $user_id");
    header('Location: notifications.php');
    exit();
}

// حذف جميع الإشعارات
if (isset($_GET['clear_all'])) {
    mysqli_query($conn, "DELETE FROM notifications WHERE user_id = $user_id");
    header('Location: notifications.php');
    exit();
}

// تعليم الكل كمقروء
if (isset($_GET['mark_read'])) {
    mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");
    header('Location: notifications.php');
    exit();
}

$notifications = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 50");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - TravelGram</title>
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
            max-width: 850px;
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

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .back-link {
            color: #65403a;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            padding: 8px 18px;
            border-radius: 40px;
            background: rgba(101, 64, 58, 0.08);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .back-link:hover {
            background: #65403a;
            color: white;
            transform: translateX(-5px);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 40px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .btn-primary {
            background: #65403a;
            color: white;
            box-shadow: 0 4px 10px rgba(101, 64, 58, 0.2);
        }
        .btn-primary:hover {
            background: #4f322c;
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(101, 64, 58, 0.25);
        }

        .btn-outline {
            background: transparent;
            border-color: #65403a;
            color: #65403a;
        }
        .btn-outline:hover {
            background: #65403a;
            color: white;
        }

        .btn-danger {
            background: transparent;
            border-color: #dc3545;
            color: #dc3545;
        }
        .btn-danger:hover {
            background: #dc3545;
            color: white;
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

        .notification-item {
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(5px);
            border-radius: 25px;
            padding: 18px 20px;
            margin-bottom: 12px;
            border: 1px solid rgba(158, 193, 215, 0.3);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .notification-item:hover {
            background: rgba(255, 255, 255, 0.8);
            border-color: #9ec1d7;
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(101, 64, 58, 0.08);
        }
        .notification-item.unread {
            background: rgba(101, 64, 58, 0.08);
            border-left: 5px solid #65403a;
        }

        .notification-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(101, 64, 58, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #65403a;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 700;
            color: #1b1c27;
            margin-bottom: 5px;
            font-size: 1.05rem;
        }

        .notification-message {
            font-size: 0.9rem;
            color: #5a6a7a;
            margin-bottom: 5px;
        }

        .notification-date {
            font-size: 0.75rem;
            color: #7a8a9a;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .delete-notif {
            background: none;
            border: none;
            color: #dc3545;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
            flex-shrink: 0;
            opacity: 0.6;
        }
        .delete-notif:hover {
            background: #dc3545;
            color: white;
            opacity: 1;
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
            .header-actions { flex-direction: column; align-items: stretch; }
            .action-buttons { justify-content: space-between; }
            .notification-item { flex-wrap: wrap; }
            .delete-notif { margin-left: auto; }
        }
    </style>
</head>
<body>
<!-- خلفية -->
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<div class="container">
    <div class="header-actions">
        <a href="profile.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Profile</a>
        <div class="action-buttons">
            <a href="?mark_read=1" class="btn btn-primary"><i class="fas fa-check-double"></i> Mark all read</a>
            <a href="?clear_all=1" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete all notifications?');"><i class="fas fa-trash-alt"></i> Clear all</a>
        </div>
    </div>

    <h2><i class="fas fa-bell"></i> Notifications</h2>

    <?php if (mysqli_num_rows($notifications) == 0): ?>
        <div class="no-data">
            <i class="fas fa-bell-slash"></i>
            <p>No notifications yet.</p>
        </div>
    <?php else: ?>
        <?php while ($n = mysqli_fetch_assoc($notifications)): ?>
            <div class="notification-item <?php echo $n['is_read'] ? '' : 'unread'; ?>">
                <div class="notification-icon">
                    <?php if ($n['type'] == 'new_tour'): ?>
                        <i class="fas fa-bus"></i>
                    <?php elseif ($n['type'] == 'new_follower'): ?>
                        <i class="fas fa-user-plus"></i>
                    <?php elseif ($n['type'] == 'booking_status'): ?>
                        <i class="fas fa-ticket-alt"></i>
                    <?php elseif ($n['type'] == 'new_office_pending'): ?>
                        <i class="fas fa-building"></i>
                    <?php else: ?>
                        <i class="fas fa-bell"></i>
                    <?php endif; ?>
                </div>
                <div class="notification-content">
                    <div class="notification-title"><?php echo htmlspecialchars($n['title']); ?></div>
                    <div class="notification-message"><?php echo htmlspecialchars($n['message']); ?></div>
                    <div class="notification-date">
                        <i class="fas fa-clock"></i>
                        <?php echo date('Y-m-d H:i', strtotime($n['created_at'])); ?>
                    </div>
                </div>
                <a href="notifications.php?delete=<?php echo $n['id']; ?>" class="delete-notif" onclick="return confirm('Delete this notification?');" title="Delete">
                    <i class="fas fa-trash-alt"></i>
                </a>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>
</body>
</html>
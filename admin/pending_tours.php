<?php
session_start();
include '../admin/connection.php';

// تحقق من صلاحية السوبر أدمن
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

// معالجة قبول/رفض الرحلة مع إرسال إشعارات
if (isset($_GET['action']) && isset($_GET['id'])) {
    $tour_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action == 'approve') {
        // تحديث الحالة إلى approved
        mysqli_query($conn, "UPDATE tours SET status = 'approved' WHERE id = $tour_id");
        $msg = "Tour approved successfully.";
        
        // جلب بيانات الرحلة لإرسال الإشعار
        $tour_query = mysqli_query($conn, "SELECT office_id, title FROM tours WHERE id = $tour_id");
        if ($tour_row = mysqli_fetch_assoc($tour_query)) {
            $office_id = $tour_row['office_id'];
            $tour_title = mysqli_real_escape_string($conn, $tour_row['title']);
            
            // جلب مدير المكتب
            $admin_query = mysqli_query($conn, "SELECT id FROM users WHERE office_id = $office_id AND role = 'office_admin' LIMIT 1");
            if ($admin_row = mysqli_fetch_assoc($admin_query)) {
                $user_id = $admin_row['id'];
                $notif_title = "✅ Tour Approved";
                $notif_message = "Your tour '{$tour_title}' has been approved and is now visible to travelers.";
                mysqli_query($conn, "INSERT INTO notifications (user_id, type, title, message, is_read, created_at) 
                                     VALUES ($user_id, 'tour_approved', '$notif_title', '$notif_message', 0, NOW())");
            }
        }
    } elseif ($action == 'reject') {
        // جلب بيانات الرحلة قبل الحذف لإرسال الإشعار
        $tour_query = mysqli_query($conn, "SELECT office_id, title FROM tours WHERE id = $tour_id");
        if ($tour_row = mysqli_fetch_assoc($tour_query)) {
            $office_id = $tour_row['office_id'];
            $tour_title = mysqli_real_escape_string($conn, $tour_row['title']);
            
            // جلب مدير المكتب
            $admin_query = mysqli_query($conn, "SELECT id FROM users WHERE office_id = $office_id AND role = 'office_admin' LIMIT 1");
            if ($admin_row = mysqli_fetch_assoc($admin_query)) {
                $user_id = $admin_row['id'];
                $notif_title = "❌ Tour Rejected";
                $notif_message = "Your tour '{$tour_title}' has been rejected and removed by the administrator.";
                mysqli_query($conn, "INSERT INTO notifications (user_id, type, title, message, is_read, created_at) 
                                     VALUES ($user_id, 'tour_rejected', '$notif_title', '$notif_message', 0, NOW())");
            }
        }
        // حذف الرحلة
        mysqli_query($conn, "DELETE FROM tours WHERE id = $tour_id");
        $msg = "Tour rejected and removed.";
    }
    
    header("Location: pending_tours.php?msg=" . urlencode($msg));
    exit();
}

// جلب الرحلات المعلقة مع تفاصيلها
$query = "
    SELECT t.*, o.name AS office_name, c.name AS city_name, co.name AS country_name
    FROM tours t
    JOIN offices o ON t.office_id = o.id
    JOIN tour_city tc ON t.id = tc.tour_id
    JOIN cities c ON tc.city_id = c.id
    JOIN countries co ON c.country_id = co.id
    WHERE t.status = 'pending'
    ORDER BY t.created_at DESC
";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Tours - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Cairo','Poppins',sans-serif;
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
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255,255,255,0.75);
            backdrop-filter: blur(15px);
            border-radius: 40px;
            padding: 30px 35px;
            box-shadow: 0 30px 50px rgba(27,28,39,0.1);
            border: 1px solid rgba(255,255,255,0.5);
            position: relative;
            z-index: 10;
        }
        h2 { font-size: 2.2rem; font-weight: 700; color: #1b1c27; margin-bottom: 25px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #65403a; text-decoration: none; font-weight: 600; padding: 8px 18px; border-radius: 40px; background: rgba(101,64,58,0.08); }
        .back-link i { margin-right: 8px; }
        .back-link:hover { background: #65403a; color: white; }
        .msg { background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 40px; margin-bottom: 20px; border-left: 5px solid #28a745; }
        
        .tours-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        .tour-card {
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(5px);
            border-radius: 25px;
            overflow: hidden;
            border: 1px solid rgba(158,193,215,0.3);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }
        .tour-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 35px rgba(101,64,58,0.15);
            border-color: #9ec1d7;
            background: rgba(255,255,255,0.85);
        }
        .tour-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-bottom: 3px solid #9ec1d7;
        }
        .tour-content {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .tour-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1b1c27;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        .office-name {
            color: #65403a;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .location {
            color: #5a6a7a;
            font-size: 0.9rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .price {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1b1c27;
            margin: 10px 0;
        }
        .meta {
            display: flex;
            justify-content: space-between;
            color: #5a6a7a;
            font-size: 0.85rem;
            margin-bottom: 15px;
        }
        .actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }
        .btn {
            flex: 1;
            padding: 10px 0;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: 0.2s;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .btn-approve {
            background: #28a745;
            color: white;
        }
        .btn-approve:hover { background: #218838; }
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        .btn-reject:hover { background: #c82333; }
        
        .no-tours {
            text-align: center;
            padding: 50px;
            color: #5a6a7a;
            font-size: 1.2rem;
        }
        @media (max-width:768px) {
            .container { padding: 20px; }
            .tours-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<div class="container">
    <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <h2><i class="fas fa-clock" style="color:#65403a;"></i> Pending Tours Approval</h2>
    <?php if (isset($_GET['msg'])): ?>
        <div class="msg"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>
    
    <?php if (mysqli_num_rows($result) == 0): ?>
        <div class="no-tours">
            <i class="fas fa-check-circle" style="font-size: 3rem; opacity: 0.5; margin-bottom: 15px;"></i>
            <p>No pending tours waiting for approval.</p>
        </div>
    <?php else: ?>
        <div class="tours-grid">
            <?php while ($tour = mysqli_fetch_assoc($result)): ?>
                <div class="tour-card">
                    <?php if (!empty($tour['main_image'])): ?>
                        <img src="../<?php echo htmlspecialchars($tour['main_image']); ?>" class="tour-image" alt="<?php echo htmlspecialchars($tour['title']); ?>">
                    <?php else: ?>
                        <div class="tour-image" style="background: #ddd; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-image" style="font-size: 3rem; color: #888;"></i>
                        </div>
                    <?php endif; ?>
                    <div class="tour-content">
                        <div class="tour-title"><?php echo htmlspecialchars($tour['title']); ?></div>
                        <div class="office-name"><i class="fas fa-building"></i> <?php echo htmlspecialchars($tour['office_name']); ?></div>
                        <div class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($tour['city_name'] . ', ' . $tour['country_name']); ?></div>
                        <div class="price"><?php echo number_format($tour['price'],2); ?> <?php echo $tour['currency']; ?></div>
                        <div class="meta">
                            <span><i class="far fa-calendar-alt"></i> <?php echo date('Y-m-d', strtotime($tour['created_at'])); ?></span>
                            <span><i class="fas fa-users"></i> <?php echo $tour['max_participants'] ?? '∞'; ?></span>
                        </div>
                        <div class="actions">
                            <a href="pending_tours.php?action=approve&id=<?php echo $tour['id']; ?>" class="btn btn-approve" onclick="return confirm('Approve this tour?')"><i class="fas fa-check"></i> Approve</a>
                            <a href="pending_tours.php?action=reject&id=<?php echo $tour['id']; ?>" class="btn btn-reject" onclick="return confirm('Reject and delete this tour?')"><i class="fas fa-times"></i> Reject</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
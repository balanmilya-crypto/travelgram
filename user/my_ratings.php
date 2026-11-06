<?php
session_start();
include 'connection.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}
$user_id = $_SESSION['user_id'];

// معالجة حذف التقييم
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $rating_id = intval($_GET['delete']);
    // تأكد من أن التقييم يخص المستخدم الحالي
    $check = mysqli_query($conn, "SELECT id FROM ratings WHERE id = $rating_id AND user_id = $user_id");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "DELETE FROM ratings WHERE id = $rating_id");
    }
    header("Location: my_ratings.php");
    exit();
}

$ratings = mysqli_query($conn, "
    SELECT r.*, t.title AS tour_title
    FROM ratings r
    JOIN tours t ON r.rateable_id = t.id
    WHERE r.rateable_type = 'tour' AND r.user_id = $user_id
    ORDER BY r.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Ratings - TravelGram</title>
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

        .rating-item {
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(5px);
            border-radius: 25px;
            padding: 20px 25px;
            margin-bottom: 15px;
            border: 1px solid rgba(158, 193, 215, 0.3);
            transition: all 0.2s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .rating-item:hover {
            background: rgba(255, 255, 255, 0.8);
            border-color: #9ec1d7;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(101, 64, 58, 0.1);
        }

        .rating-info {
            flex: 1;
        }

        .tour-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1b1c27;
            margin-bottom: 10px;
        }

        .stars {
            color: #f0ad4e;
            font-size: 1.2rem;
            margin-bottom: 8px;
        }
        .stars i { margin-right: 3px; }

        .date {
            font-size: 0.85rem;
            color: #5a6a7a;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .date i { color: #65403a; }

        .delete-btn {
            background: none;
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #dc3545;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            margin-left: 15px;
        }
        .delete-btn:hover {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
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
            .tour-title { font-size: 1.1rem; }
            .rating-item { flex-direction: column; align-items: flex-start; }
            .delete-btn { margin-left: 0; margin-top: 10px; align-self: flex-end; }
        }
    </style>
</head>
<body>
<!-- خلفية -->
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<div class="container">
    <a href="profile.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Profile</a>
    <h2><i class="fas fa-star"></i> My Ratings</h2>

    <?php if (mysqli_num_rows($ratings) == 0): ?>
        <div class="no-data">
            <i class="fas fa-star-half-alt"></i>
            <p>You haven't rated any tour yet.</p>
        </div>
    <?php else: ?>
        <?php while ($r = mysqli_fetch_assoc($ratings)): ?>
            <div class="rating-item">
                <div class="rating-info">
                    <div class="tour-title"><?php echo htmlspecialchars($r['tour_title']); ?></div>
                    <div class="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star<?php echo $i <= $r['rating'] ? '' : '-o'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="date">
                        <i class="fas fa-calendar-alt"></i>
                        <?php echo date('Y-m-d', strtotime($r['created_at'])); ?>
                    </div>
                </div>
                <a href="my_ratings.php?delete=<?php echo $r['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this rating?');">
                    <i class="fas fa-trash-alt"></i>
                </a>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>
</body>
</html>
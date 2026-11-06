<?php
session_start();
include 'connection.php';

$office_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$country_id = isset($_GET['country_id']) ? intval($_GET['country_id']) : 0;

if ($office_id == 0) {
    die("Invalid office ID.");
}

// Fetch office details
$office_sql = "SELECT id, name, logo, description FROM offices WHERE id = $office_id";
$office_res = mysqli_query($conn, $office_sql);
if (mysqli_num_rows($office_res) == 0) {
    die("Office not found.");
}
$office = mysqli_fetch_assoc($office_res);

// عدد المتابعين
$followers_query = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM follows WHERE followable_type = 'office' AND followable_id = $office_id");
$followers_count = mysqli_fetch_assoc($followers_query)['cnt'];

// هل المستخدم الحالي يتابع هذا المكتب؟
$is_following = false;
$current_user_id = $_SESSION['user_id'] ?? 0;
if ($current_user_id > 0) {
    $check = mysqli_query($conn, "SELECT id FROM follows WHERE follower_id = $current_user_id AND followable_type = 'office' AND followable_id = $office_id");
    $is_following = mysqli_num_rows($check) > 0;
}

// Build tours query with remaining seats calculation
$tours_sql = "
    SELECT 
        t.id, t.title, t.price, t.currency, t.main_image, 
        t.created_at, t.booking_deadline, t.max_participants,
        COALESCE(SUM(b.participants), 0) AS booked_participants
    FROM tours t
    LEFT JOIN bookings b ON b.item_id = t.id 
        AND b.item_type = 'tour' 
        AND b.status IN ('pending', 'confirmed')
    WHERE t.office_id = $office_id
      AND t.status = 'approved'
";

if ($country_id > 0) {
    $tours_sql .= " AND EXISTS (
        SELECT 1 FROM tour_city tc
        JOIN cities ci ON tc.city_id = ci.id
        WHERE tc.tour_id = t.id AND ci.country_id = $country_id
    )";
}

$tours_sql .= " GROUP BY t.id ORDER BY t.created_at DESC";
$tours_result = mysqli_query($conn, $tours_sql);

$active_tours = [];
$expired_tours = [];

while ($tour = mysqli_fetch_assoc($tours_result)) {
    $max = $tour['max_participants'];
    $booked = $tour['booked_participants'];
    $seats_remaining = ($max === null) ? null : ($max - $booked);
    $deadline = $tour['booking_deadline'];
    $is_deadline_passed = ($deadline !== null && strtotime($deadline) < time());

    if (($seats_remaining === null || $seats_remaining > 0) && !$is_deadline_passed) {
        $tour['seats_remaining'] = $seats_remaining;
        $active_tours[] = $tour;
    } else {
        $tour['seats_remaining'] = $seats_remaining;
        $expired_tours[] = $tour;
    }
}

$country_name = '';
if ($country_id > 0) {
    $country_q = mysqli_query($conn, "SELECT name FROM countries WHERE id = $country_id");
    if ($country_row = mysqli_fetch_assoc($country_q)) {
        $country_name = $country_row['name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($office['name']); ?> - TravelGram</title>
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
            width: 100%;
            height: 100%;
            top: 0; left: 0;
            z-index: 0;
            pointer-events: none;
            background: url('data:image/svg+xml;utf8,<svg width="100%" height="100%" viewBox="0 0 1200 800" xmlns="http://www.w3.org/2000/svg"><path d="M0,400 Q300,200 600,400 T1200,300 L1200,800 L0,800 Z" fill="rgba(158,193,215,0.05)"/><path d="M0,600 Q400,450 800,600 T1200,500 L1200,800 L0,800 Z" fill="rgba(101,64,58,0.03)"/></svg>');
            background-size: cover;
            opacity: 0.5;
        }

        @keyframes float {
            0% { transform: translateY(0px) scale(1); }
            100% { transform: translateY(-30px) scale(1.05); }
        }

        /* ==== الحاوية الرئيسية ==== */
        .container {
            max-width: 1000px;
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

        /* ==== الروابط ==== */
        .back-links { margin-bottom: 25px; }
        .back-link {
            display: inline-block;
            color: #65403a;
            text-decoration: none;
            margin-right: 20px;
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

        /* ==== رأس المكتب ==== */
        .office-header {
            text-align: center;
            margin-bottom: 35px;
        }
        .office-logo {
            max-width: 160px;
            max-height: 110px;
            object-fit: contain;
            margin-bottom: 15px;
            border-radius: 30px;
            background: #f8f8f8;
            padding: 10px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            border: 2px solid #9ec1d7;
        }
        .office-name {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #65403a, #9ec1d7);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 10px;
        }
        .office-description {
            color: #1b1c27;
            line-height: 1.7;
            margin-bottom: 25px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            opacity: 0.9;
        }

        /* ==== قسم المتابعة ==== */
        .follow-section {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-top: 15px;
        }
        .follow-btn {
            background: #65403a;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 10px 30px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 8px 20px rgba(101, 64, 58, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .follow-btn.following {
            background: transparent;
            color: #65403a;
            border: 2px solid #65403a;
            box-shadow: none;
        }
        .follow-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(101, 64, 58, 0.3);
        }
        .followers-count {
            font-weight: 600;
            color: #65403a;
            background: rgba(101, 64, 58, 0.08);
            padding: 8px 20px;
            border-radius: 40px;
            font-size: 1rem;
        }

        /* ==== العناوين ==== */
        h2 {
            color: #1b1c27;
            margin: 40px 0 25px;
            font-size: 1.8rem;
            border-left: 5px solid #65403a;
            padding-left: 20px;
        }

        /* ==== بطاقات الرحلات ==== */
        .tours-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .tour-card {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(5px);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: row;
            align-items: center;
            padding: 20px;
            border: 1px solid rgba(158, 193, 215, 0.3);
        }
        .tour-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 40px rgba(101, 64, 58, 0.15);
            border-color: #9ec1d7;
            background: rgba(255, 255, 255, 0.8);
        }
        .tour-info {
            flex: 2;
            padding-right: 25px;
        }
        .tour-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1b1c27;
            margin-bottom: 10px;
        }
        .tour-price {
            font-size: 1.4rem;
            color: #65403a;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .tour-seats {
            font-size: 1rem;
            color: #27ae60;
            margin-top: 10px;
        }
        .tour-deadline {
            font-size: 0.9rem;
            color: #5a6a7a;
            margin-top: 8px;
        }
        .tour-image {
            width: 220px;
            height: 150px;
            object-fit: cover;
            border-radius: 25px;
            flex-shrink: 0;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            border: 2px solid white;
        }
        .no-tours {
            text-align: center;
            color: #5a6a7a;
            margin: 40px 0;
            font-size: 1.2rem;
        }
        .expired-badge {
            background: #e0e0e0;
            color: #666;
            font-size: 0.8rem;
            padding: 5px 12px;
            border-radius: 30px;
            display: inline-block;
            margin-top: 10px;
        }
        .expired .tour-seats { color: #c0392b; }

        @media (max-width: 768px) {
            .container { padding: 20px 18px; }
            .tour-card { flex-direction: column; text-align: center; padding: 15px; }
            .tour-info { padding-right: 0; margin-top: 15px; }
            .tour-image { width: 100%; height: 200px; }
            h2 { font-size: 1.5rem; }
            .office-name { font-size: 2rem; }
        }
    </style>
</head>
<body>
<!-- خلفية -->
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<div class="container">
    <div class="back-links">
        <?php if ($country_id > 0): ?>
            <a href="country.php?id=<?php echo $country_id; ?>" class="back-link"><i class="fas fa-arrow-left"></i> Back to Offices</a>
        <?php endif; ?>
        <a href="../index.php" class="back-link"><i class="fas fa-home"></i> Home</a>
    </div>

    <div class="office-header">
        <?php if (!empty($office['logo'])): ?>
            <img src="../<?php echo htmlspecialchars($office['logo']); ?>" class="office-logo" alt="<?php echo htmlspecialchars($office['name']); ?>">
        <?php else: ?>
            <i class="fas fa-building" style="font-size: 80px; color: #65403a;"></i>
        <?php endif; ?>
        <h1 class="office-name"><?php echo htmlspecialchars($office['name']); ?></h1>
        <?php if (!empty($office['description'])): ?>
            <div class="office-description"><?php echo nl2br(htmlspecialchars($office['description'])); ?></div>
        <?php endif; ?>

        <!-- قسم المتابعة -->
        <div class="follow-section">
            <button class="follow-btn <?php echo $is_following ? 'following' : ''; ?>" id="followButton" data-office-id="<?php echo $office_id; ?>">
                <i class="fas <?php echo $is_following ? 'fa-user-check' : 'fa-user-plus'; ?>"></i>
                <span><?php echo $is_following ? 'Following' : 'Follow'; ?></span>
            </button>
            <div class="followers-count">
                <i class="fas fa-users"></i> <span id="followersCount"><?php echo $followers_count; ?></span> Followers
            </div>
        </div>
    </div>

    <h2>Available Tours <?php if ($country_name): ?>in <?php echo htmlspecialchars($country_name); ?><?php endif; ?></h2>
    <?php if (empty($active_tours)): ?>
        <div class="no-tours">
            <?php if ($country_name): ?>
                No available tours for this office in <?php echo htmlspecialchars($country_name); ?> at the moment.
            <?php else: ?>
                No available tours for this office at the moment.
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="tours-grid">
            <?php foreach ($active_tours as $tour): ?>
                <div class="tour-card" onclick="window.location.href='tour_details.php?id=<?php echo $tour['id']; ?>'">
                    <div class="tour-info">
                        <div class="tour-title"><?php echo htmlspecialchars($tour['title']); ?></div>
                        <div class="tour-price"><?php echo number_format($tour['price'], 2); ?> <?php echo $tour['currency']; ?></div>
                        <?php if ($tour['seats_remaining'] !== null): ?>
                            <div class="tour-seats">
                                <i class="fas fa-chair"></i> Remaining seats: <?php echo $tour['seats_remaining']; ?> / <?php echo $tour['max_participants']; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($tour['booking_deadline'])): ?>
                            <div class="tour-deadline"><i class="fas fa-calendar-alt"></i> Booking deadline: <?php echo date('Y-m-d', strtotime($tour['booking_deadline'])); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($tour['main_image'])): ?>
                        <img src="../<?php echo $tour['main_image']; ?>" class="tour-image" alt="Tour image">
                    <?php else: ?>
                        <div class="tour-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-image" style="font-size: 32px; color: #ccc;"></i>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($expired_tours)): ?>
        <h2 style="margin-top: 40px;">Expired Tours</h2>
        <div class="tours-grid">
            <?php foreach ($expired_tours as $tour): ?>
                <div class="tour-card expired" onclick="window.location.href='tour_details.php?id=<?php echo $tour['id']; ?>'">
                    <div class="tour-info">
                        <div class="tour-title"><?php echo htmlspecialchars($tour['title']); ?></div>
                        <div class="tour-price"><?php echo number_format($tour['price'], 2); ?> <?php echo $tour['currency']; ?></div>
                        <?php if ($tour['seats_remaining'] !== null && $tour['seats_remaining'] <= 0): ?>
                            <div class="tour-seats"><i class="fas fa-ban"></i> Fully booked</div>
                        <?php else: ?>
                            <div class="tour-deadline"><i class="fas fa-calendar-times"></i> Booking deadline passed</div>
                        <?php endif; ?>
                        <span class="expired-badge">Expired</span>
                    </div>
                    <?php if (!empty($tour['main_image'])): ?>
                        <img src="../<?php echo $tour['main_image']; ?>" class="tour-image" alt="Tour image">
                    <?php else: ?>
                        <div class="tour-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-image" style="font-size: 32px; color: #ccc;"></i>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#followButton').on('click', function(e) {
        e.stopPropagation();
        const btn = $(this);
        const officeId = btn.data('office-id');
        const isFollowing = btn.hasClass('following');
        const action = isFollowing ? 'unfollow' : 'follow';
        
        $.ajax({
            url: 'follow_ajax.php',
            type: 'GET',
            data: {
                action: action,
                type: 'office',
                id: officeId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.following) {
                        btn.addClass('following');
                        btn.html('<i class="fas fa-user-check"></i> <span>Following</span>');
                    } else {
                        btn.removeClass('following');
                        btn.html('<i class="fas fa-user-plus"></i> <span>Follow</span>');
                    }
                    $('#followersCount').text(response.followers_count);
                } else {
                    alert('Failed to update follow status.');
                }
            },
            error: function() {
                alert('An error occurred.');
            }
        });
    });
});
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
<?php
include 'admin/connection.php';
include 'header.php';

// استعلام لجلب أفضل الرحلات تقييماً (المعتمدة فقط)
$top_tours_sql = "
    SELECT t.id, t.title, t.main_image, t.price, t.currency, 
           o.name AS office_name, o.id AS office_id,
           AVG(r.rating) AS avg_rating, COUNT(r.id) AS rating_count
    FROM tours t
    LEFT JOIN offices o ON t.office_id = o.id
    LEFT JOIN ratings r ON r.rateable_type = 'tour' AND r.rateable_id = t.id
    WHERE t.status = 'approved'
    GROUP BY t.id
    HAVING rating_count > 0
    ORDER BY avg_rating DESC
    LIMIT 6
";
$top_tours_result = mysqli_query($conn, $top_tours_sql);

// في حال عدم وجود عمود status، استخدم الاستعلام البديل
if (!$top_tours_result) {
    $top_tours_sql = "
        SELECT t.id, t.title, t.main_image, t.price, t.currency, 
               o.name AS office_name, o.id AS office_id,
               AVG(r.rating) AS avg_rating, COUNT(r.id) AS rating_count
        FROM tours t
        LEFT JOIN offices o ON t.office_id = o.id
        LEFT JOIN ratings r ON r.rateable_type = 'tour' AND r.rateable_id = t.id
        GROUP BY t.id
        HAVING rating_count > 0
        ORDER BY avg_rating DESC
        LIMIT 6
    ";
    $top_tours_result = mysqli_query($conn, $top_tours_sql);
}

// التحقق من صلاحية المستخدم (لإظهار مسار الصورة)
$show_image_path = false;
if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] == 'super_admin' || $_SESSION['user_role'] == 'office_admin')) {
    $show_image_path = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Top Rated Tours - TravelGram</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap -->
    <link href="css/style.css" rel="stylesheet">
    
    <style>
        /* ==================== نظام الألوان ==================== */
        body {
            background: #eff1ee;
            font-family: 'Cairo', 'Poppins', sans-serif;
            color: #1b1c27;
            margin: 0;
            overflow-x: hidden;
            position: relative;
        }
        /* خلفية متحركة (مثل index.php) */
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

        h1, h2, h3, h4, h5, h6 { font-weight: 700; }
        .text-primary { color: #65403a !important; }
        .bg-primary { background: #65403a !important; }
        .btn-primary {
            background: #65403a;
            border: none;
            box-shadow: 0 8px 20px rgba(101,64,58,0.25);
            transition: 0.3s;
            border-radius: 50px;
            padding: 10px 24px;
            font-weight: 600;
            color: #eff1ee;
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(101,64,58,0.4);
            background: #4f322c;
        }

        /* ==================== قسم الهيدر ==================== */
        .page-header {
            position: relative;
            width: 100%;
            min-height: 400px;
            background-size: cover;
            background-position: center;
            overflow: hidden;
            margin-bottom: 0;
        }
        .page-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(27,28,39,0.45);
            backdrop-filter: blur(3px);
            z-index: 1;
        }
        .page-header .container {
            position: relative;
            z-index: 2;
        }
        .page-header h3 {
            font-size: 3.2rem;
            font-weight: 800;
            text-shadow: 0 8px 25px rgba(0,0,0,0.5);
            letter-spacing: 2px;
        }
        .page-header a, .page-header p {
            text-shadow: 0 2px 10px rgba(0,0,0,0.4);
            font-size: 1.1rem;
        }

        /* ==================== بطاقات الرحلات (تصميم زجاجي) ==================== */
        .tour-card {
            border-radius: 30px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 35px rgba(27,28,39,0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            height: 100%;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(158,193,215,0.3);
        }
        .tour-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 35px 50px rgba(101, 64, 58, 0.2);
            border-color: #9ec1d7;
            background: rgba(255, 255, 255, 0.85);
        }
        .tour-card-img {
            position: relative;
            overflow: hidden;
            height: 230px;
        }
        .tour-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }
        .tour-card:hover .tour-card-img img {
            transform: scale(1.1);
        }
        .review-badge {
            position: absolute;
            bottom: 15px;
            left: 15px;
            background: #65403a;
            color: white;
            padding: 8px 16px;
            border-radius: 40px;
            font-weight: bold;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            z-index: 2;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .image-path-badge {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(27,28,39,0.7);
            backdrop-filter: blur(5px);
            color: #9ec1d7;
            font-size: 10px;
            padding: 4px 10px;
            border-radius: 30px;
            z-index: 3;
            border: 1px solid rgba(255,255,255,0.15);
        }
        .tour-card-body {
            padding: 1.8rem 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .office-link {
            color: #65403a;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            text-decoration: none;
            letter-spacing: 0.5px;
            transition: 0.2s;
        }
        .office-link:hover {
            color: #9ec1d7;
        }
        .rating-stars {
            color: #ffb347;
            font-size: 0.9rem;
        }
        .tour-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 12px 0;
            color: #1b1c27;
            text-decoration: none;
            line-height: 1.4;
            transition: 0.2s;
        }
        .tour-title:hover {
            color: #65403a;
        }
        .price-tag {
            font-weight: 800;
            color: #65403a;
            font-size: 1.3rem;
        }
        .book-btn {
            background: #65403a;
            color: white;
            border-radius: 40px;
            padding: 10px 24px;
            font-weight: 600;
            transition: 0.3s;
            border: none;
            box-shadow: 0 5px 15px rgba(101,64,58,0.2);
        }
        .book-btn:hover {
            background: #9ec1d7;
            color: #1b1c27;
            transform: scale(1.03);
            box-shadow: 0 8px 20px rgba(158,193,215,0.3);
        }

        /* تحسينات الجوال */
        @media (max-width: 768px) {
            .page-header {
                min-height: 300px;
            }
            .page-header h3 {
                font-size: 2.4rem;
            }
        }
    </style>
</head>
<body>
<!-- خلفية متحركة -->
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<!-- قسم الهيدر -->
<div class="container-fluid page-header" style="background-image: url('img/img/blog.jpg');">
    <div class="container">
        <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 400px">
            <h3 class="display-4 text-white text-uppercase">Top Rated Tours</h3>
            <div class="d-inline-flex text-white">
                <p class="m-0 text-uppercase"><a class="text-white" href="index.php" style="text-decoration:none; font-weight:600;">Home</a></p>
                <i class="fa fa-angle-double-right pt-1 px-3"></i>
                <p class="m-0 text-uppercase">Top Rated</p>
            </div>
        </div>
    </div>
</div>

<!-- قسم الرحلات الأعلى تقييماً -->
<div class="container-fluid py-5">
    <div class="container pt-5 pb-3">
        <div class="text-center mb-5">
            <h6 class="text-primary text-uppercase" style="letter-spacing: 6px; font-weight: 700;">Discover</h6>
            <h1 style="color: #1b1c27; font-weight: 800;">Highest Rated Tours by Travelers</h1>
        </div>
        <div class="row">
            <?php if (mysqli_num_rows($top_tours_result) > 0): ?>
                <?php while ($tour = mysqli_fetch_assoc($top_tours_result)): 
                    $avg_rating = round($tour['avg_rating'], 1);
                    $full_stars = floor($avg_rating);
                    $half_star = ($avg_rating - $full_stars) >= 0.5;
                    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                    $image_path = !empty($tour['main_image']) ?  $tour['main_image'] : "img/default-tour.jpg";
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="tour-card">
                        <div class="tour-card-img">
                            <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($tour['title']); ?>" onerror="this.src='img/default-tour.jpg'">
                            <div class="review-badge">
                                <?php echo $tour['rating_count']; ?> <i class="fas fa-comment ml-1"></i>
                            </div>
                            <?php if ($show_image_path): ?>
                                <div class="image-path-badge">
                                    <?php echo htmlspecialchars($tour['main_image']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="tour-card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="user/office_details.php?id=<?php echo $tour['office_id']; ?>" class="office-link">
                                    <i class="fas fa-building mr-1"></i><?php echo htmlspecialchars($tour['office_name']); ?>
                                </a>
                                <span class="rating-stars">
                                    <?php for ($i = 1; $i <= $full_stars; $i++) echo '<i class="fas fa-star"></i>'; ?>
                                    <?php if ($half_star) echo '<i class="fas fa-star-half-alt"></i>'; ?>
                                    <?php for ($i = 1; $i <= $empty_stars; $i++) echo '<i class="far fa-star"></i>'; ?>
                                    <span class="ml-1 text-dark font-weight-bold"><?php echo $avg_rating; ?></span>
                                </span>
                            </div>
                            <a href="user/tour_details.php?id=<?php echo $tour['id']; ?>" class="tour-title">
                                <?php echo htmlspecialchars($tour['title']); ?>
                            </a>
                            <div class="mt-auto pt-3 d-flex justify-content-between align-items-center">
                                <span class="price-tag">
                                    <?php echo number_format($tour['price'], 2); ?> <?php echo $tour['currency']; ?>
                                </span>
                                <a href="user/book_tour.php?id=<?php echo $tour['id']; ?>" class="book-btn btn btn-sm">
                                    Book Now <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-star fa-3x text-muted mb-3" style="opacity:0.5;"></i>
                    <h4 style="color:#1b1c27;">No rated tours available yet</h4>
                    <p class="text-muted">Be the first to rate a tour!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>
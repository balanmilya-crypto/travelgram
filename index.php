<?php
session_start();

include 'admin/connection.php';

$page_title = 'الرئيسية - منصة السياحة';
include 'header.php';

$sql = "SELECT DISTINCT c.id, c.name, c.flag, c.images
        FROM countries c
        INNER JOIN cities ci ON c.id = ci.country_id
        INNER JOIN tour_city tc ON ci.id = tc.city_id
        INNER JOIN tours t ON tc.tour_id = t.id
        WHERE t.status = 'approved'
        ORDER BY c.name";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $page_title; ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="img/favicon.ico" rel="icon">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap Stylesheet -->
    <link href="css/style.css" rel="stylesheet">

    <style>
        /* ==================== نظام الألوان ==================== */
        body {
            background: #eff1ee;
            font-family: 'Cairo', 'Poppins', sans-serif;
            color: #1b1c27;
            margin: 0;
            overflow-x: hidden;
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
            padding: 12px 28px;
            font-weight: 600;
            color: #eff1ee;
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(101,64,58,0.4);
            background: #4f322c;
        }

        /* ==================== Hero Section ==================== */
        .hero-section {
            position: relative;
            width: 100%;
            height: 100vh;
            min-height: 600px;
            overflow: hidden;
        }
        .hero-background-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1;
        }
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(27,28,39,0.3);
            z-index: 2;
        }
        .hero-content {
            position: relative;
            z-index: 3;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            text-align: center;
            color: #eff1ee;
            padding: 2rem 1rem;
            padding-top: 12vh;
            margin: 0;
            width: 100%;
            max-width: 100%;
            height: auto;
            background: transparent !important;
            backdrop-filter: none !important;
            border: none !important;
            box-shadow: none !important;
        }
        .hero-content h4 {
            font-size: 1.5rem;
            letter-spacing: 8px;
            text-shadow: 0 4px 15px rgba(0,0,0,0.6);
            margin-bottom: 20px;
        }
        .hero-content h1 {
            font-size: 4.5rem;
            font-weight: 800;
            text-shadow: 0 8px 25px rgba(0,0,0,0.6);
            margin-bottom: 20px;
        }
        .hero-content h1 span {
            color: #9ec1d7;
            text-shadow: 0 8px 25px rgba(0,0,0,0.6);
        }
        .hero-content .lead {
            font-size: 1.3rem;
            text-shadow: 0 2px 15px rgba(0,0,0,0.6);
            margin-bottom: 30px;
        }

        /* ==================== شريط الصور السفلي ==================== */
        .mini-gallery {
            position: absolute;
            bottom: 30px;
            left: 0;
            right: 0;
            z-index: 4;
            display: flex;
            justify-content: center;
        }
        .gallery-carousel {
            max-width: 850px;
            width: 90%;
            padding: 18px 30px;
            background: rgba(27,28,39,0.35);
            backdrop-filter: blur(12px);
            border-radius: 80px;
            border: 1px solid rgba(158,193,215,0.5);
        }
        .gallery-item img {
            height: 120px !important;
            object-fit: cover;
            border-radius: 30px;
            border: 2px solid #eff1ee;
            transition: all 0.3s;
            cursor: pointer;
        }
        .gallery-item img:hover {
            transform: scale(1.05);
            border-color: #9ec1d7;
        }
        .gallery-control {
            background: rgba(239,241,238,0.5);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1b1c27;
            transition: 0.3s;
            font-size: 1.2rem;
        }
        .gallery-control:hover {
            background: #65403a;
            color: #eff1ee;
        }

        /* ==================== المربعات (Features & Services) - تأثيرات زرقاء ==================== */
        .feature-box {
            background: rgba(239,241,238,0.65);
            backdrop-filter: blur(8px);
            border-radius: 30px;
            padding: 25px 20px;
            border: 1px solid rgba(158,193,215,0.4);
            transition: all 0.3s;
            height: 100%;
            min-height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .feature-box:hover {
            transform: translateY(-8px);
            background: #eff1ee;
            box-shadow: 0 25px 40px rgba(79, 172, 254, 0.2);
            border-color: #4facfe;
        }
        .service-item {
            border-radius: 40px;
            background: rgba(239,241,238,0.7) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(158,193,215,0.3);
            transition: all 0.4s;
            height: 100%;
            min-height: 320px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .service-item:hover {
            background: #eff1ee !important;
            transform: scale(1.02) translateY(-10px);
            box-shadow: 0 30px 45px rgba(79, 172, 254, 0.25);
            border-color: #4facfe;
        }
        .service-item i {
            color: #65403a;
            background: rgba(101,64,58,0.08);
            padding: 20px;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            transition: 0.3s;
        }
        .service-item:hover i {
            background: #4facfe;
            color: #fff;
            transform: rotateY(180deg);
        }

        /* ==================== الوجهات - تأثيرات زرقاء ==================== */
        .destination-item {
            border-radius: 30px;
            box-shadow: 0 20px 30px rgba(27,28,39,0.1);
            transition: 0.4s;
        }
        .destination-item:hover {
            transform: translateY(-10px) rotateX(5deg) rotateY(8deg);
            box-shadow: 0 35px 50px rgba(79, 172, 254, 0.3);
        }
        .destination-item img { height: 280px !important; border-radius: 30px; }
        .destination-overlay {
            background: linear-gradient(to top, rgba(27,28,39,0.9) 0%, transparent);
            border-radius: 0 0 30px 30px;
            padding: 25px 15px 15px;
        }

        /* ==================== قسم Mega Offer - تأثيرات بنية فقط ==================== */
        .bg-registration {
            background: #73793b !important;
            border-radius: 50px !important;
            margin: 90px 20px !important;
            box-shadow: 0 20px 30px rgba(101, 64, 58, 0.2) !important;  /* ظل بني خفيف */
            transition: box-shadow 0.4s;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .bg-registration:hover {
            box-shadow: 0 30px 45px rgba(101, 64, 58, 0.35) !important;
        }
        .bg-registration .text-white { color: #eff1ee !important; }
        .bg-registration .text-primary { 
            color: #65403a !important;  /* الكلمات الزرقاء أصبحت بنية */
        }

        /* إطار الصورة بظل بني خفيف */
        .offer-image-wrapper {
            position: relative;
            display: inline-block;
            border-radius: 40px;
            overflow: hidden;
            box-shadow: 0 15px 25px rgba(101, 64, 58, 0.25);  /* ظل بني خفيف */
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 4px solid rgba(255, 255, 255, 0.3);
            max-width: 90%;
            margin: 0 auto;
        }
        .offer-image-wrapper:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 30px 40px rgba(101, 64, 58, 0.4);  /* ظل بني أقوى قليلاً */
            border-color: #65403a;  /* إطار بني */
        }
        /* تأثير انعكاس لامع */
        .offer-image-wrapper::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 60%);
            opacity: 0;
            transition: opacity 0.5s;
            pointer-events: none;
            z-index: 2;
        }
        .offer-image-wrapper:hover::after {
            opacity: 1;
        }
        .offer-image {
            display: block;
            width: 100%;
            height: auto;
            object-fit: cover;
            transition: transform 0.7s ease;
        }
        .offer-image-wrapper:hover .offer-image {
            transform: scale(1.1);
        }
        /* تأثير توهج خلفي بني */
        .image-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 130%;
            height: 130%;
            background: radial-gradient(circle, rgba(101, 64, 58, 0.2) 0%, transparent 70%);
            transform: translate(-50%, -50%);
            opacity: 0;
            transition: opacity 0.5s;
            pointer-events: none;
            z-index: -1;
        }
        .offer-image-wrapper:hover .image-glow {
            opacity: 1;
        }

        /* ==================== المدونة - تأثيرات زرقاء ==================== */
        .blog-item {
            border-radius: 30px;
            background: #eff1ee;
            box-shadow: 0 15px 25px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        .blog-item:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px rgba(79, 172, 254, 0.2);
        }
        .blog-date { background: #4facfe; color: #fff; }
        .back-to-top { background: #65403a !important; }
        .back-to-top:hover { background: #4facfe !important; color: #1b1c27; }

        /* ==================== تحسينات الجوال ==================== */
        @media (max-width: 768px) {
            .hero-content h1 { font-size: 2.8rem; }
            .gallery-item img { height: 80px !important; }
            .gallery-carousel { max-width: 95%; padding: 10px 15px; }
            .hero-content { padding-top: 15vh; }
            .offer-image-wrapper { max-width: 80%; margin-top: 20px; }
        }
    </style>
</head>
<body>

<!-- ==================== Hero Section ==================== -->
<div class="hero-section">
    <img src="img/img/primary.jpg" class="hero-background-image" alt="Hero Background">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h4 class="text-uppercase">Discover the World</h4>
        <h1>Let's Explore <span>Together</span></h1>
        <p class="lead">Find your next adventure with us. Connect, share, and travel smarter.</p>
       
    </div>
    <div class="mini-gallery">
        <div class="gallery-carousel">
            <div id="miniCarousel" class="carousel slide" data-ride="carousel" data-interval="3000">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <div class="row mx-0">
                            <div class="col-4 gallery-item"><img src="img/img/gallery1.jpg" alt=""></div>
                            <div class="col-4 gallery-item"><img src="img/img/gallery2.jpg" alt=""></div>
                            <div class="col-4 gallery-item"><img src="img/img/gallery3.jpg" alt=""></div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="row mx-0">
                            <div class="col-4 gallery-item"><img src="img/img/gallery4.jpg" alt=""></div>
                            <div class="col-4 gallery-item"><img src="img/img/gallery5.jpg" alt=""></div>
                            <div class="col-4 gallery-item"><img src="img/img/gallery6.jpg" alt=""></div>
                        </div>
                    </div>
                </div>
                <a class="carousel-control-prev" href="#miniCarousel" data-slide="prev" style="width:auto;"><span class="gallery-control"><i class="fas fa-chevron-left"></i></span></a>
                <a class="carousel-control-next" href="#miniCarousel" data-slide="next" style="width:auto;"><span class="gallery-control"><i class="fas fa-chevron-right"></i></span></a>
            </div>
        </div>
    </div>
</div>

<!-- About Start -->
<div class="container-fluid py-5">
    <div class="container pt-5">
        <div class="row">
            <div class="col-lg-6" style="min-height:500px;">
                <div class="position-relative h-100">
                    <img class="position-absolute w-100 h-100" src="img/about.jpg" style="object-fit:cover;">
                </div>
            </div>
            <div class="col-lg-6 pt-5 pb-lg-5">
                <div class="about-text bg-white p-4 p-lg-5 my-lg-5" style="background:rgba(239,241,238,0.75); backdrop-filter:blur(12px); border-radius:40px;">
                    <h6 class="text-primary text-uppercase" style="letter-spacing:5px;">About Us</h6>
                    <h1 class="mb-3">We Provide Best Tour Packages In Your Budget</h1>
                    <p>Dolores lorem lorem ipsum sit et ipsum. Sadip sea amet diam dolore sed et...</p>
                    <div class="row mb-4">
                        <div class="col-6"><img class="img-fluid" src="img/about-1.jpg" alt=""></div>
                        <div class="col-6"><img class="img-fluid" src="img/about-2.jpg" alt=""></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- About End -->

<!-- Feature Start -->
<div class="container-fluid pb-5">
    <div class="container pb-5">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="feature-box">
                    <div class="d-flex">
                        <div class="d-flex flex-shrink-0 align-items-center justify-content-center bg-primary mr-3" style="height:100px; width:100px; border-radius:25px;">
                            <i class="fa fa-2x fa-money-check-alt text-white"></i>
                        </div>
                        <div class="d-flex flex-column">
                            <h5>Competitive Pricing</h5>
                            <p class="m-0">Best prices with special discounts.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-box">
                    <div class="d-flex">
                        <div class="d-flex flex-shrink-0 align-items-center justify-content-center bg-primary mr-3" style="height:100px; width:100px; border-radius:25px;">
                            <i class="fa fa-2x fa-award text-white"></i>
                        </div>
                        <div class="d-flex flex-column">
                            <h5>Best Services</h5>
                            <p class="m-0">Top-quality service from booking to return.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-box">
                    <div class="d-flex">
                        <div class="d-flex flex-shrink-0 align-items-center justify-content-center bg-primary mr-3" style="height:100px; width:100px; border-radius:25px;">
                            <i class="fa fa-2x fa-globe text-white"></i>
                        </div>
                        <div class="d-flex flex-column">
                            <h5>Worldwide Coverage</h5>
                            <p class="m-0">Hundreds of destinations across the globe.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Feature End -->

<!-- Destination Start -->
<div class="container-fluid py-5">
    <div class="container pt-5 pb-3">
        <div class="text-center mb-3 pb-3">
            <h6 class="text-primary text-uppercase">Destination</h6>
            <h1>Explore Top Destination</h1>
        </div>
        <div class="row">
            <?php if (mysqli_num_rows($result) == 0): ?>
                <p style="text-align:center;">لا توجد رحلات متاحة حالياً.</p>
            <?php else: ?>
                <?php while ($country = mysqli_fetch_assoc($result)): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="destination-item position-relative overflow-hidden">
                            <?php $img_src = !empty($country['images']) ? 'uploads/countries/'.$country['images'] : 'uploads/countries/default.jpg'; ?>
                            <img class="img-fluid" src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($country['name']); ?>">
                            <a class="destination-overlay text-white text-decoration-none" href="user/country.php?id=<?php echo $country['id']; ?>">
                                <h5 class="text-white"><?php echo htmlspecialchars($country['name']); ?></h5>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Destination End -->

<!-- Service Start -->
<div class="container-fluid py-5">
    <div class="container pt-5 pb-3">
        <div class="text-center mb-3 pb-3">
            <h6 class="text-primary text-uppercase">Services</h6>
            <h1>Tours & Travel Services</h1>
        </div>
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="service-item bg-white text-center py-5 px-4">
                    <i class="fa fa-2x fa-route mx-auto mb-4"></i>
                    <h5>Travel Guide</h5>
                    <p>Your trusted guide to discovering cities and booking hotels.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="service-item bg-white text-center py-5 px-4">
                    <i class="fa fa-2x fa-ticket-alt mx-auto mb-4"></i>
                    <h5>Ticket Booking</h5>
                    <p>Book flights, trains, and buses with ease at best rates.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="service-item bg-white text-center py-5 px-4">
                    <i class="fa fa-2x fa-hotel mx-auto mb-4"></i>
                    <h5>Hotel Booking</h5>
                    <p>Secure hotel bookings with exclusive deals and smooth experience.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Service End -->

<!-- Registration (Mega Offer) Start - تأثيرات بنية فقط -->
<div class="container-fluid bg-registration py-5" style="margin:90px 0;">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-7 mb-5 mb-lg-0">
                <div class="mb-4">
                    <h6 class="text-primary text-uppercase" style="letter-spacing:5px;">Mega Offer</h6>
                    <h1 class="text-white"><span class="text-primary">30% OFF</span> For Honeymoon</h1>
                </div>
                <p class="text-white">Invidunt lorem justo sanctus clita. Erat lorem labore ea...</p>
                <ul class="list-inline text-white m-0">
                    <li class="py-2"><i class="fa fa-check text-primary mr-3"></i>Labore eos amet dolor</li>
                    <li class="py-2"><i class="fa fa-check text-primary mr-3"></i>Etsea et sit dolor</li>
                    <li class="py-2"><i class="fa fa-check text-primary mr-3"></i>Diam dolor diam</li>
                </ul>
            </div>
            <div class="col-lg-5 text-center">
                <div class="offer-image-wrapper">
                    <img src="img/img/honey.jpg" alt="Honeymoon Offer" class="offer-image">
                    <div class="image-glow"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Registration End -->

<!-- Blog Start -->
<div class="container-fluid py-5">
    <div class="container pt-5 pb-3">
        <div class="text-center mb-3 pb-3">
            <h6 class="text-primary text-uppercase">Our Blog</h6>
            <h1>Latest From Our Blog</h1>
        </div>
        <div class="row pb-3">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="blog-item">
                    <div class="position-relative">
                        <img class="img-fluid w-100" src="img/blog-1.jpg" alt="">
                        <div class="blog-date"><h6 class="font-weight-bold mb-n1">01</h6><small>Jan</small></div>
                    </div>
                    <div class="bg-white p-4">
                        <div class="d-flex mb-2"><a class="text-primary" href="">Admin</a><span class="text-primary px-2">|</span><a class="text-primary" href="">Tours</a></div>
                        <a class="h5 m-0 text-decoration-none">Travel, for the world is beautiful and worth discovering.</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="blog-item">
                    <div class="position-relative">
                        <img class="img-fluid w-100" src="img/blog-2.jpg" alt="">
                        <div class="blog-date"><h6>01</h6><small>Jan</small></div>
                    </div>
                    <div class="bg-white p-4">
                        <div class="d-flex mb-2"><a class="text-primary" href="">Admin</a><span class="text-primary px-2">|</span><a class="text-primary" href="">Tours</a></div>
                        <a class="h5 m-0 text-decoration-none">Every journey begins with a step, so let your first step be today.</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="blog-item">
                    <div class="position-relative">
                        <img class="img-fluid w-100" src="img/blog-3.jpg" alt="">
                        <div class="blog-date"><h6>01</h6><small>Jan</small></div>
                    </div>
                    <div class="bg-white p-4">
                        <div class="d-flex mb-2"><a class="text-primary" href="">Admin</a><span class="text-primary px-2">|</span><a class="text-primary" href="">Tours</a></div>
                        <a class="h5 m-0 text-decoration-none">Travel fills the soul with life and creates unforgettable memories.</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Blog End -->

<?php include 'footer.php'; ?>

<a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="fa fa-angle-double-up"></i></a>

<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>
<?php
session_start();
include 'connection.php';

$country_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($country_id == 0) {
    die("Invalid country ID.");
}

// Fetch country info
$country_sql = "SELECT name FROM countries WHERE id = $country_id";
$country_res = mysqli_query($conn, $country_sql);
if (mysqli_num_rows($country_res) == 0) {
    die("Country not found.");
}
$country = mysqli_fetch_assoc($country_res);
$country_name = $country['name'];

// Fetch offices that offer tours to this country
$offices_sql = "
    SELECT DISTINCT o.id, o.name, o.logo
    FROM offices o
    INNER JOIN tours t ON o.id = t.office_id
    INNER JOIN tour_city tc ON t.id = tc.tour_id
    INNER JOIN cities ci ON tc.city_id = ci.id
    WHERE ci.country_id = $country_id
      AND t.status = 'approved'
    ORDER BY o.name
";
$offices_res = mysqli_query($conn, $offices_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($country_name); ?> - TravelGram</title>
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

        /* ==== عناصر الخلفية الجمالية ==== */
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

        /* ==== الحاوية الرئيسية زجاجية ==== */
        .container {
            max-width: 1100px;
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

        .country-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .country-name {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #65403a, #9ec1d7);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
        }

        .offices-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            justify-content: center;
            margin-top: 30px;
        }

        .office-card {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(5px);
            border-radius: 30px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.05);
            width: 240px;
            text-align: center;
            padding: 25px 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid rgba(158, 193, 215, 0.3);
        }
        .office-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 40px rgba(101, 64, 58, 0.15);
            border-color: #9ec1d7;
            background: rgba(255, 255, 255, 0.8);
        }

        .office-logo {
            width: 120px;
            height: 120px;
            object-fit: contain;
            margin: 0 auto 15px;
            display: block;
            border-radius: 25px;
            background: #f8f8f8;
            padding: 8px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
            border: 2px solid white;
        }

        .office-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1b1c27;
            margin-top: 10px;
        }

        .no-offices {
            text-align: center;
            color: #5a6a7a;
            margin: 50px 0;
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .container { padding: 20px 18px; }
            .office-card { width: 200px; padding: 20px 15px; }
            .office-logo { width: 100px; height: 100px; }
            .country-name { font-size: 2rem; }
        }

        @media (max-width: 480px) {
            .office-card { width: 160px; }
            .office-logo { width: 80px; height: 80px; }
            .office-name { font-size: 1.1rem; }
        }
    </style>
</head>
<body>
<!-- خلفية -->
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<div class="container">
    <a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Home</a>

    <div class="country-header">
        <div class="country-name"><?php echo htmlspecialchars($country_name); ?></div>
    </div>

    <?php if (mysqli_num_rows($offices_res) == 0): ?>
        <div class="no-offices">No offices currently offer tours to this country.</div>
    <?php else: ?>
        <div class="offices-grid">
            <?php while ($office = mysqli_fetch_assoc($offices_res)): ?>
                <div class="office-card" onclick="window.location.href='office_profile.php?id=<?php echo $office['id']; ?>&country_id=<?php echo $country_id; ?>'">
                    <?php if (!empty($office['logo'])): ?>
                        <img src="../<?php echo htmlspecialchars($office['logo']); ?>" class="office-logo" alt="<?php echo htmlspecialchars($office['name']); ?>">
                    <?php else: ?>
                        <i class="fas fa-building" style="font-size: 70px; color:#65403a; display: block; margin: 0 auto 15px;"></i>
                    <?php endif; ?>
                    <div class="office-name"><?php echo htmlspecialchars($office['name']); ?></div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
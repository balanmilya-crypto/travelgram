<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$my_id = $_SESSION['user_id'];

// جلب المستخدمين العاديين فقط (role = 'user')
$users_sql = "SELECT id, name, avatar, role FROM users WHERE role = 'user' AND id != $my_id ORDER BY name";
$users_res = mysqli_query($conn, $users_sql);
$users = [];
while ($u = mysqli_fetch_assoc($users_res)) {
    $users[] = $u;
}

// جلب جميع المكاتب
$offices_sql = "SELECT o.id, o.name, o.logo FROM offices o ORDER BY o.name";
$offices_res = mysqli_query($conn, $offices_sql);
$offices = [];
while ($off = mysqli_fetch_assoc($offices_res)) {
    // البحث عن مدير المكتب لاستخدامه كهدف للمحادثة
    $admin_query = mysqli_query($conn, "SELECT id FROM users WHERE office_id = {$off['id']} AND role = 'office_admin' LIMIT 1");
    if ($admin = mysqli_fetch_assoc($admin_query)) {
        $off['admin_id'] = $admin['id'];
        $offices[] = $off;
    }
}

// جلب السوبر أدمن (جميعهم إن وجد أكثر من واحد)
$super_admins_sql = "SELECT id, name, avatar FROM users WHERE role = 'super_admin' AND id != $my_id ORDER BY name";
$super_res = mysqli_query($conn, $super_admins_sql);
$super_admins = [];
while ($sa = mysqli_fetch_assoc($super_res)) {
    $super_admins[] = $sa;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Discover Contacts | TravelGram</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
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
        .back-link:hover { background: #65403a; color: white; transform: translateX(-5px); }

        h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1b1c27;
            margin: 30px 0 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        h2 i { color: #65403a; }
        .super-admin-section {
            background: rgba(158, 193, 215, 0.15);
            border-radius: 25px;
            padding: 15px 20px;
            margin-bottom: 20px;
            border: 1px dashed #9ec1d7;
        }
        .super-admin-section h3 {
            font-size: 1.2rem;
            color: #65403a;
            margin-bottom: 10px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .contact-card {
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(5px);
            border-radius: 25px;
            padding: 20px;
            border: 1px solid rgba(158, 193, 215, 0.3);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: 0.2s;
        }
        .contact-card:hover {
            background: rgba(255, 255, 255, 0.8);
            border-color: #9ec1d7;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(101, 64, 58, 0.1);
        }

        .avatar-img {
            width: 55px; height: 55px; border-radius: 50%; object-fit: cover;
            border: 2px solid #9ec1d7; box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .avatar-placeholder {
            width: 55px; height: 55px; border-radius: 50%;
            background: linear-gradient(145deg, #65403a, #9ec1d7);
            color: white; display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 24px; border: 2px solid #9ec1d7;
        }

        .contact-info { flex: 1; }
        .contact-name {
            font-weight: 700; color: #1b1c27; margin-bottom: 5px;
        }
        .contact-role {
            font-size: 0.8rem; color: #5a6a7a; margin-bottom: 8px;
        }

        .btn-message {
            display: inline-block;
            background: #65403a;
            color: white;
            padding: 6px 14px;
            border-radius: 40px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            transition: 0.2s;
            box-shadow: 0 4px 10px rgba(101, 64, 58, 0.15);
        }
        .btn-message:hover {
            background: #4f322c;
            transform: translateY(-2px);
        }

        @media (max-width: 600px) {
            .container { padding: 20px 18px; }
        }
    </style>
</head>
<body>
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<div class="container">
    <a href="messenger.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Messenger</a>

    <!-- قسم السوبر أدمن -->
    <?php if (!empty($super_admins)): ?>
    <div class="super-admin-section">
        <h3><i class="fas fa-crown" style="color: #f0ad4e;"></i> Super Admin</h3>
        <div class="contact-grid">
            <?php foreach ($super_admins as $sa): 
                $avatar = !empty($sa['avatar']) && file_exists($sa['avatar']) ? $sa['avatar'] : '';
                $placeholder = strtoupper(substr($sa['name'], 0, 1));
            ?>
                <div class="contact-card">
                    <?php if ($avatar): ?>
                        <img src="<?php echo $avatar; ?>" class="avatar-img" alt="avatar">
                    <?php else: ?>
                        <div class="avatar-placeholder"><?php echo $placeholder; ?></div>
                    <?php endif; ?>
                    <div class="contact-info">
                        <div class="contact-name"><?php echo htmlspecialchars($sa['name']); ?></div>
                        <div class="contact-role">Super Admin</div>
                        <a href="messenger.php?chat_with=<?php echo $sa['id']; ?>" class="btn-message"><i class="fas fa-comment-dots"></i> Message</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <h2><i class="fas fa-users" style="color: #65403a;"></i> Regular Users</h2>
    <div class="contact-grid">
        <?php foreach ($users as $user): 
            $avatar = !empty($user['avatar']) && file_exists($user['avatar']) ? $user['avatar'] : '';
            $placeholder = strtoupper(substr($user['name'], 0, 1));
        ?>
            <div class="contact-card">
                <?php if ($avatar): ?>
                    <img src="<?php echo $avatar; ?>" class="avatar-img" alt="avatar">
                <?php else: ?>
                    <div class="avatar-placeholder"><?php echo $placeholder; ?></div>
                <?php endif; ?>
                <div class="contact-info">
                    <div class="contact-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="contact-role">Traveler</div>
                    <a href="messenger.php?chat_with=<?php echo $user['id']; ?>" class="btn-message"><i class="fas fa-comment-dots"></i> Message</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <h2 style="margin-top: 40px;"><i class="fas fa-building" style="color: #65403a;"></i> All Offices</h2>
    <div class="contact-grid">
        <?php foreach ($offices as $office): 
            $logo = !empty($office['logo']) ? $office['logo'] : '';
            if ($logo && !file_exists($logo) && file_exists('../' . $logo)) $logo = '../' . $logo;
            $placeholder = strtoupper(substr($office['name'], 0, 1));
        ?>
            <div class="contact-card">
                <?php if ($logo): ?>
                    <img src="<?php echo $logo; ?>" class="avatar-img" alt="logo">
                <?php else: ?>
                    <div class="avatar-placeholder"><?php echo $placeholder; ?></div>
                <?php endif; ?>
                <div class="contact-info">
                    <div class="contact-name"><?php echo htmlspecialchars($office['name']); ?></div>
                    <div class="contact-role">Travel Office</div>
                    <a href="messenger.php?chat_with=<?php echo $office['admin_id']; ?>" class="btn-message"><i class="fas fa-comment-dots"></i> Message</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
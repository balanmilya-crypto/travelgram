<?php
global $base_url;

$unread_total = 0;
if (isset($_SESSION['user_id']) && isset($conn)) {
    $uid = (int)$_SESSION['user_id'];
    $query = "SELECT COUNT(*) as total FROM messages WHERE recipient_id = $uid AND is_read = 0";
    $result = mysqli_query($conn, $query);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $unread_total = (int)$row['total'];
    }
}

$user_avatar = '';
if (isset($_SESSION['user_id']) && isset($conn)) {
    $uid = $_SESSION['user_id'];
    $res = mysqli_query($conn, "SELECT avatar FROM users WHERE id = $uid");
    if ($res && $row = mysqli_fetch_assoc($res)) {
        $user_avatar = $row['avatar'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <title><?php echo $page_title ?? 'Travel Platform'; ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Free HTML Templates" name="keywords">
    <meta content="Free HTML Templates" name="description">

    <link href="<?php echo $base_url; ?>img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo $base_url; ?>css/style.css" rel="stylesheet">

    <style>
        /* ========== نظام الألوان ========== 
           #1b1c27 : أساسي داكن (خلفيات رئيسية، نصوص داكنة)
           #65403a : ثانوي دافئ (أزرار، عناصر تفاعلية)
           #9ec1d7 : تمييز فاتح (حدود، أيقونات، لمسات)
           #eff1ee : خلفيات فاتحة (بطاقات، مناطق بيضاء)
        */
        body {
            background-color: #eff1ee;
        }

        .position-relative { position: relative; }
        .unread-badge-header {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #65403a;
            color: #eff1ee;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
            min-width: 18px;
            text-align: center;
        }
        .profile-avatar-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
            vertical-align: middle;
            border: 2px solid #9ec1d7;
        }

        /* الشريط العلوي */
        .topbar-custom {
            background: #1b1c27;
            color: #eff1ee;
        }
        .topbar-custom a, .topbar-custom p, .topbar-custom span {
            color: #eff1ee !important;
        }
        .topbar-custom .btn-outline-light {
            color: #eff1ee;
            border-color: #9ec1d7;
            background: transparent;
        }
        .topbar-custom .btn-outline-light:hover {
            background-color: #65403a;
            color: #eff1ee !important;
            border-color: #65403a;
        }

        /* شريط التنقل الرئيسي */
        .navbar-custom {
            background: rgba(239, 241, 238, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 3px solid #65403a;
            padding-top: 1.2rem !important;
            padding-bottom: 1.2rem !important;
            box-shadow: 0 10px 25px rgba(27, 28, 39, 0.05);
        }
        .navbar-custom .navbar-nav .nav-link {
            color: #1b1c27;
            font-weight: 600;
            padding: 0.8rem 1rem !important;
        }
        .navbar-custom .navbar-nav .nav-link:hover,
        .navbar-custom .navbar-nav .nav-link.active {
            color: #65403a;
            background: rgba(158, 193, 215, 0.1);
            border-radius: 8px;
        }

        /* الشعار */
        .navbar-brand {
            display: inline-flex !important;
            align-items: center !important;
            gap: 12px !important;
        }
        .logo-img {
            width: 45px !important;
            height: 45px !important;
            border-radius: 50% !important;
            object-fit: cover !important;
            border: 2px solid #9ec1d7 !important;
        }
        .navbar-brand h1 {
            margin: 0 !important;
            font-size: 1.9rem !important;
            font-weight: 800;
        }
        .navbar-brand h1 .text-dark {
            color: #1b1c27 !important;
        }
        .navbar-brand h1 .text-primary {
            color: #65403a !important;
        }

        /* تحسينات الجوال */
        @media (max-width: 768px) {
            .logo-img { width: 38px !important; height: 38px !important; }
            .navbar-brand h1 { font-size: 1.5rem !important; }
        }
    </style>
</head>
<body>
<!-- Topbar Start -->
<div class="container-fluid topbar-custom pt-3 d-none d-lg-block">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 text-center text-lg-left mb-2 mb-lg-0">
                <div class="d-inline-flex align-items-center">
                    <p><i class="fa fa-envelope mr-2"></i>info@example.com</p>
                    <p class="text-white px-3">|</p>
                    <p><i class="fa fa-phone-alt mr-2"></i>+012 345 6789</p>
                </div>
            </div>
            <div class="col-lg-6 text-center text-lg-right">
                <div class="d-inline-flex align-items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="ml-2">
                            <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                            <?php
                            $role_text = '';
                            switch ($_SESSION['user_role'] ?? 'user') {
                                case 'super_admin': $role_text = '(Super Admin)'; break;
                                case 'office_admin': $role_text = '(Office Admin)'; break;
                                default: $role_text = '(User)';
                            }
                            echo ' ' . $role_text;
                            ?>
                        </span>
                        <a href="<?php echo $base_url; ?>logout.php" class="btn btn-sm btn-outline-light ml-2">Logout</a>
                        <a href="<?php echo $base_url; ?>office/register_office.php" class="btn btn-sm btn-outline-light ml-2">Register Office</a>
                    <?php else: ?>
                        <a href="<?php echo $base_url; ?>login.php" class="btn btn-sm btn-outline-light ml-2">Login</a>
                        <a href="<?php echo $base_url; ?>user/register_user.php" class="btn btn-sm btn-outline-light ml-2">Sign Up</a>
                        <a href="<?php echo $base_url; ?>office/register_office.php" class="btn btn-sm btn-outline-light ml-2">Register Office</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Topbar End -->

<!-- Navbar Start -->
<div class="container-fluid position-relative nav-bar p-0">
    <div class="container-lg position-relative p-0 px-lg-3" style="z-index: 9;">
        <nav class="navbar navbar-expand-lg navbar-custom shadow-lg py-3 py-lg-0 pl-3 pl-lg-5">
            <a href="<?php echo $base_url; ?>index.php" class="navbar-brand">
                <img src="<?php echo $base_url; ?>img/logo.jpg" alt="Logo" class="logo-img">
                <h1 class="m-0"><span class="text-dark">TRAVEL</span><span class="text-primary">GRAM</span></h1>
            </a>
            <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-between px-3" id="navbarCollapse">
                <div class="navbar-nav ml-auto py-0">
                    <a href="<?php echo $base_url; ?>index.php" class="nav-item nav-link active">Tours</a>
                    <a href="<?php echo $base_url; ?>blog.php" class="nav-item nav-link">Top Rated Tours</a>
                    <a href="<?php echo $base_url; ?>user/feed.php" class="nav-item nav-link"><i class="fas fa-home"></i></a>
                    <a href="<?php echo $base_url; ?>user/messenger.php" class="nav-item nav-link position-relative">
                        <i class="fas fa-comment-dots"></i> Messenger
                        <?php if ($unread_total > 0): ?>
                            <span class="unread-badge-header"><?php echo $unread_total; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo $base_url; ?>user/profile.php" class="nav-item nav-link" title="My Profile">
                        <?php if (!empty($user_avatar) && file_exists($_SERVER['DOCUMENT_ROOT'] . $user_avatar)): ?>
                            <img src="<?php echo $base_url . $user_avatar; ?>" class="profile-avatar-icon" alt="Profile">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </nav>
    </div>
</div>
<!-- Navbar End -->
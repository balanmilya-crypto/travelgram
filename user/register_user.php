<?php
session_start();
include '../admin/connection.php'; // تعديل المسار للوصول إلى قاعدة البيانات

// إذا كان المستخدم مسجلاً بالفعل، نوجهه إلى الصفحة الرئيسية
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: ../index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // استقبال البيانات
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    // التحقق من الحقول الإجبارية
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // تنظيف البيانات للاستعلام
        $name = mysqli_real_escape_string($conn, $name);
        $email = mysqli_real_escape_string($conn, $email);
        $phone = mysqli_real_escape_string($conn, $phone);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // التحقق من عدم وجود البريد مسبقاً
        $check_sql = "SELECT id FROM users WHERE email = '$email'";
        $check_result = mysqli_query($conn, $check_sql);
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Email already registered.";
        } else {
            // معالجة رفع الصورة الشخصية
            $avatar_path = '';
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
                $target_dir = "../uploads/avatars/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($ext, $allowed)) {
                    $filename = time() . '_' . uniqid() . '.' . $ext;
                    $target_file = $target_dir . $filename;
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                        $avatar_path = '../uploads/avatars/' . $filename;
                    } else {
                        $error = "Failed to upload image.";
                    }
                } else {
                    $error = "Only JPG, JPEG, PNG, GIF are allowed for avatar.";
                }
            }

            if (empty($error)) {
                // إدراج المستخدم الجديد
                $insert_sql = "INSERT INTO users (name, email, password, phone, avatar, role, is_active, created_at, updated_at) 
                               VALUES ('$name', '$email', '$hashed_password', '$phone', '$avatar_path', 'user', 1, NOW(), NOW())";
                if (mysqli_query($conn, $insert_sql)) {
                    $success = "Registration successful! You can now login.";
                } else {
                    $error = "Database error: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | TravelGram</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', 'Poppins', sans-serif;
            background: #eff1ee;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* ==== عناصر الخلفية الجمالية (زخارف) ==== */
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

        /* خطوط منحنية زخرفية */
        .bg-wave {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
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

        /* ==== بطاقة التسجيل ==== */
        .register-container {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(15px);
            border-radius: 40px;
            padding: 40px 45px;
            width: 520px;
            max-width: 100%;
            box-shadow: 0 30px 50px rgba(27, 28, 39, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .register-container:hover {
            transform: translateY(-8px);
            box-shadow: 0 40px 60px rgba(101, 64, 58, 0.15);
            background: rgba(255, 255, 255, 0.85);
        }

        h2 {
            text-align: center;
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #65403a, #9ec1d7);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 30px;
            letter-spacing: -0.02em;
        }

        .form-group {
            margin-bottom: 22px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1b1c27;
            font-size: 0.95rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="file"] {
            width: 100%;
            padding: 14px 18px;
            border: 1.5px solid rgba(158, 193, 215, 0.3);
            border-radius: 40px;
            font-size: 1rem;
            background: rgba(239, 241, 238, 0.6);
            transition: all 0.3s;
            outline: none;
            font-family: inherit;
            color: #1b1c27;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #65403a;
            box-shadow: 0 0 0 4px rgba(101, 64, 58, 0.15);
            background: white;
        }

        input[type="file"] {
            padding: 10px 15px;
            cursor: pointer;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(145deg, #65403a, #4f322c);
            color: white;
            font-weight: 700;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            box-shadow: 0 8px 20px rgba(101, 64, 58, 0.3);
            font-family: inherit;
            letter-spacing: 0.5px;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(101, 64, 58, 0.4);
            background: linear-gradient(145deg, #4f322c, #65403a);
        }

        .error {
            background: rgba(220, 53, 69, 0.08);
            backdrop-filter: blur(8px);
            color: #b91c1c;
            border-left: 4px solid #b91c1c;
            padding: 12px 18px;
            border-radius: 40px;
            text-align: center;
            margin-bottom: 25px;
            font-size: 0.9rem;
        }

        .success {
            background: rgba(40, 167, 69, 0.08);
            backdrop-filter: blur(8px);
            color: #155724;
            border-left: 4px solid #28a745;
            padding: 12px 18px;
            border-radius: 40px;
            text-align: center;
            margin-bottom: 25px;
            font-size: 0.9rem;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            color: #1b1c27;
            font-size: 0.95rem;
        }

        .footer a {
            color: #65403a;
            text-decoration: none;
            font-weight: 700;
            transition: 0.2s;
            border-bottom: 1px solid transparent;
        }

        .footer a:hover {
            color: #4f322c;
            border-bottom-color: #65403a;
        }

        .note {
            font-size: 0.75rem;
            color: #7a8a9a;
            margin-top: 6px;
        }

        .register-icon {
            text-align: center;
            margin-bottom: 10px;
        }

        .register-icon i {
            font-size: 3rem;
            color: #65403a;
            background: rgba(101, 64, 58, 0.1);
            padding: 20px;
            border-radius: 50%;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        @media (max-width: 550px) {
            .register-container {
                padding: 30px 25px;
                border-radius: 30px;
            }
            h2 {
                font-size: 1.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- عناصر الخلفية الزخرفية -->
    <div class="bg-bubble"></div>
    <div class="bg-bubble"></div>
    <div class="bg-bubble"></div>
    <div class="bg-bubble"></div>
    <div class="bg-bubble"></div>
    <div class="bg-wave"></div>

    <div class="register-container">
        <div class="register-icon">
            <i class="fas fa-user-plus"></i>
        </div>
        <h2>Create Account</h2>

        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><i class="fas fa-check-circle mr-2"></i><?php echo $success; ?></div>
            <div class="footer">
                <a href="../login.php"><i class="fas fa-sign-in-alt mr-1"></i>Login now</a>
            </div>
        <?php else: ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name"><i class="fas fa-user mr-2"></i>Full Name *</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required autofocus>
                </div>

                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope mr-2"></i>Email *</label>
                    <input type="email" id="email" name="email" placeholder="your@email.com" required>
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock mr-2"></i>Password * (min 6 characters)</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock mr-2"></i>Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone-alt mr-2"></i>Phone (optional)</label>
                    <input type="text" id="phone" name="phone" placeholder="+1234567890">
                </div>

                <div class="form-group">
                    <label for="avatar"><i class="fas fa-image mr-2"></i>Avatar (optional)</label>
                    <input type="file" id="avatar" name="avatar" accept="image/*">
                    <div class="note">Allowed: JPG, JPEG, PNG, GIF</div>
                </div>

                <button type="submit" class="btn"><i class="fas fa-user-check mr-2"></i>Register</button>
            </form>
            <div class="footer">
                Already have an account? <a href="../login.php">Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>
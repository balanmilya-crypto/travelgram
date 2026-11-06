<?php
session_start();
include 'admin/connection.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("Invalid or missing token.");
}

// التحقق من صلاحية الرمز
$sql = "SELECT pr.*, u.id AS user_id, u.name FROM password_resets pr 
        JOIN users u ON pr.user_id = u.id 
        WHERE pr.token = '" . mysqli_real_escape_string($conn, $token) . "' 
        AND pr.expires_at > NOW()";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    die("The password reset link is invalid or has expired. Please request a new one.");
}

$reset_data = mysqli_fetch_assoc($result);
$user_id = $reset_data['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirm_password)) {
        $error = "Please fill in both password fields.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // تحديث كلمة المرور
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
        
        if (mysqli_query($conn, $update)) {
            // حذف الرمز المستخدم
            mysqli_query($conn, "DELETE FROM password_resets WHERE id = " . $reset_data['id']);
            $success = "Your password has been reset successfully. You can now <a href='login.php'>login</a> with your new password.";
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - TravelGram</title>
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

        /* ==== بطاقة إعادة تعيين كلمة المرور ==== */
        .reset-container {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(15px);
            border-radius: 40px;
            padding: 40px 45px;
            width: 480px;
            max-width: 100%;
            box-shadow: 0 30px 50px rgba(27, 28, 39, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .reset-container:hover {
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
            margin-bottom: 15px;
            letter-spacing: -0.02em;
        }

        .subtitle {
            text-align: center;
            color: #1b1c27;
            margin-bottom: 30px;
            font-size: 0.95rem;
            opacity: 0.8;
        }

        .form-group { margin-bottom: 25px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #1b1c27; font-size: 0.95rem; }
        input[type="password"] {
            width: 100%; padding: 14px 18px;
            border: 1.5px solid rgba(158, 193, 215, 0.3); border-radius: 40px;
            font-size: 1rem; background: rgba(239, 241, 238, 0.6);
            transition: all 0.3s; outline: none; font-family: inherit; color: #1b1c27;
        }
        input[type="password"]:focus {
            border-color: #65403a;
            box-shadow: 0 0 0 4px rgba(101, 64, 58, 0.15);
            background: white;
        }
        button {
            width: 100%; padding: 15px;
            background: linear-gradient(145deg, #65403a, #4f322c);
            color: white; font-weight: 700; border: none; border-radius: 50px;
            font-size: 1.1rem; cursor: pointer; transition: all 0.3s; margin-top: 15px;
            box-shadow: 0 8px 20px rgba(101, 64, 58, 0.3); font-family: inherit;
        }
        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(101, 64, 58, 0.4);
            background: linear-gradient(145deg, #4f322c, #65403a);
        }
        .success {
            background: rgba(40, 167, 69, 0.08); backdrop-filter: blur(8px);
            color: #155724; border-left: 4px solid #28a745;
            padding: 12px 18px; border-radius: 40px; text-align: center;
            margin-bottom: 25px; font-size: 0.9rem;
        }
        .success a { color: #65403a; font-weight: 700; }
        .error {
            background: rgba(220, 53, 69, 0.08); backdrop-filter: blur(8px);
            color: #b91c1c; border-left: 4px solid #b91c1c;
            padding: 12px 18px; border-radius: 40px; text-align: center;
            margin-bottom: 25px; font-size: 0.9rem;
        }
        .footer { text-align: center; margin-top: 30px; color: #1b1c27; font-size: 0.95rem; }
        .footer a { color: #65403a; text-decoration: none; font-weight: 700; transition: 0.2s; }
        .footer a:hover { color: #4f322c; border-bottom: 1px solid #65403a; }
        .icon { text-align: center; margin-bottom: 10px; }
        .icon i {
            font-size: 3rem; color: #65403a; background: rgba(101, 64, 58, 0.1);
            padding: 20px; border-radius: 50%; box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }
        @media (max-width: 500px) {
            .reset-container { padding: 30px 25px; border-radius: 30px; }
            h2 { font-size: 1.9rem; }
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

    <div class="reset-container">
        <div class="icon">
            <i class="fas fa-key"></i>
        </div>
        <h2>Reset Password</h2>
        <p class="subtitle">Hello, <?php echo htmlspecialchars($reset_data['name']); ?>. Please enter your new password below.</p>

        <?php if ($success): ?>
            <div class="success"><i class="fas fa-check-circle mr-2"></i><?php echo $success; ?></div>
        <?php elseif ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label><i class="fas fa-lock mr-2"></i>New Password</label>
                    <input type="password" name="password" placeholder="At least 6 characters" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock mr-2"></i>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Re-enter new password" required>
                </div>
                <button type="submit"><i class="fas fa-save mr-2"></i>Reset Password</button>
            </form>
        <?php endif; ?>
        <div class="footer">
            <a href="login.php"><i class="fas fa-arrow-left mr-1"></i>Back to Login</a>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>
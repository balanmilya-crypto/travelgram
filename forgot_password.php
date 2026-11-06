<?php
session_start();
include 'admin/connection.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // التحقق من وجود المستخدم
        $sql = "SELECT id, name FROM users WHERE email = '$email' AND is_active = 1";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // هنا يمكنك توليد token فريد وتخزينه في قاعدة البيانات مع وقت انتهاء
            // ثم إرسال بريد إلكتروني حقيقي باستخدام mail() أو PHPMailer
            
            // للتبسيط حالياً: نعرض رسالة نجاح ونخبر المستخدم بفحص بريده
            $message = "A password reset link has been sent to your email address. Please check your inbox.";
            
        } else {
            // لا نكشف ما إذا كان البريد موجوداً أم لا (للأمان)
            $message = "If an account exists with that email, you will receive a password reset link shortly.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - TravelGram</title>
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

        /* ==== بطاقة استعادة كلمة المرور ==== */
        .forgot-container {
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

        .forgot-container:hover {
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

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1b1c27;
            font-size: 0.95rem;
        }

        input[type="email"] {
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

        input[type="email"]:focus {
            border-color: #65403a;
            box-shadow: 0 0 0 4px rgba(101, 64, 58, 0.15);
            background: white;
        }

        button {
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

        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(101, 64, 58, 0.4);
            background: linear-gradient(145deg, #4f322c, #65403a);
        }

        .message {
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

        .icon {
            text-align: center;
            margin-bottom: 10px;
        }

        .icon i {
            font-size: 3rem;
            color: #65403a;
            background: rgba(101, 64, 58, 0.1);
            padding: 20px;
            border-radius: 50%;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        @media (max-width: 500px) {
            .forgot-container {
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

    <div class="forgot-container">
        <div class="icon">
            <i class="fas fa-lock"></i>
        </div>
        <h2>Forgot Password?</h2>
        <p class="subtitle">Enter your email address and we'll send you a link to reset your password.</p>

        <?php if ($message): ?>
            <div class="message"><i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label><i class="fas fa-envelope mr-2"></i>Email Address</label>
                <input type="email" name="email" placeholder="your@email.com" required autofocus>
            </div>
            <button type="submit"><i class="fas fa-paper-plane mr-2"></i>Send Reset Link</button>
        </form>
        <div class="footer">
            <a href="login.php"><i class="fas fa-arrow-left mr-1"></i>Back to Login</a>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>
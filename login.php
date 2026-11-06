<?php
session_start();
include 'admin/connection.php'; // Adjust path if needed

// If already logged in, redirect based on role
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    switch ($_SESSION['user_role']) {
        case 'super_admin':
            header('Location: admin/dashboard.php');
            break;
        case 'office_admin':
            header('Location: office/dashboard.php');
            break;
        default:
            header('Location: index.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username_or_email = mysqli_real_escape_string($conn, $_POST['username_or_email']);
    $password = $_POST['password'];

    if (empty($username_or_email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Find user by username or email
        $sql = "SELECT * FROM users WHERE (name = '$username_or_email' OR email = '$username_or_email') AND is_active = 1";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            if (password_verify($password, $user['password'])) {
                // Update last login time
                mysqli_query($conn, "UPDATE users SET last_login = NOW() WHERE id = " . $user['id']);

                // Set session variables
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_office_id'] = $user['office_id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // If super admin, set admin_logged_in flag
                if ($user['role'] === 'super_admin') {
                    $_SESSION['admin_logged_in'] = true;
                } else {
                    unset($_SESSION['admin_logged_in']);
                }

                // Redirect based on role
                switch ($user['role']) {
                    case 'super_admin':
                        header('Location: admin/dashboard.php');
                        break;
                    case 'office_admin':
                        header('Location: office/dashboard.php');
                        break;
                    default:
                        header('Location: index.php');
                }
                exit();
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "User not found or account inactive.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TravelGram</title>
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

        /* ==== بطاقة تسجيل الدخول ==== */
        .login-container {
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

        .login-container:hover {
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
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1b1c27;
            font-size: 0.95rem;
        }

        input[type="text"],
        input[type="password"] {
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
        input[type="password"]:focus {
            border-color: #65403a;
            box-shadow: 0 0 0 4px rgba(101, 64, 58, 0.15);
            background: white;
        }

        input::placeholder {
            color: #7a8a9a;
            opacity: 0.6;
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
            font-weight: 500;
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

        .login-icon {
            text-align: center;
            margin-bottom: 10px;
        }

        .login-icon i {
            font-size: 3rem;
            color: #65403a;
            background: rgba(101, 64, 58, 0.1);
            padding: 20px;
            border-radius: 50%;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        @media (max-width: 500px) {
            .login-container {
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

    <div class="login-container">
        <div class="login-icon">
            <i class="fas fa-plane-departure"></i>
        </div>
        <h2>Welcome Back</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label><i class="fas fa-user mr-2"></i>Username or Email</label>
                <input type="text" name="username_or_email" placeholder="Enter your username or email" required autofocus>
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock mr-2"></i>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit"><i class="fas fa-sign-in-alt mr-2"></i>Login</button>
        </form>
        <div class="footer">
            Don't have an account? <a href="user/register_user.php">Sign Up</a><br>
            <a href="forgot_password.php" style="font-size:0.9rem; font-weight:500;">Forgot password?</a>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>
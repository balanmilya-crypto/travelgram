<?php
session_start();
include '../admin/connection.php'; // تعديل المسار للوصول إلى قاعدة البيانات

if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: ../index.php');
    exit();
} 

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $office_name = $_POST['office_name'] ?? '';
    $office_description = $_POST['office_description'] ?? '';
    $office_contact_email = $_POST['office_contact_email'] ?? '';
    $office_contact_phone = $_POST['office_contact_phone'] ?? '';
    $office_address = $_POST['office_address'] ?? '';
    $office_type = $_POST['office_type'] ?? 'both';
    $admin_name = $_POST['admin_name'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    $admin_phone = $_POST['admin_phone'] ?? '';

    if (empty($office_name) || empty($admin_name) || empty($admin_email) || empty($admin_password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format for admin.";
    } elseif (!in_array($office_type, ['tourism', 'scholarship', 'both'])) {
        $office_type = 'both';
    } else {
        $office_name = mysqli_real_escape_string($conn, $office_name);
        $office_description = mysqli_real_escape_string($conn, $office_description);
        $office_contact_email = mysqli_real_escape_string($conn, $office_contact_email);
        $office_contact_phone = mysqli_real_escape_string($conn, $office_contact_phone);
        $office_address = mysqli_real_escape_string($conn, $office_address);
        $office_type = mysqli_real_escape_string($conn, $office_type);
        $admin_name = mysqli_real_escape_string($conn, $admin_name);
        $admin_email = mysqli_real_escape_string($conn, $admin_email);
        $admin_phone = mysqli_real_escape_string($conn, $admin_phone);

        $check_sql = "SELECT id FROM users WHERE email = '$admin_email'";
        $check_result = mysqli_query($conn, $check_sql);
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Email already registered.";
        } else {
            $logo_path = '';
            if (isset($_FILES['office_logo']) && $_FILES['office_logo']['error'] == 0) {
                $target_dir = "../uploads/offices/";
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $ext = strtolower(pathinfo($_FILES['office_logo']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($ext, $allowed)) {
                    $filename = time() . '_' . uniqid() . '.' . $ext;
                    $target_file = $target_dir . $filename;
                    if (move_uploaded_file($_FILES['office_logo']['tmp_name'], $target_file)) {
                        $logo_path = 'uploads/offices/' . $filename;
                    } else $error = "Failed to upload logo.";
                } else $error = "Only JPG, JPEG, PNG, GIF allowed.";
            }

            if (empty($error)) {
                mysqli_begin_transaction($conn);
                try {
                    $office_sql = "INSERT INTO offices (name, description, contact_email, contact_phone, address, type, logo, approved, created_at, updated_at) 
                                   VALUES ('$office_name', '$office_description', '$office_contact_email', '$office_contact_phone', '$office_address', '$office_type', '$logo_path', 0, NOW(), NOW())";
                    if (!mysqli_query($conn, $office_sql)) throw new Exception("Office insert failed.");
                    $office_id = mysqli_insert_id($conn);

                    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                    $hashed_password = mysqli_real_escape_string($conn, $hashed_password);
                    $admin_sql = "INSERT INTO users (name, email, password, phone, avatar, role, office_id, is_active, created_at, updated_at) 
                                  VALUES ('$admin_name', '$admin_email', '$hashed_password', '$admin_phone', '', 'office_admin', $office_id, 0, NOW(), NOW())";
                    if (!mysqli_query($conn, $admin_sql)) throw new Exception("Admin insert failed.");

                    $super_admin_id = 0;
                    $sa_query = mysqli_query($conn, "SELECT id FROM users WHERE role = 'super_admin' LIMIT 1");
                    if ($sa_query && $sa_row = mysqli_fetch_assoc($sa_query)) $super_admin_id = $sa_row['id'];
                    if ($super_admin_id > 0) {
                        $title = "🏢 New Office Pending Approval";
                        $message = "Office \"$office_name\" has registered and is waiting for your approval.";
                        mysqli_query($conn, "INSERT INTO notifications (user_id, type, title, message, is_read, created_at) 
                                             VALUES ($super_admin_id, 'new_office_pending', '$title', '$message', 0, NOW())");
                    }
                    mysqli_commit($conn);
                    $success = "Registration submitted. You will be notified once approved.";
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $error = "Error: " . $e->getMessage();
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
    <title>Register Office | TravelGram</title>
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
            width: 800px;
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
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #65403a, #9ec1d7);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 5px;
            letter-spacing: -0.02em;
        }

        .subhead {
            text-align: center;
            color: #1b1c27;
            margin-bottom: 25px;
            font-size: 0.9rem;
            opacity: 0.8;
            border-bottom: 1px solid rgba(158, 193, 215, 0.3);
            display: inline-block;
            width: auto;
            margin-left: auto;
            margin-right: auto;
            padding-bottom: 8px;
        }

        h3 {
            font-size: 1.3rem;
            margin: 20px 0 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #65403a;
            display: inline-block;
            color: #1b1c27;
            font-weight: 700;
        }

        .form-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #1b1c27;
            font-size: 0.9rem;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 18px;
            border: 1.5px solid rgba(158, 193, 215, 0.3);
            border-radius: 40px;
            font-size: 0.95rem;
            background: rgba(239, 241, 238, 0.6);
            transition: all 0.3s;
            outline: none;
            font-family: inherit;
            color: #1b1c27;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #65403a;
            box-shadow: 0 0 0 4px rgba(101, 64, 58, 0.15);
            background: white;
        }

        textarea {
            border-radius: 30px;
            resize: vertical;
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
            margin-top: 20px;
            box-shadow: 0 8px 20px rgba(101, 64, 58, 0.3);
            font-family: inherit;
            letter-spacing: 0.5px;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(101, 64, 58, 0.4);
            background: linear-gradient(145deg, #4f322c, #65403a);
        }

        .error, .success {
            padding: 12px 18px;
            border-radius: 40px;
            text-align: center;
            margin-bottom: 20px;
            font-size: 0.9rem;
            backdrop-filter: blur(8px);
        }

        .error {
            background: rgba(220, 53, 69, 0.08);
            color: #b91c1c;
            border-left: 4px solid #b91c1c;
        }

        .success {
            background: rgba(40, 167, 69, 0.08);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .note {
            font-size: 0.75rem;
            color: #7a8a9a;
            margin-top: 5px;
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            color: #1b1c27;
            font-size: 0.95rem;
        }

        .login-link a {
            color: #65403a;
            text-decoration: none;
            font-weight: 700;
            transition: 0.2s;
            border-bottom: 1px solid transparent;
        }

        .login-link a:hover {
            color: #4f322c;
            border-bottom-color: #65403a;
        }

        .register-icon {
            text-align: center;
            margin-bottom: 5px;
        }

        .register-icon i {
            font-size: 2.5rem;
            color: #65403a;
            background: rgba(101, 64, 58, 0.1);
            padding: 15px;
            border-radius: 50%;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        @media (max-width: 700px) {
            .register-container {
                padding: 30px 25px;
                border-radius: 30px;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            h2 {
                font-size: 1.8rem;
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
            <i class="fas fa-building"></i>
        </div>
        <h2>Register Your Office</h2>
        <div class="subhead">Join TravelGram and start sharing your tours</div>

        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><i class="fas fa-check-circle mr-2"></i><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <h3><i class="fas fa-building mr-2"></i>Office Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Office Name *</label>
                    <input type="text" name="office_name" placeholder="e.g., Dream Travels" required>
                </div>
                
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="office_description" rows="2" placeholder="Tell us about your office..."></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Office Email</label>
                    <input type="email" name="office_contact_email" placeholder="contact@office.com">
                </div>
                
            </div>
            <div class="form-group">
                <label>Address</label>
                <input type="text" name="office_address" placeholder="Street, City, Country">
            </div>
            <div class="form-group">
                <label>Logo (optional)</label>
                <input type="file" name="office_logo" accept="image/*">
                <div class="note">JPG, PNG, GIF</div>
            </div>

            <h3><i class="fas fa-user-tie mr-2"></i>Admin Account</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="admin_name" placeholder="Admin Name" required>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="admin_email" placeholder="admin@office.com" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="admin_password" placeholder="At least 6 characters" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="admin_phone" placeholder="+1234567890">
                </div>
            </div>
            <button type="submit" class="btn"><i class="fas fa-paper-plane mr-2"></i>Submit Registration</button>
        </form>
        <div class="login-link">
            Already have an account? <a href="../login.php">Login</a>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>
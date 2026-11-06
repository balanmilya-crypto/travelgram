<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

// 1. التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$follower_id = $_SESSION['user_id'];
$follower_name = $_SESSION['user_name'] ?? 'Someone';

// 2. استقبال البيانات
$type = $_GET['type'] ?? 'user';
$followable_id = intval($_GET['id'] ?? $_GET['user_id'] ?? 0);
$action = $_GET['action'] ?? '';

// 3. التحقق من صحة المدخلات
if (!$followable_id || !in_array($type, ['user', 'office']) || !in_array($action, ['follow', 'unfollow'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

$followed = false;

// 4. تنفيذ المتابعة أو إلغاء المتابعة
if ($action == 'follow') {
    $check = mysqli_query($conn, "SELECT id FROM follows WHERE follower_id = $follower_id AND followable_type = '$type' AND followable_id = $followable_id");
    if (mysqli_num_rows($check) == 0) {
        $insert = "INSERT INTO follows (follower_id, followable_type, followable_id, created_at) 
                   VALUES ($follower_id, '$type', $followable_id, NOW())";
        if (mysqli_query($conn, $insert)) {
            // ========== إرسال الإشعارات ==========
            if ($type == 'user') {
                // متابعة مستخدم: إشعار للمستخدم نفسه
                mysqli_query($conn, "INSERT INTO notifications (user_id, type, title, message, is_read, created_at) 
                                     VALUES ($followable_id, 'new_follower', 'New Follower', '$follower_name started following you.', 0, NOW())");
            } elseif ($type == 'office') {
                // متابعة مكتب: إشعار لمدير (أو مدراء) المكتب
                // نجلب المستخدمين المرتبطين بهذا المكتب ولهم دور office_admin
                $admins_query = mysqli_query($conn, "SELECT id FROM users WHERE office_id = $followable_id AND role = 'office_admin'");
                while ($admin = mysqli_fetch_assoc($admins_query)) {
                    $admin_id = $admin['id'];
                    mysqli_query($conn, "INSERT INTO notifications (user_id, type, title, message, is_read, created_at) 
                                         VALUES ($admin_id, 'new_follower', 'New Follower', '$follower_name started following your office.', 0, NOW())");
                }
                // إذا لم يوجد مدير، يمكن إرسال إشعار للسوبر أدمن (اختياري)
                // ...
            }
        }
    }
    $followed = true;
} elseif ($action == 'unfollow') {
    mysqli_query($conn, "DELETE FROM follows WHERE follower_id = $follower_id AND followable_type = '$type' AND followable_id = $followable_id");
    $followed = false;
}

// 5. جلب عدد المتابعين الجديد
$count_query = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM follows WHERE followable_type = '$type' AND followable_id = $followable_id");
$count = mysqli_fetch_assoc($count_query)['cnt'];

// 6. إعادة النتيجة
echo json_encode([
    'success' => true,
    'followers_count' => $count,
    'following' => $followed
]);
exit();
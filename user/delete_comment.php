<?php
session_start();
include 'connection.php'; // عدّل المسار حسب هيكل مجلداتك

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$comment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tour_id = isset($_GET['tour_id']) ? intval($_GET['tour_id']) : 0;

if ($comment_id == 0 || $tour_id == 0) {
    die('طلب غير صحيح.');
}

// التحقق من أن المستخدم هو صاحب التعليق
$check_sql = "SELECT user_id FROM comments WHERE id = $comment_id";
$check_res = mysqli_query($conn, $check_sql);
if (mysqli_num_rows($check_res) == 0) {
    die('التعليق غير موجود.');
}
$comment = mysqli_fetch_assoc($check_res);
if ($comment['user_id'] != $_SESSION['user_id']) {
    die('لا تملك صلاحية حذف هذا التعليق.');
}

// حذف التعليق (سيتم حذف الردود تلقائياً بسبب ON DELETE CASCADE)
$delete_sql = "DELETE FROM comments WHERE id = $comment_id";
if (mysqli_query($conn, $delete_sql)) {
    header("Location: tour_details.php?id=$tour_id&comment_deleted=1");
    exit();
} else {
    die('خطأ في الحذف: ' . mysqli_error($conn));
}
?>
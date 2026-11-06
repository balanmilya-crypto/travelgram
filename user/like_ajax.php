<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'غير مصرح']));
}
$user_id = $_SESSION['user_id'];
$post_id = intval($_GET['post_id']);

// التحقق من وجود إعجاب سابق
$check = mysqli_query($conn, "SELECT id FROM post_reactions WHERE user_id = $user_id AND post_id = $post_id AND reaction_type_id = 1");
if (mysqli_num_rows($check) > 0) {
    // إلغاء الإعجاب
    mysqli_query($conn, "DELETE FROM post_reactions WHERE user_id = $user_id AND post_id = $post_id AND reaction_type_id = 1");
    $user_liked = false;
} else {
    // إضافة إعجاب
    mysqli_query($conn, "INSERT INTO post_reactions (user_id, post_id, reaction_type_id, created_at) VALUES ($user_id, $post_id, 1, NOW())");
    $user_liked = true;
}

// جلب العدد الجديد
$count_query = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM post_reactions WHERE post_id = $post_id AND reaction_type_id = 1");
$count = mysqli_fetch_assoc($count_query)['cnt'];

echo json_encode([
    'success' => true,
    'likes' => $count,
    'user_liked' => $user_liked
]);
?>
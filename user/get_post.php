<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$post_id) {
    echo json_encode(['error' => 'Invalid post ID']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';

// جلب المنشور مع بيانات المستخدم
$query = "SELECT p.*, u.name AS user_name, u.avatar AS user_avatar, u.id AS user_id
          FROM posts p
          JOIN users u ON p.user_id = u.id
          WHERE p.id = $post_id";
$result = mysqli_query($conn, $query);
$post = mysqli_fetch_assoc($result);
if (!$post) {
    echo json_encode(['error' => 'Post not found']);
    exit();
}

// بناء وسائط العرض (صورة أو فيديو)
$media = '';
if (!empty($post['image'])) {
    $media = '<img src="../' . htmlspecialchars($post['image']) . '" style="max-width:100%; max-height:500px; object-fit:contain;">';
} elseif (!empty($post['video'])) {
    $media = '<video controls style="max-width:100%; max-height:500px;"><source src="../' . htmlspecialchars($post['video']) . '" type="video/mp4"></video>';
}

// دالة لجلب التعليقات والردود بشكل متداخل
function getCommentsWithReplies($post_id, $parent_id, $conn, $current_user_id, $user_role) {
    $sql = "SELECT c.*, u.name AS user_name, u.avatar AS user_avatar
            FROM comments c
            JOIN post_comments pc ON pc.comment_id = c.id
            JOIN users u ON c.user_id = u.id
            WHERE pc.commentable_type = 'post' AND pc.commentable_id = $post_id
            AND c.parent_id " . ($parent_id ? "= $parent_id" : "IS NULL") . "
            ORDER BY c.created_at ASC";
    $res = mysqli_query($conn, $sql);
    $comments = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $is_owner = ($row['user_id'] == $current_user_id);
        $is_admin = ($user_role == 'super_admin' || $user_role == 'office_admin');
        $row['is_owner'] = $is_owner || $is_admin; // السماح للأدمن بحذف أي تعليق
        $row['replies'] = getCommentsWithReplies($post_id, $row['id'], $conn, $current_user_id, $user_role);
        $comments[] = $row;
    }
    return $comments;
}

$comments_tree = getCommentsWithReplies($post_id, null, $conn, $current_user_id, $user_role);

// الإعجابات
$likes = 0;
$likes_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM post_reactions WHERE post_id = $post_id AND reaction_type_id = 1");
if ($likes_res && $row = mysqli_fetch_assoc($likes_res)) $likes = $row['cnt'];

$user_liked = false;
$liked_res = mysqli_query($conn, "SELECT id FROM post_reactions WHERE user_id = $current_user_id AND post_id = $post_id AND reaction_type_id = 1");
if ($liked_res && mysqli_num_rows($liked_res) > 0) $user_liked = true;

$is_owner = ($post['user_id'] == $current_user_id);

// إرجاع البيانات
echo json_encode([
    'media' => $media,
    'content' => nl2br(htmlspecialchars($post['content'])),
    'likes' => $likes,
    'user_liked' => $user_liked,
    'is_owner' => $is_owner,
    'user_name' => $post['user_name'],
    'user_avatar' => $post['user_avatar'],
    'comments' => $comments_tree
]);
?>
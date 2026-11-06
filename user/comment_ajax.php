<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : 'add';

if ($action == 'add') {
    $post_id = intval($_POST['post_id']);
    $comment = trim($_POST['comment']);
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

    if (empty($comment)) {
        echo json_encode(['success' => false, 'message' => 'Comment is empty']);
        exit();
    }

    $comment_esc = mysqli_real_escape_string($conn, $comment);
    $parent_sql = $parent_id ? $parent_id : "NULL";

    $insert_comment = "INSERT INTO comments (user_id, content, parent_id, created_at, updated_at) 
                       VALUES ($user_id, '$comment_esc', $parent_sql, NOW(), NOW())";
    if (mysqli_query($conn, $insert_comment)) {
        $comment_id = mysqli_insert_id($conn);
        $insert_link = "INSERT INTO post_comments (comment_id, commentable_type, commentable_id, created_at) 
                        VALUES ($comment_id, 'post', $post_id, NOW())";
        mysqli_query($conn, $insert_link);

        $count_query = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM post_comments WHERE commentable_type = 'post' AND commentable_id = $post_id");
        $count = mysqli_fetch_assoc($count_query)['cnt'];

        echo json_encode(['success' => true, 'comment_count' => $count]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
    }
} elseif ($action == 'delete') {
    $comment_id = intval($_POST['comment_id']);
    // Check if user is the owner of the comment or admin (admin condition can be added later)
    $check = mysqli_query($conn, "SELECT user_id FROM comments WHERE id = $comment_id");
    if ($row = mysqli_fetch_assoc($check)) {
        if ($row['user_id'] == $user_id || $_SESSION['user_role'] == 'super_admin' || $_SESSION['user_role'] == 'office_admin') {
            // Delete the comment and all its replies (using parent_id or cascade)
            // Note: If you have a foreign key with ON DELETE CASCADE, you can just delete the parent comment.
            // We will delete the comment and all replies associated with it (or update parent_id to NULL)
            $delete_replies = mysqli_query($conn, "DELETE FROM comments WHERE parent_id = $comment_id");
            $delete_comment = mysqli_query($conn, "DELETE FROM comments WHERE id = $comment_id");
            if ($delete_comment) {
                // Also delete records from post_comments
                mysqli_query($conn, "DELETE FROM post_comments WHERE comment_id = $comment_id");
                mysqli_query($conn, "DELETE FROM post_comments WHERE comment_id IN (SELECT id FROM comments WHERE parent_id = $comment_id)"); // Indirect but better restructure
                // To recount the number of comments, we need the post_id. We'll include it in the delete request.
                $post_id = intval($_POST['post_id']);
                $count_query = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM post_comments WHERE commentable_type = 'post' AND commentable_id = $post_id");
                $count = mysqli_fetch_assoc($count_query)['cnt'];
                echo json_encode(['success' => true, 'comment_count' => $count]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'You do not have permission']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
    }
}
?>
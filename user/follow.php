<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$current_user_id = $_SESSION['user_id'];
$type = $_POST['type'];
$id = intval($_POST['id']);
$action = $_POST['action'];

if ($action == 'follow') {
    // Follow a user or office
    $check = mysqli_query($conn, "SELECT id FROM follows WHERE follower_id = $current_user_id AND followable_type = '$type' AND followable_id = $id");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO follows (follower_id, followable_type, followable_id, created_at) VALUES ($current_user_id, '$type', $id, NOW())");
        if ($type == 'user') {
            $user_name = $_SESSION['user_name'];
            mysqli_query($conn, "INSERT INTO notifications (user_id, type, title, message, is_read, created_at) 
                                 VALUES ($id, 'new_follower', 'New Follower', 'User $user_name started following you.', 0, NOW())");
        }
    }
} elseif ($action == 'unfollow') {
    // Unfollow a user or office (the one the current user follows)
    mysqli_query($conn, "DELETE FROM follows WHERE follower_id = $current_user_id AND followable_type = '$type' AND followable_id = $id");
} elseif ($action == 'remove_follower') {
    // Remove a follower (a user who follows the current user)
    // $id here is the follower's ID (the user to be removed from the current user's followers list)
    mysqli_query($conn, "DELETE FROM follows WHERE follower_id = $id AND followable_type = 'user' AND followable_id = $current_user_id");
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();
?>
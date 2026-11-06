<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$current_user_id = $_SESSION['user_id'];
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $current_user_id;
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'posts';

// Fetch user data
$user_query = "SELECT id, name, email, avatar FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
if (mysqli_num_rows($user_result) == 0) die("User not found.");
$user = mysqli_fetch_assoc($user_result);

// Follow stats
$followers_count = 0;
$followers_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM follows WHERE followable_type = 'user' AND followable_id = $user_id");
if ($row = mysqli_fetch_assoc($followers_res)) $followers_count = $row['cnt'];

$following_count = 0;
$following_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM follows WHERE follower_id = $user_id");
if ($row = mysqli_fetch_assoc($following_res)) $following_count = $row['cnt'];

// Check if current user follows this profile
$is_following = false;
if ($user_id != $current_user_id) {
    $follow_check = mysqli_query($conn, "SELECT id FROM follows WHERE follower_id = $current_user_id AND followable_type = 'user' AND followable_id = $user_id");
    $is_following = mysqli_num_rows($follow_check) > 0;
}

// ==================== NOTIFICATION COUNT FOR SIDEBAR BELL ====================
$unread_notifications = 0;
$notif_count_query = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = $current_user_id AND is_read = 0");
if ($notif_count_query && $row = mysqli_fetch_assoc($notif_count_query)) {
    $unread_notifications = $row['cnt'];
}

// Fetch posts based on tab
if ($tab == 'reels') {
    $posts_query = "SELECT * FROM posts WHERE user_id = $user_id AND type = 'reel' ORDER BY created_at DESC";
} else {
    $posts_query = "SELECT * FROM posts WHERE user_id = $user_id AND type = 'post' ORDER BY created_at DESC";
}
$posts_result = mysqli_query($conn, $posts_query);
if (!$posts_result) die("Error in posts query: " . mysqli_error($conn));
$posts = [];
while ($post = mysqli_fetch_assoc($posts_result)) $posts[] = $post;

// Like & comment counts
$post_ids = array_column($posts, 'id');
$like_counts = [];
$comment_counts = [];
if (!empty($post_ids)) {
    $ids_string = implode(',', $post_ids);
    $likes_query = mysqli_query($conn, "SELECT post_id, COUNT(*) as like_count FROM post_reactions WHERE post_id IN ($ids_string) AND reaction_type_id = 1 GROUP BY post_id");
    while ($row = mysqli_fetch_assoc($likes_query)) $like_counts[$row['post_id']] = $row['like_count'];
    $comments_count_query = mysqli_query($conn, "SELECT pc.commentable_id, COUNT(*) as comment_count FROM post_comments pc WHERE pc.commentable_type = 'post' AND pc.commentable_id IN ($ids_string) GROUP BY pc.commentable_id");
    while ($row = mysqli_fetch_assoc($comments_count_query)) $comment_counts[$row['commentable_id']] = $row['comment_count'];
}

// =================== ACTIVE STORIES (24h) ===================
$story_cutoff = date('Y-m-d H:i:s', strtotime('-24 hours'));
$stories_query = "SELECT * FROM posts WHERE user_id = $user_id AND type = 'story' AND created_at >= '$story_cutoff' ORDER BY created_at ASC";
$stories_result = mysqli_query($conn, $stories_query);
$active_stories = [];
while ($story = mysqli_fetch_assoc($stories_result)) {
    $active_stories[] = $story;
}
$has_story = count($active_stories) > 0;

// =================== Update name ===================
$name_error = $name_success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_name']) && $user_id == $current_user_id) {
    $new_name = trim($_POST['new_name']);
    if (!empty($new_name)) {
        $new_name = mysqli_real_escape_string($conn, $new_name);
        $update = "UPDATE users SET name = '$new_name' WHERE id = $current_user_id";
        if (mysqli_query($conn, $update)) {
            $_SESSION['user_name'] = $new_name;
            $name_success = "Name updated.";
            $user['name'] = $new_name;
        } else $name_error = "Update failed.";
    } else $name_error = "Name cannot be empty.";
}

// =================== Upload avatar ===================
$avatar_error = $avatar_success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['avatar']) && $user_id == $current_user_id) {
    $target_dir = "../uploads/avatars/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($ext, $allowed)) {
        $filename = time() . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_dir . $filename)) {
            $avatar_path = '../uploads/avatars/' . $filename;
            $update = "UPDATE users SET avatar = '$avatar_path' WHERE id = $current_user_id";
            if (mysqli_query($conn, $update)) {
                $avatar_success = "Avatar updated.";
                $user['avatar'] = $avatar_path;
            } else $avatar_error = "Database update failed.";
        } else $avatar_error = "File upload failed.";
    } else $avatar_error = "Invalid file format.";
}

// =================== Change password ===================
$pass_error = $pass_success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password']) && $user_id == $current_user_id) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    $pass_query = "SELECT password FROM users WHERE id = $current_user_id";
    $pass_res = mysqli_query($conn, $pass_query);
    $pass_row = mysqli_fetch_assoc($pass_res);
    if (password_verify($current_pass, $pass_row['password'])) {
        if (strlen($new_pass) >= 6 && $new_pass == $confirm_pass) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $update = "UPDATE users SET password = '$hashed' WHERE id = $current_user_id";
            if (mysqli_query($conn, $update)) $pass_success = "Password updated.";
            else $pass_error = "Update failed.";
        } else $pass_error = "New password must be at least 6 characters and match.";
    } else $pass_error = "Current password is incorrect.";
}

// =================== Add new post ===================
$post_error = $post_success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_post']) && $user_id == $current_user_id) {
    $content = trim($_POST['content']);
    $image_path = '';
    $video_path = '';
    $type = 'post';
    $upload_error = '';

    if (!empty($_FILES['post_media']['name'])) {
        $target_dir = "../uploads/posts/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $file = $_FILES['post_media'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_images = ['jpg', 'jpeg', 'png', 'gif'];
        $allowed_videos = ['mp4', 'mov', 'avi', 'webm', 'mkv'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $upload_error = "File upload error (code: " . $file['error'] . ")";
        } elseif (in_array($ext, $allowed_images)) {
            $filename = time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $target_dir . $filename)) {
                $image_path = 'uploads/posts/' . $filename;
                $type = 'post';
            } else $upload_error = "Failed to move image.";
        } elseif (in_array($ext, $allowed_videos)) {
            $filename = time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $target_dir . $filename)) {
                $video_path = 'uploads/posts/' . $filename;
                $type = 'reel';
            } else $upload_error = "Failed to move video.";
        } else {
            $upload_error = "Unsupported format (images: jpg,jpeg,png,gif / videos: mp4,mov,avi,webm,mkv)";
        }
    }

    if (empty($content) && empty($image_path) && empty($video_path)) {
        $post_error = "Please enter text or upload media.";
    } elseif (empty($upload_error)) {
        $content_esc = mysqli_real_escape_string($conn, $content);
        $image_esc = mysqli_real_escape_string($conn, $image_path);
        $video_esc = mysqli_real_escape_string($conn, $video_path);
        $type_esc = mysqli_real_escape_string($conn, $type);

        $insert = "INSERT INTO posts (user_id, content, image, video, type, created_at, updated_at)
                   VALUES ($current_user_id, '$content_esc', '$image_esc', '$video_esc', '$type_esc', NOW(), NOW())";

        if (mysqli_query($conn, $insert)) {
            $post_success = "Post published.";
            header("Location: profile.php?user_id=$user_id&tab=$tab&post_success=1");
            exit();
        } else {
            $post_error = "Failed to publish: " . mysqli_error($conn);
        }
    } else {
        $post_error = $upload_error;
    }
}

// =================== Add Story ===================
$story_error = $story_success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_story']) && $user_id == $current_user_id) {
    $content = trim($_POST['content'] ?? '');
    $image_path = '';
    $video_path = '';
    $type = 'story';
    $upload_error = '';

    if (!empty($_FILES['story_media']['name'])) {
        $target_dir = "../uploads/posts/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $file = $_FILES['story_media'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_images = ['jpg', 'jpeg', 'png', 'gif'];
        $allowed_videos = ['mp4', 'mov', 'avi', 'webm', 'mkv'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $upload_error = "File upload error (code: " . $file['error'] . ")";
        } elseif (in_array($ext, $allowed_images)) {
            $filename = time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $target_dir . $filename)) {
                $image_path = 'uploads/posts/' . $filename;
            } else $upload_error = "Failed to move image.";
        } elseif (in_array($ext, $allowed_videos)) {
            $filename = time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $target_dir . $filename)) {
                $video_path = 'uploads/posts/' . $filename;
            } else $upload_error = "Failed to move video.";
        } else {
            $upload_error = "Unsupported format (images: jpg,jpeg,png,gif / videos: mp4,mov,avi,webm,mkv)";
        }
    } else {
        $upload_error = "Please select a photo or video.";
    }

    if (empty($upload_error)) {
        $content_esc = mysqli_real_escape_string($conn, $content);
        $image_esc = mysqli_real_escape_string($conn, $image_path);
        $video_esc = mysqli_real_escape_string($conn, $video_path);
        $type_esc = 'story';

        $insert = "INSERT INTO posts (user_id, content, image, video, type, created_at, updated_at)
                   VALUES ($current_user_id, '$content_esc', '$image_esc', '$video_esc', '$type_esc', NOW(), NOW())";

        if (mysqli_query($conn, $insert)) {
            $story_success = "Story published.";
            header("Location: profile.php?user_id=$user_id&tab=$tab&story_success=1");
            exit();
        } else {
            $story_error = "Failed to publish story: " . mysqli_error($conn);
        }
    } else {
        $story_error = $upload_error;
    }
}

// =================== Delete post / story ===================
if (isset($_GET['delete_post']) && $user_id == $current_user_id) {
    $post_id = intval($_GET['delete_post']);
    $check = mysqli_query($conn, "SELECT id FROM posts WHERE id = $post_id AND user_id = $current_user_id");
    if (mysqli_num_rows($check) > 0) {
        if (mysqli_query($conn, "DELETE FROM posts WHERE id = $post_id")) {
            header("Location: profile.php?user_id=$user_id&tab=$tab&post_deleted=1");
            exit();
        } else die("Failed to delete post: " . mysqli_error($conn));
    } else die("Post not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['name']); ?> - Profile</title>
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
            color: #1b1c27;
            padding: 20px;
            min-height: 100vh;
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

        /* ==== الحاوية الرئيسية زجاجية ==== */
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(15px);
            border-radius: 40px;
            box-shadow: 0 30px 50px rgba(27, 28, 39, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            padding: 30px 35px;
            position: relative;
            z-index: 10;
        }

        /* ==== الأزرار والروابط ==== */
        .home-btn {
            display: inline-block;
            background: #65403a;
            color: white;
            border: none;
            border-radius: 40px;
            padding: 10px 22px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            margin-bottom: 20px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(101, 64, 58, 0.2);
        }

        .home-btn i { margin-right: 8px; }
        .home-btn:hover {
            background: #4f322c;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(101, 64, 58, 0.3);
        }

        .btn {
            background: #65403a;
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
            box-shadow: 0 4px 10px rgba(101, 64, 58, 0.15);
        }

        .btn:hover {
            background: #4f322c;
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(101, 64, 58, 0.25);
        }

        .btn-edit {
            background: transparent;
            border: 1.5px solid #9ec1d7;
            color: #1b1c27;
            border-radius: 30px;
            padding: 5px 14px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.3s;
            margin-left: 8px;
        }

        .btn-edit:hover {
            background: #9ec1d7;
            color: #1b1c27;
            border-color: #9ec1d7;
        }

        .btn-follow {
            background: #65403a;
            color: white;
            border: none;
            border-radius: 30px;
            padding: 8px 24px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-follow:hover {
            background: #4f322c;
            transform: translateY(-2px);
        }

        .btn-unfollow {
            background: transparent;
            border: 1.5px solid #65403a;
            color: #65403a;
            border-radius: 30px;
            padding: 8px 24px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-unfollow:hover {
            background: #65403a;
            color: white;
        }

        /* ==== شريط جانبي ==== */
        .sidebar {
            background: linear-gradient(145deg, #65403a, #4f322c);
            border-radius: 50px;
            padding: 18px 25px;
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-around;
            box-shadow: 0 15px 30px rgba(101, 64, 58, 0.2);
        }

        .sidebar a {
            text-decoration: none;
            color: #eff1ee;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 40px;
            transition: 0.3s;
            position: relative;
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-3px);
        }

        .notification-badge {
            background-color: #ed4956;
            color: white;
            border-radius: 50%;
            padding: 3px 7px;
            font-size: 11px;
            font-weight: bold;
            min-width: 20px;
            text-align: center;
            margin-left: 8px;
        }

        @keyframes shake {
            0% { transform: translate(1px, 1px) rotate(0deg); }
            10% { transform: translate(-1px, -2px) rotate(-1deg); }
            20% { transform: translate(-3px, 0px) rotate(1deg); }
            30% { transform: translate(3px, 2px) rotate(0deg); }
            40% { transform: translate(1px, -1px) rotate(1deg); }
            50% { transform: translate(-1px, 2px) rotate(-1deg); }
            60% { transform: translate(-3px, 1px) rotate(0deg); }
            70% { transform: translate(3px, 1px) rotate(-1deg); }
            80% { transform: translate(-1px, -1px) rotate(1deg); }
            90% { transform: translate(1px, 2px) rotate(0deg); }
            100% { transform: translate(1px, -2px) rotate(-1deg); }
        }

        .bell-shake {
            animation: shake 0.5s ease-in-out infinite;
            display: inline-block;
        }

        /* ==== AVATAR & STORY RING ==== */
        .avatar-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .avatar-wrapper {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            padding: 3px;
            display: inline-block;
            border: 3px solid transparent;
            cursor: default;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .avatar-wrapper.has-story {
            border-color: #4A90E2;       /* blue ring */
            box-shadow: 0 0 15px rgba(74,144,226,0.4);
            cursor: pointer;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(74,144,226,0.5); }
            70% { box-shadow: 0 0 0 15px rgba(74,144,226,0); }
            100% { box-shadow: 0 0 0 0 rgba(74,144,226,0); }
        }

        .avatar-wrapper .avatar,
        .avatar-wrapper .avatar-placeholder {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: none;
            object-fit: cover;
            display: block;
        }

        .avatar-placeholder {
            background: #65403a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
        }

        .edit-avatar, .add-story-btn {
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            color: #65403a;
            text-align: center;
            transition: 0.2s;
        }

        .edit-avatar:hover, .add-story-btn:hover {
            color: #4f322c;
            text-decoration: underline;
        }

        /* ==== رأس البروفايل ==== */
        .profile-header {
            display: flex;
            gap: 25px;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .user-info { flex: 1; }
        .user-name {
            font-size: 26px;
            font-weight: 700;
            color: #1b1c27;
            margin-bottom: 5px;
        }

        .user-email { color: #5a6a7a; margin-bottom: 10px; }
        .stats { display: flex; gap: 25px; margin-top: 12px; }
        .stat { text-align: center; cursor: pointer; }
        .stat-number { font-weight: 700; font-size: 20px; color: #65403a; }
        .stat-label { font-size: 14px; color: #5a6a7a; }

        /* ==== التبويبات ==== */
        .tabs {
            display: flex;
            justify-content: center;
            gap: 40px;
            border-top: 1px solid rgba(158, 193, 215, 0.4);
            margin-top: 25px;
        }

        .tab {
            padding: 18px 0;
            text-decoration: none;
            color: #5a6a7a;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }

        .tab.active {
            color: #65403a;
            border-top: 3px solid #65403a;
            margin-top: -2px;
        }

        .tab:hover { color: #65403a; }

        /* ==== شبكة المنشورات ==== */
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 25px;
        }

        .post-item {
            aspect-ratio: 1 / 1;
            position: relative;
            cursor: pointer;
            overflow: hidden;
            border-radius: 20px;
            background: #f0f0f0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: 0.3s;
        }

        .post-item:hover { transform: scale(1.02); }
        .post-item img, .post-item video { width: 100%; height: 100%; object-fit: cover; }

        .post-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(27, 28, 39, 0.5);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 25px;
            opacity: 0;
            transition: 0.3s;
            color: white;
        }

        .post-item:hover .post-overlay { opacity: 1; }
        .post-overlay span { font-size: 18px; font-weight: 600; }

        /* ==== إضافة منشور ==== */
        .add-post {
            background: rgba(239, 241, 238, 0.6);
            backdrop-filter: blur(8px);
            border-radius: 30px;
            padding: 20px 25px;
            margin: 20px 0;
            border: 1px solid rgba(158, 193, 215, 0.3);
        }

        .add-post textarea {
            width: 100%;
            border: 1.5px solid rgba(158, 193, 215, 0.4);
            border-radius: 25px;
            padding: 12px 18px;
            background: white;
            resize: vertical;
            font-family: inherit;
        }

        .add-post input[type="file"] { margin-top: 12px; }

        /* ==== المودالات ==== */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(27, 28, 39, 0.8);
            backdrop-filter: blur(8px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            width: 90%;
            max-width: 950px;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 40px;
            position: relative;
            padding: 25px 30px;
            box-shadow: 0 40px 60px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            cursor: pointer;
            color: #65403a;
            background: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            z-index: 10;
        }

        .modal-body { display: flex; flex-wrap: wrap; }
        .modal-media {
            flex: 1.2;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 350px;
            border-radius: 30px;
            overflow: hidden;
        }
        .modal-details { flex: 1; padding: 0 25px; display: flex; flex-direction: column; gap: 15px; }
        .modal-user { display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #ddd; padding-bottom: 12px; }
        .modal-user-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #9ec1d7; }
        .modal-user-name { font-weight: 700; color: #1b1c27; }
        .modal-actions { display: flex; align-items: center; gap: 20px; }
        .like-button { background: none; border: none; font-size: 28px; cursor: pointer; color: #5a6a7a; transition: 0.2s; }
        .like-button.liked { color: #ed4956; }
        .delete-button { background: none; border: none; font-size: 22px; cursor: pointer; color: #ed4956; margin-left: auto; }
        .modal-likes { font-weight: 600; color: #1b1c27; }
        .modal-comments { max-height: 350px; overflow-y: auto; border-top: 1px solid #ddd; border-bottom: 1px solid #ddd; padding: 15px 0; }
        .modal-comment { background: #f0f2f5; padding: 8px 15px; border-radius: 25px; display: inline-block; max-width: 100%; margin-bottom: 8px; }
        .modal-reply-btn, .modal-delete-btn {
            background: none; border: none; color: #65403a; font-size: 12px;
            cursor: pointer; margin-left: 10px; font-weight: 500;
        }
        .modal-delete-btn { color: #ed4956; }
        .modal-form { display: flex; gap: 12px; margin-top: 15px; }
        .modal-form input {
            flex: 1; border: 1.5px solid rgba(158,193,215,0.5);
            border-radius: 40px; padding: 12px 18px; outline: none;
            background: white; font-family: inherit;
        }
        .modal-form button {
            background: #65403a; border: none; color: white; font-weight: 600;
            border-radius: 40px; padding: 0 25px; cursor: pointer; transition: 0.3s;
        }
        .modal-form button:hover { background: #4f322c; }

        /* ==== STORY VIEWER (FULLSCREEN) ==== */
        #storyModal {
            background: rgba(0,0,0,0.95);
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100vh;
            z-index: 2000;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        #storyMediaContainer {
            max-width: 90%;
            max-height: 80vh;
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        #storyMediaContainer img,
        #storyMediaContainer video {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 20px;
        }

        .story-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 28px;
            color: white;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .story-nav:hover {
            background: rgba(255,255,255,0.5);
            transform: translateY(-50%) scale(1.1);
        }

        #prevStory { left: 25px; }
        #nextStory { right: 25px; }

        #storyUserInfo {
            position: absolute;
            top: 25px;
            left: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            font-weight: 600;
            z-index: 10;
        }

        #storyUserAvatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid white;
        }

        /* ==== STORY CONTROLS OVERLAY ==== */
        .story-controls {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 20px;
            z-index: 20;
        }

        .story-control-btn {
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(10px);
            border: none;
            color: white;
            font-size: 22px;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .story-control-btn:hover {
            background: rgba(0,0,0,0.75);
            transform: scale(1.1);
        }

        /* زر الحذف في الزاوية العليا */
        #deleteStoryBtn {
            top: 25px;
            right: 80px;
            width: 35px;
            height: 35px;
            font-size: 18px;
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: none;
            justify-content: center;
            align-items: center;
            transition: 0.3s;
            z-index: 10;
            position: absolute;
        }

        #deleteStoryBtn:hover {
            background: rgba(220,53,69,0.8);
        }

        .message {
            padding: 12px 20px; border-radius: 40px; margin-bottom: 20px;
            backdrop-filter: blur(8px);
        }
        .success { background: rgba(40,167,69,0.1); color: #155724; border-left: 4px solid #28a745; }
        .error { background: rgba(220,53,69,0.1); color: #b91c1c; border-left: 4px solid #b91c1c; }

        @media (max-width: 768px) {
            .container { padding: 20px 18px; }
            .profile-header { flex-direction: column; text-align: center; }
            .user-info { text-align: center; }
            .stats { justify-content: center; }
            .sidebar a { font-size: 13px; padding: 6px 12px; }
            .modal-body { flex-direction: column; }
            .modal-media { min-height: 250px; }
            .posts-grid { gap: 6px; }
            .story-nav { width: 40px; height: 40px; font-size: 22px; }
            #prevStory { left: 10px; }
            #nextStory { right: 10px; }
            #deleteStoryBtn { top: 20px; right: 65px; width: 30px; height: 30px; font-size: 16px; }
        }
    </style>
</head>
<body>
<!-- عناصر الخلفية الزخرفية -->
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<div class="container">
    <a href="../index.php" class="home-btn"><i class="fas fa-home"></i> Home</a>

    <div class="profile-header">
        <div class="avatar-container">
            <div class="avatar-wrapper <?php echo $has_story ? 'has-story' : ''; ?>"
                <?php if ($has_story) echo 'onclick="openStoryModal(0)"'; ?>>
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?php echo $user['avatar']; ?>" class="avatar" alt="Profile picture">
                <?php else: ?>
                    <div class="avatar-placeholder"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                <?php endif; ?>
            </div>
            <?php if ($user_id == $current_user_id): ?>
                <div class="edit-avatar" onclick="openModal('avatarModal')"><i class="fas fa-camera"></i> Change</div>
                <div class="add-story-btn" onclick="openModal('storyModalUpload')"><i class="fas fa-plus-circle"></i> Add Story</div>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <div class="user-name">
                <?php echo htmlspecialchars($user['name']); ?>
                <?php if ($user_id == $current_user_id): ?>
                    <button class="btn-edit" onclick="openModal('nameModal')"><i class="fas fa-edit"></i> Edit</button>
                <?php endif; ?>
            </div>
            <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
            <div class="stats">
                <div class="stat" onclick="location.href='following.php?user_id=<?php echo $user_id; ?>'">
                    <div class="stat-number"><?php echo $following_count; ?></div>
                    <div class="stat-label">Following</div>
                </div>
                <div class="stat" onclick="location.href='followers.php?user_id=<?php echo $user_id; ?>'">
                    <div class="stat-number"><?php echo $followers_count; ?></div>
                    <div class="stat-label">Followers</div>
                </div>
            </div>
            <?php if ($user_id != $current_user_id): ?>
                <form method="post" action="follow.php" style="display:inline;">
                    <input type="hidden" name="type" value="user">
                    <input type="hidden" name="id" value="<?php echo $user_id; ?>">
                    <input type="hidden" name="action" value="<?php echo $is_following ? 'unfollow' : 'follow'; ?>">
                    <button type="submit" class="<?php echo $is_following ? 'btn-unfollow' : 'btn-follow'; ?>">
                        <?php echo $is_following ? 'Unfollow' : 'Follow'; ?>
                    </button>
                </form>
            <?php endif; ?>
            <?php if ($user_id == $current_user_id): ?>
                <button class="btn-edit" onclick="openModal('passwordModal')"><i class="fas fa-key"></i> Change Password</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <a href="my_ratings.php"><i class="fas fa-star"></i> My Ratings</a>
        <a href="my_comments.php"><i class="fas fa-envelope"></i> My Comments</a>
        <a href="my_bookings.php"><i class="fas fa-ticket-alt"></i> My Bookings</a>
        <a href="notifications.php" class="notification-link">
            <i class="fas fa-bell <?php echo $unread_notifications > 0 ? 'bell-shake' : ''; ?>"></i>
            Notifications
            <?php if ($unread_notifications > 0): ?>
                <span class="notification-badge"><?php echo $unread_notifications; ?></span>
            <?php endif; ?>
        </a>
    </div>

    <div class="tabs">
        <a href="profile.php?user_id=<?php echo $user_id; ?>&tab=posts" class="tab <?php if ($tab == 'posts') echo 'active'; ?>">
            <i class="fas fa-th-large"></i> Posts
        </a>
        <a href="profile.php?user_id=<?php echo $user_id; ?>&tab=reels" class="tab <?php if ($tab == 'reels') echo 'active'; ?>">
            <i class="fas fa-video"></i> Reels
        </a>
    </div>

    <?php if (isset($_GET['post_success'])): ?>
        <div class="message success"><i class="fas fa-check-circle mr-2"></i>Post published successfully</div>
    <?php endif; ?>
    <?php if (isset($_GET['post_deleted'])): ?>
        <div class="message success"><i class="fas fa-check-circle mr-2"></i>Post deleted</div>
    <?php endif; ?>
    <?php if (isset($_GET['story_success'])): ?>
        <div class="message success"><i class="fas fa-check-circle mr-2"></i>Story published</div>
    <?php endif; ?>

    <?php if ($user_id == $current_user_id): ?>
        <div class="add-post">
            <h4 style="margin-bottom: 15px; color: #1b1c27;"><i class="fas fa-plus-circle mr-2"></i>Create new post</h4>
            <form method="post" enctype="multipart/form-data" action="">
                <textarea name="content" rows="2" placeholder="Write something..."></textarea>
                <input type="file" name="post_media" accept="image/*,video/*">
                <button type="submit" name="add_post" class="btn" style="margin-top:15px;"><i class="fas fa-paper-plane mr-2"></i>Publish</button>
                <?php if (isset($post_error) && $post_error): ?>
                    <div class="message error" style="margin-top:15px;"><?php echo $post_error; ?></div>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>

    <?php if (empty($posts)): ?>
        <div style="text-align: center; padding: 40px; color: #5a6a7a;">
            <i class="fas fa-camera" style="font-size: 50px; margin-bottom: 15px; opacity: 0.6;"></i>
            <p>No <?php echo $tab == 'posts' ? 'posts' : 'reels'; ?> yet.</p>
        </div>
    <?php else: ?>
        <div class="posts-grid">
            <?php foreach ($posts as $post): ?>
                <div class="post-item" onclick="openPostModal(<?php echo $post['id']; ?>)">
                    <?php if (!empty($post['image'])): ?>
                        <img src="../<?php echo $post['image']; ?>" alt="Post">
                    <?php elseif (!empty($post['video'])): ?>
                        <video src="../<?php echo $post['video']; ?>" muted></video>
                    <?php endif; ?>
                    <div class="post-overlay">
                        <span><i class="fas fa-heart"></i> <?php echo isset($like_counts[$post['id']]) ? $like_counts[$post['id']] : 0; ?></span>
                        <span><i class="fas fa-comment"></i> <?php echo isset($comment_counts[$post['id']]) ? $comment_counts[$post['id']] : 0; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Post Modal -->
<div id="postModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closePostModal()">&times;</span>
        <div class="modal-body">
            <div class="modal-media" id="modalMedia"></div>
            <div class="modal-details">
                <div class="modal-user">
                    <img id="modalUserAvatar" class="modal-user-avatar" src="../img/default-avatar.png">
                    <span id="modalUserName" class="modal-user-name"></span>
                </div>
                <div id="modalContent"></div>
                <div class="modal-actions">
                    <button class="like-button" id="modalLikeBtn"><i class="fas fa-heart"></i></button>
                    <button class="delete-button" id="modalDeleteBtn" style="display: none;"><i class="fas fa-trash-alt"></i></button>
                </div>
                <div class="modal-likes" id="modalLikes"></div>
                <div class="modal-comments" id="modalComments"></div>
                <form class="modal-form" id="modalCommentForm">
                    <input type="hidden" name="post_id" id="modalPostId">
                    <input type="text" name="comment" placeholder="Add a comment..." required>
                    <button type="submit">Post</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Story Modal (fullscreen) -->
<div id="storyModal">
    <span class="close" style="top: 20px; right: 30px; color: white; background: rgba(255,255,255,0.2);" onclick="closeStoryModal()">&times;</span>
    <button id="deleteStoryBtn" onclick="deleteCurrentStory()">
        <i class="fas fa-trash-alt"></i>
    </button>
    <div id="storyUserInfo">
        <img id="storyUserAvatar" src="<?php echo $user['avatar'] ?: '../img/default-avatar.png'; ?>">
        <span id="storyUserName"><?php echo htmlspecialchars($user['name']); ?></span>
    </div>
    <div id="storyMediaContainer">
        <!-- dynamic media -->
    </div>
    <div class="story-controls" id="storyControls" style="display: none;">
        <button class="story-control-btn" id="storyPlayPauseBtn" onclick="togglePlayPause()">
            <i class="fas fa-pause"></i>
        </button>
        <button class="story-control-btn" id="storyVolumeBtn" onclick="toggleMute()">
            <i class="fas fa-volume-mute"></i>
        </button>
    </div>
    <button id="prevStory" class="story-nav" onclick="prevStory()">&#10094;</button>
    <button id="nextStory" class="story-nav" onclick="nextStory()">&#10095;</button>
</div>

<!-- Name, Avatar, Password Modals -->
<div id="nameModal" class="modal"><div class="modal-content" style="max-width:450px;"><span class="close" onclick="closeModal('nameModal')">&times;</span><h3 style="color:#65403a;">Edit Name</h3><form method="post"><input type="text" name="new_name" value="<?php echo htmlspecialchars($user['name']); ?>" style="width:100%; padding:12px 18px; border-radius:40px; border:1.5px solid #9ec1d7; margin:15px 0;"><button type="submit" name="update_name" class="btn">Save</button><?php if (isset($name_error) && $name_error): ?><div class="message error"><?php echo $name_error; ?></div><?php endif; ?><?php if (isset($name_success) && $name_success): ?><div class="message success"><?php echo $name_success; ?></div><?php endif; ?></form></div></div>
<div id="avatarModal" class="modal"><div class="modal-content" style="max-width:450px;"><span class="close" onclick="closeModal('avatarModal')">&times;</span><h3 style="color:#65403a;">Change Profile Picture</h3><form method="post" enctype="multipart/form-data"><input type="file" name="avatar" accept="image/*" required><button type="submit" class="btn" style="margin-top:15px;">Upload</button><?php if (isset($avatar_error) && $avatar_error): ?><div class="message error"><?php echo $avatar_error; ?></div><?php endif; ?><?php if (isset($avatar_success) && $avatar_success): ?><div class="message success"><?php echo $avatar_success; ?></div><?php endif; ?></form></div></div>
<div id="passwordModal" class="modal"><div class="modal-content" style="max-width:450px;"><span class="close" onclick="closeModal('passwordModal')">&times;</span><h3 style="color:#65403a;">Change Password</h3><form method="post"><input type="password" name="current_password" placeholder="Current Password" style="width:100%; padding:12px 18px; border-radius:40px; border:1.5px solid #9ec1d7; margin:10px 0;"><input type="password" name="new_password" placeholder="New Password" style="width:100%; padding:12px 18px; border-radius:40px; border:1.5px solid #9ec1d7; margin:10px 0;"><input type="password" name="confirm_password" placeholder="Confirm New Password" style="width:100%; padding:12px 18px; border-radius:40px; border:1.5px solid #9ec1d7; margin:10px 0;"><button type="submit" name="update_password" class="btn">Update</button><?php if (isset($pass_error) && $pass_error): ?><div class="message error"><?php echo $pass_error; ?></div><?php endif; ?><?php if (isset($pass_success) && $pass_success): ?><div class="message success"><?php echo $pass_success; ?></div><?php endif; ?></form></div></div>

<!-- Story Upload Modal -->
<div id="storyModalUpload" class="modal">
    <div class="modal-content" style="max-width:450px;">
        <span class="close" onclick="closeModal('storyModalUpload')">&times;</span>
        <h3 style="color:#65403a;">Add Story</h3>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="add_story" value="1">
           
            <input type="file" name="story_media" accept="image/*,video/*" required>
            <button type="submit" class="btn" style="margin-top:15px;">Share Story</button>
            <?php if (isset($story_error) && $story_error): ?><div class="message error" style="margin-top:10px;"><?php echo $story_error; ?></div><?php endif; ?>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // ======== STORY DATA ========
    const activeStories = <?php echo json_encode($active_stories); ?>;
    const isOwnProfile = <?php echo json_encode($user_id == $current_user_id); ?>; // هل هذا حسابي أنا؟
    let currentStoryIndex = -1;

    function openStoryModal(index) {
        if (activeStories.length === 0) return;
        currentStoryIndex = index;
        document.getElementById('storyModal').style.display = 'flex';
        showStory(currentStoryIndex);
        updateStoryNavButtons();
    }

    function closeStoryModal() {
        document.getElementById('storyModal').style.display = 'none';
        const video = document.getElementById('storyVideo');
        if (video) video.pause();
    }

    function showStory(index) {
        if (index < 0 || index >= activeStories.length) return;
        const story = activeStories[index];
        const container = document.getElementById('storyMediaContainer');
        let mediaHTML = '';
        if (story.video) {
            mediaHTML = `<video id="storyVideo" src="../${story.video}" autoplay loop muted playsinline style="max-width:100%; max-height:80vh; border-radius:20px;"></video>`;
        } else if (story.image) {
            mediaHTML = `<img src="../${story.image}" alt="Story" style="max-width:100%; max-height:80vh; border-radius:20px;">`;
        } else {
            mediaHTML = `<div style="color:white; padding:40px;">${escapeHtml(story.content || '')}</div>`;
        }
        container.innerHTML = mediaHTML;

        // أزرار التحكم بالفيديو
        const video = document.getElementById('storyVideo');
        const playPauseBtn = document.getElementById('storyPlayPauseBtn');
        const volumeBtn = document.getElementById('storyVolumeBtn');
        const controlsDiv = document.getElementById('storyControls');
        const deleteBtn = document.getElementById('deleteStoryBtn');

        // إظهار زر الحذف فقط إذا كان هذا حسابي ولدي ستوري
        if (isOwnProfile && activeStories.length > 0) {
            deleteBtn.style.display = 'flex';
        } else {
            deleteBtn.style.display = 'none';
        }

        if (video) {
            controlsDiv.style.display = 'flex';
            updateVolumeIcon(video.muted);
            video.onclick = togglePlayPause;
            video.onplay = () => playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
            video.onpause = () => playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
            video.onvolumechange = () => updateVolumeIcon(video.muted);
        } else {
            controlsDiv.style.display = 'none';
        }

        document.getElementById('storyUserAvatar').src = '<?php echo $user['avatar'] ?: "../img/default-avatar.png"; ?>';
        document.getElementById('storyUserName').innerText = '<?php echo htmlspecialchars($user['name']); ?>';
    }

    function updateStoryNavButtons() {
        const prevBtn = document.getElementById('prevStory');
        const nextBtn = document.getElementById('nextStory');
        if (activeStories.length <= 1) {
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';
        } else {
            prevBtn.style.display = (currentStoryIndex > 0) ? 'flex' : 'none';
            nextBtn.style.display = (currentStoryIndex < activeStories.length - 1) ? 'flex' : 'none';
        }
    }

    function prevStory() {
        if (currentStoryIndex > 0) {
            currentStoryIndex--;
            showStory(currentStoryIndex);
            updateStoryNavButtons();
        }
    }

    function nextStory() {
        if (currentStoryIndex < activeStories.length - 1) {
            currentStoryIndex++;
            showStory(currentStoryIndex);
            updateStoryNavButtons();
        } else {
            closeStoryModal();
        }
    }

    function togglePlayPause() {
        const video = document.getElementById('storyVideo');
        if (!video) return;
        if (video.paused) {
            video.play();
        } else {
            video.pause();
        }
    }

    function toggleMute() {
        const video = document.getElementById('storyVideo');
        if (!video) return;
        video.muted = !video.muted;
        updateVolumeIcon(video.muted);
    }

    function updateVolumeIcon(muted) {
        const icon = document.querySelector('#storyVolumeBtn i');
        if (muted) {
            icon.className = 'fas fa-volume-mute';
        } else {
            icon.className = 'fas fa-volume-up';
        }
    }

    function deleteCurrentStory() {
        if (!isOwnProfile || activeStories.length === 0 || currentStoryIndex < 0) return;
        if (confirm('Are you sure you want to delete this story?')) {
            const storyId = activeStories[currentStoryIndex].id;
            // إعادة توجيه لحذف الستوري (نفس آلية حذف المنشورات)
            window.location.href = `profile.php?delete_post=${storyId}&user_id=<?php echo $user_id; ?>&tab=<?php echo $tab; ?>`;
        }
    }

    document.getElementById('storyModal').addEventListener('click', function(e) {
        if (e.target === this) closeStoryModal();
    });

    // ======== POST MODAL (بدون تغيير) ========
    let currentModalPostId = null;

    function renderModalComments(comments, postId, depth = 0) {
        let html = '';
        comments.forEach(comment => {
            let margin = depth * 30;
            html += `<div style="margin-left: ${margin}px; margin-bottom: 8px;">
                        <div class="modal-comment" data-comment-id="${comment.id}">
                            <strong>${escapeHtml(comment.user_name)}</strong> ${escapeHtml(comment.content)}
                            <button class="modal-reply-btn" data-post-id="${postId}" data-comment-id="${comment.id}">Reply</button>`;
            if (comment.is_owner || <?php echo json_encode($_SESSION['user_role'] == 'super_admin' || $_SESSION['user_role'] == 'office_admin'); ?>) {
                html += `<button class="modal-delete-btn" data-post-id="${postId}" data-comment-id="${comment.id}">Delete</button>`;
            }
            html += `<div class="modal-reply-container" data-comment-id="${comment.id}" style="margin-top: 5px;"></div>
                        </div>`;
            if (comment.replies && comment.replies.length > 0) {
                html += renderModalComments(comment.replies, postId, depth + 1);
            }
            html += `</div>`;
        });
        return html;
    }

    function openPostModal(postId) {
        currentModalPostId = postId;
        fetch(`get_post.php?id=${postId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('modalMedia').innerHTML = data.media;
                document.getElementById('modalContent').innerHTML = data.content;
                document.getElementById('modalLikes').innerHTML = `<i class="fas fa-heart"></i> ${data.likes}`;
                document.getElementById('modalPostId').value = postId;
                document.getElementById('modalUserAvatar').src = data.user_avatar;
                document.getElementById('modalUserName').innerText = data.user_name;
                const likeBtn = document.getElementById('modalLikeBtn');
                if (data.user_liked) likeBtn.classList.add('liked'); else likeBtn.classList.remove('liked');
                const deleteBtn = document.getElementById('modalDeleteBtn');
                if (data.is_owner) deleteBtn.style.display = 'inline-block'; else deleteBtn.style.display = 'none';
                let commentsHtml = renderModalComments(data.comments, postId, 0);
                document.getElementById('modalComments').innerHTML = commentsHtml;
                document.getElementById('postModal').style.display = 'flex';
                attachModalReplyEvents();
                attachModalDeleteEvents();
            })
            .catch(error => console.error('Error:', error));
    }

    function attachModalReplyEvents() {
        document.querySelectorAll('.modal-reply-btn').forEach(btn => {
            btn.removeEventListener('click', modalReplyHandler);
            btn.addEventListener('click', modalReplyHandler);
        });
    }
    function attachModalDeleteEvents() {
        document.querySelectorAll('.modal-delete-btn').forEach(btn => {
            btn.removeEventListener('click', modalDeleteHandler);
            btn.addEventListener('click', modalDeleteHandler);
        });
    }

    function modalReplyHandler(e) {
        e.stopPropagation();
        const postId = this.dataset.postId;
        const parentCommentId = this.dataset.commentId;
        const container = document.querySelector(`.modal-reply-container[data-comment-id="${parentCommentId}"]`);
        if (container.innerHTML.trim() !== '') {
            container.innerHTML = '';
            return;
        }
        const formHtml = `<form class="reply-form" data-post-id="${postId}" data-parent-id="${parentCommentId}" style="display:flex; gap:5px; margin-top:5px;">
                            <input type="text" placeholder="Write a reply..." style="flex:1; padding:5px 8px; border-radius:20px; border:1px solid #dbdbdb;">
                            <button type="submit" style="background:none; border:none; color:#65403a;">Reply</button>
                          </form>`;
        container.innerHTML = formHtml;
        const form = container.querySelector('.reply-form');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const input = this.querySelector('input');
            const replyText = input.value.trim();
            if (!replyText) return;
            const postId = this.dataset.postId;
            const parentId = this.dataset.parentId;
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('post_id', postId);
            formData.append('comment', replyText);
            formData.append('parent_id', parentId);
            fetch('comment_ajax.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        openPostModal(currentModalPostId);
                        updateCommentCountOnGrid(postId, data.comment_count);
                    } else alert('Failed to add reply');
                });
        });
    }

    function modalDeleteHandler(e) {
        e.stopPropagation();
        if (!confirm('Are you sure you want to delete this comment?')) return;
        const commentId = this.dataset.commentId;
        const postId = this.dataset.postId;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('comment_id', commentId);
        formData.append('post_id', postId);
        fetch('comment_ajax.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    openPostModal(currentModalPostId);
                    updateCommentCountOnGrid(postId, data.comment_count);
                } else alert(data.message || 'Failed to delete');
            });
    }

    function updateCommentCountOnGrid(postId, newCount) {
        const gridItem = document.querySelector(`.post-item[onclick="openPostModal(${postId})"] .post-overlay span:last-child`);
        if (gridItem) gridItem.innerHTML = `<i class="fas fa-comment"></i> ${newCount}`;
    }

    function closePostModal() {
        document.getElementById('postModal').style.display = 'none';
    }

    function toggleLike() {
        fetch(`like_ajax.php?post_id=${currentModalPostId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const likeBtn = document.getElementById('modalLikeBtn');
                    likeBtn.classList.toggle('liked', data.user_liked);
                    document.getElementById('modalLikes').innerHTML = `<i class="fas fa-heart"></i> ${data.likes}`;
                    const gridItem = document.querySelector(`.post-item[onclick="openPostModal(${currentModalPostId})"] .post-overlay span:first-child`);
                    if (gridItem) gridItem.innerHTML = `<i class="fas fa-heart"></i> ${data.likes}`;
                }
            });
    }

    function deletePost() {
        if (confirm('Are you sure you want to delete this post?')) {
            window.location.href = `profile.php?delete_post=${currentModalPostId}&user_id=<?php echo $user_id; ?>&tab=<?php echo $tab; ?>`;
        }
    }

    document.getElementById('modalLikeBtn').addEventListener('click', toggleLike);
    document.getElementById('modalDeleteBtn').addEventListener('click', deletePost);
    document.getElementById('modalCommentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'add');
        fetch('comment_ajax.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    openPostModal(currentModalPostId);
                    updateCommentCountOnGrid(currentModalPostId, data.comment_count);
                } else alert('Failed to add comment');
            });
        this.reset();
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal') && event.target.id === 'postModal') {
            closePostModal();
        }
    }
</script>
</body>
</html>
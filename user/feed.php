<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$current_user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';

// جلب صورة المستخدم الحالي
$avatar_query = mysqli_query($conn, "SELECT avatar FROM users WHERE id = $current_user_id");
$current_user_avatar = ($avatar_row = mysqli_fetch_assoc($avatar_query)) ? $avatar_row['avatar'] : '';

// ==================== جلب القصص النشطة (آخر 24 ساعة) ====================
$story_cutoff = date('Y-m-d H:i:s', strtotime('-24 hours'));
$stories_query = "SELECT p.*, u.name AS user_name, u.avatar AS user_avatar
                  FROM posts p
                  JOIN users u ON p.user_id = u.id
                  WHERE p.type = 'story'
                    AND p.created_at >= '$story_cutoff'
                    AND (p.user_id = $current_user_id
                         OR p.user_id IN (SELECT followable_id FROM follows WHERE follower_id = $current_user_id AND followable_type = 'user'))
                  ORDER BY p.created_at ASC";
$stories_result = mysqli_query($conn, $stories_query);

// تجميع القصص حسب المستخدم
$story_users = [];
while ($story = mysqli_fetch_assoc($stories_result)) {
    $uid = $story['user_id'];
    if (!isset($story_users[$uid])) {
        $story_users[$uid] = [
            'user_id' => $uid,
            'user_name' => $story['user_name'],
            'user_avatar' => $story['user_avatar'],
            'stories' => []
        ];
    }
    $story_users[$uid]['stories'][] = $story;
}
$has_stories = count($story_users) > 0;

// جلب المنشورات
$posts_query = "SELECT p.*, u.name AS user_name, u.avatar AS user_avatar
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.type IN ('post', 'reel')
                ORDER BY p.created_at DESC";
$posts_result = mysqli_query($conn, $posts_query);
$posts = [];
while ($post = mysqli_fetch_assoc($posts_result)) $posts[] = $post;

$post_ids = array_column($posts, 'id');
$like_counts = [];
$comment_counts = [];
if (!empty($post_ids)) {
    $ids_string = implode(',', $post_ids);
    $likes_query = mysqli_query($conn, "SELECT post_id, COUNT(*) as like_count FROM post_reactions WHERE post_id IN ($ids_string) AND reaction_type_id = 1 GROUP BY post_id");
    while ($row = mysqli_fetch_assoc($likes_query)) $like_counts[$row['post_id']] = $row['like_count'];
    
    $comments_count_query = mysqli_query($conn, "SELECT commentable_id, COUNT(*) as comment_count FROM post_comments WHERE commentable_type='post' AND commentable_id IN ($ids_string) GROUP BY commentable_id");
    while ($row = mysqli_fetch_assoc($comments_count_query)) $comment_counts[$row['commentable_id']] = $row['comment_count'];
}

$user_ids = array_unique(array_column($posts, 'user_id'));
$follow_status = [];
if (!empty($user_ids)) {
    $ids_string = implode(',', $user_ids);
    $follow_query = mysqli_query($conn, "SELECT followable_id FROM follows WHERE follower_id = $current_user_id AND followable_type = 'user' AND followable_id IN ($ids_string)");
    while ($row = mysqli_fetch_assoc($follow_query)) $follow_status[$row['followable_id']] = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed - TravelGram</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Cairo', 'Poppins', sans-serif;
            background: #eff1ee;
            color: #1b1c27;
            padding: 20px;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* ==== عناصر الخلفية الجمالية ==== */
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
            top: 0; left: 0;
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
            max-width: 650px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(15px);
            border-radius: 40px;
            box-shadow: 0 30px 50px rgba(27, 28, 39, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            position: relative;
            z-index: 10;
            overflow: hidden;
        }

        /* ==== الهيدر ==== */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 25px;
            background: linear-gradient(145deg, #65403a, #4f322c);
            color: white;
        }
        .logo { font-size: 1.8rem; font-weight: 700; letter-spacing: -0.02em; }
        .home-btn, .search-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.3rem;
            cursor: pointer;
            margin-right: 15px;
            transition: 0.3s;
        }
        .home-btn:hover, .search-btn:hover { opacity: 0.8; transform: scale(1.1); }
        .profile-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #9ec1d7;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        /* ==== شريط القصص ==== */
        .stories-bar {
            display: flex;
            gap: 15px;
            padding: 15px 20px;
            overflow-x: auto;
            white-space: nowrap;
            border-bottom: 1px solid rgba(158, 193, 215, 0.2);
            margin-bottom: 10px;
        }
        .story-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            flex-shrink: 0;
            min-width: 70px;
        }
        .story-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            padding: 2px;
            background: transparent;
            border: 2px solid transparent;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .story-avatar.has-story {
            border-color: #4A90E2;
            box-shadow: 0 0 10px rgba(74,144,226,0.4);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(74,144,226,0.5); }
            70% { box-shadow: 0 0 0 10px rgba(74,144,226,0); }
            100% { box-shadow: 0 0 0 0 rgba(74,144,226,0); }
        }
        .story-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        .story-name {
            margin-top: 4px;
            font-size: 12px;
            font-weight: 600;
            color: #1b1c27;
            text-align: center;
            max-width: 70px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ==== المنشورات ==== */
        .post {
            border-bottom: 1px solid rgba(158, 193, 215, 0.2);
            margin-bottom: 15px;
            background: transparent;
        }
        .post:last-child { border-bottom: none; }
        .post-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
        }
        .post-header-left { display: flex; align-items: center; gap: 12px; }
        .post-user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #9ec1d7;
        }
        .post-user-name {
            font-weight: 700;
            color: #1b1c27;
            cursor: pointer;
            transition: 0.2s;
        }
        .post-user-name:hover { color: #65403a; }
        .follow-btn {
            background: #65403a;
            color: white;
            border: none;
            border-radius: 30px;
            padding: 6px 18px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 4px 10px rgba(101, 64, 58, 0.2);
        }
        .follow-btn.following {
            background: transparent;
            color: #65403a;
            border: 1.5px solid #65403a;
            box-shadow: none;
        }
        .follow-btn:hover { transform: translateY(-2px); }
        .post-media { width: 100%; max-height: 550px; object-fit: cover; }
        .post-content { padding: 15px 20px; color: #1b1c27; line-height: 1.5; }
        .post-actions { padding: 10px 20px; display: flex; gap: 20px; align-items: center; }
        .like-btn {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #5a6a7a;
            transition: 0.2s;
        }
        .like-btn.liked { color: #ed4956; }
        .like-btn:hover { transform: scale(1.1); }
        .like-count { font-size: 15px; font-weight: 600; margin-left: 5px; color: #1b1c27; }
        .comment-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            color: #5a6a7a;
            transition: 0.2s;
        }
        .comment-btn:hover { color: #65403a; }

        .comments-section { padding: 0 20px 15px; }
        .comment-wrapper { margin-bottom: 10px; }
        .comment {
            background: #f0f2f5;
            padding: 8px 15px;
            border-radius: 25px;
            display: inline-block;
            max-width: 100%;
            font-size: 14px;
        }
        .comment strong { margin-right: 5px; color: #1b1c27; }
        .reply-btn, .delete-comment-btn {
            background: none;
            border: none;
            color: #65403a;
            font-size: 12px;
            cursor: pointer;
            margin-left: 10px;
            font-weight: 500;
        }
        .delete-comment-btn { color: #ed4956; }
        .reply-form-container { margin-left: 20px; margin-top: 5px; }
        .reply-form { display: flex; gap: 8px; margin-top: 8px; }
        .reply-form input {
            flex: 1;
            padding: 8px 15px;
            border: 1.5px solid rgba(158, 193, 215, 0.4);
            border-radius: 40px;
            font-size: 13px;
            background: white;
            outline: none;
        }
        .reply-form button {
            background: #65403a;
            border: none;
            color: white;
            border-radius: 40px;
            padding: 0 20px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .reply-form button:hover { background: #4f322c; }

        .comment-form {
            display: flex;
            padding: 15px 20px;
            border-top: 1px solid rgba(158, 193, 215, 0.2);
            gap: 10px;
        }
        .comment-form input {
            flex: 1;
            border: 1.5px solid rgba(158, 193, 215, 0.4);
            border-radius: 40px;
            padding: 12px 18px;
            outline: none;
            background: white;
            font-family: inherit;
        }
        .comment-form button {
            background: #65403a;
            border: none;
            color: white;
            border-radius: 40px;
            padding: 0 25px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .comment-form button:hover { background: #4f322c; }

        /* ==== المودال (للمنشورات) ==== */
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
            box-shadow: 0 40px 60px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.3);
        }
        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            cursor: pointer;
            color: #65403a;
            background: white;
            width: 38px;
            height: 38px;
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
            border-radius: 40px 0 0 40px;
            overflow: hidden;
        }
        .modal-details {
            flex: 1;
            padding: 25px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .modal-user {
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
        }
        .modal-user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #9ec1d7;
        }
        .modal-user-name { font-weight: 700; color: #1b1c27; }
        .modal-actions { display: flex; align-items: center; gap: 20px; }
        .like-button {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #5a6a7a;
            transition: 0.2s;
        }
        .like-button.liked { color: #ed4956; }
        .delete-button {
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: #ed4956;
            margin-left: auto;
        }
        .modal-likes { font-weight: 600; color: #1b1c27; }
        .modal-comments {
            flex: 1;
            max-height: 350px;
            overflow-y: auto;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
            padding: 15px 0;
        }
        .modal-comment {
            background: #f0f2f5;
            padding: 8px 15px;
            border-radius: 25px;
            display: inline-block;
            margin-bottom: 8px;
        }
        .modal-reply-btn, .modal-delete-btn {
            background: none;
            border: none;
            color: #65403a;
            font-size: 12px;
            cursor: pointer;
            margin-left: 10px;
        }
        .modal-delete-btn { color: #ed4956; }
        .modal-form {
            display: flex;
            gap: 12px;
            margin-top: 10px;
        }
        .modal-form input {
            flex: 1;
            border: 1.5px solid rgba(158, 193, 215, 0.5);
            border-radius: 40px;
            padding: 12px 18px;
            outline: none;
            background: white;
        }
        .modal-form button {
            background: #65403a;
            border: none;
            color: white;
            border-radius: 40px;
            padding: 0 25px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
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

        @media (max-width: 768px) {
            .container { border-radius: 30px; }
            .modal-body { flex-direction: column; }
            .modal-media { min-height: 250px; border-radius: 40px 40px 0 0; }
            .story-nav { width: 40px; height: 40px; font-size: 22px; }
            #prevStory { left: 10px; }
            #nextStory { right: 10px; }
        }
    </style>
</head>
<body>
<!-- عناصر الخلفية الزخرفية -->
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<div class="container">
    <div class="header">
        <div style="display: flex; align-items: center;">
            <button class="home-btn" onclick="location.href='../index.php'"><i class="fas fa-home"></i></button>
            <div class="logo">TravelGram</div>
        </div>
        <div>
            <button class="search-btn" onclick="location.href='search.php'"><i class="fas fa-search"></i></button>
            <a href="profile.php">
                <img src="<?php echo !empty($current_user_avatar) ? $current_user_avatar : 'default-avatar.png'; ?>" class="profile-icon" onerror="this.src='default-avatar.png'">
            </a>
        </div>
    </div>

    <!-- شريط القصص -->
    <?php if ($has_stories): ?>
    <div class="stories-bar" id="storiesBar">
        <?php foreach ($story_users as $su): ?>
            <div class="story-item" onclick="openUserStory(<?php echo $su['user_id']; ?>)">
                <div class="story-avatar has-story">
                    <img src="<?php echo !empty($su['user_avatar']) ? $su['user_avatar'] : 'img/default-avatar.png'; ?>" onerror="this.src='img/default-avatar.png'">
                </div>
                <span class="story-name"><?php echo htmlspecialchars($su['user_name']); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($posts)): ?>
        <div style="text-align: center; padding: 60px; color: #5a6a7a;">
            <i class="fas fa-newspaper" style="font-size: 50px; margin-bottom: 20px; opacity: 0.6;"></i>
            <p>No posts yet. Follow some users to see their posts.</p>
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): 
            $post_id = $post['id'];
            $like_count = isset($like_counts[$post_id]) ? $like_counts[$post_id] : 0;
            $comment_count = isset($comment_counts[$post_id]) ? $comment_counts[$post_id] : 0;
            $preview_sql = "SELECT c.*, u.name AS user_name
                            FROM comments c
                            JOIN post_comments pc ON pc.comment_id = c.id
                            JOIN users u ON c.user_id = u.id
                            WHERE pc.commentable_type='post' AND pc.commentable_id=$post_id AND c.parent_id IS NULL
                            ORDER BY c.created_at DESC LIMIT 3";
            $preview_res = mysqli_query($conn, $preview_sql);
            $preview_comments = [];
            while ($row = mysqli_fetch_assoc($preview_res)) $preview_comments[] = $row;
            $user_liked = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM post_reactions WHERE user_id=$current_user_id AND post_id=$post_id AND reaction_type_id=1")) > 0;
        ?>
            <div class="post" data-post-id="<?php echo $post_id; ?>">
                <div class="post-header">
                    <div class="post-header-left">
                        <img src="<?php echo !empty($post['user_avatar']) ? $post['user_avatar'] : 'img/default-avatar.png'; ?>" class="post-user-avatar" onerror="this.src='img/default-avatar.png'">
                        <span class="post-user-name" onclick="location.href='user_view.php?user_id=<?php echo $post['user_id']; ?>'"><?php echo htmlspecialchars($post['user_name']); ?></span>
                    </div>
                    <?php if ($post['user_id'] != $current_user_id): ?>
                        <button class="follow-btn <?php echo isset($follow_status[$post['user_id']]) ? 'following' : ''; ?>" data-user-id="<?php echo $post['user_id']; ?>">
                            <?php echo isset($follow_status[$post['user_id']]) ? 'Unfollow' : 'Follow'; ?>
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (!empty($post['image'])): ?>
                    <img src="../<?php echo $post['image']; ?>" class="post-media" alt="Image">
                <?php elseif (!empty($post['video'])): ?>
                    <video controls class="post-media"><source src="../<?php echo $post['video']; ?>" type="video/mp4"></video>
                <?php endif; ?>

                <div class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>

                <div class="post-actions">
                    <button class="like-btn <?php echo $user_liked ? 'liked' : ''; ?>" data-post-id="<?php echo $post_id; ?>"><i class="fas fa-heart"></i></button>
                    <span class="like-count" data-post-id="<?php echo $post_id; ?>"><?php echo $like_count; ?></span>
                    <button class="comment-btn" data-post-id="<?php echo $post_id; ?>" onclick="openPostModal(<?php echo $post_id; ?>)"><i class="fas fa-comment"></i> <?php echo $comment_count; ?></button>
                </div>

                <div class="comments-section">
                    <?php if (!empty($preview_comments)): ?>
                        <?php foreach ($preview_comments as $c): ?>
                            <div class="comment-wrapper" data-comment-id="<?php echo $c['id']; ?>">
                                <div class="comment">
                                    <strong><?php echo htmlspecialchars($c['user_name']); ?></strong> <?php echo nl2br(htmlspecialchars($c['content'])); ?>
                                    <button class="reply-btn" data-post-id="<?php echo $post_id; ?>" data-comment-id="<?php echo $c['id']; ?>">Reply</button>
                                    <?php if ($c['user_id'] == $current_user_id || $user_role == 'super_admin' || $user_role == 'office_admin'): ?>
                                        <button class="delete-comment-btn" data-post-id="<?php echo $post_id; ?>" data-comment-id="<?php echo $c['id']; ?>">Delete</button>
                                    <?php endif; ?>
                                    <div class="reply-form-container" data-comment-id="<?php echo $c['id']; ?>" style="margin-top: 5px;"></div>
                                </div>
                                <?php
                                $replies_sql = "SELECT c.*, u.name AS user_name
                                                FROM comments c
                                                JOIN users u ON c.user_id = u.id
                                                WHERE c.parent_id = {$c['id']}
                                                ORDER BY c.created_at ASC";
                                $replies_res = mysqli_query($conn, $replies_sql);
                                while ($reply = mysqli_fetch_assoc($replies_res)):
                                ?>
                                    <div class="comment-wrapper" data-comment-id="<?php echo $reply['id']; ?>" style="margin-left: 30px; margin-top: 5px;">
                                        <div class="comment">
                                            <strong><?php echo htmlspecialchars($reply['user_name']); ?></strong> <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                                            <button class="reply-btn" data-post-id="<?php echo $post_id; ?>" data-comment-id="<?php echo $reply['id']; ?>">Reply</button>
                                            <?php if ($reply['user_id'] == $current_user_id || $user_role == 'super_admin' || $user_role == 'office_admin'): ?>
                                                <button class="delete-comment-btn" data-post-id="<?php echo $post_id; ?>" data-comment-id="<?php echo $reply['id']; ?>">Delete</button>
                                            <?php endif; ?>
                                            <div class="reply-form-container" data-comment-id="<?php echo $reply['id']; ?>" style="margin-top: 5px;"></div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($comment_count > 3): ?>
                            <div style="font-size:13px; margin-top:8px;"><a href="#" onclick="openPostModal(<?php echo $post_id; ?>); return false;" style="color:#65403a;">View all comments (<?php echo $comment_count; ?>)</a></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="color:#5a6a7a; font-size:13px;">No comments yet.</div>
                    <?php endif; ?>
                </div>

                <form class="comment-form" data-post-id="<?php echo $post_id; ?>">
                    <input type="text" placeholder="Add a comment...">
                    <button type="submit">Post</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal للمنشورات -->
<div id="postModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-close" onclick="closePostModal()">&times;</div>
        <div class="modal-body">
            <div class="modal-media" id="modalMedia"></div>
            <div class="modal-details">
                <div class="modal-user">
                    <img id="modalUserAvatar" class="modal-user-avatar" src="img/default-avatar.png">
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
    <span class="modal-close" style="top: 20px; right: 30px; background: rgba(255,255,255,0.2); color: white;" onclick="closeStoryModal()">&times;</span>
    <div id="storyUserInfo">
        <img id="storyUserAvatar" src="img/default-avatar.png">
        <span id="storyUserName"></span>
    </div>
    <div id="storyMediaContainer"></div>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // تحويل بيانات القصص من PHP إلى JavaScript
    const allStoryUsers = <?php echo json_encode($story_users); ?>;
    let currentStoryUserIndex = -1;
    let currentStoryIndex = -1;
    let currentUserStories = [];

    function openUserStory(userId) {
        if (!allStoryUsers.hasOwnProperty(userId)) return;
        currentStoryUserIndex = userId;
        currentUserStories = allStoryUsers[userId].stories;
        if (currentUserStories.length === 0) return;
        currentStoryIndex = 0;
        document.getElementById('storyModal').style.display = 'flex';
        showCurrentStory();
        updateStoryNavButtons();
    }

    function closeStoryModal() {
        document.getElementById('storyModal').style.display = 'none';
        const video = document.getElementById('storyVideo');
        if (video) video.pause();
    }

    function showCurrentStory() {
        if (currentStoryIndex < 0 || currentStoryIndex >= currentUserStories.length) return;
        const story = currentUserStories[currentStoryIndex];
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

        // أزرار التحكم
        const video = document.getElementById('storyVideo');
        const playPauseBtn = document.getElementById('storyPlayPauseBtn');
        const controlsDiv = document.getElementById('storyControls');

        if (video) {
            controlsDiv.style.display = 'flex';
            updateVolumeIcon(video.muted);
            video.onclick = togglePlayPause;
            video.onplay = () => playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
            video.onpause = () => playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
        } else {
            controlsDiv.style.display = 'none';
        }

        // معلومات المستخدم
        document.getElementById('storyUserAvatar').src = allStoryUsers[currentStoryUserIndex].user_avatar || 'img/default-avatar.png';
        document.getElementById('storyUserName').innerText = allStoryUsers[currentStoryUserIndex].user_name;
    }

    function updateStoryNavButtons() {
        const prevBtn = document.getElementById('prevStory');
        const nextBtn = document.getElementById('nextStory');
        if (currentUserStories.length <= 1) {
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';
        } else {
            prevBtn.style.display = (currentStoryIndex > 0) ? 'flex' : 'none';
            nextBtn.style.display = (currentStoryIndex < currentUserStories.length - 1) ? 'flex' : 'none';
        }
    }

    function prevStory() {
        if (currentStoryIndex > 0) {
            currentStoryIndex--;
            showCurrentStory();
            updateStoryNavButtons();
        }
    }

    function nextStory() {
        if (currentStoryIndex < currentUserStories.length - 1) {
            currentStoryIndex++;
            showCurrentStory();
            updateStoryNavButtons();
        } else {
            closeStoryModal();
        }
    }

    function togglePlayPause() {
        const video = document.getElementById('storyVideo');
        if (!video) return;
        if (video.paused) video.play();
        else video.pause();
    }

    function toggleMute() {
        const video = document.getElementById('storyVideo');
        if (!video) return;
        video.muted = !video.muted;
        updateVolumeIcon(video.muted);
    }

    function updateVolumeIcon(muted) {
        const icon = document.querySelector('#storyVolumeBtn i');
        if (muted) icon.className = 'fas fa-volume-mute';
        else icon.className = 'fas fa-volume-up';
    }

    document.getElementById('storyModal').addEventListener('click', function(e) {
        if (e.target === this) closeStoryModal();
    });

    // ========== المنشورات (نفس الكود السابق) ==========
    let currentModalPostId = null;

    function renderModalComments(comments, postId, depth = 0) {
        let html = '';
        comments.forEach(comment => {
            let margin = depth * 30;
            html += `<div style="margin-left: ${margin}px; margin-bottom: 8px;">
                        <div class="modal-comment" data-comment-id="${comment.id}">
                            <strong>${escapeHtml(comment.user_name)}</strong> ${escapeHtml(comment.content)}
                            <button class="modal-reply-btn" data-post-id="${postId}" data-comment-id="${comment.id}">Reply</button>`;
            if (comment.is_owner || <?php echo json_encode($user_role == 'super_admin' || $user_role == 'office_admin'); ?>) {
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
                            <input type="text" placeholder="Write a reply..." style="flex:1; padding:8px 15px; border-radius:40px; border:1.5px solid rgba(158,193,215,0.4);">
                            <button type="submit" style="background:#65403a; border:none; color:white; border-radius:40px; padding:0 20px;">Reply</button>
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
                        document.querySelector(`.comment-btn[data-post-id="${postId}"]`).innerHTML = `<i class="fas fa-comment"></i> ${data.comment_count}`;
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
                    document.querySelector(`.comment-btn[data-post-id="${postId}"]`).innerHTML = `<i class="fas fa-comment"></i> ${data.comment_count}`;
                } else alert(data.message || 'Failed to delete');
            });
    }

    function closePostModal() { document.getElementById('postModal').style.display = 'none'; }

    function toggleLikeModal() {
        fetch(`like_ajax.php?post_id=${currentModalPostId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const likeBtn = document.getElementById('modalLikeBtn');
                    likeBtn.classList.toggle('liked', data.user_liked);
                    document.getElementById('modalLikes').innerHTML = `<i class="fas fa-heart"></i> ${data.likes}`;
                    const cardLikeBtn = document.querySelector(`.like-btn[data-post-id="${currentModalPostId}"]`);
                    if (cardLikeBtn) {
                        cardLikeBtn.classList.toggle('liked', data.user_liked);
                        document.querySelector(`.like-count[data-post-id="${currentModalPostId}"]`).innerText = data.likes;
                    }
                }
            });
    }

    function deletePostModal() {
        if (confirm('Are you sure you want to delete this post?'))
            window.location.href = `profile.php?delete_post=${currentModalPostId}&user_id=<?php echo $current_user_id; ?>&tab=posts`;
    }

    document.getElementById('modalLikeBtn').addEventListener('click', toggleLikeModal);
    document.getElementById('modalDeleteBtn').addEventListener('click', deletePostModal);
    document.getElementById('modalCommentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'add');
        fetch('comment_ajax.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    openPostModal(currentModalPostId);
                    document.querySelector(`.comment-btn[data-post-id="${currentModalPostId}"]`).innerHTML = `<i class="fas fa-comment"></i> ${data.comment_count}`;
                } else alert('Failed to add comment');
            });
        this.reset();
    });

    // أحداث البطاقات (نفس الكود السابق)
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const postId = this.dataset.postId;
            fetch(`like_ajax.php?post_id=${postId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.classList.toggle('liked', data.user_liked);
                        document.querySelector(`.like-count[data-post-id="${postId}"]`).innerText = data.likes;
                    }
                });
        });
    });

    document.querySelectorAll('.follow-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const userId = this.dataset.userId;
            const isFollowing = this.classList.contains('following');
            const action = isFollowing ? 'unfollow' : 'follow';
            fetch(`follow_ajax.php?user_id=${userId}&action=${action}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (data.following) { this.classList.add('following'); this.innerText = 'Unfollow'; }
                        else { this.classList.remove('following'); this.innerText = 'Follow'; }
                    }
                });
        });
    });

    document.querySelectorAll('.comment-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            const input = this.querySelector('input');
            const comment = input.value.trim();
            if (!comment) return;
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('post_id', postId);
            formData.append('comment', comment);
            fetch('comment_ajax.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert('Failed');
                });
        });
    });

    document.querySelectorAll('.reply-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const postId = this.dataset.postId;
            const parentCommentId = this.dataset.commentId;
            const container = document.querySelector(`.reply-form-container[data-comment-id="${parentCommentId}"]`);
            if (container.innerHTML.trim() !== '') {
                container.innerHTML = '';
                return;
            }
            const formHtml = `<form class="reply-form" data-post-id="${postId}" data-parent-id="${parentCommentId}">
                                <input type="text" placeholder="Write a reply...">
                                <button type="submit">Reply</button>
                              </form>`;
            container.innerHTML = formHtml;
            const replyForm = container.querySelector('.reply-form');
            replyForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const input = this.querySelector('input');
                const replyText = input.value.trim();
                if (!replyText) return;
                const postId = this.dataset.postId;
                const parentId = this.dataset.parentId;
                const fd = new FormData();
                fd.append('action', 'add');
                fd.append('post_id', postId);
                fd.append('comment', replyText);
                fd.append('parent_id', parentId);
                fetch('comment_ajax.php', { method: 'POST', body: fd })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) location.reload();
                        else alert('Failed');
                    });
            });
        });
    });

    document.querySelectorAll('.delete-comment-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (!confirm('Delete this comment?')) return;
            const commentId = this.dataset.commentId;
            const postId = this.dataset.postId;
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('comment_id', commentId);
            fd.append('post_id', postId);
            fetch('comment_ajax.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert(data.message || 'Failed');
                });
        });
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal') && event.target.id === 'postModal') closePostModal();
    }
</script>
</body>
</html>
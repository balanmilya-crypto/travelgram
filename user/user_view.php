<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$current_user_id = $_SESSION['user_id'];
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($user_id == 0) die("Invalid user");

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'posts';

// Fetch user data
$user_query = "SELECT id, name, avatar FROM users WHERE id = $user_id";
$user_res = mysqli_query($conn, $user_query);
if (mysqli_num_rows($user_res) == 0) die("User not found");
$user = mysqli_fetch_assoc($user_res);

// Follow stats
$followers_count = 0;
$followers_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM follows WHERE followable_type = 'user' AND followable_id = $user_id");
if ($row = mysqli_fetch_assoc($followers_res)) $followers_count = $row['cnt'];

$following_count = 0;
$following_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM follows WHERE follower_id = $user_id");
if ($row = mysqli_fetch_assoc($following_res)) $following_count = $row['cnt'];

// Check if current user follows this user
$is_following = false;
if ($current_user_id != $user_id) {
    $check = mysqli_query($conn, "SELECT id FROM follows WHERE follower_id = $current_user_id AND followable_type = 'user' AND followable_id = $user_id");
    $is_following = mysqli_num_rows($check) > 0;
}

// Fetch posts
if ($tab == 'reels') {
    $posts_query = "SELECT * FROM posts WHERE user_id = $user_id AND type = 'reel' ORDER BY created_at DESC";
} else {
    $posts_query = "SELECT * FROM posts WHERE user_id = $user_id AND type = 'post' ORDER BY created_at DESC";
}
$posts_result = mysqli_query($conn, $posts_query);
if (!$posts_result) die("Error in posts query: " . mysqli_error($conn));
$posts = [];
while ($post = mysqli_fetch_assoc($posts_result)) $posts[] = $post;

// Like and comment counts
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['name']); ?> - TravelGram</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing: border-box; }
        body {
            font-family: 'Cairo', 'Poppins', sans-serif;
            background: #eff1ee;
            color: #1b1c27;
            padding: 20px;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* ==== خلفية متحركة ==== */
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
            width: 100%; height: 100%; top: 0; left: 0; z-index: 0; pointer-events: none;
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
            max-width: 850px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(15px);
            border-radius: 40px;
            box-shadow: 0 30px 50px rgba(27, 28, 39, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            position: relative;
            z-index: 10;
            overflow: hidden;
            padding: 25px 30px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #65403a;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            padding: 8px 18px;
            border-radius: 40px;
            background: rgba(101, 64, 58, 0.08);
        }
        .back-link i { margin-right: 8px; }
        .back-link:hover {
            background: #65403a;
            color: white;
            transform: translateX(-5px);
        }

        /* ==== رأس البروفايل ==== */
        .profile-header {
            display: flex;
            align-items: center;
            gap: 25px;
            padding: 20px 0;
            border-bottom: 1px solid rgba(158, 193, 215, 0.3);
            flex-wrap: wrap;
        }
        .profile-avatar {
            width: 90px; height: 90px; border-radius: 50%; object-fit: cover;
            border: 3px solid #9ec1d7; box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .avatar-initial {
            width: 90px; height: 90px; border-radius: 50%;
            background: linear-gradient(145deg, #65403a, #9ec1d7);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: bold; font-size: 40px;
            border: 3px solid #9ec1d7; box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .profile-info { flex: 1; }
        .profile-name { font-size: 2rem; font-weight: 700; color: #1b1c27; margin-bottom: 8px; }
        .profile-stats { display: flex; gap: 25px; margin-top: 12px; }
        .profile-stats span {
            cursor: pointer; color: #1b1c27; font-weight: 600;
            transition: 0.2s;
        }
        .profile-stats span strong { color: #65403a; font-size: 1.2rem; }
        .profile-stats span:hover { color: #65403a; }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 15px;
        }

        .follow-btn, .unfollow-btn {
            background: #65403a;
            color: white;
            border: none;
            border-radius: 40px;
            padding: 10px 24px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 8px 20px rgba(101, 64, 58, 0.25);
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .follow-btn:hover {
            background: #4f322c;
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(101, 64, 58, 0.3);
        }
        .unfollow-btn {
            background: transparent;
            color: #65403a;
            border: 2px solid #65403a;
            box-shadow: none;
        }
        .unfollow-btn:hover {
            background: #65403a;
            color: white;
        }

        .message-btn {
            background: transparent;
            color: #65403a;
            border: 2px solid #9ec1d7;
            border-radius: 40px;
            padding: 10px 24px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .message-btn:hover {
            background: #9ec1d7;
            color: #1b1c27;
            border-color: #9ec1d7;
            transform: translateY(-2px);
        }

        /* ==== التبويبات ==== */
        .tabs {
            display: flex;
            justify-content: center;
            gap: 40px;
            border-top: 1px solid rgba(158, 193, 215, 0.3);
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
            aspect-ratio: 1/1;
            position: relative;
            cursor: pointer;
            overflow: hidden;
            border-radius: 25px;
            background: #f0f0f0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: 0.3s;
        }
        .post-item:hover { transform: scale(1.02); }
        .post-item img, .post-item video { width: 100%; height: 100%; object-fit: cover; }
        .post-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(27, 28, 39, 0.5); backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center; gap: 25px;
            opacity: 0; transition: 0.3s; color: white;
        }
        .post-item:hover .post-overlay { opacity: 1; }
        .post-overlay span { font-size: 18px; font-weight: 600; }

        /* ==== المودال ==== */
        .modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(27, 28, 39, 0.8); backdrop-filter: blur(8px);
            z-index: 1000; justify-content: center; align-items: center;
        }
        .modal-content {
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(15px);
            width: 90%; max-width: 950px; max-height: 90vh; overflow-y: auto;
            border-radius: 40px; position: relative; box-shadow: 0 40px 60px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.3);
        }
        .modal-close {
            position: absolute; top: 15px; right: 20px; font-size: 28px; cursor: pointer;
            color: #65403a; background: white; width: 38px; height: 38px;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); z-index: 10;
        }
        .modal-body { display: flex; flex-wrap: wrap; }
        .modal-media {
            flex: 1.2; background: #000; display: flex; align-items: center; justify-content: center;
            min-height: 350px; border-radius: 40px 0 0 40px; overflow: hidden;
        }
        .modal-media img, .modal-media video { max-width: 100%; max-height: 500px; object-fit: contain; }
        .modal-details {
            flex: 1; padding: 25px; display: flex; flex-direction: column; gap: 15px;
        }
        .modal-user {
            display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
        }
        .modal-user-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #9ec1d7; }
        .modal-user-name { font-weight: 700; color: #1b1c27; }
        .modal-content-text { font-size: 1rem; line-height: 1.5; color: #1b1c27; }
        .modal-actions { display: flex; align-items: center; gap: 20px; }
        .like-button {
            background: none; border: none; font-size: 28px; cursor: pointer;
            color: #5a6a7a; transition: 0.2s;
        }
        .like-button.liked { color: #ed4956; }
        .modal-likes { font-weight: 600; color: #1b1c27; }
        .modal-comments {
            flex: 1; max-height: 300px; overflow-y: auto;
            border-top: 1px solid #ddd; border-bottom: 1px solid #ddd;
            padding: 15px 0;
        }
        .modal-comment { margin-bottom: 10px; font-size: 14px; }
        .modal-comment strong { color: #65403a; margin-right: 6px; }
        .modal-form { display: flex; gap: 12px; margin-top: 10px; }
        .modal-form input {
            flex: 1; border: 1.5px solid rgba(158, 193, 215, 0.5);
            border-radius: 40px; padding: 12px 18px; outline: none; background: white;
        }
        .modal-form button {
            background: #65403a; border: none; color: white; border-radius: 40px;
            padding: 0 25px; font-weight: 600; cursor: pointer; transition: 0.3s;
        }
        .modal-form button:hover { background: #4f322c; }

        @media (max-width: 768px) {
            .container { padding: 20px 18px; }
            .profile-header { flex-direction: column; text-align: center; }
            .profile-stats { justify-content: center; }
            .action-buttons { justify-content: center; }
            .modal-body { flex-direction: column; }
            .modal-media { min-height: 250px; border-radius: 40px 40px 0 0; }
        }
    </style>
</head>
<body>
<!-- خلفية -->
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<div class="container">
    <a href="feed.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Feed</a>
    
    <div class="profile-header">
        <?php if (!empty($user['avatar'])): ?>
            <img src="<?php echo $user['avatar']; ?>" class="profile-avatar" onerror="this.src='img/default-avatar.png'">
        <?php else: ?>
            <div class="avatar-initial"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
        <?php endif; ?>
        <div class="profile-info">
            <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
            <div class="profile-stats">
                <span onclick="location.href='followers.php?user_id=<?php echo $user_id; ?>'">
                    <strong><?php echo $followers_count; ?></strong> Followers
                </span>
                <span onclick="location.href='following.php?user_id=<?php echo $user_id; ?>'">
                    <strong><?php echo $following_count; ?></strong> Following
                </span>
            </div>
            <?php if ($current_user_id != $user_id): ?>
                <div class="action-buttons">
                    <form method="post" action="follow.php" style="display:inline;">
                        <input type="hidden" name="type" value="user">
                        <input type="hidden" name="id" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="action" value="<?php echo $is_following ? 'unfollow' : 'follow'; ?>">
                        <button type="submit" class="<?php echo $is_following ? 'unfollow-btn' : 'follow-btn'; ?>">
                            <i class="fas <?php echo $is_following ? 'fa-user-check' : 'fa-user-plus'; ?>"></i>
                            <?php echo $is_following ? 'Unfollow' : 'Follow'; ?>
                        </button>
                    </form>
                    <a href="messenger.php?chat_with=<?php echo $user_id; ?>" class="message-btn">
                        <i class="fas fa-comment-dots"></i> Message
                    </a>
                </div>
            <?php else: ?>
                <div style="margin-top: 15px;"></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="tabs">
        <a href="user_view.php?user_id=<?php echo $user_id; ?>&tab=posts" class="tab <?php if ($tab == 'posts') echo 'active'; ?>">
            <i class="fas fa-th-large"></i> Posts
        </a>
        <a href="user_view.php?user_id=<?php echo $user_id; ?>&tab=reels" class="tab <?php if ($tab == 'reels') echo 'active'; ?>">
            <i class="fas fa-video"></i> Reels
        </a>
    </div>

    <?php if (empty($posts)): ?>
        <div style="text-align: center; padding: 50px; color: #5a6a7a;">
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
                <div class="modal-content-text" id="modalContent"></div>
                <div class="modal-actions">
                    <button class="like-button" id="modalLikeBtn" onclick="toggleLikeModal()">
                        <i class="fas fa-heart"></i>
                    </button>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let currentModalPostId = null;

    function openPostModal(postId) {
        currentModalPostId = postId;
        fetch(`get_post.php?id=${postId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('modalMedia').innerHTML = data.media;
                document.getElementById('modalContent').innerHTML = data.content;
                document.getElementById('modalLikes').innerHTML = `<i class="fas fa-heart"></i> ${data.likes}`;
                document.getElementById('modalPostId').value = postId;
                document.getElementById('modalUserAvatar').src = data.user_avatar ? '../' + data.user_avatar : 'img/default-avatar.png';
                document.getElementById('modalUserName').innerText = data.user_name;
                const likeBtn = document.getElementById('modalLikeBtn');
                likeBtn.classList.toggle('liked', data.user_liked);
                let commentsHtml = '';
                data.comments.forEach(comment => {
                    commentsHtml += `<div class="modal-comment"><strong>${escapeHtml(comment.user_name)}</strong> ${escapeHtml(comment.content)}</div>`;
                });
                document.getElementById('modalComments').innerHTML = commentsHtml;
                document.getElementById('postModal').style.display = 'flex';
            })
            .catch(error => console.error('Error:', error));
    }

    function closePostModal() {
        document.getElementById('postModal').style.display = 'none';
    }

    function toggleLikeModal() {
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

    document.getElementById('modalCommentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('comment_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                openPostModal(currentModalPostId);
                const gridItem = document.querySelector(`.post-item[onclick="openPostModal(${currentModalPostId})"] .post-overlay span:last-child`);
                if (gridItem) gridItem.innerHTML = `<i class="fas fa-comment"></i> ${data.comment_count}`;
            } else alert('Failed to add comment');
        });
        this.reset();
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal') && event.target.id === 'postModal') {
            closePostModal();
        }
    }
</script>
</body>
</html>
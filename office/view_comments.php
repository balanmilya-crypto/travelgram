<?php
session_start();
include 'connection.php';

// Check office admin permission
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'office_admin') {
    header('Location: ../login.php');
    exit();
}

$office_id = isset($_SESSION['user_office_id']) ? intval($_SESSION['user_office_id']) : 0;
$tour_id = isset($_GET['tour_id']) ? intval($_GET['tour_id']) : 0;

$tour_title = '';
$all_comments_mode = false;

if ($tour_id == 0) {
    $all_comments_mode = true;
    $tour_title = 'All My Tours';
} else {
    $tour_sql = "SELECT title FROM tours WHERE id = $tour_id AND office_id = $office_id";
    $tour_result = mysqli_query($conn, $tour_sql);
    if (mysqli_num_rows($tour_result) == 0) die("Tour not found or you don't have permission.");
    $tour = mysqli_fetch_assoc($tour_result);
    $tour_title = $tour['title'];
}

// --- Add reply ---
$comment_error = '';
$success_msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment'])) {
    if (!isset($_SESSION['user_id'])) {
        $comment_error = "You must be logged in to add a reply.";
    } else {
        $user_id = $_SESSION['user_id'];
        $content = trim($_POST['comment_content']);
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
        if (empty($content)) {
            $comment_error = "Please enter reply text.";
        } else {
            $content = mysqli_real_escape_string($conn, $content);
            $parent_id_sql = ($parent_id == 0) ? 'NULL' : $parent_id;
            mysqli_begin_transaction($conn);
            try {
                $ins1 = "INSERT INTO comments (user_id, content, parent_id, created_at, updated_at) VALUES ($user_id, '$content', $parent_id_sql, NOW(), NOW())";
                if (!mysqli_query($conn, $ins1)) throw new Exception("Failed to insert reply");
                $new_id = mysqli_insert_id($conn);
                // Find the tour linked to the parent comment
                $tour_query = "SELECT commentable_id FROM post_comments WHERE comment_id = $parent_id AND commentable_type = 'tour'";
                $tour_res = mysqli_query($conn, $tour_query);
                if ($tour_row = mysqli_fetch_assoc($tour_res)) {
                    $actual_tour_id = $tour_row['commentable_id'];
                    $ins2 = "INSERT INTO post_comments (comment_id, commentable_type, commentable_id, created_at) VALUES ($new_id, 'tour', $actual_tour_id, NOW())";
                    if (!mysqli_query($conn, $ins2)) throw new Exception("Failed to link reply to tour");
                } else {
                    throw new Exception("Tour not found");
                }
                mysqli_commit($conn);
                $success_msg = "Reply added successfully.";
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $comment_error = $e->getMessage();
            }
        }
    }
}

// --- Delete comment ---
if (isset($_GET['delete_comment'])) {
    $comment_id = intval($_GET['delete_comment']);
    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "DELETE FROM post_comments WHERE comment_id = $comment_id");
        mysqli_query($conn, "DELETE FROM comments WHERE id = $comment_id");
        mysqli_commit($conn);
        $success_msg = "Comment deleted successfully.";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $comment_error = "Delete error: " . $e->getMessage();
    }
}

// --- Toggle reaction ---
if (isset($_GET['react_comment']) && isset($_GET['reaction_id'])) {
    $comment_id = intval($_GET['react_comment']);
    $reaction_id = intval($_GET['reaction_id']);
    $user_id = $_SESSION['user_id'] ?? 0;
    if ($user_id) {
        $check = mysqli_query($conn, "SELECT id FROM comment_reactions WHERE user_id = $user_id AND comment_id = $comment_id AND reaction_type_id = $reaction_id");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "DELETE FROM comment_reactions WHERE user_id = $user_id AND comment_id = $comment_id AND reaction_type_id = $reaction_id");
        } else {
            mysqli_query($conn, "DELETE FROM comment_reactions WHERE user_id = $user_id AND comment_id = $comment_id");
            mysqli_query($conn, "INSERT INTO comment_reactions (user_id, comment_id, reaction_type_id) VALUES ($user_id, $comment_id, $reaction_id)");
        }
        $redirect = "view_comments.php?tour_id=$tour_id";
        if ($all_comments_mode) $redirect = "view_comments.php";
        header("Location: $redirect");
        exit();
    }
}

// --- Fetch comments (with user_role and avatar, no email) ---
if ($all_comments_mode) {
    $sql = "SELECT c.*, u.name AS user_name, u.role AS user_role, u.avatar AS user_avatar, t.title AS tour_title, t.id AS tour_id
            FROM comments c
            JOIN users u ON c.user_id = u.id
            JOIN post_comments pc ON c.id = pc.comment_id
            JOIN tours t ON pc.commentable_id = t.id
            WHERE pc.commentable_type = 'tour' AND t.office_id = $office_id
            ORDER BY c.created_at ASC";
} else {
    $sql = "SELECT c.*, u.name AS user_name, u.role AS user_role, u.avatar AS user_avatar, t.title AS tour_title, t.id AS tour_id
            FROM comments c
            JOIN users u ON c.user_id = u.id
            JOIN post_comments pc ON c.id = pc.comment_id
            JOIN tours t ON pc.commentable_id = t.id
            WHERE pc.commentable_type = 'tour' AND pc.commentable_id = $tour_id
            ORDER BY c.created_at ASC";
}
$result = mysqli_query($conn, $sql);
$comments = [];
while ($row = mysqli_fetch_assoc($result)) $comments[] = $row;

// Separate top comments and replies
$top_comments = [];
$replies = [];
foreach ($comments as $c) {
    if ($c['parent_id'] === NULL) {
        $top_comments[] = $c;
    } else {
        $replies[$c['parent_id']][] = $c;
    }
}

// --- Reaction types with fixed emoji map ---
$emoji_map = [
    1 => '👍',   // Like
    2 => '❤️',   // Love
    3 => '🤗',   // Care
    4 => '😂',   // Haha
    5 => '😲',   // Wow
    6 => '😢',   // Sad
    7 => '😠'    // Angry
];
$reaction_types = [];
$rt_res = mysqli_query($conn, "SELECT id, name FROM reaction_types ORDER BY id");
while ($rt = mysqli_fetch_assoc($rt_res)) {
    $rt['icon'] = $emoji_map[$rt['id']] ?? '👍';
    $reaction_types[] = $rt;
}

// Reaction stats
$all_comment_ids = array_column($comments, 'id');
$reactions_stats_map = [];
if (!empty($all_comment_ids)) {
    $ids_string = implode(',', $all_comment_ids);
    $user_id_sql = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    $stats_query = "
        SELECT 
            rt.id AS reaction_type_id,
            rt.name,
            cr.comment_id,
            COUNT(cr.id) AS cnt,
            MAX(CASE WHEN cr.user_id = $user_id_sql THEN 1 ELSE 0 END) AS user_reacted
        FROM reaction_types rt
        LEFT JOIN comment_reactions cr ON cr.reaction_type_id = rt.id AND cr.comment_id IN ($ids_string)
        GROUP BY rt.id, cr.comment_id
    ";
    $stats_res = mysqli_query($conn, $stats_query);
    while ($row = mysqli_fetch_assoc($stats_res)) {
        $comment_id = $row['comment_id'];
        if (!isset($reactions_stats_map[$comment_id])) $reactions_stats_map[$comment_id] = [];
        $reactions_stats_map[$comment_id][] = [
            'id' => $row['reaction_type_id'],
            'name' => $row['name'],
            'icon' => $emoji_map[$row['reaction_type_id']] ?? '👍',
            'cnt' => (int)$row['cnt'],
            'user_reacted' => (int)$row['user_reacted']
        ];
    }
}

function getReactionsForComment($comment_id, $map, $all_types, $emoji_map) {
    $stats = isset($map[$comment_id]) ? $map[$comment_id] : [];
    if (empty($stats)) {
        $stats = [];
        foreach ($all_types as $type) {
            $stats[] = [
                'id' => $type['id'],
                'name' => $type['name'],
                'icon' => $emoji_map[$type['id']] ?? '👍',
                'cnt' => 0,
                'user_reacted' => 0
            ];
        }
    } else {
        $existing_ids = array_column($stats, 'id');
        foreach ($all_types as $type) {
            if (!in_array($type['id'], $existing_ids)) {
                $stats[] = [
                    'id' => $type['id'],
                    'name' => $type['name'],
                    'icon' => $emoji_map[$type['id']] ?? '👍',
                    'cnt' => 0,
                    'user_reacted' => 0
                ];
            }
        }
    }
    return $stats;
}

function display_comment($comment, $replies, $tour_id, $all_comments_mode, $reactions_stats_map, $reaction_types, $emoji_map, $level = 0) {
    $margin = $level * 30;
    $stats = getReactionsForComment($comment['id'], $reactions_stats_map, $reaction_types, $emoji_map);
    $active_reactions = array_filter($stats, function($r) { return $r['cnt'] > 0; });
    $initial = strtoupper(substr($comment['user_name'], 0, 1));
    $role_text = '';
    switch ($comment['user_role'] ?? 'user') {
        case 'super_admin': $role_text = '(Super Admin)'; break;
        case 'office_admin': $role_text = '(Office Admin)'; break;
        default: $role_text = '(User)';
    }
    ?>
    <div class="comment-card" style="margin-left: <?php echo $margin; ?>px; margin-bottom: 15px;">
        <div class="comment-header">
            <div class="avatar">
                <?php if (!empty($comment['user_avatar'])): ?>
                    <img src="<?php echo $comment['user_avatar']; ?>" alt="avatar">
                <?php else: ?>
                    <?php echo $initial; ?>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <div class="user-name">
                    <?php echo htmlspecialchars($comment['user_name']); ?>
                    <span class="user-role"><?php echo $role_text; ?></span>
                </div>
            </div>
            <div class="comment-date"><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></div>
        </div>
        <?php if ($all_comments_mode && isset($comment['tour_title'])): ?>
            <div class="tour-link-wrapper">
                <i class="fas fa-map-marker-alt"></i> <a href="view_tour.php?id=<?php echo $comment['tour_id']; ?>" class="tour-link"><?php echo htmlspecialchars($comment['tour_title']); ?></a>
            </div>
        <?php endif; ?>
        <div class="comment-content">
            <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
        </div>
        
        <!-- منطقة التفاعلات الجديدة -->
        <div class="reactions-bar">
            <div class="reaction-wrapper">
                <?php if (empty($active_reactions)): ?>
                    <?php $default_reaction = $stats[0] ?? ['id' => 1, 'icon' => '👍', 'cnt' => 0, 'user_reacted' => 0]; ?>
                    <a href="?react_comment=<?php echo $comment['id']; ?>&reaction_id=<?php echo $default_reaction['id']; ?>&tour_id=<?php echo $tour_id; ?>" 
                       class="main-reaction-btn <?php echo $default_reaction['user_reacted'] ? 'active' : ''; ?>">
                        <span class="emoji-icon"><?php echo $default_reaction['icon']; ?></span>
                        <span class="reaction-count"><?php echo $default_reaction['cnt']; ?></span>
                    </a>
                <?php else: ?>
                    <?php foreach ($active_reactions as $reaction): ?>
                        <a href="?react_comment=<?php echo $comment['id']; ?>&reaction_id=<?php echo $reaction['id']; ?>&tour_id=<?php echo $tour_id; ?>" 
                           class="main-reaction-btn <?php echo $reaction['user_reacted'] ? 'active' : ''; ?>"
                           style="margin-right: 5px;">
                            <span class="emoji-icon"><?php echo $reaction['icon']; ?></span>
                            <span class="reaction-count"><?php echo $reaction['cnt']; ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- نافذة اختيار التفاعلات -->
                <div class="reaction-picker-popup">
                    <?php foreach ($stats as $reaction): ?>
                        <a href="?react_comment=<?php echo $comment['id']; ?>&reaction_id=<?php echo $reaction['id']; ?>&tour_id=<?php echo $tour_id; ?>" 
                           class="reaction-option <?php echo $reaction['user_reacted'] ? 'active' : ''; ?>"
                           title="<?php echo ucfirst($reaction['name']); ?>">
                            <?php echo $reaction['icon']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="comment-actions">
            <button onclick="toggleReplyForm(<?php echo $comment['id']; ?>)" class="btn btn-reply"><i class="fas fa-reply"></i> Reply</button>
            <a href="?delete_comment=<?php echo $comment['id']; ?>&tour_id=<?php echo $tour_id; ?>" class="btn btn-delete" onclick="return confirm('Are you sure?')"><i class="fas fa-trash-alt"></i> Delete</a>
        </div>
        <div id="reply-form-<?php echo $comment['id']; ?>" class="reply-form">
            <form method="post">
                <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                <textarea name="comment_content" rows="2" placeholder="Write your reply..."></textarea>
                <div class="reply-form-buttons">
                    <button type="submit" name="add_comment" class="btn btn-primary">Send Reply</button>
                    <button type="button" onclick="toggleReplyForm(<?php echo $comment['id']; ?>)" class="btn btn-cancel">Cancel</button>
                </div>
            </form>
        </div>
        <?php if (isset($replies[$comment['id']])): ?>
            <div class="replies">
                <?php foreach ($replies[$comment['id']] as $child): ?>
                    <?php display_comment($child, $replies, $tour_id, $all_comments_mode, $reactions_stats_map, $reaction_types, $emoji_map, $level + 1); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments - <?php echo htmlspecialchars($tour_title); ?> | TravelGram</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Cairo', 'Poppins', sans-serif;
            background: #eff1ee;
            color: #1b1c27;
            padding: 20px;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* خلفية متحركة */
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

        /* الحاوية الرئيسية زجاجية */
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(15px);
            border-radius: 40px;
            padding: 35px 40px;
            box-shadow: 0 30px 50px rgba(27, 28, 39, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            position: relative;
            z-index: 10;
        }

        h2 {
            text-align: center;
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #65403a, #9ec1d7);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 10px;
            letter-spacing: -0.02em;
        }

        .tour-title {
            text-align: center;
            color: #5a6a7a;
            margin-bottom: 30px;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .back-link {
            display: inline-block;
            background: #65403a;
            color: white;
            padding: 10px 22px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 25px;
            transition: 0.3s;
            box-shadow: 0 8px 20px rgba(101, 64, 58, 0.2);
        }
        .back-link i { margin-right: 8px; }
        .back-link:hover {
            background: #4f322c;
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(101, 64, 58, 0.3);
        }

        .message, .error {
            padding: 15px 20px;
            border-radius: 40px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            backdrop-filter: blur(8px);
        }
        .message {
            background: rgba(40, 167, 69, 0.1);
            color: #155724;
            border-left: 5px solid #28a745;
        }
        .error {
            background: rgba(220, 53, 69, 0.1);
            color: #b91c1c;
            border-left: 5px solid #b91c1c;
        }

        .comment-card {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(5px);
            border-radius: 25px;
            padding: 20px 25px;
            margin-bottom: 20px;
            border: 1px solid rgba(158, 193, 215, 0.3);
            transition: all 0.2s ease;
        }
        .comment-card:hover {
            background: rgba(255, 255, 255, 0.8);
            border-color: #9ec1d7;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(101, 64, 58, 0.08);
        }

        .comment-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #65403a;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            overflow: hidden;
            border: 2px solid #9ec1d7;
            flex-shrink: 0;
        }
        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-info { flex: 1; }
        .user-name {
            font-weight: 700;
            color: #1b1c27;
            font-size: 1.05rem;
        }
        .user-role {
            font-size: 0.75rem;
            color: #5a6a7a;
            margin-left: 8px;
            font-weight: 400;
        }

        .comment-date {
            font-size: 0.8rem;
            color: #5a6a7a;
        }

        .tour-link-wrapper {
            margin: 5px 0 10px 0;
            color: #5a6a7a;
            font-size: 0.9rem;
        }
        .tour-link {
            color: #65403a;
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s;
        }
        .tour-link:hover {
            color: #4f322c;
            text-decoration: underline;
        }

        .comment-content {
            margin: 15px 0;
            padding: 15px 20px;
            background: rgba(239, 241, 238, 0.5);
            border-radius: 20px;
            border-left: 4px solid #9ec1d7;
            line-height: 1.7;
            color: #2d3a4a;
        }

        /* نظام التفاعلات الجديد */
        .reactions-bar {
            margin: 12px 0;
            display: flex;
            align-items: center;
        }
        .reaction-wrapper {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            flex-wrap: wrap;
            position: relative;
        }
        .main-reaction-btn {
            background: #f0f2f5;
            border: none;
            border-radius: 40px;
            padding: 5px 12px;
            font-size: 18px;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #1b1c27;
            text-decoration: none;
            border: 1px solid transparent;
        }
        .main-reaction-btn:hover {
            background: #e4e6e9;
            transform: scale(1.02);
        }
        .main-reaction-btn .emoji-icon {
            font-size: 22px;
            line-height: 1;
        }
        .main-reaction-btn .reaction-count {
            font-size: 13px;
            font-weight: 600;
        }
        .main-reaction-btn.active {
            background: #65403a;
            color: white;
        }
        .reaction-picker-popup {
            position: absolute;
            bottom: 45px;
            left: 0;
            background: white;
            border-radius: 50px;
            padding: 8px 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: none;
            gap: 8px;
            z-index: 100;
            white-space: nowrap;
            border: 1px solid rgba(158, 193, 215, 0.3);
            backdrop-filter: blur(8px);
            background: rgba(255, 255, 255, 0.95);
            animation: fadeInUp 0.2s ease;
        }
        .reaction-wrapper:hover .reaction-picker-popup {
            display: flex;
        }
        .reaction-option {
            font-size: 28px;
            padding: 6px 8px;
            cursor: pointer;
            transition: transform 0.2s, background 0.2s;
            border-radius: 50%;
            text-decoration: none;
            display: inline-block;
            line-height: 1;
        }
        .reaction-option:hover {
            transform: scale(1.3);
            background: #f0f2f5;
        }
        .reaction-option.active {
            background: #65403a;
            border-radius: 50%;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .comment-actions {
            margin-top: 15px;
            display: flex;
            gap: 15px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            text-decoration: none;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            background: #65403a;
            color: white;
            box-shadow: 0 4px 10px rgba(101, 64, 58, 0.15);
        }
        .btn:hover {
            background: #4f322c;
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(101, 64, 58, 0.2);
        }

        .btn-reply {
            background: #9ec1d7;
            color: #1b1c27;
            box-shadow: 0 4px 10px rgba(158, 193, 215, 0.2);
        }
        .btn-reply:hover {
            background: #7baec7;
        }

        .btn-delete {
            background: transparent;
            color: #dc3545;
            border: 1px solid #dc3545;
            box-shadow: none;
        }
        .btn-delete:hover {
            background: #dc3545;
            color: white;
        }

        .btn-cancel {
            background: transparent;
            color: #65403a;
            border: 1px solid #65403a;
            box-shadow: none;
        }
        .btn-cancel:hover {
            background: #65403a;
            color: white;
        }

        .reply-form {
            margin-top: 15px;
            padding: 15px 20px;
            background: rgba(239, 241, 238, 0.5);
            border-radius: 20px;
            display: none;
            border: 1px solid rgba(158, 193, 215, 0.3);
        }
        .reply-form textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1.5px solid rgba(158, 193, 215, 0.4);
            border-radius: 20px;
            font-family: inherit;
            margin-bottom: 12px;
            resize: vertical;
            background: white;
            font-size: 0.95rem;
        }
        .reply-form textarea:focus {
            border-color: #65403a;
            outline: none;
            box-shadow: 0 0 0 3px rgba(101, 64, 58, 0.1);
        }
        .reply-form-buttons {
            display: flex;
            gap: 10px;
        }

        .replies {
            margin-top: 10px;
        }

        .no-comments {
            text-align: center;
            padding: 60px 20px;
            color: #5a6a7a;
        }
        .no-comments i {
            font-size: 50px;
            color: #65403a;
            opacity: 0.5;
            margin-bottom: 15px;
        }

        .add-comment-box {
            background: rgba(239, 241, 238, 0.6);
            backdrop-filter: blur(5px);
            padding: 25px;
            border-radius: 25px;
            margin: 30px 0;
            border: 1px solid rgba(158, 193, 215, 0.3);
        }
        .add-comment-box h4 {
            color: #1b1c27;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        .add-comment-box textarea {
            width: 100%;
            padding: 15px 20px;
            border-radius: 20px;
            border: 1.5px solid rgba(158, 193, 215, 0.4);
            resize: vertical;
            background: white;
            font-family: inherit;
        }
        .add-comment-box textarea:focus {
            border-color: #65403a;
            outline: none;
            box-shadow: 0 0 0 3px rgba(101, 64, 58, 0.1);
        }

        @media (max-width: 768px) {
            .container { padding: 25px 20px; }
            h2 { font-size: 1.8rem; }
            .comment-content { margin-left: 0; }
        }
    </style>
</head>
<body>
<!-- خلفية -->
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<div class="container">
    <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <h2><i class="fas fa-comments" style="color: #65403a; margin-right: 10px;"></i>Comments</h2>
    <div class="tour-title"><?php echo htmlspecialchars($tour_title); ?></div>

    <?php if ($success_msg): ?>
        <div class="message"><i class="fas fa-check-circle mr-2"></i><?php echo $success_msg; ?></div>
    <?php endif; ?>
    <?php if ($comment_error): ?>
        <div class="error"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $comment_error; ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="add-comment-box">
            <h4><i class="fas fa-plus-circle mr-2"></i>Add a new comment</h4>
            <form method="post">
                <input type="hidden" name="parent_id" value="0">
                <textarea name="comment_content" rows="3" placeholder="Write your comment here... (will appear as new comment)" required></textarea>
                <button type="submit" name="add_comment" class="btn" style="margin-top: 15px;"><i class="fas fa-paper-plane mr-2"></i>Post Comment</button>
            </form>
        </div>
    <?php else: ?>
        <div class="message" style="background: rgba(158, 193, 215, 0.15);">To add a comment, please <a href="../login.php" style="color: #65403a; font-weight: 700;">login</a>.</div>
    <?php endif; ?>

    <?php if (!empty($top_comments)): ?>
        <div id="comments-section">
            <?php foreach ($top_comments as $root): ?>
                <?php display_comment($root, $replies, $tour_id, $all_comments_mode, $reactions_stats_map, $reaction_types, $emoji_map, 0); ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-comments">
            <i class="fas fa-comment-dots"></i>
            <p>No comments on <?php echo $all_comments_mode ? 'your tours' : 'this tour'; ?> yet.</p>
        </div>
    <?php endif; ?>
</div>

<script>
    function toggleReplyForm(commentId) {
        var form = document.getElementById('reply-form-' + commentId);
        if (form.style.display === 'none' || form.style.display === '') {
            form.style.display = 'block';
        } else {
            form.style.display = 'none';
        }
    }
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
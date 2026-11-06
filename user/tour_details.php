<?php
session_start();
include 'connection.php';

$tour_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($tour_id == 0) die("Invalid tour ID.");

// جلب بيانات الرحلة مع اسم المكتب والمقاعد المتبقية
// السماح للسوبر أدمن بمشاهدة أي رحلة (لأغراض المراجعة)، أما المستخدم العادي فيشاهد المعتمدة فقط
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'super_admin') {
    $sql = "SELECT t.*, o.name AS office_name, o.id AS office_id,
                   (SELECT COALESCE(SUM(participants), 0) FROM bookings 
                    WHERE item_id = t.id AND item_type = 'tour' AND status IN ('pending', 'confirmed')) AS booked_participants
            FROM tours t LEFT JOIN offices o ON t.office_id = o.id WHERE t.id = $tour_id";
} else {
    $sql = "SELECT t.*, o.name AS office_name, o.id AS office_id,
                   (SELECT COALESCE(SUM(participants), 0) FROM bookings 
                    WHERE item_id = t.id AND item_type = 'tour' AND status IN ('pending', 'confirmed')) AS booked_participants
            FROM tours t LEFT JOIN offices o ON t.office_id = o.id 
            WHERE t.id = $tour_id AND t.status = 'approved'";
}
$res = mysqli_query($conn, $sql);
if (mysqli_num_rows($res) == 0) die("Tour not found.");
$tour = mysqli_fetch_assoc($res);
$max = $tour['max_participants'];
$booked = $tour['booked_participants'];
$seats_remaining = ($max === null) ? null : ($max - $booked);

// المحطات
$stations = [];
$stations_res = mysqli_query($conn, "SELECT * FROM tour_stations WHERE tour_id = $tour_id ORDER BY station_order");
while ($row = mysqli_fetch_assoc($stations_res)) $stations[] = $row;
$total_stations = count($stations);

// التقييمات
$avg_rating = 0; $rating_count = 0;
$rating_res = mysqli_query($conn, "SELECT AVG(rating) AS avg_rating, COUNT(*) AS count FROM ratings WHERE rateable_type = 'tour' AND rateable_id = $tour_id");
if ($rating_row = mysqli_fetch_assoc($rating_res)) { $avg_rating = round($rating_row['avg_rating'], 1); $rating_count = $rating_row['count']; }
$ratings_result = mysqli_query($conn, "SELECT r.id, r.user_id, r.rateable_type, r.rateable_id, r.rating, r.created_at, r.updated_at, u.name AS user_name FROM ratings r JOIN users u ON r.user_id = u.id WHERE r.rateable_type = 'tour' AND r.rateable_id = $tour_id ORDER BY r.created_at DESC LIMIT 10");
// التعليقات والردود
$comments = [];
$comments_res = mysqli_query($conn, "
    SELECT c.*, u.name AS user_name, u.role AS user_role
    FROM comments c
    JOIN users u ON c.user_id = u.id
    JOIN post_comments pc ON c.id = pc.comment_id
    WHERE pc.commentable_type = 'tour' AND pc.commentable_id = $tour_id
    ORDER BY c.id ASC
");
while ($row = mysqli_fetch_assoc($comments_res)) {
    $comments[] = $row;
}

// تجميع الردود
$replies = [];
$top_comments = [];
foreach ($comments as $c) {
    if ($c['parent_id'] === NULL) {
        $top_comments[] = $c;
    } else {
        $replies[$c['parent_id']][] = $c;
    }
}

// أنواع التفاعلات (نستخدم مصفوفة ثابتة للإيموجي)
$reaction_types = [];
$rt_res = mysqli_query($conn, "SELECT id, name FROM reaction_types ORDER BY id");
$emoji_map = [
    1 => '👍',   // Like
    2 => '❤️',   // Love
    3 => '🤗',   // Care
    4 => '😂',   // Haha
    5 => '😲',   // Wow
    6 => '😢',   // Sad
    7 => '😠'    // Angry
];
while ($rt = mysqli_fetch_assoc($rt_res)) {
    $rt['icon'] = $emoji_map[$rt['id']] ?? '👍';
    $reaction_types[] = $rt;
}

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
        if (!isset($reactions_stats_map[$comment_id])) {
            $reactions_stats_map[$comment_id] = [];
        }
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

function display_comment($comment, $replies, $tour_id, $reactions_stats_map, $reaction_types, $emoji_map, $level = 0) {
    $margin = $level * 30;
    $stats = getReactionsForComment($comment['id'], $reactions_stats_map, $reaction_types, $emoji_map);
    
    // التفاعلات النشطة (التي لها عدد > 0)
    $active_reactions = array_filter($stats, function($r) { return $r['cnt'] > 0; });
    
    $role_text = '';
    switch ($comment['user_role'] ?? 'user') {
        case 'super_admin': $role_text = '(Super Admin)'; break;
        case 'office_admin': $role_text = '(Office Admin)'; break;
        default: $role_text = '(User)';
    }
    ?>
    <div class="comment-card" style="margin-right: <?php echo $margin; ?>px;">
        <div class="comment-header">
            <div class="comment-user-info">
                <i class="fas fa-user-circle comment-avatar"></i>
                <div>
                    <span class="comment-user-name"><?php echo htmlspecialchars($comment['user_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="comment-user-role"><?php echo $role_text; ?></span>
                </div>
            </div>
            <div class="comment-date"><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></div>
        </div>
        <div class="comment-content"><?php echo nl2br(htmlspecialchars($comment['content'], ENT_QUOTES, 'UTF-8')); ?></div>

        <!-- منطقة التفاعلات المتعددة -->
        <div class="reactions-bar">
            <div class="reaction-wrapper">
                <?php if (empty($active_reactions)): ?>
                    <?php $default_reaction = $stats[0] ?? ['id' => 1, 'icon' => '👍', 'cnt' => 0, 'user_reacted' => 0]; ?>
                    <a href="?id=<?php echo $tour_id; ?>&react_comment=<?php echo $comment['id']; ?>&reaction_id=<?php echo $default_reaction['id']; ?>" 
                       class="main-reaction-btn <?php echo $default_reaction['user_reacted'] ? 'active' : ''; ?>">
                        <span class="emoji-icon"><?php echo $default_reaction['icon']; ?></span>
                        <span class="reaction-count"><?php echo $default_reaction['cnt']; ?></span>
                    </a>
                <?php else: ?>
                    <?php foreach ($active_reactions as $reaction): ?>
                        <a href="?id=<?php echo $tour_id; ?>&react_comment=<?php echo $comment['id']; ?>&reaction_id=<?php echo $reaction['id']; ?>" 
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
                        <a href="?id=<?php echo $tour_id; ?>&react_comment=<?php echo $comment['id']; ?>&reaction_id=<?php echo $reaction['id']; ?>" 
                           class="reaction-option <?php echo $reaction['user_reacted'] ? 'active' : ''; ?>"
                           title="<?php echo ucfirst($reaction['name']); ?>">
                            <?php echo $reaction['icon']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <button class="reply-btn" data-comment-id="<?php echo $comment['id']; ?>"><i class="fas fa-reply"></i> Reply</button>
            <?php endif; ?>
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id']): ?>
                <a href="delete_comment.php?id=<?php echo $comment['id']; ?>&tour_id=<?php echo $tour_id; ?>" class="delete-comment-btn" onclick="return confirm('Are you sure you want to delete this comment?');"><i class="fas fa-trash-alt"></i></a>
            <?php endif; ?>
        </div>

        <div class="reply-form-container" id="reply-form-<?php echo $comment['id']; ?>">
            <form method="post">
                <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                <textarea name="comment_content" rows="2" placeholder="Write your reply..."></textarea>
                <button type="submit" name="add_comment" class="btn-submit-reply">Post Reply</button>
            </form>
        </div>

        <?php if (isset($replies[$comment['id']])): ?>
            <div class="replies-section">
                <?php foreach ($replies[$comment['id']] as $child): ?>
                    <?php display_comment($child, $replies, $tour_id, $reactions_stats_map, $reaction_types, $emoji_map, $level + 1); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// معالجة إضافة تعليق
$comment_error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment'])) {
    if (!isset($_SESSION['user_id'])) {
        $comment_error = "You must login to add a comment.";
    } else {
        $user_id = $_SESSION['user_id'];
        $content = trim($_POST['comment_content']);
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
        if (empty($content)) {
            $comment_error = "Please enter comment text.";
        } else {
            $content = mysqli_real_escape_string($conn, $content);
            $parent_id_sql = ($parent_id == 0) ? 'NULL' : $parent_id;
            mysqli_begin_transaction($conn);
            try {
                $ins1 = "INSERT INTO comments (user_id, content, parent_id, created_at, updated_at) VALUES ($user_id, '$content', $parent_id_sql, NOW(), NOW())";
                if (!mysqli_query($conn, $ins1)) throw new Exception("Failed to insert comment: " . mysqli_error($conn));
                $new_id = mysqli_insert_id($conn);
                $ins2 = "INSERT INTO post_comments (comment_id, commentable_type, commentable_id, created_at) VALUES ($new_id, 'tour', $tour_id, NOW())";
                if (!mysqli_query($conn, $ins2)) throw new Exception("Failed to link comment to tour: " . mysqli_error($conn));
                mysqli_commit($conn);
                header("Location: tour_details.php?id=$tour_id&comment_success=1");
                exit();
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $comment_error = $e->getMessage();
            }
        }
    }
}

// معالجة التفاعلات
if (isset($_GET['react_comment']) && isset($_GET['reaction_id'])) {
    if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
    $comment_id = intval($_GET['react_comment']);
    $reaction_id = intval($_GET['reaction_id']);
    $user_id = $_SESSION['user_id'];
    $check = mysqli_query($conn, "SELECT id FROM comment_reactions WHERE user_id = $user_id AND comment_id = $comment_id AND reaction_type_id = $reaction_id");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "DELETE FROM comment_reactions WHERE user_id = $user_id AND comment_id = $comment_id AND reaction_type_id = $reaction_id");
    } else {
        mysqli_query($conn, "DELETE FROM comment_reactions WHERE user_id = $user_id AND comment_id = $comment_id");
        mysqli_query($conn, "INSERT INTO comment_reactions (user_id, comment_id, reaction_type_id) VALUES ($user_id, $comment_id, $reaction_id)");
    }
    header("Location: tour_details.php?id=$tour_id");
    exit();
}

// معالجة إضافة تقييم (فوري عند النقر على النجمة)
$rating_error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rating'])) {
    if (!isset($_SESSION['user_id'])) {
        $rating_error = "You must login to rate.";
    } else {
        $user_id = $_SESSION['user_id'];
        $rating_value = intval($_POST['rating']);
        if ($rating_value < 1 || $rating_value > 5) $rating_error = "Rating must be between 1 and 5.";
        else {
            $check = mysqli_query($conn, "SELECT id FROM ratings WHERE user_id = $user_id AND rateable_type = 'tour' AND rateable_id = $tour_id");
            if (mysqli_num_rows($check) > 0) $rating_error = "You have already rated this tour.";
            else {
                $insert = "INSERT INTO ratings (user_id, rateable_type, rateable_id, rating, created_at, updated_at) VALUES ($user_id, 'tour', $tour_id, $rating_value, NOW(), NOW())";
                if (mysqli_query($conn, $insert)) {
                    header("Location: tour_details.php?id=$tour_id&rating_success=1");
                    exit();
                } else $rating_error = "Error adding rating: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tour['title'], ENT_QUOTES, 'UTF-8'); ?> - TravelGram</title>
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
            max-width: 1150px;
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

        .back-link {
            display: inline-block;
            margin-bottom: 25px;
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

        .tour-image {
            width: 80%;
            max-height: 350px;
            object-fit: cover;
            display: block;
            margin: 0 auto 30px auto;
            border-radius: 30px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
            border: 3px solid white;
        }

        h1 {
            font-size: 2.3rem;
            font-weight: 800;
            color: #1b1c27;
            margin-bottom: 15px;
        }

        .tour-price {
            font-size: 2rem;
            font-weight: 700;
            color: #65403a;
            margin-bottom: 20px;
        }

        .seats-info {
            background: rgba(158, 193, 215, 0.15);
            padding: 12px 25px;
            border-radius: 50px;
            display: inline-block;
            margin: 15px 0;
            border: 1px solid rgba(158, 193, 215, 0.3);
        }
        .seats-available { color: #28a745; font-weight: bold; }
        .seats-full { color: #dc3545; font-weight: bold; }

        .tour-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            background: rgba(239, 241, 238, 0.6);
            backdrop-filter: blur(5px);
            padding: 25px;
            border-radius: 30px;
            margin: 25px 0;
            border: 1px solid rgba(158, 193, 215, 0.3);
        }
        .meta-item {
            flex: 1;
            text-align: center;
            font-size: 1rem;
            color: #1b1c27;
        }
        .meta-item i {
            color: #65403a;
            margin-right: 8px;
        }

        .description {
            line-height: 1.9;
            margin: 30px 0;
            color: #2d3a4a;
            font-size: 1.05rem;
        }

        .btn-book {
            background: #65403a;
            color: white;
            padding: 14px 35px;
            border: none;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
            box-shadow: 0 8px 25px rgba(101, 64, 58, 0.25);
        }
        .btn-book:hover {
            background: #4f322c;
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(101, 64, 58, 0.35);
        }

        /* خريطة الرحلة */
        .journey-map {
            margin: 50px 0;
            background: rgba(239, 241, 238, 0.6);
            backdrop-filter: blur(5px);
            border-radius: 40px;
            padding: 35px 25px;
            border: 1px solid rgba(158, 193, 215, 0.3);
            position: relative;
            min-height: 550px;
        }
        .map-title {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: #65403a;
            margin-bottom: 30px;
        }
        .stations-container {
            position: relative;
            width: 100%;
            min-height: 500px;
        }
        .station-item {
            position: absolute;
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            transition: transform 0.2s;
            z-index: 5;
            width: 140px;
            text-align: center;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(5px);
            border-radius: 25px;
            padding: 10px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
            border: 1px solid rgba(158, 193, 215, 0.4);
        }
        .station-img {
            width: 85px;
            height: 85px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #65403a;
            background: #fff;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 8px;
        }
        .station-item:hover .station-img {
            transform: scale(1.05);
            border-color: #9ec1d7;
        }
        .station-number {
            background: #65403a;
            color: white;
            border-radius: 30px;
            padding: 2px 12px;
            font-size: 0.75rem;
            font-weight: bold;
            margin: 5px 0;
            display: inline-block;
        }
        .station-name {
            font-weight: 700;
            font-size: 0.9rem;
            color: #1b1c27;
            margin: 4px 0;
        }
        .station-desc {
            font-size: 0.75rem;
            color: #5a6a7a;
            line-height: 1.4;
            margin: 4px 0;
        }
        .station-extra {
            font-size: 0.7rem;
            color: #65403a;
            font-style: italic;
            margin-top: 4px;
        }
        .footprint-path {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 2;
        }
        .footprint {
            position: absolute;
            color: #65403a;
            font-size: 14px;
            opacity: 0.4;
            transform-origin: center;
        }

        /* التقييمات والتعليقات */
        .rating-summary {
            background: rgba(239, 241, 238, 0.6);
            backdrop-filter: blur(5px);
            padding: 25px;
            border-radius: 30px;
            text-align: center;
            margin: 40px 0;
            border: 1px solid rgba(158, 193, 215, 0.3);
        }
        .rating-average { font-size: 2.5rem; font-weight: 800; color: #65403a; margin-right: 15px; }
        .rating-stars { color: #f0ad4e; font-size: 1.4rem; }
        .rating-stars i { margin: 0 2px; }

        .rating-card {
            background: rgba(255,255,255,0.6);
            backdrop-filter: blur(5px);
            border-radius: 25px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 5px solid #65403a;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
        }
        .rating-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .rating-user { font-weight: 700; color: #1b1c27; }
        .rating-date { color: #5a6a7a; font-size: 0.85rem; }

        .comments-section { margin-top: 40px; }
        .comment-card {
            background: rgba(255,255,255,0.6);
            backdrop-filter: blur(5px);
            border-radius: 30px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.03);
            border: 1px solid rgba(158, 193, 215, 0.2);
            transition: 0.2s;
        }
        .comment-card:hover { border-color: #9ec1d7; background: rgba(255,255,255,0.8); }
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .comment-user-info { display: flex; align-items: center; gap: 12px; }
        .comment-avatar { font-size: 36px; color: #65403a; }
        .comment-user-name { font-weight: 700; color: #1b1c27; }
        .comment-user-role { font-size: 0.75rem; color: #5a6a7a; margin-left: 8px; }
        .comment-date { font-size: 0.85rem; color: #5a6a7a; }
        .comment-content { margin: 15px 0; line-height: 1.7; color: #2d3a4a; }
        
        /* نظام التفاعلات */
        .reactions-bar {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        .reaction-wrapper {
            display: inline-flex;
            align-items: center;
            gap: 4px;
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

        .reply-btn, .delete-comment-btn {
            background: none;
            border: none;
            color: #65403a;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 15px;
        }
        .reply-btn:hover, .delete-comment-btn:hover { color: #4f322c; }
        .reply-form-container { display: none; margin-top: 20px; }
        .reply-form-container textarea {
            width: 100%;
            padding: 12px 18px;
            border-radius: 25px;
            border: 1.5px solid rgba(158, 193, 215, 0.4);
            resize: vertical;
            background: white;
            font-family: inherit;
        }
        .btn-submit-reply {
            background: #65403a;
            color: white;
            border: none;
            padding: 8px 22px;
            border-radius: 40px;
            margin-top: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-submit-reply:hover { background: #4f322c; }
        .replies-section {
            margin-top: 20px;
            padding-right: 25px;
            border-right: 2px solid rgba(158, 193, 215, 0.4);
        }
        .add-comment-box {
            background: rgba(239, 241, 238, 0.6);
            backdrop-filter: blur(5px);
            padding: 25px;
            border-radius: 30px;
            margin: 30px 0;
            border: 1px solid rgba(158, 193, 215, 0.3);
        }
        .add-comment-box textarea {
            width: 100%;
            padding: 15px 20px;
            border-radius: 25px;
            border: 1.5px solid rgba(158, 193, 215, 0.4);
            resize: vertical;
            background: white;
            font-family: inherit;
        }
        .btn-submit {
            background: #65403a;
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 40px;
            cursor: pointer;
            margin-top: 15px;
            font-weight: 700;
            transition: 0.3s;
            box-shadow: 0 6px 15px rgba(101, 64, 58, 0.2);
        }
        .btn-submit:hover { background: #4f322c; transform: translateY(-2px); }

        .message, .error {
            padding: 15px 20px;
            border-radius: 40px;
            margin-bottom: 25px;
            backdrop-filter: blur(8px);
        }
        .message { background: rgba(40, 167, 69, 0.1); color: #155724; border-left: 5px solid #28a745; }
        .error { background: rgba(220, 53, 69, 0.1); color: #b91c1c; border-left: 5px solid #b91c1c; }

        /* تقييم النجوم */
        .star-rating {
            direction: ltr;
            display: inline-flex;
            gap: 8px;
        }
        .star-rating input { display: none; }
        .star-rating label {
            font-size: 2.2rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #f0ad4e;
        }

        @media (max-width: 768px) {
            .container { padding: 20px 18px; }
            .tour-image { width: 95%; }
            h1 { font-size: 1.8rem; }
            .tour-meta { flex-direction: column; gap: 10px; }
            .station-img { width: 65px; height: 65px; }
            .station-item { width: 120px; }
            .journey-map { min-height: 500px; }
        }
    </style>
</head>
<body>
<!-- خلفية -->
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<div class="container">
    <a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Home</a>

    <?php if (!empty($tour['main_image'])): ?>
        <img src="../<?php echo $tour['main_image']; ?>" class="tour-image" alt="Tour image">
    <?php else: ?>
        <div class="tour-image" style="background: #eee; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-image" style="font-size: 48px; color: #ccc;"></i>
        </div>
    <?php endif; ?>

    <h1><?php echo htmlspecialchars($tour['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="tour-price"><?php echo number_format($tour['price'], 2); ?> <?php echo $tour['currency']; ?></div>

    <?php if ($max !== null): ?>
        <div class="seats-info">
            <?php if ($seats_remaining > 0): ?>
                <span class="seats-available"><i class="fas fa-chair"></i> Remaining seats: <?php echo $seats_remaining; ?> / <?php echo $max; ?></span>
            <?php else: ?>
                <span class="seats-full"><i class="fas fa-ban"></i> Fully booked - no seats available</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="tour-meta">
        <div class="meta-item"><i class="fas fa-calendar-alt"></i> Duration: <?php echo $tour['duration_days'] ?? 'Not specified'; ?> days</div>
        <div class="meta-item"><i class="fas fa-calendar-check"></i> Start: <?php echo $tour['start_date'] ?? 'Not specified'; ?></div>
        <div class="meta-item"><i class="fas fa-calendar-times"></i> End: <?php echo $tour['end_date'] ?? 'Not specified'; ?></div>
        <div class="meta-item"><i class="fas fa-clock"></i> Booking deadline: <?php echo $tour['booking_deadline'] ?? 'Not specified'; ?></div>
        <div class="meta-item"><i class="fas fa-credit-card"></i> Payment: 
            <?php
            $payment_labels = ['cash' => 'Cash', 'card' => 'Card', 'bank' => 'Bank Transfer', 'online' => 'Online Payment'];
            echo $payment_labels[$tour['payment_type']] ?? $tour['payment_type'] ?? 'Not specified';
            ?>
        </div>
    </div>

    <div class="description">
        <?php echo nl2br(htmlspecialchars($tour['description'] ?? 'No description', ENT_QUOTES, 'UTF-8')); ?>
    </div>

    <?php if (($max === null || $seats_remaining > 0) && (empty($tour['booking_deadline']) || strtotime($tour['booking_deadline']) >= time())): ?>
        <div style="text-align: center;">
            <a href="book_tour.php?id=<?php echo $tour_id; ?>" class="btn-book">Book Now</a>
        </div>
    <?php else: ?>
        <div style="text-align: center;">
            <span class="btn-book" style="background:#aaa; cursor:not-allowed; box-shadow:none;">Booking Unavailable</span>
        </div>
    <?php endif; ?>

    <!-- خريطة الرحلة -->
    <?php if ($total_stations > 0): ?>
    <div class="journey-map">
        <div class="map-title"><i class="fas fa-route"></i> Tour Journey</div>
        <div class="stations-container" id="stationsContainer">
            <?php foreach ($stations as $index => $station): ?>
                <div class="station-item" data-index="<?php echo $index; ?>">
                    <img src="../<?php echo $station['image_path']; ?>" class="station-img" alt="Station image">
                    <div class="station-number">Stop #<?php echo $index + 1; ?></div>
                    <div class="station-name"><?php echo htmlspecialchars($station['custom_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php if (!empty($station['description'])): ?>
                        <div class="station-desc"><?php echo nl2br(htmlspecialchars($station['description'], ENT_QUOTES, 'UTF-8')); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($station['extra_info'])): ?>
                        <div class="station-extra"><i class="fas fa-info-circle"></i> <?php echo nl2br(htmlspecialchars($station['extra_info'], ENT_QUOTES, 'UTF-8')); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="footprint-path" id="footprintPath"></div>
    </div>
    <script>
        window.addEventListener('load', function() {
            const container = document.getElementById('stationsContainer');
            const items = document.querySelectorAll('.station-item');
            if (items.length === 0) return;
            const rect = container.getBoundingClientRect();
            const w = rect.width, h = rect.height;
            const startX = 80;
            const endX = w - 80;
            const stepX = (endX - startX) / (items.length - 1);
            const amplitude = Math.min(90, h * 0.3);
            const frequency = 1.8;
            
            for (let i = 0; i < items.length; i++) {
                let x = startX + i * stepX;
                let y = h / 2 + Math.sin(i * Math.PI / frequency) * amplitude;
                y = Math.min(h - 110, Math.max(70, y));
                items[i].style.left = (x - 70) + 'px';
                items[i].style.top = (y - 80) + 'px';
            }
            
            const footprintDiv = document.getElementById('footprintPath');
            footprintDiv.innerHTML = '';
            const points = [];
            for (let i = 0; i < items.length; i++) {
                let x = startX + i * stepX;
                let y = h / 2 + Math.sin(i * Math.PI / frequency) * amplitude;
                y = Math.min(h - 110, Math.max(70, y));
                points.push({x: x, y: y});
            }
            for (let i = 0; i < points.length - 1; i++) {
                const p1 = points[i];
                const p2 = points[i + 1];
                const cpX = (p1.x + p2.x) / 2;
                const cpY = (p1.y + p2.y) / 2 + (i % 2 === 0 ? 30 : -30);
                const distance = Math.hypot(p2.x - p1.x, p2.y - p1.y);
                const numFootprints = Math.max(6, Math.floor(distance / 25));
                for (let f = 1; f <= numFootprints; f++) {
                    const t = f / (numFootprints + 1);
                    const x = Math.pow(1-t,2) * p1.x + 2*(1-t)*t * cpX + Math.pow(t,2) * p2.x;
                    const y = Math.pow(1-t,2) * p1.y + 2*(1-t)*t * cpY + Math.pow(t,2) * p2.y;
                    let angle = 0;
                    if (t < 0.99) {
                        const t2 = t + 0.05;
                        const x2 = Math.pow(1-t2,2)*p1.x + 2*(1-t2)*t2*cpX + Math.pow(t2,2)*p2.x;
                        const y2 = Math.pow(1-t2,2)*p1.y + 2*(1-t2)*t2*cpY + Math.pow(t2,2)*p2.y;
                        angle = Math.atan2(y2 - y, x2 - x) * 180 / Math.PI;
                    }
                    const footprint = document.createElement('i');
                    footprint.className = 'fas fa-shoe-prints footprint';
                    footprint.style.left = x + 'px';
                    footprint.style.top = y + 'px';
                    footprint.style.transform = 'rotate(' + angle + 'deg)';
                    footprintDiv.appendChild(footprint);
                }
            }
        });
    </script>
    <?php endif; ?>

    <!-- ملخص التقييم -->
    <div class="rating-summary">
        <span class="rating-average"><?php echo number_format($avg_rating, 1); ?></span>
        <span class="rating-stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fas fa-star<?php echo $i <= round($avg_rating) ? '' : '-o'; ?>"></i>
            <?php endfor; ?>
        </span>
        <span style="color: #5a6a7a; margin-left: 10px;">(<?php echo $rating_count; ?> ratings)</span>
    </div>

    <?php if (isset($_GET['rating_success'])): ?>
        <div class="message"><i class="fas fa-check-circle mr-2"></i>Thank you! Your rating has been added.</div>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id'])): ?>
        <div style="background: rgba(239, 241, 238, 0.6); backdrop-filter: blur(5px); padding: 25px; border-radius: 30px; margin: 30px 0; border: 1px solid rgba(158, 193, 215, 0.3);">
            <h3 style="color: #1b1c27; margin-bottom: 15px;">Rate this tour</h3>
            <?php if ($rating_error): ?>
                <div class="error"><?php echo $rating_error; ?></div>
            <?php endif; ?>
            <form method="post" id="rating-form">
                <div class="star-rating">
                    <input type="radio" id="star5" name="rating" value="5" /><label for="star5">★</label>
                    <input type="radio" id="star4" name="rating" value="4" /><label for="star4">★</label>
                    <input type="radio" id="star3" name="rating" value="3" /><label for="star3">★</label>
                    <input type="radio" id="star2" name="rating" value="2" /><label for="star2">★</label>
                    <input type="radio" id="star1" name="rating" value="1" /><label for="star1">★</label>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="message" style="background: rgba(158, 193, 215, 0.15);">To add a rating, please <a href="../login.php" style="color:#65403a; font-weight:700;">login</a>.</div>
    <?php endif; ?>

    <!-- التقييمات السابقة -->
    <div class="ratings-section">
        <?php if (mysqli_num_rows($ratings_result) > 0): ?>
            <h3 style="color: #1b1c27; margin: 30px 0 20px;">User Ratings</h3>
            <?php while ($rating = mysqli_fetch_assoc($ratings_result)): ?>
                <div class="rating-card">
                    <div class="rating-header">
                        <span class="rating-user"><?php echo htmlspecialchars($rating['user_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="rating-date"><?php echo date('Y-m-d', strtotime($rating['created_at'])); ?></span>
                    </div>
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star<?php echo $i <= $rating['rating'] ? '' : '-o'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color: #5a6a7a; margin: 20px 0;">No ratings yet. Be the first to rate this tour!</p>
        <?php endif; ?>
    </div>

    <!-- التعليقات -->
    <div class="comments-section">
        <h3 style="color: #1b1c27; margin-bottom: 20px;">Comments</h3>
        <?php if (isset($_GET['comment_success'])): ?>
            <div class="message"><i class="fas fa-check-circle mr-2"></i>Your comment has been added.</div>
        <?php endif; ?>
        <?php if ($comment_error): ?>
            <div class="error"><?php echo $comment_error; ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="add-comment-box">
                <h4 style="color: #1b1c27; margin-bottom: 15px;">Add a new comment</h4>
                <form method="post">
                    <input type="hidden" name="parent_id" value="0">
                    <textarea name="comment_content" rows="3" placeholder="Write your comment here..." required></textarea>
                    <button type="submit" name="add_comment" class="btn-submit">Post Comment</button>
                </form>
            </div>
        <?php else: ?>
            <div class="message" style="background: rgba(158, 193, 215, 0.15);">To add a comment, please <a href="../login.php" style="color:#65403a; font-weight:700;">login</a>.</div>
        <?php endif; ?>

        <?php if (!empty($top_comments)): ?>
            <?php foreach ($top_comments as $root): ?>
                <?php display_comment($root, $replies, $tour_id, $reactions_stats_map, $reaction_types, $emoji_map, 0); ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #5a6a7a;">No comments yet. Be the first to comment!</p>
        <?php endif; ?>
    </div>
</div>

<script>
    document.querySelectorAll('.reply-btn').forEach(button => {
        button.addEventListener('click', function() {
            const commentId = this.getAttribute('data-comment-id');
            const form = document.getElementById(`reply-form-${commentId}`);
            if (form) {
                form.style.display = form.style.display === 'none' ? 'block' : 'none';
            }
        });
    });

    const starRadios = document.querySelectorAll('.star-rating input');
    starRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('rating-form').submit();
            }
        });
    });
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
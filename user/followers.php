<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$current_user_id = $_SESSION['user_id'];
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $current_user_id;

// Get displayed user name
$user_q = mysqli_query($conn, "SELECT name FROM users WHERE id = $user_id");
if (mysqli_num_rows($user_q) == 0) die("User doesn't exist");
$user_name = mysqli_fetch_assoc($user_q)['name'];

// Get list of followers (people who follow this user)
$followers = [];
$followers_res = mysqli_query($conn, "SELECT follower_id FROM follows WHERE followable_type = 'user' AND followable_id = $user_id");
while ($row = mysqli_fetch_assoc($followers_res)) {
    $uid = $row['follower_id'];
    $user_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, name, avatar FROM users WHERE id = $uid"));
    if ($user_data) $followers[] = $user_data;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Followers - <?php echo htmlspecialchars($user_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #fafafa; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: auto; background: #fff; border-radius: 20px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .back-link { display: inline-block; margin-bottom: 20px; color: #0095f6; text-decoration: none; }
        .item { display: flex; align-items: center; gap: 15px; padding: 12px 0; border-bottom: 1px solid #efefef; }
        .avatar-link { display: flex; align-items: center; text-decoration: none; color: inherit; flex: 0 0 auto; }
        .avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; background: #dbdbdb; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 18px; }
        .info { flex: 1; }
        .info a { text-decoration: none; color: inherit; display: block; }
        .name { font-weight: 600; margin-bottom: 2px; }
        .btn-follow, .btn-unfollow, .btn-remove { background: #0095f6; color: white; border: none; padding: 4px 16px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 500; }
        .btn-unfollow, .btn-remove { background: #efefef; color: #262626; border: 1px solid #dbdbdb; }
        .no-data { text-align: center; padding: 30px; color: #8e8e8e; }
        .no-data i { font-size: 48px; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="container">
    <a href="profile.php?user_id=<?php echo $user_id; ?>" class="back-link"><i class="fas fa-arrow-left"></i> Back to Profile</a>
    <h2>Followers</h2>
    <p style="color: #8e8e8e; margin-bottom: 15px;">@<?php echo htmlspecialchars($user_name); ?></p>

    <?php if (empty($followers)): ?>
        <div class="no-data">
            <i class="fas fa-user-friends"></i>
            <p>No followers yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($followers as $f): ?>
            <div class="item">
                <a href="user_view.php?user_id=<?php echo $f['id']; ?>" class="avatar-link">
                    <div class="avatar">
                        <?php if (!empty($f['avatar'])): ?>
                            <img src="<?php echo $f['avatar']; ?>" style="width:44px;height:44px;border-radius:50%;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($f['name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                </a>
                <div class="info">
                    <a href="user_view.php?user_id=<?php echo $f['id']; ?>">
                        <div class="name"><?php echo htmlspecialchars($f['name']); ?></div>
                    </a>
                </div>
                <?php if ($user_id == $current_user_id): ?>
                    <!-- User viewing their own profile: show remove button -->
                    <form method="post" action="follow.php" onsubmit="return confirm('Are you sure?');">
                        <input type="hidden" name="type" value="user">
                        <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                        <input type="hidden" name="action" value="remove_follower">
                        <button type="submit" class="btn-remove">Remove</button>
                    </form>
                <?php else: ?>
                    <!-- User viewing someone else's profile: show follow/unfollow button -->
                    <?php
                    $check = mysqli_query($conn, "SELECT id FROM follows WHERE follower_id = $current_user_id AND followable_type = 'user' AND followable_id = " . $f['id']);
                    $is_following = mysqli_num_rows($check) > 0;
                    ?>
                    <form method="post" action="follow.php">
                        <input type="hidden" name="type" value="user">
                        <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                        <input type="hidden" name="action" value="<?php echo $is_following ? 'unfollow' : 'follow'; ?>">
                        <button type="submit" class="<?php echo $is_following ? 'btn-unfollow' : 'btn-follow'; ?>">
                            <?php echo $is_following ? 'Unfollow' : 'Follow'; ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
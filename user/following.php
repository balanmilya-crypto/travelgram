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

// Get list of followed accounts (users and offices)
$following = [];
$following_res = mysqli_query($conn, "SELECT followable_type, followable_id FROM follows WHERE follower_id = $user_id");
while ($row = mysqli_fetch_assoc($following_res)) {
    if ($row['followable_type'] == 'user') {
        $uid = $row['followable_id'];
        $user_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, name, avatar FROM users WHERE id = $uid"));
        if ($user_data) $following[] = ['type' => 'user', 'data' => $user_data];
    } elseif ($row['followable_type'] == 'office') {
        $oid = $row['followable_id'];
        $office_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, name, logo FROM offices WHERE id = $oid"));
        if ($office_data) $following[] = ['type' => 'office', 'data' => $office_data];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Following - <?php echo htmlspecialchars($user_name); ?></title>
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
        .type { font-size: 12px; color: #8e8e8e; }
        .btn-unfollow { background: #efefef; border: 1px solid #dbdbdb; padding: 4px 16px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 500; color: #262626; }
        .no-data { text-align: center; padding: 30px; color: #8e8e8e; }
        .no-data i { font-size: 48px; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="container">
    <a href="profile.php?user_id=<?php echo $user_id; ?>" class="back-link"><i class="fas fa-arrow-left"></i> Back to Profile</a>
    <h2>Following</h2>
    <p style="color: #8e8e8e; margin-bottom: 15px;">@<?php echo htmlspecialchars($user_name); ?></p>

    <?php if (empty($following)): ?>
        <div class="no-data">
            <i class="fas fa-user-plus"></i>
            <p>Not following anyone yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($following as $f): 
            // Determine target link
            if ($f['type'] == 'user') {
                $profile_link = "user_view.php?user_id=" . $f['data']['id'];
            } else {
                $profile_link = "office_details.php?id=" . $f['data']['id']; // Make sure this file exists
            }
        ?>
            <div class="item">
                <a href="<?php echo $profile_link; ?>" class="avatar-link">
                    <div class="avatar">
                        <?php if ($f['type'] == 'user'): ?>
                            <?php if (!empty($f['data']['avatar'])): ?>
                                <img src="<?php echo $f['data']['avatar']; ?>" style="width:44px;height:44px;border-radius:50%;">
                            <?php else: ?>
                                <?php echo strtoupper(substr($f['data']['name'], 0, 1)); ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (!empty($f['data']['logo'])): ?>
                                <img src="../<?php echo $f['data']['logo']; ?>" style="width:44px;height:44px;border-radius:50%;">
                            <?php else: ?>
                                <i class="fas fa-building"></i>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </a>
                <div class="info">
                    <a href="<?php echo $profile_link; ?>">
                        <div class="name"><?php echo htmlspecialchars($f['data']['name']); ?></div>
                        <div class="type"><?php echo $f['type'] == 'user' ? 'User' : 'Travel Office'; ?></div>
                    </a>
                </div>
                <?php if ($user_id == $current_user_id): ?>
                    <form method="post" action="follow.php" onsubmit="return confirm('Are you sure?');">
                        <input type="hidden" name="type" value="<?php echo $f['type']; ?>">
                        <input type="hidden" name="id" value="<?php echo $f['data']['id']; ?>">
                        <input type="hidden" name="action" value="unfollow">
                        <button type="submit" class="btn-unfollow">Unfollow</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
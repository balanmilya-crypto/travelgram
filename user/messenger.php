<?php
session_start();
include('connection.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$my_id = $_SESSION['user_id'];
$user_query = "SELECT name, role, office_id, avatar FROM users WHERE id = $my_id";
$user_result = mysqli_query($conn, $user_query);
$my_data = mysqli_fetch_assoc($user_result);
$my_role = $my_data['role'];

if ($my_role == 'super_admin') {
    $back_link = '../admin/dashboard.php';
} elseif ($my_role == 'office_admin') {
    $back_link = '../office/dashboard.php';
} else {
    $back_link = '../index.php';
}

// حذف رسالة مفردة (AJAX)
if (isset($_GET['delete_message']) && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $msg_id = intval($_GET['delete_message']);
    $check = mysqli_query($conn, "SELECT id, attachment FROM messages WHERE id = $msg_id AND sender_id = $my_id");
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        if (!empty($row['attachment']) && file_exists('../' . $row['attachment'])) {
            unlink('../' . $row['attachment']);
        }
        mysqli_query($conn, "DELETE FROM messages WHERE id = $msg_id");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Not authorized']);
    }
    exit();
}

// حذف المحادثة من جهتي فقط
if (isset($_GET['delete_conversation'])) {
    $other_id = intval($_GET['delete_conversation']);
    $stmt = $conn->prepare("DELETE FROM deleted_conversations WHERE user_id = ? AND other_user_id = ?");
    $stmt->bind_param("ii", $my_id, $other_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO deleted_conversations (user_id, other_user_id, deleted_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $my_id, $other_id);
    $stmt->execute();
    $stmt->close();

    header("Location: messenger.php");
    exit();
}

$upload_dir = '../uploads/messenger/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// إرسال رسالة جديدة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $recipient_id = intval($_POST['recipient_id']);
    $message = isset($_POST['message']) ? mysqli_real_escape_string($conn, trim($_POST['message'])) : '';
    $attachment_path = '';

    // مرفق عادي
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0 && !empty($_FILES['attachment']['name'])) {
        $file = $_FILES['attachment'];
        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9\.\_\-]/', '', basename($file['name']));
        $target_file = $upload_dir . $filename;
        $ext = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'];
        if (in_array($ext, $allowed) && $file['size'] < 10 * 1024 * 1024) {
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $attachment_path = 'uploads/messenger/' . $filename;
            }
        }
    }

    // ملف صوتي
    if (isset($_FILES['voice_message']) && $_FILES['voice_message']['error'] == 0) {
        $voice = $_FILES['voice_message'];
        $voice_ext = 'webm';
        $voice_filename = time() . '_voice.' . $voice_ext;
        $target_voice = $upload_dir . $voice_filename;
        if (move_uploaded_file($voice['tmp_name'], $target_voice)) {
            $attachment_path = 'uploads/messenger/' . $voice_filename;
        }
    }

    if (!empty($message) || !empty($attachment_path)) {
        $recipient_type = 'user';
        $att_sql = $attachment_path ? "'$attachment_path'" : 'NULL';
        $query = "INSERT INTO messages (sender_id, recipient_id, recipient_type, message, attachment, is_read, created_at) 
                  VALUES ($my_id, $recipient_id, '$recipient_type', '$message', $att_sql, 0, NOW())";
        mysqli_query($conn, $query);
    }
    header("Location: messenger.php?chat_with=$recipient_id");
    exit();
}

$chat_with = isset($_GET['chat_with']) ? intval($_GET['chat_with']) : 0;
if ($chat_with > 0) {
    mysqli_query($conn, "UPDATE messages SET is_read = 1 WHERE sender_id = $chat_with AND recipient_id = $my_id AND is_read = 0");
}

// بناء قائمة جهات الاتصال (نفس المنطق السابق)
$contacts = [];
$sql_contacts = "
    SELECT DISTINCT other_id
    FROM (
        SELECT recipient_id AS other_id FROM messages WHERE sender_id = $my_id
        UNION
        SELECT sender_id AS other_id FROM messages WHERE recipient_id = $my_id
    ) AS interacted
    WHERE other_id NOT IN (
        SELECT d.other_user_id 
        FROM deleted_conversations d
        WHERE d.user_id = $my_id
          AND NOT EXISTS (
              SELECT 1 FROM messages m
              WHERE m.sender_id = d.other_user_id 
                AND m.recipient_id = $my_id 
                AND m.created_at > d.deleted_at
          )
    )
";
$res = mysqli_query($conn, $sql_contacts);
$other_ids = [];
while ($row = mysqli_fetch_assoc($res)) $other_ids[] = $row['other_id'];

if (!empty($other_ids)) {
    $ids_str = implode(',', $other_ids);
    $users_sql = "SELECT id, name, role, office_id, avatar, 'user' AS type, id AS target_user_id 
                  FROM users WHERE id IN ($ids_str)";
    $users_res = mysqli_query($conn, $users_sql);
    while ($u = mysqli_fetch_assoc($users_res)) {
        $u['display_name'] = $u['name'];
        $u['unread_count'] = 0;
        $u['last_time'] = null;
        if ($u['role'] == 'office_admin' && !empty($u['office_id'])) {
            $off_q = mysqli_query($conn, "SELECT name, logo FROM offices WHERE id = {$u['office_id']}");
            if ($off = mysqli_fetch_assoc($off_q)) {
                $u['display_name'] = $off['name'] . " (Office)";
                $u['avatar'] = $off['logo'];
                $u['type'] = 'office';
            }
        }
        $contacts[$u['id']] = $u;
    }

    foreach ($contacts as &$c) {
        $target_id = $c['id'];
        $unread = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM messages WHERE sender_id = $target_id AND recipient_id = $my_id AND is_read = 0");
        if ($row = mysqli_fetch_assoc($unread)) $c['unread_count'] = $row['cnt'];
        $last = mysqli_query($conn, "
            SELECT created_at FROM messages 
            WHERE (sender_id = $my_id AND recipient_id = $target_id)
               OR (sender_id = $target_id AND recipient_id = $my_id)
            ORDER BY created_at DESC LIMIT 1
        ");
        if ($row = mysqli_fetch_assoc($last)) $c['last_time'] = $row['created_at'];
    }
    unset($c);
    $contacts = array_values($contacts);
    usort($contacts, function($a, $b) {
        if ($a['unread_count'] > 0 && $b['unread_count'] == 0) return -1;
        if ($b['unread_count'] > 0 && $a['unread_count'] == 0) return 1;
        if ($a['unread_count'] > 0 && $b['unread_count'] > 0) return $b['unread_count'] - $a['unread_count'];
        if ($a['last_time'] && $b['last_time']) return strtotime($b['last_time']) - strtotime($a['last_time']);
        if ($a['last_time']) return -1;
        if ($b['last_time']) return 1;
        return strcasecmp($a['display_name'], $b['display_name']);
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messenger - TravelGram</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cairo', 'Poppins', sans-serif; background: #eff1ee; color: #1b1c27; padding: 20px; height: 100vh; overflow: hidden; position: relative; }
        .bg-bubble { position: fixed; border-radius: 50%; background: rgba(101, 64, 58, 0.05); z-index: 0; pointer-events: none; animation: float 12s infinite alternate ease-in-out; }
        .bg-bubble:nth-child(1) { width: 300px; height: 300px; top: -50px; left: -50px; background: rgba(158, 193, 215, 0.08); animation-duration: 15s; }
        .bg-bubble:nth-child(2) { width: 500px; height: 500px; bottom: -100px; right: -100px; background: rgba(101, 64, 58, 0.06); animation-duration: 18s; }
        .bg-bubble:nth-child(3) { width: 200px; height: 200px; top: 40%; left: 10%; background: rgba(115, 121, 59, 0.05); animation-duration: 10s; }
        .bg-bubble:nth-child(4) { width: 400px; height: 400px; bottom: 20%; left: -150px; background: rgba(158, 193, 215, 0.07); animation-duration: 20s; }
        .bg-bubble:nth-child(5) { width: 250px; height: 250px; top: 15%; right: 5%; background: rgba(101, 64, 58, 0.04); animation-duration: 14s; }
        .bg-wave { position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: 0; pointer-events: none; background: url('data:image/svg+xml;utf8,<svg width="100%" height="100%" viewBox="0 0 1200 800" xmlns="http://www.w3.org/2000/svg"><path d="M0,400 Q300,200 600,400 T1200,300 L1200,800 L0,800 Z" fill="rgba(158,193,215,0.05)"/><path d="M0,600 Q400,450 800,600 T1200,500 L1200,800 L0,800 Z" fill="rgba(101,64,58,0.03)"/></svg>'); background-size: cover; opacity: 0.5; }
        @keyframes float { 0% { transform: translateY(0px) scale(1); } 100% { transform: translateY(-30px) scale(1.05); } }
        .messenger-container { display: flex; max-width: 1400px; margin: 0 auto; background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(15px); border-radius: 40px; overflow: hidden; box-shadow: 0 30px 50px rgba(27, 28, 39, 0.1); border: 1px solid rgba(255, 255, 255, 0.5); height: calc(100vh - 40px); position: relative; z-index: 10; }
        .contacts-side { width: 340px; background: rgba(239, 241, 238, 0.5); backdrop-filter: blur(5px); border-right: 1px solid rgba(158, 193, 215, 0.3); display: flex; flex-direction: column; overflow-y: auto; flex-shrink: 0; }
        .contacts-header { background: linear-gradient(145deg, #65403a, #4f322c); color: white; padding: 22px 20px; font-weight: 700; font-size: 1.3rem; position: sticky; top: 0; z-index: 10; border-bottom: 2px solid #9ec1d7; }
        .discover-link { display: block; padding: 12px 20px; background: rgba(158, 193, 215, 0.2); border-bottom: 1px solid rgba(158, 193, 215, 0.3); color: #65403a; text-decoration: none; font-weight: 600; transition: 0.2s; }
        .discover-link:hover { background: rgba(158, 193, 215, 0.4); }
        .discover-link i { margin-right: 10px; }
        .contacts-list { flex: 1; overflow-y: auto; }
        .contact-item { padding: 16px 20px; border-bottom: 1px solid rgba(158, 193, 215, 0.2); cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 15px; }
        .contact-item:hover { background: rgba(101, 64, 58, 0.08); }
        .contact-item.active { background: rgba(101, 64, 58, 0.12); border-left: 5px solid #65403a; }
        .avatar-img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #9ec1d7; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .avatar-placeholder { width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(145deg, #65403a, #9ec1d7); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 22px; border: 2px solid #9ec1d7; }
        .contact-info { flex: 1; min-width: 0; }
        .contact-name { font-weight: 700; color: #1b1c27; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .contact-role { font-size: 13px; color: #5a6a7a; margin-top: 5px; }
        .notification-badge { background-color: #ed4956; color: white; border-radius: 50%; padding: 3px 8px; font-size: 12px; font-weight: bold; min-width: 22px; text-align: center; box-shadow: 0 3px 8px rgba(237, 73, 86, 0.3); }
        .delete-conv-btn { background: none; border: none; color: #dc3545; font-size: 14px; cursor: pointer; padding: 5px; margin-left: 5px; border-radius: 50%; transition: 0.2s; opacity: 0.6; }
        .delete-conv-btn:hover { background: rgba(220,53,69,0.1); opacity: 1; }
        .chat-side { flex: 1; display: flex; flex-direction: column; background: transparent; overflow: hidden; }
        .chat-header { background: linear-gradient(145deg, #65403a, #4f322c); color: white; padding: 18px 25px; font-weight: 700; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; border-bottom: 2px solid #9ec1d7; }
        .back-button { background: rgba(255,255,255,0.15); color: white; padding: 8px 20px; border-radius: 40px; text-decoration: none; font-size: 14px; font-weight: 600; transition: 0.3s; border: 1px solid rgba(255,255,255,0.2); }
        .back-button:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); }
        .chat-messages { flex: 1; padding: 25px; overflow-y: auto; background: rgba(239, 241, 238, 0.3); display: flex; flex-direction: column; gap: 15px; }
        .message-row { display: flex; }
        .message-row.me { justify-content: flex-end; }
        .message-row.other { justify-content: flex-start; }
        .bubble { max-width: 70%; padding: 12px 18px; border-radius: 25px; font-size: 15px; background: white; border: 1px solid rgba(158, 193, 215, 0.3); box-shadow: 0 4px 12px rgba(0,0,0,0.05); position: relative; }
        .me .bubble { background: #65403a; color: white; border: none; }
        .other .bubble strong { color: #65403a; }
        .message-time { font-size: 11px; color: #8a9aa8; margin-top: 6px; text-align: right; }
        .me .message-time { color: rgba(255,255,255,0.7); }
        .delete-msg-btn { background: none; border: none; color: rgba(255,255,255,0.6); cursor: pointer; font-size: 12px; margin-left: 8px; padding: 2px 5px; border-radius: 50%; transition: 0.2s; opacity: 0; }
        .bubble:hover .delete-msg-btn { opacity: 1; }
        .delete-msg-btn:hover { color: #ff6b6b; background: rgba(0,0,0,0.1); }
        .me .delete-msg-btn { color: rgba(255,255,255,0.7); }
        .me .delete-msg-btn:hover { color: #ffb3b3; background: rgba(255,255,255,0.1); }
        .chat-input-form { display: flex; padding: 18px 25px; background: rgba(255, 255, 255, 0.5); backdrop-filter: blur(5px); border-top: 1px solid rgba(158, 193, 215, 0.3); flex-shrink: 0; gap: 10px; align-items: center; }
        .chat-input-form input[type="text"] { flex: 1; padding: 14px 20px; border: 1.5px solid rgba(158, 193, 215, 0.4); border-radius: 50px; font-size: 15px; outline: none; transition: 0.3s; background: white; font-family: inherit; }
        .chat-input-form input[type="text"]:focus { border-color: #65403a; box-shadow: 0 0 0 4px rgba(101, 64, 58, 0.1); }
        .chat-input-form button[type="submit"] { background: #65403a; border: none; color: white; border-radius: 50px; padding: 0 30px; cursor: pointer; font-weight: 700; transition: 0.3s; box-shadow: 0 8px 20px rgba(101, 64, 58, 0.2); height: 48px; }
        .chat-input-form button[type="submit"]:hover { background: #4f322c; transform: translateY(-2px); }
        .attachment-btn, #voiceBtn { background: none; border: 1.5px solid rgba(158, 193, 215, 0.4); color: #65403a; border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 20px; transition: 0.2s; flex-shrink: 0; }
        .attachment-btn:hover, #voiceBtn:hover { background: rgba(101, 64, 58, 0.1); border-color: #65403a; }
        #file-name, #voice-file-name { font-size: 12px; color: #5a6a7a; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-left: 5px; display: none; }
        .attachment-preview img { max-width: 200px; max-height: 200px; border-radius: 15px; margin-top: 8px; cursor: pointer; border: 2px solid rgba(158,193,215,0.3); }
        .attachment-doc, .attachment-audio { display: inline-block; background: rgba(101, 64, 58, 0.1); border: 1px solid rgba(101, 64, 58, 0.2); border-radius: 20px; padding: 8px 15px; margin-top: 8px; font-size: 13px; color: #65403a; font-weight: 600; text-decoration: none; }
        .attachment-audio audio { max-width: 250px; vertical-align: middle; }
        .no-chat { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #5a6a7a; font-size: 1.2rem; background: rgba(239, 241, 238, 0.3); }
        a { text-decoration: none; }
        .contacts-side::-webkit-scrollbar, .chat-messages::-webkit-scrollbar { width: 6px; }
        .contacts-side::-webkit-scrollbar-track, .chat-messages::-webkit-scrollbar-track { background: rgba(239, 241, 238, 0.5); border-radius: 10px; }
        .contacts-side::-webkit-scrollbar-thumb, .chat-messages::-webkit-scrollbar-thumb { background: #65403a; border-radius: 10px; }
        @media (max-width: 768px) { .contacts-side { width: 280px; } }
        @media (max-width: 576px) { .contacts-side { width: 80px; } .contact-name, .contact-role { display: none; } .avatar-img, .avatar-placeholder { width: 45px; height: 45px; margin: 0 auto; } .contact-item { justify-content: center; padding: 15px; } }
    </style>
</head>
<body>
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<div class="messenger-container">
    <div class="contacts-side">
        <div class="contacts-header"><i class="fas fa-users mr-2"></i> Contacts</div>
        <a href="all_contacts.php" class="discover-link"><i class="fas fa-search"></i> Discover People & Offices</a>
        <div class="contacts-list">
            <?php foreach ($contacts as $c): 
                $image_src = '';
                $placeholder = strtoupper(mb_substr($c['display_name'], 0, 1, 'UTF-8'));
                if (!empty($c['avatar'])) {
                    $orig = $c['avatar'];
                    if (file_exists($orig)) $image_src = $orig;
                    elseif (file_exists('../' . $orig)) $image_src = '../' . $orig;
                }
                $active = ($chat_with == $c['id']) ? 'active' : '';
                $role_text = '';
                if ($c['type'] == 'user') {
                    if ($c['role'] == 'super_admin') $role_text = 'Super Admin';
                    elseif ($c['role'] == 'office_admin') $role_text = 'Office Manager';
                    else $role_text = 'Traveler';
                } else {
                    $role_text = 'Travel Office';
                }
            ?>
                <div class="contact-item <?php echo $active; ?>" onclick="location.href='messenger.php?chat_with=<?php echo $c['id']; ?>'">
                    <?php if ($image_src): ?>
                        <img src="<?php echo $image_src; ?>" class="avatar-img" alt="avatar">
                    <?php else: ?>
                        <div class="avatar-placeholder"><?php echo $placeholder; ?></div>
                    <?php endif; ?>
                    <div class="contact-info">
                        <div class="contact-name">
                            <?php echo htmlspecialchars($c['display_name']); ?>
                            <?php if ($c['unread_count'] > 0): ?>
                                <span class="notification-badge"><?php echo $c['unread_count']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="contact-role"><?php echo $role_text; ?></div>
                    </div>
                    <a href="messenger.php?delete_conversation=<?php echo $c['id']; ?>" class="delete-conv-btn" onclick="event.stopPropagation(); return confirm('Delete this conversation from your list?');" title="Delete conversation"><i class="fas fa-trash-alt"></i></a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="chat-side">
        <?php if ($chat_with == 0): ?>
            <div class="chat-header">
                <span><i class="fas fa-comment-dots mr-2"></i> Messenger</span>
                <a href="<?php echo $back_link; ?>" class="back-button"><i class="fas fa-home mr-1"></i> Back</a>
            </div>
            <div class="no-chat">
                <i class="fas fa-comments" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5; color: #65403a;"></i>
                <p>👈 Select a contact to start chatting</p>
            </div>
        <?php else:
            $other_query = "SELECT id, name, role, office_id, avatar FROM users WHERE id = $chat_with";
            $other_res = mysqli_query($conn, $other_query);
            $other = mysqli_fetch_assoc($other_res);
            $other_display = $other['name'];
            $other_image = '';
            if ($other['role'] == 'office_admin' && $other['office_id']) {
                $off = mysqli_query($conn, "SELECT name, logo FROM offices WHERE id = {$other['office_id']}");
                if ($o = mysqli_fetch_assoc($off)) {
                    $other_display = $o['name'] . " (Office)";
                    if (!empty($o['logo'])) {
                        if (file_exists($o['logo'])) $other_image = $o['logo'];
                        elseif (file_exists('../' . $o['logo'])) $other_image = '../' . $o['logo'];
                    }
                }
            } else {
                if (!empty($other['avatar']) && file_exists($other['avatar'])) {
                    $other_image = $other['avatar'];
                }
            }
        ?>
            <div class="chat-header">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <?php if ($other_image): ?>
                        <img src="<?php echo $other_image; ?>" style="width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid #9ec1d7;">
                    <?php else: ?>
                        <div style="width: 42px; height: 42px; border-radius: 50%; background: white; color: #65403a; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px;"><?php echo strtoupper(mb_substr($other_display,0,1,'UTF-8')); ?></div>
                    <?php endif; ?>
                    <span>Chat with: <?php echo htmlspecialchars($other_display); ?></span>
                </div>
                <a href="<?php echo $back_link; ?>" class="back-button"><i class="fas fa-home mr-1"></i> Back</a>
            </div>

            <div class="chat-messages" id="messagesArea">
                <?php
                $msg_query = "SELECT m.*, u.name as sender_name 
                              FROM messages m 
                              JOIN users u ON u.id = m.sender_id
                              WHERE (m.sender_id = $my_id AND m.recipient_id = $chat_with) 
                                 OR (m.sender_id = $chat_with AND m.recipient_id = $my_id)
                              ORDER BY m.created_at ASC";
                $msgs = mysqli_query($conn, $msg_query);
                while ($msg = mysqli_fetch_assoc($msgs)):
                    $is_me = ($msg['sender_id'] == $my_id);
                    $row_class = $is_me ? 'me' : 'other';
                    $time = date('H:i', strtotime($msg['created_at']));
                    $attachment = $msg['attachment'];
                ?>
                    <div class="message-row <?php echo $row_class; ?>" data-message-id="<?php echo $msg['id']; ?>">
                        <div class="bubble">
                            <?php if (!$is_me): ?>
                                <strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong><br>
                            <?php endif; ?>
                            <?php if (!empty($msg['message'])): ?>
                                <span><?php echo nl2br(htmlspecialchars($msg['message'])); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($attachment)): 
                                $ext = strtolower(pathinfo($attachment, PATHINFO_EXTENSION));
                                $src = '../' . $attachment;
                            ?>
                                <?php if (in_array($ext, ['webm', 'mp3', 'ogg', 'wav'])): ?>
                                    <div class="attachment-audio">
                                        <audio controls src="<?php echo $src; ?>" style="max-width: 250px;"></audio>
                                    </div>
                                <?php elseif (in_array($ext, ['jpg','jpeg','png','gif'])): ?>
                                    <div class="attachment-preview">
                                        <a href="<?php echo $src; ?>" target="_blank"><img src="<?php echo $src; ?>" alt="image"></a>
                                    </div>
                                <?php else: ?>
                                    <a href="<?php echo $src; ?>" class="attachment-doc" download><i class="fas fa-file"></i> Download File</a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <div class="message-time"><?php echo $time; ?></div>
                            <?php if ($is_me): ?>
                                <button class="delete-msg-btn" data-id="<?php echo $msg['id']; ?>" title="Delete message"><i class="fas fa-trash-alt"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- نموذج الإرسال مع زر الميكروفون -->
            <form class="chat-input-form" id="messageForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="recipient_id" value="<?php echo $chat_with; ?>">
                <!-- زر رفع الملفات -->
                <label class="attachment-btn" for="file-input"><i class="fas fa-paperclip"></i></label>
                <input type="file" id="file-input" name="attachment" style="display: none;" onchange="updateFileName(this, 'file-name')">
                <span id="file-name"></span>
                <!-- زر الميكروفون -->
                <button type="button" id="voiceBtn" class="attachment-btn" title="Record voice"><i class="fas fa-microphone"></i></button>
                <span id="voice-file-name"></span>
                <!-- حقل النص -->
                <input type="text" name="message" placeholder="Type your message..." autocomplete="off">
                <button type="submit" name="send_message"><i class="fas fa-paper-plane"></i> Send</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// عرض اسم الملف المرفوع
function updateFileName(input, spanId) {
    let span = document.getElementById(spanId);
    if (input.files && input.files[0]) {
        span.textContent = input.files[0].name;
        span.style.display = 'inline-block';
    } else {
        span.textContent = '';
        span.style.display = 'none';
    }
}

// تسجيل الصوت
let mediaRecorder;
let audioChunks = [];
let isRecording = false;
const voiceBtn = document.getElementById('voiceBtn');
const form = document.getElementById('messageForm');
const voiceFileNameSpan = document.getElementById('voice-file-name');

if (voiceBtn) {
    voiceBtn.addEventListener('click', async () => {
        if (!isRecording) {
            // بدء التسجيل
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                mediaRecorder.ondataavailable = e => {
                    if (e.data.size > 0) audioChunks.push(e.data);
                };
                mediaRecorder.onstop = () => {
                    // إنشاء ملف الصوت
                    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                    const file = new File([audioBlob], 'voice_message.webm', { type: 'audio/webm' });

                    // إزالة أي input سابق للصوت
                    let oldInput = form.querySelector('input[name="voice_message"]');
                    if (oldInput) oldInput.remove();

                    // إنشاء input file مخفي وإرفاق الملف
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    const fileInput = document.createElement('input');
                    fileInput.type = 'file';
                    fileInput.name = 'voice_message';
                    fileInput.files = dt.files;
                    fileInput.style.display = 'none';
                    form.appendChild(fileInput);

                    // عرض اسم الملف
                    voiceFileNameSpan.textContent = 'voice_message.webm';
                    voiceFileNameSpan.style.display = 'inline-block';

                    // تنظيف
                    audioChunks = [];
                    stream.getTracks().forEach(track => track.stop());
                };

                mediaRecorder.start();
                isRecording = true;
                voiceBtn.innerHTML = '<i class="fas fa-stop"></i>';
                voiceBtn.style.background = '#dc3545';
                voiceBtn.style.color = 'white';
            } catch (err) {
                alert('تعذر الوصول إلى الميكروفون: ' + err.message);
            }
        } else {
            // إيقاف التسجيل (سيتم تشغيل onstop تلقائياً)
            mediaRecorder.stop();
            isRecording = false;
            voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
            voiceBtn.style.background = '';
            voiceBtn.style.color = '';
        }
    });
}

// عند الضغط على زر الإرسال أثناء التسجيل، أوقف التسجيل أولاً ثم أرسل
form.addEventListener('submit', function(e) {
    if (isRecording) {
        e.preventDefault();
        // إيقاف التسجيل - سيؤدي onstop إلى إرفاق الملف، ثم نرسل النموذج يدوياً بعد فترة قصيرة
        mediaRecorder.stop();
        isRecording = false;
        voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
        voiceBtn.style.background = '';
        voiceBtn.style.color = '';
        // انتظر قليلاً حتى ينتهي onstop من إرفاق الملف ثم أرسل
        setTimeout(function() {
            form.submit();
        }, 200);
    }
});

// حذف رسالة
$(function(){
    let msgDiv = document.getElementById('messagesArea');
    if (msgDiv) msgDiv.scrollTop = msgDiv.scrollHeight;

    function deleteMessage(btn) {
        if (!confirm('Delete this message?')) return;
        let msgId = btn.data('id');
        let row = btn.closest('.message-row');
        $.ajax({
            url: 'messenger.php?delete_message=' + msgId,
            type: 'GET',
            dataType: 'json',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            success: function(r){ if (r.success) row.fadeOut(300, function(){ $(this).remove(); }); },
            error: function(){ alert('Error'); }
        });
    }

    $('.delete-msg-btn').on('click', function(e){
        e.stopPropagation();
        deleteMessage($(this));
    });
    $('#messagesArea').on('click', '.delete-msg-btn', function(e){
        e.stopPropagation();
        deleteMessage($(this));
    });
});
</script>
</body>
</html>
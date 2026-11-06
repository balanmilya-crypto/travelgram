<?php
session_start();
include 'onnection.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'غير مصرح']));
}

$current_user_id = $_SESSION['user_id'];
$contact_id = intval($_GET['contact_id']);
$contact_type = $_GET['contact_type'];
$last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;

// بناء شرط المستلم حسب نوع جهة الاتصال
if ($contact_type == 'user') {
    $recipient_condition = "(m.sender_id = $current_user_id AND m.recipient_type = 'user' AND m.recipient_id = $contact_id)
                            OR (m.sender_id = $contact_id AND m.recipient_type = 'user' AND m.recipient_id = $current_user_id)";
} elseif ($contact_type == 'office') {
    $recipient_condition = "(m.sender_id = $current_user_id AND m.recipient_type = 'office' AND m.recipient_id = $contact_id)
                            OR (m.sender_id IN (SELECT user_id FROM offices WHERE id = $contact_id) AND m.recipient_type = 'user' AND m.recipient_id = $current_user_id)";
} elseif ($contact_type == 'super_admin') {
    $recipient_condition = "(m.sender_id = $current_user_id AND m.recipient_type = 'super_admin')
                            OR (m.sender_id IN (SELECT id FROM users WHERE role = 'super_admin') AND m.recipient_type = 'user' AND m.recipient_id = $current_user_id)";
} else {
    die(json_encode(['error' => 'نوع غير صحيح']));
}

$sql = "SELECT m.*, u.name AS sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE $recipient_condition
        ORDER BY m.created_at ASC";
$result = mysqli_query($conn, $sql);
$messages = [];
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['id'] > $last_id) {
        $messages[] = [
            'id' => $row['id'],
            'sender_id' => $row['sender_id'],
            'message' => $row['message'],
            'created_at' => $row['created_at']
        ];
    }
}

echo json_encode(['messages' => $messages]);
?>
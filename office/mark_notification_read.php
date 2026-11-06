<?php
session_start();
include 'connection.php'; // تعديل المسار حسب موقع الملف

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$user_id = $_SESSION['user_id'];
$notif_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($notif_id > 0) {
    $query = "UPDATE notifications SET is_read = 1 WHERE id = $notif_id AND user_id = $user_id";
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>
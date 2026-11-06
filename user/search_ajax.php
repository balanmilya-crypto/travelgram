<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode([]));
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
if (strlen($query) < 1) {
    echo json_encode([]);
    exit();
}

$search_esc = mysqli_real_escape_string($conn, $query);
$sql = "SELECT id, name, avatar 
        FROM users 
        WHERE name LIKE '%$search_esc%' 
        ORDER BY name 
        LIMIT 10";
$result = mysqli_query($conn, $sql);

$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'avatar' => $row['avatar']
    ];
}

header('Content-Type: application/json');
echo json_encode($users);
?>
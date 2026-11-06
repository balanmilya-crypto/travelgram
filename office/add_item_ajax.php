<?php
session_start();
include 'connection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'office_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$type = $_POST['type'] ?? '';
$name = trim($_POST['name'] ?? '');
$city_id = intval($_POST['city_id'] ?? 0);

if (!in_array($type, ['hotel', 'restaurant', 'landmark'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid type']);
    exit();
}

if (empty($name) || $city_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$city_res = mysqli_query($conn, "SELECT country_id FROM cities WHERE id = $city_id");
if (!$city_res || mysqli_num_rows($city_res) == 0) {
    echo json_encode(['success' => false, 'message' => 'City not found']);
    exit();
}
$city_data = mysqli_fetch_assoc($city_res);
$country_id = $city_data['country_id'];

$table = '';
switch ($type) {
    case 'hotel': $table = 'hotels'; break;
    case 'restaurant': $table = 'restaurants'; break;
    case 'landmark': $table = 'landmarks'; break;
}

$name_esc = mysqli_real_escape_string($conn, $name);

$insert = "INSERT INTO $table (name, city_id, country_id) VALUES ('$name_esc', $city_id, $country_id)";
if (mysqli_query($conn, $insert)) {
    $new_id = mysqli_insert_id($conn);
    echo json_encode(['success' => true, 'id' => $new_id, 'name' => $name]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
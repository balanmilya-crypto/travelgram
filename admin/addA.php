<?php
include 'connection.php';

$username = 'milya';
$email = 'milyaballan1@gmail.com';
$pass = '2005';
$phone = '0984155287';
$avatar = '';
$role = 'super_admin';
$office_id = null;
$is_active = 1;
$hashed = password_hash($pass, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (name, email, password, phone, avatar, role, is_active  ) 
        VALUES ('$username', '$email', '$hashed', '$phone', '$avatar', '$role',  $is_active)";

$result = mysqli_query($conn, $sql);
if ($result) {
    header('Location: ../login.php');
    exit();
} else {
    echo "Insert Error:" . mysqli_error($conn);
}
?>
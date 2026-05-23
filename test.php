<?php
require_once 'connexionbd.php';
$cnx = connectMaBasi();

if (!$cnx) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "OK";
?>
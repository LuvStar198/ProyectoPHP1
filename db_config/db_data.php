<?php

$db_username = "root";
$db_password = "";
$dbname = "ecommerce";
$host = "localhost";

$conn = mysqli_connect($host, $db_username, $db_password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}   

?>
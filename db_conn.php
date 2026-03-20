<?php
date_default_timezone_set('Asia/Kolkata');

$serverName = "localhost";
$username   = "root";
$password   = "";

$master = mysqli_connect($serverName, $username, $password, "app_master");
if (!$master) {
    die("Master DB connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($master, "utf8mb4");
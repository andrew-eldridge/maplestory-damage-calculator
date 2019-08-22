<?php

// Localhost configuration

$dbServername = 'localhost';
$dbUsername = 'root';
$dbPassword = '';
$dbName = 'personal-site';

$conn = mysqli_connect(
    $dbServername,
    $dbUsername,
    $dbPassword,
    $dbName
);

?>
<?php
$servername   = "localhost";
$hostusername = "root";
$hostpassword = "";
$dbname       = "beanflow";

// Create connection using MySQLi Object-Oriented approach
$conn = new mysqli($servername, $hostusername, $hostpassword, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
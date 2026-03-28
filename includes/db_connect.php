<?php
// Database connection configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school_erp";

// Create connection using mysqli_connect (without specifying database first)
$conn = mysqli_connect($servername, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (mysqli_query($conn, $sql)) {
    // Database created successfully or already exists
} else {
    die("Error creating database: " . mysqli_error($conn));
}

// Select the database
mysqli_select_db($conn, $dbname);

// Set charset to utf8 for proper encoding
mysqli_set_charset($conn, "utf8");
?>
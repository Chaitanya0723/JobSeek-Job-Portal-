<?php
// config.php

// Database credentials
$host = 'localhost'; // Database host (usually 'localhost')
$dbname = 'job_portal'; // Database name
$username = 'root'; // Database username
$password = ''; // Database password (empty by default in XAMPP)

// Create a PDO instance to connect to the database
try {
    // Establish PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Optionally, you can set the default fetch mode to associative arrays
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // If the connection fails, show the error message
    echo "Connection failed: " . $e->getMessage();
    exit();
}
?>

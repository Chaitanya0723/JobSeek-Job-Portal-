<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = $_POST['post_id'];
    $name = $_POST['applicant_name'];
    $email = $_POST['applicant_email'];
    $resume = $_POST['resume_link'];

    $stmt = $conn->prepare("INSERT INTO applications (post_id, applicant_name, applicant_email, resume_link, applied_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$postId, $name, $email, $resume]);

    header("Location: home.php");
    exit();
}
?>
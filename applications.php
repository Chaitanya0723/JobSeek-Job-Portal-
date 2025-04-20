<?php
session_start();
require_once __DIR__ . '/config.php';

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employer') {
    header('Location: index.php');
    exit;
}

// Ensure a job ID is provided
if (!isset($_GET['job_id'])) {
    die("Job ID not provided.");
}

$job_id = $_GET['job_id'];

// Fetch applicants for this job
$applicants = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE post_id = ?");
    $stmt->execute([$job_id]);
    $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching applicants: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>JobSeek - Applicants</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .header {
            background-color: #1f2937;
            color: #fff;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            font-size: 24px;
            margin: 0;
        }
        .logout-btn {
            background-color: #ef4444;
            color: #fff;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .applications-container {
            max-width: 1000px;
            margin: 2rem auto;
            background-color: #ffffff;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
        }
        .btn-primary {
            background-color: #3b82f6;
            color: white;
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
        }
        .no-applicants {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Applicants for Job #<?= htmlspecialchars($job_id) ?></h1>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="applications-container">
        <?php if (count($applicants) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Applicant Name</th>
                        <th>Email</th>
                        <th>Profile</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applicants as $applicant): ?>
                        <tr>
                            <td><?= htmlspecialchars($applicant['applicant_name']) ?></td>
                            <td><?= htmlspecialchars($applicant['applicant_email']) ?></td>
                            <td>
                                <a href="applicant_profile.php?email=<?= urlencode($applicant['applicant_email']) ?>" class="btn-primary" target="_blank">View Profile</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-applicants">
                No applicants have applied to this job posting yet.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
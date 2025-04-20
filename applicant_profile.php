<?php
require_once __DIR__ . '/config.php';

// Check if email is provided in URL
if (!isset($_GET['email']) || empty($_GET['email'])) {
    die("No email provided.");
}

$email = $_GET['email'];

try {
    // Fetch user_id from the users table using email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("No user found with this email.");
    }

    $user_id = $user['id'];

    // Fetch employee details from employees table using user_id
    $stmt = $pdo->prepare("SELECT e.full_name, e.skills, e.resume_link, e.created_at 
                           FROM employees e
                           WHERE e.user_id = ?");
    $stmt->execute([$user_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        die("No employee found for this user.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Prepare resume preview URL
$resume_embed = '';
if (!empty($employee['resume_link']) && strpos($employee['resume_link'], '/file/d/') !== false) {
    $file_id = explode('/file/d/', $employee['resume_link'])[1];
    $file_id = explode('/', $file_id)[0];
    $resume_embed = "https://drive.google.com/file/d/$file_id/preview";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Applicant Profile</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 2rem;
        }
        
        header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logout-btn {
            padding: 10px 20px;
            background-color: #f44336;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            text-decoration: none;
        }

        .logout-btn:hover {
            background-color: #e7352c;
        }
        .container {
            display: flex;
            gap: 2rem;
            max-width: 1200px;
            margin: auto;
        }
        .left, .right {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .left {
            flex: 1;
        }
        .right {
            flex: 1.5;
        }
        .info p {
            margin: 0.5rem 0;
        }
        .info span {
            font-weight: bold;
        }
        iframe {
            width: 100%;
            height: 600px;
            border: none;
            border-radius: 10px;
        }
        .btn-back {
            margin-top: 2rem;
            padding: 10px 20px;
            background-color: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            display: inline-block;
        }
    </style>
</head>
<body>
<header>
        <div class="header-content">
            <h1>Employer Dashboard</h1>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>
    <div class="container">
        <!-- Left Section - Details -->
        <div class="left">
            <div class="info">
                <p><span>Full Name:</span> <?= htmlspecialchars($employee['full_name']) ?></p>
                <p><span>Email:</span> <?= htmlspecialchars($email) ?></p>
                <p><span>Skills:</span> <?= htmlspecialchars($employee['skills']) ?></p>
                <p><span>Resume Link:</span> 
                    <?php if (!empty($employee['resume_link'])): ?>
                        <a href="<?= htmlspecialchars($employee['resume_link']) ?>" target="_blank">View</a>
                    <?php else: ?>
                        Not provided
                    <?php endif; ?>
                </p>
            </div>
            <button onclick="window.close()" class="btn-back">⬅ Back to Applications</button>
        </div>

        <!-- Right Section - Resume Viewer -->
        <div class="right">
            <h2>Resume Preview</h2>
            <?php if (!empty($resume_embed)): ?>
                <iframe src="<?= htmlspecialchars($resume_embed) ?>"></iframe>
            <?php else: ?>
                <p style="color: gray;">No embedded resume preview available.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Close the tab when the "Back" button is clicked
        function closeTab() {
            window.close();
        }
    </script>
</body>
</html>

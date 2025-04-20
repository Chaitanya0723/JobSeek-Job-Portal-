<?php
include('config.php');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employer') {
    header("Location: index.php");
    exit();
}

$employer_id = $_SESSION['user_id'];
$query = $pdo->prepare("SELECT * FROM employers WHERE user_id = :user_id");
$query->execute([':user_id' => $employer_id]);
$employer = $query->fetch();

if (!$employer) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $updateQuery = $pdo->prepare("UPDATE employers SET company_name = :company_name, contact_person = :contact_person, position = :position, official_email = :official_email, website = :website WHERE user_id = :user_id");
    $updateQuery->execute([
        ':company_name' => $_POST['company_name'],
        ':contact_person' => $_POST['contact_person'],
        ':position' => $_POST['position'],
        ':official_email' => $_POST['official_email'],
        ':website' => $_POST['website'],
        ':user_id' => $employer_id
    ]);
    header("Location: admin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_job'])) {
    $checkJob = $pdo->prepare("SELECT * FROM jobs WHERE employer_id = :employer_id AND title = :title");
    $checkJob->execute([':employer_id' => $employer_id, ':title' => $_POST['title']]);
    $existingJob = $checkJob->fetch();

    if (!$existingJob) {
        $insertJob = $pdo->prepare("INSERT INTO jobs (employer_id, title, description, requirements, location, salary, job_type, posted_at, deadline, is_active) VALUES (:employer_id, :title, :description, :requirements, :location, :salary, :job_type, NOW(), :deadline, 1)");
        $insertJob->execute([
            ':employer_id' => $employer_id,
            ':title' => $_POST['title'],
            ':description' => $_POST['description'],
            ':requirements' => $_POST['requirements'],
            ':location' => $_POST['location'],
            ':salary' => $_POST['salary'],
            ':job_type' => $_POST['job_type'],
            ':deadline' => $_POST['deadline']
        ]);

        $jobId = $pdo->lastInsertId();
        $tableName = "applications_job_" . $jobId;
        $createTableSQL = "CREATE TABLE IF NOT EXISTS `$tableName` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            applicant_name VARCHAR(255) NOT NULL,
            applicant_email VARCHAR(255) NOT NULL,
            profile_link VARCHAR(255) NOT NULL
        )";
        $pdo->exec($createTableSQL);
    } else {
        $error_message = "Job posting with this title already exists.";
    }
}

if (isset($_POST['toggle_status']) && isset($_POST['job_id'])) {
    $job_id = $_POST['job_id'];
    $currentStatusQuery = $pdo->prepare("SELECT is_active FROM jobs WHERE id = :job_id");
    $currentStatusQuery->execute([':job_id' => $job_id]);
    $job = $currentStatusQuery->fetch();
    if ($job) {
        $newStatus = $job['is_active'] == 1 ? 0 : 1;
        $updateStatus = $pdo->prepare("UPDATE jobs SET is_active = :status WHERE id = :job_id");
        $updateStatus->execute([':status' => $newStatus, ':job_id' => $job_id]);
    }
}

$jobs = $pdo->prepare("SELECT * FROM jobs WHERE employer_id = :employer_id");
$jobs->execute([':employer_id' => $employer_id]);
$jobs = $jobs->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employer Dashboard</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            color: #333;
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

        h1 {
            font-size: 2em;
        }

        .form-container {
            background-color: white;
            padding: 20px;
            margin: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .profile-container, .post-job-container {
            padding: 20px;
            margin: 20px 0;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .profile-container h2, .post-job-container h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .profile-container p, .post-job-container p {
            color: #555;
            font-size: 16px;
        }

        .toggle-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
            margin-top: 20px;
        }

        .toggle-btn:hover {
            background-color: #45a049;
        }

        form input, form select, form textarea {
            width: 95%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        form button {
            background-color: #007BFF;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
        }

        form button:hover {
            background-color: #0056b3;
        }

        .card {
            background-color: white;
            padding: 20px;
            margin: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .card h3 {
            color: #333;
            font-size: 1.5em;
        }

        .card p {
            color: #555;
            font-size: 16px;
        }

        .table-container {
            background-color: white;
            padding: 20px;
            margin: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: #4CAF50;
            color: white;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table tr:hover {
            background-color: #f1f1f1;
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

    <!-- Profile Section -->
    <div class="card">
        <h3>Welcome, <?php echo htmlspecialchars($employer['company_name']); ?>!</h3>
        <p><strong>Contact Person:</strong> <?php echo htmlspecialchars($employer['contact_person']); ?></p>
        <p><strong>Position:</strong> <?php echo htmlspecialchars($employer['position']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($employer['official_email']); ?></p>
        <p><strong>Website:</strong> <a href="<?php echo htmlspecialchars($employer['website']); ?>" target="_blank"><?php echo htmlspecialchars($employer['website']); ?></a></p>
        <button class="toggle-btn" onclick="toggleForm('update-profile')">Update Profile</button>
        <button class="toggle-btn" onclick="toggleForm('post-job')">Post Job</button>
    </div>

    <!-- Profile Update Form (Initially Hidden) -->
    <div class="form-container" id="update-profile" style="display:none;">
        <h2>Update Profile</h2>
        <form method="POST">
            <label for="company_name">Company Name:</label>
            <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($employer['company_name']); ?>" required>
            
            <label for="contact_person">Contact Person:</label>
            <input type="text" id="contact_person" name="contact_person" value="<?php echo htmlspecialchars($employer['contact_person']); ?>" required>
            
            <label for="position">Position:</label>
            <input type="text" id="position" name="position" value="<?php echo htmlspecialchars($employer['position']); ?>" required>
            
            <label for="official_email">Official Email:</label>
            <input type="email" id="official_email" name="official_email" value="<?php echo htmlspecialchars($employer['official_email']); ?>" required>
            
            <label for="website">Company Website (Optional):</label>
            <input type="url" id="website" name="website" value="<?php echo htmlspecialchars($employer['website']); ?>">

            <button type="submit" name="update_profile">Update</button>
        </form>
    </div>

    <!-- Job Posting Form (Initially Hidden) -->
    <div class="form-container" id="post-job" style="display:none;">
        <h2>Post a New Job</h2>
        <form method="POST">
            <label for="title">Job Title:</label>
            <input type="text" id="title" name="title" required>
            
            <label for="description">Job Description:</label>
            <textarea id="description" name="description" rows="5" required></textarea>
            
            <label for="requirements">Job Requirements:</label>
            <textarea id="requirements" name="requirements" rows="5" required></textarea>
            
            <label for="location">Location:</label>
            <input type="text" id="location" name="location" required>
            
            <label for="salary">Salary:</label>
            <input type="number" id="salary" name="salary" required>
            
            <label for="job_type">Job Type:</label>
            <select id="job_type" name="job_type" required>
                <option value="Full-time">Full-time</option>
                <option value="Part-time">Part-time</option>
                <option value="Contract">Contract</option>
            </select>

            <label for="deadline">Application Deadline:</label>
            <input type="date" id="deadline" name="deadline" required>
            
            <button type="submit" name="post_job">Post Job</button>
        </form>
    </div>

    <!-- Job Listings Table -->
    <div class="table-container">
        <h2>Your Job Listings</h2>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Location</th>
                    <th>Salary</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th>Applicants</th> <!-- New column for applicants -->
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobs as $job): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($job['title']); ?></td>
                        <td><?php echo htmlspecialchars($job['description']); ?></td>
                        <td><?php echo htmlspecialchars($job['location']); ?></td>
                        <td><?php echo htmlspecialchars($job['salary']); ?></td>
                        <td><?php echo htmlspecialchars($job['deadline']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                <button type="submit" name="toggle_status">
                                    <?php echo $job['is_active'] == 1 ? 'Close Job' : 'Open Job'; ?>
                                </button>
                            </form>
                        </td>
                        <!-- New Applicants column with link to applications.php -->
                        <td>
                            <a href="applications.php?job_id=<?php echo $job['id']; ?>">View Applicants</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Toggle between profile update and post job forms
        function toggleForm(formId) {
            let forms = document.querySelectorAll('.form-container');
            forms.forEach(form => form.style.display = 'none');
            document.getElementById(formId).style.display = 'block';
        }
    </script>
</body>
</html>
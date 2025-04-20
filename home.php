<?php
session_start();
require_once 'config.php';

// Redirect if not employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employee') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];

// Fetch employee data
$empStmt = $pdo->prepare("SELECT * FROM employees WHERE user_id = ?");
$empStmt->execute([$user_id]);
$employee = $empStmt->fetch();

// Filtering
$locationFilter = $_GET['location'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$params = [];
$filterQuery = "WHERE is_active = 1";

// Apply filters to query
if (!empty($locationFilter)) {
    $filterQuery .= " AND location = ?";
    $params[] = $locationFilter;
}
if (!empty($typeFilter)) {
    $filterQuery .= " AND job_type = ?";
    $params[] = $typeFilter;
}

// Fetch jobs with applied filters
$jobsStmt = $pdo->prepare("SELECT * FROM jobs $filterQuery ORDER BY posted_at DESC");
$jobsStmt->execute($params);
$jobs = $jobsStmt->fetchAll();

// Handle selected job
$selected_job = null;
if (isset($_GET['apply']) && is_numeric($_GET['apply'])) {
    $jobId = $_GET['apply'];
    $jobStmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $jobStmt->execute([$jobId]);
    $selected_job = $jobStmt->fetch();
}

// Handle Application Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_job_id'])) {
    $post_id = $_POST['apply_job_id'];
    $applicant_name = $employee['full_name'];
    $resume_link = $employee['resume_link'];

    $insert = $pdo->prepare("INSERT INTO applications (post_id, applicant_name, applicant_email, resume_link, applied_at) VALUES (?, ?, ?, ?, NOW())");
    $insert->execute([$post_id, $applicant_name, $email, $resume_link]);

    echo "<script>alert('Application submitted successfully!'); window.location.href='home.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Employee Dashboard</title>
    <meta charset="UTF-8">
    <style>
        /* Dashboard Specific Styles */
        .dashboard {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        header {
            background: #2563eb;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        
        .logo img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            cursor: pointer;
            object-fit: cover;
            border: 2px solid white;
        }
        .split-container {
            display: flex;
        }
        .left-side {
            width: 50%;
            padding: 20px;
            border-right: 2px solid #ccc;
        }
        .right-side {
            width: 50%;
            padding: 20px;
        }
        .form-container {
            margin-top: 20px;
        }
        .apply-btn {
            margin-top: 10px;
            display: block;
            width: 100%;
            padding: 10px;
            text-align: center;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        
        .user-actions a {
            color: white;
            text-decoration: none;
            margin-left: 1rem;
        }
        
        .search-filter {
            padding: 1rem 2rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .search-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .search-container input {
            flex: 1;
            padding: 0.5rem 1rem;
            border: 1px solid #cbd5e0;
            border-radius: 4px;
        }
        
        .search-container button {
            background: #2563eb;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .filter-options {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .filter-options select {
            padding: 0.5rem;
            border: 1px solid #cbd5e0;
            border-radius: 4px;
            background: white;
        }
        
        .job-listings {
            padding: 2rem;
            flex: 1;
        }
        
        .job-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            border-left: 4px solid #2563eb;
        }
        
        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .job-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        
        .job-company {
            color: #4a5568;
            margin-bottom: 0.5rem;
        }
        
        .job-meta {
            display: flex;
            gap: 1rem;
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .job-meta span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .job-description {
            color: #4a5568;
            margin-bottom: 1rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .apply-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }
        
        .apply-btn:hover {
            background: #1d4ed8;
        }
        
        .no-jobs {
            text-align: center;
            padding: 2rem;
            color: #718096;
        }
        
        .reset-btn {
            background: #94a3b8;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        
        .reset-btn:hover {
            background: #64748b;
        }

        #filter{
            background: #94a3b8;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        </style>
</head>
<body class="dashboard">

<header>
    <div class="logo">
        <img src="<?= $employee['profile_pic'] ?>" alt="Profile">
        Welcome, <?= htmlspecialchars($employee['full_name']) ?>
    </div>
    <div class="user-actions">
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </div>
</header>

<div class="search-filter">
    <form class="search-container" method="GET" action="">
        <input type="text" name="search" placeholder="Search (Coming Soon)" disabled>
        <button type="submit">Search</button>
    </form>

    <div class="filter-options">
        <form method="GET">
            <select name="location">
                <option value="">Filter by Location</option>
                <?php
                $cities = ['Pune', 'Bangalore', 'Chennai', 'Mumbai', 'Hyderabad'];
                foreach ($cities as $city) {
                    $selected = ($locationFilter == $city) ? 'selected' : '';
                    echo "<option value='$city' $selected>$city</option>";
                }
                ?>
            </select>

            <select name="type">
                <option value="">Filter by Job Type</option>
                <?php
                $types = ['Full-Time', 'Part-Time', 'Internship'];
                foreach ($types as $type) {
                    $selected = ($typeFilter == $type) ? 'selected' : '';
                    echo "<option value='$type' $selected>$type</option>";
                }
                ?>
            </select>

            <button type="submit" id="filter">Apply Filters</button>
            <a class="reset-btn" href="home.php">Reset</a>
        </form>
    </div>
</div>

<?php if ($selected_job): ?>
    <!-- Split View When Apply is Clicked -->
    <div class="split-container">
        <!-- Left Side: Jobs List -->
        <div class="left-side job-listings">
            <?php foreach ($jobs as $job): ?>
                <div class="job-card">
                    <div class="job-title"><?= htmlspecialchars($job['title']) ?></div>
                    <div class="job-meta">
                        <span>📍 <?= htmlspecialchars($job['location']) ?></span>
                        <span>💰 ₹<?= htmlspecialchars($job['salary']) ?></span>
                        <span>🕒 <?= htmlspecialchars($job['job_type']) ?></span>
                    </div>
                    <div class="job-description"><?= nl2br(htmlspecialchars(substr($job['description'], 0, 200))) ?>...</div>
                    <a class="apply-btn" href="home.php?apply=<?= $job['id'] ?>">View & Apply</a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Right Side: Job Details and Apply -->
        <div class="right-side">
            <h2><?= htmlspecialchars($selected_job['title']) ?></h2>
            <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($selected_job['description'])) ?></p>
            <p><strong>Requirements:</strong> <?= nl2br(htmlspecialchars($selected_job['requirements'])) ?></p>
            <p><strong>Location:</strong> <?= htmlspecialchars($selected_job['location']) ?></p>
            <p><strong>Salary:</strong> ₹<?= htmlspecialchars($selected_job['salary']) ?></p>
            <p><strong>Type:</strong> <?= htmlspecialchars($selected_job['job_type']) ?></p>
            <p><strong>Deadline:</strong> <?= htmlspecialchars($selected_job['deadline']) ?></p>

            <div class="form-container">
                <form method="POST">
                    <input type="hidden" name="apply_job_id" value="<?= $selected_job['id'] ?>">
                    <p><strong>Name:</strong> <?= htmlspecialchars($employee['full_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
                    <p><strong>Resume:</strong> <a href="<?= $employee['resume_link'] ?>" target="_blank">View Resume</a></p>
                    <button class="apply-btn" type="submit">Submit Application</button>
                    <br><br>
                    <a class="reset-btn" href="home.php">← Close</a>
                </form>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Default View (Full width job list) -->
    <div class="job-listings" style="padding: 2rem;">
        <?php if (count($jobs) > 0): ?>
            <?php foreach ($jobs as $job): ?>
                <div class="job-card">
                    <div class="job-title"><?= htmlspecialchars($job['title']) ?></div>
                    <div class="job-meta">
                        <span>📍 <?= htmlspecialchars($job['location']) ?></span>
                        <span>💰 ₹<?= htmlspecialchars($job['salary']) ?></span>
                        <span>🕒 <?= htmlspecialchars($job['job_type']) ?></span>
                    </div>
                    <div class="job-description"><?= nl2br(htmlspecialchars(substr($job['description'], 0, 200))) ?>...</div>
                    <a class="apply-btn" href="home.php?apply=<?= $job['id'] ?>">View & Apply</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-jobs">No jobs available with selected filters.</div>
        <?php endif; ?>
    </div>
<?php endif; ?>

</body>
</html>
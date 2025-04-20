<?php
session_start();
require_once __DIR__ . '/config.php';

// Redirect if not logged in as employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employee') {
    header('Location: /index.php');
    exit;
}

// Fetch employee data
$employee = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fetch user email
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $employee['email'] = $user['email'];
} catch (PDOException $e) {
    die("Error fetching employee data: " . $e->getMessage());
}

// Handle form submission for updates
$update_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    try {
        $resume_link = $_POST['resume_link'] ?? '';
        
        // Validate Google Drive link
        if (!empty($resume_link) && !preg_match('/^https:\/\/drive\.google\.com\//', $resume_link)) {
            throw new Exception("Please provide a valid Google Drive link");
        }

        $stmt = $pdo->prepare("UPDATE employees SET full_name = ?, skills = ?, resume_link = ? WHERE user_id = ?");
        $stmt->execute([
            $_POST['full_name'],
            $_POST['skills'],
            $resume_link,
            $_SESSION['user_id']
        ]);
        
        // Update email if changed
        if ($_POST['email'] !== $employee['email']) {
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$_POST['email'], $_SESSION['user_id']]);
        }
        
        $update_success = true;
        // Refresh data
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        $employee['email'] = $_POST['email'];
    } catch (Exception $e) {
        die("Error updating profile: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobSeek - My Profile</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .profile-container {
            display: flex;
            min-height: 100vh;
            background-color: #f8fafc;
        }
        
        .profile-details {
            flex: 1;
            padding: 2rem;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .resume-viewer {
            flex: 1;
            padding: 2rem;
            background: #f1f5f9;
            display: flex;
            flex-direction: column;
        }
        
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 1rem;
        }
        
        .profile-title {
            font-size: 1.8rem;
            color: #1e293b;
        }
        
        .profile-section {
            margin-bottom: 1.5rem;
        }
        
        .profile-section h3 {
            color: #334155;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }
        
        .profile-info {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .profile-info label {
            font-weight: 600;
            color: #64748b;
        }
        
        .profile-info .value {
            color: #1e293b;
        }
        
        .edit-form {
            display: none;
            margin-top: 2rem;
        }
        
        .edit-form.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #475569;
        }
        
        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background-color: #2563eb;
            color: white;
        }
        
        .btn-secondary {
            background-color: #e2e8f0;
            color: #334155;
        }
        
        .resume-container {
            flex: 1;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .resume-iframe {
            flex: 1;
            border: none;
        }
        
        .resume-actions {
            padding: 1rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }
        
        .update-success {
            background-color: #dcfce7;
            color: #166534;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            display: none;
        }
        
        .update-success.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <!-- Left Side - Profile Details -->
        <div class="profile-details">
            <div class="profile-header">
                <h1 class="profile-title">My Profile</h1>
                <button id="edit-btn" class="btn btn-primary">Edit Profile</button>
            </div>
            
            <div class="update-success <?= $update_success ? 'active' : '' ?>">
                Profile updated successfully!
            </div>
            
            <!-- View Mode -->
            <div id="view-mode">
                <div class="profile-section">
                    <h3>Personal Information</h3>
                    <div class="profile-info">
                        <label>Full Name:</label>
                        <div class="value"><?= htmlspecialchars($employee['full_name'] ?? 'Not provided') ?></div>
                        
                        <label>Email:</label>
                        <div class="value"><?= htmlspecialchars($employee['email'] ?? 'Not provided') ?></div>
                    </div>
                </div>
                
                <div class="profile-section">
                    <h3>Professional Details</h3>
                    <div class="profile-info">
                        <label>Skills:</label>
                        <div class="value"><?= htmlspecialchars($employee['skills'] ?? 'Not provided') ?></div>
                        
                        <label>Resume Link:</label>
                        <div class="value">
                            <?php if (!empty($employee['resume_link'])): ?>
                                <a href="<?= htmlspecialchars($employee['resume_link']) ?>" target="_blank">View Resume</a>
                            <?php else: ?>
                                Not provided
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Edit Mode -->
            <form method="POST" class="edit-form" id="edit-form">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($employee['full_name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($employee['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="skills">Skills (comma separated)</label>
                    <input type="text" id="skills" name="skills" value="<?= htmlspecialchars($employee['skills'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="resume_link">Resume (Google Drive Link)</label>
                    <input type="url" id="resume_link" name="resume_link" value="<?= htmlspecialchars($employee['resume_link'] ?? '') ?>" placeholder="https://drive.google.com/file/d/...">
                    <small>Make sure the file is shared with "Anyone with the link"</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="update" class="btn btn-primary">Save Changes</button>
                    <button type="button" id="cancel-btn" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
        
        <!-- Right Side - Resume Viewer -->
        <div class="resume-viewer">
            <h2>My Resume</h2>
            <div class="resume-container">
                <?php if (!empty($employee['resume_link'])): ?>
                    <?php 
                    $embed_url = $employee['resume_link'];
                    // Convert to embeddable format if needed
                    if (strpos($embed_url, '/file/d/') !== false && strpos($embed_url, '/preview') === false) {
                        $file_id = explode('/file/d/', $embed_url)[1];
                        $file_id = explode('/', $file_id)[0];
                        $embed_url = "https://drive.google.com/file/d/$file_id/preview";
                    }
                    ?>
                    <iframe src="<?= htmlspecialchars($embed_url) ?>" 
                            class="resume-iframe"
                            frameborder="0">
                    </iframe>
                    <div class="resume-actions">
                        <a href="<?= htmlspecialchars($employee['resume_link']) ?>" 
                           target="_blank" 
                           class="btn btn-primary">
                            Open in New Tab
                        </a>
                    </div>
                <?php else: ?>
                    <div style="padding: 2rem; text-align: center; color: #64748b;">
                        No resume uploaded yet. Please add your resume link in profile settings.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Toggle between view and edit modes
        document.getElementById('edit-btn').addEventListener('click', function() {
            document.getElementById('view-mode').style.display = 'none';
            document.getElementById('edit-form').classList.add('active');
            this.style.display = 'none';
        });
        
        document.getElementById('cancel-btn').addEventListener('click', function() {
            document.getElementById('view-mode').style.display = 'block';
            document.getElementById('edit-form').classList.remove('active');
            document.getElementById('edit-btn').style.display = 'block';
        });
        
        // Hide success message after 5 seconds
        setTimeout(() => {
            const successMsg = document.querySelector('.update-success');
            if (successMsg) {
                successMsg.classList.remove('active');
            }
        }, 5000);
    </script>
</body>
</html>
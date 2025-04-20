<?php
session_start();
require_once 'config.php';

// Redirect logged-in users only if they're trying to access index.php
if (isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) === 'index.php') {
    header('Location: ' . ($_SESSION['user_type'] === 'employee' ? 'home.php' : 'admin.php'));
    exit;
}

$error = '';
$success = '';
$current_role = isset($_GET['role']) && $_GET['role'] === 'employer' ? 'employer' : 'employee';
$current_action = isset($_GET['action']) ? $_GET['action'] : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'auth.php';
    $auth = new Auth($pdo);
    
    $current_role = $_POST['user_type'] ?? $current_role;
    
    if (isset($_POST['login'])) {
        if ($auth->login($_POST['email'], $_POST['password'], $current_role)) {
            header('Location: ' . ($current_role === 'employee' ? 'home.php' : 'admin.php'));
            exit;
        } else {
            $error = "Invalid credentials";
            $current_action = 'login';
        }
    }
    
    if (isset($_POST['register'])) {
        if ($current_role === 'employee') {
            if ($auth->registerEmployee(
                $_POST['full_name'],
                $_POST['email'],
                $_POST['password'],
                $_POST['skills'],
                $_POST['resume_link']
            )) {
                $success = "Registration successful! Please login.";
                $current_action = 'login';
            } else {
                $error = "Registration failed. Email may already exist.";
            }
        } else {
            // Employer registration
            if ($auth->registerEmployer(
                $_POST['company_name'],
                $_POST['contact_name'],
                $_POST['position'],
                $_POST['email'],
                $_POST['password'],
                $_POST['website']
            )) {
                $success = "Employer registration successful! Please login.";
                $current_action = 'login';
            } else {
                $error = "Registration failed. Email may already exist.";
            }
        }
    }
    
    if (isset($_POST['forgot_password'])) {
        if ($auth->forgotPassword($_POST['email'], $current_role)) {
            $success = "Password reset link sent to your email";
        } else {
            $error = "Email not found";
        }
        $current_action = 'forgot';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobSeek - Find Your Dream Job</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="<?= $current_role === 'employee' ? 'employee-bg' : 'employer-bg' ?>">
    <div class="container" id="main-container">
        <div class="form-card" id="form-card">
            <div class="role-selector">
                <button class="role-btn <?= $current_role === 'employee' ? 'active' : '' ?>" 
                        data-role="employee" 
                        onclick="switchRole('employee')">Job Seeker</button>
                <button class="role-btn <?= $current_role === 'employer' ? 'active' : '' ?>" 
                        data-role="employer" 
                        onclick="switchRole('employer')">Employer</button>
            </div>

            <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <div class="form-container">
                <!-- Login Form -->
                <form method="POST" class="auth-form <?= $current_action === 'login' ? 'active' : '' ?>" id="login-form">
                    <h2><?= $current_role === 'employee' ? 'Job Seeker Login' : 'Employer Login' ?></h2>
                    <input type="hidden" name="user_type" value="<?= $current_role ?>">
                    
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    
                    <button type="submit" name="login" class="submit-btn">Login</button>
                    <p class="form-toggle">Don't have an account? <a href="#" onclick="switchAction('register')">Register Now</a></p>
                    <p class="forgot-password"><a href="#" onclick="switchAction('forgot')">Forgot Password?</a></p>
                </form>

                <!-- Registration Form - Job Seeker -->
                <form method="POST" class="auth-form <?= $current_action === 'register' && $current_role === 'employee' ? 'active' : '' ?>" id="register-employee-form">
                    <h2>Job Seeker Registration</h2>
                    <input type="hidden" name="user_type" value="employee">
                    
                    <div class="form-group">
                        <input type="text" name="full_name" placeholder="Full Name" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <div class="form-group">
                        <input type="text" name="skills" placeholder="Your Skills (comma separated)">
                    </div>
                    <div class="form-group">
                        <input type="url" name="resume_link" placeholder="Resume (Google Drive Link)" required>
                    </div>
                    
                    <button type="submit" name="register" class="submit-btn">Register</button>
                    <p class="form-toggle">Already have an account? <a href="#" onclick="switchAction('login')">Login</a></p>
                </form>

                <!-- Registration Form - Employer -->
                <form method="POST" class="auth-form <?= $current_action === 'register' && $current_role === 'employer' ? 'active' : '' ?>" id="register-employer-form">
                    <h2>Employer Registration</h2>
                    <input type="hidden" name="user_type" value="employer">
                    
                    <div class="form-group">
                        <input type="text" name="company_name" placeholder="Company Name" required>
                    </div>
                    <div class="form-group">
                        <input type="text" name="contact_name" placeholder="Contact Person Name" required>
                    </div>
                    <div class="form-group">
                        <input type="text" name="position" placeholder="Your Position" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Official Email" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <div class="form-group">
                        <input type="url" name="website" placeholder="Company Website (Optional)">
                    </div>
                    
                    <button type="submit" name="register" class="submit-btn">Register</button>
                    <p class="form-toggle">Already have an account? <a href="#" onclick="switchAction('login')">Login</a></p>
                </form>

                <!-- Forgot Password Form -->
                <form method="POST" class="auth-form <?= $current_action === 'forgot' ? 'active' : '' ?>" id="forgot-form">
                    <h2>Reset Password</h2>
                    <input type="hidden" name="user_type" value="<?= $current_role ?>">
                    
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Enter your email" required>
                    </div>
                    
                    <button type="submit" name="forgot_password" class="submit-btn">Send Reset Link</button>
                    <p class="form-toggle"><a href="#" onclick="switchAction('login')">Back to Login</a></p>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Current state
        let currentRole = '<?= $current_role ?>';
        let currentAction = '<?= $current_action ?>';
        
        // Switch between roles
        function switchRole(role) {
            if (role === currentRole) return;
            
            // Add transition class
            document.body.classList.add('bg-transition');
            document.getElementById('form-card').classList.add('form-transition');
            
            // Update background
            document.body.classList.remove(currentRole + '-bg');
            document.body.classList.add(role + '-bg');
            
            // Update active buttons
            document.querySelectorAll('.role-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.role === role);
            });
            
            // Update forms
            updateForms(role, currentAction);
            currentRole = role;
            
            // Remove transition classes after animation completes
            setTimeout(() => {
                document.body.classList.remove('bg-transition');
                document.getElementById('form-card').classList.remove('form-transition');
            }, 800);
        }
        
        // Switch between actions (login/register/forgot)
        function switchAction(action) {
            if (action === currentAction) return;
            
            // Add transition class
            document.getElementById('form-card').classList.add('form-transition');
            
            // Update forms
            updateForms(currentRole, action);
            currentAction = action;
            
            // Remove transition class after animation completes
            setTimeout(() => {
                document.getElementById('form-card').classList.remove('form-transition');
            }, 500);
        }
        
        // Update all forms based on current state
        function updateForms(role, action) {
            // Hide all forms
            document.querySelectorAll('.auth-form').forEach(form => {
                form.classList.remove('active');
            });
            
            // Show active form
            if (action === 'login') {
                document.getElementById('login-form').classList.add('active');
                document.querySelector('#login-form h2').textContent = 
                    role === 'employee' ? 'Job Seeker Login' : 'Employer Login';
            } 
            else if (action === 'register') {
                if (role === 'employee') {
                    document.getElementById('register-employee-form').classList.add('active');
                } else {
                    document.getElementById('register-employer-form').classList.add('active');
                }
            }
            else if (action === 'forgot') {
                document.getElementById('forgot-form').classList.add('active');
            }
            
            // Update hidden fields
            document.querySelectorAll('input[name="user_type"]').forEach(input => {
                input.value = role;
            });
            
            // Adjust form height
            adjustFormHeight();
        }
        
        // Adjust form card height to fit content
        function adjustFormHeight() {
            const activeForm = document.querySelector('.auth-form.active');
            const formCard = document.getElementById('form-card');
            if (activeForm && formCard) {
                formCard.style.height = (activeForm.scrollHeight + 180) + 'px';
            }
        }
        
        // Initialize
        window.addEventListener('load', function() {
            adjustFormHeight();
            // Add initial animation
            document.querySelector('.auth-form.active').classList.add('form-fade-in');
        });
        window.addEventListener('resize', adjustFormHeight);
    </script>
</body>
</html>
<?php
class Auth {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Register new user
    public function register($data) {
        try {
            $this->pdo->beginTransaction();

            // Check if email exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Email already exists");
            }

            // Insert into users table
            $stmt = $this->pdo->prepare("INSERT INTO users (email, password, user_type) VALUES (?, ?, ?)");
            $password = password_hash($data['password'], PASSWORD_BCRYPT);
            $stmt->execute([$data['email'], $password, $data['user_type']]);
            $user_id = $this->pdo->lastInsertId();

            // Insert into specific role table
            if ($data['user_type'] === 'employee') {
                $stmt = $this->pdo->prepare("INSERT INTO employees (user_id, full_name, skills, resume_link) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $user_id,
                    $data['full_name'],
                    $data['skills'] ?? '',
                    $data['resume_link'] ?? ''
                ]);
            } else if ($data['user_type'] === 'employer') {
                $stmt = $this->pdo->prepare("INSERT INTO employers (user_id, company_name, contact_person, position, official_email, website) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $user_id,
                    $data['company_name'],
                    $data['contact_person'],
                    $data['position'],
                    $data['email'], // official email same as login email
                    $data['website'] ?? null
                ]);
            }

            $this->pdo->commit();
            return true;
        } catch(Exception $e) {
            $this->pdo->rollBack();
            error_log("Registration error: " . $e->getMessage());
            return false;
        }
    }

    // Specific employer registration
    public function registerEmployer($company_name, $contact_person, $position, $email, $password, $website = '') {
        return $this->register([
            'email' => $email,
            'password' => $password,
            'user_type' => 'employer',
            'company_name' => $company_name,
            'contact_person' => $contact_person,
            'position' => $position,
            'website' => $website
        ]);
    }

    // Specific employee registration
    public function registerEmployee($full_name, $email, $password, $skills, $resume_link) {
        return $this->register([
            'email' => $email,
            'password' => $password,
            'user_type' => 'employee',
            'full_name' => $full_name,
            'skills' => $skills,
            'resume_link' => $resume_link
        ]);
    }

    // Login user
    public function login($email, $password, $user_type) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND user_type = ?");
            $stmt->execute([$email, $user_type]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['email'] = $user['email'];
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    // Get employer profile
    public function getEmployerProfile($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.email, e.* 
                FROM users u
                JOIN employers e ON u.id = e.user_id
                WHERE u.id = ? 
            ");
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch();
            
            return $profile ?: [
                'company_name' => '',
                'contact_person' => '',
                'position' => '',
                'official_email' => '',
                'website' => '',
                'email' => ''
            ];
        } catch(PDOException $e) {
            error_log("Error fetching employer profile: " . $e->getMessage());
            return [
                'company_name' => '',
                'contact_person' => '',
                'position' => '',
                'official_email' => '',
                'website' => '',
                'email' => ''
            ];
        }
    }

    // Get employer jobs
    public function getEmployerJobs($employer_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, title, posted_at, is_active 
                FROM jobs 
                WHERE employer_id = ? 
                ORDER BY posted_at DESC
            ");
            $stmt->execute([$employer_id]);
            return $stmt->fetchAll() ?: [];
        } catch(PDOException $e) {
            error_log("Error fetching employer jobs: " . $e->getMessage());
            return [];
        }
    }

    // Post new job
    public function postJob($jobData) {
        try {
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO jobs (
                    employer_id, title, description, requirements,
                    location, salary, job_type, posted_at, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), TRUE)
            ");
            
            $success = $stmt->execute([ 
                $jobData['employer_id'], 
                $jobData['title'],
                $jobData['description'],
                $jobData['requirements'],
                $jobData['location'],
                $jobData['salary'],
                $jobData['job_type']
            ]);
            
            if (!$success) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Database error: " . $errorInfo[2]);
            }
            
            $this->pdo->commit();
            return true;
        } catch(Exception $e) {
            $this->pdo->rollBack();
            error_log("Job posting failed: " . $e->getMessage());
            return false;
        }
    }

    // Update job status
    public function updateJobStatus($job_id, $employer_id, $is_active) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE jobs 
                SET is_active = ? 
                WHERE id = ? AND employer_id = ?
            ");
            return $stmt->execute([$is_active, $job_id, $employer_id]);
        } catch(PDOException $e) {
            error_log("Error updating job status: " . $e->getMessage());
            return false;
        }
    }

    // Get job details
    public function getJobDetails($job_id, $employer_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM jobs 
                WHERE id = ? AND employer_id = ?
            ");
            $stmt->execute([$job_id, $employer_id]);
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Error fetching job details: " . $e->getMessage());
            return false;
        }
    }
}
?>

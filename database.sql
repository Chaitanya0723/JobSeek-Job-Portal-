CREATE DATABASE IF NOT EXISTS job_portal;
USE job_portal;

-- Create the users table
CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('employee', 'employer') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create the employers table
CREATE TABLE IF NOT EXISTS employers (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    official_email VARCHAR(255) NOT NULL,
    website VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create the jobs table
CREATE TABLE IF NOT EXISTS jobs (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    employer_id INT(11) NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT NOT NULL,
    location VARCHAR(100) NOT NULL,
    salary VARCHAR(50) DEFAULT NULL,
    job_type ENUM('full-time', 'part-time', 'contract', 'internship') NOT NULL,
    posted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deadline DATE DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (employer_id) REFERENCES employers(id)
);

-- Create the employees table
CREATE TABLE IF NOT EXISTS employees (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    skills TEXT DEFAULT NULL,
    resume_link VARCHAR(255) DEFAULT NULL,
    resume_path VARCHAR(255) DEFAULT NULL,
    profile_pic VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create the applications table
CREATE TABLE IF NOT EXISTS applications (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    post_id INT(11) DEFAULT NULL,
    applicant_name VARCHAR(100) DEFAULT NULL,
    applicant_email VARCHAR(100) DEFAULT NULL,
    resume_link TEXT DEFAULT NULL,
    applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES jobs(id)
);

-- Optionally, you can add indexes to foreign keys for better performance
CREATE INDEX idx_users_id ON employers(user_id);
CREATE INDEX idx_jobs_employer_id ON jobs(employer_id);
CREATE INDEX idx_applications_post_id ON applications(post_id);

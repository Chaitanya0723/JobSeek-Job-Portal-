# Job Portal Project

## Description
This is a simple job portal system built using PHP, MySQL, HTML, CSS, and JavaScript. The system allows **employers** to post job listings and **employees** to browse and apply for available jobs. The project includes a fully functional backend to manage users, job postings, and applications. It's designed for easy setup with **XAMPP** and a MySQL database.

The main features of the portal include:
- **Employer Features:**
  - Post new job listings.
  - View job applications.
  - Manage job postings.
- **Employee Features:**
  - View available job listings.
  - Apply to job postings.
  - Upload a resume and manage their profile.

## How to Run the Code

### 1. Download or Clone the Repository
You can either clone or download the project from GitHub to your local machine.

- **Clone Command** (if using Git):
  ```bash
  git clone https://github.com/yourusername/job-portal.git

### 2. Place the Project Files in the XAMPP Directory
Once you have downloaded or cloned the repository, follow these steps:
-Copy all the project files into the C:\xampp\htdocs directory. This is where XAMPP serves files for local development.

For example:
--If your project folder is named job-portal, the path should be C:\xampp\htdocs\job-portal.

-Ensure that the project is accessible from http://localhost/job-portal in your web browser.

### 3. Import the Database Using database.sql
To set up the MySQL database, follow these steps:
Open XAMPP Control Panel and start both Apache and MySQL services.
Ensure that both services are running properly. If not, you may need to troubleshoot the configuration.
Once Apache and MySQL are running, navigate to http://localhost/phpmyadmin/ in your web browser.
In phpMyAdmin, create a new database:
Click on the Databases tab.
Enter job_portal as the database name and click Create.
After creating the database, click on the newly created database (job_portal) from the list on the left.
Go to the Import tab in phpMyAdmin.
Click on Choose File and select the database.sql file from your project folder.
Once the file is uploaded, click Go to import the SQL file.
This will create all the necessary tables in the job_portal database.

### 4. Run the Project
After setting up the database:

Open your browser and go to http://localhost/job-portal to view and interact with the Job Portal system.

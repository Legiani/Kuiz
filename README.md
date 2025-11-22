# Kuizio

A robust PHP-based quiz application featuring Google Sign-In, CSV test uploads, progress tracking, and quiz sharing capabilities.

## Features

-   **Google Sign-In**: Secure authentication using Google Identity Services.
-   **Dashboard**: Central hub to view available tests, pending invitations, and manage your quizzes.
-   **Test Archive**:
    -   Upload your own tests via CSV files.
    -   Download source CSV files for tests you have access to.
-   **Quiz Engine**:
    -   Weighted question selection (prioritizes unseen and incorrectly answered questions).
    -   Detailed results with correct answer highlighting.
    -   Persistent progress tracking (stored in MariaDB).
-   **Sharing**:
    -   Private-by-default uploads.
    -   Share tests with other users via Gmail invitations.
    -   Accept invitations to gain access to shared tests.

## Prerequisites

-   PHP 7.4+
-   MariaDB / MySQL
-   Web Server (Apache/Nginx)
-   Google Cloud Console Project (for OAuth Client ID)

## Installation

1.  **Clone the repository** to your web server directory.

2.  **Database Setup**:
    -   Create a new MariaDB database.
    -   Import the schema or let the setup script handle table creation.

3.  **Configuration**:
    -   Create a `config.php` file in the root directory (based on the example below).
    -   Enter your Google Client ID and Database credentials.

    ```php
    <?php
    return [
        'google_client_id' => 'YOUR_GOOGLE_CLIENT_ID',
        'db_host' => 'localhost',
        'db_name' => 'your_db_name',
        'db_user' => 'your_db_user',
        'db_pass' => 'your_db_pass'
    ];
    ?>
    ```

4.  **Initialize Database**:
    -   Navigate to `http://your-domain.com/setup_db.php` in your browser.
    -   This script will create the necessary tables (`users`, `progress`, `tests`, `invitations`, `test_access`) and insert the default test.

## Usage

1.  **Login**: Sign in using your Google account.
2.  **Dashboard**:
    -   View "System" tests (available to everyone).
    -   View your uploaded tests.
    -   View tests shared with you.
3.  **Upload**: Click "Add New Test" or go to "Upload Test" in the navbar to upload a CSV file.
    -   **CSV Format**: The CSV should be standard comma-separated values containing your questions and answers.
4.  **Share**: Go to the detail page of a test you uploaded and enter a friend's Gmail address to invite them.
5.  **Take Quiz**: Select a test, choose the number of questions, and start!

## Directory Structure

-   `tests/`: Stores the uploaded CSV test files.
-   `index.php`: Main dashboard.
-   `quiz.php`: Quiz interface.
-   `result.php`: Quiz results and progress saving.
-   `upload_test.php`: File upload handler.
-   `test_detail.php`: Test management and actions.
-   `my_tests.php`: Legacy management page (optional).

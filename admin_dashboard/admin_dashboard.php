<?php
// Start the session
session_start();

// Manually set session variables to fake an admin login
$_SESSION['logged_in'] = true;
$_SESSION['role'] = 'admin';
$_SESSION['username'] = 'FakeAdmin';

// Check if the user is logged in and has the 'admin' role
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    // User is not logged in as an admin, redirect to the login page
    header('Location: login.php');
    exit;
}

// Proceed with the rest of the dashboard code if the user is an admin
?>
<?php
$servername = "localhost"; // usually localhost
$username = "root";
$password = "root";
$database = "mylibrary";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to DB successfully"; 
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
    </header>
    <nav id="admin-nav">
        <ul>
            <li><a href="add_book.php">Add Book</a></li>
            <li><a href="manage_authors.php">Manage Authors</a></li>
            <li><a href="view_users.php">View Users</a></li>
            <li><a href="site_settings.php">Site Settings</a></li>
        </ul>
    </nav>
        <main>
        <section id="welcome">
            <h2>Welcome, Admin!</h2>
            <p>This is your dashboard, where you can manage the entire library.</p>
        </section>
        <section id="stats">
            <div class="stat">
                <h3>Books in Library</h3>
                <p>1234</p>
            </div>
            <div class="stat">
                <h3>Active Users</h3>
                <p>234</p>
            </div>
            <div class="stat">
                <h3>Authors Registered</h3>
                <p>96</p>
            </div>
            <!-- More stats can be added here -->

        </section>
        <section id="latest-activity">
            <h2>Latest Activity</h2>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- This data would be populated with PHP from the database -->
                    <tr>
                        <td>Jane Doe</td>
                        <td>Checked out "The Great Gatsby"</td>
                        <td>2023-12-19</td>
                    </tr>
                    <tr>
                        <td>John Smith</td>
                        <td>Returned "1984"</td>
                        <td>2023-12-18</td>
                    </tr>
                    <!-- More rows can be added here -->
                </tbody>
            </table>
        </section>
        
    </main>
    <footer>
        <p>&copy; 2023 Library Dashboard</p>
    </footer>
</body>
</html>

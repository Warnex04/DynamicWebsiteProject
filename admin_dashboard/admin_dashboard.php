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
    header('Location: ../login/login.php');
    exit;
}

// Proceed with the rest of the dashboard code if the user is an admin
?>
<?php
$servername = "localhost"; // usually localhost
$username = "root";
$password = "";
$database = "mylibrary";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to DB successfully"; 
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

function fetchTableData($conn, $tableName) {
    try {
        $stmt = $conn->prepare("SELECT * FROM " . $tableName);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle error appropriately
        // For debugging purposes, you can uncomment the line below
        // echo "Error: " . $e->getMessage();
        return []; // Return an empty array to prevent further errors
    }
}

// Fetch data from all tables
$admins = fetchTableData($conn, 'admin');
$authors = fetchTableData($conn, 'author');
$books = fetchTableData($conn, 'book');
$ecrits = fetchTableData($conn, 'ecrit');
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
            <li><a href="logout.php">Logout</a></li>
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
        <div class="dashboard-grid">
        <!-- Admin Table Section -->
        <section class="table-container">
        <h2>Admins</h2>
        <div class="scrollable-table">
            <!-- Repeat the following table structure for each table -->
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['ID']); ?></td>
                            <td><?php echo htmlspecialchars($row['FirstName']); ?></td>
                            <td><?php echo htmlspecialchars($row['LastName']); ?></td>
                            <td><?php echo htmlspecialchars($row['Mail']); ?></td>
                            <td><?php echo htmlspecialchars($row['Phone']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

<!-- Author Table Section -->
<section class="table-container">
    <h2>Authors</h2>
    <div class="scrollable-table">
        <table>
            <thead>
                <tr>
                    <th>Num</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Birthdate</th>
                    <th>Nationality</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($authors as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['Num']); ?></td>
                        <td><?php echo htmlspecialchars($row['FirstName']); ?></td>
                        <td><?php echo htmlspecialchars($row['LastName']); ?></td>
                        <td><?php echo htmlspecialchars($row['BirthDate']); ?></td>
                        <td><?php echo htmlspecialchars($row['Nationality']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Book Table Section -->
<section class="table-container">
    <h2>Books</h2>
    <div class="scrollable-table">
        <table>
            <thead>
                <tr>
                    <th>ISSN</th>
                    <th>Title</th>
                    <th>Summary</th>
                    <th>Number of Pages</th>
                    <th>Category</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['ISSN']); ?></td>
                        <td><?php echo htmlspecialchars($row['Title']); ?></td>
                        <td><?php echo htmlspecialchars($row['Summary']); ?></td>
                        <td><?php echo htmlspecialchars($row['NbPages']); ?></td>
                        <td><?php echo htmlspecialchars($row['Category']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Ecrit Table Section -->
<section class="table-container">
    <h2>Ecrit</h2>
    <div class="scrollable-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Author Num</th>
                    <th>Book ISSN</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ecrits as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['ID']); ?></td>
                        <td><?php echo htmlspecialchars($row['Num']); ?></td>
                        <td><?php echo htmlspecialchars($row['ISSN']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
                </div>
        
    </main>
    <footer>
        <p>&copy; 2023 Library</p>
    </footer>
</body>
</html>

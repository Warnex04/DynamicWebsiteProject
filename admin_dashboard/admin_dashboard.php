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

// Capture search terms for each table
$searchAdmin = isset($_GET['search_admin']) ? $_GET['search_admin'] : '';
$searchAuthor = isset($_GET['search_author']) ? $_GET['search_author'] : '';
$searchBook = isset($_GET['search_book']) ? $_GET['search_book'] : '';
$searchEcrit = isset($_GET['search_ecrit']) ? $_GET['search_ecrit'] : '';


// Function to validate if the provided column is allowed
function validateColumn($column, $allowedColumns) {
    return in_array($column, $allowedColumns, true);
}

function fetchTableData($conn, $tableName, $searchTerm, $searchColumns) {
    try {
        // Define the allowed columns for each table to prevent SQL injection
        $allowedColumnsMap = [
            'admin' => ['FirstName', 'LastName', 'Mail', 'Phone'],
            'author' => ['FirstName', 'LastName', 'BirthDate', 'Nationality'],
            'book' => ['Title', 'Summary', 'NbPages', 'Category'],
            'ecrit' => ['Num', 'ISSN'],
        ];

        // Check if the table name is valid to prevent SQL injection
        if (!array_key_exists($tableName, $allowedColumnsMap)) {
            throw new Exception("Invalid table name");
        }

        // Retrieve the list of allowed columns for the specified table
        $allowedColumns = $allowedColumnsMap[$tableName];

        // Filter the search columns to include only those that are allowed
        $searchColumns = array_filter($searchColumns, function ($column) use ($allowedColumns) {
            return in_array($column, $allowedColumns, true);
        });

        // Continue only if there are valid columns to search and a search term is provided
        if ($searchTerm && !empty($searchColumns)) {
            $conditions = [];
            $params = [];

            // Construct the WHERE clause based on the search terms and selected columns
            foreach ($searchColumns as $column) {
                if (in_array($column, $allowedColumns)) {
                    // For each selected column, add a LIKE condition
                    $conditions[] = "$column LIKE :$column";
                    $params[":$column"] = '%' . $searchTerm . '%';
                }
            }

            // Start building the SQL query
            $query = "SELECT * FROM $tableName";
            // If there are conditions, append them to the query
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' OR ', $conditions);
            }

            // Prepare the SQL statement
            $stmt = $conn->prepare($query);

            // Bind parameters for the prepared statement
            foreach ($params as $key => &$val) {
                $stmt->bindParam($key, $val);
            }

            // Execute the query and return the results
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // If no valid columns or no search term, return all records from the table
            $stmt = $conn->prepare("SELECT * FROM $tableName");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        // Handle any exceptions and display an error message
        echo "Error: " . $e->getMessage();
        // Return an empty array to prevent further errors
        return [];
    }
}

$searchTermAdmin = $_GET['search_term_admin'] ?? '';
$adminSearchColumns = $_GET['admin_search_columns'] ?? [];
$admins = fetchTableData($conn, 'admin', $searchTermAdmin, $adminSearchColumns);


$searchTermAuthor = $_GET['search_term_author'] ?? '';
$authorSearchColumns = $_GET['author_search_columns'] ?? [];
$authors = fetchTableData($conn, 'author', $searchTermAuthor, $authorSearchColumns);

$searchTermBook = $_GET['search_term_book'] ?? '';
$bookSearchColumns = $_GET['book_search_columns'] ?? [];
$books = fetchTableData($conn, 'book', $searchTermBook, $bookSearchColumns);

$searchTermEcrit = $_GET['search_term_ecrit'] ?? '';
$ecritSearchColumns = $_GET['ecrit_search_columns'] ?? [];
$ecrits = fetchTableData($conn, 'ecrit', $searchTermEcrit, $ecritSearchColumns);


function logActivity($conn, $username, $action) {
  $currentDateTime = date('Y-m-d H:i:s'); // Get the current date and time
  $logStmt = $conn->prepare("INSERT INTO activity_log (user, action, date) VALUES (?, ?, ?)");
  $logStmt->execute([$username, $action, $currentDateTime]);
}


// Process Author Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['insert_author'])) {
  $stmt = $conn->prepare("INSERT INTO author (FirstName, LastName, BirthDate, Nationality) VALUES (?, ?, ?, ?)");
  $stmt->execute([
      $_POST['author_first_name'],
      $_POST['author_last_name'],
      $_POST['author_birth_date'],
      $_POST['author_nationality']
  ]);

  if ($stmt->rowCount()) {
      logActivity($conn, $_SESSION['username'], 'Added new author');
      header('Location: admin_dashboard.php');
      exit;
  }
}

// Process Book Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['insert_book'])) {
  $stmt = $conn->prepare("INSERT INTO book (ISSN, Title, Summary, NbPages, Category) VALUES (?, ?, ?, ?, ?)");
  $stmt->execute([
      $_POST['book_issn'],
      $_POST['book_title'],
      $_POST['book_summary'],
      $_POST['book_nb_pages'],
      $_POST['book_category']
  ]);

  if ($stmt->rowCount()) {
      logActivity($conn, $_SESSION['username'], 'Added new book');
      header('Location: admin_dashboard.php');
      exit;
  }
}

// Process Ecrit Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['insert_ecrit'])) {
  $authorCheckStmt = $conn->prepare("SELECT * FROM author WHERE Num = ?");
  $authorCheckStmt->execute([$_POST['ecrit_num']]);
  $authorExists = $authorCheckStmt->fetch();

  $bookCheckStmt = $conn->prepare("SELECT * FROM book WHERE ISSN = ?");
  $bookCheckStmt->execute([$_POST['ecrit_issn']]);
  $bookExists = $bookCheckStmt->fetch();

  if ($authorExists && $bookExists) {
      $stmt = $conn->prepare("INSERT INTO ecrit (Num, ISSN) VALUES (?, ?)");
      $stmt->execute([
          $_POST['ecrit_num'],
          $_POST['ecrit_issn']
      ]);

      if ($stmt->rowCount()) {
          logActivity($conn, $_SESSION['username'], 'Added new ecrit record');
          header('Location: admin_dashboard.php');
          exit;
      }
  } else {
      if (!$authorExists) {
          echo "<script>alert('Author with this Num does not exist.');</script>";
      }
      if (!$bookExists) {
          echo "<script>alert('Book with this ISSN does not exist.');</script>";
      }
  }
}

// Fetch the latest activities for the activity log
$activityLogStmt = $conn->query("SELECT user, action, date FROM activity_log ORDER BY date DESC LIMIT 10");
$activities = $activityLogStmt->fetchAll(PDO::FETCH_ASSOC);

// Query to get the number of books
$bookCountStmt = $conn->query("SELECT COUNT(*) FROM book");
$bookCount = $bookCountStmt->fetchColumn();

// Query to get the number of authors
$authorCountStmt = $conn->query("SELECT COUNT(*) FROM author");
$authorCount = $authorCountStmt->fetchColumn();

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
        <!-- Buttons to Open the Modals -->
      <ul>
        <li>
        <button id="btnAddBook">Add Book</button>
        </li>
        <li>
        <button id="btnAddAuthor">Add Author</button>
        </li>
        <li>
        <button id="btnAddEcrit">Add Ecrit</button>
        </li>
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
    <p><?php echo $bookCount; ?></p>
  </div>
  <div class="stat">
    <h3>Authors Registered</h3>
    <p><?php echo $authorCount; ?></p>
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
      <?php foreach ($activities as $activity): ?>
      <tr>
        <td><?php echo htmlspecialchars($activity['user']); ?></td>
        <td><?php echo htmlspecialchars($activity['action']); ?></td>
        <td><?php echo htmlspecialchars($activity['date']); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

      <div class="dashboard-grid">
        <!-- Admin Table Section -->
        <section class="table-container">
          <h2>Admins</h2>
          <form method="get" action="admin_dashboard.php">
            <input class="input" type="text" name="search_term_admin" placeholder="Search admins..." />
            <!-- Checkboxes for selecting columns to search -->
            <label class="cl-checkbox">
              <input type="checkbox" name="admin_search_columns[]" value="FirstName"> <span>First Name </span></label>
            <label class="cl-checkbox">
              <input type="checkbox" name="admin_search_columns[]" value="LastName"> <span>Last Name</span></label>
            <label class="cl-checkbox">
              <input type="checkbox" name="admin_search_columns[]" value="Mail"> <span>Email</span></label>
            <label class="cl-checkbox">
              <input type="checkbox" name="admin_search_columns[]" value="Phone"> <span>Phone</span> </label>
            <!-- Submit button -->
            <button type="submit">Search</button>
          </form>
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
              <tbody> <?php foreach ($admins as $row): ?> <tr>
                  <td> <?php echo htmlspecialchars($row['ID']); ?> </td>
                  <td> <?php echo htmlspecialchars($row['FirstName']); ?> </td>
                  <td> <?php echo htmlspecialchars($row['LastName']); ?> </td>
                  <td> <?php echo htmlspecialchars($row['Mail']); ?> </td>
                  <td> <?php echo htmlspecialchars($row['Phone']); ?> </td>
                </tr> <?php endforeach; ?> </tbody>
            </table>
          </div>
        </section>
        <!-- Author Table Section -->
        <section class="table-container">
          <h2>Authors</h2>
          <!-- Form for searching the Author table -->
          <form method="get" action="admin_dashboard.php">
            <input class="input" type="text" name="search_term_author" placeholder="Search authors..." />
            <!-- Checkboxes for selecting columns to search -->
            <label class="cl-checkbox">
              <input type="checkbox" name="author_search_columns[]" value="FirstName"> <span>First Name</span> </label>
            <label class="cl-checkbox">
              <input type="checkbox" name="author_search_columns[]" value="LastName"> <span>Last Name</span> </label>
            <label class="cl-checkbox">
              <input type="checkbox" name="author_search_columns[]" value="BirthDate"> <span>Birthdate</span> </label>
            <label class="cl-checkbox">
              <input type="checkbox" name="author_search_columns[]" value="Nationality"> <span>Nationality</span> </label>
            <!-- Submit button -->
            <button type="submit">Search</button>
          </form>
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
              <tbody> <?php foreach ($authors as $row): ?> <tr>
                  <td> <?php echo htmlspecialchars($row['Num']); ?> </td>
                  <td> <?php echo htmlspecialchars($row['FirstName']); ?> </td>
                  <td> <?php echo htmlspecialchars($row['LastName']); ?> </td>
                  <td> <?php echo htmlspecialchars($row['BirthDate']); ?> </td>
                  <td> <?php echo htmlspecialchars($row['Nationality']); ?> </td>
                </tr> <?php endforeach; ?> </tbody>
            </table>
          </div>
        </section>
        <!-- Book Table Section -->
        <section class="table-container">
          <h2>Books</h2>
          <!-- Search form for the Book table -->
          <form method="get" action="admin_dashboard.php">
            <input class="input" type="text" name="search_term_book" placeholder="Search books..." />
            <!-- Checkboxes for selecting columns to search -->
            <label class="cl-checkbox">
              <input type="checkbox" name="book_search_columns[]" value="Title"> <span>Title</span> </label>
            <label class="cl-checkbox">
              <input type="checkbox" name="book_search_columns[]" value="Summary"> <span>Summary</span> </label>
            <label class="cl-checkbox">
              <input type="checkbox" name="book_search_columns[]" value="NbPages"> <span>Number of Pages</span> </label>
            <label class="cl-checkbox">
              <input type="checkbox" name="book_search_columns[]" value="Category"> <span>Category</span> </label>
            <!-- Submit button -->
            <button type="submit">Search</button>
          </form>
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
              <tbody> <?php foreach ($books as $row): ?> <tr>
                  <td> <?php echo htmlspecialchars($row['ISSN']); ?> </td>
                  <td> <?php echo htmlspecialchars($row['Title']); ?> </td>
                  <td> <?php echo htmlspecialchars($row['Summary']); ?> </td>
                  <td> <?php echo htmlspecialchars($row['NbPages']); ?> </td>
                  <td> <?php echo htmlspecialchars($row['Category']); ?> </td>
                </tr> <?php endforeach; ?> </tbody>
            </table>
          </div>
        </section>
        <!-- Ecrit Table Section -->
        <section class="table-container">
          <h2>Ecrit</h2>
          <!-- Search form for the Ecrit table -->
          <form method="get" action="admin_dashboard.php">
            <input class="input" type="text" name="search_term_ecrit" placeholder="Search ecrit records..." />
            <!-- Checkboxes for selecting columns to search -->
            <label class="cl-checkbox">
              <input type="checkbox" name="ecrit_search_columns[]" value="Num"> <span>Author Num</span> </label>
            <label class="cl-checkbox">
              <input type="checkbox" name="ecrit_search_columns[]" value="ISSN"> <span>Book ISSN</span> </label>
            <!-- Submit button -->
            <button type="submit">Search</button>
          </form>
          <div class="scrollable-table">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Author Num</th>
                  <th>Book ISSN</th>
                </tr>
              </thead>
              <tbody> <?php foreach ($ecrits as $row): ?> <tr>
                  <td> <?php echo htmlspecialchars($row['ID']); ?> </td>
                  <td> <?php echo htmlspecialchars($row['Num']); ?> </td>
                  <td> <?php echo htmlspecialchars($row['ISSN']); ?> </td>
                </tr> <?php endforeach; ?> </tbody>
            </table>
          </div>
        </section>
      </div>
    </main>
    <footer>
      <p>&copy; 2023 Library</p>
    </footer>
          <!-- Author Modal -->
<div id="authorModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <!-- Form for Inserting a New Author -->
<div id="authorForm" class="modal-form">
    <h2>Add New Author</h2>
    <form method="post" action="admin_dashboard.php">
        <input type="text" name="author_first_name" placeholder="First Name" required>
        <input type="text" name="author_last_name" placeholder="Last Name" required>
        <input type="date" name="author_birth_date" placeholder="Birth Date" required>
        <input type="text" name="author_nationality" placeholder="Nationality" required>
        <button type="submit" name="insert_author">Insert Author</button>
    </form>
</div>

    </div>
</div>

<!-- Book Modal -->
<div id="bookModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <!-- Form for Inserting a New Book -->
<div id="bookForm" class="modal-form">
    <h2>Add New Book</h2>
    <form method="post" action="admin_dashboard.php">
        <input type="text" name="book_issn" placeholder="ISSN" required>
        <input type="text" name="book_title" placeholder="Title" required>
        <textarea name="book_summary" placeholder="Summary" required></textarea>
        <input type="number" name="book_nb_pages" placeholder="Number of Pages" required>
        <input type="text" name="book_category" placeholder="Category" required>
        <button type="submit" name="insert_book">Insert Book</button>
    </form>
</div>

    </div>
</div>

<!-- Ecrit Modal -->
<div id="ecritModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <!-- Form for Inserting a New Ecrit Record -->
<div id="ecritForm" class="modal-form">
    <h2>Add New Ecrit Record</h2>
    <form method="post" action="admin_dashboard.php">
        <input type="number" name="ecrit_num" placeholder="Author Num (ID)" required>
        <input type="text" name="ecrit_issn" placeholder="Book ISSN" required>
        <button type="submit" name="insert_ecrit">Insert Ecrit Record</button>
    </form>
</div>

    </div>
</div>

<script>
    // Get the modals
    var authorModal = document.getElementById("authorModal");
    var bookModal = document.getElementById("bookModal");
    var ecritModal = document.getElementById("ecritModal");

    // Get the buttons that open the modals
    var btnAddAuthor = document.getElementById("btnAddAuthor");
    var btnAddBook = document.getElementById("btnAddBook");
    var btnAddEcrit = document.getElementById("btnAddEcrit");

    // Get the <span> elements that close the modals
    var spans = document.getElementsByClassName("close");

    // Open modals
    btnAddAuthor.onclick = function() { authorModal.style.display = "block"; }
    btnAddBook.onclick = function() { bookModal.style.display = "block"; }
    btnAddEcrit.onclick = function() { ecritModal.style.display = "block"; }

    // Close modals
    for (var i = 0; i < spans.length; i++) {
        spans[i].onclick = function() {
            authorModal.style.display = "none";
            bookModal.style.display = "none";
            ecritModal.style.display = "none";
        }
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        if (event.target == authorModal || event.target == bookModal || event.target == ecritModal) {
            authorModal.style.display = "none";
            bookModal.style.display = "none";
            ecritModal.style.display = "none";
        }
    }
</script>
  </body>
</html>
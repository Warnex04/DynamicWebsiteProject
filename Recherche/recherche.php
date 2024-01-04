<?php
session_start();

// Database connection parameters
$host = 'localhost';
$dbname = 'projetdb';
$db_username = 'root';
$db_password = '';

// Initialize variables
$searchString = '';
$filters = [];
$results = [];
$uniqueWords = [];
$nationalities = [];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch categories
    $categoryQuery = "SELECT category FROM book";
    $stmt = $pdo->query($categoryQuery);
    $allCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Process categories
    $allWords = [];
    foreach ($allCategories as $category) {
        $words = explode(' ', strtolower($category));
        $allWords = array_merge($allWords, $words);
    }
    $uniqueWords = array_unique($allWords);

    // Fetch nationalities
    $nationalityQuery = "SELECT DISTINCT Nationality FROM author";
    $nationalityStmt = $pdo->query($nationalityQuery);
    $nationalities = $nationalityStmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}

// Process search
function processSearch($pdo, $searchString, $filters) {
    global $results;

    $titleAuthorConditions = [];
    $searchParams = [];
    $i = 0;
    if (in_array('title', $filters)) {
        $titleAuthorConditions[] = "LOWER(book.title) LIKE :searchTitle";
        $searchParams[':searchTitle'] = "%{$searchString}%";
    }
    if (in_array('author', $filters)) {
        $titleAuthorConditions[] = "LOWER(CONCAT(author.FirstName, ' ', author.LastName)) LIKE :searchAuthor";
        $searchParams[':searchAuthor'] = "%{$searchString}%";
    }

    $searchConditions = [];
    if (!empty($titleAuthorConditions)) {
        $searchConditions[] = '(' . implode(' OR ', $titleAuthorConditions) . ')';
    }

    $wordConditions = [];
    if (!empty($_POST['filter_word'])) {
        foreach ($_POST['filter_word'] as $word) {
            $paramName = ":word" . ++$i;
            $wordConditions[] = "LOWER(book.category) LIKE $paramName";
            $searchParams[$paramName] = '%' . $word . '%';
        }
    }
    if (!empty($wordConditions)) {
        $searchConditions[] = '(' . implode(' OR ', $wordConditions) . ')';
    }

    $nationalityConditions = [];
    if (!empty($_POST['filter_nationality'])) {
        foreach ($_POST['filter_nationality'] as $nationality) {
            $paramName = ":nationality" . ++$i;
            $nationalityConditions[] = "author.Nationality = $paramName";
            $searchParams[$paramName] = $nationality;
        }
    }
    if (!empty($nationalityConditions)) {
        $searchConditions[] = '(' . implode(' OR ', $nationalityConditions) . ')';
    }

    $query = "
    SELECT book.ISSN, book.title, book.category, CONCAT(author.FirstName, ' ', author.LastName) AS author_name, author.Nationality
    FROM book 
    LEFT JOIN ecrit ON ecrit.ISSN = book.ISSN 
    LEFT JOIN author ON ecrit.Num = author.Num";

    if (!empty($searchConditions)) {
        $query .= " WHERE " . implode(' AND ', $searchConditions);
    }

    try {
        $stmt = $pdo->prepare($query);
        foreach ($searchParams as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Erreur lors de l'exécution de la requête : " . $e->getMessage();
    }
}

// Check for main search form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $searchString = strtolower($_POST['search']);
    $filters = $_POST['filter'] ?? ['title', 'author'];
    processSearch($pdo, $searchString, $filters);
    $_SESSION['search_results'] = $results;
}

// Check for filter form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['searchString'])) {
    $searchString = strtolower($_POST['searchString']);
    $filters = $_POST['filter'] ?? ['title', 'author'];
    processSearch($pdo, $searchString, $filters);
    $_SESSION['search_results'] = $results;
}

function getBookDetails($pdo, $issn) {
    $query = "SELECT book.title, book.summary, book.nbpages, 
                     author.FirstName, author.LastName, author.BirthDate 
              FROM book 
              LEFT JOIN ecrit ON ecrit.ISSN = book.ISSN 
              LEFT JOIN author ON ecrit.Num = author.Num 
              WHERE book.ISSN = :issn";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':issn' => $issn]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($book) {
        // Format the array into an HTML structure
        return "<div class='container'>
                    <h1>" . htmlspecialchars($book['title']) . "</h1>
                    <p class='summary'>Résumé: " . htmlspecialchars($book['summary']) . "</p>
                    <p>Nombre de pages: " . htmlspecialchars($book['nbpages']) . "</p>
                    <p>Auteur: " . htmlspecialchars($book['FirstName']) . " " . htmlspecialchars($book['LastName']) . "</p>
                    <p>Date de naissance: " . htmlspecialchars($book['BirthDate']) . "</p>
                </div>";
    } else {
        return "<div class='container'><p class='not-found'>Livre introuvable.</p></div>";
    }
}





if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['issn'])) {
    $issn = $_POST['issn'];
    
    // Call the function and echo its result
    echo getBookDetails($pdo, $issn);

    exit();
}


?>



<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche dans la Bibliothèque</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
    
    <style>

/* Header styles */
header {
    background-color: #005f73; /* Deep blue background */
    color: white;
    padding: 1rem 2rem; /* Increased padding for better spacing */
    display: flex; /* Flexbox for layout */
    align-items: center; /* Align items vertically */
    justify-content: space-between; /* Space between elements */
}

/* Navigation styles */
nav ul {
    list-style: none;
    padding: 0;
    display: flex; /* Flexbox for horizontal layout */
    margin: 0;
}

nav ul li {
    margin-right: 20px;
}

nav a {
    color: white;
    text-decoration: none;
    padding: 0.5rem 1rem; /* Padding for clickable area */
    transition: background-color 0.3s; /* Smooth transition for hover effect */
}

nav a:hover {
    background-color: #00a896; /* Slightly lighter blue on hover */
}


/* button des filtres  */
.setting-btn {
            width: 25px;
            height: 25px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            background-color: #94d2bd;
            border-radius: 10px;
            cursor: pointer;
            border: none;
            box-shadow: 0px 0px 0px 2px rgb(212, 209, 255);
            transition: background-color 0.3s; /* Smooth transition for hover effect */
        }
        
        .bar {
            width: 50%;
            height: 2px;
            background-color: rgb(229, 229, 229);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            border-radius: 2px;
        }

        .bar::before {
            content: "";
            width: 2px;
            height: 2px;
            background-color: rgb(126, 117, 255);
            position: absolute;
            border-radius: 50%;
            border: 2px solid white;
            transition: all 0.3s;
            box-shadow: 0px 0px 5px white;
        }

        .bar1::before {
            transform: translateX(-4px);
        }

        .bar2::before {
            transform: translateX(4px);
        }

        .setting-btn:hover .bar1::before {
            transform: translateX(4px);
        }

        .setting-btn:hover .bar2::before {
            transform: translateX(-4px);
        }



/* Search bar styles */
#search-bar-container {
    flex-grow: 1; /* Allow the container to grow */
    display: flex;
    justify-content: center; /* Center the search bar */
}

#search-bar {
    display: flex;
    align-items: center;
}

#search-text {
    padding: 0.5rem;
    width: 200px;
    border-radius: 5px; /* Rounded corners */
    border: 1px solid #ddd; /* Light border */
    margin-right: 10px;
    color: black;
}

#search-button {
    padding: 0.5rem 1rem;
    background-color: #94d2bd; /* Light green background */
    border: none;
    border-radius: 5px; /* Rounded corners */
    color: white;
    cursor: pointer;
    transition: background-color 0.3s; /* Smooth transition for hover effect */
}

#search-button:hover {
    background-color: #82bfb9; /* Slightly darker green on hover */
}


/* Login button styles */
#login-button {
    padding: 0.5rem 1rem;
    background-color: #00a896; /* Matching the hover color of nav links */
    border: none;
    border-radius: 5px; /* Rounded corners */
    color: white;
    cursor: pointer;
    transition: background-color 0.3s; /* Smooth transition for hover effect */
}

#login-button:hover {
    background-color: #007f7f; /* Slightly darker shade for hover */
}




.sidebar h3 {
    font-size: 18px; }




.sidebar {
    position: fixed;
    left: -200px; /* Start offscreen */
    width: 200px; /* Width of sidebar */
    top: 0;
    bottom: 0;
    background-color: #f0f0f0;
    transition: left 0.3s; /* Smooth transition for sidebar */
    z-index: 100; /* Above main content */
    padding: 3%;
}

.sidebar.active {
    left: 0; /* Slide into view */
    

}

.main-content {
    transition: margin-left 0.3s, width 0.3s; /* Smooth transition for content */
}

.main-content.active {
    margin-left: 200px; /* Make room for sidebar */
    width: calc(100% - 200px); /* Decrease width */
    margin-top: 0%;
}

#msbo.active {
    position: fixed;
    left: 200px; /* Déplacez le bouton vers la droite avec la sidebar */
    transition: left 0.5s ease; /* Animez le mouvement du bouton */
}




.thesis-box {
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 4px;
    margin-left: 1.5%;
    margin-right: 1.5%;
}

.thesis-title {
    color: #337ab7;
    font-size: 18px;
    font-weight: bold;
}

.thesis-details {
    font-size: 14px;
}

.thesis-category {
    color: #5cb85c;
    float: right;
    font-style: italic;
}

.clearfix::after {
    content: "";
    clear: both;
    display: table;
}




.results-heading {
    font-size: 20px; /* Adjust the font size as needed */
    margin-left: 2%;
    margin-top: 4%;
}

.search-bar-container {
    display: flex;
    justify-content: center; /* Center the search bar */
    margin-top: 5%;
}

.search-input-group {
    display: flex;
    border-radius: 22px; /* Adjust the border-radius to fit the search input */
    border: 2px solid #007bff;
    overflow: hidden;
}

.search-bar-input {
    flex-grow: 1;
    border: none;
    padding: 10px 20px;
    font-size: 18px;
    border-radius: 0px 0 0 0px; /* Adjust the border-radius to fit the container */
}

.search-bar-button {
    background-color: #007bff;
    border: none;
    color: white;
    padding: 10px 20px;
    cursor: pointer;
    border-radius: 0 0px 0px 0; /* Match the border-radius of the input */
    font-size: 18px; /* Match the font size of the input for consistent height */
    box-sizing: border-box; /* Include padding in width and height */
}

.search-bar-input:focus {
    outline: none;
}



/* button fermer */

.button {
  position: relative;
  width: 1.5em;
  height: 1.5em;
  border: none;
  background: rgba(180, 83, 107, 0.4);
  border-radius: 5px;
  transition: background 0.5s;
}

.X {
  content: "";
  position: absolute;
  top: 50%;
  left: 50%;
  width: 1em;
  height: 1.5px;
  background-color: rgb(255, 255, 255);
  transform: translateX(-50%) rotate(45deg);
}

.Y {
  content: "";
  position: absolute;
  top: 50%;
  left: 50%;
  width: 1em;
  height: 1.5px;
  background-color: #fff;
  transform: translateX(-50%) rotate(-45deg);
}


.button:hover {
  background-color: rgb(211, 21, 21);
}

.button:active {
  background-color: rgb(130, 0, 0);
}




/* reset button */

#reset-filters-button {
  width: 23px;
  height: 23px;
  border-radius: 50%;
  background-color: rgb(20, 20, 20);
  border: none;
  font-weight: 600;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.164);
  cursor: pointer;
  transition-duration: 0.3s;
  overflow: hidden;
  position: absolute;
  top: 6.5%;
  left: 70%; 
  gap: 2px;
}

#reset-filters-button .svgIcon {
  width: 10px;
  transition-duration: 0.3s;
}

#reset-filters-button .svgIcon path {
  fill: white;
}

#reset-filters-button:hover {
  transition-duration: 0.3s;
  background-color: rgb(255, 69, 69);
  align-items: center;
  gap: 0;
}

#reset-filters-button .bin-top {
  transform-origin: bottom right;
}

#reset-filters-button:hover .bin-top {
  transition-duration: 0.5s;
  transform: rotate(160deg);
}

/* Modal styles */
/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.6);
}

.modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    width: 70%;
    max-width: 700px;
    overflow: hidden;
}

.close-button {
    color: #0056b3;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.close-button:hover,
.close-button:focus {
    color: #333;
    text-decoration: none;
    cursor: pointer;
}

/* Modal Heading and Paragraph */
#book-details-modal h1 {
    color: #0056b3;
    margin-bottom: 20px;
}

#book-details-modal p {
    margin: 10px 0;
}

/* Summary Style */
.summary {
    background-color: #eef;
    padding: 10px;
    border-left: 4px solid #0056b3;
    margin-bottom: 20px;
    word-wrap: break-word;

}


   </style>
</head>
<body>

<div class="main-content">
<header>
    <nav>
        <ul>
        <li>
        <button class="setting-btn" onclick="toggleSidebar()" id="filter-button">
                    <span class="bar bar1"></span>
                    <span class="bar bar2"></span>
                    <span class="bar bar1"></span>
                </button>
            </li>
            <li><a href="../home/home.php">Home </a></li>
        </ul>
    </nav>
    <div id="search-bar-container">
        <div id="search-bar">
            <!-- Note that the form action needs to point to the script that processes the search -->
            <form method="post" action="" class="search-form">
                <input type="text" id="search-text" name="search" placeholder="Rechercher des livres..." required autocomplete="off">
                <button type="submit" id="search-button">Rechercher</button>
            </form>
        </div>
    </div>
    <a id="login-button" href="../login/login.php">Admin panel</a>
</header>


    <div class="sidebar">
        <form method="post" action="">
            <input type="hidden" name="searchString" value="<?php echo htmlspecialchars($searchString); ?>">
          
            <button class="button"  id="close-sidebar-button">
            <span class="X"></span>
            <span class="Y"></span>

            </button>

            <!-- Options de filtrage -->
            <h3>Filtres:</h3>
            <div>
                <input type="checkbox" name="filter[]" value="title" id="filter_title" <?php if (in_array('title', $filters)) echo 'checked'; ?>>
                <label for="filter_title">Titre</label>
            </div>
            <div>
                <input type="checkbox" name="filter[]" value="author" id="filter_author" <?php if (in_array('author', $filters)) echo 'checked'; ?>>
                <label for="filter_author">Auteur</label>
            </div>

            <h3>Catégorie:</h3>
            <?php foreach ($uniqueWords as $word): ?>
                <div>
                    <input type="checkbox" name="filter_word[]" value="<?php echo htmlspecialchars($word); ?>" id="word_<?php echo htmlspecialchars($word); ?>" <?php if (in_array($word, $_POST['filter_word'] ?? [])) echo 'checked'; ?>>
                    <label for="word_<?php echo htmlspecialchars($word); ?>"><?php echo htmlspecialchars(ucfirst($word)); ?></label>
                </div>
            <?php endforeach; ?>

            <h3>Nationalités de l'auteur:</h3>
            <?php foreach ($nationalities as $nationality): ?>
                <div>
                    <input type="checkbox" name="filter_nationality[]" value="<?php echo htmlspecialchars($nationality); ?>" id="nationality_<?php echo htmlspecialchars($nationality); ?>" <?php if (in_array($nationality, $_POST['filter_nationality'] ?? [])) echo 'checked'; ?>>
                    <label for="nationality_<?php echo htmlspecialchars($nationality); ?>"><?php echo htmlspecialchars($nationality); ?></label>
                </div>
            <?php endforeach; ?>
            <button type="button" id="reset-filters-button">
            <svg
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 69 14"
    class="svgIcon bin-top"
  >
    <g clip-path="url(#clip0_35_24)">
      <path
        fill="black"
        d="M20.8232 2.62734L19.9948 4.21304C19.8224 4.54309 19.4808 4.75 19.1085 4.75H4.92857C2.20246 4.75 0 6.87266 0 9.5C0 12.1273 2.20246 14.25 4.92857 14.25H64.0714C66.7975 14.25 69 12.1273 69 9.5C69 6.87266 66.7975 4.75 64.0714 4.75H49.8915C49.5192 4.75 49.1776 4.54309 49.0052 4.21305L48.1768 2.62734C47.3451 1.00938 45.6355 0 43.7719 0H25.2281C23.3645 0 21.6549 1.00938 20.8232 2.62734ZM64.0023 20.0648C64.0397 19.4882 63.5822 19 63.0044 19H5.99556C5.4178 19 4.96025 19.4882 4.99766 20.0648L8.19375 69.3203C8.44018 73.0758 11.6746 76 15.5712 76H53.4288C57.3254 76 60.5598 73.0758 60.8062 69.3203L64.0023 20.0648Z"
      ></path>
    </g>
    <defs>
      <clipPath id="clip0_35_24">
        <rect fill="white" height="14" width="69"></rect>
      </clipPath>
    </defs>
  </svg>

  <svg
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 69 57"
    class="svgIcon bin-bottom"
  >
    <g clip-path="url(#clip0_35_22)">
      <path
        fill="black"
        d="M20.8232 -16.3727L19.9948 -14.787C19.8224 -14.4569 19.4808 -14.25 19.1085 -14.25H4.92857C2.20246 -14.25 0 -12.1273 0 -9.5C0 -6.8727 2.20246 -4.75 4.92857 -4.75H64.0714C66.7975 -4.75 69 -6.8727 69 -9.5C69 -12.1273 66.7975 -14.25 64.0714 -14.25H49.8915C49.5192 -14.25 49.1776 -14.4569 49.0052 -14.787L48.1768 -16.3727C47.3451 -17.9906 45.6355 -19 43.7719 -19H25.2281C23.3645 -19 21.6549 -17.9906 20.8232 -16.3727ZM64.0023 1.0648C64.0397 0.4882 63.5822 0 63.0044 0H5.99556C5.4178 0 4.96025 0.4882 4.99766 1.0648L8.19375 50.3203C8.44018 54.0758 11.6746 57 15.5712 57H53.4288C57.3254 57 60.5598 54.0758 60.8062 50.3203L64.0023 1.0648Z"
      ></path>
    </g>
    <defs>
      <clipPath id="clip0_35_22">
        <rect fill="white" height="57" width="69"></rect>
      </clipPath>
    </defs>
  </svg>
</button>

            <input type="submit" value="Appliquer">
        </form>
    </div>

<!-- The main content of your page -->
   <!-- The search results and any other content you want to include -->
   <?php if (!empty($results)): ?>
       <h2 class="results-heading"><?php echo count($results); ?> résultat(s) trouvé(s) pour '<?php echo htmlspecialchars($searchString); ?>'</h2>
       
       <?php foreach ($results as $result): ?>
           <div class="thesis-box clearfix">
               <div class="thesis-title">
                   <!-- Update the link to use JavaScript for AJAX call -->
                   <a href="javascript:void(0);" onclick="fetchBookDetails('<?php echo htmlspecialchars($result['ISSN']); ?>')">
                       <?php echo htmlspecialchars($result['title']); ?>
                   </a>
               </div>          

               <div class="thesis-details">
                   écrit par <strong><?php echo htmlspecialchars($result['author_name']); ?></strong>
                   et sa nationalité <strong><?php echo htmlspecialchars($result['Nationality']); ?></strong>
                   <span class="thesis-category"><?php echo htmlspecialchars($result['category']); ?></span>
               </div>
           </div>
       <?php endforeach; ?>
   <?php elseif (isset($searchString) && $searchString != ''): ?>
       <p class="results-heading">Aucun résultat trouvé pour '<?php echo htmlspecialchars($searchString); ?>'.</p>
   <?php endif; ?>

</div>

<!-- Container for displaying book details -->
<div id="book-details-container"></div>
<!-- Modal structure -->
<div id="bookModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <div id="book-details-modal"></div>
    </div>
</div>

<!-- jQuery inclusion -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<script>
 // JavaScript function for AJAX call
function fetchBookDetails(issn) {
    $.ajax({
        url: 'recherche.php',
        type: 'POST',
        data: { 'issn': issn },
        success: function(response) {
            $('#book-details-modal').html(response);
            // Show the modal
            $('#bookModal').css('display', 'block');
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("AJAX request failed:", textStatus, errorThrown);
        }
    });
}

// When the user clicks on <span> (x), close the modal
$('.close-button').click(function() {
    $('#bookModal').css('display', 'none');
});

// When the user clicks anywhere outside of the modal, close it
$(window).click(function(event) {
    if ($(event.target).is('#bookModal')) {
        $('#bookModal').css('display', 'none');
    }
});



$(function() {

    

     // Event handler for the search form submission
     $('.search-form').on('submit', function() {
        // Clear the search results session variable on new search
        $.post('clear_session.php', function(response) {
            console.log(response); // Log the response from the server
        });
    });

    // Event handler for opening the sidebar
    $('#filter-button').click(function() {
        $('.sidebar').toggleClass('active');
        $('.main-content').toggleClass('active');
    });

    // Event handler for closing the sidebar
    $('#close-sidebar-button').click(function() {
        $('.sidebar').removeClass('active');
        $('.main-content').removeClass('active');
    });

    // Event handler for the reset filters button
    $('#reset-filters-button').click(function() {
        // Uncheck all checkbox filters in the sidebar
        $('.sidebar input[type="checkbox"]').prop('checked', false);

       
    });

});
</script>




</body>
</html>
<?php
session_start();

// Paramètres de connexion à la base de données
$host = 'localhost';
$dbname = 'projetdb';
$db_username = 'root';
$db_password = '';

$searchString = ''; // Initialisez $searchString
$filters = []; // Initialisez les filtres
$results = []; // Initialisez les résultats
$uniqueWords = []; // Pour stocker les mots uniques de la catégorie
$nationalities = []; // Pour stocker les nationalités uniques

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer toutes les catégories
    $categoryQuery = "SELECT category FROM book";
    $stmt = $pdo->query($categoryQuery);
    $allCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Diviser chaque catégorie en mots, convertir en minuscules, et les fusionner dans un seul tableau
    $allWords = [];
    foreach ($allCategories as $category) {
        $words = explode(' ', strtolower($category));
        $allWords = array_merge($allWords, $words);
    }

    // Filtrer les mots uniques
    $uniqueWords = array_unique($allWords);

    // Récupérer les nationalités uniques
    $nationalityQuery = "SELECT DISTINCT Nationality FROM author";
    $nationalityStmt = $pdo->query($nationalityQuery);
    $nationalities = $nationalityStmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}

// Vérifiez si le formulaire de recherche principal a été soumis
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $searchString = strtolower($_POST['search']);
    $filters = $_POST['filter'] ?? ['title', 'author']; // Appliquez les filtres par défaut si aucun n'est sélectionné
    processSearch($pdo, $searchString, $filters);
}

// Vérifiez si le formulaire de filtre a été soumis
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['searchString'])) {
    $searchString = strtolower($_POST['searchString']);
    $filters = $_POST['filter'] ?? ['title', 'author'];
    processSearch($pdo, $searchString, $filters);
}

function processSearch($pdo, $searchString, $filters) {
    global $results;

    // Construisez les conditions de recherche
    $titleAuthorConditions = [];
    $searchParams = [];
    $i = 0;
    if (in_array('title', $filters)) {
        $i++;
        $titleAuthorConditions[] = "LOWER(book.title) LIKE :searchTitle";
        $searchParams[':searchTitle'] = "%{$searchString}%";
    }
    if (in_array('author', $filters)) {
        $i++;
        $titleAuthorConditions[] = "LOWER(CONCAT(author.FirstName, ' ', author.LastName)) LIKE :searchAuthor";
        $searchParams[':searchAuthor'] = "%{$searchString}%";
    }

    // Regroupez les conditions de titre et d'auteur avec un OR
    $searchConditions = [];
    if (!empty($titleAuthorConditions)) {
        $searchConditions[] = '(' . implode(' OR ', $titleAuthorConditions) . ')';
    }

    // Conditions pour les mots de catégorie
    $wordConditions = [];
    if (!empty($_POST['filter_word'])) {
        foreach ($_POST['filter_word'] as $word) {
            $i++;
            $paramName = ":word$i";
            $wordConditions[] = "LOWER(book.category) LIKE $paramName";
            $searchParams[$paramName] = '%' . $word . '%';
        }
    }
    if (!empty($wordConditions)) {
        $searchConditions[] = '(' . implode(' OR ', $wordConditions) . ')';
    }

    // Conditions pour les nationalités
    $nationalityConditions = [];
    if (!empty($_POST['filter_nationality'])) {
        foreach ($_POST['filter_nationality'] as $nationality) {
            $i++;
            $paramName = ":nationality$i";
            $nationalityConditions[] = "author.Nationality = $paramName";
            $searchParams[$paramName] = $nationality;
        }
    }
    if (!empty($nationalityConditions)) {
        $searchConditions[] = '(' . implode(' OR ', $nationalityConditions) . ')';
    }
    
    // Construire la requête finale
    $query = "
        SELECT book.title, book.category, CONCAT(author.FirstName, ' ', author.LastName) AS author_name, author.Nationality
        FROM book 
        LEFT JOIN ecrit ON ecrit.ISSN = book.ISSN 
        LEFT JOIN author ON ecrit.Num = author.Num
    ";
    if (!empty($searchConditions)) {
        $query .= " WHERE " . implode(' AND ', $searchConditions);
    }

    // Exécutez la requête préparée
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


?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche dans la Bibliothèque</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
    
    <style>

.search-form {
    display: flex;
    justify-content: center;
    margin-top: -2%;
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
    margin-left: 250px; /* Make room for sidebar */
    width: calc(100% - 200px); /* Decrease width */
    margin-top: 5%;
}

#msbo.active {
    position: fixed;
    left: 200px; /* Déplacez le bouton vers la droite avec la sidebar */
    transition: left 0.5s ease; /* Animez le mouvement du bouton */
}


button {
  width: fit-content;
  min-width: 100px;
  height: 45px;
  padding: 8px;
  border-radius: 5px;
  border: 2.5px solid #E0E1E4;
  box-shadow: 0px 0px 20px -20px;
  cursor: pointer;
  background-color: white;
  transition: all 0.2s ease-in-out 0ms;
  user-select: none;
  font-family: 'Poppins', sans-serif;
  
}


button#filter-button {
    position: absolute; /* Position relative to main-content because it's set to relative */
    top: 5%; /* Adjust as needed */
    left: 1%; /* Adjust as needed */
   }

button:hover {
  background-color: #F2F2F2;
  box-shadow: 0px 0px 20px -18px;
}

button:active {
  transform: scale(0.95);
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
   </style>
</head>
<body>
<button type="button" id="filter-button">
    Filtres
</button>


    <div class="sidebar">
        <form method="post" action="">
            <input type="hidden" name="searchString" value="<?php echo htmlspecialchars($searchString); ?>">
          
            <button type="button" id="close-sidebar-button">Fermer</button>

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

            <input type="submit" value="Appliquer">
        </form>
    </div>

    <div class="main-content">
        <!-- Formulaire de recherche principal -->
        <form method="post" action="" class="search-form">
        <div class="search-bar-container">
        <div class="search-input-group">
            <input type="text" class="search-bar-input" placeholder="Recherche..." name="search" required />
            <button type="submit" class="search-bar-button">Rechercher</button>
        </div>
    </div>
        </form>

        <!-- Résultats de la recherche -->
        <?php if (!empty($results)): ?>
        <h2 class="results-heading"><?php echo count($results); ?> résultat(s) trouvé(s) pour '<?php echo htmlspecialchars($searchString); ?>'</h2>
        <?php foreach ($results as $result): ?>
            <div class="thesis-box clearfix">
                <div class="thesis-title"><?php echo htmlspecialchars($result['title']); ?></div>
                <div class="thesis-details">
                    écrit par <strong><?php echo htmlspecialchars($result['author_name']); ?></strong>
                    et sa nationalité <strong><?php echo htmlspecialchars($result['Nationality']); ?></strong>
                    <span class="thesis-category"><?php echo htmlspecialchars($result['category']); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php elseif ($searchString): ?>
        <p class="results-heading">Aucun résultat trouvé pour '<?php echo htmlspecialchars($searchString); ?>'.</p>
    <?php endif; ?>
</div>

  
    <!-- Inclusion de jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>


    <script>
$(function() {
    // Gestionnaire d'événements pour ouvrir la sidebar
    $('#filter-button').click(function() {
        $('.sidebar').toggleClass('active');
        $('.main-content').toggleClass('active');
    });

    // Gestionnaire d'événements pour fermer la sidebar
    $('#close-sidebar-button').click(function() {
        $('.sidebar').removeClass('active');
        $('.main-content').removeClass('active');
    });
});
</script>




</body>
</html>
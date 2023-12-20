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

// Vérifiez si le formulaire a été soumis
if (isset($_GET['search'])) {
    $searchString = strtolower($_GET['search']); // Convertissez la chaîne de recherche en minuscules

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

        $filters = $_GET['filter'] ?? ['title', 'author']; // Appliquez les filtres par défaut si aucun n'est sélectionné

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
        if (!empty($_GET['filter_word'])) {
            foreach ($_GET['filter_word'] as $word) {
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
        if (!empty($_GET['filter_nationality'])) {
            foreach ($_GET['filter_nationality'] as $nationality) {
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

        $stmt = $pdo->prepare($query);
        foreach ($searchParams as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch(PDOException $e) {
        echo "Erreur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche dans la Bibliothèque</title>
    <style>
        .input-container {
            width: 220px;
            position: relative;
        }

        .icon {
            position: absolute;
            right: 10px;
            top: calc(50% + 5px);
            transform: translateY(calc(-50% - 5px));
        }

        .input {
            width: 100%;
            height: 40px;
            padding: 10px;
            transition: .2s linear;
            border: 2.5px solid black;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .input:focus {
            outline: none;
            border: 0.5px solid black;
            box-shadow: -5px -5px 0px black;
        }

        .input-container:hover > .icon {
            animation: anim 1s linear infinite;
        }

        @keyframes anim {
            0%, 100% {
                transform: translateY(calc(-50% - 5px)) scale(1);
            }

            50% {
                transform: translateY(calc(-50% - 5px)) scale(1.1);
            }
        }
    </style>
</head>
<body>
    <form method="get" action="">
        <input type="text" name="search" placeholder="Rechercher..." required>
        <input type="submit" value="Recherche">
        
        <h3>Filtres:</h3>
        <div>
            <input type="checkbox" name="filter[]" value="title" id="filter_title" <?php if (in_array('title', $filters)) echo 'checked'; ?>>
            <label for="filter_title">Titre</label>
        </div>
        <div>
            <input type="checkbox" name="filter[]" value="author" id="filter_author" <?php if (in_array('author', $filters)) echo 'checked'; ?>>
            <label for="filter_author">Auteur</label>
        </div>
        <div>
    <h3>Catégorie:</h3>
    <?php foreach ($uniqueWords as $word): ?>
        <div>
            <input type="checkbox" name="filter_word[]" value="<?php echo htmlspecialchars($word); ?>" id="word_<?php echo htmlspecialchars($word); ?>" <?php if (in_array($word, $_GET['filter_word'] ?? [])) echo 'checked'; ?>>
            <label for="word_<?php echo htmlspecialchars($word); ?>"><?php echo htmlspecialchars(ucfirst($word)); ?></label>
        </div>
    <?php endforeach; ?>
</div>

            <h3>Nationalités de l'auteur:</h3>
            <?php foreach ($nationalities as $nationality): ?>
                <div>
                    <input type="checkbox" name="filter_nationality[]" value="<?php echo htmlspecialchars($nationality); ?>" id="nationality_<?php echo htmlspecialchars($nationality); ?>" <?php if (in_array($nationality, $_GET['filter_nationality'] ?? [])) echo 'checked'; ?>>
                    <label for="nationality_<?php echo htmlspecialchars($nationality); ?>"><?php echo htmlspecialchars($nationality); ?></label>
                </div>
            <?php endforeach; ?>
        </div>
    </form>

    <?php if ($searchString && !empty($results)): ?>
        <h2>Résultats de recherche pour '<?php echo htmlspecialchars($searchString); ?>'</h2>

        <?php foreach ($results as $result): ?>
            <p><?php echo htmlspecialchars($result['title']); ?> - Auteur: <?php echo htmlspecialchars($result['author_name']); ?> - Catégorie: <?php echo htmlspecialchars($result['category']); ?> - Nationalité: <?php echo htmlspecialchars($result['Nationality']); ?></p>
        <?php endforeach; ?>
    <?php elseif ($searchString): ?>
        <p>Aucun résultat trouvé pour '<?php echo htmlspecialchars($searchString); ?>'.</p>
    <?php endif; ?>
</body>
</html>

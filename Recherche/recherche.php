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

// Vérifiez si le formulaire a été soumis
if (isset($_GET['search'])) {
    $searchString = strtolower($_GET['search']); // Convertissez la chaîne de recherche en minuscules

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $filters = $_GET['filter'] ?? ['title', 'author', 'category']; // Appliquez tous les filtres par défaut si aucun n'est sélectionné

        // Construisez les conditions de recherche en fonction des filtres
        $searchConditions = [];
        $searchParams = [];
        if (in_array('title', $filters)) {
            $searchConditions[] = "LOWER(book.title) LIKE :search";
            $searchParams[':search'] = "%{$searchString}%";
        }
        if (in_array('author', $filters)) {
            $searchConditions[] = "LOWER(CONCAT(author.FirstName, ' ', author.LastName)) LIKE :searchAuthor";
            $searchParams[':searchAuthor'] = "%{$searchString}%";
        }
        if (in_array('category', $filters)) {
            $searchConditions[] = "LOWER(book.category) LIKE :searchCategory";
            $searchParams[':searchCategory'] = "%{$searchString}%";
        }

        $query = "
            SELECT book.title, book.category 
            FROM book 
            LEFT JOIN ecrit ON ecrit.ISSN = book.ISSN 
            LEFT JOIN author ON ecrit.Num = author.Num
        ";
        if (!empty($searchConditions)) {
            $query .= " WHERE " . implode(' OR ', $searchConditions);
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($searchParams);
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
</head>
<body>
    <form method="get" action="">
        <input type="text" name="search" placeholder="Tapez 'bad' pour chercher..." required>
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
            <input type="checkbox" name="filter[]" value="category" id="filter_category" <?php if (in_array('category', $filters)) echo 'checked'; ?>>
            <label for="filter_category">Catégorie</label>
        </div>
    </form>

    <?php if ($searchString && !empty($results)): ?>
        <h2>Résultats de recherche pour '<?php echo htmlspecialchars($searchString); ?>'</h2>

        <?php foreach ($results as $result): ?>
            <p><?php echo htmlspecialchars($result['title']); ?> - Catégorie: <?php echo htmlspecialchars($result['category']); ?></p>
        <?php endforeach; ?>
    <?php elseif ($searchString): ?>
        <p>Aucun résultat trouvé pour '<?php echo htmlspecialchars($searchString); ?>'.</p>
    <?php endif; ?>
</body>
</html>

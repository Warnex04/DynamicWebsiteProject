<?php
    session_start();
    if (!isset($_SESSION['username'])) {
        header('Location: sign-in.php');
    exit();
    }
    $username = $_SESSION['username'];

    include('db.php');
    // Récupérer tous les utilisateurs
    $query = "SELECT id, username FROM users";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>

<body>
    <h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>
    <a href="logout.php">Logout</a>
    <a href="change_password.php">Modifier le mot de passe</a>

    <h2>Liste des Utilisateurs</h2>
    <form action="change_other_user_password.php" method="get">
        <label for="user_id">Sélectionnez un utilisateur :</label>
        <select name="user_id" id="user_id">
            <?php foreach ($users as $user): ?>
                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Modifier le Mot de Passe</button>
    </form>
</body>
</html>


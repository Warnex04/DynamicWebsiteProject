<?php
include('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['new_username'];
    $password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Vérifier si les mots de passe correspondent
    if ($password !== $confirm_password) {
        $error_message = "Les mots de passe ne correspondent pas.";
    } else {
        // Hashage du mot de passe
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Vérifiez d'abord si le nom d'utilisateur existe déjà
        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Le nom d'utilisateur existe déjà
            $error_message = "Le nom d'utilisateur existe déjà.";
        } else {
            // Le nom d'utilisateur n'existe pas, procédez à l'inscription
            $query = "INSERT INTO users (username, password) VALUES (:username, :hashed_password)";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':hashed_password', $hashed_password, PDO::PARAM_STR);

            try {
                $stmt->execute();
                // Redirection vers sign-in.php après inscription réussie
                header('Location: sign-in.php');
                exit();
            } catch (PDOException $e) {
                $error_message = "Erreur: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
</head>
<body>
    <h2>Sign Up</h2>
    <?php if (isset($error_message)) : ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>
    
    <form method="post" action="">
        <label for="new_username">Username:</label>
        <input type="text" name="new_username" required><br>
        <label for="new_password">Password:</label>
        <input type="password" name="new_password" required><br>
        <label for="confirm_password">Confirm Password:</label>
        <input type="password" name="confirm_password" required><br>
        <button type="submit">Enregistrer</button>
    </form>
</body>
</html>
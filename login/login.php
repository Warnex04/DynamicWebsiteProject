<?php
session_start();
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mail = $_POST['username'];
    $password = $_POST['password'];

    // Rechercher l'utilisateur dans la base de données
    $query = "SELECT * FROM admin_library WHERE Mail = :Mail";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':Mail', $mail, PDO::PARAM_STR);
    $stmt->execute();

    if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Vérifiez le mot de passe
        if (password_verify($password, $user['Password'])) {
            $_SESSION['user_id'] = $user['ID'];
            $_SESSION['username'] = $user['Mail']; // ou utilisez FirstName et LastName si vous préférez
            header('Location: ../admin_dashboard/admin_dashboard.php');
            exit();
        } else {
            $error_message = 'Identifiants incorrects';
        }
    } else {
        $error_message = 'Identifiants incorrects';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <?php if (isset($error_message)) : ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>
    
    <form method="post" action="">
        <label for="username">Mail:</label>
        <input type="email" name="username" required><br>
        <label for="password">Mot de passe:</label>
        <input type="password" name="password" required><br>
        <button type="submit">Connexion</button>
    </form>
    <a href="sign-up.php">Enregistrer</a>

</body>
</html>

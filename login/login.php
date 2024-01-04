<?php
session_start();
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mail = $_POST['email'];
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
            $_SESSION['email'] = $user['Mail']; // ou utilisez FirstName et LastName si vous préférez
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
    <link rel="stylesheet" href="./login design/style.css">
</head>

<body>    
    <form method="post" action="" class="form">
        <h2 class="heading">Login</h2>
        <?php if (isset($error_message)) : ?>
            <p style="color: red;"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <div class="input-group">
        <label for="email">Mail:</label>
        <input type="email" name="email" required class="input" id="email" placeholder="Email"><br>
        
        <label for="password">Mot de passe:</label>
        <input type="password" name="password" required  class="input" id="password" placeholder="Password"><br>
        
        <button type="submit" class="btn">Connexion</button>

        <a href="sign-up.php">Enregistrer</a>
        </div>
    </form>
    

</body>
</html>

<?php
session_start();
include('db.php');

if (!isset($_SESSION['username'])) {
    header('Location: sign-in.php');
    exit();
}

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // Vérifier si le nouveau mot de passe et la confirmation correspondent
    if ($new_password !== $confirm_new_password) {
        $error_message = 'Les nouveaux mots de passe ne correspondent pas.';
    } 
    if ($new_password == $current_password) {
        $error_message = 'Le nouveau mot de passe correspond au mot de passe actuel.';
    } 
    else {
        // Vérifier le mot de passe actuel et le mettre à jour
        $username = $_SESSION['username'];
        $query = "SELECT password FROM users WHERE username = :username";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = :new_password WHERE username = :username";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->bindParam(':new_password', $hashed_password, PDO::PARAM_STR);
            $update_stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $update_stmt->execute();

            $error_message = 'Mot de passe mis à jour avec succès.';

            try {
                $stmt->execute();
                // Redirection vers sign-in.php après inscription réussie
                header('Location: sign-in.php');
                exit();
            } catch (PDOException $e) {
                $error_message = "Erreur: " . $e->getMessage();
            }
        } else {
            $error_message = 'Mot de passe actuel incorrect.';
        }
    }

}
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier le Mot de Passe</title>
</head>
<body>
    <h2>Modifier le Mot de Passe</h2>
    <?php if (!empty($error_message)) : ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <form method="post" action="">
        <label for="current_password">Mot de Passe Actuel:</label>
        <input type="password" name="current_password" required><br>
        <label for="new_password">Nouveau Mot de Passe:</label>
        <input type="password" name="new_password" required><br>
        <label for="confirm_new_password">Confirmer Nouveau Mot de Passe:</label>
        <input type="password" name="confirm_new_password" required><br>
        <button type="submit">Modifier</button>
    </form>
</body>
</html>
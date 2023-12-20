<?php
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['new_username']; // ou Mail si vous utilisez Mail comme identifiant
    $password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $firstName = $_POST['FirstName'];
    $lastName = $_POST['LastName'];
    $mail = $_POST['Mail'];
    $phone = $_POST['Phone'];

    // Vérifier si les mots de passe correspondent
    if ($password !== $confirm_password) {
        $error_message = "Les mots de passe ne correspondent pas.";
    } else {
        // Hashage du mot de passe
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Vérifiez si le mail existe déjà
        $query = "SELECT * FROM admin_library WHERE Mail = :mail";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':mail', $mail, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $error_message = "Un compte avec ce mail existe déjà.";
        } else {
            $query = "INSERT INTO admin_library (FirstName, LastName, Mail, Password, Phone) VALUES (:firstName, :lastName, :mail, :hashed_password, :phone)";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':firstName', $firstName, PDO::PARAM_STR);
            $stmt->bindParam(':lastName', $lastName, PDO::PARAM_STR);
            $stmt->bindParam(':mail', $mail, PDO::PARAM_STR);
            $stmt->bindParam(':hashed_password', $hashed_password, PDO::PARAM_STR);
            $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);

            try {
                $stmt->execute();
                header('Location: login.php');
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
    <title>Inscription Admin</title>
</head>
<body>
    <h2>Inscription Admin</h2>
    <?php if (isset($error_message)) : ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>
    
    <form method="post" action="">
        <label for="FirstName">Prénom:</label>
        <input type="text" name="FirstName" required><br>
        <label for="LastName">Nom:</label>
        <input type="text" name="LastName" required><br>
        <label for="Mail">Mail:</label>
        <input type="email" name="Mail" required><br>
        <label for="Phone">Téléphone:</label>
        <input type="text" name="Phone" required><br>
        <label for="new_password">Mot de passe:</label>
        <input type="password" name="new_password" required><br>
        <label for="confirm_password">Confirmer le mot de passe:</label>
        <input type="password" name="confirm_password" required><br>
        <button type="submit">Enregistrer</button>
    </form>
</body>
</html>

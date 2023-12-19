<?php
include('db.php');

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    // Mettre à jour le mot de passe
    $query = "UPDATE users SET password = :new_password WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':new_password', $new_password, PDO::PARAM_STR);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        $success_message = "Mot de passe modifié avec succès.";
        try {
            $stmt->execute();
            // Redirection vers sign-in.php après inscription réussie
            header('Location: dashboard.php');
            exit();
        } catch (PDOException $e) {
            $error_message = "Erreur: " . $e->getMessage();
        }
    } 
    else {
        $error_message = "Erreur lors de la modification du mot de passe.";
    }
}

// Récupérer l'ID de l'utilisateur sélectionné
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Changer le Mot de Passe</title>
</head>
<body>
    <h2>Changer le Mot de Passe</h2>
    <?php if ($error_message): ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <p style="color: green;"><?php echo $success_message; ?></p>
    <?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
        <label for="new_password">Nouveau Mot de Passe:</label>
        <input type="password" name="new_password" required><br>
        <button type="submit">Changer</button>
    </form>
</body>
</html>
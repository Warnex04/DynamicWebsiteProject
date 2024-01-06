<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail de la Bibliothèque</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="#new-arrivals">Nouveautés</a></li>
                <li><a href="#popular-books">Livres populaires</a></li>
            </ul>
        </nav>
        <div id="search-bar-container">
            <form action="../Recherche/recherche.php" method="post">
                <div id="search-bar">
                    <!--<input type="text" name="search-text" id="search-text" placeholder="Rechercher des livres...">-->
                    <button id="search-button" href="Recherche\recherche.php">Rechercher</button>
                </div>
            </form>
        </div>


        <a id="login-button" href="../login/login.php">Admin panel</a>

    </header>
   
    <main>
        <section id="new-arrivals">
            <!--<h2>Nouveautés</h2>-->
            <div class="book-grid">
                <!-- Contenu dynamique ici -->
            </div>
        </section>
        <section id="popular-books">
            <!--<h2>Livres populaires</h2>
            <div class="book-grid">-->
                <!-- Contenu dynamique ici -->
            </div>
        </section>
        <!-- Autres sections -->
    </main>
    
    <footer>
        <!--<p>&copy; 2023 Portail de la Bibliothèque</p>-->
    </footer>
</body>
</html>

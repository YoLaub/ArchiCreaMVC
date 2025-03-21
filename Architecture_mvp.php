<?php

function createDirectory($path)
{
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
        echo "Dossier créé : $path\n";
    }
}

function createFile($path, $content = '')
{
    if (!file_exists($path)) {
        file_put_contents($path, $content);
        echo "Fichier créé : $path\n";
    }
}

// Création de l'arborescence
$folders = ['app/Controllers', 'app/Models', 'app/Views', 'app/Class','public/css', 'public/images','public/fonts','public/js'];
foreach ($folders as $folder) {
    createDirectory($folder);
}

// Fichiers config
createFile('app/config.php', <<<'EOD'

<?php
//Chemin absolu de l'application :
define("RACINE", dirname(__DIR__)."/");

?>


EOD
);
// Fichiers de base
createFile('index.php', <<<'EOD'

<?php
// Inclusion des fichiers de configuration et des classes principales
require dirname(__FILE__) . "/app/config.php";
require RACINE . "app/Class/Routes.php";
require RACINE . "app/Class/RoutesPrive.php";
require RACINE . "app/Models/connectDb.php";

// Démarrer la session si elle n'est pas déjà active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si on est sur une route admin
if (isset($_GET["admin"]) && $_GET["admin"] === "1") {
    $route = new RoutesPrive();
} else {
    $route = new Routes();
}

// Récupérer l'action demandée, sinon utiliser la valeur par défaut
$action = $_GET["action"] ?? "defaut";
$route->redirection($action); 

?>


EOD
);
// Créer le fichier connectDb.php
createFile('app/Models/connectDb.php', <<<'EOD'
<?php
require_once RACINE . "vendor/autoload.php";

abstract class DbConnect {
    public static function connexion() {
        try {
            $dsn = 'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE');
            $pdo = new PDO($dsn, getenv('DB_USERNAME'), getenv('DB_PASSWORD'), array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"'));
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            return $e->getMessage() . "<br />Erreur de connexion PDO !";
        }
    }

    protected static function executerRequete($sql, $values = []) {
        try {
            $query = self::connexion()->prepare($sql);
            if (!empty($values)) {
                foreach ($values as $key => $value) {
                    $param = (strpos($key, ":") === 0) ? $key : ":" . $key;
                    $query->bindValue($param, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
            }
            $query->execute();
            return $query;
        } catch (Exception $e) {
            return $e->getMessage() . "<br />Impossible d'envoyer les données dans la base de données !";
        }
    }
}
EOD
);
// Créer le fichier Routes.php
createFile('app/Class/Routes.php', <<<'EOD'
<?php
class Routes {
    private array $lesActions;
    private string $action;
    private const DEFAULT_ROUTE = 'accueil_ctrl.php';
    private const ERROR_ROUTE = 'page404_ctrl.php';

    public function __construct() {
        $this->lesActions = [
            'defaut'  => self::DEFAULT_ROUTE,
            'accueil' => self::DEFAULT_ROUTE,
            'page404' => self::ERROR_ROUTE,
        ];
    }

    public function redirection(string $action = 'defaut'): void {
        $this->action = $action;
        $controller_id = $this->lesActions[$this->action] ?? self::ERROR_ROUTE;

        try {
            require $this->getFilePath($controller_id);
        } catch (Exception $e) {
            error_log($e->getMessage());
            require $this->getFilePath(self::ERROR_ROUTE);
        }
    }

    private function getFilePath(string $file): string {
        $path = RACINE . 'app/Controllers/' . $file;
        if (!file_exists($path)) {
            throw new Exception("Le fichier de contrôle {$file} est introuvable.");
        }
        return $path;
    }
}
EOD
);
// Créer le fichier RoutesPrive.php
createFile('app/Class/RoutesPrive.php', <<<'EOD'
<?php

class RoutesPrive {
    private array $adminActions; // Tableau associatif des actions du back-office
    private string $action;
    private const ERROR_ROUTE = "page404_ctrl.php"; // Route d'erreur

    public function __construct() {
        // Liste des actions disponibles uniquement pour les administrateurs
        $this->adminActions = [
            "accueilBo" => "#",
            "utilisateur" => "#",
            "produit" => "#",
            "fiche" => "#",
            "page404" => self::ERROR_ROUTE,
        ];
    }

    public function redirection(string $action = "accueilBo"): void {
        session_start(); // Démarre la session (nécessaire pour vérifier le rôle de l'utilisateur)

        // Vérifie si l'utilisateur est bien un administrateur
        if (!$this->isAdmin()) {
            header("Location: app/controleurs/" . self::ERROR_ROUTE);
            exit;
        }

        $this->action = $action;

        // Vérifie si l'action demandée existe, sinon, redirige vers la page 404
        $controller_id = $this->adminActions[$this->action] ?? self::ERROR_ROUTE;

        try {
            require $this->getFilePath($controller_id);
        } catch (Exception $e) {
            error_log($e->getMessage()); // Log l'erreur dans un fichier
            require $this->getFilePath(self::ERROR_ROUTE);
        }
    }

    private function getFilePath(string $file): string {
        // Chemin vers les fichiers du back-office
        $path = RACINE . "app/controleurs/admin/" . $file; 

        // Vérifie si le fichier existe avant de l'inclure
        if (!file_exists($path)) {
            throw new Exception("Le fichier de contrôle '$file' est introuvable.");
        }
        return $path;
    }

    private function isAdmin(): bool {
        // Vérifie si la session contient un rôle administrateur
        return isset($_SESSION['role']) && $_SESSION['role'] === "admin";
    }
}
EOD
);
// Créer le fichier page_accueil.php
createFile('app/Views/page_accueil.php', <<<'EOD'
<h1>Redirection ok</h1>
EOD
);
// Créer le fichier accueil_ctrl.php
createFile('app/Controllers/accueil_ctrl.php', <<<'EOD'
<?php
    //Affichage des Views
    include_once RACINE . "app/Views/page_header.php";
    include_once RACINE . "app/Views/page_accueil.php";
    include_once RACINE . "app/Views/page_footer.php"; 


?>
EOD
);
// Créer le fichier page404_ctrl.php
createFile('app/Controllers/page404_ctrl.php', <<<'EOD'
<?php
    //Affichage des Views
    include_once RACINE . "app/Views/page_header.php";
    include_once RACINE . "app/Views/page404.php";
    include_once RACINE . "app/Views/page_footer.php"; 


?>
EOD
);
// Créer le fichier page404.php
createFile('app/Views/page404.php', <<<'EOD'
<h1>Page404</h1>
EOD
);

// Créer le fichier page_header.php
createFile('app/Views/page_header.php', <<<'EOD'
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Découvrez Orb'E, l'assistant personnel intelligent et innovant. Jouez à notre mini-jeu interactif, cumulez des points et bénéficiez de réductions exclusives sur votre achat. Un mélange unique de technologie !">
    <meta name="language" content="fr">
    <meta name="Geography" content="Vannes, FR, 56000">
    <meta name="customer" content="FR-USER-<?php 
                                                if(isset($_SESSION['user_id'])){
                                                    echo $_SESSION['user_id'];
                                                }else{
                                                    echo "00";
                                                } ?> ">
    <meta name="author" content="AY-Lab">

    <link rel="icon" href="./publique/images/logo/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="./publique/css/style.css">
    <title>Orb'E</title>
</head>

<body>
EOD
);

// Créer le fichier page_footer.php
createFile('app/Views/page_footer.php', <<<'EOD'
</body>
</html>
EOD
);

echo "Fichiers créés avec succès !\n";

// Vérification de Composer
if (!file_exists('composer.phar')) {
    echo "Installation de Composer...\n";
    copy('https://getcomposer.org/installer', 'composer-setup.php');
    shell_exec('php composer-setup.php');
    unlink('composer-setup.php');
}

// Installation des dépendances
shell_exec('php composer.phar require vlucas/phpdotenv');

echo "\nInstallation terminée ! Vous pouvez commencer à coder.";

<?php
// ══ CONNEXION BASE DE DONNÉES — WampServer local ══

$host     = "localhost";               // WampServer utilise toujours localhost
$user     = "root";                    // utilisateur par défaut WampServer
$password = "";                        // mot de passe vide par défaut sur WampServer
$database = "if0_41629912_automarket"; // nom de votre base de données

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Erreur de connexion : " . mysqli_connect_error());
}

// Encodage UTF-8 pour les caractères arabes et français
mysqli_set_charset($conn, "utf8mb4");
?>
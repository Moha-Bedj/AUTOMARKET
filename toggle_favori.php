<?php
session_start();
require_once 'connexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['idUtilisateur'])) {
    echo json_encode(['success' => false, 'needLogin' => true]);
    exit;
}

$idUser = $_SESSION['idUtilisateur'];
$action = $_POST['action'] ?? 'toggle';
$idAnnonce = $_POST['idAnnonce'] ?? '';

if (!$idAnnonce) {
    echo json_encode(['success' => false, 'message' => 'ID annonce manquant']);
    exit;
}

$idUserSql = mysqli_real_escape_string($conn, $idUser);
$idAnnonceSql = mysqli_real_escape_string($conn, $idAnnonce);

if ($action === 'toggle') {
    // Vérifier si déjà en favoris
    $rCheck = mysqli_query($conn, "
        SELECT idFav FROM Favoris 
        WHERE idUtilisateur = '$idUserSql' 
        AND idAnnonce = '$idAnnonceSql' 
        LIMIT 1
    ");
    
    if ($rCheck && mysqli_num_rows($rCheck) > 0) {
        // Retirer des favoris
        $fav = mysqli_fetch_assoc($rCheck);
        $idFav = mysqli_real_escape_string($conn, $fav['idFav']);
        mysqli_query($conn, "DELETE FROM Favoris WHERE idFav = '$idFav'");
        
        echo json_encode([
            'success' => true,
            'favori' => false,
            'message' => 'Retiré des favoris'
        ]);
    } else {
        // Ajouter aux favoris
        $idFav = sprintf('%s-%s-%s-%s-%s',
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(6))
        );
        $now = date('Y-m-d H:i:s');
        
        $ok = mysqli_query($conn, "
            INSERT INTO Favoris (idFav, idUtilisateur, idAnnonce, dateAjout) 
            VALUES ('$idFav', '$idUserSql', '$idAnnonceSql', '$now')
        ");
        
        if ($ok) {
            echo json_encode([
                'success' => true,
                'favori' => true,
                'message' => 'Ajouté aux favoris'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout aux favoris'
            ]);
        }
    }
} elseif ($action === 'check') {
    // Vérifier le statut favori
    $rCheck = mysqli_query($conn, "
        SELECT idFav FROM Favoris 
        WHERE idUtilisateur = '$idUserSql' 
        AND idAnnonce = '$idAnnonceSql' 
        LIMIT 1
    ");
    
    echo json_encode([
        'success' => true,
        'favori' => ($rCheck && mysqli_num_rows($rCheck) > 0)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Action inconnue']);
}
?>
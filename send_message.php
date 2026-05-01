<?php
session_start();
require_once 'connexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['idUtilisateur'])) {
    echo json_encode(['success' => false, 'needLogin' => true]);
    exit;
}

$idUser = $_SESSION['idUtilisateur'];
$idConv = $_POST['idConversation'] ?? '';
$contenu = trim($_POST['contenu'] ?? '');

if (!$idConv || !$contenu) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}
if (mb_strlen($contenu) > 2000) {
    echo json_encode(['success' => false, 'message' => 'Message trop long (max 2000 caractères)']);
    exit;
}

$idConvSql = mysqli_real_escape_string($conn, $idConv);
$idUserSql = mysqli_real_escape_string($conn, $idUser);

/* Vérifier que l'user fait partie de la conversation */
$check = mysqli_query($conn, "
    SELECT idAcheteur, idVendeur 
    FROM Conversation 
    WHERE idConversation = '$idConvSql' 
    LIMIT 1
");
if (!$check || mysqli_num_rows($check) === 0) {
    echo json_encode(['success' => false, 'message' => 'Conversation introuvable']);
    exit;
}
$conv = mysqli_fetch_assoc($check);
if ($conv['idAcheteur'] !== $idUser && $conv['idVendeur'] !== $idUser) {
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

/* Insertion du message */
$idMsg = bin2hex(random_bytes(8)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(6));
$contenuSql = mysqli_real_escape_string($conn, $contenu);
$now = date('Y-m-d H:i:s');

$ok = mysqli_query($conn, "
    INSERT INTO Message (idMessage, idConversation, idUtilisateur, contenu, dateEnvoi, statutLecture)
    VALUES ('$idMsg', '$idConvSql', '$idUserSql', '$contenuSql', '$now', 0)
");

if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'Erreur DB : ' . mysqli_error($conn)]);
    exit;
}

/* Mettre à jour la conversation avec dernier message */
$apercu = mb_substr($contenu, 0, 100);
$apercuSql = mysqli_real_escape_string($conn, $apercu);
mysqli_query($conn, "
    UPDATE Conversation 
    SET dernierMessage = '$apercuSql', dateDernierMessage = '$now'
    WHERE idConversation = '$idConvSql'
");

echo json_encode([
    'success' => true,
    'message' => [
        'idMessage' => $idMsg,
        'contenu' => $contenu,
        'dateEnvoi' => $now,
        'heure' => date('H:i', strtotime($now)),
        'expediteur' => 'me'
    ]
]);
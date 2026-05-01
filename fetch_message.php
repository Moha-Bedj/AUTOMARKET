<?php
session_start();
require_once 'connexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['idUtilisateur'])) {
    echo json_encode(['success' => false, 'needLogin' => true]);
    exit;
}

$idUser = $_SESSION['idUtilisateur'];
$idConv = $_GET['idConversation'] ?? '';
$apresId = $_GET['apresId'] ?? null; /* Pour ne récupérer que les nouveaux */

if (!$idConv) {
    echo json_encode(['success' => false, 'message' => 'idConversation manquant']);
    exit;
}

$idConvSql = mysqli_real_escape_string($conn, $idConv);
$idUserSql = mysqli_real_escape_string($conn, $idUser);

/* Vérifier accès */
$check = mysqli_query($conn, "
    SELECT idAcheteur, idVendeur 
    FROM Conversation 
    WHERE idConversation = '$idConvSql' 
    LIMIT 1
");
if (!$check || mysqli_num_rows($check) === 0) {
    echo json_encode(['success' => false, 'message' => 'Introuvable']);
    exit;
}
$conv = mysqli_fetch_assoc($check);
if ($conv['idAcheteur'] !== $idUser && $conv['idVendeur'] !== $idUser) {
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

/* Récupérer messages */
$where = "idConversation = '$idConvSql'";
if ($apresId) {
    $apresIdSql = mysqli_real_escape_string($conn, $apresId);
    /* Récupérer dateEnvoi du message de référence pour filtrer les + récents */
    $rDate = mysqli_query($conn, "SELECT dateEnvoi FROM Message WHERE idMessage = '$apresIdSql' LIMIT 1");
    if ($rDate && mysqli_num_rows($rDate) > 0) {
        $dateRef = mysqli_fetch_assoc($rDate)['dateEnvoi'];
        $dateRefSql = mysqli_real_escape_string($conn, $dateRef);
        $where .= " AND dateEnvoi > '$dateRefSql'";
    }
}

$sql = "
    SELECT idMessage, idUtilisateur, contenu, dateEnvoi, statutLecture
    FROM Message
    WHERE $where
    ORDER BY dateEnvoi ASC
";
$res = mysqli_query($conn, $sql);

$messages = [];
if ($res) {
    while ($m = mysqli_fetch_assoc($res)) {
        $messages[] = [
            'idMessage' => $m['idMessage'],
            'contenu' => $m['contenu'],
            'dateEnvoi' => $m['dateEnvoi'],
            'heure' => date('H:i', strtotime($m['dateEnvoi'])),
            'expediteur' => $m['idUtilisateur'] === $idUser ? 'me' : 'them',
            'lu' => (int)$m['statutLecture']
        ];
    }
}

/* Marquer comme lus les messages reçus */
mysqli_query($conn, "
    UPDATE Message 
    SET statutLecture = 1 
    WHERE idConversation = '$idConvSql' 
    AND idUtilisateur != '$idUserSql' 
    AND statutLecture = 0
");

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'count' => count($messages)
]);
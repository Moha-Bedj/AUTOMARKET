<?php
/**
 * ════════════════════════════════════════════════════════════════════
 * AUTOMARKET — Endpoint AJAX pour marquer une notification comme lue
 * ════════════════════════════════════════════════════════════════════
 * Appelé par notif_banner.php, notif_dropdown.php et notifications.php
 * 
 * POST :
 *   - idNotification : UUID de la notif (obligatoire)
 *   - action : 'read' (défaut) | 'all' | 'delete'
 * 
 * Retourne JSON : { success: true/false, ... }
 * ════════════════════════════════════════════════════════════════════
 */

session_start();
require_once 'connexion.php';
require_once 'notification_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['idUtilisateur']) || empty(trim($_SESSION['idUtilisateur']))) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$idUser = trim($_SESSION['idUtilisateur']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode invalide']);
    exit;
}

$action = $_POST['action'] ?? 'read';
$idNotif = trim($_POST['idNotification'] ?? '');

if ($action === 'all') {
    /* Marquer toutes les notifs de l'user comme lues */
    if (marquerToutesLues($conn, $idUser)) {
        echo json_encode(['success' => true, 'message' => 'Toutes marquées comme lues']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur SQL']);
    }
    exit;
}

if (!$idNotif) {
    echo json_encode(['success' => false, 'message' => 'idNotification manquant']);
    exit;
}

if ($action === 'delete') {
    if (supprimerNotif($conn, $idNotif, $idUser)) {
        echo json_encode(['success' => true, 'message' => 'Supprimée']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur ou non autorisé']);
    }
    exit;
}

/* Action par défaut : marquer comme lue */
if (marquerNotifLue($conn, $idNotif, $idUser)) {
    $nbRestantes = compterNotifsNonLues($conn, $idUser);
    echo json_encode([
        'success' => true, 
        'unread_count' => $nbRestantes
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur ou non autorisé']);
}
exit;
<?php
/**
 * ════════════════════════════════════════════════════════════════════
 * AUTOMARKET — Notification Helper
 * ════════════════════════════════════════════════════════════════════
 * Fonctions réutilisables pour gérer les notifications.
 * À inclure dans tous les fichiers PHP qui ont besoin des notifs.
 * 
 * USAGE :
 *   require_once 'notification_helper.php';
 *   
 *   // Créer une notif
 *   creerNotification($conn, $idUser, "Votre annonce a été vue 10 fois", "info");
 *   
 *   // Avec lien cliquable
 *   creerNotification($conn, $idUser, "Nouveau message", "message", "messagerie.php?conv=ABC");
 *   
 *   // Compter non lues
 *   $nb = compterNotifsNonLues($conn, $idUser);
 *   
 *   // Récupérer les dernières
 *   $notifs = getNotifs($conn, $idUser, 10);
 *   
 *   // Marquer comme lue
 *   marquerNotifLue($conn, $idNotif, $idUser);
 * ════════════════════════════════════════════════════════════════════
 */

if (!function_exists('uuidNotif')) {
    function uuidNotif() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
            mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
            mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)
        );
    }
}

/**
 * ════════════════════════════════════════════════════════════════════
 * CRÉER UNE NOTIFICATION
 * ════════════════════════════════════════════════════════════════════
 * @param mysqli $conn      Connexion MySQL
 * @param string $idUser    UUID du destinataire
 * @param string $contenu   Texte de la notification
 * @param string $type      Type : info, success, warning, error,
 *                          pro_attente, pro_valide, pro_rejete,
 *                          message, favori, vente, alerte
 * @param string $lien      (optionnel) URL cliquable
 * @return string|false     idNotification créée ou false si erreur
 */
function creerNotification($conn, $idUser, $contenu, $type = 'info', $lien = null) {
    if (!$idUser || !$contenu) return false;
    
    $idNotif = uuidNotif();
    $idUserE = mysqli_real_escape_string($conn, $idUser);
    $typeE   = mysqli_real_escape_string($conn, $type);
    
    /* Stocker le lien en l'embarquant dans le contenu via séparateur ||| */
    $contenuFull = $lien ? ($contenu . '|||' . $lien) : $contenu;
    
    /* Limiter à 255 caractères (taille du varchar) */
    if (mb_strlen($contenuFull) > 255) {
        $contenuFull = mb_substr($contenuFull, 0, 252) . '...';
    }
    
    $contenuE = mysqli_real_escape_string($conn, $contenuFull);
    
    $sql = "INSERT INTO Notification 
            (idNotification, contenu, typeNoti, dateNoti, statutLecture, idUtilisateur) 
            VALUES 
            ('$idNotif', '$contenuE', '$typeE', NOW(), 0, '$idUserE')";
    
    if (mysqli_query($conn, $sql)) {
        return $idNotif;
    }
    return false;
}

/**
 * ════════════════════════════════════════════════════════════════════
 * COMPTER LES NOTIFICATIONS NON LUES
 * ════════════════════════════════════════════════════════════════════
 */
function compterNotifsNonLues($conn, $idUser) {
    if (!$idUser) return 0;
    $idUserE = mysqli_real_escape_string($conn, $idUser);
    $r = mysqli_query($conn, "SELECT COUNT(*) AS nb FROM Notification 
                              WHERE idUtilisateur='$idUserE' AND statutLecture=0");
    if (!$r) return 0;
    return (int)(mysqli_fetch_assoc($r)['nb'] ?? 0);
}

/**
 * ════════════════════════════════════════════════════════════════════
 * RÉCUPÉRER LES NOTIFICATIONS
 * ════════════════════════════════════════════════════════════════════
 * @param mysqli $conn
 * @param string $idUser
 * @param int $limit       Nombre max de résultats (défaut 20)
 * @param bool $onlyUnread Si true, seulement les non lues
 * @return array
 */
function getNotifs($conn, $idUser, $limit = 20, $onlyUnread = false) {
    if (!$idUser) return [];
    $idUserE = mysqli_real_escape_string($conn, $idUser);
    $limit = (int)$limit;
    
    $whereLue = $onlyUnread ? " AND statutLecture=0" : "";
    
    $sql = "SELECT idNotification, contenu, typeNoti, dateNoti, statutLecture 
            FROM Notification 
            WHERE idUtilisateur='$idUserE' $whereLue 
            ORDER BY dateNoti DESC, idNotification DESC 
            LIMIT $limit";
    
    $r = mysqli_query($conn, $sql);
    if (!$r) return [];
    
    $notifs = [];
    while ($row = mysqli_fetch_assoc($r)) {
        /* Parse le contenu pour extraire le lien si présent */
        $contenu = $row['contenu'];
        $lien = null;
        if (strpos($contenu, '|||') !== false) {
            list($contenu, $lien) = explode('|||', $contenu, 2);
        }
        $row['texte'] = $contenu;
        $row['lien'] = $lien;
        $row['important'] = estNotifImportante($row['typeNoti']);
        $notifs[] = $row;
    }
    return $notifs;
}

/**
 * ════════════════════════════════════════════════════════════════════
 * RÉCUPÉRER LES NOTIFICATIONS IMPORTANTES (pour bandeau)
 * ════════════════════════════════════════════════════════════════════
 * Récupère les notifs marquées comme "importantes" (à afficher en bandeau)
 * Convention : typeNoti commençant par 'pro_' ou contenant '_important'
 */
function getNotifsImportantes($conn, $idUser) {
    if (!$idUser) return [];
    $idUserE = mysqli_real_escape_string($conn, $idUser);
    
    /* On récupère les types importants : pro_attente, pro_valide, pro_rejete, etc. */
    $sql = "SELECT idNotification, contenu, typeNoti, dateNoti, statutLecture 
            FROM Notification 
            WHERE idUtilisateur='$idUserE' 
            AND statutLecture=0 
            AND (typeNoti LIKE 'pro_%' OR typeNoti LIKE '%_important')
            ORDER BY dateNoti DESC, idNotification DESC";
    
    $r = mysqli_query($conn, $sql);
    if (!$r) return [];
    
    $notifs = [];
    while ($row = mysqli_fetch_assoc($r)) {
        $contenu = $row['contenu'];
        $lien = null;
        if (strpos($contenu, '|||') !== false) {
            list($contenu, $lien) = explode('|||', $contenu, 2);
        }
        $row['texte'] = $contenu;
        $row['lien'] = $lien;
        $notifs[] = $row;
    }
    return $notifs;
}

/**
 * ════════════════════════════════════════════════════════════════════
 * EST-CE UNE NOTIF IMPORTANTE (à afficher en bandeau) ?
 * ════════════════════════════════════════════════════════════════════
 */
function estNotifImportante($type) {
    if (!$type) return false;
    return (strpos($type, 'pro_') === 0) || (strpos($type, '_important') !== false);
}

/**
 * ════════════════════════════════════════════════════════════════════
 * MARQUER UNE NOTIFICATION COMME LUE
 * ════════════════════════════════════════════════════════════════════
 * Sécurité : vérifie que la notif appartient au user
 */
function marquerNotifLue($conn, $idNotif, $idUser) {
    if (!$idNotif || !$idUser) return false;
    $idNotifE = mysqli_real_escape_string($conn, $idNotif);
    $idUserE = mysqli_real_escape_string($conn, $idUser);
    
    return mysqli_query($conn, "UPDATE Notification 
                                SET statutLecture=1 
                                WHERE idNotification='$idNotifE' 
                                AND idUtilisateur='$idUserE'");
}

/**
 * ════════════════════════════════════════════════════════════════════
 * MARQUER TOUTES LES NOTIFICATIONS COMME LUES
 * ════════════════════════════════════════════════════════════════════
 */
function marquerToutesLues($conn, $idUser) {
    if (!$idUser) return false;
    $idUserE = mysqli_real_escape_string($conn, $idUser);
    return mysqli_query($conn, "UPDATE Notification 
                                SET statutLecture=1 
                                WHERE idUtilisateur='$idUserE' AND statutLecture=0");
}

/**
 * ════════════════════════════════════════════════════════════════════
 * SUPPRIMER UNE NOTIFICATION
 * ════════════════════════════════════════════════════════════════════
 */
function supprimerNotif($conn, $idNotif, $idUser) {
    if (!$idNotif || !$idUser) return false;
    $idNotifE = mysqli_real_escape_string($conn, $idNotif);
    $idUserE = mysqli_real_escape_string($conn, $idUser);
    
    return mysqli_query($conn, "DELETE FROM Notification 
                                WHERE idNotification='$idNotifE' 
                                AND idUtilisateur='$idUserE'");
}

/**
 * ════════════════════════════════════════════════════════════════════
 * NOTIFIER TOUS LES ADMINS
 * ════════════════════════════════════════════════════════════════════
 * Crée une notification pour tous les utilisateurs ayant le rôle admin.
 * Utile pour : "Nouvelle demande Pro", "Nouveau signalement", etc.
 */
function notifierAdmins($conn, $contenu, $type = 'info', $lien = null) {
    /* Récupérer tous les admins via la table Admin OU le role */
    $r = mysqli_query($conn, "
        SELECT u.idUtilisateur 
        FROM Utilisateur u 
        WHERE u.role='admin' 
           OR u.idUtilisateur IN (SELECT idUtilisateur FROM Admin)
    ");
    if (!$r) return 0;
    
    $count = 0;
    while ($admin = mysqli_fetch_assoc($r)) {
        if (creerNotification($conn, $admin['idUtilisateur'], $contenu, $type, $lien)) {
            $count++;
        }
    }
    return $count;
}

/**
 * ════════════════════════════════════════════════════════════════════
 * HELPER : ICÔNE SVG SELON LE TYPE
 * ════════════════════════════════════════════════════════════════════
 */
function notifIconeSvg($type) {
    $type = strtolower($type ?? '');
    
    /* Pro */
    if ($type === 'pro_attente' || $type === 'pro_pending') {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
    }
    if ($type === 'pro_valide' || $type === 'pro_active') {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
    }
    if ($type === 'pro_rejete' || $type === 'pro_rejected') {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
    }
    /* Message */
    if ($type === 'message') {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>';
    }
    /* Favori */
    if ($type === 'favori') {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M11.29 20.66c.2.2.45.29.71.29s.51-.1.71-.29l7.5-7.5c2.35-2.35 2.35-6.05 0-8.41-2.3-2.28-5.85-2.35-8.21-.2-2.36-2.15-5.91-2.09-8.21.2-2.35 2.36-2.35 6.06 0 8.41z"/></svg>';
    }
    /* Vente */
    if ($type === 'vente') {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>';
    }
    /* Alerte */
    if ($type === 'alerte') {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 13l2-2 4 4 8-8 4 4"/><path d="M14 8l5 5"/></svg>';
    }
    /* Success */
    if ($type === 'success') {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
    }
    /* Warning */
    if ($type === 'warning') {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
    }
    /* Error */
    if ($type === 'error') {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    }
    /* Info / défaut */
    return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>';
}

/**
 * ════════════════════════════════════════════════════════════════════
 * HELPER : COULEUR SELON LE TYPE
 * ════════════════════════════════════════════════════════════════════
 * Retourne [bg, color, border] pour styliser
 */
function notifCouleurs($type) {
    $type = strtolower($type ?? '');
    
    if ($type === 'pro_attente' || $type === 'warning' || $type === 'pro_pending') {
        return ['#FAEEDA', '#854F0B', '#FAC775'];  /* orange/amber */
    }
    if ($type === 'pro_valide' || $type === 'success' || $type === 'pro_active' || $type === 'vente') {
        return ['#EAF3DE', '#27500A', '#C0DD97'];  /* vert */
    }
    if ($type === 'pro_rejete' || $type === 'error' || $type === 'pro_rejected') {
        return ['#FCEBEB', '#791F1F', '#F09595'];  /* rouge */
    }
    if ($type === 'favori') {
        return ['#FCEBEB', '#791F1F', '#F09595'];  /* rouge clair */
    }
    if ($type === 'message' || $type === 'alerte') {
        return ['#E6F1FB', '#0C447C', '#B5D4F4'];  /* bleu */
    }
    /* Défaut info */
    return ['#E6F1FB', '#0C447C', '#B5D4F4'];
}

/**
 * ════════════════════════════════════════════════════════════════════
 * HELPER : TEMPS RELATIF
 * ════════════════════════════════════════════════════════════════════
 */
if (!function_exists('notifTimeAgo')) {
    function notifTimeAgo($date) {
        if (!$date) return '';
        $diff = time() - strtotime($date);
        if ($diff < 60) return 'à l\'instant';
        if ($diff < 3600) return 'il y a '.floor($diff/60).' min';
        if ($diff < 86400) return 'il y a '.floor($diff/3600).'h';
        if ($diff < 172800) return 'hier';
        if ($diff < 604800) return 'il y a '.floor($diff/86400).' jours';
        return date('d/m/Y', strtotime($date));
    }
}
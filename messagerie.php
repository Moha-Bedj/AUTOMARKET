<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['idUtilisateur'])) {
    header("Location: inscription.php?redirect=messagerie.php");
    exit;
}

$idUser = $_SESSION['idUtilisateur'];
$idUserSql = mysqli_real_escape_string($conn, $idUser);

/* ════════════════════════════════════════════════════════ */
/* ═══   AUTO-CRÉER ACHETEUR / VENDEUR                    ═══ */
/* ════════════════════════════════════════════════════════ */
mysqli_query($conn, "INSERT IGNORE INTO Acheteur (idUtilisateur) VALUES ('$idUserSql')");
mysqli_query($conn, "INSERT IGNORE INTO Vendeur (idUtilisateur, typeVendeur, nbrAnnonceAct) VALUES ('$idUserSql', 'particulier', 0)");

/* ════════════════════════════════════════════════════════ */
/* ═══   CRÉER CONVERSATION SI ?vendeur=X&annonce=Y       ═══ */
/* ════════════════════════════════════════════════════════ */
$vendeurDest = $_GET['vendeur'] ?? '';
$annonceContexte = $_GET['annonce'] ?? '';
$idConvActive = $_GET['conv'] ?? '';

if ($vendeurDest && $annonceContexte && $vendeurDest !== $idUser) {
    $vendDestSql = mysqli_real_escape_string($conn, $vendeurDest);
    $annContSql = mysqli_real_escape_string($conn, $annonceContexte);

    /* Auto-créer Acheteur/Vendeur destinataire si absent (sécurité) */
    mysqli_query($conn, "INSERT IGNORE INTO Acheteur (idUtilisateur) VALUES ('$vendDestSql')");
    mysqli_query($conn, "INSERT IGNORE INTO Vendeur (idUtilisateur, typeVendeur, nbrAnnonceAct) VALUES ('$vendDestSql', 'particulier', 0)");

    /* Vérifier si conversation existe déjà */
    $rExist = mysqli_query($conn, "
        SELECT idConversation 
        FROM Conversation 
        WHERE idAcheteur='$idUserSql' 
        AND idVendeur='$vendDestSql' 
        AND idAnnonce='$annContSql'
        LIMIT 1
    ");
    
    if ($rExist && mysqli_num_rows($rExist) > 0) {
        $idConvActive = mysqli_fetch_assoc($rExist)['idConversation'];
    } else {
        /* Créer nouvelle conversation */
        $idConvNew = bin2hex(random_bytes(8)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(6));
        $today = date('Y-m-d');
        $createOk = mysqli_query($conn, "
            INSERT INTO Conversation (idConversation, dateConversation, statutConversation, idAcheteur, idVendeur, idAnnonce)
            VALUES ('$idConvNew', '$today', 'active', '$idUserSql', '$vendDestSql', '$annContSql')
        ");
        if ($createOk) {
            $idConvActive = $idConvNew;
        }
    }
    
    /* Rediriger pour nettoyer l'URL */
    if ($idConvActive) {
        header("Location: messagerie.php?conv=" . urlencode($idConvActive));
        exit;
    }
}

/* ════════════════════════════════════════════════════════ */
/* ═══   RÉCUPÉRER LISTE DES CONVERSATIONS               ═══ */
/* ════════════════════════════════════════════════════════ */
$sqlConvs = "
    SELECT 
        c.idConversation, c.idAcheteur, c.idVendeur, c.idAnnonce,
        c.dernierMessage, c.dateDernierMessage, c.dateConversation,
        a.titre AS annonce_titre, a.prix AS annonce_prix,
        (SELECT urlPhoto FROM Photos WHERE idAnnonce = c.idAnnonce ORDER BY ordrePhoto ASC LIMIT 1) AS annonce_photo,
        CASE 
            WHEN c.idAcheteur = '$idUserSql' THEN c.idVendeur
            ELSE c.idAcheteur
        END AS idAutre,
        u.nom AS autre_nom, u.prenom AS autre_prenom, u.badgeVerifie AS autre_verif,
        (SELECT COUNT(*) FROM Message m 
         WHERE m.idConversation = c.idConversation 
         AND m.idUtilisateur != '$idUserSql' 
         AND m.statutLecture = 0) AS nbNonLus
    FROM Conversation c
    LEFT JOIN Annonce a ON c.idAnnonce = a.idAnnonce
    LEFT JOIN Utilisateur u ON u.idUtilisateur = (
        CASE WHEN c.idAcheteur = '$idUserSql' THEN c.idVendeur ELSE c.idAcheteur END
    )
    WHERE c.idAcheteur = '$idUserSql' OR c.idVendeur = '$idUserSql'
    ORDER BY COALESCE(c.dateDernierMessage, c.dateConversation) DESC
";
$resConvs = mysqli_query($conn, $sqlConvs);
$conversations = [];
$totalNonLus = 0;
if ($resConvs) {
    while ($c = mysqli_fetch_assoc($resConvs)) {
        $conversations[] = $c;
        $totalNonLus += (int)$c['nbNonLus'];
    }
}

/* Sélectionner conversation active (1ère par défaut) */
if (!$idConvActive && !empty($conversations)) {
    $idConvActive = $conversations[0]['idConversation'];
}

/* ════════════════════════════════════════════════════════ */
/* ═══   CHARGER CONVERSATION ACTIVE                     ═══ */
/* ════════════════════════════════════════════════════════ */
$convActive = null;
$messages = [];
if ($idConvActive) {
    $idConvSql = mysqli_real_escape_string($conn, $idConvActive);
    
    /* Détails de la conversation active */
    foreach ($conversations as $c) {
        if ($c['idConversation'] === $idConvActive) {
            $convActive = $c;
            break;
        }
    }
    
    /* Si pas dans la liste, c'est qu'on n'a pas accès → reset */
    if (!$convActive) {
        $idConvActive = '';
    } else {
        /* Charger messages */
        $rMsg = mysqli_query($conn, "
            SELECT idMessage, idUtilisateur, contenu, dateEnvoi, statutLecture
            FROM Message
            WHERE idConversation = '$idConvSql'
            ORDER BY dateEnvoi ASC
        ");
        if ($rMsg) {
            while ($m = mysqli_fetch_assoc($rMsg)) {
                $messages[] = $m;
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
    }
}

/* Helpers */
function timeAgo($dateStr) {
    if (!$dateStr) return '';
    $d = new DateTime($dateStr);
    $now = new DateTime();
    $diffSec = $now->getTimestamp() - $d->getTimestamp();
    
    if ($diffSec < 60) return "À l'instant";
    if ($diffSec < 3600) return floor($diffSec/60) . " min";
    if ($diffSec < 86400 && $now->format('Y-m-d') === $d->format('Y-m-d')) return $d->format('H:i');
    
    $diffJ = (new DateTime($now->format('Y-m-d')))->diff(new DateTime($d->format('Y-m-d')))->days;
    if ($diffJ === 1) return "Hier";
    if ($diffJ < 7) return $diffJ . " j";
    return $d->format('d/m');
}

function dateLabel($dateStr) {
    $d = new DateTime($dateStr);
    $now = new DateTime();
    if ($d->format('Y-m-d') === $now->format('Y-m-d')) return "Aujourd'hui";
    if ($d->format('Y-m-d') === $now->modify('-1 day')->format('Y-m-d')) return "Hier";
    $mois = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
    return $d->format('d') . ' ' . $mois[(int)$d->format('m')-1] . ' ' . $d->format('Y');
}

function colorAvatar($id) {
    $colors = ['#185FA5', '#639922', '#BA7517', '#7F77DD', '#D85A30', '#1D9E75', '#993556'];
    $hash = 0;
    for ($i = 0; $i < strlen($id); $i++) $hash += ord($id[$i]);
    return $colors[$hash % count($colors)];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messagerie — AUTOMARKET</title>
  <link rel="icon" href="images/logo.png">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --blue: #185FA5; --blue-dk: #0C447C; --blue-bg: #E6F1FB; --blue-bd: #B5D4F4;
      --bg0: #ffffff; --bg1: #f5f4f0; --bg2: #eceae4;
      --t1: #1a1a18; --t2: #5f5e5a; --t3: #888780;
      --bd: rgba(0,0,0,0.11); --bd2: rgba(0,0,0,0.22);
      --green: #639922; --green-dk: #27500A;
      --red: #E24B4A;
      --r6: 6px; --r8: 8px; --r10: 10px;
    }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; background: var(--bg1); color: var(--t1); height: 100vh; overflow: hidden; display: flex; flex-direction: column; }

    /* NAVBAR */
    .nav { background: var(--bg0); border-bottom: 0.5px solid var(--bd); height: 52px; display: flex; align-items: center; padding: 0 20px; gap: 16px; flex-shrink: 0; }
    .logo img { height: 32px; }
    .nav-back { font-size: 13px; color: var(--t2); display: flex; align-items: center; gap: 6px; text-decoration: none; padding: 6px 12px; border-radius: var(--r6); }
    .nav-back:hover { background: var(--bg1); color: var(--t1); }
    .nav-user { margin-left: auto; display: flex; align-items: center; gap: 8px; font-size: 13px; }
    .avatar-mini { width: 28px; height: 28px; border-radius: 50%; background: var(--blue); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 500; }

    /* MAIN GRID */
    .messagerie {
      display: grid;
      grid-template-columns: 320px 1fr;
      flex: 1;
      min-height: 0;
      max-width: 1400px;
      margin: 0 auto;
      width: 100%;
      background: var(--bg0);
      border-left: 0.5px solid var(--bd);
      border-right: 0.5px solid var(--bd);
    }

    /* ═══ SIDEBAR ═══ */
    .sidebar { display: flex; flex-direction: column; border-right: 0.5px solid var(--bd); background: #fafafa; }
    .sidebar-h {
      padding: 14px 16px; border-bottom: 0.5px solid var(--bd);
      display: flex; align-items: center; justify-content: space-between;
      background: var(--bg0); flex-shrink: 0;
    }
    .sidebar-title { font-size: 16px; font-weight: 600; }
    .sidebar-count {
      background: var(--blue-bg); color: var(--blue-dk);
      font-size: 11px; padding: 3px 8px;
      border-radius: 10px; font-weight: 500;
    }

    .sidebar-search {
      padding: 10px 12px; border-bottom: 0.5px solid var(--bd);
      background: var(--bg0); flex-shrink: 0;
    }
    .search-wrap { position: relative; }
    .search-wrap svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--t3); }
    .search-input {
      width: 100%; height: 34px;
      border: 0.5px solid var(--bd2); border-radius: 17px;
      padding: 0 12px 0 32px; font-size: 12px;
      background: var(--bg1); outline: none;
      font-family: inherit;
    }
    .search-input:focus { border-color: var(--blue); background: var(--bg0); }

    .conv-list { flex: 1; overflow-y: auto; }
    .conv-list::-webkit-scrollbar { width: 6px; }
    .conv-list::-webkit-scrollbar-thumb { background: var(--bd2); border-radius: 3px; }

    .conv-item {
      display: flex; gap: 10px; padding: 12px 14px;
      border-bottom: 0.5px solid var(--bd);
      cursor: pointer; text-decoration: none;
      color: var(--t1); transition: background .15s;
      position: relative;
    }
    .conv-item:hover { background: var(--bg0); }
    .conv-item.active {
      background: var(--blue-bg);
      border-left: 3px solid var(--blue);
      padding-left: 11px;
    }
    .conv-item.unread::before {
      content: '';
      position: absolute; left: 4px; top: 50%;
      transform: translateY(-50%);
      width: 7px; height: 7px;
      background: var(--blue); border-radius: 50%;
    }
    .conv-item.unread.active::before { display: none; }

    .conv-avatar {
      width: 42px; height: 42px;
      border-radius: 50%; color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-weight: 600; font-size: 15px;
      flex-shrink: 0;
    }

    .conv-content { flex: 1; min-width: 0; }
    .conv-top {
      display: flex; justify-content: space-between;
      align-items: baseline; margin-bottom: 3px;
    }
    .conv-name {
      font-size: 13px; font-weight: 600;
      white-space: nowrap; overflow: hidden;
      text-overflow: ellipsis;
      display: flex; align-items: center; gap: 5px;
    }
    .verif-mini {
      background: var(--blue-bg); color: var(--blue-dk);
      font-size: 9px; padding: 1px 5px;
      border-radius: 8px; font-weight: 500;
    }
    .conv-time { font-size: 10px; color: var(--t3); flex-shrink: 0; margin-left: 6px; }
    .conv-annonce {
      font-size: 11px; color: var(--blue);
      margin-bottom: 3px;
      white-space: nowrap; overflow: hidden;
      text-overflow: ellipsis;
      display: flex; align-items: center; gap: 4px;
    }
    .conv-preview-row { display: flex; justify-content: space-between; align-items: center; gap: 6px; }
    .conv-preview {
      font-size: 12px; color: var(--t2);
      white-space: nowrap; overflow: hidden;
      text-overflow: ellipsis; flex: 1;
    }
    .conv-preview.unread { color: var(--t1); font-weight: 500; }
    .conv-badge {
      background: var(--blue); color: #fff;
      font-size: 10px; padding: 1px 7px;
      border-radius: 10px; min-width: 18px;
      text-align: center; flex-shrink: 0;
      font-weight: 500;
    }

    .empty-list {
      padding: 40px 20px; text-align: center;
      color: var(--t3); font-size: 13px;
    }

    /* ═══ ZONE CHAT ═══ */
    .chat { display: flex; flex-direction: column; background: var(--bg0); min-width: 0; }

    .chat-empty {
      flex: 1; display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      color: var(--t3); padding: 40px;
    }
    .chat-empty svg { color: var(--bd2); margin-bottom: 16px; }
    .chat-empty-title { font-size: 16px; font-weight: 500; margin-bottom: 6px; color: var(--t2); }
    .chat-empty-sub { font-size: 13px; }

    /* HEADER CHAT */
    .chat-h {
      display: flex; align-items: center; gap: 12px;
      padding: 14px 18px; border-bottom: 0.5px solid var(--bd);
      flex-shrink: 0;
    }
    .chat-avatar {
      width: 40px; height: 40px;
      border-radius: 50%; color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-weight: 600; font-size: 15px;
    }
    .chat-info { flex: 1; min-width: 0; }
    .chat-name {
      font-size: 14px; font-weight: 600;
      display: flex; align-items: center; gap: 6px;
    }
    .chat-status { font-size: 11px; color: var(--t3); margin-top: 2px; }
    .chat-actions { display: flex; gap: 6px; }
    .icon-btn {
      width: 36px; height: 36px;
      border-radius: 50%;
      border: 0.5px solid var(--bd);
      background: var(--bg0);
      display: flex; align-items: center; justify-content: center;
      color: var(--t2); cursor: pointer;
      transition: all .15s;
    }
    .icon-btn:hover { background: var(--bg1); color: var(--blue); border-color: var(--blue); }

    /* BANDEAU ANNONCE */
    .annonce-bar {
      display: flex; align-items: center; gap: 12px;
      padding: 12px 18px;
      background: var(--bg1);
      border-bottom: 0.5px solid var(--bd);
      cursor: pointer; transition: background .15s;
      text-decoration: none; color: var(--t1);
      flex-shrink: 0;
    }
    .annonce-bar:hover { background: var(--bg2); }
    .annonce-img {
      width: 56px; height: 42px;
      background: linear-gradient(135deg, #c4c8d0, #a8b1bc);
      border-radius: var(--r6);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; color: rgba(255,255,255,0.6);
      overflow: hidden;
    }
    .annonce-img img { width: 100%; height: 100%; object-fit: cover; }
    .annonce-info { flex: 1; min-width: 0; }
    .annonce-title {
      font-size: 13px; font-weight: 600;
      white-space: nowrap; overflow: hidden;
      text-overflow: ellipsis;
    }
    .annonce-price { font-size: 14px; color: var(--blue); font-weight: 700; }
    .annonce-link {
      font-size: 11px; color: var(--t2);
      display: flex; align-items: center; gap: 3px;
      flex-shrink: 0;
    }

    /* MESSAGES */
    .messages {
      flex: 1; overflow-y: auto;
      padding: 18px 24px;
      display: flex; flex-direction: column;
      gap: 8px; background: var(--bg1);
    }
    .messages::-webkit-scrollbar { width: 6px; }
    .messages::-webkit-scrollbar-thumb { background: var(--bd2); border-radius: 3px; }

    .date-divider {
      display: flex; align-items: center; justify-content: center;
      margin: 14px 0 8px;
    }
    .date-divider span {
      font-size: 11px; color: var(--t3);
      background: var(--bg0); padding: 4px 12px;
      border-radius: 12px;
      border: 0.5px solid var(--bd);
    }

    .bubble {
      max-width: 70%;
      padding: 9px 13px;
      border-radius: 16px;
      font-size: 13px;
      line-height: 1.45;
      word-wrap: break-word;
      animation: bubbleIn 0.2s ease;
    }
    @keyframes bubbleIn {
      from { opacity: 0; transform: translateY(6px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .bubble-them {
      background: var(--bg0);
      color: var(--t1);
      border: 0.5px solid var(--bd);
      border-bottom-left-radius: 5px;
      align-self: flex-start;
    }
    .bubble-me {
      background: var(--blue);
      color: #fff;
      border-bottom-right-radius: 5px;
      align-self: flex-end;
    }
    .bubble-time {
      font-size: 10px;
      opacity: 0.7;
      margin-top: 4px;
      display: flex; align-items: center; gap: 3px;
      justify-content: flex-end;
    }
    .bubble-them .bubble-time { color: var(--t3); opacity: 1; }
    .bubble-me .bubble-time { color: rgba(255,255,255,0.85); }

    /* INPUT */
    .input-area {
      padding: 12px 18px;
      border-top: 0.5px solid var(--bd);
      background: var(--bg0);
      flex-shrink: 0;
    }
    .input-row {
      display: flex; align-items: flex-end; gap: 8px;
    }
    .input-actions { display: flex; gap: 4px; }
    .input-btn {
      width: 36px; height: 36px;
      border-radius: 50%;
      background: transparent;
      border: none;
      color: var(--t2);
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: all .15s;
    }
    .input-btn:hover { background: var(--bg1); color: var(--blue); }

    .msg-input {
      flex: 1;
      min-height: 38px;
      max-height: 120px;
      border: 0.5px solid var(--bd2);
      border-radius: 19px;
      padding: 9px 16px;
      font-size: 13px;
      resize: none;
      outline: none;
      font-family: inherit;
      line-height: 1.4;
      transition: border-color .15s, box-shadow .15s;
    }
    .msg-input:focus {
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(24,95,165,0.1);
    }

    .send-btn {
      width: 38px; height: 38px;
      border-radius: 50%;
      background: var(--blue);
      border: none;
      color: #fff;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      transition: background .15s;
    }
    .send-btn:hover { background: var(--blue-dk); }
    .send-btn:disabled { background: var(--bd2); cursor: not-allowed; }

    /* RESPONSIVE */
    @media (max-width: 768px) {
      .messagerie { grid-template-columns: 1fr; }
      .sidebar { display: var(--sidebar-display, flex); }
      .chat { display: var(--chat-display, none); }
      body.chat-open .sidebar { display: none; }
      body.chat-open .chat { display: flex; }
      .nav-back-mobile { display: flex !important; }
    }
    .nav-back-mobile { display: none; }
    .messages-empty {
      flex: 1; display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      color: var(--t3);
    }
  </style>
</head>
<body class="<?= $idConvActive ? 'chat-open' : '' ?>">

  <!-- NAVBAR -->
  <nav class="nav">
    <a class="logo" href="index.php">
      <img src="images/logo.png" alt="AUTOMARKET">
    </a>
    <a href="index.php" class="nav-back">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Retour
    </a>
    <div class="nav-user">
      <span><?= htmlspecialchars($_SESSION['prenom'] ?? 'U') ?></span>
      <div class="avatar-mini"><?= strtoupper(substr($_SESSION['prenom'] ?? 'U', 0, 1)) ?></div>
    </div>
  </nav>

  <!-- MAIN -->
  <div class="messagerie">

    <!-- ═══ SIDEBAR CONVERSATIONS ═══ -->
    <aside class="sidebar">

      <div class="sidebar-h">
        <span class="sidebar-title">Messages</span>
        <?php if ($totalNonLus > 0): ?>
          <span class="sidebar-count"><?= $totalNonLus ?> nouveaux</span>
        <?php endif; ?>
      </div>

      <div class="sidebar-search">
        <div class="search-wrap">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input class="search-input" id="search-conv" placeholder="Rechercher une conversation...">
        </div>
      </div>

      <div class="conv-list" id="conv-list">
        <?php if (empty($conversations)): ?>
          <div class="empty-list">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.3;margin-bottom:10px">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <div style="font-weight:500;color:var(--t2);margin-bottom:4px">Aucune conversation</div>
            <div style="font-size:11px">Contactez un vendeur depuis sa fiche d'annonce</div>
          </div>
        <?php else: ?>
          <?php foreach ($conversations as $c):
            $isActive = $c['idConversation'] === $idConvActive;
            $isUnread = (int)$c['nbNonLus'] > 0;
            $autreNom = trim(($c['autre_prenom'] ?? '') . ' ' . ($c['autre_nom'] ?? '')) ?: 'Utilisateur';
            $initiale = strtoupper(substr($c['autre_prenom'] ?? 'U', 0, 1));
            $color = colorAvatar($c['idAutre'] ?? '');
            $preview = $c['dernierMessage'] ?: 'Démarrer la conversation';
            $time = $c['dateDernierMessage'] ? timeAgo($c['dateDernierMessage']) : timeAgo($c['dateConversation']);
          ?>
            <a class="conv-item <?= $isActive ? 'active' : '' ?> <?= $isUnread ? 'unread' : '' ?>" 
               href="messagerie.php?conv=<?= urlencode($c['idConversation']) ?>"
               data-search="<?= htmlspecialchars(strtolower($autreNom . ' ' . ($c['annonce_titre'] ?? ''))) ?>">
              <div class="conv-avatar" style="background: <?= $color ?>">
                <?= htmlspecialchars($initiale) ?>
              </div>
              <div class="conv-content">
                <div class="conv-top">
                  <span class="conv-name">
                    <?= htmlspecialchars($autreNom) ?>
                    <?php if ($c['autre_verif']): ?>
                      <span class="verif-mini">✓</span>
                    <?php endif; ?>
                  </span>
                  <span class="conv-time"><?= htmlspecialchars($time) ?></span>
                </div>
                <?php if ($c['annonce_titre']): ?>
                  <div class="conv-annonce">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="6" width="22" height="13" rx="3"/><circle cx="7" cy="16" r="1.5"/><circle cx="17" cy="16" r="1.5"/></svg>
                    <?= htmlspecialchars($c['annonce_titre']) ?>
                  </div>
                <?php endif; ?>
                <div class="conv-preview-row">
                  <span class="conv-preview <?= $isUnread ? 'unread' : '' ?>"><?= htmlspecialchars($preview) ?></span>
                  <?php if ($isUnread): ?>
                    <span class="conv-badge"><?= $c['nbNonLus'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </aside>

    <!-- ═══ ZONE CHAT ═══ -->
    <main class="chat">
      <?php if (!$convActive): ?>

        <div class="chat-empty">
          <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
          <div class="chat-empty-title">Sélectionnez une conversation</div>
          <div class="chat-empty-sub">Choisissez une conversation à gauche pour commencer à discuter</div>
        </div>

      <?php else: 
        $autreNom = trim(($convActive['autre_prenom'] ?? '') . ' ' . ($convActive['autre_nom'] ?? '')) ?: 'Utilisateur';
        $initiale = strtoupper(substr($convActive['autre_prenom'] ?? 'U', 0, 1));
        $color = colorAvatar($convActive['idAutre'] ?? '');
      ?>

        <!-- HEADER CHAT -->
        <div class="chat-h">
          <a href="javascript:history.back()" class="icon-btn nav-back-mobile" style="margin-right:4px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
          </a>
          <div class="chat-avatar" style="background: <?= $color ?>">
            <?= htmlspecialchars($initiale) ?>
          </div>
          <div class="chat-info">
            <div class="chat-name">
              <?= htmlspecialchars($autreNom) ?>
              <?php if ($convActive['autre_verif']): ?>
                <span class="verif-mini">✓ Vérifié</span>
              <?php endif; ?>
            </div>
            <div class="chat-status">Conversation depuis <?= htmlspecialchars(timeAgo($convActive['dateConversation'])) ?></div>
          </div>
          <div class="chat-actions">
            <button class="icon-btn" title="Plus d'options" onclick="toggleMenu()">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
            </button>
          </div>
        </div>

        <!-- BANDEAU ANNONCE -->
        <?php if ($convActive['annonce_titre']): ?>
          <a href="fiche_annonce.php?id=<?= urlencode($convActive['idAnnonce']) ?>" class="annonce-bar">
            <div class="annonce-img">
              <?php if ($convActive['annonce_photo']): ?>
                <img src="<?= htmlspecialchars($convActive['annonce_photo']) ?>" alt="">
              <?php else: ?>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.8"><rect x="1" y="6" width="22" height="13" rx="3"/><circle cx="7" cy="16" r="1.5"/><circle cx="17" cy="16" r="1.5"/></svg>
              <?php endif; ?>
            </div>
            <div class="annonce-info">
              <div class="annonce-title"><?= htmlspecialchars($convActive['annonce_titre']) ?></div>
              <div class="annonce-price"><?= number_format($convActive['annonce_prix'] ?? 0, 0, ',', ' ') ?> DA</div>
            </div>
            <div class="annonce-link">
              Voir l'annonce
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </div>
          </a>
        <?php endif; ?>

        <!-- MESSAGES -->
        <div class="messages" id="messages">
          <?php
          if (empty($messages)) {
            echo '<div class="messages-empty">';
            echo '<svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="opacity:0.3;margin-bottom:12px"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>';
            echo '<div style="font-size:13px;text-align:center">Démarrez la conversation !<br>Posez votre question au vendeur.</div>';
            echo '</div>';
          } else {
            $lastDate = '';
            foreach ($messages as $m) {
              $msgDate = (new DateTime($m['dateEnvoi']))->format('Y-m-d');
              if ($msgDate !== $lastDate) {
                echo '<div class="date-divider"><span>' . htmlspecialchars(dateLabel($m['dateEnvoi'])) . '</span></div>';
                $lastDate = $msgDate;
              }
              $isMe = $m['idUtilisateur'] === $idUser;
              $heure = (new DateTime($m['dateEnvoi']))->format('H:i');
              echo '<div class="bubble bubble-' . ($isMe ? 'me' : 'them') . '" data-id="' . htmlspecialchars($m['idMessage']) . '">';
              echo nl2br(htmlspecialchars($m['contenu']));
              echo '<div class="bubble-time">' . $heure;
              if ($isMe) {
                $check = $m['statutLecture'] 
                  ? '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><svg width="13" height="13" style="margin-left:-9px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>'
                  : '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>';
                echo $check;
              }
              echo '</div>';
              echo '</div>';
            }
          }
          ?>
        </div>

        <!-- INPUT -->
        <div class="input-area">
          <form class="input-row" id="form-msg" onsubmit="sendMessage(event)">
            <textarea class="msg-input" id="msg-input" placeholder="Écrivez un message..." rows="1" maxlength="2000" required></textarea>
            <button type="submit" class="send-btn" id="send-btn" title="Envoyer">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
          </form>
        </div>

      <?php endif; ?>
    </main>
  </div>

  <script>
    /* ════════════════════════════════════════════════════════ */
    /* ═══   ÉTAT GLOBAL                                      ═══ */
    /* ════════════════════════════════════════════════════════ */
    const ID_CONV = <?= $idConvActive ? json_encode($idConvActive) : 'null' ?>;
    const ID_USER = <?= json_encode($idUser) ?>;
    let lastMsgId = null;
    let pollInterval = null;

    /* ════════════════════════════════════════════════════════ */
    /* ═══   RECHERCHE CONVERSATIONS                          ═══ */
    /* ════════════════════════════════════════════════════════ */
    document.getElementById('search-conv')?.addEventListener('input', function(e) {
      const q = e.target.value.toLowerCase().trim();
      document.querySelectorAll('.conv-item').forEach(item => {
        const txt = item.dataset.search || '';
        item.style.display = (q === '' || txt.includes(q)) ? '' : 'none';
      });
    });

    /* ════════════════════════════════════════════════════════ */
    /* ═══   AUTO-SCROLL EN BAS                               ═══ */
    /* ════════════════════════════════════════════════════════ */
    function scrollBottom() {
      const m = document.getElementById('messages');
      if (m) m.scrollTop = m.scrollHeight;
    }
    window.addEventListener('load', scrollBottom);

    /* ════════════════════════════════════════════════════════ */
    /* ═══   AUTO-RESIZE TEXTAREA                             ═══ */
    /* ════════════════════════════════════════════════════════ */
    const ta = document.getElementById('msg-input');
    if (ta) {
      ta.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
      });
      ta.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          document.getElementById('form-msg').requestSubmit();
        }
      });
    }

    /* ════════════════════════════════════════════════════════ */
    /* ═══   ENVOYER MESSAGE                                  ═══ */
    /* ════════════════════════════════════════════════════════ */
    function sendMessage(e) {
      if (e) e.preventDefault();
      const input = document.getElementById('msg-input');
      const btn = document.getElementById('send-btn');
      const contenu = input.value.trim();
      if (!contenu || !ID_CONV) return;

      btn.disabled = true;

      const fd = new FormData();
      fd.append('idConversation', ID_CONV);
      fd.append('contenu', contenu);

      fetch('send_message.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
          if (json.success) {
            input.value = '';
            input.style.height = 'auto';
            appendMessage(json.message);
            scrollBottom();
          } else if (json.needLogin) {
            location.href = 'connexion.php?redirect=messagerie.php';
          } else {
            alert(json.message || 'Erreur d\'envoi');
          }
        })
        .catch(() => alert('Erreur réseau'))
        .finally(() => { btn.disabled = false; input.focus(); });
    }

    /* ════════════════════════════════════════════════════════ */
    /* ═══   AJOUTER MESSAGE AU DOM                           ═══ */
    /* ════════════════════════════════════════════════════════ */
    function appendMessage(m) {
      const cont = document.getElementById('messages');
      if (!cont) return;
      
      /* Retirer le placeholder vide si présent */
      const empty = cont.querySelector('.messages-empty');
      if (empty) empty.remove();

      const div = document.createElement('div');
      div.className = 'bubble bubble-' + m.expediteur;
      div.dataset.id = m.idMessage;
      div.innerHTML = escapeHtml(m.contenu).replace(/\n/g, '<br>');
      
      const t = document.createElement('div');
      t.className = 'bubble-time';
      t.innerHTML = m.heure;
      if (m.expediteur === 'me') {
        t.innerHTML += ' <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>';
      }
      div.appendChild(t);
      cont.appendChild(div);
      lastMsgId = m.idMessage;
    }

    function escapeHtml(s) {
      return s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
    }

    /* ════════════════════════════════════════════════════════ */
    /* ═══   POLLING NOUVEAUX MESSAGES                        ═══ */
    /* ════════════════════════════════════════════════════════ */
    function fetchNewMessages() {
      if (!ID_CONV) return;
      const lastBubble = document.querySelector('.bubble:last-of-type');
      if (lastBubble && !lastMsgId) lastMsgId = lastBubble.dataset.id;

      let url = 'fetch_messages.php?idConversation=' + encodeURIComponent(ID_CONV);
      if (lastMsgId) url += '&apresId=' + encodeURIComponent(lastMsgId);

      fetch(url)
        .then(r => r.json())
        .then(json => {
          if (json.success && json.messages.length > 0) {
            json.messages.forEach(m => appendMessage(m));
            scrollBottom();
          }
        })
        .catch(() => {});
    }

    if (ID_CONV) {
      /* Polling toutes les 5 sec */
      pollInterval = setInterval(fetchNewMessages, 5000);
    }

    /* ════════════════════════════════════════════════════════ */
    /* ═══   FOCUS INPUT À L'OUVERTURE                        ═══ */
    /* ════════════════════════════════════════════════════════ */
    window.addEventListener('load', () => {
      const ta = document.getElementById('msg-input');
      if (ta) ta.focus();
    });

    /* ════════════════════════════════════════════════════════ */
    /* ═══   MENU OPTIONS                                     ═══ */
    /* ════════════════════════════════════════════════════════ */
    function toggleMenu() {
      if (confirm('Supprimer cette conversation ?')) {
        alert('Fonction à implémenter (delete_conversation.php)');
      }
    }
  </script>
</body>
</html>
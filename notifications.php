<?php
session_start();
require_once 'connexion.php';
require_once 'notification_helper.php';

if (!isset($_SESSION['idUtilisateur']) || empty(trim($_SESSION['idUtilisateur']))) {
    header("Location: connexion.php?redirect=notifications.php");
    exit;
}

$idUser = trim($_SESSION['idUtilisateur']);
$idUserSql = mysqli_real_escape_string($conn, $idUser);

$rUser = mysqli_query($conn, "SELECT nom, prenom FROM Utilisateur WHERE idUtilisateur='$idUserSql' LIMIT 1");
$user = mysqli_fetch_assoc($rUser) ?: ['nom'=>'', 'prenom'=>''];
$initiales = strtoupper(mb_substr($user['prenom'] ?: 'U', 0, 1));

/* ════════════════════════════════════════════════════════════════ */
/* ═══   ACTIONS POST                                            ═══ */
/* ════════════════════════════════════════════════════════════════ */
$flashMsg = null;
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'mark_all_read') {
        marquerToutesLues($conn, $idUser);
        $flashMsg = "Toutes les notifications marquées comme lues.";
    }
    elseif ($action === 'delete_all_read') {
        mysqli_query($conn, "DELETE FROM Notification WHERE idUtilisateur='$idUserSql' AND statutLecture=1");
        $flashMsg = "Notifications lues supprimées.";
    }
    elseif ($action === 'delete' && !empty($_POST['idNotification'])) {
        supprimerNotif($conn, $_POST['idNotification'], $idUser);
        $flashMsg = "Notification supprimée.";
    }
    elseif ($action === 'mark_read' && !empty($_POST['idNotification'])) {
        marquerNotifLue($conn, $_POST['idNotification'], $idUser);
        $flashMsg = "Marquée comme lue.";
    }
    
    header("Location: notifications.php" . (isset($_GET['filtre']) ? '?filtre='.$_GET['filtre'] : '') . ($flashMsg ? (strpos($_SERVER['REQUEST_URI'], '?')!==false ? '&' : '?').'msg='.urlencode($flashMsg) : ''));
    exit;
}

if (isset($_GET['msg'])) {
    $flashMsg = $_GET['msg'];
}

/* ════════════════════════════════════════════════════════════════ */
/* ═══   FILTRES                                                 ═══ */
/* ════════════════════════════════════════════════════════════════ */
$filtre = $_GET['filtre'] ?? 'toutes';
$validFilters = ['toutes', 'non_lues', 'message', 'favori', 'vente', 'alerte', 'pro'];
if (!in_array($filtre, $validFilters)) $filtre = 'toutes';

$whereFiltre = '';
if ($filtre === 'non_lues') {
    $whereFiltre = " AND statutLecture=0";
} elseif ($filtre === 'pro') {
    $whereFiltre = " AND typeNoti LIKE 'pro_%'";
} elseif ($filtre === 'message') {
    $whereFiltre = " AND typeNoti = 'message'";
} elseif ($filtre === 'favori') {
    $whereFiltre = " AND typeNoti = 'favori'";
} elseif ($filtre === 'vente') {
    $whereFiltre = " AND typeNoti = 'vente'";
} elseif ($filtre === 'alerte') {
    $whereFiltre = " AND typeNoti = 'alerte'";
}

/* ════════════════════════════════════════════════════════════════ */
/* ═══   RÉCUPÉRATION                                            ═══ */
/* ════════════════════════════════════════════════════════════════ */

/* Compteurs par catégorie */
$stats = ['toutes'=>0, 'non_lues'=>0, 'message'=>0, 'favori'=>0, 'vente'=>0, 'alerte'=>0, 'pro'=>0];
$rStats = mysqli_query($conn, "
    SELECT typeNoti, statutLecture, COUNT(*) AS nb 
    FROM Notification 
    WHERE idUtilisateur='$idUserSql' 
    GROUP BY typeNoti, statutLecture
");
if ($rStats) {
    while ($s = mysqli_fetch_assoc($rStats)) {
        $stats['toutes'] += (int)$s['nb'];
        if ((int)$s['statutLecture'] === 0) $stats['non_lues'] += (int)$s['nb'];
        $t = strtolower($s['typeNoti'] ?? '');
        if (strpos($t, 'pro_') === 0) $stats['pro'] += (int)$s['nb'];
        elseif (isset($stats[$t])) $stats[$t] += (int)$s['nb'];
    }
}

/* Pagination */
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$rTot = mysqli_query($conn, "SELECT COUNT(*) AS n FROM Notification WHERE idUtilisateur='$idUserSql' $whereFiltre");
$totalRows = $rTot ? (int)mysqli_fetch_assoc($rTot)['n'] : 0;
$totalPages = max(1, ceil($totalRows / $perPage));

$rList = mysqli_query($conn, "
    SELECT idNotification, contenu, typeNoti, dateNoti, statutLecture 
    FROM Notification 
    WHERE idUtilisateur='$idUserSql' $whereFiltre 
    ORDER BY dateNoti DESC, idNotification DESC 
    LIMIT $perPage OFFSET $offset
");

$notifs = [];
if ($rList) {
    while ($n = mysqli_fetch_assoc($rList)) {
        $contenu = $n['contenu'];
        $lien = null;
        if (strpos($contenu, '|||') !== false) {
            list($contenu, $lien) = explode('|||', $contenu, 2);
        }
        $n['texte'] = $contenu;
        $n['lien'] = $lien;
        $notifs[] = $n;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mes notifications — AUTOMARKET</title>
  <link rel="icon" href="images/logo.png">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --blue: #185FA5; --blue-dk: #0C447C; --blue-bg: #E6F1FB; --blue-bd: #B5D4F4;
      --bg0: #ffffff; --bg1: #f5f4f0; --bg2: #eceae4;
      --t1: #1a1a18; --t2: #5f5e5a; --t3: #888780;
      --bd: rgba(0,0,0,0.11); --bd2: rgba(0,0,0,0.22);
      --green: #639922; --green-bg: #EAF3DE; --green-dk: #27500A;
      --red: #E24B4A; --red-bg: #FCEBEB;
      --orange: #FF8A4C; --orange-bg: #FAEEDA; --orange-dk: #854F0B;
      --r6: 6px; --r8: 8px; --r10: 10px;
    }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; background: var(--bg1); color: var(--t1); min-height: 100vh; }

    /* NAVBAR */
    .nav { background: var(--bg0); border-bottom: 0.5px solid var(--bd); height: 56px; display: flex; align-items: center; padding: 0 28px; gap: 16px; position: sticky; top: 0; z-index: 100; }
    .logo { display: flex; align-items: center; text-decoration: none; }
    .logo img { height: 30px; }
    .nav-spacer { flex: 1; }
    .nav-link { font-size: 13px; color: var(--t2); text-decoration: none; padding: 6px 12px; border-radius: var(--r6); }
    .nav-link:hover { background: var(--bg1); color: var(--t1); }
    .nav-avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--blue); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 500; cursor: pointer; text-decoration: none; }

    .container { max-width: 880px; margin: 0 auto; padding: 0; }

    /* HEADER */
    .page-header { background: var(--bg0); padding: 24px 28px 16px; border-bottom: 0.5px solid var(--bd); }
    .breadcrumb { font-size: 12px; color: var(--t3); margin-bottom: 8px; }
    .breadcrumb a { color: var(--blue); text-decoration: none; }
    .breadcrumb a:hover { text-decoration: underline; }
    .breadcrumb-current { color: var(--t1); font-weight: 500; }
    .page-title { font-size: 22px; font-weight: 600; color: var(--t1); margin: 0 0 4px; }
    .page-sub { font-size: 13px; color: var(--t2); margin: 0; }

    /* FILTRES */
    .filters-bar { background: var(--bg0); padding: 14px 28px; border-bottom: 0.5px solid var(--bd); display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .filter-pill { font-size: 12px; padding: 6px 14px; border-radius: 14px; border: 0.5px solid var(--bd); cursor: pointer; text-decoration: none; color: var(--t2); transition: all .15s; display: inline-flex; align-items: center; gap: 6px; }
    .filter-pill:hover { border-color: var(--blue); color: var(--blue); }
    .filter-pill.active { background: var(--blue-bg); color: var(--blue-dk); border-color: var(--blue-bd); font-weight: 500; }
    .filter-count { font-size: 10px; padding: 1px 6px; background: rgba(0,0,0,0.06); border-radius: 8px; }
    .filter-pill.active .filter-count { background: rgba(255,255,255,0.6); }

    .actions-bar { background: var(--bg0); padding: 0 28px 14px; display: flex; gap: 8px; justify-content: flex-end; border-bottom: 0.5px solid var(--bd); }
    .btn-action { font-size: 12px; padding: 7px 13px; border-radius: var(--r6); border: 0.5px solid var(--bd); background: var(--bg0); color: var(--t2); cursor: pointer; font-family: inherit; transition: all .15s; }
    .btn-action:hover { border-color: var(--bd2); color: var(--t1); }
    .btn-action.danger:hover { color: var(--red); border-color: var(--red); }

    /* FLASH */
    .flash { padding: 12px 28px; font-size: 13px; display: flex; align-items: center; gap: 10px; background: var(--green-bg); color: var(--green-dk); border-bottom: 0.5px solid #C0DD97; }

    /* LIST */
    .notifs-list { background: var(--bg0); }
    .notif-row {
      display: grid;
      grid-template-columns: auto 1fr auto;
      gap: 14px;
      padding: 14px 28px;
      border-bottom: 0.5px solid var(--bd);
      align-items: center;
      transition: background .15s;
      position: relative;
    }
    .notif-row:hover { background: var(--bg1); }
    .notif-row.unread { background: #FAFCFF; }
    .notif-row.unread::before {
      content: '';
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--blue);
    }

    .notif-icon { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .notif-icon.message { background: var(--green-bg); color: var(--green); }
    .notif-icon.favori { background: var(--red-bg); color: var(--red); }
    .notif-icon.vente { background: var(--blue-bg); color: var(--blue); }
    .notif-icon.alerte { background: var(--blue-bg); color: var(--blue); }
    .notif-icon.pro_attente { background: var(--orange-bg); color: #BA7517; }
    .notif-icon.pro_valide,
    .notif-icon.pro_active { background: var(--green-bg); color: var(--green); }
    .notif-icon.pro_rejete { background: var(--red-bg); color: var(--red); }
    .notif-icon.success { background: var(--green-bg); color: var(--green); }
    .notif-icon.warning { background: var(--orange-bg); color: #BA7517; }
    .notif-icon.error { background: var(--red-bg); color: var(--red); }
    .notif-icon.info { background: var(--blue-bg); color: var(--blue); }

    .notif-content { min-width: 0; }
    .notif-text {
      font-size: 14px;
      color: var(--t1);
      line-height: 1.4;
      margin-bottom: 3px;
      word-wrap: break-word;
    }
    .notif-row.unread .notif-text { font-weight: 500; }
    .notif-meta { font-size: 11px; color: var(--t3); display: flex; align-items: center; gap: 8px; }
    .notif-type-tag { font-size: 10px; padding: 1px 8px; background: var(--bg2); color: var(--t2); border-radius: 8px; text-transform: uppercase; letter-spacing: 0.3px; font-weight: 500; }
    .notif-link {
      font-size: 12px;
      color: var(--blue);
      text-decoration: none;
      margin-left: 6px;
    }
    .notif-link:hover { text-decoration: underline; }

    .notif-actions { display: flex; gap: 6px; flex-shrink: 0; }
    .icon-btn { width: 32px; height: 32px; border: 0.5px solid var(--bd); border-radius: var(--r6); background: var(--bg0); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--t2); transition: all .15s; padding: 0; font-family: inherit; }
    .icon-btn:hover { background: var(--bg1); border-color: var(--bd2); color: var(--t1); }
    .icon-btn.danger:hover { background: var(--red-bg); border-color: var(--red); color: var(--red); }

    /* EMPTY */
    .empty { padding: 60px 30px; text-align: center; color: var(--t2); background: var(--bg0); }
    .empty-icon { width: 60px; height: 60px; margin: 0 auto 16px; background: var(--blue-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--blue); }
    .empty-title { font-size: 16px; font-weight: 500; margin-bottom: 6px; color: var(--t1); }
    .empty-text { font-size: 13px; color: var(--t2); }

    /* PAGINATION */
    .pagination { padding: 20px 28px; background: var(--bg0); display: flex; gap: 6px; justify-content: center; align-items: center; flex-wrap: wrap; }
    .page-link { padding: 6px 12px; border-radius: var(--r6); border: 0.5px solid var(--bd); color: var(--t2); text-decoration: none; font-size: 13px; transition: all .15s; }
    .page-link:hover { border-color: var(--blue); color: var(--blue); }
    .page-link.current { background: var(--blue); color: #fff; border-color: var(--blue); }
    .page-link.disabled { opacity: 0.4; pointer-events: none; }
    .page-info { font-size: 12px; color: var(--t3); margin: 0 8px; }

    @media (max-width: 700px) {
      .nav { padding: 0 14px; }
      .page-header, .filters-bar, .actions-bar, .notif-row, .pagination, .flash { padding-left: 14px; padding-right: 14px; }
      .notif-row { grid-template-columns: auto 1fr; gap: 12px; }
      .notif-actions { grid-column: 1 / -1; justify-content: flex-end; padding-top: 4px; }
    }
  </style>
</head>
<body>

  <nav class="nav">
    <a class="logo" href="index.php">
      <img src="images/logo.png" alt="AUTOMARKET">
    </a>
    <div class="nav-spacer"></div>
    <a href="index.php" class="nav-link">Accueil</a>
    <a href="favoris.php" class="nav-link">Favoris</a>
    <a href="messagerie.php" class="nav-link">Messages</a>
    <?php include __DIR__ . '/notif_dropdown.php'; ?>
    <a href="monprofil.php" class="nav-avatar"><?= htmlspecialchars($initiales) ?></a>
  </nav>

  <?php include __DIR__ . '/notif_banner.php'; ?>

  <div class="container">

    <!-- HEADER -->
    <div class="page-header">
      <div class="breadcrumb">
        <a href="index.php">Accueil</a> <span>›</span>
        <span class="breadcrumb-current">Notifications</span>
      </div>
      <h1 class="page-title">Mes notifications</h1>
      <p class="page-sub">
        <?php if ($stats['non_lues'] > 0): ?>
          <strong><?= $stats['non_lues'] ?></strong> non lue<?= $stats['non_lues']>1?'s':'' ?> sur <?= $stats['toutes'] ?> au total
        <?php else: ?>
          <?= $stats['toutes'] ?> notification<?= $stats['toutes']>1?'s':'' ?> · Toutes lues ✓
        <?php endif; ?>
      </p>
    </div>

    <?php if ($flashMsg): ?>
      <div class="flash">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <?= htmlspecialchars($flashMsg) ?>
      </div>
    <?php endif; ?>

    <!-- FILTRES -->
    <div class="filters-bar">
      <a href="?filtre=toutes" class="filter-pill <?= $filtre==='toutes'?'active':'' ?>">
        Toutes <span class="filter-count"><?= $stats['toutes'] ?></span>
      </a>
      <a href="?filtre=non_lues" class="filter-pill <?= $filtre==='non_lues'?'active':'' ?>">
        Non lues <span class="filter-count"><?= $stats['non_lues'] ?></span>
      </a>
      <?php if ($stats['message'] > 0): ?>
      <a href="?filtre=message" class="filter-pill <?= $filtre==='message'?'active':'' ?>">
        Messages <span class="filter-count"><?= $stats['message'] ?></span>
      </a>
      <?php endif; ?>
      <?php if ($stats['favori'] > 0): ?>
      <a href="?filtre=favori" class="filter-pill <?= $filtre==='favori'?'active':'' ?>">
        Favoris <span class="filter-count"><?= $stats['favori'] ?></span>
      </a>
      <?php endif; ?>
      <?php if ($stats['vente'] > 0): ?>
      <a href="?filtre=vente" class="filter-pill <?= $filtre==='vente'?'active':'' ?>">
        Ventes <span class="filter-count"><?= $stats['vente'] ?></span>
      </a>
      <?php endif; ?>
      <?php if ($stats['alerte'] > 0): ?>
      <a href="?filtre=alerte" class="filter-pill <?= $filtre==='alerte'?'active':'' ?>">
        Alertes <span class="filter-count"><?= $stats['alerte'] ?></span>
      </a>
      <?php endif; ?>
      <?php if ($stats['pro'] > 0): ?>
      <a href="?filtre=pro" class="filter-pill <?= $filtre==='pro'?'active':'' ?>">
        Compte Pro <span class="filter-count"><?= $stats['pro'] ?></span>
      </a>
      <?php endif; ?>
    </div>

    <!-- ACTIONS -->
    <?php if ($stats['toutes'] > 0): ?>
    <div class="actions-bar">
      <?php if ($stats['non_lues'] > 0): ?>
        <form method="POST" style="display:inline;margin:0">
          <input type="hidden" name="action" value="mark_all_read">
          <button type="submit" class="btn-action">✓ Tout marquer comme lu</button>
        </form>
      <?php endif; ?>
      <?php if ($stats['toutes'] - $stats['non_lues'] > 0): ?>
        <form method="POST" style="display:inline;margin:0" onsubmit="return confirm('Supprimer toutes les notifications déjà lues ?')">
          <input type="hidden" name="action" value="delete_all_read">
          <button type="submit" class="btn-action danger">🗑 Effacer les lues</button>
        </form>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- LISTE -->
    <div class="notifs-list">
      <?php if (count($notifs) === 0): ?>
        <div class="empty">
          <div class="empty-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          </div>
          <div class="empty-title">Aucune notification</div>
          <div class="empty-text">
            <?= $filtre === 'non_lues' ? 'Vous avez tout lu !' : 'Vous n\'avez aucune notification dans cette catégorie.' ?>
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($notifs as $n):
          $t = strtolower($n['typeNoti'] ?? 'info');
          $unread = (int)$n['statutLecture'] === 0;
          $time = notifTimeAgo($n['dateNoti']);
        ?>
          <div class="notif-row <?= $unread?'unread':'' ?>">
            <div class="notif-icon <?= htmlspecialchars($t) ?>">
              <?= notifIconeSvg($t) ?>
            </div>
            <div class="notif-content">
              <div class="notif-text"><?= htmlspecialchars($n['texte']) ?></div>
              <div class="notif-meta">
                <span class="notif-type-tag"><?= htmlspecialchars(str_replace('_', ' ', $t)) ?></span>
                <span><?= htmlspecialchars($time) ?></span>
                <?php if (!empty($n['lien'])): ?>
                  <a href="<?= htmlspecialchars($n['lien']) ?>" class="notif-link">Voir →</a>
                <?php endif; ?>
              </div>
            </div>
            <div class="notif-actions">
              <?php if ($unread): ?>
                <form method="POST" style="margin:0">
                  <input type="hidden" name="action" value="mark_read">
                  <input type="hidden" name="idNotification" value="<?= htmlspecialchars($n['idNotification']) ?>">
                  <button type="submit" class="icon-btn" title="Marquer comme lue">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  </button>
                </form>
              <?php endif; ?>
              <form method="POST" style="margin:0" onsubmit="return confirm('Supprimer cette notification ?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="idNotification" value="<?= htmlspecialchars($n['idNotification']) ?>">
                <button type="submit" class="icon-btn danger" title="Supprimer">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg>
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <a href="?filtre=<?= htmlspecialchars($filtre) ?>&page=<?= $page-1 ?>" 
           class="page-link <?= $page<=1?'disabled':'' ?>">← Précédent</a>
        <?php
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        for ($p = $start; $p <= $end; $p++):
        ?>
          <a href="?filtre=<?= htmlspecialchars($filtre) ?>&page=<?= $p ?>" 
             class="page-link <?= $p===$page?'current':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <a href="?filtre=<?= htmlspecialchars($filtre) ?>&page=<?= $page+1 ?>" 
           class="page-link <?= $page>=$totalPages?'disabled':'' ?>">Suivant →</a>
        <span class="page-info">Page <?= $page ?> sur <?= $totalPages ?></span>
      </div>
    <?php endif; ?>

  </div>

</body>
</html>
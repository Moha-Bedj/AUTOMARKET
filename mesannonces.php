<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['idUtilisateur'])) {
    header('Location: inscription.php');
    exit;
}

$id = mysqli_real_escape_string($conn, $_SESSION['idUtilisateur']);

/* Filtre par statut */
$filter = $_GET['filter'] ?? 'all';

/* Compter par statut */
$counts = ['all' => 0, 'active' => 0, 'pause' => 0, 'vendue' => 0, 'expiree' => 0];

$rC = mysqli_query($conn, "SELECT statutAnnonce, COUNT(*) as n FROM Annonce WHERE idVendeur='$id' GROUP BY statutAnnonce");
while ($r = mysqli_fetch_assoc($rC)) {
    $counts[$r['statutAnnonce']] = (int)$r['n'];
    $counts['all'] += (int)$r['n'];
}

/* WHERE clause selon filtre */
$where = "a.idVendeur='$id'";
if ($filter !== 'all') {
    $f = mysqli_real_escape_string($conn, $filter);
    $where .= " AND a.statutAnnonce='$f'";
}

/* Récupérer les annonces */
$sql = "
    SELECT
        a.idAnnonce, a.titre, a.prix, a.localisation, a.datePublication, a.statutAnnonce,
        v.annee, v.kilometrage, v.carburant, v.transmission, v.typeVehicule
    FROM Annonce a, Vehicule v
    WHERE $where
    AND a.idVehicule = v.idVehicule
    ORDER BY a.datePublication DESC
";
$res = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mes annonces — AUTOMARKET</title>
    <link rel="icon" href="images/logo.png">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue: #185FA5; --blue-dk: #0C447C; --blue-bg: #E6F1FB; --blue-bd: #B5D4F4;
      --bg0: #ffffff; --bg1: #f5f4f0;
      --t1: #1a1a18; --t2: #5f5e5a; --t3: #888780;
      --bd: rgba(0,0,0,0.11); --bd2: rgba(0,0,0,0.22);
      --green: #639922; --green-bg: #EAF3DE; --green-dk: #27500A; --green-bd: #C0DD97;
      --red: #E24B4A; --red-bg: #FCEBEB; --red-dk: #791F1F;
      --amber: #BA7517; --amber-bg: #FAEEDA; --amber-bd: #FAC775;
      --gray: #6b7280; --gray-bg: #f3f4f6;
      --r6: 6px; --r8: 8px; --r10: 10px; --r12: 12px;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      font-size: 14px;
      background: var(--bg1);
      color: var(--t1);
      min-height: 100vh;
    }

    /* NAVBAR */
    .nav {
      background: var(--bg0);
      border-bottom: 0.5px solid var(--bd);
      height: 52px;
      display: flex;
      align-items: center;
      padding: 0 20px;
      gap: 16px;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .logo { display: flex; align-items: center; text-decoration: none; }
    .back-btn {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 13px;
      color: var(--t2);
      text-decoration: none;
      padding: 6px 12px;
      border-radius: var(--r6);
      transition: all .15s;
      margin-left: auto;
    }
    .back-btn:hover { background: var(--bg1); color: var(--t1); }

    .container {
      max-width: 960px;
      margin: 0 auto;
      padding: 24px 16px 48px;
    }

    /* HEADER */
    .page-head {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      margin-bottom: 20px;
      flex-wrap: wrap;
      gap: 12px;
    }
    .page-title {
      font-size: 22px;
      font-weight: 500;
      margin-bottom: 4px;
    }
    .page-sub { font-size: 13px; color: var(--t2); }
    .btn-primary {
      background: var(--blue);
      color: #fff;
      border: none;
      border-radius: var(--r8);
      padding: 10px 18px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      font-family: inherit;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: background .15s;
    }
    .btn-primary:hover { background: var(--blue-dk); }

    /* FILTRES */
    .filter-tabs {
      display: flex;
      gap: 6px;
      margin-bottom: 16px;
      overflow-x: auto;
      padding-bottom: 4px;
      scrollbar-width: none;
    }
    .filter-tabs::-webkit-scrollbar { display: none; }

    .filter-tab {
      padding: 8px 14px;
      border-radius: 20px;
      border: 0.5px solid var(--bd2);
      background: var(--bg0);
      font-size: 13px;
      color: var(--t2);
      cursor: pointer;
      white-space: nowrap;
      font-family: inherit;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      text-decoration: none;
      transition: all .15s;
    }
    .filter-tab:hover {
      border-color: var(--blue);
      color: var(--blue);
    }
    .filter-tab.active {
      background: var(--blue);
      color: #fff;
      border-color: var(--blue);
      font-weight: 500;
    }
    .filter-tab .tab-count {
      background: rgba(0,0,0,.08);
      padding: 1px 7px;
      border-radius: 10px;
      font-size: 11px;
      font-weight: 500;
    }
    .filter-tab.active .tab-count {
      background: rgba(255,255,255,.25);
    }

    /* LISTINGS */
    .listings { display: flex; flex-direction: column; gap: 10px; }

    .lcard {
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r10);
      display: flex;
      overflow: hidden;
      position: relative;
      transition: border-color .15s, opacity .3s, transform .3s;
    }
    .lcard:hover { border-color: var(--blue); }
    .lcard.sold-card { opacity: 0.7; }
    .lcard.removing {
      opacity: 0;
      transform: translateX(30px);
    }

    .lcard-img {
      width: 190px;
      flex-shrink: 0;
      background: var(--bg1);
      position: relative;
      overflow: hidden;
      height: 150px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--t3);
      cursor: pointer;
    }

    /* Status badge sur l'image */
    .status-badge {
      position: absolute;
      top: 10px;
      left: 10px;
      font-size: 11px;
      font-weight: 500;
      padding: 3px 10px;
      border-radius: 20px;
      color: #fff;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .status-active { background: var(--green); }
    .status-pause  { background: var(--amber); }
    .status-vendue { background: var(--gray); }
    .status-expiree { background: var(--red); }
    .status-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: currentColor;
      opacity: .8;
    }

    .lcard-body {
      flex: 1;
      padding: 14px 16px;
      display: flex;
      flex-direction: column;
      min-width: 0;
    }
    .lcard-top {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 8px;
      margin-bottom: 7px;
    }
    .lcard-title {
      font-size: 15px;
      font-weight: 500;
      line-height: 1.3;
      cursor: pointer;
    }
    .lcard-title:hover { color: var(--blue); }
    .lcard-price {
      font-size: 18px;
      font-weight: 500;
      color: var(--blue);
      white-space: nowrap;
    }
    .lcard-specs {
      display: flex;
      gap: 10px;
      margin-bottom: 10px;
      flex-wrap: wrap;
    }
    .lspec { font-size: 12px; color: var(--t2); }
    .lspec-dot {
      width: 3px; height: 3px;
      border-radius: 50%;
      background: var(--t3);
      align-self: center;
    }

    /* ACTIONS */
    .lcard-actions {
      display: flex;
      gap: 6px;
      margin-top: auto;
      padding-top: 10px;
      border-top: 0.5px solid var(--bd);
      flex-wrap: wrap;
      align-items: center;
    }
    .action-btn {
      padding: 6px 12px;
      border-radius: var(--r6);
      border: 0.5px solid var(--bd2);
      background: var(--bg0);
      font-size: 12px;
      cursor: pointer;
      font-family: inherit;
      color: var(--t2);
      display: inline-flex;
      align-items: center;
      gap: 4px;
      text-decoration: none;
      transition: all .15s;
    }
    .action-btn:hover {
      border-color: var(--blue);
      color: var(--blue);
      background: var(--blue-bg);
    }
    .action-btn.danger:hover {
      border-color: var(--red);
      color: var(--red);
      background: var(--red-bg);
    }
    .action-btn.success:hover {
      border-color: var(--green);
      color: var(--green-dk);
      background: var(--green-bg);
    }

    .lcard-date {
      margin-left: auto;
      font-size: 11px;
      color: var(--t3);
    }

    /* DROPDOWN ACTIONS */
    .more-wrap { position: relative; }
    .more-btn {
      width: 30px;
      height: 30px;
      border-radius: var(--r6);
      border: 0.5px solid var(--bd2);
      background: var(--bg0);
      cursor: pointer;
      color: var(--t2);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all .15s;
    }
    .more-btn:hover {
      border-color: var(--blue);
      color: var(--blue);
    }
    .more-dropdown {
      position: absolute;
      right: 0;
      top: 36px;
      background: #fff;
      border: 0.5px solid var(--bd);
      border-radius: var(--r8);
      padding: 4px 0;
      min-width: 180px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      z-index: 10;
      display: none;
    }
    .more-dropdown.show { display: block; }
    .more-item {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      font-size: 13px;
      color: var(--t1);
      cursor: pointer;
      background: none;
      border: none;
      width: 100%;
      text-align: left;
      font-family: inherit;
    }
    .more-item:hover { background: var(--bg1); }
    .more-item.danger { color: var(--red); }
    .more-divider {
      height: 0.5px;
      background: var(--bd);
      margin: 4px 0;
    }

    /* EMPTY STATE */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r12);
    }
    .empty-icon {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: var(--bg1);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      color: var(--t3);
    }
    .empty-title {
      font-size: 16px;
      font-weight: 500;
      margin-bottom: 6px;
    }
    .empty-sub {
      font-size: 13px;
      color: var(--t2);
      margin-bottom: 20px;
      line-height: 1.6;
      max-width: 380px;
      margin-left: auto;
      margin-right: auto;
    }

    /* MODAL */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.5);
      z-index: 500;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .modal-overlay.show { display: flex; }
    .modal {
      background: var(--bg0);
      border-radius: var(--r12);
      padding: 24px;
      max-width: 420px;
      width: 100%;
      animation: modalPop .2s ease;
    }
    @keyframes modalPop {
      from { opacity: 0; transform: scale(.95); }
      to   { opacity: 1; transform: scale(1); }
    }
    .modal-title {
      font-size: 17px;
      font-weight: 500;
      margin-bottom: 8px;
    }
    .modal-title.danger { color: var(--red-dk); }
    .modal-sub {
      font-size: 13px;
      color: var(--t2);
      margin-bottom: 18px;
      line-height: 1.5;
    }
    .modal-actions {
      display: flex;
      gap: 10px;
    }
    .btn-ghost {
      background: transparent;
      color: var(--t2);
      border: 0.5px solid var(--bd2);
      border-radius: var(--r8);
      padding: 10px 18px;
      font-size: 13px;
      cursor: pointer;
      font-family: inherit;
      flex: 1;
    }
    .btn-ghost:hover { background: var(--bg1); }
    .btn-danger {
      background: var(--red);
      color: #fff;
      border: none;
      border-radius: var(--r8);
      padding: 10px 18px;
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      font-family: inherit;
      flex: 1;
    }
    .btn-danger:hover { background: #c53030; }
    .btn-success {
      background: var(--green);
      color: #fff;
      border: none;
      border-radius: var(--r8);
      padding: 10px 18px;
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      font-family: inherit;
      flex: 1;
    }
    .btn-success:hover { background: var(--green-dk); }

    /* TOAST */
    .toast {
      position: fixed;
      bottom: 24px;
      left: 50%;
      transform: translateX(-50%) translateY(20px);
      background: var(--t1);
      color: #fff;
      padding: 10px 20px;
      border-radius: var(--r8);
      font-size: 13px;
      opacity: 0;
      pointer-events: none;
      transition: all .3s;
      z-index: 999;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .toast.show {
      opacity: 1;
      transform: translateX(-50%) translateY(0);
    }

    @media (max-width: 600px) {
      .lcard { flex-direction: column; }
      .lcard-img { width: 100%; height: 180px; }
      .container { padding: 16px 10px 32px; }
      .page-head { flex-direction: column; align-items: stretch; }
      .btn-primary { width: 100%; justify-content: center; }
      .lcard-actions { gap: 4px; }
      .action-btn { font-size: 11px; padding: 5px 9px; }
    }
  </style>
</head>
<body>

  <nav class="nav">
    <a class="logo" href="index.php">
      <img src="images/logo.png" alt="AUTOMARKET" style="height:34px;">
    </a>
    <a href="index.php" class="back-btn">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <path d="M19 12H5M12 19l-7-7 7-7"/>
      </svg>
      Retour à l'accueil
    </a>
  </nav>

  <div class="container">

    <!-- HEADER -->
    <div class="page-head">
      <div>
        <h1 class="page-title">Mes annonces</h1>
        <div class="page-sub">
          <?= $counts['all'] ?>
          <?= $counts['all'] > 1 ? 'annonces publiées' : 'annonce publiée' ?>
          · <?= $counts['active'] ?> active<?= $counts['active'] > 1 ? 's' : '' ?>
        </div>
      </div>
      <a href="publier.php" class="btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <path d="M12 5v14M5 12h14"/>
        </svg>
        Publier une annonce
      </a>
    </div>

    <!-- FILTRES -->
    <div class="filter-tabs">
      <a href="?filter=all" class="filter-tab <?= $filter==='all' ? 'active' : '' ?>">
        Toutes <span class="tab-count"><?= $counts['all'] ?></span>
      </a>
      <a href="?filter=active" class="filter-tab <?= $filter==='active' ? 'active' : '' ?>">
        <span class="status-dot" style="background:var(--green)"></span>
        Actives <span class="tab-count"><?= $counts['active'] ?></span>
      </a>
      <a href="?filter=pause" class="filter-tab <?= $filter==='pause' ? 'active' : '' ?>">
        <span class="status-dot" style="background:var(--amber)"></span>
        En pause <span class="tab-count"><?= $counts['pause'] ?></span>
      </a>
      <a href="?filter=vendue" class="filter-tab <?= $filter==='vendue' ? 'active' : '' ?>">
        <span class="status-dot" style="background:var(--gray)"></span>
        Vendues <span class="tab-count"><?= $counts['vendue'] ?></span>
      </a>
      <a href="?filter=expiree" class="filter-tab <?= $filter==='expiree' ? 'active' : '' ?>">
        <span class="status-dot" style="background:var(--red)"></span>
        Expirées <span class="tab-count"><?= $counts['expiree'] ?></span>
      </a>
    </div>

    <?php if ($counts['all'] == 0): ?>

      <!-- AUCUNE ANNONCE -->
      <div class="empty-state">
        <div class="empty-icon">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="1" y="6" width="22" height="13" rx="3"/>
            <circle cx="7" cy="16" r="1.5"/>
            <circle cx="17" cy="16" r="1.5"/>
          </svg>
        </div>
        <div class="empty-title">Vous n'avez pas encore d'annonce</div>
        <div class="empty-sub">
          Publiez votre première annonce en moins de 5 minutes. C'est gratuit et visible par des milliers d'acheteurs.
        </div>
        <a href="publier.php" class="btn-primary">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <path d="M12 5v14M5 12h14"/>
          </svg>
          Publier une annonce
        </a>
      </div>

    <?php elseif (!$res || mysqli_num_rows($res) == 0): ?>

      <!-- AUCUN RÉSULTAT POUR LE FILTRE -->
      <div class="empty-state">
        <div class="empty-icon">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
            <path d="M21 21l-4.35-4.35M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16z"/>
          </svg>
        </div>
        <div class="empty-title">Aucune annonce dans cette catégorie</div>
        <div class="empty-sub">Essayez un autre filtre ou publiez une nouvelle annonce.</div>
        <a href="?filter=all" class="btn-primary" style="background:var(--bg1);color:var(--t2)">
          Voir toutes mes annonces
        </a>
      </div>

    <?php else: ?>

      <!-- LISTE -->
      <div class="listings">
        <?php
        $statusLabels = [
            'active'  => ['Active', 'status-active'],
            'pause'   => ['En pause', 'status-pause'],
            'vendue'  => ['Vendue', 'status-vendue'],
            'expiree' => ['Expirée', 'status-expiree'],
        ];

        while ($a = mysqli_fetch_assoc($res)):
            $titre = htmlspecialchars($a['titre']);
            $prix  = number_format($a['prix'], 0, ',', ' ');
            $loc   = htmlspecialchars($a['localisation']);
            $km    = number_format($a['kilometrage'], 0, ',', ' ');
            $annee = $a['annee'];
            $carbu = htmlspecialchars($a['carburant']);
            $trans = htmlspecialchars($a['transmission']);
            $idAnn = $a['idAnnonce'];
            $statut = $a['statutAnnonce'];
            $statutInfo = $statusLabels[$statut] ?? [ucfirst($statut), 'status-pause'];

            $date = new DateTime($a['datePublication']);
            $diff = (new DateTime())->diff($date)->days;
            if ($diff == 0)     $dl = "Publiée aujourd'hui";
            elseif ($diff == 1) $dl = "Publiée hier";
            else                $dl = "Publiée il y a $diff jours";
        ?>
          <div class="lcard <?= $statut === 'vendue' ? 'sold-card' : '' ?>" id="card-<?= $idAnn ?>">

            <div class="lcard-img" onclick="location.href='fiche_annonce.php?id=<?= $idAnn ?>'">
              <div class="status-badge <?= $statutInfo[1] ?>">
                <span class="status-dot"></span>
                <?= $statutInfo[0] ?>
              </div>
              <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.8">
                <rect x="1" y="6" width="22" height="13" rx="3"/>
                <circle cx="7" cy="16" r="1.5"/>
                <circle cx="17" cy="16" r="1.5"/>
              </svg>
            </div>

            <div class="lcard-body">
              <div class="lcard-top">
                <div class="lcard-title" onclick="location.href='fiche_annonce.php?id=<?= $idAnn ?>'">
                  <?= $titre ?>
                </div>
                <div class="lcard-price"><?= $prix ?> DA</div>
              </div>
              <div class="lcard-specs">
                <span class="lspec"><?= $annee ?></span>
                <span class="lspec-dot"></span>
                <span class="lspec"><?= $km ?> km</span>
                <span class="lspec-dot"></span>
                <span class="lspec"><?= $carbu ?></span>
                <span class="lspec-dot"></span>
                <span class="lspec"><?= $trans ?></span>
                <span class="lspec-dot"></span>
                <span class="lspec"><?= $loc ?></span>
              </div>

              <div class="lcard-actions">
                <a href="modifier_annonce.php?id=<?= $idAnn ?>" class="action-btn">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                  Modifier
                </a>

                <a href="fiche_annonce.php?id=<?= $idAnn ?>" class="action-btn">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                  Voir
                </a>

                <?php if ($statut === 'active'): ?>
                  <button class="action-btn success" onclick="openSoldModal(<?= $idAnn ?>)">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                      <path d="M20 6L9 17l-5-5"/>
                    </svg>
                    Marquer vendue
                  </button>
                <?php endif; ?>

                <!-- Menu ⋯ -->
                <div class="more-wrap">
                  <button class="more-btn" onclick="toggleMore(<?= $idAnn ?>, event)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                      <circle cx="12" cy="5" r="2"/>
                      <circle cx="12" cy="12" r="2"/>
                      <circle cx="12" cy="19" r="2"/>
                    </svg>
                  </button>
                  <div class="more-dropdown" id="more-<?= $idAnn ?>">

                    <?php if ($statut === 'active'): ?>
                      <button class="more-item" onclick="doAction(<?= $idAnn ?>, 'pause')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <rect x="6" y="4" width="4" height="16"/>
                          <rect x="14" y="4" width="4" height="16"/>
                        </svg>
                        Mettre en pause
                      </button>
                    <?php elseif ($statut === 'pause'): ?>
                      <button class="more-item" onclick="doAction(<?= $idAnn ?>, 'activate')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <polygon points="5 3 19 12 5 21 5 3"/>
                        </svg>
                        Remettre en ligne
                      </button>
                    <?php endif; ?>

                    <?php if ($statut === 'active' || $statut === 'pause'): ?>
                      <button class="more-item" onclick="openSoldModal(<?= $idAnn ?>)">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                          <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        Marquer comme vendue
                      </button>
                    <?php endif; ?>

                    <button class="more-item" onclick="shareAnnonce(<?= $idAnn ?>)">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <circle cx="18" cy="5" r="3"/>
                        <circle cx="6" cy="12" r="3"/>
                        <circle cx="18" cy="19" r="3"/>
                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                      </svg>
                      Partager
                    </button>

                    <div class="more-divider"></div>

                    <button class="more-item danger" onclick="openDeleteModal(<?= $idAnn ?>)">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/>
                        <path d="M10 11v6M14 11v6"/>
                      </svg>
                      Supprimer
                    </button>
                  </div>
                </div>

                <div class="lcard-date"><?= $dl ?></div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>

    <?php endif; ?>

  </div>

  <!-- MODAL SUPPRESSION -->
  <div class="modal-overlay" id="delete-modal">
    <div class="modal">
      <div class="modal-title danger">⚠ Supprimer cette annonce ?</div>
      <div class="modal-sub">
        Cette action est <strong>irréversible</strong>. L'annonce et toutes ses données seront définitivement supprimées.
      </div>
      <div class="modal-actions">
        <button class="btn-ghost" onclick="closeModal('delete-modal')">Annuler</button>
        <button class="btn-danger" onclick="confirmDelete()">Supprimer</button>
      </div>
    </div>
  </div>

  <!-- MODAL VENDUE -->
  <div class="modal-overlay" id="sold-modal">
    <div class="modal">
      <div class="modal-title">✓ Marquer comme vendue ?</div>
      <div class="modal-sub">
        Votre annonce ne sera plus visible par les acheteurs. Vous pourrez toujours la retrouver dans l'onglet <strong>Vendues</strong>.
      </div>
      <div class="modal-actions">
        <button class="btn-ghost" onclick="closeModal('sold-modal')">Annuler</button>
        <button class="btn-success" onclick="confirmSold()">Confirmer</button>
      </div>
    </div>
  </div>

  <!-- TOAST -->
  <div class="toast" id="toast"></div>

  <script>
    let currentActionId = null;

    function showToast(message) {
      const t = document.getElementById('toast');
      t.textContent = message;
      t.classList.add('show');
      setTimeout(() => t.classList.remove('show'), 2500);
    }

    function toggleMore(id, e) {
      e.stopPropagation();
      document.querySelectorAll('.more-dropdown').forEach(d => {
        if (d.id !== 'more-' + id) d.classList.remove('show');
      });
      document.getElementById('more-' + id).classList.toggle('show');
    }

    document.addEventListener('click', function(e) {
      if (!e.target.closest('.more-wrap')) {
        document.querySelectorAll('.more-dropdown').forEach(d => d.classList.remove('show'));
      }
    });

    function doAction(id, action) {
      const fd = new FormData();
      fd.append('action', action);
      fd.append('idAnnonce', id);

      fetch('action_annonce.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
          if (json.success) {
            if (action === 'pause') showToast('Annonce mise en pause');
            if (action === 'activate') showToast('Annonce remise en ligne');
            setTimeout(() => location.reload(), 500);
          } else {
            showToast('Erreur : ' + (json.message || ''));
          }
        })
        .catch(() => showToast('Erreur réseau'));
    }

    /* MODAL SUPPRIMER */
    function openDeleteModal(id) {
      currentActionId = id;
      document.getElementById('delete-modal').classList.add('show');
      document.querySelectorAll('.more-dropdown').forEach(d => d.classList.remove('show'));
    }
    function confirmDelete() {
      const id = currentActionId;
      const fd = new FormData();
      fd.append('action', 'delete');
      fd.append('idAnnonce', id);

      fetch('action_annonce.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
          if (json.success) {
            closeModal('delete-modal');
            const card = document.getElementById('card-' + id);
            card.classList.add('removing');
            setTimeout(() => {
              card.remove();
              showToast('Annonce supprimée');
              setTimeout(() => location.reload(), 800);
            }, 300);
          } else {
            showToast('Erreur : ' + (json.message || ''));
          }
        });
    }

    /* MODAL VENDUE */
    function openSoldModal(id) {
      currentActionId = id;
      document.getElementById('sold-modal').classList.add('show');
      document.querySelectorAll('.more-dropdown').forEach(d => d.classList.remove('show'));
    }
    function confirmSold() {
      const id = currentActionId;
      const fd = new FormData();
      fd.append('action', 'mark_sold');
      fd.append('idAnnonce', id);

      fetch('action_annonce.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
          if (json.success) {
            closeModal('sold-modal');
            showToast('Annonce marquée comme vendue');
            setTimeout(() => location.reload(), 800);
          } else {
            showToast('Erreur');
          }
        });
    }

    function closeModal(id) {
      document.getElementById(id).classList.remove('show');
    }

    /* FERMETURE AU CLIC EXTÉRIEUR */
    document.querySelectorAll('.modal-overlay').forEach(m => {
      m.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('show');
      });
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('show'));
        document.querySelectorAll('.more-dropdown').forEach(d => d.classList.remove('show'));
      }
    });

    /* PARTAGE */
    function shareAnnonce(id) {
      const url = window.location.origin + '/automarket/fiche_annonce.php?id=' + id;
      if (navigator.share) {
        navigator.share({ title: 'Annonce AUTOMARKET', url: url });
      } else {
        navigator.clipboard.writeText(url);
        showToast('Lien copié dans le presse-papier');
      }
      document.querySelectorAll('.more-dropdown').forEach(d => d.classList.remove('show'));
    }
  </script>
</body>
</html>
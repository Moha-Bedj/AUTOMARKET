<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['idUtilisateur'])) {
    header("Location: inscription.php");
    exit();
}

$idUtilisateur = $_SESSION['idUtilisateur'];

$sql = "
    SELECT 
        f.idFav,
        a.idAnnonce,
        a.titre,
        a.prix,
        a.localisation,
        a.datePublication,
        a.vendeurVerif,
        v.annee,
        v.kilometrage,
        v.carburant,
        v.transmission,
        u.nom AS vendeur_nom,
        u.prenom AS vendeur_prenom,
        (
            SELECT p.urlPhoto
            FROM Photos p
            WHERE p.idAnnonce = a.idAnnonce
            ORDER BY p.ordrePhoto ASC, p.idPhoto ASC
            LIMIT 1
        ) AS photo
    FROM Favoris f
    INNER JOIN Annonce a ON f.idAnnonce = a.idAnnonce
    INNER JOIN Vehicule v ON a.idVehicule = v.idVehicule
    INNER JOIN Utilisateur u ON a.idVendeur = u.idUtilisateur
    WHERE f.idUtilisateur = ?
    ORDER BY f.dateAjout DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $idUtilisateur);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$totalFavoris = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mes favoris — AUTOMARKET</title>
  <link rel="icon" href="images/logo.png">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue:       #185FA5;
      --blue-dk:    #0C447C;
      --blue-bg:    #E6F1FB;
      --blue-bd:    #B5D4F4;
      --bg0:        #ffffff;
      --bg1:        #f5f4f0;
      --bg2:        #eceae4;
      --t1:         #1a1a18;
      --t2:         #5f5e5a;
      --t3:         #888780;
      --bd:         rgba(0,0,0,0.11);
      --bd2:        rgba(0,0,0,0.22);
      --green:      #639922;
      --green-bg:   #EAF3DE;
      --green-dk:   #27500A;
      --green-bd:   #C0DD97;
      --red:        #E24B4A;
      --red-bg:     #FCEBEB;
      --amber:      #BA7517;
      --amber-bg:   #FAEEDA;
      --amber-bd:   #FAC775;
      --r6:  6px;
      --r8:  8px;
      --r10: 10px;
      --r12: 12px;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      font-size: 14px;
      background: var(--bg1);
      color: var(--t1);
      min-height: 100vh;
    }

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

    .logo {
      font-size: 17px;
      font-weight: 500;
      color: var(--blue);
      letter-spacing: -0.4px;
      display: flex;
      align-items: center;
      gap: 5px;
      white-space: nowrap;
      cursor: pointer;
      text-decoration: none;
    }

    .nav-search {
      flex: 1;
      max-width: 340px;
      position: relative;
    }

    .nav-search input {
      width: 100%;
      height: 34px;
      border: 0.5px solid var(--bd2);
      border-radius: 20px;
      padding: 0 12px 0 34px;
      font-size: 13px;
      background: var(--bg1);
      color: var(--t1);
      outline: none;
      font-family: inherit;
    }

    .nav-search-icon {
      position: absolute;
      left: 10px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--t3);
      pointer-events: none;
    }

    .nav-links {
      display: flex;
      gap: 4px;
      align-items: center;
      margin-left: auto;
    }

    .nav-fav {
      position: relative;
      cursor: pointer;
      color: var(--blue);
      padding: 6px;
      display: flex;
      align-items: center;
      border-radius: var(--r6);
      transition: color .15s;
      text-decoration: none;
    }

    .nav-fav:hover { color: var(--blue-dk); }

    .user-menu {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 4px 10px 4px 4px;
      border-radius: 20px;
      cursor: pointer;
      transition: background .15s;
    }

    .user-menu:hover { background: var(--bg1); }

    .user-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      object-fit: cover;
      border: 0.5px solid var(--bd);
    }

    .user-avatar-initial {
      background: var(--blue);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      font-weight: 500;
      border: none;
    }

    .user-name {
      font-size: 13px;
      font-weight: 500;
    }

    .dropdown {
      position: absolute;
      right: 20px;
      top: 48px;
      background: #fff;
      border: 0.5px solid var(--bd);
      border-radius: 8px;
      padding: 6px 0;
      min-width: 160px;
      z-index: 200;
    }

    .dropdown-item {
      display: block;
      padding: 8px 14px;
      font-size: 13px;
      color: var(--t1);
      text-decoration: none;
      transition: background .15s;
    }

    .dropdown-item:hover { background: var(--bg1); }

    .page-wrap {
      max-width: 1100px;
      margin: 0 auto;
      padding: 24px 16px 48px;
    }

    .page-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .page-title {
      font-size: 24px;
      font-weight: 500;
      color: var(--t1);
    }

    .page-sub {
      font-size: 13px;
      color: var(--t2);
      margin-top: 4px;
    }

    .back-link {
      text-decoration: none;
      color: var(--blue);
      font-size: 13px;
      font-weight: 500;
    }

    .back-link:hover { text-decoration: underline; }

    .empty-box {
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r10);
      padding: 40px 24px;
      text-align: center;
    }

    .empty-box h2 {
      font-size: 18px;
      font-weight: 500;
      margin-bottom: 8px;
    }

    .empty-box p {
      font-size: 13px;
      color: var(--t2);
      margin-bottom: 18px;
    }

    .btn-fill {
      background: var(--blue);
      color: #fff;
      border: none;
      border-radius: var(--r6);
      padding: 10px 16px;
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
    }

    .btn-fill:hover { background: var(--blue-dk); }

    .listings {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .lcard {
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r10);
      display: flex;
      overflow: hidden;
      transition: border-color .15s, box-shadow .15s;
    }

    .lcard:hover {
      border-color: var(--blue);
      box-shadow: 0 0 0 2px rgba(24,95,165,.08);
    }

    .lcard-img {
      width: 220px;
      flex-shrink: 0;
      background: var(--bg1);
      position: relative;
      height: 160px;
      overflow: hidden;
    }

    .lcard-img img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .lcard-img-ph {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--t3);
    }

    .fav-top-btn {
      position: absolute;
      top: 8px;
      right: 8px;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: rgba(255,255,255,.95);
      display: flex;
      align-items: center;
      justify-content: center;
      border: 0.5px solid rgba(0,0,0,.1);
      cursor: pointer;
      text-decoration: none;
      color: var(--red);
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
      justify-content: space-between;
      gap: 10px;
      align-items: flex-start;
      margin-bottom: 8px;
    }

    .lcard-title {
      font-size: 16px;
      font-weight: 500;
      color: var(--t1);
      text-decoration: none;
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
      align-items: center;
    }

    .lspec {
      font-size: 12px;
      color: var(--t2);
    }

    .lspec-dot {
      width: 3px;
      height: 3px;
      border-radius: 50%;
      background: var(--t3);
      flex-shrink: 0;
    }

    .lcard-tags {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      margin-bottom: 10px;
    }

    .ltag {
      font-size: 11px;
      padding: 2px 8px;
      border-radius: 20px;
      border: 0.5px solid;
    }

    .ltag-blue {
      background: var(--blue-bg);
      color: var(--blue-dk);
      border-color: var(--blue-bd);
    }

    .ltag-green {
      background: var(--green-bg);
      color: var(--green-dk);
      border-color: var(--green-bd);
    }

    .lcard-foot {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: auto;
      padding-top: 10px;
      border-top: 0.5px solid var(--bd);
      gap: 10px;
      flex-wrap: wrap;
    }

    .lseller {
      font-size: 12px;
      color: var(--t2);
      display: flex;
      align-items: center;
      gap: 5px;
      flex-wrap: wrap;
    }

    .seller-badge {
      font-size: 10px;
      background: var(--blue-bg);
      color: var(--blue);
      padding: 1px 6px;
      border-radius: 10px;
      border: 0.5px solid var(--blue-bd);
    }

    .ldate {
      font-size: 11px;
      color: var(--t3);
    }

    .remove-link {
      color: var(--red);
      text-decoration: none;
      font-size: 12px;
      font-weight: 500;
    }

    .remove-link:hover { text-decoration: underline; }

    .footer {
      background: var(--bg0);
      border-top: 0.5px solid var(--bd);
      padding: 24px 20px;
      text-align: center;
      font-size: 12px;
      color: var(--t3);
      margin-top: 40px;
    }

    .footer a {
      color: var(--blue);
      text-decoration: none;
    }

    .footer a:hover { text-decoration: underline; }

    @media (max-width: 700px) {
      #logo-id { display: none; }
      .nav-search { display: none; }
      .lcard { flex-direction: column; }
      .lcard-img {
        width: 100%;
        height: 200px;
      }
      .page-title {
        font-size: 20px;
      }
    }
  </style>
</head>
<body>

  <nav class="nav">
    <a class="logo" href="index.php">
      <img src="images/logo.png" alt="AUTOMARKET" style="height:34px;width:auto;display:block;">
      <img src="images/id.png" alt="" style="height:34px;width:auto;display:block;" id="logo-id">
    </a>

   

    <div class="nav-links">
     

      <div class="user-menu" onclick="toggleMenu()">
        <?php if (!empty($_SESSION['photo'])): ?>
          <img src="<?= htmlspecialchars($_SESSION['photo']) ?>"
               class="user-avatar"
               alt=""
               referrerpolicy="no-referrer"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
          <div class="user-avatar user-avatar-initial" style="display:none">
            <?= strtoupper(substr($_SESSION['prenom'] ?? 'U', 0, 1)) ?>
          </div>
        <?php else: ?>
          <div class="user-avatar user-avatar-initial">
            <?= strtoupper(substr($_SESSION['prenom'] ?? 'U', 0, 1)) ?>
          </div>
        <?php endif; ?>
        <span class="user-name"><?= htmlspecialchars($_SESSION['prenom'] ?? 'Utilisateur') ?></span>
      </div>

      <div id="user-dropdown" class="dropdown" style="display:none">
        <a href="monprofil.php" class="dropdown-item">Mon profil</a>
        <a href="mes_annonces.php" class="dropdown-item">Mes annonces</a>
        <a href="favoris.php" class="dropdown-item">Mes favoris</a>
        <hr style="border:none;border-top:0.5px solid var(--bd);margin:4px 0">
        <a href="deconnexion.php" class="dropdown-item" style="color:var(--red)">Se déconnecter</a>
      </div>
    </div>
  </nav>

  <div class="page-wrap">
    <div class="page-head">
      <div>
        <h1 class="page-title">Mes favoris</h1>
        <div class="page-sub"><?= $totalFavoris ?> annonce<?= $totalFavoris > 1 ? 's' : '' ?> enregistrée<?= $totalFavoris > 1 ? 's' : '' ?></div>
      </div>
      <a href="index.php" class="back-link">← Retour à l’accueil</a>
    </div>

    <?php if ($totalFavoris == 0): ?>
      <div class="empty-box">
        <h2>Aucune annonce en favoris</h2>
        <p>Ajoutez des annonces à vos favoris pour les retrouver rapidement ici.</p>
        <a href="index.php" class="btn-fill">Découvrir les annonces</a>
      </div>
    <?php else: ?>
      <div class="listings">
        <?php while ($a = mysqli_fetch_assoc($result)): ?>
          <?php
            $titre = htmlspecialchars($a['titre']);
            $prix = number_format($a['prix'], 0, ',', ' ');
            $loc = htmlspecialchars($a['localisation']);
            $nom = htmlspecialchars(trim(($a['vendeur_nom'] ?? '') . ' ' . ($a['vendeur_prenom'] ?? '')));
            $km = number_format($a['kilometrage'], 0, ',', ' ');
            $annee = htmlspecialchars($a['annee']);
            $carbu = htmlspecialchars($a['carburant']);
            $trans = htmlspecialchars($a['transmission']);
            $photo = !empty($a['photo']) ? htmlspecialchars($a['photo']) : '';
            $pro = !empty($a['vendeurVerif']);

            $date = new DateTime($a['datePublication']);
            $diff = (new DateTime())->diff($date)->days;
            if ($diff == 0) {
                $dl = "Aujourd'hui";
            } elseif ($diff == 1) {
                $dl = "Hier";
            } else {
                $dl = "Il y a $diff jours";
            }
          ?>
          <div class="lcard">
            <div class="lcard-img">
              <?php if ($photo): ?>
                <img src="<?= $photo ?>" alt="<?= $titre ?>">
              <?php else: ?>
                <div class="lcard-img-ph">
                  <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.8">
                    <rect x="1" y="6" width="22" height="13" rx="3"/>
                    <circle cx="7" cy="16" r="1.5"/><circle cx="17" cy="16" r="1.5"/>
                  </svg>
                </div>
              <?php endif; ?>

              <a href="retirer_favori.php?id=<?= urlencode($a['idAnnonce']) ?>" class="fav-top-btn" title="Retirer des favoris">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M11.29 20.66c.2.2.45.29.71.29s.51-.1.71-.29l7.5-7.5c2.35-2.35 2.35-6.05 0-8.41-2.3-2.28-5.85-2.35-8.21-.2-2.36-2.15-5.91-2.09-8.21.2-2.35 2.36-2.35 6.06 0 8.41z"></path>
                </svg>
              </a>
            </div>

            <div class="lcard-body">
              <div class="lcard-top">
                <a href="fiche_annonce.php?id=<?= urlencode($a['idAnnonce']) ?>" class="lcard-title"><?= $titre ?></a>
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

              <div class="lcard-tags">
                <span class="ltag ltag-blue">Favori</span>
                <?php if ($pro): ?>
                  <span class="ltag ltag-green">Vendeur vérifié</span>
                <?php endif; ?>
              </div>

              <div class="lcard-foot">
                <div class="lseller">
                  <?= $nom ?>
                  <?php if ($pro): ?>
                    <span class="seller-badge">Pro vérifié</span>
                  <?php endif; ?>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                  <div class="ldate"><?= $dl ?></div>
                  <a href="retirer_favori.php?id=<?= urlencode($a['idAnnonce']) ?>" class="remove-link">Retirer</a>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </div>

  <footer class="footer">
    © 2025 AUTOMARKET — Marketplace automobile algérienne &nbsp;·&nbsp;
    <a href="#">Aide</a> &nbsp;·&nbsp;
    <a href="#">Confidentialité</a> &nbsp;·&nbsp;
    <a href="#">Conditions d'utilisation</a>
  </footer>

  <script>
    function toggleMenu() {
      const d = document.getElementById('user-dropdown');
      d.style.display = d.style.display === 'none' ? 'block' : 'none';
    }

    document.addEventListener('click', function(e) {
      if (!e.target.closest('.user-menu') && !e.target.closest('#user-dropdown')) {
        const d = document.getElementById('user-dropdown');
        if (d) d.style.display = 'none';
      }
    });
  </script>
</body>
</html>
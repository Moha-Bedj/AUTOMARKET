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
        idUtilisateur,
        nom,
        prenom,
        email,
        numTel,
        statut,
        role,
        dateInscription,
        emailVerifie,
        badgeVerifie
    FROM Utilisateur
    WHERE idUtilisateur = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $idUtilisateur);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    die("Utilisateur introuvable.");
}

$user = mysqli_fetch_assoc($result);

$prenom = htmlspecialchars($user['prenom'] ?? '');
$nom = htmlspecialchars($user['nom'] ?? '');
$email = htmlspecialchars($user['email'] ?? '');
$tel = htmlspecialchars($user['numTel'] ?? '');
$statut = htmlspecialchars($user['statut'] ?? '');
$role = htmlspecialchars($user['role'] ?? '');
$dateInscription = !empty($user['dateInscription']) ? date('d/m/Y', strtotime($user['dateInscription'])) : '-';
$emailVerifie = !empty($user['emailVerifie']);
$badgeVerifie = !empty($user['badgeVerifie']);
$initiale = strtoupper(substr($user['prenom'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mon profil — AUTOMARKET</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue:       #185FA5;
      --blue-dk:    #0C447C;
      --blue-bg:    #E6F1FB;
      --blue-bd:    #B5D4F4;
      --bg0:        #ffffff;
      --bg1:        #f5f4f0;
      --t1:         #1a1a18;
      --t2:         #5f5e5a;
      --t3:         #888780;
      --bd:         rgba(0,0,0,0.11);
      --bd2:        rgba(0,0,0,0.22);
      --green:      #639922;
      --green-bg:   #EAF3DE;
      --green-bd:   #C0DD97;
      --green-dk:   #27500A;
      --red:        #E24B4A;
      --red-bg:     #FCEBEB;
      --red-bd:     #F7C1C1;
      --r6: 6px;
      --r8: 8px;
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
      color: var(--t2);
      padding: 6px;
      display: flex;
      align-items: center;
      border-radius: var(--r6);
      transition: color .15s;
      text-decoration: none;
    }

    .nav-fav:hover { color: var(--blue); }

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
    }

    .page-sub {
      font-size: 13px;
      color: var(--t2);
      margin-top: 4px;
    }

    .back-link {
      color: var(--blue);
      text-decoration: none;
      font-size: 13px;
      font-weight: 500;
    }

    .back-link:hover { text-decoration: underline; }

    .profile-layout {
      display: grid;
      grid-template-columns: 300px 1fr;
      gap: 20px;
    }

    .card {
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r10);
      overflow: hidden;
    }

    .profile-card {
      padding: 20px;
      text-align: center;
    }

    .profile-avatar {
      width: 88px;
      height: 88px;
      border-radius: 50%;
      background: var(--blue);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 30px;
      font-weight: 500;
      margin: 0 auto 14px;
    }

    .profile-name {
      font-size: 20px;
      font-weight: 500;
      margin-bottom: 4px;
    }

    .profile-role {
      font-size: 13px;
      color: var(--t2);
      margin-bottom: 14px;
    }

    .badge-row {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      justify-content: center;
    }

    .badge {
      font-size: 11px;
      padding: 4px 10px;
      border-radius: 20px;
      border: 0.5px solid;
    }

    .badge-ok {
      background: var(--green-bg);
      color: var(--green-dk);
      border-color: var(--green-bd);
    }

    .badge-no {
      background: var(--red-bg);
      color: #791F1F;
      border-color: var(--red-bd);
    }

    .info-card {
      padding: 0;
    }

    .info-head {
      padding: 16px 18px;
      border-bottom: 0.5px solid var(--bd);
      font-size: 15px;
      font-weight: 500;
    }

    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
    }

    .info-item {
      padding: 16px 18px;
      border-bottom: 0.5px solid var(--bd);
    }

    .info-item:nth-child(odd) {
      border-right: 0.5px solid var(--bd);
    }

    .info-label {
      font-size: 12px;
      color: var(--t3);
      margin-bottom: 6px;
    }

    .info-value {
      font-size: 14px;
      color: var(--t1);
      font-weight: 500;
      word-break: break-word;
    }

    .actions-card {
      margin-top: 20px;
      padding: 16px 18px;
    }

    .actions-title {
      font-size: 15px;
      font-weight: 500;
      margin-bottom: 12px;
    }

    .actions-row {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .btn {
      height: 38px;
      padding: 0 14px;
      border-radius: var(--r8);
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-family: inherit;
    }

    .btn-fill {
      background: var(--blue);
      color: #fff;
      border: none;
    }

    .btn-fill:hover { background: var(--blue-dk); }

    .btn-outline {
      background: transparent;
      color: var(--blue);
      border: 0.5px solid var(--blue);
    }

    .btn-outline:hover {
      background: var(--blue-bg);
    }

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

    @media (max-width: 800px) {
      #logo-id { display: none; }
      .nav-search { display: none; }
      .profile-layout {
        grid-template-columns: 1fr;
      }
      .info-grid {
        grid-template-columns: 1fr;
      }
      .info-item:nth-child(odd) {
        border-right: none;
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

   

   

      <div id="user-dropdown" class="dropdown" style="display:none">
        <a href="monprofil.php" class="dropdown-item">Mon profil</a>
        <a href="mesannonces.php" class="dropdown-item">Mes annonces</a>
        <a href="favoris.php" class="dropdown-item">Mes favoris</a>
        <hr style="border:none;border-top:0.5px solid var(--bd);margin:4px 0">
        <button class="btn btn-fill" onclick="openLogoutModal()">Se déconnecter</button>
    
      </div>
    </div>
  </nav>

  <div class="page-wrap">
    <div class="page-head">
      <div>
        <h1 class="page-title">Mon profil</h1>
        <div class="page-sub">Consultez vos informations personnelles</div>
      </div>
      <a href="index.php" class="back-link">← Retour à l’accueil</a>
    </div>

    <div class="profile-layout">
      <div>
        <div class="card profile-card">
          <div class="profile-avatar"><?= $initiale ?></div>
          <div class="profile-name"><?= $prenom . ' ' . $nom ?></div>
          <div class="profile-role"><?= ucfirst($role ?: 'Utilisateur') ?></div>

          <div class="badge-row">
            <span class="badge <?= $emailVerifie ? 'badge-ok' : 'badge-no' ?>">
              <?= $emailVerifie ? 'Email vérifié' : 'Email non vérifié' ?>
            </span>
            <span class="badge <?= $badgeVerifie ? 'badge-ok' : 'badge-no' ?>">
              <?= $badgeVerifie ? 'Badge vérifié' : 'Badge non vérifié' ?>
            </span>
          </div>
        </div>
      </div>

      <div>
        <div class="card info-card">
          <div class="info-head">Informations du compte</div>
          <div class="info-grid">
            <div class="info-item">
              <div class="info-label">Prénom</div>
              <div class="info-value"><?= $prenom ?: '-' ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Nom</div>
              <div class="info-value"><?= $nom ?: '-' ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Adresse e-mail</div>
              <div class="info-value"><?= $email ?: '-' ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Téléphone</div>
              <div class="info-value"><?= $tel ?: '-' ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Rôle</div>
              <div class="info-value"><?= $role ?: '-' ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Statut</div>
              <div class="info-value"><?= $statut ?: '-' ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Inscrit depuis</div>
              <div class="info-value"><?= $dateInscription ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Identifiant</div>
              <div class="info-value"><?= htmlspecialchars($user['idUtilisateur']) ?></div>
            </div>
          </div>
        </div>

        <div class="card actions-card">
          <div class="actions-title">Actions rapides</div>
          <div class="actions-row">
            <a href="favoris.php" class="btn btn-outline">Mes favoris</a>
            <a href="mesannonces.php" class="btn btn-outline">Mes annonces</a>
            <a href="deconnexion.php" class="btn btn-fill">Se déconnecter</a>
          </div>
        </div>
      </div>
    </div>
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
    function openLogoutModal() {
  document.getElementById('logout-modal').style.display = 'flex';
}

function closeLogoutModal() {
  document.getElementById('logout-modal').style.display = 'none';
}

function logout() {
  window.location.href = 'deconnexion.php';
}

    document.addEventListener('click', function(e) {
      if (!e.target.closest('.user-menu') && !e.target.closest('#user-dropdown')) {
        const d = document.getElementById('user-dropdown');
        if (d) d.style.display = 'none';
      }
    });
  </script>
  <div id="logout-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); align-items:center; justify-content:center; z-index:999;">
  <div style="background:#fff; padding:20px 24px; border-radius:10px; width:300px; text-align:center;">
    
    <h3 style="margin-bottom:10px;">Déconnexion</h3>
    <p style="font-size:13px; color:#555; margin-bottom:20px;">
      Êtes-vous sûr de vouloir vous déconnecter ?
    </p>

    <div style="display:flex; gap:10px; justify-content:center;">
      <button onclick="logout()" style="padding:8px 14px; background:#185FA5; color:#fff; border:none; border-radius:6px; cursor:pointer;">
        Oui
      </button>

      <button onclick="closeLogoutModal()" style="padding:8px 14px; background:#eee; border:none; border-radius:6px; cursor:pointer;">
        Non
      </button>
    </div>

  </div>
</div>
</body>
</html>
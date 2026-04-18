<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['idUtilisateur'])) {
    header('Location: inscription.php');
    exit;
}

$id = mysqli_real_escape_string($conn, $_SESSION['idUtilisateur']);
$msg = '';
$msgType = '';

/* ── MISE À JOUR DU PROFIL ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_profil') {
        $nom    = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $tel    = trim($_POST['tel'] ?? '');
        $wilaya = trim($_POST['wilaya'] ?? '');

        if (!$nom || !$prenom) {
            $msg = 'Nom et prénom sont obligatoires';
            $msgType = 'error';
        } elseif ($tel && !preg_match('/^[567][0-9]{8}$/', $tel)) {
            $msg = 'Numéro de téléphone invalide (ex: 5XXXXXXXX)';
            $msgType = 'error';
        } else {
            $n = mysqli_real_escape_string($conn, $nom);
            $p = mysqli_real_escape_string($conn, $prenom);
            $t = mysqli_real_escape_string($conn, $tel);
            $w = mysqli_real_escape_string($conn, $wilaya);

            $ok = mysqli_query($conn, "UPDATE Utilisateur 
                SET nom='$n', prenom='$p', numTel='$t', wilaya='$w' 
                WHERE idUtilisateur='$id'");

            if ($ok) {
                $_SESSION['nom']    = $nom;
                $_SESSION['prenom'] = $prenom;
                $msg = 'Profil mis à jour avec succès';
                $msgType = 'success';
            } else {
                $msg = 'Erreur lors de la mise à jour';
                $msgType = 'error';
            }
        }
    }

    /* ── CHANGEMENT DE MOT DE PASSE ── */
    if ($_POST['action'] === 'change_pass') {
        $oldPass = $_POST['old_pass'] ?? '';
        $newPass = $_POST['new_pass'] ?? '';
        $newPass2 = $_POST['new_pass2'] ?? '';

        $res = mysqli_query($conn, "SELECT motDePasse FROM Utilisateur WHERE idUtilisateur='$id'");
        $user = mysqli_fetch_assoc($res);

        if (!password_verify($oldPass, $user['motDePasse'])) {
            $msg = 'Mot de passe actuel incorrect';
            $msgType = 'error';
        } elseif (strlen($newPass) < 8) {
            $msg = 'Le nouveau mot de passe doit contenir au moins 8 caractères';
            $msgType = 'error';
        } elseif ($newPass !== $newPass2) {
            $msg = 'Les nouveaux mots de passe ne correspondent pas';
            $msgType = 'error';
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE Utilisateur SET motDePasse='$hash' WHERE idUtilisateur='$id'");
            $msg = 'Mot de passe modifié avec succès';
            $msgType = 'success';
        }
    }

    /* ── SUPPRESSION DU COMPTE ── */
    if ($_POST['action'] === 'delete_account') {
        $pass = $_POST['confirm_pass'] ?? '';
        $res = mysqli_query($conn, "SELECT motDePasse FROM Utilisateur WHERE idUtilisateur='$id'");
        $user = mysqli_fetch_assoc($res);

        if (password_verify($pass, $user['motDePasse'])) {
            mysqli_query($conn, "DELETE FROM Utilisateur WHERE idUtilisateur='$id'");
            session_destroy();
            header('Location: index.php');
            exit;
        } else {
            $msg = 'Mot de passe incorrect — compte non supprimé';
            $msgType = 'error';
        }
    }
}

/* ── RÉCUPÉRER LES DONNÉES UTILISATEUR ── */
$res  = mysqli_query($conn, "SELECT * FROM Utilisateur WHERE idUtilisateur='$id'");
$user = mysqli_fetch_assoc($res);

/* ── STATISTIQUES ── */
$stats = ['annonces' => 0, 'actives' => 0, 'favoris' => 0];

$r1 = mysqli_query($conn, "SELECT COUNT(*) AS n FROM Annonce WHERE idVendeur='$id'");
if ($r1) $stats['annonces'] = mysqli_fetch_assoc($r1)['n'];

$r2 = mysqli_query($conn, "SELECT COUNT(*) AS n FROM Annonce WHERE idVendeur='$id' AND statutAnnonce='active'");
if ($r2) $stats['actives'] = mysqli_fetch_assoc($r2)['n'];

$r3 = mysqli_query($conn, "SELECT COUNT(*) AS n FROM Favoris WHERE idUtilisateur='$id'");
if ($r3) $stats['favoris'] = mysqli_fetch_assoc($r3)['n'];
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
      --blue: #185FA5; --blue-dk: #0C447C; --blue-bg: #E6F1FB; --blue-bd: #B5D4F4;
      --bg0: #ffffff; --bg1: #f5f4f0; --bg2: #eceae4;
      --t1: #1a1a18; --t2: #5f5e5a; --t3: #888780;
      --bd: rgba(0,0,0,0.11); --bd2: rgba(0,0,0,0.22);
      --green: #639922; --green-bg: #EAF3DE; --green-dk: #27500A; --green-bd: #C0DD97;
      --red: #E24B4A; --red-bg: #FCEBEB; --red-dk: #791F1F;
      --amber: #BA7517; --amber-bg: #FAEEDA; --amber-bd: #FAC775;
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
      max-width: 780px;
      margin: 0 auto;
      padding: 24px 16px 48px;
    }

    .page-title {
      font-size: 22px;
      font-weight: 500;
      margin-bottom: 24px;
    }

    /* HEADER PROFIL */
    .profile-card {
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r12);
      padding: 24px;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .profile-avatar-wrap { position: relative; flex-shrink: 0; }
    .profile-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--bd);
    }
    .profile-avatar-initial {
      background: var(--blue);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
      font-weight: 500;
      border: none;
    }
    .profile-info { flex: 1; min-width: 0; }
    .profile-info h2 {
      font-size: 20px;
      font-weight: 500;
      margin-bottom: 4px;
    }
    .profile-email {
      font-size: 13px;
      color: var(--t2);
      margin-bottom: 10px;
      word-break: break-all;
    }
    .profile-badges { display: flex; gap: 6px; flex-wrap: wrap; }
    .badge {
      font-size: 11px;
      padding: 3px 10px;
      border-radius: 20px;
      border: 0.5px solid;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .badge-green {
      background: var(--green-bg);
      color: var(--green-dk);
      border-color: var(--green-bd);
    }
    .badge-blue {
      background: var(--blue-bg);
      color: var(--blue-dk);
      border-color: var(--blue-bd);
    }
    .badge-amber {
      background: var(--amber-bg);
      color: #633806;
      border-color: var(--amber-bd);
    }

    /* STATS */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
      margin-bottom: 16px;
    }
    .stat-card {
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r10);
      padding: 18px 14px;
      text-align: center;
      transition: all .15s;
    }
    .stat-card.clickable { cursor: pointer; }
    .stat-card.clickable:hover {
      border-color: var(--blue);
      transform: translateY(-1px);
    }
    .stat-number {
      font-size: 28px;
      font-weight: 500;
      color: var(--blue);
      margin-bottom: 2px;
    }
    .stat-label {
      font-size: 12px;
      color: var(--t2);
    }

    /* FORM CARD */
    .card {
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r12);
      padding: 24px;
      margin-bottom: 16px;
    }
    .card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 18px;
      padding-bottom: 12px;
      border-bottom: 0.5px solid var(--bd);
    }
    .card-title {
      font-size: 15px;
      font-weight: 500;
    }
    .card-sub {
      font-size: 12px;
      color: var(--t2);
      margin-top: 2px;
    }

    .row2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-bottom: 12px;
    }
    .field { margin-bottom: 12px; }
    .label {
      font-size: 12px;
      color: var(--t2);
      margin-bottom: 5px;
      display: block;
      font-weight: 500;
    }
    .field-hint {
      font-size: 11px;
      color: var(--t3);
      margin-top: 4px;
    }
    input, select {
      width: 100%;
      height: 40px;
      border: 0.5px solid var(--bd2);
      border-radius: var(--r8);
      padding: 0 12px;
      font-size: 14px;
      background: var(--bg0);
      color: var(--t1);
      outline: none;
      font-family: inherit;
      appearance: none;
      -webkit-appearance: none;
      transition: border-color .15s, box-shadow .15s;
    }
    select {
      background-image: url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23888' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
      padding-right: 28px;
      cursor: pointer;
    }
    input:focus, select:focus {
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(24,95,165,0.1);
    }
    input:disabled {
      background: var(--bg1);
      color: var(--t3);
      cursor: not-allowed;
    }

    .tel-row { display: flex; gap: 8px; }
    .tel-prefix {
      display: flex;
      align-items: center;
      padding: 0 12px;
      border: 0.5px solid var(--bd2);
      border-radius: var(--r8);
      background: var(--bg1);
      font-size: 14px;
      color: var(--t2);
      font-weight: 500;
    }
    .tel-row input { flex: 1; }

    .pass-wrap { position: relative; }
    .pass-wrap input { padding-right: 40px; }
    .eye-btn {
      position: absolute;
      right: 11px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: var(--t3);
      background: none;
      border: none;
      padding: 0;
      display: flex;
      align-items: center;
    }

    .btn-primary {
      background: var(--blue);
      color: #fff;
      border: none;
      border-radius: var(--r8);
      padding: 10px 22px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      font-family: inherit;
      transition: background .15s;
    }
    .btn-primary:hover { background: var(--blue-dk); }

    .btn-danger {
      background: var(--red);
      color: #fff;
      border: none;
      border-radius: var(--r8);
      padding: 10px 22px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      font-family: inherit;
      transition: all .15s;
    }
    .btn-danger:hover { background: #c53030; }

    .btn-ghost {
      background: transparent;
      color: var(--t2);
      border: 0.5px solid var(--bd2);
      border-radius: var(--r8);
      padding: 10px 22px;
      font-size: 14px;
      cursor: pointer;
      font-family: inherit;
    }
    .btn-ghost:hover { background: var(--bg1); color: var(--t1); }

    .form-actions {
      display: flex;
      gap: 10px;
      margin-top: 8px;
    }

    /* ALERTS */
    .alert {
      padding: 12px 16px;
      border-radius: var(--r8);
      margin-bottom: 16px;
      font-size: 13px;
      display: flex;
      align-items: center;
      gap: 10px;
      animation: slideDown .3s ease;
    }
    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-10px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .alert-success {
      background: var(--green-bg);
      border: 0.5px solid var(--green-bd);
      color: var(--green-dk);
    }
    .alert-error {
      background: var(--red-bg);
      border: 0.5px solid rgba(226,75,74,.3);
      color: var(--red-dk);
    }
    .alert-icon {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 12px;
      font-weight: 600;
      flex-shrink: 0;
    }
    .alert-success .alert-icon { background: var(--green); }
    .alert-error .alert-icon { background: var(--red); }

    /* ZONE DE DANGER */
    .danger-zone {
      border-color: rgba(226,75,74,.3);
      background: #FFFBFB;
    }
    .danger-zone .card-title {
      color: var(--red-dk);
    }
    .danger-text {
      font-size: 13px;
      color: var(--t2);
      margin-bottom: 14px;
      line-height: 1.6;
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
      margin-bottom: 6px;
      color: var(--red-dk);
    }
    .modal-sub {
      font-size: 13px;
      color: var(--t2);
      margin-bottom: 16px;
      line-height: 1.5;
    }

    /* SECTION TITLES */
    .section-title {
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      color: var(--t3);
      letter-spacing: .5px;
      margin: 14px 0 10px;
    }

    @media (max-width: 600px) {
      .profile-card { flex-direction: column; text-align: center; }
      .stats-grid { grid-template-columns: 1fr 1fr 1fr; }
      .row2 { grid-template-columns: 1fr; }
      .form-actions { flex-direction: column; }
      .form-actions button { width: 100%; }
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

    <h1 class="page-title">Mon profil</h1>

    <?php if ($msg): ?>
      <div class="alert alert-<?= $msgType ?>">
        <div class="alert-icon"><?= $msgType === 'success' ? '✓' : '!' ?></div>
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <!-- HEADER PROFIL -->
    <div class="profile-card">
      <div class="profile-avatar-wrap">
        <?php if (!empty($_SESSION['photo'])): ?>
          <img src="<?= htmlspecialchars($_SESSION['photo']) ?>"
               class="profile-avatar" alt=""
               referrerpolicy="no-referrer"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
          <div class="profile-avatar profile-avatar-initial" style="display:none">
            <?= strtoupper(substr($user['prenom'], 0, 1)) ?>
          </div>
        <?php else: ?>
          <div class="profile-avatar profile-avatar-initial">
            <?= strtoupper(substr($user['prenom'], 0, 1)) ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="profile-info">
        <h2><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h2>
        <div class="profile-email"><?= htmlspecialchars($user['email']) ?></div>
        <div class="profile-badges">
          <?php if ($user['emailVerifie']): ?>
            <span class="badge badge-green">✓ E-mail vérifié</span>
          <?php else: ?>
            <span class="badge badge-amber">E-mail non vérifié</span>
          <?php endif; ?>

          <?php if ($user['badgeVerifie']): ?>
            <span class="badge badge-blue">
              <svg width="11" height="11" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
              </svg>
              Vendeur Pro
            </span>
          <?php endif; ?>

          <span class="badge badge-blue">
            Membre depuis <?= date('Y', strtotime($user['dateInscription'])) ?>
          </span>
        </div>
      </div>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
      <div class="stat-card clickable" onclick="location.href='mesannonces.php'">
        <div class="stat-number"><?= $stats['annonces'] ?></div>
        <div class="stat-label"><?= $stats['annonces'] > 1 ? 'Annonces publiées' : 'Annonce publiée' ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?= $stats['actives'] ?></div>
        <div class="stat-label"><?= $stats['actives'] > 1 ? 'Annonces actives' : 'Annonce active' ?></div>
      </div>
      <div class="stat-card clickable" onclick="location.href='favoris.php'">
        <div class="stat-number"><?= $stats['favoris'] ?></div>
        <div class="stat-label"><?= $stats['favoris'] > 1 ? 'Favoris' : 'Favori' ?></div>
      </div>
    </div>

    <!-- FORMULAIRE PROFIL -->
    <form method="POST" class="card">
      <input type="hidden" name="action" value="update_profil">

      <div class="card-header">
        <div>
          <div class="card-title">Informations personnelles</div>
          <div class="card-sub">Modifiez vos informations de contact</div>
        </div>
      </div>

      <div class="row2">
        <div>
          <label class="label">Prénom *</label>
          <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" required>
        </div>
        <div>
          <label class="label">Nom *</label>
          <input type="text" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required>
        </div>
      </div>

      <div class="field">
        <label class="label">Adresse e-mail</label>
        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
        <div class="field-hint">L'adresse e-mail ne peut pas être modifiée</div>
      </div>

      <div class="field">
        <label class="label">Téléphone</label>
        <div class="tel-row">
          <div class="tel-prefix">+213</div>
          <input type="tel" name="tel" placeholder="5XXXXXXXX" maxlength="9"
                 value="<?= htmlspecialchars($user['numTel'] ?? '') ?>"
                 oninput="validatePhone(this)">
        </div>
      </div>

      <div class="field">
        <label class="label">Wilaya</label>
        <select name="wilaya">
          <option value="">Sélectionner votre wilaya</option>
          <?php
          $wilayas = [
              'Adrar','Aïn Defla','Aïn Témouchent','Alger','Annaba','Batna','Béchar',
              'Béjaïa','Biskra','Blida','Bordj Bou Arreridj','Bouira','Boumerdès',
              'Chlef','Constantine','Djelfa','El Bayadh','El Oued','El Tarf',
              'Ghardaïa','Guelma','Illizi','Jijel','Khenchela','Laghouat','Mascara',
              'Médéa','Mila','Mostaganem','M\'Sila','Naâma','Oran','Ouargla',
              'Oum El Bouaghi','Relizane','Saïda','Sétif','Sidi Bel Abbès','Skikda',
              'Souk Ahras','Tamanrasset','Tébessa','Tiaret','Tindouf','Tipaza',
              'Tissemsilt','Tizi Ouzou','Tlemcen'
          ];
          foreach ($wilayas as $w):
              $selected = ($user['wilaya'] ?? '') === $w ? 'selected' : '';
          ?>
            <option value="<?= $w ?>" <?= $selected ?>><?= $w ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn-primary">Enregistrer les modifications</button>
      </div>
    </form>

    <!-- CHANGER MOT DE PASSE -->
    <form method="POST" class="card">
      <input type="hidden" name="action" value="change_pass">

      <div class="card-header">
        <div>
          <div class="card-title">Changer le mot de passe</div>
          <div class="card-sub">Pour sécuriser votre compte, utilisez un mot de passe fort</div>
        </div>
      </div>

      <div class="field">
        <label class="label">Mot de passe actuel *</label>
        <div class="pass-wrap">
          <input type="password" name="old_pass" id="old_pass" required>
          <button type="button" class="eye-btn" onclick="togglePass('old_pass', this)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>

      <div class="row2">
        <div>
          <label class="label">Nouveau mot de passe *</label>
          <div class="pass-wrap">
            <input type="password" name="new_pass" id="new_pass" minlength="8" required>
            <button type="button" class="eye-btn" onclick="togglePass('new_pass', this)">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>
        <div>
          <label class="label">Confirmer le mot de passe *</label>
          <div class="pass-wrap">
            <input type="password" name="new_pass2" id="new_pass2" minlength="8" required>
            <button type="button" class="eye-btn" onclick="togglePass('new_pass2', this)">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn-primary">Changer le mot de passe</button>
      </div>
    </form>

    <!-- ZONE DE DANGER -->
    <div class="card danger-zone">
      <div class="card-header">
        <div>
          <div class="card-title">Zone de danger</div>
          <div class="card-sub">Actions irréversibles</div>
        </div>
      </div>

      <div class="danger-text">
        La suppression de votre compte est <strong>définitive</strong>. Toutes vos annonces, 
        favoris et données personnelles seront supprimés et ne pourront pas être récupérés.
      </div>

      <button class="btn-danger" onclick="openDeleteModal()">
        Supprimer mon compte
      </button>
    </div>

  </div>

  <!-- MODAL SUPPRESSION -->
  <div class="modal-overlay" id="delete-modal">
    <div class="modal">
      <div class="modal-title">⚠ Supprimer votre compte ?</div>
      <div class="modal-sub">
        Cette action est <strong>irréversible</strong>. Entrez votre mot de passe pour confirmer la suppression définitive de votre compte.
      </div>

      <form method="POST">
        <input type="hidden" name="action" value="delete_account">

        <div class="field">
          <label class="label">Mot de passe *</label>
          <div class="pass-wrap">
            <input type="password" name="confirm_pass" id="confirm_pass" required autofocus>
            <button type="button" class="eye-btn" onclick="togglePass('confirm_pass', this)">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="form-actions">
          <button type="button" class="btn-ghost" onclick="closeDeleteModal()" style="flex:1">Annuler</button>
          <button type="submit" class="btn-danger" style="flex:1">Supprimer définitivement</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function togglePass(id, btn) {
      const input = document.getElementById(id);
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.style.color = show ? 'var(--blue)' : 'var(--t3)';
    }

    function validatePhone(input) {
      let value = input.value.replace(/[^0-9]/g, '');
      if (value.length > 0 && !['5','6','7'].includes(value[0])) {
        value = value.substring(1);
      }
      input.value = value.substring(0, 9);
    }

    function openDeleteModal() {
      document.getElementById('delete-modal').classList.add('show');
    }
    function closeDeleteModal() {
      document.getElementById('delete-modal').classList.remove('show');
    }

    document.getElementById('delete-modal').addEventListener('click', function(e) {
      if (e.target === this) closeDeleteModal();
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeDeleteModal();
    });
  </script>
</body>
</html>
<?php
require_once 'connexion.php';

/* ══ Traitement soumission ══ */
$success = false;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ── Récupérer les champs ── */
    $typeVehicule  = $_POST['typeVehicule']  ?? '';
    $idMarque      = $_POST['idMarque']      ?? '';
    $idModele      = $_POST['idModele']      ?? '';
    $annee         = $_POST['annee']         ?? '';
    $carburant     = $_POST['carburant']     ?? '';
    $transmission  = $_POST['transmission']  ?? '';
    $kilometrage   = $_POST['kilometrage']   ?? '';
    $puissance     = $_POST['puissance']     ?? '';
    $etat          = $_POST['etat']          ?? '';
    $titre         = trim($_POST['titre']    ?? '');
    $description   = trim($_POST['description'] ?? '');
    $wilaya        = $_POST['wilaya']        ?? '';
    $prix          = $_POST['prix']          ?? '';
    $negociable    = isset($_POST['negociable']) ? 1 : 0;
    $credit        = isset($_POST['credit'])    ? 1 : 0;
    $echange       = isset($_POST['echange'])   ? 1 : 0;
    $telephone     = trim($_POST['telephone']  ?? '');

    /* Équipements */
    $equipements = $_POST['equipements'] ?? [];

    /* ── Validation ── */
    if (!$typeVehicule) $errors[] = "Sélectionnez le type de véhicule.";
    if (!$idMarque)     $errors[] = "Sélectionnez la marque.";
    if (!$titre)        $errors[] = "Le titre est obligatoire.";
    if (!$prix || !is_numeric($prix)) $errors[] = "Entrez un prix valide.";
    if (!$telephone)    $errors[] = "Le numéro de téléphone est obligatoire.";

    if (empty($errors)) {

        /* ── Insérer Vehicule ── */
        $sql_veh = "INSERT INTO Vehicule (typeVehicule, idModele, annee, carburant, transmission, kilometrage, puissance, etatVehicule)
                    VALUES ('$typeVehicule', '$idModele', '$annee', '$carburant', '$transmission', '$kilometrage', '$puissance', '$etat')";
        mysqli_query($conn, $sql_veh);
        $idVehicule = mysqli_insert_id($conn);

        /* ── Insérer Annonce ── */
        $idVendeur = $_SESSION['idUtilisateur'] ?? 1; /* À remplacer par session réelle */
        $sql_ann = "INSERT INTO Annonce (titre, description, prix, localisation, idVehicule, idVendeur, statutAnnonce, vendeurVerif, datePublication)
                    VALUES ('".mysqli_real_escape_string($conn,$titre)."',
                            '".mysqli_real_escape_string($conn,$description)."',
                            '$prix',
                            '".mysqli_real_escape_string($conn,$wilaya)."',
                            '$idVehicule',
                            '$idVendeur',
                            'active',
                            0,
                            NOW())";
        mysqli_query($conn, $sql_ann);
        $idAnnonce = mysqli_insert_id($conn);

        /* ── Upload photos ── */
        if (!empty($_FILES['photos']['name'][0])) {
            $uploadDir = 'uploads/annonces/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            foreach ($_FILES['photos']['tmp_name'] as $i => $tmp) {
                if ($_FILES['photos']['error'][$i] === 0) {
                    $ext      = pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION);
                    $filename = uniqid('photo_') . '.' . $ext;
                    $dest     = $uploadDir . $filename;
                    if (move_uploaded_file($tmp, $dest)) {
                        $estPrincipale = ($i === 0) ? 1 : 0;
                        $sql_ph = "INSERT INTO Photos (idAnnonce, urlPhoto, estPrincipale)
                                   VALUES ('$idAnnonce', '$dest', $estPrincipale)";
                        mysqli_query($conn, $sql_ph);
                    }
                }
            }
        }

        $success = true;
    }
}

/* ══ Charger marques pour le select ══ */
$res_marques = mysqli_query($conn, "SELECT * FROM Marque ORDER BY nom ASC");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Publier une annonce — AUTOMARKET</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue:    #185FA5;
      --blue-dk: #0C447C;
      --blue-bg: #E6F1FB;
      --blue-bd: #B5D4F4;
      --bg0:     #ffffff;
      --bg1:     #f5f4f0;
      --t1:      #1a1a18;
      --t2:      #5f5e5a;
      --t3:      #888780;
      --bd:      rgba(0,0,0,0.11);
      --bd2:     rgba(0,0,0,0.22);
      --green:   #639922;
      --green-bg:#EAF3DE;
      --red:     #E24B4A;
      --red-bg:  #FCEBEB;
      --r6: 6px; --r8: 8px; --r10: 10px;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      font-size: 14px;
      background: var(--bg1);
      color: var(--t1);
      min-height: 100vh;
    }

    /* ── Navbar ── */
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
      text-decoration: none;
      cursor: pointer;
    }
    .logo-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--blue); }
    .nav-back {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 13px;
      color: var(--t2);
      cursor: pointer;
      padding: 5px 10px;
      border-radius: var(--r6);
      text-decoration: none;
      transition: background .15s;
      margin-left: auto;
    }
    .nav-back:hover { background: var(--bg1); }

    /* ── Layout ── */
    .page-wrap {
      max-width: 760px;
      margin: 0 auto;
      padding: 24px 16px 60px;
    }

    /* ── Top bar ── */
    .top-bar {
      background: var(--blue);
      padding: 16px 20px;
      border-radius: var(--r10) var(--r10) 0 0;
    }
    .top-bar-title { color: #fff; font-size: 16px; font-weight: 500; margin-bottom: 2px; }
    .top-bar-sub   { color: rgba(255,255,255,.7); font-size: 12px; }

    /* ── Progress bar ── */
    .progress {
      height: 4px;
      background: rgba(255,255,255,.2);
      overflow: hidden;
    }
    .progress-fill {
      height: 100%;
      background: #fff;
      transition: width .3s ease;
    }

    /* ── Steps ── */
    .steps-bar {
      display: flex;
      background: var(--blue-dk);
    }
    .step {
      flex: 1;
      padding: 10px 4px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 3px;
      cursor: pointer;
      position: relative;
      transition: background .15s;
    }
    .step.active  { background: var(--blue); }
    .step.done    { background: #0d5a9a; cursor: pointer; }
    .step-num {
      width: 22px; height: 22px;
      border-radius: 50%;
      background: rgba(255,255,255,.2);
      color: rgba(255,255,255,.6);
      font-size: 11px;
      display: flex; align-items: center; justify-content: center;
      font-weight: 500;
    }
    .step.active .step-num { background: #fff; color: var(--blue); }
    .step.done   .step-num { background: #27500A; color: #C0DD97; }
    .step-lbl { font-size: 9px; color: rgba(255,255,255,.5); text-align: center; }
    .step.active .step-lbl { color: #fff; font-weight: 500; }
    .step.done   .step-lbl { color: rgba(255,255,255,.7); }
    .step-sep { width: 1px; background: rgba(255,255,255,.1); align-self: stretch; margin: 8px 0; }

    /* ── Form card ── */
    .form-card {
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-top: none;
      border-radius: 0 0 var(--r10) var(--r10);
      padding: 24px;
    }

    .section-head {
      font-size: 14px;
      font-weight: 500;
      margin-bottom: 16px;
      padding-bottom: 10px;
      border-bottom: 0.5px solid var(--bd);
    }

    /* ── Fields ── */
    .field { margin-bottom: 16px; }
    .field label {
      display: block;
      font-size: 11px;
      color: var(--t3);
      margin-bottom: 5px;
      font-weight: 500;
      letter-spacing: .2px;
      text-transform: uppercase;
    }
    .field input,
    .field select,
    .field textarea {
      width: 100%;
      height: 40px;
      border: 0.5px solid var(--bd2);
      border-radius: var(--r8);
      padding: 0 12px;
      font-size: 13px;
      color: var(--t1);
      background: var(--bg0);
      outline: none;
      font-family: inherit;
      transition: border-color .15s;
    }
    .field textarea {
      height: 100px;
      padding: 10px 12px;
      resize: vertical;
    }
    .field input:focus,
    .field select:focus,
    .field textarea:focus {
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(24,95,165,.1);
    }
    .field select {
      appearance: none; -webkit-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23888' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
      cursor: pointer;
    }

    .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .grid3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }

    /* ── Vtype buttons ── */
    .vtype-row { display: flex; gap: 8px; margin-bottom: 16px; }
    .vtype-btn {
      flex: 1;
      height: 64px;
      border: 0.5px solid var(--bd2);
      border-radius: var(--r10);
      background: var(--bg0);
      color: var(--t2);
      cursor: pointer;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 5px;
      font-size: 11px;
      font-family: inherit;
      transition: all .15s;
    }
    .vtype-btn:hover  { border-color: var(--blue); color: var(--blue); background: var(--blue-bg); }
    .vtype-btn.active { border-color: var(--blue); border-width: 1.5px; color: var(--blue); background: var(--blue-bg); }
    .vtype-btn svg    { width: 24px; height: 24px; }

    /* ── Checkboxes ── */
    .chk-row { display: flex; align-items: center; gap: 8px; padding: 5px 0; }
    .chk-row input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--blue); cursor: pointer; }
    .chk-row label { font-size: 13px; color: var(--t1); cursor: pointer; }

    /* ── Upload zone ── */
    .upload-zone {
      border: 1.5px dashed var(--bd2);
      border-radius: var(--r10);
      padding: 28px 20px;
      text-align: center;
      cursor: pointer;
      transition: all .15s;
      margin-bottom: 16px;
    }
    .upload-zone:hover { border-color: var(--blue); background: var(--blue-bg); }
    .upload-zone-title { font-size: 14px; font-weight: 500; margin-bottom: 4px; }
    .upload-zone-sub   { font-size: 12px; color: var(--t2); }
    .upload-zone label {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: var(--blue);
      color: #fff;
      border-radius: var(--r6);
      padding: 7px 16px;
      font-size: 12px;
      font-weight: 500;
      cursor: pointer;
      margin-top: 12px;
      font-family: inherit;
    }
    .upload-zone input[type="file"] { display: none; }

    /* Preview photos */
    .photos-preview {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
      margin-bottom: 16px;
    }
    .photo-thumb {
      aspect-ratio: 1;
      border-radius: var(--r8);
      object-fit: cover;
      border: 0.5px solid var(--bd);
    }

    /* ── Info box ── */
    .info-box {
      background: var(--blue-bg);
      border: 0.5px solid var(--blue-bd);
      border-radius: var(--r8);
      padding: 12px 14px;
      margin-bottom: 16px;
      display: flex;
      gap: 10px;
      align-items: flex-start;
      font-size: 12px;
      color: var(--blue-dk);
      line-height: 1.5;
    }

    /* ── Error box ── */
    .error-box {
      background: #FCEBEB;
      border: 0.5px solid #F09595;
      border-radius: var(--r8);
      padding: 12px 14px;
      margin-bottom: 16px;
      font-size: 13px;
      color: #A32D2D;
    }
    .error-box ul { padding-left: 16px; margin-top: 4px; }

    /* ── Price row ── */
    .price-row { display: flex; align-items: center; gap: 8px; }
    .price-row input { flex: 1; }
    .price-unit { font-size: 13px; font-weight: 500; color: var(--t2); white-space: nowrap; }

    /* ── Navigation buttons ── */
    .nav-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 24px;
      padding-top: 16px;
      border-top: 0.5px solid var(--bd);
    }
    .btn-prev {
      height: 40px;
      padding: 0 20px;
      border: 0.5px solid var(--bd2);
      border-radius: var(--r8);
      background: transparent;
      color: var(--t2);
      font-size: 13px;
      cursor: pointer;
      font-family: inherit;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: background .15s;
    }
    .btn-prev:hover { background: var(--bg1); }
    .btn-next {
      height: 40px;
      padding: 0 24px;
      border: none;
      border-radius: var(--r8);
      background: var(--blue);
      color: #fff;
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      font-family: inherit;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: background .15s;
    }
    .btn-next:hover { background: var(--blue-dk); }

    /* ── Resume ── */
    .resume-card {
      background: var(--bg1);
      border-radius: var(--r8);
      padding: 14px;
      margin-bottom: 12px;
    }
    .resume-title {
      font-size: 11px;
      color: var(--t3);
      margin-bottom: 8px;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: .3px;
    }
    .resume-row {
      display: flex;
      justify-content: space-between;
      padding: 3px 0;
      font-size: 13px;
    }
    .resume-row span:first-child { color: var(--t2); }
    .resume-row span:last-child  { font-weight: 500; }

    /* ── Success ── */
    .success-wrap {
      text-align: center;
      padding: 40px 20px;
    }
    .success-icon {
      width: 60px; height: 60px;
      border-radius: 50%;
      background: var(--green-bg);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
    }
    .success-title { font-size: 18px; font-weight: 500; margin-bottom: 8px; }
    .success-sub   { font-size: 13px; color: var(--t2); margin-bottom: 24px; }

    /* ── Responsive ── */
    @media (max-width: 600px) {
      .grid2, .grid3 { grid-template-columns: 1fr 1fr; }
      .grid3 > div:last-child { grid-column: span 2; }
      .step-lbl { display: none; }
      .photos-preview { grid-template-columns: repeat(3, 1fr); }
    }
  </style>
</head>
<body>

<nav class="nav">
  <a class="logo" href="index.php">
    <img src="images/logo.png" alt="AUTOMARKET" style="height:32px;width:auto;">
  </a>
  <a class="nav-back" href="index.php">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Retour aux annonces
  </a>
</nav>

<div class="page-wrap">

<?php if ($success): ?>
  <!-- ══ Succès ══ -->
  <div class="top-bar"><div class="top-bar-title">Annonce publiée !</div></div>
  <div class="form-card">
    <div class="success-wrap">
      <div class="success-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#639922" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <div class="success-title">Votre annonce a été publiée avec succès !</div>
      <div class="success-sub">Elle sera visible après validation (moins de 24h).</div>
      <a href="index.php" style="display:inline-flex;align-items:center;gap:6px;background:var(--blue);color:#fff;padding:10px 24px;border-radius:var(--r8);font-size:13px;font-weight:500;text-decoration:none;">
        Voir les annonces
      </a>
    </div>
  </div>

<?php else: ?>

  <!-- ══ Formulaire multi-étapes ══ -->
  <form method="POST" enctype="multipart/form-data" id="mainForm">

    <div class="top-bar">
      <div class="top-bar-title">Publier une annonce</div>
      <div class="top-bar-sub">Gratuit · Visible par des milliers d'acheteurs</div>
    </div>

    <div class="progress"><div class="progress-fill" id="prog" style="width:20%"></div></div>

    <div class="steps-bar">
      <div class="step active" id="s1"><div class="step-num">1</div><div class="step-lbl">Véhicule</div></div>
      <div class="step-sep"></div>
      <div class="step" id="s2"><div class="step-num">2</div><div class="step-lbl">Détails</div></div>
      <div class="step-sep"></div>
      <div class="step" id="s3"><div class="step-num">3</div><div class="step-lbl">Photos</div></div>
      <div class="step-sep"></div>
      <div class="step" id="s4"><div class="step-num">4</div><div class="step-lbl">Prix</div></div>
      <div class="step-sep"></div>
      <div class="step" id="s5"><div class="step-num">5</div><div class="step-lbl">Résumé</div></div>
    </div>

    <div class="form-card">

      <?php if (!empty($errors)): ?>
      <div class="error-box">
        <strong>Veuillez corriger les erreurs suivantes :</strong>
        <ul><?php foreach($errors as $e) echo "<li>$e</li>"; ?></ul>
      </div>
      <?php endif; ?>

      <!-- ════ ÉTAPE 1 — Véhicule ════ -->
      <div id="step1">
        <div class="section-head">Type de véhicule</div>

        <div class="vtype-row">
          <button type="button" class="vtype-btn active" onclick="selVtype(this,'voiture')">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="m20.77 9.16-1.37-4.1a3 3 0 0 0-2.85-2.05H7.44a3 3 0 0 0-2.85 2.05l-1.37 4.1c-.72.3-1.23 1.02-1.23 1.84v5c0 .74.41 1.38 1 1.72V20c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-2h12v2c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-2.28a2 2 0 0 0 1-1.72v-5c0-.83-.51-1.54-1.23-1.84ZM7.44 5h9.12a1 1 0 0 1 .95.68L18.62 9H5.39L6.5 5.68A1 1 0 0 1 7.45 5ZM4 16v-5h16v5z"/></svg>
            Voiture
          </button>
          <button type="button" class="vtype-btn" onclick="selVtype(this,'moto')">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.26 14.47s-.06-.04-.1-.05c-.5-.27-1.07-.42-1.66-.42h-.06l-2.19-5.01h1.26c.28 0 .5-.22.5-.5v-1c0-.28-.22-.5-.5-.5h-2.13l-.09-.2a3.01 3.01 0 0 0-2.75-1.8h-1.53v2h1.53c.4 0 .76.24.92.6l1.05 2.4h-3.93c-.29 0-.56.12-.75.33L7.44 13l-2.72-2.72a1 1 0 0 0-.71-.29H1.84v2h1.75L5.6 14c-1.11 0-2.13.51-2.79 1.38C2.51 15.77 2.28 16.25 2.16 16.81c-.04.22-.07.45-.07.68 0 1.93 1.57 3.5 3.5 3.5s3.5-1.57 3.5-3.5v-.1l1.3 1.3c.19.19.44.29.71.29h2c.25 0 .5-.1.68-.27L15 17.47c0 1.93 1.57 3.49 3.5 3.49s3.5-1.57 3.5-3.5c0-1.24-.67-2.4-1.74-3.03Z"/></svg>
            Moto
          </button>
          <button type="button" class="vtype-btn" onclick="selVtype(this,'camion')">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.1 7.8c-.38-.5-.97-.8-1.6-.8H15V6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2 0 1.65 1.35 3 3 3s3-1.35 3-3h4c0 1.65 1.35 3 3 3s3-1.35 3-3c1.1 0 2-.9 2-2v-3.67c0-.43-.14-.86-.4-1.2zM17.5 9l1.5 2h-4V9z"/></svg>
            Camion
          </button>
        </div>
        <input type="hidden" name="typeVehicule" id="typeVehicule" value="voiture">

        <div class="grid2">
          <div class="field">
            <label>Marque</label>
            <select name="idMarque" id="sel-marque" onchange="loadModeles()" required>
              <option value="">Sélectionnez</option>
              <?php while($m = mysqli_fetch_assoc($res_marques)): ?>
              <option value="<?= $m['idMarque'] ?>"><?= htmlspecialchars($m['nom']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="field">
            <label>Modèle</label>
            <select name="idModele" id="sel-modele" required>
              <option value="">Sélectionnez d'abord la marque</option>
            </select>
          </div>
        </div>

        <div class="grid3">
          <div class="field">
            <label>Année</label>
            <select name="annee" required>
              <?php for($y=2025;$y>=1990;$y--) echo "<option>$y</option>"; ?>
            </select>
          </div>
          <div class="field">
            <label>Carburant</label>
            <select name="carburant" required>
              <option>Essence</option>
              <option>Diesel</option>
              <option>GPL</option>
              <option>Hybride</option>
              <option>Électrique</option>
            </select>
          </div>
          <div class="field">
            <label>Transmission</label>
            <select name="transmission" required>
              <option>Manuelle</option>
              <option>Automatique</option>
              <option>Semi-automatique</option>
            </select>
          </div>
        </div>

        <div class="grid2">
          <div class="field">
            <label>Kilométrage (km)</label>
            <input type="number" name="kilometrage" placeholder="ex: 45000" min="0" required>
          </div>
          <div class="field">
            <label>Puissance (CV)</label>
            <input type="number" name="puissance" placeholder="ex: 110" min="0">
          </div>
        </div>

        <div class="field">
          <label>État du véhicule</label>
          <select name="etat" required>
            <option value="Occasion">Occasion</option>
            <option value="Neuf">Neuf</option>
            <option value="Accidente">Accidenté / Pièces</option>
          </select>
        </div>
      </div>

      <!-- ════ ÉTAPE 2 — Détails ════ -->
      <div id="step2" style="display:none">
        <div class="section-head">Description de l'annonce</div>
        <div class="field">
          <label>Titre de l'annonce</label>
          <input type="text" name="titre" id="f-titre" placeholder="ex: Toyota Corolla 2022 — 1.6 Comfort essence" maxlength="100">
        </div>
        <div class="field">
          <label>Description</label>
          <textarea name="description" placeholder="Décrivez votre véhicule : état général, historique d'entretien, options, raison de vente…"></textarea>
        </div>

        <div class="section-head">Équipements</div>
        <div class="grid2">
          <?php
          $equips = ['Climatisation','Caméra de recul','GPS / Navigation','Bluetooth',
                     'Radar de stationnement','Régulateur de vitesse','Vitres électriques',
                     'Toit ouvrant','Jantes alliage','Sièges cuir','ABS','Airbags'];
          foreach($equips as $eq):
          ?>
          <div class="chk-row">
            <input type="checkbox" name="equipements[]" id="eq_<?= md5($eq) ?>" value="<?= $eq ?>">
            <label for="eq_<?= md5($eq) ?>"><?= $eq ?></label>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="field" style="margin-top:16px">
          <label>Wilaya</label>
          <select name="wilaya" required>
            <?php
            $wilayas = ['Adrar','Aïn Defla','Aïn Témouchent','Alger','Annaba','Batna',
                        'Béchar','Béjaïa','Biskra','Blida','Bordj Bou Arreridj','Bouira',
                        'Boumerdès','Chlef','Constantine','Djelfa','El Bayadh','El Oued',
                        'El Tarf','Ghardaïa','Guelma','Illizi','Jijel','Khenchela',
                        'Laghouat','Mascara','Médéa','Mila','Mostaganem','M\'Sila',
                        'Naâma','Oran','Ouargla','Oum El Bouaghi','Relizane','Saïda',
                        'Sétif','Sidi Bel Abbès','Skikda','Souk Ahras','Tamanrasset',
                        'Tébessa','Tiaret','Tindouf','Tipaza','Tissemsilt','Tizi Ouzou','Tlemcen'];
            foreach($wilayas as $w) echo "<option>$w</option>";
            ?>
          </select>
        </div>
      </div>

      <!-- ════ ÉTAPE 3 — Photos ════ -->
      <div id="step3" style="display:none">
        <div class="section-head">Photos du véhicule</div>
        <div class="info-box">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#185FA5" stroke-width="2" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
          <div>Ajoutez jusqu'à 12 photos. La première sera la photo principale. Les annonces avec photos reçoivent 3× plus de vues.</div>
        </div>
        <div class="upload-zone">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#185FA5" stroke-width="1.5" style="margin:0 auto 8px;display:block"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
          <div class="upload-zone-title">Glissez vos photos ici</div>
          <div class="upload-zone-sub">JPG, PNG — max 5 MB par photo</div>
          <label>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Choisir des fichiers
            <input type="file" name="photos[]" multiple accept="image/*" onchange="previewPhotos(this)">
          </label>
        </div>
        <div class="photos-preview" id="photos-preview"></div>
      </div>

      <!-- ════ ÉTAPE 4 — Prix ════ -->
      <div id="step4" style="display:none">
        <div class="section-head">Prix et contact</div>
        <div class="field">
          <label>Prix (DA)</label>
          <div class="price-row">
            <input type="number" name="prix" id="f-prix" placeholder="ex: 3 500 000" min="0" required>
            <span class="price-unit">DA</span>
          </div>
        </div>
        <div class="field">
          <div class="chk-row">
            <input type="checkbox" name="negociable" id="neg">
            <label for="neg">Prix négociable</label>
          </div>
          <div class="chk-row">
            <input type="checkbox" name="credit" id="cred" checked>
            <label for="cred">Paiement par crédit accepté</label>
          </div>
          <div class="chk-row">
            <input type="checkbox" name="echange" id="ech">
            <label for="ech">Échange possible</label>
          </div>
        </div>

        <div class="section-head">Contact</div>
        <div class="grid2">
          <div class="field">
            <label>Nom complet</label>
            <input type="text" name="nom_contact" placeholder="Votre nom">
          </div>
          <div class="field">
            <label>Téléphone</label>
            <input type="tel" name="telephone" placeholder="05XX XX XX XX" required>
          </div>
        </div>
      </div>

      <!-- ════ ÉTAPE 5 — Résumé ════ -->
      <div id="step5" style="display:none">
        <div class="section-head">Récapitulatif de votre annonce</div>
        <div class="resume-card">
          <div class="resume-title">Véhicule</div>
          <div class="resume-row"><span>Type</span><span id="r-type">—</span></div>
          <div class="resume-row"><span>Année</span><span id="r-annee">—</span></div>
          <div class="resume-row"><span>Carburant</span><span id="r-carbu">—</span></div>
          <div class="resume-row"><span>Kilométrage</span><span id="r-km">—</span></div>
          <div class="resume-row"><span>État</span><span id="r-etat">—</span></div>
        </div>
        <div class="resume-card">
          <div class="resume-title">Annonce</div>
          <div class="resume-row"><span>Titre</span><span id="r-titre">—</span></div>
          <div class="resume-row"><span>Wilaya</span><span id="r-wilaya">—</span></div>
        </div>
        <div class="resume-card">
          <div class="resume-title">Prix</div>
          <div class="resume-row"><span>Prix demandé</span><span id="r-prix" style="color:var(--blue);font-size:15px">—</span></div>
        </div>
        <div class="info-box">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#185FA5" stroke-width="2" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
          <div>En publiant, vous acceptez les conditions d'utilisation d'AUTOMARKET. Votre annonce sera visible après validation (moins de 24h).</div>
        </div>
      </div>

      <!-- Boutons navigation -->
      <div class="nav-row">
        <button type="button" class="btn-prev" id="btn-prev" onclick="prev()" style="visibility:hidden">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
          Précédent
        </button>
        <button type="button" class="btn-next" id="btn-next" onclick="next()">
          Suivant
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </button>
      </div>

    </div><!-- end form-card -->
  </form>

<?php endif; ?>

</div><!-- end page-wrap -->

<script>
let cur = 1;
const total = 5;

function goStep(n) {
  document.getElementById('step'+cur).style.display = 'none';
  const sc = document.getElementById('s'+cur);
  sc.classList.remove('active');
  if (cur < n) sc.classList.add('done');
  else sc.classList.remove('done');

  cur = n;
  document.getElementById('step'+cur).style.display = 'block';
  const sn = document.getElementById('s'+cur);
  sn.classList.remove('done');
  sn.classList.add('active');

  document.getElementById('prog').style.width = (cur/total*100)+'%';
  document.getElementById('btn-prev').style.visibility = cur > 1 ? 'visible' : 'hidden';

  const btnNext = document.getElementById('btn-next');
  if (cur === total) {
    buildResume();
    btnNext.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Publier l\'annonce';
  } else {
    btnNext.innerHTML = 'Suivant <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>';
  }
}

function next() {
  if (cur === total) {
    document.getElementById('mainForm').submit();
  } else {
    goStep(cur + 1);
  }
}

function prev() {
  if (cur > 1) goStep(cur - 1);
}

function selVtype(el, val) {
  document.querySelectorAll('.vtype-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('typeVehicule').value = val;
}

function loadModeles() {
  const idMarque = document.getElementById('sel-marque').value;
  const sel = document.getElementById('sel-modele');
  sel.innerHTML = '<option value="">Chargement…</option>';
  if (!idMarque) { sel.innerHTML = '<option value="">Sélectionnez d\'abord la marque</option>'; return; }
  fetch('get_modeles.php?idMarque=' + idMarque)
    .then(r => r.json())
    .then(data => {
      sel.innerHTML = '<option value="">Sélectionnez</option>';
      data.forEach(m => {
        const o = document.createElement('option');
        o.value = m.idModele;
        o.textContent = m.nom;
        sel.appendChild(o);
      });
    });
}

function previewPhotos(input) {
  const preview = document.getElementById('photos-preview');
  preview.innerHTML = '';
  Array.from(input.files).slice(0, 12).forEach(file => {
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.createElement('img');
      img.src = e.target.result;
      img.className = 'photo-thumb';
      preview.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
}

function buildResume() {
  const f = document.getElementById('mainForm');
  document.getElementById('r-type').textContent   = document.getElementById('typeVehicule').value;
  document.getElementById('r-annee').textContent  = f.annee.value;
  document.getElementById('r-carbu').textContent  = f.carburant.value;
  document.getElementById('r-km').textContent     = parseInt(f.kilometrage.value||0).toLocaleString() + ' km';
  document.getElementById('r-etat').textContent   = f.etat.value;
  document.getElementById('r-titre').textContent  = f.titre.value || '—';
  document.getElementById('r-wilaya').textContent = f.wilaya.value;
  const prix = parseInt(f.prix.value||0);
  document.getElementById('r-prix').textContent   = prix ? prix.toLocaleString()+' DA' : '—';
}
</script>

</body>
</html>
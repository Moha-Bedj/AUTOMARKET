<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['idUtilisateur'])) {
  header('Location: inscription.php');
  exit();
}

$idUser = mysqli_real_escape_string($conn, $_SESSION['idUtilisateur']);
$res = mysqli_query($conn, "SELECT nom, prenom , numTel FROM Utilisateur WHERE idUtilisateur = '$idUser'");
$user = mysqli_fetch_assoc($res);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $action = $_POST['action'] ?? '';

  if ($action === 'vehicule') {
    $typeVehicule = $_POST['typeVehicule'] ?? '';
    $idMarque = $_POST['idMarque'] ?? '';
    $idModele = $_POST['idModele'] ?? '';
    $annee = $_POST['annee'] ?? '';
    $carburant = $_POST['carburant'] ?? '';
    $transmission = $_POST['transmission'] ?? '';
    $kilometrage = $_POST['kilometrage'] ?? '';
    $puissance = $_POST['puissance'] ?? '';
    $etat = $_POST['etat'] ?? '';
  }  




  if ($action == 'annonce') {
    /* Récupération des données */
    $typeVehicule = $_POST['typeVehicule'] ?? '';
    $idMarque     = $_POST['idMarque'] ?? '';
    $idModele     = $_POST['idModele'] ?? '';
    $annee        = (int)($_POST['annee'] ?? 0);
    $carburant    = $_POST['carburant'] ?? '';
    $transmission = $_POST['transmission'] ?? '';
    $kilometrage  = (int)($_POST['kilometrage'] ?? 0);
    $puissance    = (int)($_POST['puissance'] ?? 0);
    $etat         = $_POST['etat'] ?? '';

    $titre        = mysqli_real_escape_string($conn, $_POST['titre'] ?? '');
    $description  = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
    $wilaya       = mysqli_real_escape_string($conn, $_POST['wilaya'] ?? '');
    $prix         = (int)($_POST['prix'] ?? 0);
    $telephone    = mysqli_real_escape_string($conn, $_POST['telephone'] ?? '');
    $negociable   = isset($_POST['negociable']) ? 1 : 0;
    $credit       = isset($_POST['credit']) ? 1 : 0;
    $echange      = isset($_POST['echange']) ? 1 : 0;

    /* Si l'utilisateur n'avait pas de téléphone, l'ajouter à son profil */
    if (empty($user['numTel']) && !empty($telephone)) {
        $tel = mysqli_real_escape_string($conn, $telephone);
        mysqli_query($conn, "UPDATE Utilisateur SET numTel = '$tel' 
                             WHERE idUtilisateur = '$idUser'");
    }

    /* 1. Insérer le Véhicule */
    $sqlV = "INSERT INTO Vehicule 
        (typeVehicule, idMarque, idModele, annee, carburant, transmission, kilometrage, puissance, etat)
        VALUES 
        ('$typeVehicule', '$idMarque', '$idModele', $annee, '$carburant', '$transmission', $kilometrage, $puissance, '$etat')";

    if (!mysqli_query($conn, $sqlV)) {
        echo json_encode(['success' => false, 'message' => 'Erreur véhicule : ' . mysqli_error($conn)]);
        exit;
    }
    $idVehicule = mysqli_insert_id($conn);

    /* 2. Insérer l'Annonce */
    $sqlA = "INSERT INTO Annonce 
        (idVendeur, idVehicule, titre, description, prix, localisation, datePublication, statutAnnonce, negociable, credit, echange)
        VALUES 
        ('$idUser', $idVehicule, '$titre', '$description', $prix, '$wilaya', NOW(), 'active', $negociable, $credit, $echange)";

    if (!mysqli_query($conn, $sqlA)) {
        echo json_encode(['success' => false, 'message' => 'Erreur annonce : ' . mysqli_error($conn)]);
        exit;
    }

    $idAnnonce = mysqli_insert_id($conn);

    /* 3. Redirection vers index.php avec message succès */
    header('Location: index.php?publie=1&id=' . $idAnnonce);
    exit;
}


  if($action == 'photo') {
    $photos = $_FILES['photos'] ?? null;
  }

}  

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

    .page-wrap {
      max-width: 760px;
      margin: 0 auto;
      padding: 24px 16px 60px;
    }

    .top-bar {
      background: var(--blue);
      padding: 16px 20px;
      border-radius: var(--r10) var(--r10) 0 0;
    }
    .top-bar-title { color: #fff; font-size: 16px; font-weight: 500; margin-bottom: 2px; }
    .top-bar-sub   { color: rgba(255,255,255,.7); font-size: 12px; }

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

    .form-card {
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-top: none;
      border-radius: 0 0 var(--r10) var(--r10);
      padding: 34px;
    }

    .section-head {
      font-size: 14px;
      font-weight: 500;
      margin-bottom: 16px;
      padding-bottom: 10px;
      border-bottom: 0.5px solid var(--bd);
    }

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
    .field input[readonly] {
      background: var(--bg1);
      color: var(--t2);
      border-color: var(--bd);
    }
    .field input[readonly]:focus {
      border-color: var(--bd);
      box-shadow: none;
    }
    .tel-row {
      display: flex;
      gap: 8px;
    }
    .tel-prefix {
      display: flex;
      align-items: center;
      padding: 0 12px;
      border: 0.5px solid var(--bd2);
      border-radius: var(--r8);
      background: var(--bg1);
      font-size: 13px;
      color: var(--t2);
      font-weight: 500;
    }
    .tel-row input { 
      flex: 1; 
    }

    .field input[readonly] {
      background: var(--bg1);
      color: var(--t2);
      border-color: var(--bd);
    }
    .field input[readonly]:focus {
      border-color: var(--bd);
      box-shadow: none;
    }
    .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .grid3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }

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

    .chk-row { display: flex; align-items: center; gap: 8px; padding: 5px 0; }
    .chk-row input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--blue); cursor: pointer; }
    .chk-row label { font-size: 13px; color: var(--t1); cursor: pointer; }

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

    .price-row { display: flex; align-items: center; gap: 8px; }
    .price-row input { flex: 1; }
    .price-unit { font-size: 13px; font-weight: 500; color: var(--t2); white-space: nowrap; }

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
  <a class="nav-back" href="mesannonces.php">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Retour aux annonces
  </a>
</nav>

<div class="page-wrap">

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

      <div id="step1">
        <div class="section-head">Type de véhicule</div>

        <div class="vtype-row">
          <button type="button" class="vtype-btn active" onclick="selVtype(this,'voiture')">Voiture</button>
          <button type="button" class="vtype-btn" onclick="selVtype(this,'moto')">Moto</button>
          <button type="button" class="vtype-btn" onclick="selVtype(this,'camion')">Camion</button>
        </div>
        <input type="hidden" name="typeVehicule" id="typeVehicule" value="voiture">

        <div class="grid2">
          <div class="field">
            <label>Marque</label>
            <select name="idMarque" id="sel-marque" onchange="loadModeles()" required>
              <option value="">Sélectionnez</option>
              <option value="1">Exemple marque</option>
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
              <option>2025</option>
              <option>2024</option>
              <option>2023</option>
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
          <div class="chk-row"><input type="checkbox" name="equipements[]" id="eq1" value="Climatisation"><label for="eq1">Climatisation</label></div>
          <div class="chk-row"><input type="checkbox" name="equipements[]" id="eq2" value="Caméra de recul"><label for="eq2">Caméra de recul</label></div>
          <div class="chk-row"><input type="checkbox" name="equipements[]" id="eq3" value="GPS / Navigation"><label for="eq3">GPS / Navigation</label></div>
          <div class="chk-row"><input type="checkbox" name="equipements[]" id="eq4" value="Bluetooth"><label for="eq4">Bluetooth</label></div>
        </div>

        <div class="field" style="margin-top:16px">
          <label>Wilaya</label>
          <select name="wilaya" required>
            <option>Adrar</option>
            <option>Alger</option>
            <option>Oran</option>
            <option>Constantine</option>
          </select>
        </div>
      </div>

      <div id="step3" style="display:none">
        <div class="section-head">Photos du véhicule</div>
        <div class="info-box">
          <div>Ajoutez jusqu'à 12 photos. La première sera la photo principale.</div>
        </div>
        <div class="upload-zone">
          <div class="upload-zone-title">Glissez vos photos ici</div>
          <div class="upload-zone-sub">JPG, PNG — max 5 MB par photo</div>
          <label>
            Choisir des fichiers
            <input type="file" name="photos[]" multiple accept="image/*" onchange="previewPhotos(this)">
          </label>
        </div>
        <div class="photos-preview" id="photos-preview"></div>
      </div>

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
            <input type="text" name="nom_contact" placeholder="Votre nom" value="<?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?>" readonly>
          </div>
          <div class="field">
            <label>
              Téléphone
              <?php if (!empty($user['numTel'])): ?>
                
              <?php endif; ?>
            </label>

            <?php if (!empty($user['numTel'])): ?>
              <!-- Téléphone existe → readonly -->
              <div class="tel-row">
                <div class="tel-prefix">+213</div>
                <input type="tel" 
                      name="telephone" 
                      value="<?= htmlspecialchars($user['numTel']) ?>" 
                      readonly>
              </div>
            <?php else: ?>
              <!-- Pas de téléphone → saisie manuelle -->
              <div class="tel-row">
                <div class="tel-prefix">+213</div>
                <input type="tel" 
                      name="telephone" 
                      placeholder="5XXXXXXXX" 
                      maxlength="9"
                      oninput="validatePhoneInput(this)"
                      required>
              </div>
              <div style="font-size:11px;color:var(--t3);margin-top:4px">
                Votre téléphone sera aussi enregistré dans votre profil
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

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
      </div>

      <div class="nav-row">
        <button type="button" class="btn-prev" id="btn-prev" onclick="prev()" style="visibility:hidden">Précédent</button>
        <button type="button" class="btn-next" id="btn-next" onclick="next()">Suivant</button>
      </div>

    </div>
  </form>

</div>

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
    btnNext.innerHTML = "Publier l'annonce";
  } else {
    btnNext.innerHTML = 'Suivant';
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
  if (!idMarque) {
    sel.innerHTML = '<option value="">Sélectionnez d\'abord la marque</option>';
    return;
  }
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
function validatePhoneInput(input) {
  let value = input.value.replace(/[^0-9]/g, '');
  if (value.length > 0 && !['5','6','7'].includes(value[0])) {
    value = value.substring(1);
  }
  input.value = value.substring(0, 9);
}
</script>

</body>
</html>
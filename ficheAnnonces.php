<?php
session_start();
require_once 'connexion.php';

$idAnnonce = $_GET['id'] ?? '';
if (!$idAnnonce) {
    header("Location: index.php");
    exit;
}
$idAnnonceSql = mysqli_real_escape_string($conn, $idAnnonce);

/* Récupération annonce + véhicule + vendeur */
$sql = "
    SELECT 
        a.idAnnonce, a.titre, a.description, a.prix, a.localisation,
        a.datePublication, a.nbrVus, a.vendeurVerif, a.idVendeur,
        v.idVehicule, v.typeVehicule, v.annee, v.kilometrage, v.carburant,
        v.transmission, v.puissance, v.couleur, v.nbrPortes, v.nbrPlaces,
        v.etatVehicule, v.cylindree, v.idModele,
        u.nom AS vendeur_nom, u.prenom AS vendeur_prenom, u.email AS vendeur_email,
        u.numTel AS vendeur_tel, u.dateInscription AS vendeur_date,
        u.badgeVerifie,
        m.nomMarque, mo.nomModele,
        ven.typeVendeur, ven.nbrAnnonceAct
    FROM Annonce a
    LEFT JOIN Vehicule v ON a.idVehicule = v.idVehicule
    LEFT JOIN Utilisateur u ON a.idVendeur = u.idUtilisateur
    LEFT JOIN Vendeur ven ON ven.idUtilisateur = u.idUtilisateur
    LEFT JOIN Modele mo ON v.idModele = mo.idModele
    LEFT JOIN Marque m ON mo.idMarque = m.idMarque
    WHERE a.idAnnonce = '$idAnnonceSql'
    LIMIT 1
";

$res = mysqli_query($conn, $sql);
if (!$res || mysqli_num_rows($res) === 0) {
    header("Location: index.php?erreur=annonce_introuvable");
    exit;
}
$a = mysqli_fetch_assoc($res);

/* Incrémenter compteur de vues (sauf si c'est le vendeur lui-même) */
if (!isset($_SESSION['idUtilisateur']) || $_SESSION['idUtilisateur'] !== $a['idVendeur']) {
    mysqli_query($conn, "UPDATE Annonce SET nbrVus = COALESCE(nbrVus, 0) + 1 WHERE idAnnonce = '$idAnnonceSql'");
    $a['nbrVus'] = ($a['nbrVus'] ?? 0) + 1;
}

/* Photos */
$photos = [];
$rPhotos = mysqli_query($conn, "SELECT idPhoto, urlPhoto, descriptionPhoto FROM Photos WHERE idAnnonce='$idAnnonceSql' ORDER BY ordrePhoto ASC");
if ($rPhotos) {
    while ($p = mysqli_fetch_assoc($rPhotos)) {
        $photos[] = $p;
    }
}
$nbPhotos = count($photos);

/* Équipements groupés par catégorie */
$equipements = [];
$rEq = mysqli_query($conn, "
    SELECT e.libelleEquipement, e.categorieEquipement
    FROM Equipement e, Vehicule_Equipement ve
    WHERE ve.idEquipement = e.idEquipement
    AND ve.idVehicule = '" . mysqli_real_escape_string($conn, $a['idVehicule']) . "'
");
if ($rEq) {
    while ($e = mysqli_fetch_assoc($rEq)) {
        $cat = $e['categorieEquipement'] ?: 'Autres';
        $equipements[$cat][] = $e['libelleEquipement'];
    }
}

/* Vérifier si en favoris (si user connecté) */
$estFavori = false;
if (isset($_SESSION['idUtilisateur'])) {
    $idU = mysqli_real_escape_string($conn, $_SESSION['idUtilisateur']);
    $rFav = mysqli_query($conn, "SELECT 1 FROM Favoris WHERE idUtilisateur='$idU' AND idAnnonce='$idAnnonceSql' LIMIT 1");
    if ($rFav && mysqli_num_rows($rFav) > 0) $estFavori = true;
}

/* Compteur favoris global */
$nbFavoris = 0;
$rNbFav = mysqli_query($conn, "SELECT COUNT(*) AS nb FROM Favoris WHERE idAnnonce='$idAnnonceSql'");
if ($rNbFav) $nbFavoris = mysqli_fetch_assoc($rNbFav)['nb'] ?? 0;

/* Helpers */
function diffDate($date) {
    $d = new DateTime($date);
    $now = new DateTime();
    $diff = $now->diff($d)->days;
    if ($diff == 0) return "Aujourd'hui";
    if ($diff == 1) return "Hier";
    if ($diff < 30) return "Il y a $diff jours";
    if ($diff < 365) return "Il y a " . floor($diff/30) . " mois";
    return "Il y a " . floor($diff/365) . " an(s)";
}

function dateFr($date) {
    $mois = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
    $d = new DateTime($date);
    return $mois[(int)$d->format('m')-1] . ' ' . $d->format('Y');
}

/* Initiale avatar */
$initiale = strtoupper(substr($a['vendeur_prenom'] ?? 'U', 0, 1));
$nomVendeur = trim(($a['vendeur_prenom'] ?? '') . ' ' . ($a['vendeur_nom'] ?? ''));

/* Référence courte */
$refCourte = 'AM-' . strtoupper(substr($idAnnonce, 0, 8));

/* Couleur ext / int */
$couleurExt = $a['couleur'];
$couleurInt = '';
if (strpos($couleurExt, '/ int.') !== false) {
    list($couleurExt, $tmp) = explode('/ int.', $couleurExt);
    $couleurExt = trim($couleurExt);
    $couleurInt = trim($tmp);
}

/* Sous-titre auto */
$sousTitre = [];
if ($a['typeVehicule']) $sousTitre[] = ucfirst($a['typeVehicule']);
if ($a['etatVehicule']) $sousTitre[] = ucfirst($a['etatVehicule']);
$sousTitreStr = implode(' · ', $sousTitre);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($a['titre']) ?> — AUTOMARKET</title>
  <link rel="icon" href="images/logo.png">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --blue: #185FA5; --blue-dk: #0C447C; --blue-bg: #E6F1FB; --blue-bd: #B5D4F4;
      --bg0: #ffffff; --bg1: #f5f4f0; --bg2: #eceae4;
      --t1: #1a1a18; --t2: #5f5e5a; --t3: #888780;
      --bd: rgba(0,0,0,0.11); --bd2: rgba(0,0,0,0.22);
      --green: #639922; --green-bg: #EAF3DE; --green-dk: #27500A; --green-bd: #C0DD97;
      --red: #E24B4A; --red-bg: #FCEBEB;
      --orange: #FF8A4C; --amber: #BA7517; --amber-bg: #FAEEDA;
      --r6: 6px; --r8: 8px; --r10: 10px;
    }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; background: var(--bg1); color: var(--t1); min-height: 100vh; }

    /* NAVBAR */
    .nav { background: var(--bg0); border-bottom: 0.5px solid var(--bd); height: 52px; display: flex; align-items: center; padding: 0 20px; gap: 16px; position: sticky; top: 0; z-index: 100; }
    .logo { display: flex; align-items: center; }
    .nav-back { font-size: 13px; color: var(--t2); display: flex; align-items: center; gap: 6px; text-decoration: none; padding: 6px 12px; border-radius: var(--r6); transition: background .15s; }
    .nav-back:hover { background: var(--bg1); color: var(--t1); }
    .nav-actions { margin-left: auto; display: flex; gap: 6px; }
    .nav-icon-btn { width: 34px; height: 34px; border-radius: 50%; border: 0.5px solid var(--bd2); background: var(--bg0); display: flex; align-items: center; justify-content: center; color: var(--t2); cursor: pointer; transition: all .15s; }
    .nav-icon-btn:hover { background: var(--bg1); color: var(--blue); border-color: var(--blue); }

    /* CONTAINER */
    .container { max-width: 1100px; margin: 0 auto; padding: 18px 16px 40px; }

    /* BREADCRUMB */
    .breadcrumb { font-size: 12px; color: var(--t3); margin-bottom: 14px; }
    .breadcrumb a { color: var(--t3); text-decoration: none; }
    .breadcrumb a:hover { color: var(--blue); }
    .breadcrumb .sep { margin: 0 6px; }
    .breadcrumb .active { color: var(--t1); font-weight: 500; }

    /* GRID 2 COLONNES */
    .grid { display: grid; grid-template-columns: 1fr 320px; gap: 20px; align-items: start; }

    /* GALERIE */
    .gallery { display: grid; grid-template-columns: 1fr 100px; gap: 10px; margin-bottom: 16px; }
    .main-img-wrap {
      aspect-ratio: 4/3;
      background: linear-gradient(135deg, #d4d8e0, #b8c1cc);
      border-radius: var(--r10);
      position: relative;
      overflow: hidden;
      cursor: zoom-in;
    }
    .main-img-wrap img {
      width: 100%; height: 100%;
      object-fit: cover;
      display: block;
    }
    .main-img-placeholder {
      width: 100%; height: 100%;
      display: flex; align-items: center; justify-content: center;
      color: rgba(255,255,255,0.5);
    }
    .img-counter {
      position: absolute; bottom: 12px; right: 12px;
      background: rgba(0,0,0,0.65);
      color: #fff; font-size: 12px;
      padding: 5px 12px; border-radius: 14px;
      backdrop-filter: blur(4px);
      display: flex; align-items: center; gap: 6px;
    }
    .img-fav {
      position: absolute; top: 12px; right: 12px;
      width: 42px; height: 42px;
      border-radius: 50%;
      background: rgba(255,255,255,0.95);
      border: none;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer;
      color: var(--t2);
      transition: all .15s;
    }
    .img-fav:hover { background: var(--red); color: #fff; transform: scale(1.08); }
    .img-fav.faved { color: var(--red); }
    .img-fav.faved svg { fill: var(--red); stroke: var(--red); }

    .img-nav {
      position: absolute; top: 50%; transform: translateY(-50%);
      width: 38px; height: 38px;
      border-radius: 50%;
      background: rgba(255,255,255,0.92);
      border: none;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer;
      color: var(--t1);
      transition: all .15s;
    }
    .img-nav:hover { background: var(--blue); color: #fff; }
    .img-nav-left { left: 12px; }
    .img-nav-right { right: 12px; }

    .thumbs { display: flex; flex-direction: column; gap: 7px; max-height: 100%; overflow-y: auto; }
    .thumbs::-webkit-scrollbar { width: 4px; }
    .thumbs::-webkit-scrollbar-thumb { background: var(--bd2); border-radius: 2px; }
    .thumb {
      aspect-ratio: 4/3;
      background: linear-gradient(135deg, #c4c8d0, #a8b1bc);
      border-radius: var(--r6);
      cursor: pointer;
      opacity: 0.65;
      transition: all .15s;
      border: 2px solid transparent;
      overflow: hidden;
    }
    .thumb:hover { opacity: 1; }
    .thumb.active { opacity: 1; border-color: var(--blue); }
    .thumb img {
      width: 100%; height: 100%;
      object-fit: cover;
      display: block;
    }
    .thumb-more {
      position: relative;
    }
    .thumb-more::after {
      content: '+' attr(data-count);
      position: absolute; inset: 0;
      background: rgba(0,0,0,0.6);
      color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; font-weight: 500;
      border-radius: var(--r6);
    }

    /* TITRE */
    .title-block { margin-bottom: 14px; }
    .page-title { font-size: 24px; font-weight: 600; line-height: 1.2; color: var(--t1); margin-bottom: 6px; }
    .page-subtitle { font-size: 13px; color: var(--t2); }

    /* PRIX BAR */
    .price-bar {
      display: flex; align-items: flex-end; justify-content: space-between;
      padding: 16px 20px;
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r10);
      margin-bottom: 14px;
      flex-wrap: wrap;
      gap: 12px;
    }
    .price-main { font-size: 28px; font-weight: 700; color: var(--blue); line-height: 1; }
    .price-info { font-size: 11px; color: var(--t3); margin-top: 6px; }
    .price-tags { display: flex; gap: 6px; flex-wrap: wrap; }
    .price-tag {
      font-size: 11px; padding: 5px 12px;
      border-radius: 14px;
      background: var(--blue-bg); color: var(--blue-dk);
      font-weight: 500;
    }
    .price-tag.credit { background: var(--green-bg); color: var(--green-dk); }
    .price-tag.echange { background: var(--amber-bg); color: var(--amber); }

    /* SECTION CARD */
    .card {
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r10);
      padding: 18px 20px;
      margin-bottom: 14px;
    }
    .card-h {
      font-size: 15px; font-weight: 600;
      margin-bottom: 14px;
      padding-bottom: 12px;
      border-bottom: 0.5px solid var(--bd);
      display: flex; align-items: center; gap: 8px;
    }
    .card-h-icon { color: var(--blue); }

    /* SPECS GRID */
    .specs-grid {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 12px 24px;
    }
    .spec { display: flex; flex-direction: column; gap: 3px; }
    .spec-label { font-size: 11px; color: var(--t3); }
    .spec-val { font-size: 14px; font-weight: 500; color: var(--t1); }

    /* DESCRIPTION */
    .desc-text {
      font-size: 13px;
      color: var(--t1);
      line-height: 1.6;
      white-space: pre-line;
    }

    /* EQUIPEMENTS */
    .equip-section {
      font-size: 11px;
      color: var(--t3);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .4px;
      margin: 14px 0 8px;
    }
    .equip-section:first-child { margin-top: 0; }
    .equip-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
    }
    .equip-item {
      display: flex; align-items: center; gap: 8px;
      font-size: 13px;
      color: var(--t1);
    }
    .equip-check {
      width: 18px; height: 18px;
      border-radius: 50%;
      background: var(--green-bg);
      color: var(--green);
      display: flex; align-items: center; justify-content: center;
      font-size: 11px;
      flex-shrink: 0;
    }

    /* SIDEBAR */
    .sidebar {
      display: flex; flex-direction: column;
      gap: 12px;
      position: sticky; top: 70px;
    }

    /* SELLER CARD */
    .seller-card {
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r10);
      padding: 18px;
    }
    .seller-h {
      display: flex; align-items: center; gap: 12px;
      padding-bottom: 14px;
      border-bottom: 0.5px solid var(--bd);
      margin-bottom: 14px;
    }
    .seller-avatar {
      width: 48px; height: 48px;
      border-radius: 50%;
      background: var(--blue);
      color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-weight: 600; font-size: 18px;
      flex-shrink: 0;
    }
    .seller-info-name { font-size: 14px; font-weight: 600; line-height: 1.3; }
    .seller-info-type { font-size: 12px; color: var(--t3); margin-top: 4px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .verif-badge {
      background: var(--blue-bg);
      color: var(--blue-dk);
      font-size: 10px;
      padding: 2px 7px;
      border-radius: 10px;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 3px;
    }

    .seller-stats { display: flex; flex-direction: column; gap: 6px; font-size: 12px; }
    .seller-stat { display: flex; justify-content: space-between; color: var(--t2); }
    .seller-stat strong { color: var(--t1); font-weight: 500; }

    /* CONTACT BTNS */
    .contact-btns { display: flex; flex-direction: column; gap: 8px; margin-top: 16px; }
    .btn-primary {
      background: var(--blue); color: #fff; border: none;
      border-radius: var(--r8);
      padding: 12px 16px;
      font-size: 14px; font-weight: 600;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      font-family: inherit;
      transition: background .15s;
    }
    .btn-primary:hover { background: var(--blue-dk); }
    .btn-msg {
      background: var(--bg0);
      border: 0.5px solid var(--blue);
      color: var(--blue);
      border-radius: var(--r8);
      padding: 11px 16px;
      font-size: 13px; font-weight: 500;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      font-family: inherit;
      transition: all .15s;
      text-decoration: none;
    }
    .btn-msg:hover { background: var(--blue-bg); }
    .btn-secondary {
      background: var(--bg0);
      border: 0.5px solid var(--bd2);
      color: var(--t1);
      border-radius: var(--r8);
      padding: 10px 16px;
      font-size: 13px;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      font-family: inherit;
      transition: all .15s;
    }
    .btn-secondary:hover { background: var(--bg1); border-color: var(--blue); color: var(--blue); }
    .btn-secondary.faved { color: var(--red); border-color: var(--red); }
    .btn-secondary.faved svg { fill: var(--red); stroke: var(--red); }

    /* PHONE REVEAL */
    .phone-display {
      background: var(--blue-bg);
      border: 0.5px solid var(--blue-bd);
      color: var(--blue-dk);
      padding: 12px 16px;
      border-radius: var(--r8);
      font-size: 16px;
      font-weight: 600;
      text-align: center;
      letter-spacing: 1px;
      margin-bottom: 0;
    }

    /* LOCATION */
    .location-card {
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r10);
      padding: 14px 16px;
    }
    .loc-h { font-size: 13px; font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
    .loc-h svg { color: var(--blue); }
    .loc-val { font-size: 13px; color: var(--t2); }
    .loc-val strong { color: var(--t1); }
    .loc-map {
      background: linear-gradient(135deg, #e8f0e8, #c8d8c8);
      height: 110px;
      border-radius: var(--r8);
      margin-top: 10px;
      display: flex; align-items: center; justify-content: center;
      color: var(--green-dk);
      font-size: 11px;
      cursor: pointer;
      transition: opacity .15s;
    }
    .loc-map:hover { opacity: 0.85; }

    /* ACTIONS BAR */
    .actions-card {
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r10);
      padding: 12px 14px;
    }
    .action-row {
      display: flex; align-items: center; gap: 8px;
      padding: 8px 0;
      font-size: 12px;
      color: var(--t2);
    }
    .action-row svg { color: var(--t3); flex-shrink: 0; }
    .action-row + .action-row {
      border-top: 0.5px solid var(--bd);
    }

    /* TIPS */
    .tips-card {
      background: var(--green-bg);
      border: 0.5px solid var(--green-bd);
      border-radius: var(--r10);
      padding: 12px 14px;
    }
    .tips-h {
      font-size: 12px; font-weight: 600;
      color: var(--green-dk);
      display: flex; align-items: center; gap: 6px;
      margin-bottom: 6px;
    }
    .tips-text {
      font-size: 11px; color: var(--green-dk);
      line-height: 1.5;
    }

    /* TOAST */
    .toast {
      position: fixed; bottom: 24px; right: 24px;
      background: var(--t1); color: #fff;
      padding: 12px 18px;
      border-radius: var(--r8);
      font-size: 13px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      z-index: 1000;
      opacity: 0;
      transform: translateY(20px);
      transition: all .3s;
    }
    .toast.show { opacity: 1; transform: translateY(0); }

    /* LIGHTBOX */
    .lightbox {
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.92);
      z-index: 2000;
      display: none;
      align-items: center; justify-content: center;
      padding: 40px;
    }
    .lightbox.show { display: flex; }
    .lightbox img {
      max-width: 100%; max-height: 100%;
      object-fit: contain;
      border-radius: 6px;
    }
    .lightbox-close {
      position: absolute; top: 20px; right: 20px;
      width: 44px; height: 44px;
      background: rgba(255,255,255,0.15);
      color: #fff;
      border: none;
      border-radius: 50%;
      cursor: pointer;
      font-size: 24px;
      display: flex; align-items: center; justify-content: center;
    }
    .lightbox-close:hover { background: rgba(255,255,255,0.25); }
    .lightbox-nav {
      position: absolute; top: 50%; transform: translateY(-50%);
      width: 50px; height: 50px;
      background: rgba(255,255,255,0.15);
      color: #fff;
      border: none;
      border-radius: 50%;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
    }
    .lightbox-nav:hover { background: rgba(255,255,255,0.25); }
    .lightbox-prev { left: 20px; }
    .lightbox-next { right: 20px; }
    .lightbox-counter {
      position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%);
      background: rgba(255,255,255,0.15);
      color: #fff;
      padding: 8px 16px;
      border-radius: 14px;
      font-size: 13px;
    }

    /* RESPONSIVE */
    @media (max-width: 900px) {
      .grid { grid-template-columns: 1fr; }
      .sidebar { position: static; }
    }
    @media (max-width: 600px) {
      .container { padding: 12px 10px 32px; }
      .gallery { grid-template-columns: 1fr; }
      .thumbs { flex-direction: row; max-height: none; overflow-x: auto; }
      .thumb { flex: 0 0 70px; }
      .specs-grid { grid-template-columns: 1fr 1fr; }
      .equip-grid { grid-template-columns: 1fr; }
      .page-title { font-size: 19px; }
      .price-main { font-size: 24px; }
      .card { padding: 14px 16px; }
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="nav">
    <a class="logo" href="index.php">
      <img src="images/logo.png" alt="AUTOMARKET" style="height:34px;">
    </a>
    <a href="javascript:history.back()" class="nav-back">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Retour aux résultats
    </a>
    <div class="nav-actions">
      <button class="nav-icon-btn" title="Partager" onclick="sharePage()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
      </button>
      <button class="nav-icon-btn" title="Signaler" onclick="signaler()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
      </button>
    </div>
  </nav>

  <div class="container">

    <!-- BREADCRUMB -->
    <div class="breadcrumb">
      <a href="index.php">Accueil</a>
      <span class="sep">›</span>
      <a href="recherche.php?type=<?= htmlspecialchars($a['typeVehicule']) ?>"><?= ucfirst($a['typeVehicule'] ?? 'Véhicules') ?></a>
      <?php if ($a['nomMarque']): ?>
        <span class="sep">›</span>
        <a href="recherche.php?marque=<?= urlencode($a['nomMarque']) ?>"><?= htmlspecialchars($a['nomMarque']) ?></a>
      <?php endif; ?>
      <?php if ($a['nomModele']): ?>
        <span class="sep">›</span>
        <span class="active"><?= htmlspecialchars($a['nomModele']) ?></span>
      <?php endif; ?>
    </div>

    <div class="grid">

      <!-- COLONNE PRINCIPALE -->
      <div>

        <!-- GALERIE -->
        <div class="gallery">
          <div class="main-img-wrap" id="main-img-wrap" onclick="openLightbox(currentImg)">
            <?php if ($nbPhotos > 0): ?>
              <img id="main-img" src="<?= htmlspecialchars($photos[0]['urlPhoto']) ?>" alt="<?= htmlspecialchars($a['titre']) ?>">
              
              <?php if ($nbPhotos > 1): ?>
                <button class="img-nav img-nav-left" onclick="event.stopPropagation();prevImg()">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <button class="img-nav img-nav-right" onclick="event.stopPropagation();nextImg()">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
                </button>
              <?php endif; ?>
              
              <div class="img-counter">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                <span id="img-counter-text">1 / <?= $nbPhotos ?> photos</span>
              </div>
            <?php else: ?>
              <div class="main-img-placeholder">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.5"><rect x="1" y="6" width="22" height="13" rx="3"/><circle cx="7" cy="16" r="1.5"/><circle cx="17" cy="16" r="1.5"/></svg>
              </div>
            <?php endif; ?>

            <button class="img-fav <?= $estFavori ? 'faved' : '' ?>" id="img-fav-btn" onclick="event.stopPropagation();toggleFavori()">
              <svg width="20" height="20" fill="<?= $estFavori ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M11.29 20.66c.2.2.45.29.71.29s.51-.1.71-.29l7.5-7.5c2.35-2.35 2.35-6.05 0-8.41-2.3-2.28-5.85-2.35-8.21-.2-2.36-2.15-5.91-2.09-8.21.2-2.35 2.36-2.35 6.06 0 8.41z"/>
              </svg>
            </button>
          </div>

          <?php if ($nbPhotos > 1): ?>
            <div class="thumbs">
              <?php 
              $maxThumbs = 5;
              for ($i = 0; $i < min($maxThumbs, $nbPhotos); $i++): 
                $isLast = ($i == $maxThumbs - 1) && ($nbPhotos > $maxThumbs);
              ?>
                <div class="thumb <?= $i === 0 ? 'active' : '' ?> <?= $isLast ? 'thumb-more' : '' ?>" 
                     <?= $isLast ? 'data-count="' . ($nbPhotos - $maxThumbs + 1) . '"' : '' ?>
                     onclick="setMainImg(<?= $i ?>)">
                  <img src="<?= htmlspecialchars($photos[$i]['urlPhoto']) ?>" alt="">
                </div>
              <?php endfor; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- TITRE -->
        <div class="title-block">
          <h1 class="page-title"><?= htmlspecialchars($a['titre']) ?></h1>
          <p class="page-subtitle"><?= htmlspecialchars($sousTitreStr) ?></p>
        </div>

        <!-- PRIX -->
        <div class="price-bar">
          <div>
            <div class="price-main"><?= number_format($a['prix'], 0, ',', ' ') ?> DA</div>
            <div class="price-info">Référence : <?= $refCourte ?></div>
          </div>
          <div class="price-tags">
            <span class="price-tag">À vendre</span>
            <?php if ($a['typeVehicule']): ?>
              <span class="price-tag credit"><?= ucfirst($a['typeVehicule']) ?></span>
            <?php endif; ?>
          </div>
        </div>

        <!-- CARACTÉRISTIQUES -->
        <div class="card">
          <div class="card-h">
            <svg class="card-h-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Caractéristiques techniques
          </div>
          <div class="specs-grid">
            <div class="spec">
              <span class="spec-label">Année</span>
              <span class="spec-val"><?= $a['annee'] ?: '—' ?></span>
            </div>
            <div class="spec">
              <span class="spec-label">Kilométrage</span>
              <span class="spec-val"><?= $a['kilometrage'] ? number_format($a['kilometrage'], 0, ',', ' ') . ' km' : '—' ?></span>
            </div>
            <div class="spec">
              <span class="spec-label">Carburant</span>
              <span class="spec-val"><?= htmlspecialchars($a['carburant'] ?: '—') ?></span>
            </div>
            <div class="spec">
              <span class="spec-label">Transmission</span>
              <span class="spec-val"><?= htmlspecialchars($a['transmission'] ?: '—') ?></span>
            </div>
            <?php if ($a['puissance']): ?>
            <div class="spec">
              <span class="spec-label">Puissance</span>
              <span class="spec-val"><?= $a['puissance'] ?> ch</span>
            </div>
            <?php endif; ?>
            <?php if ($a['cylindree']): ?>
            <div class="spec">
              <span class="spec-label">Cylindrée</span>
              <span class="spec-val"><?= number_format($a['cylindree'], 0, ',', ' ') ?> cm³</span>
            </div>
            <?php endif; ?>
            <div class="spec">
              <span class="spec-label">État</span>
              <span class="spec-val"><?= ucfirst($a['etatVehicule'] ?: 'Occasion') ?></span>
            </div>
            <?php if ($a['nbrPortes']): ?>
            <div class="spec">
              <span class="spec-label">Portes / Places</span>
              <span class="spec-val"><?= $a['nbrPortes'] ?> / <?= $a['nbrPlaces'] ?: '—' ?></span>
            </div>
            <?php endif; ?>
            <?php if ($couleurExt): ?>
            <div class="spec">
              <span class="spec-label">Couleur ext.</span>
              <span class="spec-val"><?= htmlspecialchars($couleurExt) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($couleurInt): ?>
            <div class="spec">
              <span class="spec-label">Couleur int.</span>
              <span class="spec-val"><?= htmlspecialchars($couleurInt) ?></span>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- DESCRIPTION -->
        <?php if ($a['description']): ?>
        <div class="card">
          <div class="card-h">
            <svg class="card-h-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="14" y2="18"/></svg>
            Description
          </div>
          <div class="desc-text"><?= nl2br(htmlspecialchars($a['description'])) ?></div>
        </div>
        <?php endif; ?>

        <!-- ÉQUIPEMENTS -->
        <?php if (!empty($equipements)): ?>
        <div class="card">
          <div class="card-h">
            <svg class="card-h-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Équipements &amp; options
          </div>

          <?php foreach ($equipements as $cat => $items): ?>
            <div class="equip-section"><?= htmlspecialchars($cat) ?></div>
            <div class="equip-grid">
              <?php foreach ($items as $eq): ?>
                <div class="equip-item">
                  <span class="equip-check">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                  </span>
                  <?= htmlspecialchars($eq) ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

      </div>

      <!-- SIDEBAR -->
      <div class="sidebar">

        <!-- VENDEUR -->
        <div class="seller-card">
          <div class="seller-h">
            <div class="seller-avatar"><?= $initiale ?></div>
            <div>
              <div class="seller-info-name"><?= htmlspecialchars($nomVendeur) ?></div>
              <div class="seller-info-type">
                <?= ucfirst($a['typeVendeur'] ?: 'Particulier') ?>
                <?php if ($a['badgeVerifie']): ?>
                  <span class="verif-badge">
                    <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    Vérifié
                  </span>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="seller-stats">
            <div class="seller-stat">
              <span>Membre depuis</span>
              <strong><?= $a['vendeur_date'] ? dateFr($a['vendeur_date']) : '—' ?></strong>
            </div>
            <div class="seller-stat">
              <span>Annonces actives</span>
              <strong><?= $a['nbrAnnonceAct'] ?: 1 ?></strong>
            </div>
            <div class="seller-stat">
              <span>Temps de réponse</span>
              <strong>~ 2 heures</strong>
            </div>
          </div>

          <div class="contact-btns">
            <?php if ($a['vendeur_tel']): ?>
              <button class="btn-primary" id="btn-phone" onclick="revealPhone()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                Afficher le numéro
              </button>
              <div id="phone-display" class="phone-display" style="display:none">
                <?= htmlspecialchars($a['vendeur_tel']) ?>
              </div>
            <?php endif; ?>

            <a href="messagerie.php?vendeur=<?= urlencode($a['idVendeur']) ?>&annonce=<?= urlencode($idAnnonce) ?>" class="btn-msg">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
              Envoyer un message
            </a>

            <button class="btn-secondary <?= $estFavori ? 'faved' : '' ?>" id="btn-fav" onclick="toggleFavori()">
              <svg width="14" height="14" fill="<?= $estFavori ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11.29 20.66c.2.2.45.29.71.29s.51-.1.71-.29l7.5-7.5c2.35-2.35 2.35-6.05 0-8.41-2.3-2.28-5.85-2.35-8.21-.2-2.36-2.15-5.91-2.09-8.21.2-2.35 2.36-2.35 6.06 0 8.41z"/></svg>
              <span id="fav-text"><?= $estFavori ? 'Retirer des favoris' : 'Ajouter aux favoris' ?></span>
            </button>
          </div>
        </div>

        <!-- LOCALISATION -->
        <?php if ($a['localisation']): ?>
        <div class="location-card">
          <div class="loc-h">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Localisation
          </div>
          <div class="loc-val"><strong><?= htmlspecialchars($a['localisation']) ?></strong></div>
          <a href="https://www.google.com/maps/search/<?= urlencode($a['localisation']) ?>" target="_blank" style="text-decoration:none;color:inherit">
            <div class="loc-map">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <span style="margin-left:8px">Voir sur la carte</span>
            </div>
          </a>
        </div>
        <?php endif; ?>

        <!-- ACTIONS / STATS -->
        <div class="actions-card">
          <div class="action-row">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Publiée <?= diffDate($a['datePublication']) ?>
          </div>
          <div class="action-row">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <?= number_format($a['nbrVus'] ?: 0, 0, ',', ' ') ?> vues · <?= $nbFavoris ?> favoris
          </div>
          <div class="action-row">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg>
            Réf. <?= $refCourte ?>
          </div>
        </div>

        <!-- TIPS -->
        <div class="tips-card">
          <div class="tips-h">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Conseils sécurité
          </div>
          <div class="tips-text">
            Rencontrez le vendeur dans un lieu public. Vérifiez tous les documents avant de payer. Ne versez jamais d'acompte sans avoir vu le véhicule.
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- LIGHTBOX -->
  <div class="lightbox" id="lightbox" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="event.stopPropagation();closeLightbox()">×</button>
    <?php if ($nbPhotos > 1): ?>
      <button class="lightbox-nav lightbox-prev" onclick="event.stopPropagation();prevImg()">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg>
      </button>
      <button class="lightbox-nav lightbox-next" onclick="event.stopPropagation();nextImg()">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
      </button>
    <?php endif; ?>
    <img id="lightbox-img" src="" alt="" onclick="event.stopPropagation()">
    <div class="lightbox-counter" id="lightbox-counter">1 / <?= $nbPhotos ?></div>
  </div>

  <!-- TOAST -->
  <div class="toast" id="toast"></div>

  <script>
    /* ════════════════════════════════════════════════════════ */
    /* ═══   GALERIE                                          ═══ */
    /* ════════════════════════════════════════════════════════ */
    const PHOTOS = <?= json_encode(array_map(fn($p) => $p['urlPhoto'], $photos)) ?>;
    const NB_PHOTOS = PHOTOS.length;
    let currentImg = 0;

    function setMainImg(idx) {
      if (idx < 0 || idx >= NB_PHOTOS) return;
      currentImg = idx;
      const mainImg = document.getElementById('main-img');
      if (mainImg) mainImg.src = PHOTOS[idx];
      
      document.querySelectorAll('.thumb').forEach((t, i) => {
        t.classList.toggle('active', i === idx);
      });

      const counter = document.getElementById('img-counter-text');
      if (counter) counter.textContent = `${idx + 1} / ${NB_PHOTOS} photos`;

      const lbCounter = document.getElementById('lightbox-counter');
      if (lbCounter) lbCounter.textContent = `${idx + 1} / ${NB_PHOTOS}`;
      
      const lbImg = document.getElementById('lightbox-img');
      if (lbImg) lbImg.src = PHOTOS[idx];
    }

    function nextImg() {
      setMainImg((currentImg + 1) % NB_PHOTOS);
    }
    function prevImg() {
      setMainImg((currentImg - 1 + NB_PHOTOS) % NB_PHOTOS);
    }

    /* ════════════════════════════════════════════════════════ */
    /* ═══   LIGHTBOX                                         ═══ */
    /* ════════════════════════════════════════════════════════ */
    function openLightbox(idx) {
      if (NB_PHOTOS === 0) return;
      const lb = document.getElementById('lightbox');
      const lbImg = document.getElementById('lightbox-img');
      lbImg.src = PHOTOS[idx];
      document.getElementById('lightbox-counter').textContent = `${idx + 1} / ${NB_PHOTOS}`;
      lb.classList.add('show');
      document.body.style.overflow = 'hidden';
    }
    function closeLightbox() {
      document.getElementById('lightbox').classList.remove('show');
      document.body.style.overflow = '';
    }

    document.addEventListener('keydown', (e) => {
      const lb = document.getElementById('lightbox');
      if (lb && lb.classList.contains('show')) {
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') prevImg();
        if (e.key === 'ArrowRight') nextImg();
      } else {
        if (e.key === 'ArrowLeft' && NB_PHOTOS > 1) prevImg();
        if (e.key === 'ArrowRight' && NB_PHOTOS > 1) nextImg();
      }
    });

    /* ════════════════════════════════════════════════════════ */
    /* ═══   TÉLÉPHONE                                        ═══ */
    /* ════════════════════════════════════════════════════════ */
    function revealPhone() {
      <?php if (!isset($_SESSION['idUtilisateur'])): ?>
        if (!confirm("Vous devez être connecté pour voir le numéro. Aller à la page de connexion ?")) return;
        location.href = 'connexion.php?redirect=fiche_annonce.php?id=<?= urlencode($idAnnonce) ?>';
        return;
      <?php endif; ?>

      document.getElementById('btn-phone').style.display = 'none';
      document.getElementById('phone-display').style.display = 'block';
      
      /* Ping serveur pour stats */
      fetch('action_annonce.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=phone_view&idAnnonce=<?= urlencode($idAnnonce) ?>'
      }).catch(() => {});
    }

    /* ════════════════════════════════════════════════════════ */
    /* ═══   FAVORIS                                          ═══ */
    /* ════════════════════════════════════════════════════════ */
    function toggleFavori() {
      const fd = new FormData();
      fd.append('action', 'toggle');
      fd.append('idAnnonce', '<?= htmlspecialchars($idAnnonce) ?>');

      fetch('toggle_favori.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
          if (json.needLogin) {
            location.href = 'connexion.php?redirect=fiche_annonce.php?id=<?= urlencode($idAnnonce) ?>';
            return;
          }

          const imgFav = document.getElementById('img-fav-btn');
          const btnFav = document.getElementById('btn-fav');
          const txtFav = document.getElementById('fav-text');
          const isFav = json.favori;

          if (imgFav) {
            imgFav.classList.toggle('faved', isFav);
            imgFav.querySelector('svg').setAttribute('fill', isFav ? 'currentColor' : 'none');
          }
          if (btnFav) {
            btnFav.classList.toggle('faved', isFav);
            btnFav.querySelector('svg').setAttribute('fill', isFav ? 'currentColor' : 'none');
          }
          if (txtFav) txtFav.textContent = isFav ? 'Retirer des favoris' : 'Ajouter aux favoris';

          showToast(isFav ? '❤️ Ajouté à vos favoris' : 'Retiré des favoris');
        })
        .catch(() => showToast('Erreur, réessayez'));
    }

    /* ════════════════════════════════════════════════════════ */
    /* ═══   ACTIONS                                          ═══ */
    /* ════════════════════════════════════════════════════════ */
    function sharePage() {
      const url = window.location.href;
      if (navigator.share) {
        navigator.share({
          title: <?= json_encode($a['titre']) ?>,
          text: 'Regarde cette annonce sur AUTOMARKET',
          url: url
        }).catch(() => {});
      } else {
        navigator.clipboard.writeText(url).then(() => {
          showToast('🔗 Lien copié dans le presse-papier');
        });
      }
    }

    function signaler() {
      <?php if (!isset($_SESSION['idUtilisateur'])): ?>
        if (!confirm("Vous devez être connecté pour signaler. Aller à la connexion ?")) return;
        location.href = 'connexion.php?redirect=fiche_annonce.php?id=<?= urlencode($idAnnonce) ?>';
        return;
      <?php endif; ?>
      
      const motif = prompt("Pourquoi signalez-vous cette annonce ?\n\n(arnaque, contenu inapproprié, doublon, etc.)");
      if (!motif || motif.trim().length < 5) return;

      const fd = new FormData();
      fd.append('action', 'signaler');
      fd.append('idAnnonce', '<?= htmlspecialchars($idAnnonce) ?>');
      fd.append('motif', motif);

      fetch('action_annonce.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
          if (json.success) {
            showToast('✓ Signalement envoyé. Merci !');
          } else {
            showToast(json.message || 'Erreur');
          }
        })
        .catch(() => showToast('Erreur réseau'));
    }

    /* ════════════════════════════════════════════════════════ */
    /* ═══   TOAST                                            ═══ */
    /* ════════════════════════════════════════════════════════ */
    let toastTimer;
    function showToast(msg) {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.classList.add('show');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(() => t.classList.remove('show'), 2500);
    }
  </script>
</body>
</html>
<?php
session_start();
require_once 'connexion.php';

/* CAPTURE DES FILTRES */
$f = [
    'q'         => trim($_GET['q'] ?? ''),
    'type'      => $_GET['type'] ?? '',
    'marque'    => $_GET['marque'] ?? '',
    'modele'    => $_GET['modele'] ?? '',
    'annee_min' => $_GET['annee_min'] ?? '',
    'annee_max' => $_GET['annee_max'] ?? '',
    'prix_min'  => $_GET['prix_min'] ?? '',
    'prix_max'  => $_GET['prix_max'] ?? '',
    'km_min'    => $_GET['km_min'] ?? '',
    'km_max'    => $_GET['km_max'] ?? '',
    'carburant' => $_GET['carburant'] ?? [],
    'transmis'  => $_GET['transmis'] ?? '',
    'couleur'   => $_GET['couleur'] ?? '',
    'wilaya'    => $_GET['wilaya'] ?? '',
    'verifie'   => isset($_GET['verifie']),
    'sort'      => $_GET['sort'] ?? 'date_desc',
    'page'      => max(1, (int)($_GET['page'] ?? 1)),
];
if (is_string($f['carburant'])) $f['carburant'] = $f['carburant'] ? [$f['carburant']] : [];

$perPage = 20;
$offset = ($f['page'] - 1) * $perPage;

/* CONSTRUCTION DU WHERE */
$conditions = ["a.statutAnnonce = 'active'"];
$esc = fn($v) => mysqli_real_escape_string($conn, $v);

if ($f['q']) {
    $q = $esc($f['q']);
    $conditions[] = "(a.titre LIKE '%$q%' OR a.description LIKE '%$q%' OR m.nomMarque LIKE '%$q%' OR mo.nomModele LIKE '%$q%')";
}
if ($f['type'])      $conditions[] = "v.typeVehicule = '" . $esc($f['type']) . "'";
if ($f['marque'])    $conditions[] = "m.nomMarque = '" . $esc($f['marque']) . "'";
if ($f['modele'])    $conditions[] = "mo.nomModele = '" . $esc($f['modele']) . "'";
if ($f['annee_min']) $conditions[] = "v.annee >= " . (int)$f['annee_min'];
if ($f['annee_max']) $conditions[] = "v.annee <= " . (int)$f['annee_max'];
if ($f['prix_min'])  $conditions[] = "a.prix >= " . (float)$f['prix_min'];
if ($f['prix_max'])  $conditions[] = "a.prix <= " . (float)$f['prix_max'];
if ($f['km_min'])    $conditions[] = "v.kilometrage >= " . (int)$f['km_min'];
if ($f['km_max'])    $conditions[] = "v.kilometrage <= " . (int)$f['km_max'];
if (!empty($f['carburant'])) {
    $list = array_map(fn($c) => "'" . $esc($c) . "'", $f['carburant']);
    $conditions[] = "v.carburant IN (" . implode(',', $list) . ")";
}
if ($f['transmis'])  $conditions[] = "v.transmission = '" . $esc($f['transmis']) . "'";
if ($f['couleur'])   $conditions[] = "v.couleur LIKE '%" . $esc($f['couleur']) . "%'";
if ($f['wilaya'])    $conditions[] = "a.localisation LIKE '%" . $esc($f['wilaya']) . "%'";
if ($f['verifie'])   $conditions[] = "u.badgeVerifie = 1";

$where = implode(' AND ', $conditions);

$orderBy = match($f['sort']) {
    'prix_asc'    => 'a.prix ASC',
    'prix_desc'   => 'a.prix DESC',
    'km_asc'      => 'v.kilometrage ASC',
    'annee_desc'  => 'v.annee DESC',
    default       => 'a.datePublication DESC'
};

$sqlBase = "
    FROM Annonce a
    LEFT JOIN Vehicule v ON a.idVehicule = v.idVehicule
    LEFT JOIN Utilisateur u ON a.idVendeur = u.idUtilisateur
    LEFT JOIN Modele mo ON v.idModele = mo.idModele
    LEFT JOIN Marque m ON mo.idMarque = m.idMarque
    LEFT JOIN Vendeur ven ON ven.idUtilisateur = u.idUtilisateur
    WHERE $where
";

$rTotal = mysqli_query($conn, "SELECT COUNT(*) AS nb $sqlBase");
$totalAnnonces = $rTotal ? (int)mysqli_fetch_assoc($rTotal)['nb'] : 0;
$totalPages = max(1, (int)ceil($totalAnnonces / $perPage));

$rAvg = mysqli_query($conn, "SELECT AVG(a.prix) AS moy $sqlBase");
$prixMoyen = $rAvg ? (int)(mysqli_fetch_assoc($rAvg)['moy'] ?? 0) : 0;

$sql = "
    SELECT 
        a.idAnnonce, a.titre, a.prix, a.localisation, a.datePublication,
        a.vendeurVerif, a.idVendeur,
        v.typeVehicule, v.annee, v.kilometrage, v.carburant,
        v.transmission, v.puissance, v.couleur, v.etatVehicule,
        u.nom AS vendeur_nom, u.prenom AS vendeur_prenom, u.badgeVerifie,
        m.nomMarque, mo.nomModele,
        ven.typeVendeur,
        (SELECT urlPhoto FROM Photos WHERE idAnnonce = a.idAnnonce ORDER BY ordrePhoto ASC LIMIT 1) AS photo,
        (SELECT COUNT(*) FROM Photos WHERE idAnnonce = a.idAnnonce) AS nbPhotos
    $sqlBase
    ORDER BY $orderBy
    LIMIT $offset, $perPage
";
$res = mysqli_query($conn, $sql);
$annonces = [];
if ($res) while ($row = mysqli_fetch_assoc($res)) $annonces[] = $row;

/* Compteurs filtres */
function countBy($conn, $sqlBase, $field) {
    $rs = mysqli_query($conn, "SELECT $field AS val, COUNT(*) AS nb $sqlBase GROUP BY $field");
    $out = [];
    if ($rs) while ($row = mysqli_fetch_assoc($rs)) {
        if ($row['val']) $out[$row['val']] = $row['nb'];
    }
    return $out;
}
$countCarburant = countBy($conn, $sqlBase, 'v.carburant');

/* Favoris user */
$myFavoris = [];
if (isset($_SESSION['idUtilisateur'])) {
    $idU = $esc($_SESSION['idUtilisateur']);
    $rFav = mysqli_query($conn, "SELECT idAnnonce FROM Favoris WHERE idUtilisateur = '$idU'");
    if ($rFav) while ($f2 = mysqli_fetch_assoc($rFav)) $myFavoris[] = $f2['idAnnonce'];
}

/* Messages non lus */
$nbMessagesNonLus = 0;
if (isset($_SESSION['idUtilisateur'])) {
    $idU = $esc($_SESSION['idUtilisateur']);
    $rMsg = mysqli_query($conn, "
        SELECT COUNT(*) AS nb 
        FROM Message msg 
        JOIN Conversation c ON msg.idConversation = c.idConversation
        WHERE (c.idAcheteur = '$idU' OR c.idVendeur = '$idU')
        AND msg.idUtilisateur != '$idU'
        AND msg.statutLecture = 0
    ");
    if ($rMsg) $nbMessagesNonLus = (int)(mysqli_fetch_assoc($rMsg)['nb'] ?? 0);
}

/* Helpers */
function timeAgo($d) {
    $diff = (new DateTime())->diff(new DateTime($d))->days;
    if ($diff == 0) return "Aujourd'hui";
    if ($diff == 1) return "Hier";
    if ($diff < 30) return "Il y a $diff jours";
    return (new DateTime($d))->format('d/m/Y');
}
function buildUrl($overrides = [], $remove = []) {
    global $f;
    $params = $f;
    foreach ($overrides as $k => $v) $params[$k] = $v;
    foreach ($remove as $k) unset($params[$k]);
    unset($params['page']);
    if (isset($params['carburant']) && empty($params['carburant'])) unset($params['carburant']);
    if (!isset($params['verifie']) || !$params['verifie']) unset($params['verifie']);
    return 'recherche.php?' . http_build_query($params);
}
function colorAvatar($id) {
    $colors = ['#185FA5', '#639922', '#BA7517', '#7F77DD', '#D85A30', '#1D9E75', '#993556'];
    $hash = 0;
    for ($i = 0; $i < strlen($id ?? ''); $i++) $hash += ord($id[$i]);
    return $colors[$hash % count($colors)];
}

/* Marques disponibles */
$rMarques = mysqli_query($conn, "
    SELECT DISTINCT m.nomMarque 
    FROM Marque m 
    JOIN Modele mo ON mo.idMarque = m.idMarque 
    JOIN Vehicule v ON v.idModele = mo.idModele 
    JOIN Annonce a ON a.idVehicule = v.idVehicule
    WHERE a.statutAnnonce = 'active'
    ORDER BY m.nomMarque ASC
");
$marquesDispo = [];
if ($rMarques) while ($r = mysqli_fetch_assoc($rMarques)) $marquesDispo[] = $r['nomMarque'];

$modelesDispo = [];
if ($f['marque']) {
    $marqueEsc = $esc($f['marque']);
    $rModeles = mysqli_query($conn, "
        SELECT DISTINCT mo.nomModele 
        FROM Modele mo 
        JOIN Marque m ON mo.idMarque = m.idMarque
        WHERE m.nomMarque = '$marqueEsc'
        ORDER BY mo.nomModele ASC
    ");
    if ($rModeles) while ($r = mysqli_fetch_assoc($rModeles)) $modelesDispo[] = $r['nomModele'];
}

$wilayas = ['Adrar','Aïn Defla','Aïn Témouchent','Alger','Annaba','Batna','Béchar','Béjaïa','Biskra','Blida','Bordj Bou Arreridj','Bouira','Boumerdès','Chlef','Constantine','Djelfa','El Bayadh','El Oued','El Tarf','Ghardaïa','Guelma','Illizi','Jijel','Khenchela','Laghouat','Mascara','Médéa','Mila','Mostaganem','M\'Sila','Naâma','Oran','Ouargla','Oum El Bouaghi','Relizane','Saïda','Sétif','Sidi Bel Abbès','Skikda','Souk Ahras','Tamanrasset','Tébessa','Tiaret','Tindouf','Tipaza','Tissemsilt','Tizi Ouzou','Tlemcen'];

/* Chips actifs */
$activeChips = [];
if ($f['type'])      $activeChips[] = ['label' => ucfirst($f['type']), 'remove' => 'type'];
if ($f['marque'])    $activeChips[] = ['label' => $f['marque'], 'remove' => 'marque'];
if ($f['modele'])    $activeChips[] = ['label' => $f['modele'], 'remove' => 'modele'];
if ($f['annee_min'] || $f['annee_max']) {
    $lbl = ($f['annee_min'] ?: '...') . ' - ' . ($f['annee_max'] ?: '...');
    $activeChips[] = ['label' => $lbl, 'remove' => ['annee_min','annee_max']];
}
if ($f['prix_min'] || $f['prix_max']) {
    $lbl = number_format((float)($f['prix_min']?:0),0,',',' ') . ' - ' . number_format((float)($f['prix_max']?:0),0,',',' ') . ' DA';
    $activeChips[] = ['label' => $lbl, 'remove' => ['prix_min','prix_max']];
}
if ($f['km_max']) $activeChips[] = ['label' => '≤ ' . number_format((int)$f['km_max'],0,',',' ') . ' km', 'remove' => 'km_max'];
foreach ($f['carburant'] as $carb) {
    $activeChips[] = ['label' => $carb, 'remove' => "carburant_$carb"];
}
if ($f['transmis']) $activeChips[] = ['label' => $f['transmis'], 'remove' => 'transmis'];
if ($f['couleur'])  $activeChips[] = ['label' => $f['couleur'], 'remove' => 'couleur'];
if ($f['wilaya'])   $activeChips[] = ['label' => $f['wilaya'], 'remove' => 'wilaya'];
if ($f['verifie'])  $activeChips[] = ['label' => '✓ Vérifié', 'remove' => 'verifie'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recherche véhicules — AUTOMARKET</title>
  <link rel="icon" href="images/logo.png">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --blue: #185FA5; --blue-dk: #0C447C; --blue-bg: #E6F1FB; --blue-bd: #B5D4F4;
      --bg0: #ffffff; --bg1: #f5f4f0; --bg2: #eceae4;
      --t1: #1a1a18; --t2: #5f5e5a; --t3: #888780;
      --bd: rgba(0,0,0,0.11); --bd2: rgba(0,0,0,0.22);
      --green: #639922; --green-bg: #EAF3DE; --green-dk: #27500A; --green-bd: #C0DD97;
      --red: #E24B4A; --orange: #FFA366;
      --r6: 6px; --r8: 8px; --r10: 10px;
    }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 13px; background: var(--bg1); color: var(--t1); min-height: 100vh; }

    .nav { background: var(--bg0); border-bottom: 0.5px solid var(--bd); height: 52px; display: flex; align-items: center; padding: 0 20px; gap: 14px; position: sticky; top: 0; z-index: 100; }
    .logo img { height: 32px; }
    .nav-search-mini { flex: 1; max-width: 360px; height: 34px; border: 0.5px solid var(--bd2); border-radius: 17px; padding: 0 12px 0 32px; background: var(--bg1); display: flex; align-items: center; position: relative; }
    .nav-search-mini svg { position: absolute; left: 11px; color: var(--t3); }
    .nav-search-mini input { flex: 1; border: none; background: transparent; outline: none; font-family: inherit; font-size: 13px; color: var(--t1); width: 100%; }
    .nav-actions { margin-left: auto; display: flex; gap: 6px; align-items: center; }
    .nav-icon-btn { width: 34px; height: 34px; border-radius: 50%; border: 0.5px solid var(--bd); background: var(--bg0); display: flex; align-items: center; justify-content: center; color: var(--t2); cursor: pointer; position: relative; text-decoration: none; }
    .nav-icon-btn:hover { color: var(--blue); border-color: var(--blue); }
    .nav-icon-badge { position: absolute; top: -3px; right: -3px; background: var(--red); color: #fff; font-size: 9px; font-weight: 600; padding: 1px 5px; border-radius: 8px; min-width: 16px; text-align: center; line-height: 1.2; border: 1.5px solid var(--bg0); }
    .user-mini { display: flex; align-items: center; gap: 6px; padding: 4px 10px 4px 4px; border-radius: 18px; background: var(--bg1); cursor: pointer; text-decoration: none; color: var(--t1); font-size: 12px; }
    .user-mini-avatar { width: 26px; height: 26px; border-radius: 50%; background: var(--blue); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 500; }

    .container { max-width: 1280px; margin: 0 auto; padding: 14px 16px 40px; }

    .breadcrumb { font-size: 11px; color: var(--t3); margin-bottom: 12px; padding: 0 4px; }
    .breadcrumb a { color: var(--t3); text-decoration: none; }
    .breadcrumb a:hover { color: var(--blue); }
    .breadcrumb .sep { margin: 0 5px; }
    .breadcrumb .active { color: var(--t1); font-weight: 500; }

    .result-head { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; background: var(--bg0); border: 0.5px solid var(--bd); border-radius: var(--r10); margin-bottom: 12px; flex-wrap: wrap; gap: 10px; }
    .result-title { font-size: 18px; font-weight: 600; }
    .result-count { font-size: 12px; color: var(--t2); margin-top: 3px; }
    .result-count strong { color: var(--blue); }
    .result-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .save-search-btn { font-size: 12px; padding: 7px 14px; background: var(--blue-bg); color: var(--blue-dk); border: 0.5px solid var(--blue-bd); border-radius: 16px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; font-family: inherit; }
    .save-search-btn:hover { background: var(--blue-bd); }
    .sort-sel { height: 32px; border: 0.5px solid var(--bd2); border-radius: var(--r6); padding: 0 26px 0 10px; font-size: 12px; background: var(--bg0); cursor: pointer; outline: none; font-family: inherit; }

    .active-filters { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; padding: 0 4px; }
    .active-filters-label { font-size: 11px; color: var(--t3); font-weight: 500; }
    .chip { display: inline-flex; align-items: center; gap: 5px; padding: 4px 6px 4px 12px; background: var(--bg0); border: 0.5px solid var(--bd2); border-radius: 14px; font-size: 11px; color: var(--t1); text-decoration: none; }
    .chip-close { width: 16px; height: 16px; border-radius: 50%; background: var(--bg2); color: var(--t2); display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 600; }
    .chip:hover .chip-close { background: var(--red); color: #fff; }
    .clear-all { font-size: 11px; color: var(--red); cursor: pointer; padding: 4px 8px; font-weight: 500; text-decoration: none; }

    .layout-grid { display: grid; grid-template-columns: 270px 1fr; gap: 14px; }

    .sidebar { display: flex; flex-direction: column; gap: 10px; align-self: flex-start; position: sticky; top: 60px; max-height: calc(100vh - 70px); overflow-y: auto; padding-right: 4px; }
    .sidebar::-webkit-scrollbar { width: 5px; }
    .sidebar::-webkit-scrollbar-thumb { background: var(--bd2); border-radius: 3px; }

    .filter-card { background: var(--bg0); border: 0.5px solid var(--bd); border-radius: var(--r8); overflow: hidden; }
    .filter-h { padding: 11px 14px; font-size: 12px; font-weight: 600; border-bottom: 0.5px solid var(--bd); display: flex; align-items: center; justify-content: space-between; cursor: pointer; user-select: none; }
    .filter-h::after { content: '−'; font-size: 14px; color: var(--t3); }
    .filter-h.collapsed::after { content: '+'; }
    .filter-h.collapsed + .filter-body { display: none; }
    .filter-body { padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; }

    .filter-input, .filter-select { width: 100%; height: 32px; border: 0.5px solid var(--bd2); border-radius: var(--r6); padding: 0 8px; font-size: 12px; background: var(--bg0); font-family: inherit; outline: none; }
    .filter-input:focus, .filter-select:focus { border-color: var(--blue); box-shadow: 0 0 0 2px rgba(24,95,165,0.1); }

    .filter-row { display: flex; gap: 6px; align-items: center; }
    .filter-row .filter-input { flex: 1; min-width: 0; }
    .filter-arrow { color: var(--t3); font-size: 12px; }

    .checkbox-row { display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 4px 0; font-size: 12px; user-select: none; }
    .checkbox-row input { display: none; }
    .checkbox-fake { width: 14px; height: 14px; border: 0.5px solid var(--bd2); border-radius: 3px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--bg0); }
    .checkbox-row input:checked + .checkbox-fake { background: var(--blue); border-color: var(--blue); }
    .checkbox-row input:checked + .checkbox-fake::after { content: ''; width: 4px; height: 7px; border: 1.5px solid #fff; border-top: none; border-left: none; transform: rotate(45deg) translateY(-1px); }
    .checkbox-count { margin-left: auto; font-size: 10px; color: var(--t3); }

    .pill-group { display: flex; flex-wrap: wrap; gap: 4px; }
    .pill { padding: 5px 11px; font-size: 11px; background: var(--bg1); border: 0.5px solid var(--bd); border-radius: 12px; cursor: pointer; text-decoration: none; color: var(--t1); }
    .pill:hover { border-color: var(--blue); color: var(--blue); }
    .pill.active { background: var(--blue-bg); color: var(--blue-dk); border-color: var(--blue-bd); font-weight: 500; }

    .color-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 6px; }
    .color-dot { aspect-ratio: 1; border-radius: 50%; border: 1px solid var(--bd); cursor: pointer; position: relative; text-decoration: none; }
    .color-dot.active { box-shadow: 0 0 0 2px var(--blue); }
    .color-dot.active::after { content: '✓'; position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 11px; font-weight: 700; text-shadow: 0 0 3px rgba(0,0,0,0.5); }

    .apply-btn { background: var(--blue); color: #fff; border: none; padding: 11px; border-radius: var(--r8); font-size: 13px; font-weight: 600; cursor: pointer; width: 100%; font-family: inherit; }
    .apply-btn:hover { background: var(--blue-dk); }

    .results { display: flex; flex-direction: column; gap: 10px; }

    .ad-card { display: flex; background: var(--bg0); border: 0.5px solid var(--bd); border-radius: var(--r10); overflow: hidden; cursor: pointer; transition: all .15s; position: relative; text-decoration: none; color: var(--t1); }
    .ad-card:hover { border-color: var(--blue); box-shadow: 0 0 0 2px rgba(24,95,165,0.08); }
    .ad-card.deal { border-color: var(--orange); }
    .ad-card.deal::before { content: '⭐ DEAL'; position: absolute; top: 8px; left: 8px; background: var(--orange); color: #fff; font-size: 10px; font-weight: 700; padding: 3px 9px; border-radius: 11px; z-index: 2; letter-spacing: 0.4px; }

    .card-img { width: 240px; flex-shrink: 0; aspect-ratio: 4/3; background: linear-gradient(135deg, #c4c8d0, #a8b1bc); position: relative; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.5); overflow: hidden; }
    .card-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .card-img-count { position: absolute; bottom: 8px; left: 8px; background: rgba(0,0,0,0.65); color: #fff; font-size: 10px; padding: 3px 8px; border-radius: 10px; backdrop-filter: blur(4px); display: flex; align-items: center; gap: 4px; }
    .card-fav { position: absolute; top: 8px; right: 8px; width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.95); border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--t2); transition: all .15s; z-index: 2; }
    .card-fav:hover { background: var(--red); color: #fff; }
    .card-fav.faved { color: var(--red); }
    .card-fav.faved svg { fill: currentColor; }

    .card-body { flex: 1; padding: 12px 16px; display: flex; flex-direction: column; min-width: 0; }
    .card-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 6px; }
    .card-title { font-size: 15px; font-weight: 600; line-height: 1.25; }
    .card-subtitle { font-size: 12px; color: var(--t2); margin-top: 3px; }
    .card-price-block { text-align: right; flex-shrink: 0; }
    .card-price { font-size: 18px; font-weight: 700; color: var(--blue); line-height: 1; }
    .card-price-info { font-size: 11px; color: var(--green); font-weight: 500; margin-top: 4px; }

    .card-specs { display: flex; flex-wrap: wrap; gap: 8px; margin: 8px 0; align-items: center; }
    .spec { font-size: 11px; color: var(--t2); display: inline-flex; align-items: center; gap: 4px; }
    .spec-dot { width: 2px; height: 2px; border-radius: 50%; background: var(--t3); }

    .card-tags { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 8px; }
    .card-tag { font-size: 10px; padding: 2px 8px; border-radius: 8px; background: var(--bg1); color: var(--t2); border: 0.5px solid var(--bd); }
    .card-tag.green { background: var(--green-bg); color: var(--green-dk); border-color: var(--green-bd); }

    .card-foot { display: flex; align-items: center; justify-content: space-between; margin-top: auto; padding-top: 8px; border-top: 0.5px solid var(--bd); font-size: 11px; color: var(--t3); gap: 10px; flex-wrap: wrap; }
    .card-seller { display: flex; align-items: center; gap: 6px; color: var(--t2); }
    .seller-avatar { width: 20px; height: 20px; border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 500; }
    .pro-badge { background: var(--blue-bg); color: var(--blue-dk); font-size: 9px; padding: 1px 5px; border-radius: 8px; border: 0.5px solid var(--blue-bd); }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 4px; margin-top: 14px; padding: 12px; }
    .page-btn { min-width: 32px; height: 32px; border: 0.5px solid var(--bd2); background: var(--bg0); border-radius: var(--r6); font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; text-decoration: none; color: var(--t1); padding: 0 8px; }
    .page-btn:hover { border-color: var(--blue); color: var(--blue); }
    .page-btn.active { background: var(--blue); color: #fff; border-color: var(--blue); font-weight: 600; }
    .page-btn.disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; }
    .page-dots { color: var(--t3); padding: 0 4px; }

    .empty-state { padding: 60px 30px; text-align: center; background: var(--bg0); border: 0.5px solid var(--bd); border-radius: var(--r10); }
    .empty-state svg { color: var(--t3); margin-bottom: 14px; opacity: 0.4; }
    .empty-state h3 { font-size: 15px; margin-bottom: 6px; }
    .empty-state p { font-size: 12px; color: var(--t2); }

    .toast { position: fixed; bottom: 24px; right: 24px; background: var(--t1); color: #fff; padding: 12px 18px; border-radius: var(--r8); font-size: 13px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 1000; opacity: 0; transform: translateY(20px); transition: all .3s; }
    .toast.show { opacity: 1; transform: translateY(0); }

    @media (max-width: 900px) {
      .layout-grid { grid-template-columns: 1fr; }
      .sidebar { position: static; max-height: none; display: none; }
      .sidebar.show { display: flex; }
      .mobile-filter-btn { display: inline-flex !important; }
    }
    .mobile-filter-btn { display: none; }
    @media (max-width: 600px) {
      .container { padding: 10px; }
      .nav-search-mini { display: none; }
      .ad-card { flex-direction: column; }
      .card-img { width: 100%; aspect-ratio: 16/10; }
      .result-head { padding: 12px; }
      .result-title { font-size: 16px; }
      .card-top { flex-direction: column; }
      .card-price-block { text-align: left; }
    }
  </style>
</head>
<body>

  <nav class="nav">
    <a class="logo" href="index.php">
      <img src="images/logo.png" alt="AUTOMARKET">
    </a>

    <form class="nav-search-mini" action="recherche.php" method="GET">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" name="q" id="nav-q" placeholder="Rechercher un véhicule..." value="<?= htmlspecialchars($f['q']) ?>">
    </form>

    <div class="nav-actions">
      <?php if (isset($_SESSION['idUtilisateur'])): ?>
        <a href="messagerie.php" class="nav-icon-btn" title="Messages">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          <?php if ($nbMessagesNonLus > 0): ?>
            <span class="nav-icon-badge"><?= $nbMessagesNonLus > 9 ? '9+' : $nbMessagesNonLus ?></span>
          <?php endif; ?>
        </a>
        <a href="favoris.php" class="nav-icon-btn" title="Favoris">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M11.29 20.66c.2.2.45.29.71.29s.51-.1.71-.29l7.5-7.5c2.35-2.35 2.35-6.05 0-8.41-2.3-2.28-5.85-2.35-8.21-.2-2.36-2.15-5.91-2.09-8.21.2-2.35 2.36-2.35 6.06 0 8.41z"/></svg>
        </a>
        <a href="monprofil.php" class="user-mini">
          <div class="user-mini-avatar"><?= strtoupper(substr($_SESSION['prenom'] ?? 'U', 0, 1)) ?></div>
          <span><?= htmlspecialchars($_SESSION['prenom'] ?? 'U') ?></span>
        </a>
      <?php else: ?>
        <a href="inscription.php" class="nav-icon-btn" style="width:auto;padding:0 14px;border-radius:var(--r6);font-size:13px;color:var(--blue);font-weight:500">Connexion</a>
      <?php endif; ?>
    </div>
  </nav>

  <div class="container">

    <div class="breadcrumb">
      <a href="index.php">Accueil</a>
      <?php if ($f['type']): ?>
        <span class="sep">›</span>
        <a href="recherche.php?type=<?= urlencode($f['type']) ?>"><?= ucfirst($f['type']) ?>s</a>
      <?php else: ?>
        <span class="sep">›</span><span class="active">Tous les véhicules</span>
      <?php endif; ?>
      <?php if ($f['marque']): ?>
        <span class="sep">›</span>
        <a href="recherche.php?marque=<?= urlencode($f['marque']) ?>"><?= htmlspecialchars($f['marque']) ?></a>
      <?php endif; ?>
      <?php if ($f['modele']): ?>
        <span class="sep">›</span>
        <span class="active"><?= htmlspecialchars($f['modele']) ?></span>
      <?php endif; ?>
    </div>

    <div class="result-head">
      <div>
        <div class="result-title">
          <?php
          $title = "Véhicules en Algérie";
          if ($f['marque'] && $f['modele']) $title = htmlspecialchars($f['marque']) . " " . htmlspecialchars($f['modele']) . " d'occasion";
          elseif ($f['marque']) $title = htmlspecialchars($f['marque']) . " d'occasion";
          elseif ($f['type']) $title = ucfirst($f['type']) . "s en Algérie";
          echo $title;
          ?>
        </div>
        <div class="result-count">
          <strong><?= number_format($totalAnnonces, 0, ',', ' ') ?> annonces</strong> trouvées
          <?php if ($prixMoyen > 0 && $totalAnnonces > 0): ?>
            · prix moyen <?= number_format($prixMoyen, 0, ',', ' ') ?> DA
          <?php endif; ?>
        </div>
      </div>
      <div class="result-actions">
        <button class="mobile-filter-btn nav-icon-btn" onclick="toggleSidebar()" style="width:auto;padding:0 14px;border-radius:var(--r6);font-size:12px">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="10" y1="18" x2="14" y2="18"/></svg>
          &nbsp;Filtres
        </button>
        <?php if (isset($_SESSION['idUtilisateur']) && $totalAnnonces > 0): ?>
        <button class="save-search-btn" onclick="saveSearch()">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
          Sauvegarder
        </button>
        <?php endif; ?>
        <select class="sort-sel" onchange="changeSort(this.value)">
          <option value="date_desc" <?= $f['sort']==='date_desc'?'selected':'' ?>>Plus récentes</option>
          <option value="prix_asc" <?= $f['sort']==='prix_asc'?'selected':'' ?>>Prix croissant</option>
          <option value="prix_desc" <?= $f['sort']==='prix_desc'?'selected':'' ?>>Prix décroissant</option>
          <option value="km_asc" <?= $f['sort']==='km_asc'?'selected':'' ?>>Kilométrage ↑</option>
          <option value="annee_desc" <?= $f['sort']==='annee_desc'?'selected':'' ?>>Année récente</option>
        </select>
      </div>
    </div>

    <?php if (!empty($activeChips)): ?>
    <div class="active-filters">
      <span class="active-filters-label">Filtres actifs :</span>
      <?php foreach ($activeChips as $chip):
        $rm = (array)$chip['remove'];
        $rmFlat = [];
        $overrides = [];
        foreach ($rm as $k) {
          if (str_starts_with($k, 'carburant_')) {
            $val = substr($k, 10);
            $newCarb = array_filter($f['carburant'], fn($c) => $c !== $val);
            $overrides['carburant'] = $newCarb;
          } else {
            $rmFlat[] = $k;
          }
        }
      ?>
        <a class="chip" href="<?= buildUrl($overrides, $rmFlat) ?>">
          <?= htmlspecialchars($chip['label']) ?>
          <span class="chip-close">×</span>
        </a>
      <?php endforeach; ?>
      <a class="clear-all" href="recherche.php">× Tout effacer</a>
    </div>
    <?php endif; ?>

    <div class="layout-grid">

      <aside class="sidebar" id="sidebar">
        <form id="filter-form" method="GET" action="recherche.php">
          <input type="hidden" name="q" value="<?= htmlspecialchars($f['q']) ?>">

          <div class="filter-card">
            <div class="filter-h">Type de véhicule</div>
            <div class="filter-body">
              <div class="pill-group">
                <a class="pill <?= !$f['type']?'active':'' ?>" href="<?= buildUrl([], ['type']) ?>">Tous</a>
                <a class="pill <?= $f['type']==='voiture'?'active':'' ?>" href="<?= buildUrl(['type'=>'voiture']) ?>">🚗 Voiture</a>
                <a class="pill <?= $f['type']==='moto'?'active':'' ?>" href="<?= buildUrl(['type'=>'moto']) ?>">🏍 Moto</a>
                <a class="pill <?= $f['type']==='camion'?'active':'' ?>" href="<?= buildUrl(['type'=>'camion']) ?>">🚛 Camion</a>
              </div>
            </div>
          </div>

          <div class="filter-card">
            <div class="filter-h">Marque & Modèle</div>
            <div class="filter-body">
              <select name="marque" class="filter-select" onchange="this.form.modele.value='';this.form.submit()">
                <option value="">Toutes les marques</option>
                <?php foreach ($marquesDispo as $mq): ?>
                  <option value="<?= htmlspecialchars($mq) ?>" <?= $f['marque']===$mq?'selected':'' ?>><?= htmlspecialchars($mq) ?></option>
                <?php endforeach; ?>
              </select>
              <select name="modele" class="filter-select" <?= !$f['marque']?'disabled':'' ?> onchange="this.form.submit()">
                <option value="">Tous les modèles</option>
                <?php foreach ($modelesDispo as $mo): ?>
                  <option value="<?= htmlspecialchars($mo) ?>" <?= $f['modele']===$mo?'selected':'' ?>><?= htmlspecialchars($mo) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="filter-card">
            <div class="filter-h">Prix (DA)</div>
            <div class="filter-body">
              <div class="filter-row">
                <input type="number" name="prix_min" class="filter-input" placeholder="Min" value="<?= htmlspecialchars($f['prix_min']) ?>">
                <span class="filter-arrow">→</span>
                <input type="number" name="prix_max" class="filter-input" placeholder="Max" value="<?= htmlspecialchars($f['prix_max']) ?>">
              </div>
            </div>
          </div>

          <div class="filter-card">
            <div class="filter-h">Année</div>
            <div class="filter-body">
              <div class="filter-row">
                <input type="number" name="annee_min" class="filter-input" placeholder="Min" min="1990" max="2026" value="<?= htmlspecialchars($f['annee_min']) ?>">
                <span class="filter-arrow">→</span>
                <input type="number" name="annee_max" class="filter-input" placeholder="Max" min="1990" max="2026" value="<?= htmlspecialchars($f['annee_max']) ?>">
              </div>
            </div>
          </div>

          <div class="filter-card">
            <div class="filter-h">Kilométrage</div>
            <div class="filter-body">
              <div class="filter-row">
                <input type="number" name="km_min" class="filter-input" placeholder="Min" value="<?= htmlspecialchars($f['km_min']) ?>">
                <span class="filter-arrow">→</span>
                <input type="number" name="km_max" class="filter-input" placeholder="Max" value="<?= htmlspecialchars($f['km_max']) ?>">
              </div>
            </div>
          </div>

          <div class="filter-card">
            <div class="filter-h">Carburant</div>
            <div class="filter-body">
              <?php foreach (['Essence','Diesel','Hybride','Électrique','GPL'] as $carb):
                $count = $countCarburant[$carb] ?? 0;
                $checked = in_array($carb, $f['carburant']);
              ?>
                <label class="checkbox-row">
                  <input type="checkbox" name="carburant[]" value="<?= $carb ?>" <?= $checked?'checked':'' ?> onchange="this.form.submit()">
                  <span class="checkbox-fake"></span>
                  <?= $carb ?>
                  <?php if ($count > 0): ?><span class="checkbox-count"><?= $count ?></span><?php endif; ?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="filter-card">
            <div class="filter-h">Transmission</div>
            <div class="filter-body">
              <div class="pill-group">
                <a class="pill <?= !$f['transmis']?'active':'' ?>" href="<?= buildUrl([], ['transmis']) ?>">Toutes</a>
                <a class="pill <?= $f['transmis']==='Manuelle'?'active':'' ?>" href="<?= buildUrl(['transmis'=>'Manuelle']) ?>">Manuelle</a>
                <a class="pill <?= $f['transmis']==='Automatique'?'active':'' ?>" href="<?= buildUrl(['transmis'=>'Automatique']) ?>">Automatique</a>
              </div>
            </div>
          </div>

          <div class="filter-card">
            <div class="filter-h">Couleur ext.</div>
            <div class="filter-body">
              <div class="color-grid">
                <?php
                $colors = [
                  'Blanc' => '#fff','Noir' => '#000','Gris' => '#94a3b8','Argent' => '#cbd5e1',
                  'Bleu' => '#1e3a8a','Rouge' => '#dc2626','Jaune' => '#facc15','Vert' => '#16a34a',
                  'Marron' => '#92400e','Orange' => '#fb923c','Beige' => '#d4c5a9','Or' => '#fbbf24'
                ];
                foreach ($colors as $name => $hex):
                  $isActive = $f['couleur'] === $name;
                  $url = $isActive ? buildUrl([], ['couleur']) : buildUrl(['couleur'=>$name]);
                ?>
                  <a class="color-dot <?= $isActive?'active':'' ?>" 
                     style="background:<?= $hex ?>;border:1px solid <?= $hex==='#fff'?'#ccc':$hex ?>" 
                     href="<?= $url ?>"
                     title="<?= $name ?>"></a>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="filter-card">
            <div class="filter-h">Localisation</div>
            <div class="filter-body">
              <select name="wilaya" class="filter-select" onchange="this.form.submit()">
                <option value="">Toutes les wilayas</option>
                <?php foreach ($wilayas as $w): ?>
                  <option value="<?= htmlspecialchars($w) ?>" <?= $f['wilaya']===$w?'selected':'' ?>><?= htmlspecialchars($w) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="filter-card">
            <div class="filter-h">Vendeur</div>
            <div class="filter-body">
              <label class="checkbox-row">
                <input type="checkbox" name="verifie" value="1" <?= $f['verifie']?'checked':'' ?> onchange="this.form.submit()">
                <span class="checkbox-fake"></span>
                Vendeurs vérifiés uniquement
              </label>
            </div>
          </div>

          <button type="submit" class="apply-btn">Appliquer (<?= number_format($totalAnnonces, 0, ',', ' ') ?>)</button>
        </form>
      </aside>

      <div class="results">

        <?php if (empty($annonces)): ?>
          <div class="empty-state">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <h3>Aucun résultat</h3>
            <p>Essayez de modifier vos filtres ou <a href="recherche.php" style="color:var(--blue)">réinitialiser la recherche</a></p>
          </div>
        <?php else:
          foreach ($annonces as $a):
            $isFav = in_array($a['idAnnonce'], $myFavoris);
            $isDeal = ($prixMoyen > 0 && $a['prix'] < $prixMoyen * 0.95);
            $titre = htmlspecialchars($a['titre']);
            $prix = number_format($a['prix'], 0, ',', ' ');
            $loc = htmlspecialchars($a['localisation']);
            $km = number_format($a['kilometrage'], 0, ',', ' ');
            $nomVend = trim(($a['vendeur_prenom'] ?? '') . ' ' . substr($a['vendeur_nom'] ?? '', 0, 1) . '.');
            $initVend = strtoupper(substr($a['vendeur_prenom'] ?? 'U', 0, 1));
            $colorVend = colorAvatar($a['idVendeur']);
            $sousTitre = trim(($a['etatVehicule']?:'') . ($a['nomMarque']?' · '.$a['nomMarque']:'') . ($a['nomModele']?' '.$a['nomModele']:''), ' ·');
            $diff = $prixMoyen > 0 ? $prixMoyen - $a['prix'] : 0;
        ?>
          <a class="ad-card <?= $isDeal?'deal':'' ?>" href="fiche_annonce.php?id=<?= urlencode($a['idAnnonce']) ?>">
            <div class="card-img">
              <?php if ($a['photo']): ?>
                <img src="<?= htmlspecialchars($a['photo']) ?>" alt="<?= $titre ?>" onerror="this.style.display='none'">
              <?php else: ?>
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.8"><rect x="1" y="6" width="22" height="13" rx="3"/><circle cx="7" cy="16" r="1.5"/><circle cx="17" cy="16" r="1.5"/></svg>
              <?php endif; ?>

              <button class="card-fav <?= $isFav?'faved':'' ?>" onclick="event.preventDefault();event.stopPropagation();toggleFav('<?= $a['idAnnonce'] ?>', this)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="<?= $isFav?'currentColor':'none' ?>" stroke="currentColor" stroke-width="2"><path d="M11.29 20.66c.2.2.45.29.71.29s.51-.1.71-.29l7.5-7.5c2.35-2.35 2.35-6.05 0-8.41-2.3-2.28-5.85-2.35-8.21-.2-2.36-2.15-5.91-2.09-8.21.2-2.35 2.36-2.35 6.06 0 8.41z"/></svg>
              </button>

              <?php if ($a['nbPhotos'] > 0): ?>
              <div class="card-img-count">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                <?= $a['nbPhotos'] ?>
              </div>
              <?php endif; ?>
            </div>

            <div class="card-body">
              <div class="card-top">
                <div style="min-width:0">
                  <div class="card-title"><?= $titre ?></div>
                  <?php if ($sousTitre): ?>
                    <div class="card-subtitle"><?= htmlspecialchars($sousTitre) ?></div>
                  <?php endif; ?>
                </div>
                <div class="card-price-block">
                  <div class="card-price"><?= $prix ?> DA</div>
                  <?php if ($isDeal): ?>
                    <div class="card-price-info">↓ <?= number_format($diff, 0, ',', ' ') ?> DA / moy.</div>
                  <?php endif; ?>
                </div>
              </div>

              <div class="card-specs">
                <?php if ($a['annee']): ?><span class="spec">📅 <?= $a['annee'] ?></span><span class="spec-dot"></span><?php endif; ?>
                <?php if ($a['kilometrage']): ?><span class="spec">🛣 <?= $km ?> km</span><span class="spec-dot"></span><?php endif; ?>
                <?php if ($a['carburant']): ?><span class="spec">⛽ <?= htmlspecialchars($a['carburant']) ?></span><span class="spec-dot"></span><?php endif; ?>
                <?php if ($a['transmission']): ?><span class="spec">⚙ <?= htmlspecialchars($a['transmission']) ?></span><?php endif; ?>
                <?php if ($a['puissance']): ?><span class="spec-dot"></span><span class="spec"><?= $a['puissance'] ?> ch</span><?php endif; ?>
              </div>

              <div class="card-tags">
                <?php if ($a['etatVehicule']): ?>
                  <span class="card-tag"><?= ucfirst(htmlspecialchars($a['etatVehicule'])) ?></span>
                <?php endif; ?>
                <?php if ($a['couleur']): ?>
                  <span class="card-tag"><?= htmlspecialchars($a['couleur']) ?></span>
                <?php endif; ?>
                <?php if ($a['typeVendeur'] === 'concessionnaire'): ?>
                  <span class="card-tag green">Pro</span>
                <?php endif; ?>
              </div>

              <div class="card-foot">
                <div class="card-seller">
                  <div class="seller-avatar" style="background:<?= $colorVend ?>"><?= $initVend ?></div>
                  <span><?= htmlspecialchars($nomVend) ?></span>
                  <?php if ($a['badgeVerifie']): ?>
                    <span class="pro-badge">✓</span>
                  <?php endif; ?>
                </div>
                <div>📍 <?= $loc ?> · <?= timeAgo($a['datePublication']) ?></div>
              </div>
            </div>
          </a>
        <?php endforeach; endif; ?>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <?php
          $prevPage = max(1, $f['page'] - 1);
          $nextPage = min($totalPages, $f['page'] + 1);
          ?>
          <a class="page-btn <?= $f['page']<=1?'disabled':'' ?>" href="<?= buildUrl(['page'=>$prevPage]) ?>">‹</a>

          <?php
          $pagesToShow = [1];
          if ($f['page'] > 3) $pagesToShow[] = '...';
          for ($i = max(2, $f['page']-1); $i <= min($totalPages-1, $f['page']+1); $i++) {
              $pagesToShow[] = $i;
          }
          if ($f['page'] < $totalPages - 2) $pagesToShow[] = '...';
          if ($totalPages > 1) $pagesToShow[] = $totalPages;
          $pagesToShow = array_unique($pagesToShow, SORT_REGULAR);

          foreach ($pagesToShow as $p):
            if ($p === '...'): ?>
              <span class="page-dots">…</span>
            <?php else: ?>
              <a class="page-btn <?= $p==$f['page']?'active':'' ?>" href="<?= buildUrl(['page'=>$p]) ?>"><?= $p ?></a>
            <?php endif;
          endforeach; ?>

          <a class="page-btn <?= $f['page']>=$totalPages?'disabled':'' ?>" href="<?= buildUrl(['page'=>$nextPage]) ?>">›</a>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <div class="toast" id="toast"></div>

  <script>
    function changeSort(val) {
      const url = new URL(location.href);
      url.searchParams.set('sort', val);
      url.searchParams.delete('page');
      location.href = url.toString();
    }

    function toggleFav(id, btn) {
      <?php if (!isset($_SESSION['idUtilisateur'])): ?>
        location.href = 'inscription.php';
        return;
      <?php endif; ?>
      const fd = new FormData();
      fd.append('action', 'toggle');
      fd.append('idAnnonce', id);
      fetch('toggle_favori.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
          if (json.needLogin) { location.href = 'inscription.php'; return; }
          btn.classList.toggle('faved', json.favori);
          btn.querySelector('svg').setAttribute('fill', json.favori ? 'currentColor' : 'none');
          showToast(json.favori ? '❤️ Ajouté aux favoris' : 'Retiré des favoris');
        })
        .catch(() => showToast('Erreur'));
    }

    function saveSearch() {
      <?php if (!isset($_SESSION['idUtilisateur'])): ?>
        location.href = 'inscription.php?redirect=recherche.php';
        return;
      <?php endif; ?>
      const fd = new FormData();
      fd.append('action', 'save_search');
      fd.append('params', location.search);
      fetch('save_search.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
          if (json.success) showToast('💾 Recherche sauvegardée !');
          else showToast(json.message || 'Erreur');
        })
        .catch(() => showToast('Sauvegardée localement'));
    }

    let toastTimer;
    function showToast(msg) {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.classList.add('show');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(() => t.classList.remove('show'), 2500);
    }

    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('show');
    }

    document.querySelectorAll('.filter-h').forEach(h => {
      h.addEventListener('click', () => h.classList.toggle('collapsed'));
    });
  </script>
</body>
</html>
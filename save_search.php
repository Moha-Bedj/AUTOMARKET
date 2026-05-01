<?php
session_start();
require_once 'connexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['idUtilisateur'])) {
    echo json_encode(['success' => false, 'needLogin' => true]);
    exit;
}

$idUser = $_SESSION['idUtilisateur'];
$idUserSql = mysqli_real_escape_string($conn, $idUser);

/* Récupérer les paramètres de l'URL passés en POST */
$paramsStr = $_POST['params'] ?? '';
if ($paramsStr && $paramsStr[0] === '?') $paramsStr = substr($paramsStr, 1);
parse_str($paramsStr, $params);

$esc = fn($v) => mysqli_real_escape_string($conn, $v);

/* Construction des champs à sauvegarder */
$motsCles = $esc($params['q'] ?? ($params['marque'] ?? '') . ' ' . ($params['modele'] ?? ''));
$motsCles = trim($motsCles);
if (mb_strlen($motsCles) > 250) $motsCles = mb_substr($motsCles, 0, 250);

$prixMin = !empty($params['prix_min']) ? (float)$params['prix_min'] : 'NULL';
$prixMax = !empty($params['prix_max']) ? (float)$params['prix_max'] : 'NULL';
$anneeMin = !empty($params['annee_min']) ? (int)$params['annee_min'] : 'NULL';
$anneeMax = !empty($params['annee_max']) ? (int)$params['annee_max'] : 'NULL';
$kiloMin = !empty($params['km_min']) ? (int)$params['km_min'] : 'NULL';
$kiloMax = !empty($params['km_max']) ? (int)$params['km_max'] : 'NULL';

$carb = '';
if (!empty($params['carburant'])) {
    if (is_array($params['carburant'])) $carb = implode(',', $params['carburant']);
    else $carb = $params['carburant'];
}
$carbSql = $carb ? "'" . $esc($carb) . "'" : 'NULL';

$transmis = !empty($params['transmis']) ? "'" . $esc($params['transmis']) . "'" : 'NULL';
$wilaya = !empty($params['wilaya']) ? "'" . $esc($params['wilaya']) . "'" : 'NULL';

/* Vérifier si recherche identique existe déjà */
$rExist = mysqli_query($conn, "
    SELECT idRecherche FROM RechercheSauvegarde 
    WHERE idUtilisateur = '$idUserSql'
    AND motsCles " . ($motsCles ? "= '$motsCles'" : "IS NULL OR motsCles = ''") . "
    LIMIT 1
");

if ($rExist && mysqli_num_rows($rExist) > 0) {
    echo json_encode(['success' => true, 'message' => '✓ Cette recherche est déjà sauvegardée']);
    exit;
}

/* Générer UUID */
$idRecherche = sprintf('%s-%s-%s-%s-%s',
    bin2hex(random_bytes(4)),
    bin2hex(random_bytes(2)),
    bin2hex(random_bytes(2)),
    bin2hex(random_bytes(2)),
    bin2hex(random_bytes(6))
);

/* Insertion */
$sql = "
    INSERT INTO RechercheSauvegarde 
    (idRecherche, motsCles, prixMin, prixMax, anneeMin, anneeMax, kiloMin, kiloMax, carburant, transmission, localisation, idUtilisateur)
    VALUES (
        '$idRecherche',
        " . ($motsCles ? "'$motsCles'" : "NULL") . ",
        $prixMin, $prixMax,
        $anneeMin, $anneeMax,
        $kiloMin, $kiloMax,
        $carbSql, $transmis, $wilaya,
        '$idUserSql'
    )
";

$ok = mysqli_query($conn, $sql);

if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'Erreur DB : ' . mysqli_error($conn)]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => '💾 Recherche sauvegardée !',
    'idRecherche' => $idRecherche
]);
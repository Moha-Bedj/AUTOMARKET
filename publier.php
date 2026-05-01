<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['idUtilisateur'])) {
    header("Location: connexion.php?redirect=publier.php");
    exit;
}
$idUser = $_SESSION['idUtilisateur'];
$idUserSql = mysqli_real_escape_string($conn, $idUser);
$rUser = mysqli_query($conn, "SELECT nom, prenom, numTel FROM Utilisateur WHERE idUtilisateur = '$idUserSql'");
$user = mysqli_fetch_assoc($rUser) ?: ['nom'=>'', 'prenom'=>'', 'numTel'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'publier') {

    header('Content-Type: application/json');

    $type        = mysqli_real_escape_string($conn, $_POST['type'] ?? 'voiture');
    $marque      = trim($_POST['marque'] ?? '');
    $modele      = trim($_POST['modele'] ?? '');
    $version     = trim($_POST['version'] ?? '');
    $annee       = (int)($_POST['annee'] ?? 0);
    $km          = (int)($_POST['kilometrage'] ?? 0);
    $carburant   = mysqli_real_escape_string($conn, $_POST['carburant'] ?? '');
    $transmission= mysqli_real_escape_string($conn, $_POST['transmission'] ?? '');
    $puissance   = (int)($_POST['puissance'] ?? 0);
    $cylindree   = (int)($_POST['cylindree'] ?? 0);
    $portes      = (int)($_POST['portes'] ?? 0);
    $places      = (int)($_POST['places'] ?? 0);
    $etat        = mysqli_real_escape_string($conn, $_POST['etat'] ?? 'occasion');
    $couleurExt  = trim($_POST['couleur_ext'] ?? '');
    $couleurInt  = trim($_POST['couleur_int'] ?? '');
    $titre       = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix        = (float)($_POST['prix'] ?? 0);
    $localisation= mysqli_real_escape_string($conn, $_POST['wilaya'] ?? '');
    $negociable  = isset($_POST['negociable']) ? 1 : 0;
    $credit      = isset($_POST['credit']) ? 1 : 0;
    $echange     = isset($_POST['echange']) ? 1 : 0;
    $telephone   = trim($_POST['telephone'] ?? '');
    $equipements = $_POST['equipements'] ?? [];

    $errors = [];
    if (!$titre || strlen($titre) < 10) $errors[] = "Le titre doit contenir au moins 10 caracteres.";
    if (!$description || strlen($description) < 50) $errors[] = "La description doit contenir au moins 50 caracteres.";
    if (!$marque) $errors[] = "Marque obligatoire.";
    if (!$modele) $errors[] = "Modele obligatoire.";
    if ($annee < 1900 || $annee > 2030) $errors[] = "Annee invalide.";
    if ($prix <= 0) $errors[] = "Prix invalide.";
    if (!$localisation) $errors[] = "Wilaya obligatoire.";
    if (empty($_FILES['photos']['name'][0])) $errors[] = "Au moins 1 photo est requise.";

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    function uuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
            mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
            mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)
        );
    }

    $idVehicule = uuid();
    $idAnnonce  = uuid();

    $titreFinal = $titre;
    if (strlen($titreFinal) < 20) {
        $titreFinal = "$marque $modele " . ($version ? "$version " : '') . "- $annee";
    }
    $titreFinalE = mysqli_real_escape_string($conn, $titreFinal);

    $couleurFull = $couleurExt . ($couleurInt ? " / int. $couleurInt" : '');
    $couleurFullE = mysqli_real_escape_string($conn, $couleurFull);
    $descriptionE = mysqli_real_escape_string($conn, $description);

    mysqli_begin_transaction($conn);

    try {
        /* === Résolution dynamique idMarque (créer si n'existe pas) === */
        $marqueE = mysqli_real_escape_string($conn, $marque);
        $rM = mysqli_query($conn, "SELECT idMarque FROM Marque WHERE nomMarque='$marqueE' LIMIT 1");
        if ($rM && mysqli_num_rows($rM) > 0) {
            $idMarque = mysqli_fetch_assoc($rM)['idMarque'];
        } else {
            $idMarque = uuid();
            if (!mysqli_query($conn, "INSERT INTO Marque (idMarque, nomMarque) VALUES ('$idMarque', '$marqueE')")) {
                throw new Exception("Erreur creation Marque : " . mysqli_error($conn));
            }
        }

        /* === Résolution dynamique idModele (créer si n'existe pas) === */
        $modeleE = mysqli_real_escape_string($conn, $modele);
        $rMod = mysqli_query($conn, "SELECT idModele FROM Modele WHERE nomModele='$modeleE' AND idMarque='$idMarque' LIMIT 1");
        if ($rMod && mysqli_num_rows($rMod) > 0) {
            $idModele = mysqli_fetch_assoc($rMod)['idModele'];
        } else {
            $idModele = uuid();
            if (!mysqli_query($conn, "INSERT INTO Modele (idModele, nomModele, idMarque) VALUES ('$idModele', '$modeleE', '$idMarque')")) {
                throw new Exception("Erreur creation Modele : " . mysqli_error($conn));
            }
        }

        /* === Résolution dynamique idVersion (optionnel) === */
        $idVersion = null;
        if ($version) {
            $versionE = mysqli_real_escape_string($conn, $version);
            $rVer = mysqli_query($conn, "SELECT idVersion FROM Version WHERE nomVersion='$versionE' AND idModele='$idModele' LIMIT 1");
            if ($rVer && mysqli_num_rows($rVer) > 0) {
                $idVersion = mysqli_fetch_assoc($rVer)['idVersion'];
            } else {
                $idVersion = uuid();
                mysqli_query($conn, "INSERT INTO Version (idVersion, nomVersion, idModele) VALUES ('$idVersion', '$versionE', '$idModele')");
            }
        }

        /* === INSERT Vehicule avec idModele réel === */
        $idVersionSql = $idVersion ? "'$idVersion'" : 'NULL';
        $sqlV = "INSERT INTO Vehicule (idVehicule, idVersion, idModele, typeVehicule, annee, kilometrage, carburant, transmission, puissance, couleur, nbrPortes, nbrPlaces, etatVehicule, cylindree) VALUES ('$idVehicule', $idVersionSql, '$idModele', '$type', $annee, $km, '$carburant', '$transmission', " . ($puissance ?: 'NULL') . ", '$couleurFullE', " . ($portes ?: 'NULL') . ", " . ($places ?: 'NULL') . ", '$etat', " . ($cylindree ?: 'NULL') . ")";
        if (!mysqli_query($conn, $sqlV)) throw new Exception("Erreur Vehicule : " . mysqli_error($conn));

        $today = date('Y-m-d');
        $expir = date('Y-m-d', strtotime('+90 days'));

        /* === S'assurer que l'utilisateur existe dans Acheteur === */
        /* (Vendeur a une FK vers Acheteur dans cette BDD) */
        $rA = mysqli_query($conn, "SELECT idUtilisateur FROM Acheteur WHERE idUtilisateur='$idUserSql' LIMIT 1");
        if (!$rA || mysqli_num_rows($rA) === 0) {
            if (!mysqli_query($conn, "INSERT INTO Acheteur (idUtilisateur) VALUES ('$idUserSql')")) {
                throw new Exception("Erreur creation Acheteur : " . mysqli_error($conn));
            }
        }

        /* === S'assurer que l'utilisateur existe dans Vendeur === */
        $rV = mysqli_query($conn, "SELECT idUtilisateur FROM Vendeur WHERE idUtilisateur='$idUserSql' LIMIT 1");
        if (!$rV || mysqli_num_rows($rV) === 0) {
            if (!mysqli_query($conn, "INSERT INTO Vendeur (idUtilisateur, typeVendeur, nbrAnnonceAct) VALUES ('$idUserSql', 'particulier', 0)")) {
                throw new Exception("Erreur creation Vendeur : " . mysqli_error($conn));
            }
        }

        $sqlA = "INSERT INTO Annonce (idAnnonce, idVehicule, idVendeur, titre, description, prix, datePublication, dateExpiration, statutAnnonce, typeAnnonce, localisation, vendeurVerif) VALUES ('$idAnnonce', '$idVehicule', '$idUserSql', '$titreFinalE', '$descriptionE', $prix, '$today', '$expir', 'active', 'vente', '$localisation', 0)";
        if (!mysqli_query($conn, $sqlA)) throw new Exception("Erreur Annonce : " . mysqli_error($conn));

        /* Incrémenter compteur d'annonces actives du vendeur */
        mysqli_query($conn, "UPDATE Vendeur SET nbrAnnonceAct = nbrAnnonceAct + 1 WHERE idUtilisateur='$idUserSql'");

        if ($telephone && $telephone !== ($user['numTel'] ?? '')) {
            $tel = mysqli_real_escape_string($conn, $telephone);
            mysqli_query($conn, "UPDATE Utilisateur SET numTel='$tel' WHERE idUtilisateur='$idUserSql'");
        }

        $uploadDir = 'uploads/annonces/';
        if (!is_dir($uploadDir)) {
            if (!@mkdir($uploadDir, 0755, true)) {
                throw new Exception("Impossible de creer le dossier $uploadDir. Verifiez les permissions.");
            }
        }
        if (!is_writable($uploadDir)) {
            throw new Exception("Le dossier $uploadDir n'est pas accessible en ecriture.");
        }

        $photosFiles = $_FILES['photos'];
        $photoOrdre = 0;
        $photosErrors = [];

        for ($i = 0; $i < count($photosFiles['name']); $i++) {
            $errCode = $photosFiles['error'][$i];
            $origName = $photosFiles['name'][$i];

            if ($errCode !== UPLOAD_ERR_OK) {
                $msgs = [
                    UPLOAD_ERR_INI_SIZE => "depasse upload_max_filesize (".ini_get('upload_max_filesize').")",
                    UPLOAD_ERR_FORM_SIZE => "depasse MAX_FILE_SIZE",
                    UPLOAD_ERR_PARTIAL => "upload partiel",
                    UPLOAD_ERR_NO_FILE => "aucun fichier",
                    UPLOAD_ERR_NO_TMP_DIR => "pas de dossier temporaire",
                    UPLOAD_ERR_CANT_WRITE => "impossible d'ecrire",
                    UPLOAD_ERR_EXTENSION => "extension PHP a stoppe l'upload"
                ];
                $photosErrors[] = "Photo \"$origName\" : " . ($msgs[$errCode] ?? "erreur $errCode");
                continue;
            }

            $tmpName = $photosFiles['tmp_name'][$i];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $photosErrors[] = "Photo \"$origName\" : format non supporte ($ext)";
                continue;
            }
            if ($photosFiles['size'][$i] > 10 * 1024 * 1024) {
                $photosErrors[] = "Photo \"$origName\" : depasse 10 Mo";
                continue;
            }

            $newName = $idAnnonce . '_' . $photoOrdre . '_' . uniqid() . '.' . $ext;
            $dest = $uploadDir . $newName;

            if (move_uploaded_file($tmpName, $dest)) {
                $idPhoto = uuid();
                $url = mysqli_real_escape_string($conn, $dest);
                $desc = mysqli_real_escape_string($conn, $origName);
                $sqlPhoto = "INSERT INTO Photos (idPhoto, idAnnonce, urlPhoto, ordrePhoto, descriptionPhoto) VALUES ('$idPhoto', '$idAnnonce', '$url', $photoOrdre, '$desc')";
                if (!mysqli_query($conn, $sqlPhoto)) {
                    @unlink($dest);
                    throw new Exception("Erreur INSERT Photos : " . mysqli_error($conn));
                }
                $photoOrdre++;
            } else {
                $photosErrors[] = "Photo \"$origName\" : echec move_uploaded_file";
            }
        }

        if ($photoOrdre === 0) {
            $detail = !empty($photosErrors) ? " Details : " . implode(' | ', $photosErrors) : "";
            throw new Exception("Aucune photo n'a pu etre uploadee." . $detail);
        }

        if (!empty($equipements) && is_array($equipements)) {
            foreach ($equipements as $eqLib) {
                $eqLibE = mysqli_real_escape_string($conn, trim($eqLib));
                if (!$eqLibE) continue;
                $rE = mysqli_query($conn, "SELECT idEquipement FROM Equipement WHERE libelleEquipement='$eqLibE' LIMIT 1");
                $idEq = null;
                if ($rE && mysqli_num_rows($rE) > 0) {
                    $idEq = mysqli_fetch_assoc($rE)['idEquipement'];
                } else {
                    $idEq = uuid();
                    mysqli_query($conn, "INSERT INTO Equipement (idEquipement, libelleEquipement) VALUES ('$idEq', '$eqLibE')");
                }
                if ($idEq) {
                    mysqli_query($conn, "INSERT INTO Vehicule_Equipement (idVehicule, idEquipement) VALUES ('$idVehicule', '$idEq')");
                }
            }
        }

        mysqli_commit($conn);

        echo json_encode([
            'success' => true,
            'idAnnonce' => $idAnnonce,
            'redirect' => 'index.php?publie=1&id=' . $idAnnonce
        ]);
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'errors' => [$e->getMessage()]]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Publier une annonce — AUTOMARKET</title>
    <link rel="icon" href="images/logo.png">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --blue: #185FA5; --blue-dk: #0C447C; --blue-bg: #E6F1FB; --blue-bd: #B5D4F4;
      --bg0: #ffffff; --bg1: #f5f4f0; --bg2: #eceae4;
      --t1: #1a1a18; --t2: #5f5e5a; --t3: #888780;
      --bd: rgba(0,0,0,0.11); --bd2: rgba(0,0,0,0.22);
      --green: #16a34a; --green-bg: #EAF3DE; --green-dk: #14532d;
      --red: #E24B4A; --red-bg: #FCEBEB;
      --orange: #FF8A4C;
      --r6: 6px; --r8: 8px; --r10: 10px;
    }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; background: var(--bg1); color: var(--t1); min-height: 100vh; }

    /* NAVBAR */
    .nav { background: var(--bg0); border-bottom: 0.5px solid var(--bd); height: 52px; display: flex; align-items: center; padding: 0 20px; gap: 16px; position: sticky; top: 0; z-index: 100; }
    .logo { display: flex; align-items: center; text-decoration: none; }
    .nav-back { margin-left: auto; font-size: 13px; color: var(--t2); display: flex; align-items: center; gap: 6px; text-decoration: none; padding: 6px 12px; border-radius: var(--r6); }
    .nav-back:hover { background: var(--bg1); }

    /* CONTAINER */
    .container { max-width: 820px; margin: 0 auto; padding: 24px 20px 40px; }
    .page-h1 { font-size: 22px; font-weight: 600; margin-bottom: 4px; }
    .page-sub { font-size: 13px; color: var(--t2); margin-bottom: 20px; }

    /* PROGRESS BAR */
    .progress-wrap { background: var(--bg2); border-radius: 6px; height: 6px; overflow: hidden; margin-bottom: 8px; }
    .progress-bar { height: 100%; background: var(--blue); border-radius: 6px; width: 20%; transition: width .3s ease; }
    .progress-text { font-size: 12px; color: var(--t2); display: flex; justify-content: space-between; margin-bottom: 16px; }
    .progress-percent { color: var(--blue); font-weight: 600; }

    /* STEPPER */
    .stepper { display: flex; align-items: flex-start; gap: 4px; margin-bottom: 24px; padding: 16px 18px; background: var(--bg0); border: 0.5px solid var(--bd); border-radius: var(--r10); }
    .step { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; }
    .step-dot { width: 30px; height: 30px; border-radius: 50%; background: var(--bg1); border: 0.5px solid var(--bd); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 500; color: var(--t3); transition: all .2s; }
    .step.done .step-dot { background: var(--blue); color: #fff; border-color: var(--blue); }
    .step.active .step-dot { background: var(--blue); color: #fff; border-color: var(--blue); box-shadow: 0 0 0 4px var(--blue-bg); }
    .step-label { font-size: 11px; color: var(--t3); text-align: center; line-height: 1.3; max-width: 90px; }
    .step.active .step-label, .step.done .step-label { color: var(--t1); font-weight: 500; }
    .step-line { flex: 1; height: 1px; background: var(--bd); margin-top: 15px; }
    .step-line.done { background: var(--blue); }

    .step-banner { background: var(--blue-bg); border-left: 3px solid var(--blue); padding: 10px 14px; border-radius: 0 8px 8px 0; margin-bottom: 16px; font-size: 13px; color: var(--blue-dk); display: flex; align-items: center; gap: 8px; }
    .step-banner svg { flex-shrink: 0; }

    /* FORM CARD */
    .form-card { background: var(--bg0); border: 0.5px solid var(--bd); border-radius: var(--r10); padding: 22px 24px; margin-bottom: 14px; }
    .form-card-h { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 0.5px solid var(--bd); }
    .form-card-h-icon { width: 36px; height: 36px; border-radius: 8px; background: var(--blue-bg); color: var(--blue); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .form-card-h-title { font-size: 16px; font-weight: 600; }
    .form-card-h-sub { font-size: 12px; color: var(--t2); margin-top: 2px; }

    /* TYPE GRID */
    .type-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
    .type-card { padding: 22px 14px; border: 0.5px solid var(--bd2); border-radius: var(--r10); display: flex; flex-direction: column; align-items: center; gap: 8px; cursor: pointer; transition: all .15s; background: var(--bg0); }
    .type-card:hover { border-color: var(--blue); background: var(--blue-bg); }
    .type-card.selected { border: 2px solid var(--blue); background: var(--blue-bg); padding: 21px 13px; }
    .type-card svg { width: 40px; height: 40px; color: var(--t2); }
    .type-card.selected svg { color: var(--blue); }
    .type-name { font-size: 13px; font-weight: 500; }
    .type-card.selected .type-name { color: var(--blue); }

    /* CHAMPS */
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
    .field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
    .field-label { font-size: 12px; color: var(--t2); font-weight: 500; display: flex; align-items: center; gap: 4px; }
    .field-label .req { color: var(--red); }
    .field-input, .field-select { height: 40px; border: 0.5px solid var(--bd2); border-radius: var(--r8); padding: 0 12px; font-size: 13px; background: var(--bg0); font-family: inherit; outline: none; width: 100%; transition: all .15s; }
    .field-select { appearance: none; padding-right: 30px; background-image: url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23888' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; }
    .field-input:focus, .field-select:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(24,95,165,.1); }
    .field-input.filled, .field-select.filled { border-color: var(--blue); background: #F8FBFF; }
    .field-input.error { border-color: var(--red); background: var(--red-bg); }
    .field-textarea { min-height: 100px; padding: 10px 12px; border: 0.5px solid var(--bd2); border-radius: var(--r8); font-size: 13px; resize: vertical; font-family: inherit; outline: none; width: 100%; transition: all .15s; }
    .field-textarea:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(24,95,165,.1); }
    .field-hint { font-size: 11px; color: var(--t3); display: flex; justify-content: space-between; margin-top: 2px; }

    /* INPUT WITH ICON */
    .input-with-icon { position: relative; }
    .input-with-icon input { padding-right: 36px; }
    .input-icon-right { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--t3); pointer-events: none; }
    .input-with-icon input[readonly] { background: var(--bg1); cursor: not-allowed; }

    /* OPTIONS */
    .options-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .option { display: flex; align-items: center; gap: 8px; padding: 10px 12px; border: 0.5px solid var(--bd); border-radius: var(--r8); font-size: 13px; background: var(--bg0); cursor: pointer; user-select: none; transition: all .15s; }
    .option:hover { border-color: var(--blue); background: var(--blue-bg); }
    .option input { display: none; }
    .option .cb { width: 16px; height: 16px; border: 0.5px solid var(--bd2); border-radius: 4px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: transparent; }
    .option:has(input:checked) { border-color: var(--blue); background: var(--blue-bg); color: var(--blue-dk); font-weight: 500; }
    .option:has(input:checked) .cb { background: var(--blue); border-color: var(--blue); color: #fff; }
    .options-subtitle { font-size: 12px; color: var(--t3); font-weight: 600; text-transform: uppercase; letter-spacing: .3px; margin: 16px 0 8px; }
    .options-subtitle:first-child { margin-top: 0; }

    /* PHOTOS */
    .photo-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
    .photo-slot { aspect-ratio: 1; border: 1.5px dashed var(--bd2); border-radius: var(--r10); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; color: var(--t3); font-size: 11px; cursor: pointer; background: var(--bg1); transition: all .15s; position: relative; overflow: hidden; }
    .photo-slot:hover { border-color: var(--blue); background: var(--blue-bg); color: var(--blue); }
    .photo-slot.uploaded { border: 0.5px solid var(--bd); border-style: solid; background-size: cover; background-position: center; cursor: default; }
    .photo-slot.main { border: 2px solid var(--blue); }
    .photo-badge { position: absolute; top: 6px; left: 6px; background: var(--blue); color: #fff; font-size: 9px; padding: 2px 7px; border-radius: 4px; font-weight: 500; text-transform: uppercase; letter-spacing: .3px; }
    .photo-remove { position: absolute; top: 6px; right: 6px; width: 24px; height: 24px; background: rgba(0,0,0,0.7); color: #fff; border-radius: 50%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 500; }
    .photo-remove:hover { background: var(--red); }
    .photo-info { background: var(--bg1); border-radius: var(--r8); padding: 12px 14px; margin-top: 14px; display: flex; gap: 10px; align-items: flex-start; font-size: 12px; color: var(--t2); }
    .photo-info svg { flex-shrink: 0; margin-top: 1px; }
    .photo-info-title { color: var(--t1); font-weight: 500; margin-bottom: 3px; }

    /* COULEURS */
    .color-grid { display: flex; flex-wrap: wrap; gap: 8px; }
    .color-pick { display: flex; align-items: center; gap: 7px; padding: 6px 12px 6px 6px; border: 0.5px solid var(--bd); border-radius: 18px; font-size: 12px; cursor: pointer; transition: all .15s; background: var(--bg0); user-select: none; }
    .color-pick:hover { border-color: var(--blue); }
    .color-pick.selected { border-color: var(--blue); background: var(--blue-bg); color: var(--blue-dk); font-weight: 500; }
    .color-dot { width: 18px; height: 18px; border-radius: 50%; border: 0.5px solid rgba(0,0,0,.15); flex-shrink: 0; }

    /* PRIX */
    .price-row { display: flex; gap: 10px; align-items: end; }
    .price-input-wrap { position: relative; flex: 1; }
    .price-input-wrap input { width: 100%; height: 52px; padding: 0 60px 0 14px; font-size: 22px; font-weight: 600; border: 0.5px solid var(--bd2); border-radius: var(--r8); font-family: inherit; outline: none; color: var(--blue); }
    .price-input-wrap input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(24,95,165,.1); }
    .price-unit { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); font-size: 14px; color: var(--t3); pointer-events: none; font-weight: 500; }
    .price-toggles { display: flex; gap: 8px; margin-top: 14px; flex-wrap: wrap; }
    .price-chip { padding: 8px 14px; border: 0.5px solid var(--bd2); border-radius: 20px; font-size: 12px; cursor: pointer; background: var(--bg0); transition: all .15s; user-select: none; display: flex; align-items: center; gap: 6px; }
    .price-chip:hover { border-color: var(--blue); }
    .price-chip input { display: none; }
    .price-chip:has(input:checked) { background: var(--blue-bg); color: var(--blue-dk); border-color: var(--blue); font-weight: 500; }

    /* PRÉVISUALISATION */
    .preview-card { display: grid; grid-template-columns: 160px 1fr; background: var(--bg0); border: 0.5px solid var(--bd); border-radius: var(--r10); overflow: hidden; }
    .preview-img { background: linear-gradient(135deg, #d4d8e0, #b8c1cc); min-height: 130px; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,.7); background-size: cover; background-position: center; }
    .preview-body { padding: 12px 14px; display: flex; flex-direction: column; gap: 4px; }
    .preview-title { font-size: 14px; font-weight: 600; }
    .preview-sub { font-size: 12px; color: var(--t2); }
    .preview-price { font-size: 18px; font-weight: 700; color: var(--blue); margin-top: 4px; }
    .preview-specs { display: flex; gap: 4px; flex-wrap: wrap; margin-top: 4px; }
    .preview-spec { font-size: 10px; background: var(--bg1); padding: 2px 7px; border-radius: 4px; color: var(--t2); }

    /* RECAP */
    .recap-section { background: var(--bg0); border: 0.5px solid var(--bd); border-radius: var(--r10); padding: 16px 20px; margin-bottom: 12px; }
    .recap-section-h { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 0.5px solid var(--bd); }
    .recap-section-title { font-size: 13px; font-weight: 600; color: var(--t1); }
    .recap-edit { font-size: 12px; color: var(--blue); cursor: pointer; text-decoration: none; }
    .recap-edit:hover { text-decoration: underline; }
    .recap-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 13px; gap: 16px; }
    .recap-key { color: var(--t2); }
    .recap-val { color: var(--t1); font-weight: 500; text-align: right; }

    /* ACTIONS */
    .actions { display: flex; gap: 10px; justify-content: space-between; align-items: center; padding: 16px 0 8px; }
    .btn-prev { padding: 10px 20px; background: transparent; border: 0.5px solid var(--bd2); border-radius: var(--r8); font-size: 13px; color: var(--t2); cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-family: inherit; transition: all .15s; text-decoration: none; }
    .btn-prev:hover { background: var(--bg1); color: var(--t1); }
    .btn-next { padding: 11px 24px; background: var(--blue); color: #fff; border: none; border-radius: var(--r8); font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-family: inherit; transition: background .15s; }
    .btn-next:hover { background: var(--blue-dk); }
    .btn-publish { padding: 12px 28px; background: var(--green); color: #fff; border: none; border-radius: var(--r8); font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-family: inherit; transition: background .15s; }
    .btn-publish:hover { background: var(--green-dk); }
    .btn-publish:disabled { opacity: 0.6; cursor: not-allowed; }

    /* ALERT */
    .alert { padding: 12px 16px; border-radius: var(--r8); margin-bottom: 14px; font-size: 13px; display: flex; gap: 10px; align-items: flex-start; }
    .alert-error { background: var(--red-bg); color: #791F1F; border: 0.5px solid rgba(226,75,74,0.3); }
    .alert-success { background: var(--green-bg); color: var(--green-dk); border: 0.5px solid rgba(22,163,74,0.3); }

    .step-content { display: none; }
    .step-content.active { display: block; }

    .spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.8s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    @media (max-width: 700px) {
      .container { padding: 16px 12px 40px; }
      .grid-2, .grid-3 { grid-template-columns: 1fr; gap: 10px; }
      .options-grid { grid-template-columns: 1fr 1fr; }
      .photo-grid { grid-template-columns: repeat(3, 1fr); }
      .step-label { font-size: 10px; max-width: 60px; }
      .stepper { padding: 12px 10px; }
      .form-card { padding: 16px; }
      .preview-card { grid-template-columns: 1fr; }
      .preview-img { min-height: 160px; }
      .actions { flex-wrap: wrap; }
      .btn-next, .btn-publish { flex: 1; justify-content: center; }
    }
  </style>
</head>
<body>

  <nav class="nav">
    <a class="logo" href="index.php">
      <img src="images/logo.png" alt="AUTOMARKET" style="height:34px;">
    </a>
    <a href="index.php" class="nav-back">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Retour
    </a>
  </nav>

  <div class="container">

    <h1 class="page-h1">Publier une annonce</h1>
    <p class="page-sub">Vendez votre véhicule en quelques minutes</p>

    <div class="progress-wrap"><div class="progress-bar" id="progress-bar"></div></div>
    <div class="progress-text">
      <span id="progress-step">Étape 1 sur 5 — Type de véhicule</span>
      <span class="progress-percent" id="progress-percent">20% complété</span>
    </div>

    <div class="stepper" id="stepper">
      <div class="step active"><div class="step-dot">1</div><div class="step-label">Type de véhicule</div></div>
      <div class="step-line"></div>
      <div class="step"><div class="step-dot">2</div><div class="step-label">Caractéristiques</div></div>
      <div class="step-line"></div>
      <div class="step"><div class="step-dot">3</div><div class="step-label">Photos & description</div></div>
      <div class="step-line"></div>
      <div class="step"><div class="step-dot">4</div><div class="step-label">Prix & conditions</div></div>
      <div class="step-line"></div>
      <div class="step"><div class="step-dot">5</div><div class="step-label">Aperçu & publier</div></div>
    </div>

    <div id="alert-zone"></div>

    <form id="publish-form" enctype="multipart/form-data">
      <input type="hidden" name="action" value="publier">


      <!-- ETAPE 1 : TYPE DE VEHICULE -->
      <div class="step-content active" data-step-content="1">
        <div class="step-banner">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
          Choisissez le type de véhicule que vous souhaitez vendre
        </div>

        <div class="form-card">
          <div class="form-card-h">
            <div class="form-card-h-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="m20.77 9.16-1.37-4.1a2.99 2.99 0 0 0-2.85-2.05H7.44a3 3 0 0 0-2.85 2.05l-1.37 4.1c-.72.3-1.23 1.02-1.23 1.84v5c0 .74.41 1.38 1 1.72V20c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-2h12v2c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-2.28a2 2 0 0 0 1-1.72v-5c0-.83-.51-1.54-1.23-1.84Z"/></svg>
            </div>
            <div>
              <div class="form-card-h-title">Type de véhicule</div>
              <div class="form-card-h-sub">Sélectionnez la catégorie qui correspond</div>
            </div>
          </div>

          <div class="type-grid">
            <div class="type-card selected" data-type="voiture" onclick="selectType(this)">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="m20.77 9.16-1.37-4.1a2.99 2.99 0 0 0-2.85-2.05H7.44a3 3 0 0 0-2.85 2.05l-1.37 4.1c-.72.3-1.23 1.02-1.23 1.84v5c0 .74.41 1.38 1 1.72V20c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-2h12v2c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-2.28a2 2 0 0 0 1-1.72v-5c0-.83-.51-1.54-1.23-1.84ZM4 16v-5h16v5z"/><circle cx="6.5" cy="13.5" r="1.5"/><circle cx="17.5" cy="13.5" r="1.5"/></svg>
              <span class="type-name">Voiture</span>
            </div>
            <div class="type-card" data-type="moto" onclick="selectType(this)">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.26 14.47c-.5-.27-1.07-.42-1.66-.42h-.06l-2.19-5.01h1.26c.28 0 .5-.22.5-.5v-1c0-.28-.22-.5-.5-.5h-2.13l-.09-.2a3.01 3.01 0 0 0-2.75-1.8h-1.53v2h1.53c.4 0 .76.24.92.6l1.05 2.4h-3.93c-.29 0-.56.12-.75.33L7.44 13l-2.72-2.72a1 1 0 0 0-.71-.29H1.84v2h1.75L5.6 14c-1.11 0-2.13.51-2.79 1.38-.3.39-.53.87-.65 1.43-.04.22-.07.45-.07.68 0 1.93 1.57 3.5 3.5 3.5s3.5-1.57 3.5-3.5v-.1l1.3 1.3c.19.19.44.29.71.29h2c.25 0 .5-.1.68-.27L15 17.47c0 1.93 1.57 3.49 3.5 3.49s3.5-1.57 3.5-3.5c0-1.24-.67-2.4-1.74-3.03Z"/></svg>
              <span class="type-name">Moto</span>
            </div>
            <div class="type-card" data-type="camion" onclick="selectType(this)">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.1 7.8c-.38-.5-.97-.8-1.6-.8H15V6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2 0 1.65 1.35 3 3 3s3-1.35 3-3h4c0 1.65 1.35 3 3 3s3-1.35 3-3c1.1 0 2-.9 2-2v-3.67c0-.43-.14-.86-.4-1.2zM17.5 9l1.5 2h-4V9z"/></svg>
              <span class="type-name">Camion</span>
            </div>
          </div>
          <input type="hidden" name="type" id="f-type" value="voiture">
        </div>

        <div class="actions">
          <a href="index.php" class="btn-prev">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Annuler
          </a>
          <button type="button" class="btn-next" onclick="goStep(2)">
            Continuer
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </button>
        </div>
      </div>

      <!-- ETAPE 2 : CARACTERISTIQUES -->
      <div class="step-content" data-step-content="2">
        <div class="step-banner">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
          Plus vous donnez de détails, plus votre annonce attirera d'acheteurs
        </div>

        <div class="form-card">
          <div class="form-card-h">
            <div class="form-card-h-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div>
              <div class="form-card-h-title">Identification</div>
              <div class="form-card-h-sub">Marque, modèle et version du véhicule</div>
            </div>
          </div>

          <div class="grid-3">
            <div class="field">
              <label class="field-label">Marque <span class="req">*</span></label>
              <select class="field-select" name="marque" id="f-marque" onchange="updateModeles(); checkFilled(this)">
                <option value="">Choisir...</option>
              </select>
            </div>
            <div class="field">
              <label class="field-label">Modèle <span class="req">*</span></label>
              <select class="field-select" name="modele" id="f-modele" onchange="checkFilled(this)">
                <option value="">Choisir...</option>
              </select>
            </div>
            <div class="field">
              <label class="field-label">Version</label>
              <input class="field-input" type="text" name="version" placeholder="Ex: GT Line" oninput="checkFilled(this)">
            </div>
          </div>
        </div>

        <div class="form-card">
          <div class="form-card-h">
            <div class="form-card-h-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div>
              <div class="form-card-h-title">Caractéristiques techniques</div>
              <div class="form-card-h-sub">Année, kilométrage et motorisation</div>
            </div>
          </div>

          <div class="grid-2">
            <div class="field">
              <label class="field-label">Année <span class="req">*</span></label>
              <input class="field-input" type="number" name="annee" min="1990" max="2026" placeholder="Ex: 2022" oninput="checkFilled(this)">
            </div>
            <div class="field">
              <label class="field-label">Kilométrage <span class="req">*</span></label>
              <input class="field-input" type="number" name="kilometrage" min="0" placeholder="Ex: 85000" oninput="checkFilled(this)">
            </div>
          </div>

          <div class="grid-2">
            <div class="field">
              <label class="field-label">Carburant <span class="req">*</span></label>
              <select class="field-select" name="carburant" onchange="checkFilled(this)">
                <option value="">Choisir...</option>
                <option>Essence</option><option>Diesel</option><option>GPL</option>
                <option>Hybride</option><option>Électrique</option>
              </select>
            </div>
            <div class="field">
              <label class="field-label">Transmission <span class="req">*</span></label>
              <select class="field-select" name="transmission" onchange="checkFilled(this)">
                <option value="">Choisir...</option>
                <option>Manuelle</option><option>Automatique</option><option>Semi-automatique</option>
              </select>
            </div>
          </div>

          <div class="grid-3">
            <div class="field">
              <label class="field-label">Puissance (CV)</label>
              <input class="field-input" type="number" name="puissance" min="0" placeholder="Ex: 130" oninput="checkFilled(this)">
            </div>
            <div class="field">
              <label class="field-label">Cylindrée (cm³)</label>
              <input class="field-input" type="number" name="cylindree" min="0" placeholder="Ex: 1500" oninput="checkFilled(this)">
            </div>
            <div class="field">
              <label class="field-label">État</label>
              <select class="field-select" name="etat" onchange="checkFilled(this)">
                <option value="occasion">Occasion</option>
                <option value="neuf">Neuf</option>
                <option value="accidente">Accidenté</option>
              </select>
            </div>
          </div>

          <div class="grid-2">
            <div class="field">
              <label class="field-label">Nb portes</label>
              <select class="field-select" name="portes" onchange="checkFilled(this)">
                <option value="">Choisir...</option>
                <option>2</option><option>3</option><option>4</option><option>5</option>
              </select>
            </div>
            <div class="field">
              <label class="field-label">Nb places</label>
              <select class="field-select" name="places" onchange="checkFilled(this)">
                <option value="">Choisir...</option>
                <option>2</option><option>4</option><option>5</option><option>7</option><option>9</option>
              </select>
            </div>
          </div>
        </div>

        <div class="actions">
          <button type="button" class="btn-prev" onclick="goStep(1)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Précédent
          </button>
          <button type="button" class="btn-next" onclick="goStep(3)">
            Étape suivante
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </button>
        </div>
      </div>


      <!-- ETAPE 3 : PHOTOS & DESCRIPTION -->
      <div class="step-content" data-step-content="3">
        <div class="step-banner">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
          Astuce : les annonces avec 5+ photos reçoivent 3× plus de contacts
        </div>

        <div class="form-card">
          <div class="form-card-h">
            <div class="form-card-h-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </div>
            <div>
              <div class="form-card-h-title">Photos du véhicule <span class="req">*</span></div>
              <div class="form-card-h-sub">1 à 20 photos · La 1ère sera la photo principale</div>
            </div>
          </div>

          <input type="file" id="photo-input" multiple accept="image/jpeg,image/png,image/webp" style="display:none" onchange="handlePhotoUpload(event)">

          <div class="photo-grid" id="photo-grid">
            <div class="photo-slot" onclick="document.getElementById('photo-input').click()">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Ajouter
            </div>
          </div>

          <div class="photo-info">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <div>
              <div class="photo-info-title">Conseils pour de bonnes photos</div>
              <div>Formats : JPG, PNG, WEBP · Max 5 Mo par photo · <span id="photo-counter">0/20 photos ajoutées</span></div>
            </div>
          </div>
        </div>

        <div class="form-card">
          <div class="form-card-h">
            <div class="form-card-h-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="14" y2="18"/></svg>
            </div>
            <div>
              <div class="form-card-h-title">Titre & description</div>
              <div class="form-card-h-sub">Décrivez votre véhicule pour attirer l'attention</div>
            </div>
          </div>

          <div class="field">
            <label class="field-label">Titre de l'annonce <span class="req">*</span></label>
            <input class="field-input" type="text" name="titre" id="f-titre" maxlength="100" placeholder="Ex: Peugeot 208 GT Line - 1.5 BlueHDi - 130 ch" oninput="updateCounter('f-titre', 'titre-counter', 100); checkFilled(this)">
            <div class="field-hint">
              <span>Soyez précis : marque, modèle, version, motorisation</span>
              <span><span id="titre-counter">0</span>/100 caractères</span>
            </div>
          </div>

          <div class="field">
            <label class="field-label">Description détaillée <span class="req">*</span></label>
            <textarea class="field-textarea" name="description" id="f-desc" maxlength="2000" placeholder="Décrivez l'état général, l'historique, les équipements particuliers, l'entretien..." oninput="updateCounter('f-desc', 'desc-counter', 2000)"></textarea>
            <div class="field-hint">
              <span>Min. 50 caractères · Plus c'est détaillé, plus vous attirez d'acheteurs sérieux</span>
              <span><span id="desc-counter">0</span>/2000</span>
            </div>
          </div>
        </div>

        <div class="form-card">
          <div class="form-card-h">
            <div class="form-card-h-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </div>
            <div>
              <div class="form-card-h-title">Équipements & options</div>
              <div class="form-card-h-sub">Cochez tout ce qui est inclus dans le véhicule</div>
            </div>
          </div>

          <div class="options-subtitle">Confort</div>
          <div class="options-grid">
            <label class="option"><input type="checkbox" name="equipements[]" value="Climatisation"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Climatisation</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="Clim auto bi-zone"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Clim auto bi-zone</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="Sièges cuir"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Sièges cuir</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="Sièges chauffants"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Sièges chauffants</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="Sièges électriques"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Sièges électriques</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="Toit ouvrant"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Toit ouvrant</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="Toit panoramique"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Toit panoramique</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="Volant cuir"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Volant cuir</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="Démarrage bouton"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Démarrage bouton</label>
          </div>

          <div class="options-subtitle">Multimédia</div>
          <div class="options-grid">
            <label class="option"><input type="checkbox" name="equipements[]" value="GPS"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>GPS / Navigation</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="Bluetooth"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Bluetooth</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="Apple CarPlay"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Apple CarPlay</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="Android Auto"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Android Auto</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="Écran tactile"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Écran tactile</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="Prise USB"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Prise USB</label>
          </div>

          <div class="options-subtitle">Sécurité & conduite</div>
          <div class="options-grid">
            <label class="option"><input type="checkbox" name="equipements[]" value="Caméra de recul"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Caméra de recul</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="Radar avant"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Radar avant</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="Radar arrière"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Radar arrière</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="Régulateur vitesse"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Régulateur</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="ABS"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>ABS</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="ESP"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>ESP</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="Airbags"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Airbags</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="Phares LED"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Phares LED</label>
            <label class="option"><input type="checkbox" name="equipements[]" value="Jantes alu"><span class="cb"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Jantes alu</label>
          </div>
        </div>

        <div class="form-card">
          <div class="form-card-h">
            <div class="form-card-h-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.999 6.059 17.5 2 12 2z"/></svg>
            </div>
            <div>
              <div class="form-card-h-title">Couleurs</div>
              <div class="form-card-h-sub">Sélectionnez les couleurs du véhicule</div>
            </div>
          </div>

          <div class="field">
            <label class="field-label">Couleur extérieure</label>
            <input type="hidden" name="couleur_ext" id="couleur-ext-input">
            <div class="color-grid">
              <div class="color-pick" data-color="Noir" data-target="couleur-ext-input" onclick="selectColor(this)"><div class="color-dot" style="background:#1a1a18"></div>Noir</div>
              <div class="color-pick" data-color="Blanc" data-target="couleur-ext-input" onclick="selectColor(this)"><div class="color-dot" style="background:#fff;border:1px solid #ccc"></div>Blanc</div>
              <div class="color-pick" data-color="Gris" data-target="couleur-ext-input" onclick="selectColor(this)"><div class="color-dot" style="background:#9ca3af"></div>Gris</div>
              <div class="color-pick" data-color="Argent" data-target="couleur-ext-input" onclick="selectColor(this)"><div class="color-dot" style="background:linear-gradient(135deg,#f7f7f7,#aaa)"></div>Argent</div>
              <div class="color-pick" data-color="Rouge" data-target="couleur-ext-input" onclick="selectColor(this)"><div class="color-dot" style="background:#dc2626"></div>Rouge</div>
              <div class="color-pick" data-color="Bleu" data-target="couleur-ext-input" onclick="selectColor(this)"><div class="color-dot" style="background:#185FA5"></div>Bleu</div>
              <div class="color-pick" data-color="Vert" data-target="couleur-ext-input" onclick="selectColor(this)"><div class="color-dot" style="background:#16a34a"></div>Vert</div>
              <div class="color-pick" data-color="Jaune" data-target="couleur-ext-input" onclick="selectColor(this)"><div class="color-dot" style="background:#facc15"></div>Jaune</div>
              <div class="color-pick" data-color="Orange" data-target="couleur-ext-input" onclick="selectColor(this)"><div class="color-dot" style="background:#f97316"></div>Orange</div>
              <div class="color-pick" data-color="Marron" data-target="couleur-ext-input" onclick="selectColor(this)"><div class="color-dot" style="background:#78350f"></div>Marron</div>
              <div class="color-pick" data-color="Beige" data-target="couleur-ext-input" onclick="selectColor(this)"><div class="color-dot" style="background:#d6b47b"></div>Beige</div>
            </div>
          </div>

          <div class="field" style="margin-bottom:0">
            <label class="field-label">Couleur intérieure</label>
            <input type="hidden" name="couleur_int" id="couleur-int-input">
            <div class="color-grid">
              <div class="color-pick" data-color="Noir" data-target="couleur-int-input" onclick="selectColor(this)"><div class="color-dot" style="background:#1a1a18"></div>Noir</div>
              <div class="color-pick" data-color="Gris" data-target="couleur-int-input" onclick="selectColor(this)"><div class="color-dot" style="background:#9ca3af"></div>Gris</div>
              <div class="color-pick" data-color="Beige" data-target="couleur-int-input" onclick="selectColor(this)"><div class="color-dot" style="background:#d6b47b"></div>Beige</div>
              <div class="color-pick" data-color="Marron" data-target="couleur-int-input" onclick="selectColor(this)"><div class="color-dot" style="background:#78350f"></div>Marron</div>
              <div class="color-pick" data-color="Rouge" data-target="couleur-int-input" onclick="selectColor(this)"><div class="color-dot" style="background:#dc2626"></div>Rouge</div>
              <div class="color-pick" data-color="Blanc" data-target="couleur-int-input" onclick="selectColor(this)"><div class="color-dot" style="background:#fff;border:1px solid #ccc"></div>Blanc</div>
            </div>
          </div>
        </div>

        <div class="actions">
          <button type="button" class="btn-prev" onclick="goStep(2)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Précédent
          </button>
          <button type="button" class="btn-next" onclick="goStep(4)">
            Étape suivante
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </button>
        </div>
      </div>


      <!-- ETAPE 4 : PRIX & CONDITIONS -->
      <div class="step-content" data-step-content="4">
        <div class="step-banner">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
          Un prix juste = vente plus rapide. Consultez des annonces similaires.
        </div>

        <div class="form-card">
          <div class="form-card-h">
            <div class="form-card-h-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div>
              <div class="form-card-h-title">Prix de vente <span class="req">*</span></div>
              <div class="form-card-h-sub">Indiquez le montant souhaité en dinars algériens</div>
            </div>
          </div>

          <div class="price-row">
            <div class="price-input-wrap">
              <input type="number" name="prix" id="f-prix" min="0" step="1000" placeholder="0" oninput="checkPrice()">
              <span class="price-unit">DA</span>
            </div>
          </div>

          <div class="price-toggles">
            <label class="price-chip">
              <input type="checkbox" name="negociable">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6l3 3l3-3"/><path d="M3 12l3 3l3-3"/><path d="M3 18l3 3l3-3"/></svg>
              Prix négociable
            </label>
            <label class="price-chip">
              <input type="checkbox" name="credit">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
              Crédit accepté
            </label>
            <label class="price-chip">
              <input type="checkbox" name="echange">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
              Échange possible
            </label>
          </div>
        </div>

        <div class="form-card">
          <div class="form-card-h">
            <div class="form-card-h-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div>
              <div class="form-card-h-title">Localisation</div>
              <div class="form-card-h-sub">Wilaya où se trouve le véhicule</div>
            </div>
          </div>

          <div class="field" style="margin-bottom:0">
            <label class="field-label">Wilaya <span class="req">*</span></label>
            <select class="field-select" name="wilaya" id="f-wilaya" onchange="checkFilled(this)">
              <option value="">Choisir une wilaya...</option>
              <?php
              $wilayas = ['Adrar','Aïn Defla','Aïn Témouchent','Alger','Annaba','Batna','Béchar','Béjaïa','Biskra','Blida','Bordj Bou Arréridj','Bouira','Boumerdès','Chlef','Constantine','Djelfa','El Bayadh','El Oued','El Tarf','Ghardaïa','Guelma','Illizi','Jijel','Khenchela','Laghouat','Mascara','Médéa','Mila','Mostaganem','M\'Sila','Naâma','Oran','Ouargla','Oum El Bouaghi','Relizane','Saïda','Sétif','Sidi Bel Abbès','Skikda','Souk Ahras','Tamanrasset','Tébessa','Tiaret','Tindouf','Tipaza','Tissemsilt','Tizi Ouzou','Tlemcen'];
              foreach ($wilayas as $w) echo "<option>" . htmlspecialchars($w) . "</option>";
              ?>
            </select>
          </div>
        </div>

        <div class="form-card">
          <div class="form-card-h">
            <div class="form-card-h-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <div>
              <div class="form-card-h-title">Téléphone de contact <span class="req">*</span></div>
              <div class="form-card-h-sub">Les acheteurs vous contacteront sur ce numéro</div>
            </div>
          </div>

          <div class="field" style="margin-bottom:0">
            <label class="field-label">Numéro de téléphone</label>
            <?php if (!empty($user['numTel'])): ?>
              <div class="input-with-icon">
                <input class="field-input" type="tel" name="telephone" value="<?= htmlspecialchars($user['numTel']) ?>" readonly>
                <span class="input-icon-right">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
              </div>
              <div class="field-hint"><span>Numéro déjà enregistré dans votre profil</span></div>
            <?php else: ?>
              <input class="field-input" type="tel" name="telephone" placeholder="+213 XX XX XX XX" oninput="checkFilled(this)">
              <div class="field-hint"><span>Sera ajouté à votre profil pour les futures annonces</span></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="actions">
          <button type="button" class="btn-prev" onclick="goStep(3)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Précédent
          </button>
          <button type="button" class="btn-next" onclick="goStep(5)">
            Voir l'aperçu
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </button>
        </div>
      </div>

      <!-- ETAPE 5 : APERCU & PUBLIER -->
      <div class="step-content" data-step-content="5">
        <div class="step-banner">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          Vérifiez les informations puis publiez votre annonce
        </div>

        <div class="form-card">
          <div class="form-card-h">
            <div class="form-card-h-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </div>
            <div>
              <div class="form-card-h-title">Aperçu de votre annonce</div>
              <div class="form-card-h-sub">Voici comment elle apparaîtra dans les résultats de recherche</div>
            </div>
          </div>

          <div class="preview-card">
            <div class="preview-img" id="preview-img">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.8"><rect x="1" y="6" width="22" height="13" rx="3"/><circle cx="7" cy="16" r="1.5"/><circle cx="17" cy="16" r="1.5"/></svg>
            </div>
            <div class="preview-body">
              <div class="preview-title" id="preview-title">Titre de votre annonce</div>
              <div class="preview-sub" id="preview-sub">Type · Transmission · Puissance</div>
              <div class="preview-price" id="preview-price">0 DA</div>
              <div class="preview-specs" id="preview-specs"></div>
            </div>
          </div>
        </div>

        <div class="recap-section">
          <div class="recap-section-h">
            <div class="recap-section-title">Caractéristiques</div>
            <a class="recap-edit" onclick="goStep(2)">Modifier</a>
          </div>
          <div id="recap-caracteristiques"></div>
        </div>

        <div class="recap-section">
          <div class="recap-section-h">
            <div class="recap-section-title">Photos & description</div>
            <a class="recap-edit" onclick="goStep(3)">Modifier</a>
          </div>
          <div id="recap-photos"></div>
        </div>

        <div class="recap-section">
          <div class="recap-section-h">
            <div class="recap-section-title">Prix & contact</div>
            <a class="recap-edit" onclick="goStep(4)">Modifier</a>
          </div>
          <div id="recap-prix"></div>
        </div>

        <div class="actions">
          <button type="button" class="btn-prev" onclick="goStep(4)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Précédent
          </button>
          <button type="button" class="btn-publish" id="btn-publish" onclick="submitForm()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Publier l'annonce
          </button>
        </div>
      </div>

    </form>
  </div>


  <script>
    /* ============================================ */
    /* ===   DONNÉES MARQUES / MODÈLES          === */
    /* ============================================ */
    const MARQUES = {
      voiture: {
        Toyota:['Corolla','Yaris','Camry','RAV4','Hilux','Land Cruiser','Auris','Avensis','C-HR','Prius'],
        Hyundai:['Tucson','Elantra','Santa Fe','i10','i20','i30','Creta','Accent','Kona','Sonata'],
        Renault:['Clio','Megane','Duster','Kadjar','Logan','Talisman','Symbol','Captur','Express','Trafic'],
        Peugeot:['208','308','3008','5008','Partner','Rifter','2008','508','Expert','Boxer'],
        Volkswagen:['Golf','Polo','Passat','Tiguan','T-Roc','Caddy','Touareg','Jetta','Amarok'],
        BMW:['Série 1','Série 2','Série 3','Série 5','Série 7','X1','X2','X3','X5','X6','X7'],
        Mercedes:['Classe A','Classe G','Classe B','Classe C','Classe E','Classe S','GLA','GLC','GLE','GLS','Vito','Sprinter'],
        Kia:['Sportage','Cerato','Picanto','Sorento','Rio','Stonic','Carnival','Optima'],
        Dacia:['Duster','Logan','Sandero','Dokker','Lodgy','Stepway','Spring'],
        Ford:['Focus','Fiesta','Kuga','Puma','Ranger','Mondeo','EcoSport','Transit'],
        Suzuki:['Swift','Vitara','Jimny','S-Cross','Baleno','Celerio'],
        Mitsubishi:['Pajero','L200','ASX','Outlander','Eclipse Cross','Lancer'],
        Nissan:['Qashqai','Juke','Micra','X-Trail','Patrol','Navara','Sunny'],
        Skoda:['Octavia','Fabia','Kodiaq','Karoq','Superb','Scala'],
        Citroën:['C3','C4','C5','Berlingo','C-Elysée','C5 Aircross','Jumper'],
        Fiat:['Tipo','500','Panda','Doblo','Punto','Ducato'],
        Seat:['Ibiza','Leon','Ateca','Arona','Tarraco'],
        Audi:['A1','A3','A4','A5','A6','A7','A8','Q2','Q3','Q5','Q7','Q8'],
        Chery:['Tiggo 4','Tiggo 7','Tiggo 8','Arrizo 5','Arrizo 6'],
        Geely:['Coolray','Emgrand','Atlas','Tugella'],
      },
      moto: {
        Honda:['CB500','CBR600','Africa Twin','Forza 300','PCX','MSX 125','CB125','CRF250'],
        Yamaha:['MT-07','MT-09','YZF-R3','TMAX','XMAX','MT-03','YZF-R1','XSR700'],
        Kawasaki:['Z650','Ninja 400','Z900','Versys','Z1000','Vulcan'],
        KTM:['Duke 390','Duke 790','Adventure 390','RC 200','Duke 125','SX-F'],
        Suzuki:['GSX-R 600','V-Strom','GSX-S 750','Hayabusa','Burgman'],
        BMW:['R 1250 GS','S 1000 RR','F 850 GS','R nineT','G 310'],
        Ducati:['Monster','Panigale V4','Multistrada','Scrambler'],
      },
      camion: {
        Mercedes:['Actros','Axor','Atego','Sprinter','Vario','Antos','Arocs'],
        Volvo:['FH','FM','FMX','FE','FL'],
        MAN:['TGX','TGS','TGM','TGL','TGE'],
        Scania:['R Series','S Series','P Series','G Series'],
        Iveco:['Daily','Eurocargo','Stralis','S-Way'],
        Renault:['Master','T-Series','C-Series','D-Series','K-Series'],
        DAF:['XF','CF','LF','XG'],
      }
    };

    /* ============================================ */
    /* ===   ÉTAT GLOBAL                         === */
    /* ============================================ */
    let currentStep = 1;
    let uploadedPhotos = [];
    const totalSteps = 5;

    /* ============================================ */
    /* ===   NAVIGATION ENTRE ÉTAPES             === */
    /* ============================================ */
    function goStep(step) {
      if (step > currentStep) {
        if (!validateStep(currentStep)) return;
      }

      document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
      document.querySelector(`[data-step-content="${step}"]`).classList.add('active');

      document.querySelectorAll('.step').forEach((el, i) => {
        const stepNum = i + 1;
        el.classList.remove('active', 'done');
        if (stepNum < step) el.classList.add('done');
        if (stepNum === step) el.classList.add('active');
      });

      document.querySelectorAll('.step-line').forEach((el, i) => {
        el.classList.toggle('done', i < step - 1);
      });

      const percent = Math.round((step / totalSteps) * 100);
      document.getElementById('progress-bar').style.width = percent + '%';
      document.getElementById('progress-percent').textContent = percent + '% complété';

      const titles = ['Type de véhicule', 'Caractéristiques', 'Photos & description', 'Prix & conditions', 'Aperçu & publier'];
      document.getElementById('progress-step').textContent = `Étape ${step} sur ${totalSteps} — ${titles[step-1]}`;

      currentStep = step;
      window.scrollTo({ top: 0, behavior: 'smooth' });

      if (step === 5) updatePreview();
    }

    /* ============================================ */
    /* ===   VALIDATION PAR ÉTAPE                === */
    /* ============================================ */
    function validateStep(step) {
      clearAlert();

      if (step === 1) {
        if (!document.getElementById('f-type').value) {
          showAlert('error', 'Sélectionnez un type de véhicule');
          return false;
        }
      }

      if (step === 2) {
        const required = ['marque', 'modele', 'annee', 'kilometrage', 'carburant', 'transmission'];
        const labels = { marque:'Marque', modele:'Modèle', annee:'Année', kilometrage:'Kilométrage', carburant:'Carburant', transmission:'Transmission' };
        for (const name of required) {
          const el = document.querySelector(`[name="${name}"]`);
          if (!el.value) {
            el.classList.add('error');
            el.focus();
            showAlert('error', `Le champ "${labels[name]}" est obligatoire`);
            return false;
          }
        }
        const annee = parseInt(document.querySelector('[name="annee"]').value);
        if (annee < 1900 || annee > 2030) {
          showAlert('error', 'Année invalide (1900-2030)');
          return false;
        }
      }

      if (step === 3) {
        if (uploadedPhotos.length === 0) {
          showAlert('error', 'Ajoutez au moins 1 photo');
          return false;
        }
        const titre = document.getElementById('f-titre').value.trim();
        const desc = document.getElementById('f-desc').value.trim();
        if (titre.length < 10) {
          showAlert('error', 'Le titre doit contenir au moins 10 caractères');
          return false;
        }
        if (desc.length < 50) {
          showAlert('error', 'La description doit contenir au moins 50 caractères');
          return false;
        }
      }

      if (step === 4) {
        const prix = parseFloat(document.getElementById('f-prix').value);
        if (!prix || prix <= 0) {
          showAlert('error', 'Le prix doit être supérieur à 0');
          return false;
        }
        if (!document.getElementById('f-wilaya').value) {
          showAlert('error', 'Sélectionnez une wilaya');
          return false;
        }
        const tel = document.querySelector('[name="telephone"]').value.trim();
        if (!tel) {
          showAlert('error', 'Le téléphone est obligatoire');
          return false;
        }
      }

      return true;
    }

    /* ============================================ */
    /* ===   ÉTAPE 1 : SÉLECTION TYPE            === */
    /* ============================================ */
    function selectType(card) {
      document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
      document.getElementById('f-type').value = card.dataset.type;
      buildMarques();
    }

    /* ============================================ */
    /* ===   ÉTAPE 2 : MARQUES / MODÈLES         === */
    /* ============================================ */
    function buildMarques() {
      const type = document.getElementById('f-type').value;
      const sel = document.getElementById('f-marque');
      sel.innerHTML = '<option value="">Choisir...</option>';
      Object.keys(MARQUES[type] || {}).sort().forEach(m => {
        const o = document.createElement('option');
        o.value = m; o.textContent = m;
        sel.appendChild(o);
      });
      const optAutre = document.createElement('option');
      optAutre.value = 'Autre'; optAutre.textContent = 'Autre...';
      sel.appendChild(optAutre);

      document.getElementById('f-modele').innerHTML = '<option value="">Choisir...</option>';
    }

    function updateModeles() {
      const type = document.getElementById('f-type').value;
      const marque = document.getElementById('f-marque').value;
      const sel = document.getElementById('f-modele');
      sel.innerHTML = '<option value="">Choisir...</option>';
      const list = (MARQUES[type] || {})[marque];
      if (list) {
        list.forEach(m => {
          const o = document.createElement('option');
          o.value = m; o.textContent = m;
          sel.appendChild(o);
        });
      }
      const optAutre = document.createElement('option');
      optAutre.value = 'Autre'; optAutre.textContent = 'Autre...';
      sel.appendChild(optAutre);
    }

    /* ============================================ */
    /* ===   FIELD INDICATORS                    === */
    /* ============================================ */
    function checkFilled(el) {
      el.classList.toggle('filled', !!el.value);
      el.classList.remove('error');
    }
    function checkPrice() {
      const el = document.getElementById('f-prix');
      el.classList.toggle('filled', el.value && parseFloat(el.value) > 0);
    }
    function updateCounter(inputId, counterId, max) {
      const val = document.getElementById(inputId).value;
      const counter = document.getElementById(counterId);
      counter.textContent = val.length;
      counter.style.color = val.length > max * 0.9 ? 'var(--red)' : '';
    }

    /* ============================================ */
    /* ===   ÉTAPE 3 : UPLOAD PHOTOS             === */
    /* ============================================ */
    function handlePhotoUpload(e) {
      const files = Array.from(e.target.files);

      for (const file of files) {
        if (uploadedPhotos.length >= 20) {
          alert('Maximum 20 photos autorisées');
          break;
        }
        if (file.size > 5 * 1024 * 1024) {
          alert(`"${file.name}" dépasse 5 Mo`);
          continue;
        }
        if (!['image/jpeg','image/png','image/webp'].includes(file.type)) {
          alert(`"${file.name}" : format non supporté (JPG/PNG/WEBP uniquement)`);
          continue;
        }

        const reader = new FileReader();
        reader.onload = (ev) => {
          uploadedPhotos.push({ file: file, dataUrl: ev.target.result });
          renderPhotos();
        };
        reader.readAsDataURL(file);
      }

      e.target.value = '';
    }

    function renderPhotos() {
      const grid = document.getElementById('photo-grid');
      grid.innerHTML = '';

      uploadedPhotos.forEach((p, idx) => {
        const slot = document.createElement('div');
        slot.className = 'photo-slot uploaded' + (idx === 0 ? ' main' : '');
        slot.style.backgroundImage = `url(${p.dataUrl})`;

        if (idx === 0) {
          const badge = document.createElement('div');
          badge.className = 'photo-badge';
          badge.textContent = 'Principale';
          slot.appendChild(badge);
        }

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'photo-remove';
        removeBtn.innerHTML = '×';
        removeBtn.onclick = () => {
          uploadedPhotos.splice(idx, 1);
          renderPhotos();
        };
        slot.appendChild(removeBtn);

        grid.appendChild(slot);
      });

      if (uploadedPhotos.length < 20) {
        const addSlot = document.createElement('div');
        addSlot.className = 'photo-slot';
        addSlot.onclick = () => document.getElementById('photo-input').click();
        addSlot.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Ajouter';
        grid.appendChild(addSlot);
      }

      document.getElementById('photo-counter').textContent = `${uploadedPhotos.length}/20 photos ajoutées`;
    }

    /* ============================================ */
    /* ===   COULEURS                            === */
    /* ============================================ */
    function selectColor(box) {
      const targetId = box.dataset.target;
      const color = box.dataset.color;
      const hidden = document.getElementById(targetId);

      const sameTarget = document.querySelectorAll(`.color-pick[data-target="${targetId}"]`);

      if (hidden.value === color) {
        sameTarget.forEach(c => c.classList.remove('selected'));
        hidden.value = '';
      } else {
        sameTarget.forEach(c => c.classList.remove('selected'));
        box.classList.add('selected');
        hidden.value = color;
      }
    }

    /* ============================================ */
    /* ===   ALERTES                             === */
    /* ============================================ */
    function showAlert(type, message) {
      const zone = document.getElementById('alert-zone');
      const icon = type === 'error'
        ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'
        : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
      zone.innerHTML = `<div class="alert alert-${type}">${icon}<div>${message}</div></div>`;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function clearAlert() {
      document.getElementById('alert-zone').innerHTML = '';
    }

    /* ============================================ */
    /* ===   ÉTAPE 5 : PRÉVISUALISATION          === */
    /* ============================================ */
    function updatePreview() {
      const fd = new FormData(document.getElementById('publish-form'));
      const data = {};
      const equipements = [];
      for (const [k, v] of fd.entries()) {
        if (k === 'equipements[]') {
          equipements.push(v);
        } else {
          data[k] = v;
        }
      }

      /* Image principale */
      const previewImg = document.getElementById('preview-img');
      if (uploadedPhotos.length > 0) {
        previewImg.style.backgroundImage = `url(${uploadedPhotos[0].dataUrl})`;
        previewImg.innerHTML = '';
      }

      /* Titre */
      const titre = data.titre || `${data.marque || ''} ${data.modele || ''} ${data.version || ''}`.trim();
      document.getElementById('preview-title').textContent = titre || 'Titre de votre annonce';

      /* Sous-titre */
      const subParts = [];
      if (data.puissance) subParts.push(data.puissance + ' ch');
      if (data.carburant) subParts.push(data.carburant);
      if (data.transmission) subParts.push(data.transmission);
      document.getElementById('preview-sub').textContent = subParts.join(' · ') || '—';

      /* Prix */
      const prix = parseFloat(data.prix || 0);
      document.getElementById('preview-price').textContent = prix.toLocaleString('fr-FR').replace(/,/g, ' ') + ' DA';

      /* Specs */
      const specs = document.getElementById('preview-specs');
      specs.innerHTML = '';
      const specList = [];
      if (data.annee) specList.push(data.annee);
      if (data.kilometrage) specList.push(parseInt(data.kilometrage).toLocaleString('fr-FR').replace(/,/g, ' ') + ' km');
      if (data.carburant) specList.push(data.carburant);
      if (data.transmission) specList.push(data.transmission);
      if (data.couleur_ext) specList.push(data.couleur_ext);
      specList.forEach(s => {
        const sp = document.createElement('span');
        sp.className = 'preview-spec';
        sp.textContent = s;
        specs.appendChild(sp);
      });

      /* RECAP CARACTÉRISTIQUES */
      const car = document.getElementById('recap-caracteristiques');
      car.innerHTML =
        recapRow('Type', cap(data.type)) +
        recapRow('Marque / Modèle', `${data.marque || '—'} ${data.modele || ''} ${data.version || ''}`.trim()) +
        recapRow('Année', data.annee || '—') +
        recapRow('Kilométrage', data.kilometrage ? parseInt(data.kilometrage).toLocaleString('fr-FR').replace(/,/g, ' ') + ' km' : '—') +
        recapRow('Carburant', data.carburant || '—') +
        recapRow('Transmission', data.transmission || '—') +
        (data.puissance ? recapRow('Puissance', data.puissance + ' ch') : '') +
        (data.cylindree ? recapRow('Cylindrée', data.cylindree + ' cm³') : '') +
        recapRow('État', cap(data.etat)) +
        (data.portes ? recapRow('Portes', data.portes) : '') +
        (data.places ? recapRow('Places', data.places) : '') +
        (data.couleur_ext ? recapRow('Couleur ext.', data.couleur_ext) : '') +
        (data.couleur_int ? recapRow('Couleur int.', data.couleur_int) : '');

      /* RECAP PHOTOS & DESC */
      const ph = document.getElementById('recap-photos');
      ph.innerHTML =
        recapRow('Photos', uploadedPhotos.length + ' photo(s) ajoutée(s)') +
        recapRow('Titre', data.titre ? (data.titre.length > 60 ? data.titre.substring(0, 60) + '…' : data.titre) : '—') +
        recapRow('Description', (data.description || '').length + ' caractères') +
        recapRow('Équipements', equipements.length > 0 ? equipements.length + ' option(s) cochée(s)' : 'Aucun');

      /* RECAP PRIX */
      const pr = document.getElementById('recap-prix');
      const conditions = [];
      if (data.negociable) conditions.push('Négociable');
      if (data.credit) conditions.push('Crédit accepté');
      if (data.echange) conditions.push('Échange possible');

      pr.innerHTML =
        recapRow('Prix', prix.toLocaleString('fr-FR').replace(/,/g, ' ') + ' DA') +
        recapRow('Conditions', conditions.length > 0 ? conditions.join(', ') : 'Aucune') +
        recapRow('Wilaya', data.wilaya || '—') +
        recapRow('Téléphone', data.telephone || '—');
    }

    function recapRow(key, val) {
      return `<div class="recap-row"><span class="recap-key">${key}</span><span class="recap-val">${val}</span></div>`;
    }
    function cap(s) {
      return s ? s.charAt(0).toUpperCase() + s.slice(1) : '—';
    }

    /* ============================================ */
    /* ===   SOUMISSION FINALE                   === */
    /* ============================================ */
    async function submitForm() {
      clearAlert();
      const btn = document.getElementById('btn-publish');
      btn.disabled = true;
      btn.innerHTML = '<div class="spinner"></div>Publication en cours...';

      const fd = new FormData(document.getElementById('publish-form'));

      /* Ajouter les photos uploadées */
      uploadedPhotos.forEach(p => fd.append('photos[]', p.file));

      try {
        const res = await fetch('publier.php', {
          method: 'POST',
          body: fd
        });
        const text = await res.text();
        let json;
        try {
          json = JSON.parse(text);
        } catch (e) {
          throw new Error('Réponse serveur invalide : ' + text.substring(0, 200));
        }

        if (json.success) {
          showAlert('success', 'Annonce publiée avec succès ! Redirection...');
          setTimeout(() => location.href = json.redirect, 1500);
        } else {
          btn.disabled = false;
          btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Publier l\'annonce';
          showAlert('error', json.errors ? json.errors.join('. ') : 'Erreur lors de la publication');
        }
      } catch (err) {
        btn.disabled = false;
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Publier l\'annonce';
        showAlert('error', 'Erreur : ' + err.message);
      }
    }

    /* ============================================ */
    /* ===   INITIALISATION                      === */
    /* ============================================ */
    buildMarques();
    goStep(1);
  </script>

</body>
</html>
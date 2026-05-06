<?php
function detecterDoublon($conn, $vin, $immat, $marque, $modele, $annee, $km, $prix, $wilaya) {
    $score = 0;
    $motifs = [];

    $vin = mysqli_real_escape_string($conn, trim($vin));
    $immat = mysqli_real_escape_string($conn, trim($immat));

    // 1. VIN identique
    if ($vin !== '') {
        $q = mysqli_query($conn, "
            SELECT a.idAnnonce 
            FROM Annonce a
            JOIN Vehicule v ON a.idVehicule = v.idVehicule
            WHERE v.vin = '$vin'
            AND a.statutAnnonce != 'supprimee'
            LIMIT 1
        ");

        if (mysqli_num_rows($q) > 0) {
            $score += 80;
            $motifs[] = "VIN identique";
        }
    }

    // 2. Immatriculation identique
    if ($immat !== '') {
        $q = mysqli_query($conn, "
            SELECT a.idAnnonce 
            FROM Annonce a
            JOIN Vehicule v ON a.idVehicule = v.idVehicule
            WHERE v.immatriculation = '$immat'
            AND a.statutAnnonce != 'supprimee'
            LIMIT 1
        ");

        if (mysqli_num_rows($q) > 0) {
            $score += 70;
            $motifs[] = "Immatriculation identique";
        }
    }

    // 3. Similarité véhicule
    $marque = mysqli_real_escape_string($conn, trim($marque));
    $modele = mysqli_real_escape_string($conn, trim($modele));
    $wilaya = mysqli_real_escape_string($conn, trim($wilaya));

    $anneeMin = (int)$annee - 1;
    $anneeMax = (int)$annee + 1;
    $kmMin = max(0, (int)$km - 10000);
    $kmMax = (int)$km + 10000;
    $prixMin = max(0, (int)$prix * 0.90);
    $prixMax = (int)$prix * 1.10;

    $q = mysqli_query($conn, "
        SELECT a.idAnnonce
        FROM Annonce a
        JOIN Vehicule v ON a.idVehicule = v.idVehicule
        WHERE v.marque = '$marque'
        AND v.modele = '$modele'
        AND v.annee BETWEEN $anneeMin AND $anneeMax
        AND v.kilometrage BETWEEN $kmMin AND $kmMax
        AND a.prix BETWEEN $prixMin AND $prixMax
        AND a.localisation = '$wilaya'
        AND a.statutAnnonce != 'supprimee'
        LIMIT 1
    ");

    if (mysqli_num_rows($q) > 0) {
        $score += 45;
        $motifs[] = "Annonce très similaire";
    }

    if ($score >= 80) {
        $statut = "en_attente";
    } elseif ($score >= 45) {
        $statut = "suspecte";
    } else {
        $statut = "validee";
    }

    return [
        "score" => $score,
        "statut" => $statut,
        "motif" => implode(", ", $motifs)
    ];
}
?>
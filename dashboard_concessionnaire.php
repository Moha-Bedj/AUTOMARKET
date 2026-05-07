<?php
session_start();

require_once 'connexion.php';
require_once 'notification_helper.php';

if (!isset($_SESSION['idUtilisateur'])) {
    header("Location: inscription.php?redirect=dashboard_concessionnaire.php");
    exit;
}

$idUser = $_SESSION['idUtilisateur'];
$idUserSql = mysqli_real_escape_string($conn, $idUser);

/* =========================
   VÉRIFIER COMPTE PRO
========================= */
$sqlPro = "
SELECT 
    u.idUtilisateur,
    u.nom,
    u.prenom,
    u.email,
    u.numTel,
    u.wilaya,
    u.role,
    u.dateInscription,
    u.badgeVerifie,
    c.nomEntreprise,
    c.adresseEntreprise,
    c.siteWeb,
    c.numRegistreCommerce,
    c.statutPro,
    c.justificatifRegistre
FROM Utilisateur u
LEFT JOIN Concessionnaire c ON c.idUtilisateur = u.idUtilisateur
WHERE u.idUtilisateur = '$idUserSql'
LIMIT 1
";

$rPro = mysqli_query($conn, $sqlPro);

if (!$rPro) {
    die("Erreur SQL concessionnaire : " . mysqli_error($conn));
}

$pro = mysqli_fetch_assoc($rPro);

if (!$pro || empty($pro['nomEntreprise'])) {
    header("Location: index.php");
    exit;
}

if (($pro['statutPro'] ?? '') !== 'valide' || ($pro['role'] ?? '') !== 'concessionnaire') {
    header("Location: verification_compte_pro.php");
    exit;
}

/* =========================
   DONNÉES ENTREPRISE
========================= */
$nomEntreprise = $pro['nomEntreprise'] ?: 'Compte Pro';
$adresse = $pro['adresseEntreprise'] ?: ($pro['wilaya'] ?: '-');
$initiales = strtoupper(substr($nomEntreprise, 0, 1));

$parts = explode(' ', trim($nomEntreprise));
if (count($parts) >= 2) {
    $initiales = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
}

$dateInscription = !empty($pro['dateInscription'])
    ? date('m/Y', strtotime($pro['dateInscription']))
    : '-';

/* =========================
   STATISTIQUES
========================= */
$totalAnnonces = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM Annonce 
    WHERE idVendeur = '$idUserSql'
"))['total'] ?? 0;

$annoncesActives = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM Annonce 
    WHERE idVendeur = '$idUserSql'
    AND statutAnnonce = 'active'
"))['total'] ?? 0;

$annoncesAttente = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM Annonce 
    WHERE idVendeur = '$idUserSql'
    AND statutAnnonce = 'en_attente'
"))['total'] ?? 0;

$totalVues = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(nbrVus), 0) AS total
    FROM Annonce
    WHERE idVendeur = '$idUserSql'
"))['total'] ?? 0;

$contactsRecus = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM Conversation
    WHERE idVendeur = '$idUserSql'
"))['total'] ?? 0;

$messagesNonLus = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM Message m
    JOIN Conversation c ON c.idConversation = m.idConversation
    WHERE c.idVendeur = '$idUserSql'
    AND m.idUtilisateur != '$idUserSql'
    AND m.statutLecture = 0
"))['total'] ?? 0;

$annoncesMois = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM Annonce
    WHERE idVendeur = '$idUserSql'
    AND MONTH(datePublication) = MONTH(CURDATE())
    AND YEAR(datePublication) = YEAR(CURDATE())
"))['total'] ?? 0;

/* =========================
   TOP ANNONCES
========================= */
$sqlTop = "
SELECT 
    a.idAnnonce,
    a.titre,
    a.prix,
    a.nbrVus,
    a.statutAnnonce,
    a.datePublication,
    v.annee,
    v.kilometrage,
    ma.nomMarque,
    mo.nomModele,
    (
        SELECT COUNT(*) 
        FROM Conversation c 
        WHERE c.idAnnonce = a.idAnnonce
    ) AS nbContacts
FROM Annonce a
LEFT JOIN Vehicule v ON v.idVehicule = a.idVehicule
LEFT JOIN modele mo ON mo.idModele = v.idModele
LEFT JOIN marque ma ON ma.idMarque = mo.idMarque
WHERE a.idVendeur = '$idUserSql'
ORDER BY a.nbrVus DESC
LIMIT 4
";

$rTop = mysqli_query($conn, $sqlTop);

/* =========================
   ANNONCES RÉCENTES
========================= */
$sqlRecent = "
SELECT 
    a.idAnnonce,
    a.titre,
    a.prix,
    a.nbrVus,
    a.statutAnnonce,
    a.datePublication,
    v.annee,
    v.kilometrage,
    ma.nomMarque,
    mo.nomModele,
    (
        SELECT urlPhoto 
        FROM Photos p 
        WHERE p.idAnnonce = a.idAnnonce 
        ORDER BY ordrePhoto ASC 
        LIMIT 1
    ) AS photo,
    (
        SELECT COUNT(*) 
        FROM Conversation c 
        WHERE c.idAnnonce = a.idAnnonce
    ) AS nbContacts
FROM Annonce a
LEFT JOIN Vehicule v ON v.idVehicule = a.idVehicule
LEFT JOIN modele mo ON mo.idModele = v.idModele
LEFT JOIN marque ma ON ma.idMarque = mo.idMarque
WHERE a.idVendeur = '$idUserSql'
ORDER BY a.datePublication DESC
LIMIT 5
";

$rRecent = mysqli_query($conn, $sqlRecent);

/* =========================
   ACTIVITÉ RÉCENTE
========================= */
$sqlNotif = "
SELECT contenu, typeNoti, dateNoti
FROM Notification
WHERE idUtilisateur = '$idUserSql'
ORDER BY dateNoti DESC
LIMIT 4
";

$rNotif = mysqli_query($conn, $sqlNotif);

function formatPrix($prix) {
    return number_format((float)$prix, 0, ',', ' ') . ' DA';
}

function badgeStatut($statut) {
    $s = strtolower($statut ?? '');

    if ($s === 'active') {
        return '<span class="badge badge-active">Active</span>';
    }

    if ($s === 'en_attente') {
        return '<span class="badge badge-wait">En attente</span>';
    }

    if ($s === 'refusee' || $s === 'refuse') {
        return '<span class="badge badge-refused">Refusée</span>';
    }

    if ($s === 'vendue') {
        return '<span class="badge badge-sold">Vendue</span>';
    }

    return '<span class="badge badge-default">'.htmlspecialchars($statut ?: '-').'</span>';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Dashboard Pro — AUTOMARKET</title>
<link rel="icon" href="images/logo.png">

<style>
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

:root {
  --blue: #185FA5;
  --blue-dk: #0C447C;
  --blue-bg: #E6F1FB;
  --green: #639922;
  --green-bg: #EAF3DE;
  --green-dk: #27500A;
  --red: #E24B4A;
  --red-bg: #FCEBEB;
  --amber: #BA7517;
  --amber-bg: #FAEEDA;
  --bg0: #ffffff;
  --bg1: #f5f4f0;
  --bg2: #eceae4;
  --t1: #1a1a18;
  --t2: #5f5e5a;
  --t3: #888780;
  --bd: rgba(0,0,0,.11);
  --bd2: rgba(0,0,0,.22);
  --r6: 6px;
  --r8: 8px;
  --r10: 10px;
  --r14: 14px;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: var(--bg1);
  color: var(--t1);
  min-height: 100vh;
  font-size: 14px;
}

.pro-wrapper {
  max-width: 1180px;
  margin: 0 auto;
  padding: 18px;
}

.topbar {
  background: var(--bg0);
  border: 0.5px solid var(--bd);
  border-radius: var(--r14) var(--r14) 0 0;
  padding: 12px 20px;
  display: flex;
  align-items: center;
  gap: 16px;
}

.logo-pro {
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
}

.logo-pro-icon {
  width: 34px;
  height: 34px;
  object-fit: contain;
  display: block;
}

.logo-pro-name {
  height: 22px;
  width: auto;
  object-fit: contain;
  display: block;
}

.pro-tag {
  font-size: 10px;
  padding: 2px 6px;
  background: var(--blue);
  color: #fff;
  border-radius: 4px;
  margin-left: 2px;
  font-weight: 600;
}

.top-spacer {
  flex: 1;
}

.verified {
  font-size: 11px;
  padding: 4px 10px;
  background: var(--green-bg);
  color: var(--green-dk);
  border-radius: 10px;
  border: 0.5px solid #C0DD97;
  font-weight: 600;
}

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--blue);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
}

.header {
  background: var(--bg0);
  padding: 20px 28px;
  border-left: 0.5px solid var(--bd);
  border-right: 0.5px solid var(--bd);
  border-bottom: 0.5px solid var(--bd);
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 16px;
  align-items: center;
}

.company-logo {
  width: 58px;
  height: 58px;
  border-radius: 14px;
  background: var(--blue);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: 600;
}

.header h1 {
  font-size: 21px;
  font-weight: 600;
  margin-bottom: 5px;
}

.meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  font-size: 12px;
  color: var(--t2);
}

.header-actions {
  display: flex;
  gap: 8px;
}

.btn {
  border: none;
  padding: 9px 14px;
  border-radius: var(--r8);
  text-decoration: none;
  font-size: 12px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-weight: 500;
}

.btn-primary {
  background: var(--blue);
  color: #fff;
}

.btn-primary:hover {
  background: var(--blue-dk);
}

.btn-light {
  background: var(--bg0);
  color: var(--t1);
  border: 0.5px solid var(--bd);
}

.tabs {
  background: var(--bg0);
  padding: 0 28px;
  border-left: 0.5px solid var(--bd);
  border-right: 0.5px solid var(--bd);
  border-bottom: 0.5px solid var(--bd);
  display: flex;
  gap: 4px;
  overflow-x: auto;
}

.tab {
  font-size: 13px;
  padding: 12px 16px;
  color: var(--t2);
  text-decoration: none;
  white-space: nowrap;
}

.tab.active {
  color: var(--blue);
  border-bottom: 2px solid var(--blue);
  font-weight: 600;
}

.tab-count {
  font-size: 10px;
  padding: 1px 6px;
  background: var(--bg2);
  border-radius: 8px;
  margin-left: 4px;
}

.content {
  background: var(--bg1);
  padding: 18px 16px 16px;
  border-radius: 0 0 var(--r14) var(--r14);
}

.welcome {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}

.welcome-title {
  font-size: 19px;
  font-weight: 600;
}

.welcome-sub {
  font-size: 12px;
  color: var(--t2);
  margin-top: 3px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  margin-bottom: 14px;
}

.stat-card {
  background: var(--bg0);
  padding: 15px 16px;
  border-radius: var(--r10);
  border: 0.5px solid var(--bd);
}

.stat-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.stat-label {
  font-size: 11px;
  color: var(--t2);
}

.stat-number {
  font-size: 26px;
  font-weight: 600;
}

.stat-note {
  font-size: 11px;
  color: var(--green);
  margin-top: 4px;
}

.grid-main {
  display: grid;
  grid-template-columns: 1.5fr 1fr;
  gap: 12px;
  margin-bottom: 14px;
}

.panel {
  background: var(--bg0);
  padding: 18px 20px;
  border-radius: var(--r14);
  border: 0.5px solid var(--bd);
}

.panel-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}

.panel h3 {
  font-size: 15px;
  font-weight: 600;
}

.link {
  font-size: 12px;
  color: var(--blue);
  text-decoration: none;
}

.chart {
  width: 100%;
  height: 180px;
}

.top-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.top-item {
  display: flex;
  gap: 10px;
  align-items: center;
}

.rank {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: var(--amber-bg);
  color: var(--amber);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 600;
  flex-shrink: 0;
}

.top-text {
  flex: 1;
  min-width: 0;
}

.top-title {
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.top-sub {
  font-size: 10px;
  color: var(--t2);
}

.two-cols {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 14px;
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.activity-item {
  display: flex;
  gap: 10px;
  padding-bottom: 12px;
  border-bottom: 0.5px solid var(--bd);
}

.activity-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.activity-icon {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: var(--blue-bg);
  color: var(--blue);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.activity-text {
  font-size: 12px;
  line-height: 1.4;
}

.activity-date {
  font-size: 10px;
  color: var(--t3);
  margin-top: 2px;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}

th {
  padding: 10px 12px;
  text-align: left;
  font-weight: 600;
  color: var(--t2);
  font-size: 11px;
  text-transform: uppercase;
  background: var(--bg2);
}

td {
  padding: 12px;
  border-bottom: 0.5px solid var(--bd);
  vertical-align: middle;
}

.ad-cell {
  display: flex;
  gap: 10px;
  align-items: center;
}

.ad-photo {
  width: 48px;
  height: 36px;
  border-radius: 5px;
  background: var(--bg2);
  object-fit: cover;
  flex-shrink: 0;
}

.ad-title {
  font-weight: 600;
}

.ad-sub {
  font-size: 10px;
  color: var(--t2);
}

.badge {
  font-size: 10px;
  padding: 3px 8px;
  border-radius: 10px;
  font-weight: 600;
}

.badge-active {
  background: var(--green-bg);
  color: var(--green-dk);
}

.badge-wait {
  background: var(--amber-bg);
  color: var(--amber);
}

.badge-refused {
  background: var(--red-bg);
  color: #791F1F;
}

.badge-sold {
  background: var(--bg2);
  color: var(--t2);
}

.badge-default {
  background: var(--bg2);
  color: var(--t2);
}

.empty {
  color: var(--t3);
  font-size: 13px;
  padding: 20px;
  text-align: center;
}

@media(max-width: 900px) {
  .header {
    grid-template-columns: auto 1fr;
  }

  .header-actions {
    grid-column: 1 / -1;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .grid-main,
  .two-cols {
    grid-template-columns: 1fr;
  }
}

@media(max-width: 600px) {
  .pro-wrapper {
    padding: 10px;
  }

  .topbar,
  .header {
    padding: 14px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .welcome {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
}
</style>
</head>

<body>

<div class="pro-wrapper">

  <div class="topbar">
   <a class="logo-pro" href="index.php">
      <img src="images/logo.png" alt="Logo AUTOMARKET" class="logo-pro-icon">
      <img src="images/id.png" alt="AUTOMARKET" class="logo-pro-name">
      <span class="pro-tag">PRO</span>
    </a>

    <div class="top-spacer"></div>

    <span class="verified">✓ Vérifié</span>
    <span><?= htmlspecialchars($nomEntreprise) ?></span>
    <div class="avatar"><?= htmlspecialchars($initiales) ?></div>
  </div>

  <div class="header">
    <div class="company-logo"><?= htmlspecialchars($initiales) ?></div>

    <div>
      <h1><?= htmlspecialchars($nomEntreprise) ?></h1>

      <div class="meta">
        <span><?= htmlspecialchars($adresse) ?></span>
        <span>·</span>
        <span>Membre depuis <?= htmlspecialchars($dateInscription) ?></span>
        <span>·</span>
        <span style="color:#BA7517;">★ Compte professionnel vérifié</span>
      </div>
    </div>

    <div class="header-actions">
      <a href="monprofil.php" class="btn btn-light">Paramètres</a>
      <a href="publier.php" class="btn btn-primary">+ Publier annonce</a>
    </div>
  </div>

  <div class="tabs">
    <a href="dashboard_concessionnaire.php" class="tab active">Vue d'ensemble</a>
    <a href="mesannonces.php" class="tab">
      Mes annonces <span class="tab-count"><?= intval($totalAnnonces) ?></span>
    </a>
    <a href="messagerie.php" class="tab">
      Messages <span class="tab-count"><?= intval($messagesNonLus) ?></span>
    </a>
    <a href="#" class="tab">Statistiques</a>
    <a href="#" class="tab">Avis clients</a>
    <a href="#" class="tab">Facturation</a>
  </div>

  <?php include 'notif_banner.php'; ?>

  <div class="content">

    <div class="welcome">
      <div>
        <div class="welcome-title">Bonjour, <?= htmlspecialchars($nomEntreprise) ?> 👋</div>
        <div class="welcome-sub">Voici votre activité actuelle sur AUTOMARKET</div>
      </div>
    </div>

    <div class="stats-grid">

      <div class="stat-card">
        <div class="stat-head">
          <span class="stat-label">Annonces actives</span>
        </div>
        <div class="stat-number"><?= intval($annoncesActives) ?></div>
        <div class="stat-note"><?= intval($annoncesMois) ?> annonce(s) publiée(s) ce mois</div>
      </div>

      <div class="stat-card">
        <div class="stat-head">
          <span class="stat-label">Vues totales</span>
        </div>
        <div class="stat-number"><?= intval($totalVues) ?></div>
        <div class="stat-note">Toutes vos annonces</div>
      </div>

      <div class="stat-card">
        <div class="stat-head">
          <span class="stat-label">Contacts reçus</span>
        </div>
        <div class="stat-number"><?= intval($contactsRecus) ?></div>
        <div class="stat-note"><?= intval($messagesNonLus) ?> message(s) non lu(s)</div>
      </div>

      <div class="stat-card">
        <div class="stat-head">
          <span class="stat-label">Annonces en attente</span>
        </div>
        <div class="stat-number"><?= intval($annoncesAttente) ?></div>
        <div class="stat-note">Validation / modération</div>
      </div>

    </div>

    <div class="grid-main">

      <div class="panel">
        <div class="panel-head">
          <h3>Vues & contacts</h3>
          <span class="link">Vue simplifiée</span>
        </div>

        <svg viewBox="0 0 540 200" class="chart">
          <line x1="40" y1="20" x2="540" y2="20" stroke="rgba(0,0,0,0.04)" stroke-width="1"/>
          <line x1="40" y1="60" x2="540" y2="60" stroke="rgba(0,0,0,0.04)" stroke-width="1"/>
          <line x1="40" y1="100" x2="540" y2="100" stroke="rgba(0,0,0,0.04)" stroke-width="1"/>
          <line x1="40" y1="140" x2="540" y2="140" stroke="rgba(0,0,0,0.04)" stroke-width="1"/>

          <text x="32" y="24" text-anchor="end" font-size="9" fill="#888780">800</text>
          <text x="32" y="64" text-anchor="end" font-size="9" fill="#888780">600</text>
          <text x="32" y="104" text-anchor="end" font-size="9" fill="#888780">400</text>
          <text x="32" y="144" text-anchor="end" font-size="9" fill="#888780">200</text>
          <text x="32" y="184" text-anchor="end" font-size="9" fill="#888780">0</text>

          <path d="M 50 150 Q 90 120, 130 130 T 220 100 T 320 80 T 420 70 T 520 60 L 520 180 L 50 180 Z" fill="#185FA5" opacity="0.08"/>
          <path d="M 50 150 Q 90 120, 130 130 T 220 100 T 320 80 T 420 70 T 520 60" fill="none" stroke="#185FA5" stroke-width="2"/>
          <path d="M 50 170 Q 90 160, 130 162 T 220 150 T 320 140 T 420 132 T 520 128" fill="none" stroke="#FF8A4C" stroke-width="2"/>

          <text x="50" y="195" font-size="9" fill="#888780">S1</text>
          <text x="180" y="195" font-size="9" fill="#888780">S2</text>
          <text x="310" y="195" font-size="9" fill="#888780">S3</text>
          <text x="430" y="195" font-size="9" fill="#888780">S4</text>
        </svg>
      </div>

      <div class="panel">
        <div class="panel-head">
          <h3>Top annonces</h3>
          <a href="mesannonces.php" class="link">Tout voir →</a>
        </div>

        <div class="top-list">
          <?php if ($rTop && mysqli_num_rows($rTop) > 0): ?>
            <?php $rank = 1; while ($a = mysqli_fetch_assoc($rTop)): ?>
              <div class="top-item">
                <div class="rank"><?= $rank ?></div>
                <div class="top-text">
                  <div class="top-title">
                    <?= htmlspecialchars($a['titre'] ?: (($a['nomMarque'] ?? '') . ' ' . ($a['nomModele'] ?? ''))) ?>
                  </div>
                  <div class="top-sub">
                    <?= intval($a['nbrVus']) ?> vues · <?= intval($a['nbContacts']) ?> contacts
                  </div>
                </div>
              </div>
              <?php $rank++; ?>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="empty">Aucune annonce pour le moment.</div>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <div class="two-cols">

      <div class="panel">
        <div class="panel-head">
          <h3>Activité récente</h3>
          <a href="notifications.php" class="link">Tout voir →</a>
        </div>

        <div class="activity-list">
          <?php if ($rNotif && mysqli_num_rows($rNotif) > 0): ?>
            <?php while ($n = mysqli_fetch_assoc($rNotif)): ?>
              <?php
                $texteNotif = $n['contenu'];
                if (strpos($texteNotif, '|||') !== false) {
                    $partsNotif = explode('|||', $texteNotif, 2);
                    $texteNotif = $partsNotif[0];
                }
              ?>
              <div class="activity-item">
                <div class="activity-icon">●</div>
                <div>
                  <div class="activity-text"><?= htmlspecialchars($texteNotif) ?></div>
                  <div class="activity-date"><?= htmlspecialchars($n['dateNoti']) ?></div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="empty">Aucune activité récente.</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head">
          <h3>Performance rapide</h3>
        </div>

        <div style="font-size:13px;color:var(--t2);line-height:1.7">
          <p><strong><?= intval($annoncesActives) ?></strong> annonce(s) active(s)</p>
          <p><strong><?= intval($totalVues) ?></strong> vue(s) cumulée(s)</p>
          <p><strong><?= intval($contactsRecus) ?></strong> contact(s) reçu(s)</p>
          <p><strong><?= intval($annoncesAttente) ?></strong> annonce(s) en attente</p>
        </div>
      </div>

    </div>

    <div class="panel">
      <div class="panel-head">
        <h3>Annonces récentes</h3>
        <a href="mesannonces.php" class="link">Gérer toutes →</a>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Annonce</th>
              <th>Statut</th>
              <th style="text-align:right">Prix</th>
              <th style="text-align:right">Vues</th>
              <th style="text-align:right">Contacts</th>
              <th style="text-align:center">Action</th>
            </tr>
          </thead>

          <tbody>
          <?php if ($rRecent && mysqli_num_rows($rRecent) > 0): ?>
            <?php while ($a = mysqli_fetch_assoc($rRecent)): ?>
              <tr>
                <td>
                  <div class="ad-cell">
                    <?php if (!empty($a['photo'])): ?>
                      <img src="<?= htmlspecialchars($a['photo']) ?>" class="ad-photo" alt="">
                    <?php else: ?>
                      <div class="ad-photo"></div>
                    <?php endif; ?>

                    <div>
                      <div class="ad-title">
                        <?= htmlspecialchars($a['titre'] ?: (($a['nomMarque'] ?? '') . ' ' . ($a['nomModele'] ?? ''))) ?>
                      </div>
                      <div class="ad-sub">
                        <?= htmlspecialchars($a['annee'] ?? '-') ?> · 
                        <?= number_format((int)($a['kilometrage'] ?? 0), 0, ',', ' ') ?> km
                      </div>
                    </div>
                  </div>
                </td>

                <td>
                  <?= badgeStatut($a['statutAnnonce']) ?>
                </td>

                <td style="text-align:right;font-weight:600;color:#185FA5">
                  <?= formatPrix($a['prix']) ?>
                </td>

                <td style="text-align:right">
                  <?= intval($a['nbrVus']) ?>
                </td>

                <td style="text-align:right">
                  <?= intval($a['nbContacts']) ?>
                </td>

                <td style="text-align:center">
                  <a href="fiche_annonce.php?id=<?= urlencode($a['idAnnonce']) ?>" class="link">Voir</a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="empty">
                Aucune annonce récente.
              </td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

</div>

</body>
</html>
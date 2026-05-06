<?php
session_start();

require_once 'auth_admin.php';
require_once 'connexion.php';

/* Vérifier connexion admin */
if (!isset($_SESSION['idUtilisateur'])) {
    header("Location: inscription.php");
    exit;
}

/* =========================
   STATISTIQUES DASHBOARD
========================= */

$totalAnnonces = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) total FROM Annonce
"))['total'] ?? 0;

$annoncesActives = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) total FROM Annonce WHERE statutAnnonce='active'
"))['total'] ?? 0;

$annoncesAttente = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) total FROM Annonce WHERE statutAnnonce='en_attente'
"))['total'] ?? 0;

$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) total FROM Utilisateur
"))['total'] ?? 0;

$vendeursVerifies = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) total FROM Utilisateur WHERE badgeVerifie=1
"))['total'] ?? 0;

$demandesProAttente = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) total 
    FROM Concessionnaire 
    WHERE statutPro IN ('en_attente_verification', 'en_attente_admin')"))['total'] ?? 0;

$demandesProTotal = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) total 
    FROM Concessionnaire
"))['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Admin — Tableau de bord</title>
<link rel="icon" href="images/logo.png">

<style>
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: Arial, sans-serif;
  background: #f5f4f0;
  color: #1a1a18;
}

.admin-layout {
  display: flex;
  min-height: 100vh;
}

.sidebar {
  width: 250px;
  background: #0C447C;
  padding: 24px 18px;
  color: white;
}

.logo {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 30px;
}

.logo img {
  width: 140px;
  filter: brightness(0) invert(1);
}

.menu a {
  display: block;
  color: rgba(255,255,255,.85);
  text-decoration: none;
  padding: 12px 14px;
  border-radius: 8px;
  margin-bottom: 8px;
  font-size: 14px;
}

.menu a:hover,
.menu a.active {
  background: rgba(255,255,255,.15);
  color: #fff;
}

.main {
  flex: 1;
  padding: 28px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 26px;
}

.header h1 {
  font-size: 28px;
}

.admin-name {
  color: #5f5e5a;
  font-size: 14px;
}

.cards {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 18px;
  margin-bottom: 30px;
}

.card-link {
  text-decoration: none;
  color: inherit;
  display: block;
}

.card {
  background: white;
  border-radius: 14px;
  padding: 20px;
  border: 1px solid rgba(0,0,0,.1);
  box-shadow: 0 6px 18px rgba(0,0,0,.06);
  transition: 0.15s;
  min-height: 110px;
}

.card-link:hover .card {
  border-color: #185FA5;
  transform: translateY(-2px);
  box-shadow: 0 8px 22px rgba(0,0,0,.08);
}

.card.warning {
  background: #FAEEDA;
  border-color: #FAC775;
}

.card-title {
  font-size: 13px;
  color: #777;
  margin-bottom: 12px;
}

.card-number {
  font-size: 30px;
  font-weight: bold;
  color: #185FA5;
}

.card.warning .card-number {
  color: #BA7517;
}

.section {
  background: white;
  border-radius: 14px;
  padding: 22px;
  border: 1px solid rgba(0,0,0,.1);
}

.section h2 {
  font-size: 20px;
  margin-bottom: 18px;
}

.quick-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.quick-actions a {
  background: #185FA5;
  color: white;
  padding: 11px 18px;
  border-radius: 8px;
  text-decoration: none;
  font-size: 14px;
}

.quick-actions a:hover {
  background: #0C447C;
}

.quick-actions a.warning-action {
  background: #BA7517;
}

.quick-actions a.warning-action:hover {
  background: #854F0B;
}

@media(max-width: 1200px) {
  .cards {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media(max-width: 1000px) {
  .sidebar {
    width: 220px;
  }
}

@media(max-width: 700px) {
  .admin-layout {
    flex-direction: column;
  }

  .sidebar {
    width: 100%;
  }

  .cards {
    grid-template-columns: 1fr;
  }

  .header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
}
</style>
</head>

<body>

<div class="admin-layout">

  <aside class="sidebar">

    <div class="logo">
      <img src="images/logo.png" alt="AUTOMARKET">
    </div>

    <nav class="menu">
      <a href="admin_dashboard.php" class="active">Tableau de bord</a>
      <a href="admin_annonces.php">Annonces</a>
      <a href="admin_utilisateurs.php">Utilisateurs</a>
      <a href="admin_demandes_pro.php">Demandes Pro</a>
      <a href="admin_marques.php">Marques / Modèles</a>
      <a href="admin_equipements.php">Équipements</a>
      <a href="admin_signalements.php">Signalements</a>
      <a href="index.php">Retour au site</a>
    </nav>

  </aside>

  <main class="main">

    <div class="header">
      <h1>Tableau de bord</h1>

      <div class="admin-name">
        Connecté : <?= htmlspecialchars($_SESSION['prenom'] ?? 'Admin') ?>
      </div>
    </div>

    <div class="cards">

      <div class="card">
        <div class="card-title">Total annonces</div>
        <div class="card-number"><?= intval($totalAnnonces) ?></div>
      </div>

      <div class="card">
        <div class="card-title">Annonces actives</div>
        <div class="card-number"><?= intval($annoncesActives) ?></div>
      </div>

      <div class="card">
        <div class="card-title">Annonces en attente</div>
        <div class="card-number"><?= intval($annoncesAttente) ?></div>
      </div>

      <a href="admin_demandes_pro.php" class="card-link">
        <div class="card warning">
          <div class="card-title">Demandes Pro</div>
          <div class="card-number"><?= intval($demandesProAttente) ?></div>
        </div>
      </a>

      <div class="card">
        <div class="card-title">Utilisateurs</div>
        <div class="card-number"><?= intval($totalUsers) ?></div>
      </div>

      <div class="card">
        <div class="card-title">Vendeurs vérifiés</div>
        <div class="card-number"><?= intval($vendeursVerifies) ?></div>
      </div>

    </div>

    <div class="section">
      <h2>Actions rapides</h2>

      <div class="quick-actions">
        <a href="admin_annonces.php">Gérer les annonces</a>
        <a href="admin_utilisateurs.php">Gérer les utilisateurs</a>
        <a href="admin_demandes_pro.php" class="warning-action">Valider comptes Pro</a>
        <a href="admin_marques.php">Ajouter marque / modèle</a>
        <a href="admin_signalements.php">Voir signalements</a>
      </div>
    </div>

  </main>

</div>

</body>
</html>
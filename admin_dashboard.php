<?php
session_start();

require_once 'auth_admin.php';
require_once 'connexion.php';

/* À adapter plus tard avec rôle admin */
if (!isset($_SESSION['idUtilisateur'])) {
    header("Location: inscription.php");
    exit;
}

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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Admin — Tableau de bord</title>

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
  width: 240px;
  background: #0C447C;
  color: white;
  padding: 24px 18px;
}

.logo {
  display: flex;
  align-items: center;
  justify-content: center; /* 👈 bien à gauche */
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
  grid-template-columns: repeat(5, 1fr);
  gap: 18px;
  margin-bottom: 30px;
}

.card {
  background: white;
  border-radius: 14px;
  padding: 20px;
  border: 1px solid rgba(0,0,0,.1);
  box-shadow: 0 6px 18px rgba(0,0,0,.06);
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

@media(max-width: 1000px) {
  .cards {
    grid-template-columns: repeat(2, 1fr);
  }

  .sidebar {
    width: 200px;
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
        <div class="card-number"><?= $totalAnnonces ?></div>
      </div>

      <div class="card">
        <div class="card-title">Annonces actives</div>
        <div class="card-number"><?= $annoncesActives ?></div>
      </div>

      <div class="card">
        <div class="card-title">En attente</div>
        <div class="card-number"><?= $annoncesAttente ?></div>
      </div>

      <div class="card">
        <div class="card-title">Utilisateurs</div>
        <div class="card-number"><?= $totalUsers ?></div>
      </div>

      <div class="card">
        <div class="card-title">Vendeurs vérifiés</div>
        <div class="card-number"><?= $vendeursVerifies ?></div>
      </div>
    </div>

    <div class="section">
      <h2>Actions rapides</h2>

      <div class="quick-actions">
        <a href="admin_annonces.php">Gérer les annonces</a>
        <a href="admin_utilisateurs.php">Gérer les utilisateurs</a>
        <a href="admin_marques.php">Ajouter marque / modèle</a>
        <a href="admin_signalements.php">Voir signalements</a>
      </div>
    </div>

  </main>

</div>

</body>
</html>
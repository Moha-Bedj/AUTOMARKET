<?php
session_start();
require_once 'connexion.php';

/* AJOUT */
if (isset($_POST['add_equipement'])) {
    $libelle = mysqli_real_escape_string($conn, trim($_POST['libelleEquipement']));
    $categorie = mysqli_real_escape_string($conn, trim($_POST['categorieEquipement']));

    if ($libelle !== '') {
        $id = uniqid();
        mysqli_query($conn, "
            INSERT INTO equipement (idEquipement, libelleEquipement, categorieEquipement)
            VALUES ('$id', '$libelle', '$categorie')
        ");
    }

    header("Location: admin_equipements.php");
    exit;
}

/* SUPPRESSION */
if (isset($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    mysqli_query($conn, "DELETE FROM equipement WHERE idEquipement='$id'");
    header("Location: admin_equipements.php");
    exit;
}

$equipements = mysqli_query($conn, "
    SELECT *
    FROM equipement
    ORDER BY categorieEquipement, libelleEquipement
");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Admin - Équipements</title>

<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f5f4f0;color:#1a1a18}
.admin-layout{display:flex;min-height:100vh}
.sidebar{width:250px;background:#0C447C;padding:24px 18px;color:white}
.logo{margin-bottom:30px}
.logo img{width:145px;filter:brightness(0) invert(1)}
.menu{display:flex;flex-direction:column;gap:8px}
.menu a{color:rgba(255,255,255,.75);text-decoration:none;padding:11px 12px;border-radius:8px;font-size:14px}
.menu a:hover,.menu a.active{background:rgba(255,255,255,.15);color:white}

.main{flex:1;padding:28px}
h1{font-size:26px;margin-bottom:24px}
.grid{display:grid;grid-template-columns:1fr 2fr;gap:18px;margin-bottom:24px}
.card{background:white;border:1px solid rgba(0,0,0,.1);border-radius:14px;padding:18px;box-shadow:0 8px 22px rgba(0,0,0,.06)}
.card h2{font-size:18px;margin-bottom:14px}
input,select{width:100%;height:40px;border:1px solid rgba(0,0,0,.2);border-radius:8px;padding:0 10px;margin-bottom:10px}
button{height:40px;border:none;border-radius:8px;background:#185FA5;color:white;font-weight:600;cursor:pointer;width:100%}
button:hover{background:#0C447C}

.table-card{background:white;border-radius:14px;border:1px solid rgba(0,0,0,.1);overflow:hidden;margin-bottom:22px}
.table-title{padding:16px;font-size:18px;font-weight:600;background:#fff}
table{width:100%;border-collapse:collapse}
th{background:#f5f4f0;text-align:left;font-size:12px;color:#5f5e5a;padding:13px}
td{padding:13px;border-top:1px solid rgba(0,0,0,.08);font-size:13px}
.delete{background:#FCEBEB;color:#791F1F;padding:7px 10px;border-radius:7px;text-decoration:none;font-size:12px}
.badge{background:#E6F1FB;color:#185FA5;padding:4px 8px;border-radius:20px;font-size:11px}
</style>
</head>

<body>

<div class="admin-layout">

<aside class="sidebar">
  <div class="logo">
    <img src="images/logo.png" alt="AUTOMARKET">
  </div>

  <nav class="menu">
    <a href="admin_dashboard.php">Tableau de bord</a>
    <a href="admin_annonces.php">Annonces</a>
    <a href="admin_utilisateurs.php">Utilisateurs</a>
    <a href="admin_marques.php">Marques / Modèles</a>
    <a href="admin_equipements.php" class="active">Équipements</a>
    <a href="admin_signalements.php">Signalements</a>
    <a href="index.php">Retour au site</a>
  </nav>
</aside>

<main class="main">

<h1>Gestion des équipements</h1>

<div class="grid">

  <div class="card">
    <h2>Ajouter un équipement</h2>

    <form method="post">
      <input type="text" name="libelleEquipement" placeholder="Ex : Bluetooth" required>

      <select name="categorieEquipement">
        <option value="">Catégorie</option>
        <option value="Confort">Confort</option>
        <option value="Sécurité">Sécurité</option>
        <option value="Technologie">Technologie</option>
        <option value="Extérieur">Extérieur</option>
        <option value="Historique">État & historique</option>
      </select>

      <button type="submit" name="add_equipement">Ajouter</button>
    </form>
  </div>

  <div class="card">
    <h2>Résumé</h2>
    <p style="font-size:14px;color:#5f5e5a;line-height:1.7">
      Ici tu peux gérer les équipements affichés dans tes filtres avancés :
      climatisation, Bluetooth, GPS, caméra de recul, ABS, ESP, airbags, etc.
    </p>
  </div>

</div>

<div class="table-card">
  <div class="table-title">Liste des équipements</div>

  <table>
    <tr>
      <th>Équipement</th>
      <th>Catégorie</th>
      <th>Action</th>
    </tr>

    <?php if ($equipements && mysqli_num_rows($equipements) > 0): ?>
      <?php while($e = mysqli_fetch_assoc($equipements)): ?>
        <tr>
          <td><?= htmlspecialchars($e['libelleEquipement'] ?? '') ?></td>
          <td>
            <span class="badge">
              <?= htmlspecialchars($e['categorieEquipement'] ?? 'Non classé') ?>
            </span>
          </td>
          <td>
            <a class="delete"
               href="admin_equipements.php?delete=<?= $e['idEquipement'] ?>"
               onclick="return confirm('Supprimer cet équipement ?')">
               Supprimer
            </a>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr>
        <td colspan="3" style="text-align:center;color:#888;padding:30px">
          Aucun équipement trouvé.
        </td>
      </tr>
    <?php endif; ?>
  </table>
</div>

</main>
</div>

</body>
</html>
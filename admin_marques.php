<?php
session_start();

require_once 'auth_admin.php';
require_once 'connexion.php';

/* AJOUT MARQUE */
if (isset($_POST['add_marque'])) {
    $nom = mysqli_real_escape_string($conn, trim($_POST['nomMarque']));

    if ($nom !== '') {
        $id = uniqid();
        mysqli_query($conn, "INSERT INTO marque (idMarque, nomMarque) VALUES ('$id', '$nom')");
    }
    header("Location: admin_marques.php");
    exit;
}

/* AJOUT MODELE */
if (isset($_POST['add_modele'])) {
    $nom = mysqli_real_escape_string($conn, trim($_POST['nomModele']));
    $idMarque = mysqli_real_escape_string($conn, $_POST['idMarque']);

    if ($nom !== '' && $idMarque !== '') {
        $id = uniqid();
        mysqli_query($conn, "INSERT INTO modele (idModele, nomModele, idMarque) VALUES ('$id', '$nom', '$idMarque')");
    }
    header("Location: admin_marques.php");
    exit;
}

/* AJOUT VERSION */
if (isset($_POST['add_version'])) {
    $nom = mysqli_real_escape_string($conn, trim($_POST['nomVersion']));
    $finition = mysqli_real_escape_string($conn, trim($_POST['finition']));
    $idModele = mysqli_real_escape_string($conn, $_POST['idModele']);

    if ($nom !== '' && $idModele !== '') {
        $id = uniqid();
        mysqli_query($conn, "INSERT INTO version (idVersion, nomVersion, finition, idModele) VALUES ('$id', '$nom', '$finition', '$idModele')");
    }
    header("Location: admin_marques.php");
    exit;
}

/* SUPPRESSIONS */
if (isset($_GET['delete_marque'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete_marque']);
    mysqli_query($conn, "DELETE FROM marque WHERE idMarque='$id'");
    header("Location: admin_marques.php");
    exit;
}

if (isset($_GET['delete_modele'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete_modele']);
    mysqli_query($conn, "DELETE FROM modele WHERE idModele='$id'");
    header("Location: admin_marques.php");
    exit;
}

if (isset($_GET['delete_version'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete_version']);
    mysqli_query($conn, "DELETE FROM version WHERE idVersion='$id'");
    header("Location: admin_marques.php");
    exit;
}

$marques = mysqli_query($conn, "SELECT * FROM marque ORDER BY nomMarque");
$modeles = mysqli_query($conn, "
    SELECT mo.*, ma.nomMarque 
    FROM modele mo
    JOIN marque ma ON ma.idMarque = mo.idMarque
    ORDER BY ma.nomMarque, mo.nomModele
");

$versions = mysqli_query($conn, "
    SELECT v.*, mo.nomModele, ma.nomMarque
    FROM version v
    JOIN modele mo ON mo.idModele = v.idModele
    JOIN marque ma ON ma.idMarque = mo.idMarque
    ORDER BY ma.nomMarque, mo.nomModele, v.nomVersion
");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Admin - Marques / Modèles / Versions</title>

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
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:24px}
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
    <a href="admin_marques.php" class="active">Marques / Modèles</a>
    <a href="admin_equipements.php">Équipements</a>
    <a href="admin_signalements.php">Signalements</a>
    <a href="index.php">Retour au site</a>
  </nav>
</aside>

<main class="main">

<h1>Gestion des marques, modèles et versions</h1>

<div class="grid">

  <div class="card">
    <h2>Ajouter une marque</h2>
    <form method="post">
      <input type="text" name="nomMarque" placeholder="Ex : Toyota" required>
      <button type="submit" name="add_marque">Ajouter</button>
    </form>
  </div>

  <div class="card">
    <h2>Ajouter un modèle</h2>
    <form method="post">
      <select name="idMarque" required>
        <option value="">Choisir une marque</option>
        <?php
        $m1 = mysqli_query($conn, "SELECT * FROM marque ORDER BY nomMarque");
        while($m = mysqli_fetch_assoc($m1)):
        ?>
          <option value="<?= $m['idMarque'] ?>"><?= htmlspecialchars($m['nomMarque']) ?></option>
        <?php endwhile; ?>
      </select>
      <input type="text" name="nomModele" placeholder="Ex : Corolla" required>
      <button type="submit" name="add_modele">Ajouter</button>
    </form>
  </div>

  <div class="card">
    <h2>Ajouter une version</h2>
    <form method="post">
      <select name="idModele" required>
        <option value="">Choisir un modèle</option>
        <?php
        $mo1 = mysqli_query($conn, "
          SELECT mo.idModele, mo.nomModele, ma.nomMarque
          FROM modele mo
          JOIN marque ma ON ma.idMarque = mo.idMarque
          ORDER BY ma.nomMarque, mo.nomModele
        ");
        while($mo = mysqli_fetch_assoc($mo1)):
        ?>
          <option value="<?= $mo['idModele'] ?>">
            <?= htmlspecialchars($mo['nomMarque'].' - '.$mo['nomModele']) ?>
          </option>
        <?php endwhile; ?>
      </select>

      <input type="text" name="nomVersion" placeholder="Ex : Corolla 1.6" required>
      <input type="text" name="finition" placeholder="Ex : Active, GT Line, AMG Line">
      <button type="submit" name="add_version">Ajouter</button>
    </form>
  </div>

</div>

<div class="table-card">
  <div class="table-title">Marques</div>
  <table>
    <tr>
      <th>Marque</th>
      <th>Action</th>
    </tr>

    <?php while($m = mysqli_fetch_assoc($marques)): ?>
    <tr>
      <td><?= htmlspecialchars($m['nomMarque']) ?></td>
      <td>
        <a class="delete" href="admin_marques.php?delete_marque=<?= $m['idMarque'] ?>" onclick="return confirm('Supprimer cette marque ?')">Supprimer</a>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
</div>

<div class="table-card">
  <div class="table-title">Modèles</div>
  <table>
    <tr>
      <th>Marque</th>
      <th>Modèle</th>
      <th>Action</th>
    </tr>

    <?php while($mo = mysqli_fetch_assoc($modeles)): ?>
    <tr>
      <td><span class="badge"><?= htmlspecialchars($mo['nomMarque']) ?></span></td>
      <td><?= htmlspecialchars($mo['nomModele']) ?></td>
      <td>
        <a class="delete" href="admin_marques.php?delete_modele=<?= $mo['idModele'] ?>" onclick="return confirm('Supprimer ce modèle ?')">Supprimer</a>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
</div>

<div class="table-card">
  <div class="table-title">Versions</div>
  <table>
    <tr>
      <th>Marque</th>
      <th>Modèle</th>
      <th>Version</th>
      <th>Finition</th>
      <th>Action</th>
    </tr>

    <?php while($v = mysqli_fetch_assoc($versions)): ?>
    <tr>
      <td><?= htmlspecialchars($v['nomMarque']) ?></td>
      <td><?= htmlspecialchars($v['nomModele']) ?></td>
      <td><?= htmlspecialchars($v['nomVersion']) ?></td>
      <td><?= htmlspecialchars($v['finition'] ?? '-') ?></td>
      <td>
        <a class="delete" href="admin_marques.php?delete_version=<?= $v['idVersion'] ?>" onclick="return confirm('Supprimer cette version ?')">Supprimer</a>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
</div>

</main>
</div>

</body>
</html>
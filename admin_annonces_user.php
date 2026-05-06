<?php
session_start();

require_once 'auth_admin.php';
require_once 'connexion.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$idUtilisateur = $_GET['id'] ?? '';

if (empty($idUtilisateur)) {
    die("Erreur : aucun utilisateur sélectionné.");
}

/* Récupérer l'utilisateur */
$sqlUser = "
SELECT idUtilisateur, nom, prenom, email
FROM Utilisateur
WHERE idUtilisateur = ?
";

$stmtUser = mysqli_prepare($conn, $sqlUser);

if (!$stmtUser) {
    die("Erreur SQL utilisateur : " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmtUser, "s", $idUtilisateur);
mysqli_stmt_execute($stmtUser);
$resUser = mysqli_stmt_get_result($stmtUser);
$user = mysqli_fetch_assoc($resUser);

if (!$user) {
    die("Utilisateur introuvable.");
}

/* Récupérer ses annonces */
$sql = "
SELECT 
    a.idAnnonce,
    a.titre,
    a.prix,
    a.localisation,
    a.statutAnnonce,
    a.datePublication,
    v.annee,
    v.kilometrage,
    v.carburant
FROM Annonce a
LEFT JOIN Vehicule v ON a.idVehicule = v.idVehicule
WHERE a.idVendeur = ?
ORDER BY a.datePublication DESC
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Erreur SQL annonces : " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "s", $idUtilisateur);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Annonces utilisateur</title>

<style>
*{box-sizing:border-box;margin:0;padding:0}

body{
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
  background:#f5f4f0;
  color:#1a1a18;
  padding:30px;
}

.header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:25px;
}

h1{
  font-size:26px;
}

.back{
  background:#0C447C;
  color:white;
  text-decoration:none;
  padding:10px 14px;
  border-radius:8px;
  font-size:14px;
}

.card{
  background:white;
  border-radius:14px;
  border:1px solid rgba(0,0,0,.1);
  overflow:hidden;
  box-shadow:0 8px 22px rgba(0,0,0,.06);
}

.table{
  width:100%;
  border-collapse:collapse;
}

.table th{
  background:#f5f4f0;
  text-align:left;
  font-size:12px;
  color:#5f5e5a;
  padding:14px;
}

.table td{
  padding:14px;
  border-top:1px solid rgba(0,0,0,.08);
  font-size:13px;
}

.badge{
  padding:4px 9px;
  border-radius:20px;
  font-size:11px;
  font-weight:600;
  background:#E6F1FB;
  color:#185FA5;
}

.price{
  font-weight:700;
  color:#0C447C;
}

.btn-view{
  background:#E6F1FB;
  color:#185FA5;
  border:none;
  padding:7px 10px;
  border-radius:7px;
  cursor:pointer;
  font-size:12px;
}
</style>
</head>

<body>

<div class="header">
  <div>
    <h1>Annonces de l'utilisateur</h1>
    <p>
      <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>
      —
      <?= htmlspecialchars($user['email']) ?>
    </p>
  </div>

  <a href="admin_utilisateurs.php" class="back">Retour</a>
</div>

<div class="card">
<table class="table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Titre</th>
      <th>Prix</th>
      <th>Infos</th>
      <th>Localisation</th>
      <th>Statut</th>
      <th>Date</th>
      <th>Action</th>
    </tr>
  </thead>

  <tbody>
  <?php if ($res && mysqli_num_rows($res) > 0): ?>
    <?php while ($a = mysqli_fetch_assoc($res)): ?>
      <tr>
        <td><?= htmlspecialchars($a['idAnnonce']) ?></td>

        <td><?= htmlspecialchars($a['titre']) ?></td>

        <td class="price">
          <?= number_format($a['prix'], 0, ',', ' ') ?> DA
        </td>

        <td>
          <?= htmlspecialchars($a['annee'] ?? '-') ?> ·
          <?= number_format($a['kilometrage'] ?? 0, 0, ',', ' ') ?> km ·
          <?= htmlspecialchars($a['carburant'] ?? '-') ?>
        </td>

        <td><?= htmlspecialchars($a['localisation'] ?? '-') ?></td>

        <td>
          <span class="badge">
            <?= htmlspecialchars($a['statutAnnonce'] ?? '-') ?>
          </span>
        </td>

        <td><?= htmlspecialchars($a['datePublication'] ?? '-') ?></td>

        <td>
          <button 
            class="btn-view"
            onclick="location.href='ficheAnnonces.php?id=<?= urlencode($a['idAnnonce']) ?>'">
            Voir
          </button>
        </td>
      </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr>
      <td colspan="8" style="text-align:center;padding:30px;color:#888">
        Cet utilisateur n'a aucune annonce.
      </td>
    </tr>
  <?php endif; ?>
  </tbody>
</table>
</div>

</body>
</html>
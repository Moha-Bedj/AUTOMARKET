

<?php
session_start();
require_once 'auth_admin.php';
require_once 'connexion.php';

$sql = "
SELECT a.idAnnonce, a.titre, a.prix, a.localisation, a.statutAnnonce, a.datePublication,
       v.annee, v.kilometrage, v.carburant,
       u.nom, u.prenom
FROM Annonce a
JOIN Vehicule v ON a.idVehicule = v.idVehicule
JOIN Utilisateur u ON a.idVendeur = u.idUtilisateur
ORDER BY a.datePublication DESC
";

$res = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Admin - Gestion des annonces</title>

<style>
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
  justify-content: center;
  margin-bottom: 30px;
}

.logo img {
  width: 145px;
  height: auto;
  filter: brightness(0) invert(1);
}

.menu {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.menu a {
  color: rgba(255,255,255,.75);
  text-decoration: none;
  padding: 11px 12px;
  border-radius: 8px;
  font-size: 14px;
}

.menu a:hover,
.menu a.active {
  background: rgba(255,255,255,.15);
  color: white;
}

.main {
  flex: 1;
  padding: 28px;
}

.topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.topbar h1 {
  font-size: 26px;
  font-weight: 700;
}

.search-box input {
  width: 280px;
  height: 40px;
  border: 1px solid rgba(0,0,0,.2);
  border-radius: 8px;
  padding: 0 12px;
  outline: none;
}

.card {
  background: white;
  border-radius: 14px;
  border: 1px solid rgba(0,0,0,.1);
  overflow: hidden;
  box-shadow: 0 8px 22px rgba(0,0,0,.06);
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th {
  background: #f5f4f0;
  text-align: left;
  font-size: 12px;
  color: #5f5e5a;
  padding: 14px;
}

.table td {
  padding: 14px;
  border-top: 1px solid rgba(0,0,0,.08);
  font-size: 13px;
  vertical-align: middle;
}

.title {
  font-weight: 600;
}

.status {
  padding: 4px 9px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
}

.status.active {
  background: #EAF3DE;
  color: #27500A;
}

.status.en_attente {
  background: #FAEEDA;
  color: #8a520d;
}

.status.refusee,
.status.inactive {
  background: #FCEBEB;
  color: #791F1F;
}

.actions {
  display: flex;
  gap: 6px;
}

.btn {
  border: none;
  padding: 7px 10px;
  border-radius: 7px;
  cursor: pointer;
  font-size: 12px;
}

.btn-view {
  background: #E6F1FB;
  color: #185FA5;
}

.btn-approve {
  background: #EAF3DE;
  color: #27500A;
}

.btn-refuse {
  background: #FCEBEB;
  color: #791F1F;
}

.btn-delete {
  background: #eee;
  color: #444;
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
      <a href="admin_dashboard.php">Tableau de bord</a>
      <a href="admin_annonces.php" class="active">Annonces</a>
      <a href="admin_utilisateurs.php">Utilisateurs</a>
      <a href="admin_marques.php">Marques / Modèles</a>
      <a href="admin_equipements.php">Équipements</a>
      <a href="admin_signalements.php">Signalements</a>
      <a href="index.php">Retour au site</a>
    </nav>
  </aside>

  <main class="main">

    <div class="topbar">
      <h1>Gestion des annonces</h1>

      <div class="search-box">
        <input type="text" id="searchInput" placeholder="Rechercher une annonce...">
      </div>
    </div>

    <div class="card">
      <table class="table" id="annoncesTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Annonce</th>
            <th>Vendeur</th>
            <th>Prix</th>
            <th>Infos</th>
            <th>Localisation</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>

        <tbody>
        <?php if ($res && mysqli_num_rows($res) > 0): ?>
          <?php while ($a = mysqli_fetch_assoc($res)): ?>
            <?php
              $statut = strtolower($a['statutAnnonce']);
              $statutClass = str_replace(' ', '_', $statut);
            ?>
            <tr>
              <td><?= $a['idAnnonce'] ?></td>

              <td>
                <div class="title"><?= htmlspecialchars($a['titre']) ?></div>
                <small><?= htmlspecialchars($a['datePublication']) ?></small>
              </td>

              <td><?= htmlspecialchars($a['prenom'].' '.$a['nom']) ?></td>

              <td><?= number_format($a['prix'], 0, ',', ' ') ?> DA</td>

              <td>
                <?= htmlspecialchars($a['annee']) ?> ·
                <?= number_format($a['kilometrage'], 0, ',', ' ') ?> km ·
                <?= htmlspecialchars($a['carburant']) ?>
              </td>

              <td><?= htmlspecialchars($a['localisation']) ?></td>

              <td>
                <span class="status <?= $statutClass ?>">
                  <?= htmlspecialchars($a['statutAnnonce']) ?>
                </span>
              </td>

              <td>
                <div class="actions">
                  <button class="btn btn-view" onclick="location.href='fiche_annonce.php?id=<?= $a['idAnnonce'] ?>'">Voir</button>
                  <button class="btn btn-approve">Valider</button>
                  <button class="btn btn-refuse">Refuser</button>
                  <button class="btn btn-delete">Suppr.</button>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" style="text-align:center;padding:30px;color:#888;">
              Aucune annonce trouvée.
            </td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>
</div>

<script>
const searchInput = document.getElementById('searchInput');
const rows = document.querySelectorAll('#annoncesTable tbody tr');

searchInput.addEventListener('input', function () {
  const q = this.value.toLowerCase();

  rows.forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});
</script>

</body>
</html>
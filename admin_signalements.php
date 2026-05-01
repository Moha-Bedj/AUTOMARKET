<?php
session_start();
require_once 'connexion.php';

$sql = "
SELECT 
  s.idSignalement, 
  s.motif, 
  s.description, 
  s.dateSignalement, 
  'en_attente' AS statut,
  a.idAnnonce, 
  a.titre,
  u.idUtilisateur, 
  u.nom, 
  u.prenom, 
  u.email
FROM signalement s
LEFT JOIN Annonce a ON a.idAnnonce = s.idAnnonce
LEFT JOIN Utilisateur u ON u.idUtilisateur = s.idUtilisateur
ORDER BY s.dateSignalement DESC
";
$res = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Admin - Signalements</title>

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
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
.topbar h1{font-size:26px}
.search-box input{width:300px;height:40px;border:1px solid rgba(0,0,0,.2);border-radius:8px;padding:0 12px}

.card{background:white;border-radius:14px;border:1px solid rgba(0,0,0,.1);overflow:hidden;box-shadow:0 8px 22px rgba(0,0,0,.06)}
table{width:100%;border-collapse:collapse}
th{background:#f5f4f0;text-align:left;font-size:12px;color:#5f5e5a;padding:14px}
td{padding:14px;border-top:1px solid rgba(0,0,0,.08);font-size:13px;vertical-align:top}

.badge{padding:4px 9px;border-radius:20px;font-size:11px;font-weight:600}
.en_attente{background:#FAEEDA;color:#8a520d}
.traite{background:#EAF3DE;color:#27500A}
.rejete{background:#FCEBEB;color:#791F1F}

.actions{display:flex;gap:6px;flex-wrap:wrap}
.btn{border:none;padding:7px 10px;border-radius:7px;cursor:pointer;font-size:12px;text-decoration:none}
.btn-view{background:#E6F1FB;color:#185FA5}
.btn-ok{background:#EAF3DE;color:#27500A}
.btn-delete{background:#FCEBEB;color:#791F1F}
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
    <a href="admin_equipements.php">Équipements</a>
    <a href="admin_signalements.php" class="active">Signalements</a>
    <a href="index.php">Retour au site</a>
  </nav>
</aside>

<main class="main">

<div class="topbar">
  <h1>Gestion des signalements</h1>
  <div class="search-box">
    <input type="text" id="searchInput" placeholder="Rechercher un signalement...">
  </div>
</div>

<div class="card">
<table id="signalementsTable">
  <thead>
    <tr>
      <th>ID</th>
      <th>Annonce signalée</th>
      <th>Utilisateur</th>
      <th>Motif</th>
      <th>Description</th>
      <th>Date</th>
      <th>Statut</th>
      <th>Actions</th>
    </tr>
  </thead>

  <tbody>
  <?php if ($res && mysqli_num_rows($res) > 0): ?>
    <?php while($s = mysqli_fetch_assoc($res)): ?>
      <?php
        $statut = strtolower($s['statut'] ?? 'en_attente');
        $class = str_replace(' ', '_', $statut);
      ?>
      <tr>
        <td><?= htmlspecialchars($s['idSignalement']) ?></td>

        <td>
          <strong><?= htmlspecialchars($s['titre'] ?? 'Annonce supprimée') ?></strong><br>
          <small>ID annonce : <?= htmlspecialchars($s['idAnnonce'] ?? '-') ?></small>
        </td>

        <td>
          <?= htmlspecialchars(($s['prenom'] ?? '').' '.($s['nom'] ?? '')) ?><br>
          <small><?= htmlspecialchars($s['email'] ?? '-') ?></small>
        </td>

        <td><?= htmlspecialchars($s['motif'] ?? '-') ?></td>

        <td><?= htmlspecialchars($s['description'] ?? '-') ?></td>

        <td><?= htmlspecialchars($s['dateSignalement'] ?? '-') ?></td>

        <td>
          <span class="badge <?= $class ?>">
            <?= htmlspecialchars($s['statut'] ?? 'en_attente') ?>
          </span>
        </td>

        <td>
          <div class="actions">
            <?php if (!empty($s['idAnnonce'])): ?>
              <a class="btn btn-view" href="fiche_annonce.php?id=<?= $s['idAnnonce'] ?>">Voir annonce</a>
            <?php endif; ?>

            <a class="btn btn-ok" href="#">Traiter</a>
            <a class="btn btn-delete" href="#">Rejeter</a>
          </div>
        </td>
      </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr>
      <td colspan="8" style="text-align:center;color:#888;padding:30px">
        Aucun signalement trouvé.
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
const rows = document.querySelectorAll('#signalementsTable tbody tr');

searchInput.addEventListener('input', function () {
  const q = this.value.toLowerCase();
  rows.forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});
</script>

</body>
</html>
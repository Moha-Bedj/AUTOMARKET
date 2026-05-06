<?php
session_start();

require_once 'auth_admin.php';
require_once 'connexion.php';

/* =========================
   AFFICHER LES ERREURS PHP
   À enlever quand tout marche
========================= */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* =========================
   ACTIONS DES BOUTONS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idUtilisateur = $_POST['idUtilisateur'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!empty($idUtilisateur) && !empty($action)) {

        if ($action === 'bloquer') {

            $sqlUpdate = "UPDATE Utilisateur SET statut = 'bloque' WHERE idUtilisateur = ?";

        } elseif ($action === 'debloquer') {

            $sqlUpdate = "UPDATE Utilisateur SET statut = 'actif' WHERE idUtilisateur = ?";

        } elseif ($action === 'verifier') {

            $sqlUpdate = "UPDATE Utilisateur SET badgeVerifie = 1 WHERE idUtilisateur = ?";

        } elseif ($action === 'retirer_verification') {

            $sqlUpdate = "UPDATE Utilisateur SET badgeVerifie = 0 WHERE idUtilisateur = ?";

        } else {

            $sqlUpdate = "";
        }

        if ($sqlUpdate !== "") {

            $stmt = mysqli_prepare($conn, $sqlUpdate);

            if (!$stmt) {
                die("Erreur préparation SQL : " . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($stmt, "s", $idUtilisateur);

            if (!mysqli_stmt_execute($stmt)) {
                die("Erreur exécution SQL : " . mysqli_stmt_error($stmt));
            }

            mysqli_stmt_close($stmt);

            $_SESSION['message_admin'] = "Action effectuée avec succès.";
        }
    }

    header("Location: admin_utilisateurs.php");
    exit;
}

/* =========================
   RÉCUPÉRATION DES UTILISATEURS
========================= */
$sql = "
SELECT 
  u.idUtilisateur,
  u.nom,
  u.prenom,
  u.email,
  u.numTel,
  u.wilaya,
  u.statut,
  u.role,
  u.badgeVerifie,
  u.dateInscription,
  COUNT(a.idAnnonce) AS nbAnnonces
FROM Utilisateur u
LEFT JOIN Annonce a ON a.idVendeur = u.idUtilisateur
GROUP BY 
  u.idUtilisateur,
  u.nom,
  u.prenom,
  u.email,
  u.numTel,
  u.wilaya,
  u.statut,
  u.role,
  u.badgeVerifie,
  u.dateInscription
ORDER BY u.dateInscription DESC
";

$res = mysqli_query($conn, $sql);

if (!$res) {
    die("Erreur SELECT utilisateurs : " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Admin - Gestion utilisateurs</title>
<link rel="icon" href="images/logo.png">

<style>
*{box-sizing:border-box;margin:0;padding:0}

body{
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
  background:#f5f4f0;
  color:#1a1a18;
}

.admin-layout{
  display:flex;
  min-height:100vh;
}

.sidebar{
  width:250px;
  background:#0C447C;
  padding:24px 18px;
  color:white;
}

.logo{
  display:flex;
  justify-content:center;
  margin-bottom:30px;
}

.logo img{
  width:145px;
  height:auto;
  filter:brightness(0) invert(1);
}

.menu{
  display:flex;
  flex-direction:column;
  gap:8px;
}

.menu a{
  color:rgba(255,255,255,.75);
  text-decoration:none;
  padding:11px 12px;
  border-radius:8px;
  font-size:14px;
}

.menu a:hover,
.menu a.active{
  background:rgba(255,255,255,.15);
  color:white;
}

.main{
  flex:1;
  padding:28px;
}

.topbar{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:24px;
}

.topbar h1{
  font-size:26px;
}

.search-box input{
  width:280px;
  height:40px;
  border:1px solid rgba(0,0,0,.2);
  border-radius:8px;
  padding:0 12px;
  outline:none;
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
  vertical-align:middle;
}

.user-name{
  font-weight:600;
}

.badge{
  padding:4px 9px;
  border-radius:20px;
  font-size:11px;
  font-weight:600;
}

.actif{
  background:#EAF3DE;
  color:#27500A;
}

.bloque{
  background:#FCEBEB;
  color:#791F1F;
}

.verifie{
  background:#E6F1FB;
  color:#185FA5;
}

.nonverifie{
  background:#eee;
  color:#555;
}

.actions{
  display:flex;
  gap:6px;
  flex-wrap:wrap;
}

.btn{
  border:none;
  padding:7px 10px;
  border-radius:7px;
  cursor:pointer;
  font-size:12px;
}

.btn:hover{
  opacity:.85;
}

.btn-view{
  background:#E6F1FB;
  color:#185FA5;
}

.btn-block{
  background:#FCEBEB;
  color:#791F1F;
}

.btn-unblock{
  background:#EAF3DE;
  color:#27500A;
}

.btn-check{
  background:#FAEEDA;
  color:#8a520d;
}

.action-form{
  display:inline;
}

.alert{
  background:#EAF3DE;
  color:#27500A;
  padding:12px 16px;
  border-radius:10px;
  margin-bottom:18px;
  font-size:14px;
  border:1px solid rgba(39,80,10,.2);
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
      <a href="admin_annonces.php">Annonces</a>
      <a href="admin_utilisateurs.php" class="active">Utilisateurs</a>
      <a href="admin_marques.php">Marques / Modèles</a>
      <a href="admin_equipements.php">Équipements</a>
      <a href="admin_signalements.php">Signalements</a>
      <a href="index.php">Retour au site</a>
    </nav>
  </aside>

  <main class="main">

    <div class="topbar">
      <h1>Gestion des utilisateurs</h1>

      <div class="search-box">
        <input type="text" id="searchInput" placeholder="Rechercher un utilisateur...">
      </div>
    </div>

    <?php if (!empty($_SESSION['message_admin'])): ?>
      <div class="alert">
        <?= htmlspecialchars($_SESSION['message_admin']) ?>
      </div>
      <?php unset($_SESSION['message_admin']); ?>
    <?php endif; ?>

    <div class="card">
      <table class="table" id="usersTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Utilisateur</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Wilaya</th>
            <th>Rôle</th>
            <th>Statut</th>
            <th>Vérification</th>
            <th>Annonces</th>
            <th>Actions</th>
          </tr>
        </thead>

        <tbody>
        <?php if ($res && mysqli_num_rows($res) > 0): ?>

          <?php while ($u = mysqli_fetch_assoc($res)): ?>

            <?php
              $statut = strtolower($u['statut'] ?? 'actif');

              if ($statut === 'bloque') {
                  $statutClass = 'bloque';
              } else {
                  $statutClass = 'actif';
              }

              $badge = $u['badgeVerifie'] ?? 0;

              if ($badge == 1) {
                  $verifClass = 'verifie';
                  $verifText = 'Vérifié';
              } else {
                  $verifClass = 'nonverifie';
                  $verifText = 'Non vérifié';
              }
            ?>

            <tr>
              <td><?= htmlspecialchars($u['idUtilisateur']) ?></td>

              <td>
                <div class="user-name">
                  <?= htmlspecialchars(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? '')) ?>
                </div>
                <small>
                  Inscrit le <?= htmlspecialchars($u['dateInscription'] ?? '-') ?>
                </small>
              </td>

              <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>

              <td><?= htmlspecialchars($u['numTel'] ?? '-') ?></td>

              <td><?= htmlspecialchars($u['wilaya'] ?? '-') ?></td>

              <td><?= htmlspecialchars($u['role'] ?? '-') ?></td>

              <td>
                <span class="badge <?= $statutClass ?>">
                  <?= htmlspecialchars($u['statut'] ?? 'actif') ?>
                </span>
              </td>

              <td>
                <span class="badge <?= $verifClass ?>">
                  <?= $verifText ?>
                </span>
              </td>

              <td><?= intval($u['nbAnnonces']) ?></td>

              <td>
                <div class="actions">

                  <button 
                    type="button"
                    class="btn btn-view" 
                    onclick="location.href='admin_annonces_user.php?id=<?= urlencode($u['idUtilisateur']) ?>'">
                    Voir annonces
                  </button>

                  <?php if ($statut === 'bloque'): ?>

                    <form method="POST" class="action-form" onsubmit="return confirm('Voulez-vous débloquer cet utilisateur ?');">
                      <input type="hidden" name="idUtilisateur" value="<?= htmlspecialchars($u['idUtilisateur']) ?>">
                      <input type="hidden" name="action" value="debloquer">
                      <button type="submit" class="btn btn-unblock">Débloquer</button>
                    </form>

                  <?php else: ?>

                    <form method="POST" class="action-form" onsubmit="return confirm('Voulez-vous bloquer cet utilisateur ?');">
                      <input type="hidden" name="idUtilisateur" value="<?= htmlspecialchars($u['idUtilisateur']) ?>">
                      <input type="hidden" name="action" value="bloquer">
                      <button type="submit" class="btn btn-block">Bloquer</button>
                    </form>

                  <?php endif; ?>

                  <?php if ($badge == 1): ?>

                    <form method="POST" class="action-form" onsubmit="return confirm('Voulez-vous retirer la vérification de cet utilisateur ?');">
                      <input type="hidden" name="idUtilisateur" value="<?= htmlspecialchars($u['idUtilisateur']) ?>">
                      <input type="hidden" name="action" value="retirer_verification">
                      <button type="submit" class="btn btn-check">Retirer vérif.</button>
                    </form>

                  <?php else: ?>

                    <form method="POST" class="action-form" onsubmit="return confirm('Voulez-vous vérifier cet utilisateur ?');">
                      <input type="hidden" name="idUtilisateur" value="<?= htmlspecialchars($u['idUtilisateur']) ?>">
                      <input type="hidden" name="action" value="verifier">
                      <button type="submit" class="btn btn-check">Vérifier</button>
                    </form>

                  <?php endif; ?>

                </div>
              </td>
            </tr>

          <?php endwhile; ?>

        <?php else: ?>

          <tr>
            <td colspan="10" style="text-align:center;padding:30px;color:#888">
              Aucun utilisateur trouvé.
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
const rows = document.querySelectorAll('#usersTable tbody tr');

searchInput.addEventListener('input', function () {
  const q = this.value.toLowerCase();

  rows.forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});
</script>

</body>
</html>
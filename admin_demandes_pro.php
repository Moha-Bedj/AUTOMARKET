<?php
session_start();

require_once 'auth_admin.php';
require_once 'connexion.php';
require_once 'notification_helper.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* Fonction UUID si besoin */
function uuidAdminPro() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0,0xffff), mt_rand(0,0xffff),
        mt_rand(0,0xffff),
        mt_rand(0,0x0fff) | 0x4000,
        mt_rand(0,0x3fff) | 0x8000,
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)
    );
}

/* =========================
   ACTIONS ADMIN
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idUtilisateur = $_POST['idUtilisateur'] ?? '';
    $action = $_POST['action'] ?? '';
    $motifRefus = trim($_POST['motifRefus'] ?? '');

    if (!empty($idUtilisateur) && !empty($action)) {

        $idSql = mysqli_real_escape_string($conn, $idUtilisateur);
        $motifSql = mysqli_real_escape_string($conn, $motifRefus);

        mysqli_begin_transaction($conn);

        try {

            /* Récupérer l'utilisateur */
            $rUser = mysqli_query($conn, "
                SELECT idUtilisateur, email, nom, prenom
                FROM Utilisateur
                WHERE idUtilisateur = '$idSql'
                LIMIT 1
            ");

            if (!$rUser || mysqli_num_rows($rUser) === 0) {
                throw new Exception("Utilisateur introuvable.");
            }

            $user = mysqli_fetch_assoc($rUser);

            if ($action === 'valider') {

                /* 1. Valider la demande dans Concessionnaire */
                $ok1 = mysqli_query($conn, "
                    UPDATE Concessionnaire
                    SET 
                        statutPro = 'valide',
                        dateVerificationPro = NOW(),
                        motifRefus = NULL
                    WHERE idUtilisateur = '$idSql'
                ");

                if (!$ok1) {
                    throw new Exception("Erreur validation Concessionnaire : " . mysqli_error($conn));
                }

                /* 2. Transformer l'utilisateur en concessionnaire */
                $ok2 = mysqli_query($conn, "
                    UPDATE Utilisateur
                    SET 
                        role = 'concessionnaire',
                        badgeVerifie = 1
                    WHERE idUtilisateur = '$idSql'
                ");

                if (!$ok2) {
                    throw new Exception("Erreur update Utilisateur : " . mysqli_error($conn));
                }

                /* 3. Vérifier Acheteur, car Vendeur dépend souvent de Acheteur */
                $rAcheteur = mysqli_query($conn, "
                    SELECT idUtilisateur 
                    FROM Acheteur 
                    WHERE idUtilisateur = '$idSql'
                    LIMIT 1
                ");

                if (!$rAcheteur || mysqli_num_rows($rAcheteur) === 0) {
                    $okA = mysqli_query($conn, "
                        INSERT INTO Acheteur (idUtilisateur)
                        VALUES ('$idSql')
                    ");

                    if (!$okA) {
                        throw new Exception("Erreur création Acheteur : " . mysqli_error($conn));
                    }
                }

                /* 4. Créer ou mettre à jour Vendeur */
                $rVendeur = mysqli_query($conn, "
                    SELECT idUtilisateur 
                    FROM Vendeur 
                    WHERE idUtilisateur = '$idSql'
                    LIMIT 1
                ");

                if ($rVendeur && mysqli_num_rows($rVendeur) > 0) {
                    $okV = mysqli_query($conn, "
                        UPDATE Vendeur
                        SET typeVendeur = 'concessionnaire'
                        WHERE idUtilisateur = '$idSql'
                    ");
                } else {
                    $okV = mysqli_query($conn, "
                        INSERT INTO Vendeur (idUtilisateur, typeVendeur, nbrAnnonceAct)
                        VALUES ('$idSql', 'concessionnaire', 0)
                    ");
                }

                if (!$okV) {
                    throw new Exception("Erreur Vendeur : " . mysqli_error($conn));
                }

                /* 5. Notification utilisateur */
                creerNotification(
                    $conn,
                    $idUtilisateur,
                    "Votre compte professionnel a été validé. Vous pouvez maintenant publier vos annonces.",
                    "pro_valide",
                    "publier.php"
                );

                $_SESSION['message_admin'] = "Compte professionnel validé avec succès.";
            }

            elseif ($action === 'refuser') {

                if ($motifRefus === '') {
                    throw new Exception("Veuillez écrire un motif de refus.");
                }

                $ok1 = mysqli_query($conn, "
                    UPDATE Concessionnaire
                    SET 
                        statutPro = 'refuse',
                        dateVerificationPro = NOW(),
                        motifRefus = '$motifSql'
                    WHERE idUtilisateur = '$idSql'
                ");

                if (!$ok1) {
                    throw new Exception("Erreur refus Concessionnaire : " . mysqli_error($conn));
                }

                mysqli_query($conn, "
                    UPDATE Utilisateur
                    SET role = 'utilisateur', badgeVerifie = 0
                    WHERE idUtilisateur = '$idSql'
                ");

                mysqli_query($conn, "
                    UPDATE Vendeur
                    SET typeVendeur = 'professionnel_refuse'
                    WHERE idUtilisateur = '$idSql'
                ");

                creerNotification(
                    $conn,
                    $idUtilisateur,
                    "Votre demande de compte professionnel a été refusée. Veuillez consulter le motif et renvoyer un justificatif.",
                    "pro_rejete",
                    "verification_compte_pro.php"
                );

                $_SESSION['message_admin'] = "Demande professionnelle refusée.";
            }

            mysqli_commit($conn);

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['message_admin_error'] = $e->getMessage();
        }
    }

    header("Location: admin_demandes_pro.php");
    exit;
}

/* =========================
   RÉCUPÉRATION DES DEMANDES
========================= */
$sql = "
SELECT 
    c.idUtilisateur,
    c.nomEntreprise,
    c.adresseEntreprise,
    c.siteWeb,
    c.numRegistreCommerce,
    c.statutPro,
    c.justificatifRegistre,
    c.dateDemandePro,
    c.dateVerificationPro,
    c.motifRefus,
    u.nom,
    u.prenom,
    u.email,
    u.numTel,
    u.wilaya,
    u.role,
    u.badgeVerifie
FROM Concessionnaire c
JOIN Utilisateur u ON u.idUtilisateur = c.idUtilisateur
ORDER BY 
    CASE 
        WHEN c.statutPro = 'en_attente_admin' THEN 1
        WHEN c.statutPro = 'en_attente_verification' THEN 2
        WHEN c.statutPro = 'refuse' THEN 3
        WHEN c.statutPro = 'valide' THEN 4
        ELSE 5
    END,
    c.dateDemandePro DESC
";

$res = mysqli_query($conn, $sql);

if (!$res) {
    die("Erreur SQL : " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Admin - Demandes Pro</title>
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
  margin-bottom:22px;
}

.topbar h1{
  font-size:26px;
}

.topbar p{
  font-size:13px;
  color:#5f5e5a;
  margin-top:4px;
}

.search-box input{
  width:280px;
  height:40px;
  border:1px solid rgba(0,0,0,.2);
  border-radius:8px;
  padding:0 12px;
  outline:none;
}

.alert{
  padding:12px 16px;
  border-radius:10px;
  margin-bottom:18px;
  font-size:14px;
}

.alert.success{
  background:#EAF3DE;
  color:#27500A;
  border:1px solid rgba(39,80,10,.2);
}

.alert.error{
  background:#FCEBEB;
  color:#791F1F;
  border:1px solid rgba(121,31,31,.2);
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
  vertical-align:top;
}

.user-name{
  font-weight:600;
}

.small{
  font-size:12px;
  color:#777;
  margin-top:2px;
}

.badge{
  padding:5px 10px;
  border-radius:20px;
  font-size:11px;
  font-weight:600;
  display:inline-block;
  white-space:nowrap;
}

.wait-file{
  background:#FAEEDA;
  color:#854F0B;
}

.wait-admin{
  background:#E6F1FB;
  color:#0C447C;
}

.valid{
  background:#EAF3DE;
  color:#27500A;
}

.refused{
  background:#FCEBEB;
  color:#791F1F;
}

.actions{
  display:flex;
  flex-direction:column;
  gap:8px;
  min-width:170px;
}

.btn{
  border:none;
  padding:8px 11px;
  border-radius:7px;
  cursor:pointer;
  font-size:12px;
  text-align:center;
  text-decoration:none;
  font-family:inherit;
}

.btn:hover{
  opacity:.85;
}

.btn-file{
  background:#E6F1FB;
  color:#185FA5;
}

.btn-valid{
  background:#EAF3DE;
  color:#27500A;
}

.btn-refuse{
  background:#FCEBEB;
  color:#791F1F;
}

.refus-form{
  display:flex;
  flex-direction:column;
  gap:6px;
}

.refus-form textarea{
  width:100%;
  min-height:55px;
  border:1px solid rgba(0,0,0,.18);
  border-radius:8px;
  padding:8px;
  font-size:12px;
  resize:vertical;
  font-family:inherit;
}

.empty{
  text-align:center;
  padding:34px;
  color:#888;
}

@media(max-width:1000px){
  .admin-layout{
    flex-direction:column;
  }

  .sidebar{
    width:100%;
  }

  .table{
    min-width:1100px;
  }

  .card{
    overflow-x:auto;
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
      <a href="admin_dashboard.php">Tableau de bord</a>
      <a href="admin_annonces.php">Annonces</a>
      <a href="admin_utilisateurs.php">Utilisateurs</a>
      <a href="admin_demandes_pro.php" class="active">Demandes Pro</a>
      <a href="admin_marques.php">Marques / Modèles</a>
      <a href="admin_equipements.php">Équipements</a>
      <a href="admin_signalements.php">Signalements</a>
      <a href="index.php">Retour au site</a>
    </nav>
  </aside>

  <main class="main">

    <div class="topbar">
      <div>
        <h1>Demandes de comptes Pro</h1>
        <p>Validez ou refusez les demandes de concessionnaires.</p>
      </div>

      <div class="search-box">
        <input type="text" id="searchInput" placeholder="Rechercher une demande...">
      </div>
    </div>

    <?php if (!empty($_SESSION['message_admin'])): ?>
      <div class="alert success">
        <?= htmlspecialchars($_SESSION['message_admin']) ?>
      </div>
      <?php unset($_SESSION['message_admin']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['message_admin_error'])): ?>
      <div class="alert error">
        <?= htmlspecialchars($_SESSION['message_admin_error']) ?>
      </div>
      <?php unset($_SESSION['message_admin_error']); ?>
    <?php endif; ?>

    <div class="card">
      <table class="table" id="demandesTable">
        <thead>
          <tr>
            <th>Utilisateur</th>
            <th>Entreprise</th>
            <th>RC / NIF</th>
            <th>Contact</th>
            <th>Justificatif</th>
            <th>Statut</th>
            <th>Dates</th>
            <th>Actions</th>
          </tr>
        </thead>

        <tbody>
        <?php if ($res && mysqli_num_rows($res) > 0): ?>
          <?php while ($d = mysqli_fetch_assoc($res)): ?>
            <?php
              $statut = $d['statutPro'] ?? 'en_attente_verification';

              if ($statut === 'en_attente_admin') {
                  $badgeClass = 'wait-admin';
                  $badgeText = 'En attente admin';
              } elseif ($statut === 'en_attente_verification') {
                  $badgeClass = 'wait-file';
                  $badgeText = 'Justificatif demandé';
              } elseif ($statut === 'valide') {
                  $badgeClass = 'valid';
                  $badgeText = 'Validé';
              } elseif ($statut === 'refuse') {
                  $badgeClass = 'refused';
                  $badgeText = 'Refusé';
              } else {
                  $badgeClass = 'wait-file';
                  $badgeText = $statut;
              }
            ?>

            <tr>
              <td>
                <div class="user-name">
                  <?= htmlspecialchars(($d['prenom'] ?? '') . ' ' . ($d['nom'] ?? '')) ?>
                </div>
                <div class="small"><?= htmlspecialchars($d['email'] ?? '-') ?></div>
                <div class="small">Rôle : <?= htmlspecialchars($d['role'] ?? '-') ?></div>
              </td>

              <td>
                <strong><?= htmlspecialchars($d['nomEntreprise'] ?? '-') ?></strong>
                <div class="small">
                  Site :
                  <?php if (!empty($d['siteWeb'])): ?>
                    <a href="<?= htmlspecialchars($d['siteWeb']) ?>" target="_blank">
                      <?= htmlspecialchars($d['siteWeb']) ?>
                    </a>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </div>
                <div class="small">
                  Adresse : <?= htmlspecialchars($d['adresseEntreprise'] ?: '-') ?>
                </div>
              </td>

              <td>
                <?= htmlspecialchars($d['numRegistreCommerce'] ?? '-') ?>
              </td>

              <td>
                <div><?= htmlspecialchars($d['numTel'] ?: '-') ?></div>
                <div class="small">Wilaya : <?= htmlspecialchars($d['wilaya'] ?: '-') ?></div>
              </td>

              <td>
                <?php if (!empty($d['justificatifRegistre'])): ?>
                  <a 
                    href="<?= htmlspecialchars($d['justificatifRegistre']) ?>" 
                    target="_blank" 
                    class="btn btn-file">
                    Voir fichier
                  </a>
                <?php else: ?>
                  <span class="small">Aucun fichier</span>
                <?php endif; ?>
              </td>

              <td>
                <span class="badge <?= $badgeClass ?>">
                  <?= htmlspecialchars($badgeText) ?>
                </span>

                <?php if ($statut === 'refuse' && !empty($d['motifRefus'])): ?>
                  <div class="small" style="margin-top:6px">
                    Motif : <?= htmlspecialchars($d['motifRefus']) ?>
                  </div>
                <?php endif; ?>
              </td>

              <td>
                <div class="small">
                  Demande : <?= htmlspecialchars($d['dateDemandePro'] ?? '-') ?>
                </div>
                <div class="small">
                  Vérification : <?= htmlspecialchars($d['dateVerificationPro'] ?? '-') ?>
                </div>
              </td>

              <td>
                <div class="actions">

                  <?php if ($statut === 'en_attente_admin'): ?>

                    <form method="POST" onsubmit="return confirm('Valider ce compte professionnel ?');">
                      <input type="hidden" name="idUtilisateur" value="<?= htmlspecialchars($d['idUtilisateur']) ?>">
                      <input type="hidden" name="action" value="valider">
                      <button type="submit" class="btn btn-valid">
                        Valider
                      </button>
                    </form>

                    <form method="POST" class="refus-form" onsubmit="return confirm('Refuser cette demande ?');">
                      <input type="hidden" name="idUtilisateur" value="<?= htmlspecialchars($d['idUtilisateur']) ?>">
                      <input type="hidden" name="action" value="refuser">
                      <textarea name="motifRefus" placeholder="Motif du refus..." required></textarea>
                      <button type="submit" class="btn btn-refuse">
                        Refuser
                      </button>
                    </form>

                  <?php elseif ($statut === 'en_attente_verification'): ?>

                    <span class="small">
                      En attente du justificatif utilisateur.
                    </span>

                  <?php elseif ($statut === 'valide'): ?>

                    <span class="small">
                      Compte déjà validé.
                    </span>

                  <?php elseif ($statut === 'refuse'): ?>

                    <span class="small">
                      Demande refusée. L’utilisateur peut renvoyer un fichier.
                    </span>

                  <?php endif; ?>

                </div>
              </td>
            </tr>

          <?php endwhile; ?>

        <?php else: ?>

          <tr>
            <td colspan="8" class="empty">
              Aucune demande professionnelle trouvée.
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
const rows = document.querySelectorAll('#demandesTable tbody tr');

searchInput.addEventListener('input', function () {
  const q = this.value.toLowerCase();

  rows.forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});
</script>

</body>
</html>
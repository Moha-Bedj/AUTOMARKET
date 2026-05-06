<?php
session_start();

require_once 'connexion.php';
require_once 'notification_helper.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* Vérifier connexion */
if (!isset($_SESSION['idUtilisateur']) || empty($_SESSION['idUtilisateur'])) {
    header("Location: inscription.php");
    exit;
}

$idUser = $_SESSION['idUtilisateur'];
$idUserSql = mysqli_real_escape_string($conn, $idUser);

/* Récupérer la demande Pro */
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
    u.email
FROM Concessionnaire c
JOIN Utilisateur u ON u.idUtilisateur = c.idUtilisateur
WHERE c.idUtilisateur = '$idUserSql'
LIMIT 1
";

$res = mysqli_query($conn, $sql);

if (!$res) {
    die("Erreur SQL : " . mysqli_error($conn));
}

$demande = mysqli_fetch_assoc($res);

if (!$demande) {
    die("Vous n'avez pas de demande de compte professionnel.");
}

$message = "";
$typeMessage = "success";

/* Traitement upload */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['justificatif']) || $_FILES['justificatif']['error'] !== UPLOAD_ERR_OK) {
        $message = "Veuillez sélectionner un fichier valide.";
        $typeMessage = "error";
    } else {

        $file = $_FILES['justificatif'];

        $allowedExt = ['pdf', 'png', 'jpg', 'jpeg'];
        $allowedMime = [
            'application/pdf',
            'image/png',
            'image/jpeg'
        ];

        $originalName = $file['name'];
        $tmpName = $file['tmp_name'];
        $size = $file['size'];

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mime = mime_content_type($tmpName);

        if (!in_array($ext, $allowedExt)) {
            $message = "Format non autorisé. Utilisez PDF, PNG, JPG ou JPEG.";
            $typeMessage = "error";
        } elseif (!in_array($mime, $allowedMime)) {
            $message = "Type de fichier invalide.";
            $typeMessage = "error";
        } elseif ($size > 5 * 1024 * 1024) {
            $message = "Le fichier est trop volumineux. Maximum : 5 Mo.";
            $typeMessage = "error";
        } else {

            $uploadDir = __DIR__ . "/uploads/registre_commerce/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newName = "rc_" . $idUser . "_" . time() . "." . $ext;
            $destination = $uploadDir . $newName;

            $dbPath = "uploads/registre_commerce/" . $newName;

            if (move_uploaded_file($tmpName, $destination)) {

                $dbPathSql = mysqli_real_escape_string($conn, $dbPath);

                $update = "
                UPDATE Concessionnaire
                SET 
                    justificatifRegistre = '$dbPathSql',
                    statutPro = 'en_attente_admin',
                    dateDemandePro = NOW(),
                    motifRefus = NULL
                WHERE idUtilisateur = '$idUserSql'
                ";

                if (mysqli_query($conn, $update)) {

                    notifierAdmins(
                        $conn,
                        "Nouvelle demande de vérification compte Pro à traiter.",
                        "pro_attente",
                        "admin_demandes_pro.php"
                    );

                    creerNotification(
                        $conn,
                        $idUser,
                        "Votre justificatif a été envoyé. Votre compte Pro est maintenant en attente de validation admin.",
                        "pro_attente",
                        "verification_compte_pro.php"
                    );

                    header("Location: verification_compte_pro.php?success=1");
                    exit;

                } else {
                    $message = "Erreur SQL : " . mysqli_error($conn);
                    $typeMessage = "error";
                }

            } else {
                $message = "Erreur lors de l'envoi du fichier.";
                $typeMessage = "error";
            }
        }
    }
}

/* Recharger après modification */
$res = mysqli_query($conn, $sql);
$demande = mysqli_fetch_assoc($res);

if (isset($_GET['success'])) {
    $message = "Justificatif envoyé avec succès. Votre demande est en attente de validation.";
    $typeMessage = "success";
}

$statutPro = $demande['statutPro'] ?? 'en_attente_verification';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Vérification compte Pro — AUTOMARKET</title>
<link rel="icon" href="images/logo.png">

<style>
*{
  box-sizing:border-box;
  margin:0;
  padding:0;
}

:root {
  --blue:#185FA5;
  --blue-dk:#0C447C;
  --blue-bg:#E6F1FB;
  --blue-bd:#B5D4F4;
  --bg0:#ffffff;
  --bg1:#f5f4f0;
  --bg2:#eceae4;
  --t1:#1a1a18;
  --t2:#5f5e5a;
  --t3:#888780;
  --bd:rgba(0,0,0,0.11);
  --bd2:rgba(0,0,0,0.22);
  --green:#16a34a;
  --green-bg:#EAF3DE;
  --green-dk:#27500A;
  --green-bd:#C0DD97;
  --red:#E24B4A;
  --red-bg:#FCEBEB;
  --red-dk:#791F1F;
  --amber-bg:#FAEEDA;
  --amber-dk:#854F0B;
  --r6:6px;
  --r8:8px;
  --r10:10px;
  --r14:14px;
}

body{
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
  background:var(--bg1);
  color:var(--t1);
  min-height:100vh;
  font-size:14px;
}

.nav{
  background:var(--bg0);
  border-bottom:0.5px solid var(--bd);
  height:56px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:0 28px;
  position:sticky;
  top:0;
  z-index:100;
}

.logo{
  display:flex;
  align-items:center;
  text-decoration:none;
}

.logo img{
  height:34px;
}

.nav-back{
  font-size:13px;
  color:var(--t2);
  display:flex;
  align-items:center;
  gap:6px;
  text-decoration:none;
  padding:7px 12px;
  border-radius:var(--r6);
}

.nav-back:hover{
  background:var(--bg1);
  color:var(--t1);
}

.container{
  max-width:780px;
  margin:34px auto;
  padding:0 18px;
}

.page-title{
  font-size:24px;
  font-weight:600;
  margin-bottom:5px;
}

.page-sub{
  color:var(--t2);
  font-size:13px;
  margin-bottom:20px;
  line-height:1.5;
}

.card{
  background:var(--bg0);
  border:0.5px solid var(--bd);
  border-radius:var(--r14);
  box-shadow:0 8px 22px rgba(0,0,0,.06);
  overflow:hidden;
}

.card-head{
  padding:24px 28px;
  border-bottom:0.5px solid var(--bd);
  display:flex;
  align-items:flex-start;
  gap:14px;
}

.card-head-icon{
  width:42px;
  height:42px;
  border-radius:10px;
  background:var(--blue-bg);
  color:var(--blue);
  display:flex;
  align-items:center;
  justify-content:center;
  flex-shrink:0;
}

.card-head h1{
  font-size:22px;
  margin-bottom:6px;
}

.card-head p{
  color:var(--t2);
  font-size:13px;
  line-height:1.5;
}

.card-body{
  padding:26px 28px 30px;
}

.info-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:14px;
  margin-bottom:22px;
}

.info{
  background:var(--bg1);
  padding:13px;
  border-radius:10px;
  border:0.5px solid rgba(0,0,0,.04);
}

.info strong{
  display:block;
  font-size:12px;
  color:var(--t3);
  margin-bottom:5px;
}

.info span{
  font-size:14px;
  color:var(--t1);
}

.badge{
  display:inline-block;
  padding:6px 12px;
  border-radius:20px;
  font-size:12px;
  font-weight:600;
  margin-top:10px;
}

.badge.wait-file{
  background:var(--amber-bg);
  color:var(--amber-dk);
}

.badge.wait-admin{
  background:var(--blue-bg);
  color:var(--blue-dk);
}

.badge.valid{
  background:var(--green-bg);
  color:var(--green-dk);
}

.badge.refused{
  background:var(--red-bg);
  color:var(--red-dk);
}

.alert{
  padding:12px 14px;
  border-radius:var(--r8);
  margin-bottom:18px;
  font-size:14px;
  line-height:1.5;
}

.alert.success{
  background:var(--green-bg);
  color:var(--green-dk);
  border:0.5px solid var(--green-bd);
}

.alert.error{
  background:var(--red-bg);
  color:var(--red-dk);
  border:0.5px solid rgba(226,75,74,.3);
}

/* ESPACE UPLOAD STYLE PUBLIER.PHP */
.rc-upload-wrap {
  margin-top:22px;
}

.rc-file-input {
  display:none;
}

.rc-upload-zone {
  border:1.5px dashed var(--bd2);
  border-radius:var(--r14);
  background:var(--bg1);
  min-height:190px;
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  transition:all .15s;
  text-align:center;
  padding:24px;
  position:relative;
}

.rc-upload-zone:hover {
  border-color:var(--blue);
  background:var(--blue-bg);
  color:var(--blue);
}

.rc-upload-icon {
  width:54px;
  height:54px;
  border-radius:14px;
  background:var(--bg0);
  border:0.5px solid var(--bd);
  display:flex;
  align-items:center;
  justify-content:center;
  margin:0 auto 12px;
  color:var(--blue);
}

.rc-upload-title {
  font-size:15px;
  font-weight:600;
  color:var(--t1);
  margin-bottom:5px;
}

.rc-upload-sub {
  font-size:12px;
  color:var(--t2);
  line-height:1.5;
}

.rc-file-name {
  margin-top:12px;
  background:var(--green-bg);
  color:var(--green-dk);
  border:0.5px solid var(--green-bd);
  padding:10px 12px;
  border-radius:var(--r8);
  font-size:13px;
  display:none;
  align-items:center;
  gap:8px;
}

.rc-file-name.show {
  display:flex;
}

.rc-info {
  background:var(--bg1);
  border-radius:var(--r8);
  padding:12px 14px;
  margin-top:14px;
  display:flex;
  gap:10px;
  align-items:flex-start;
  font-size:12px;
  color:var(--t2);
}

.rc-info svg {
  flex-shrink:0;
  margin-top:1px;
}

.rc-info-title {
  color:var(--t1);
  font-weight:500;
  margin-bottom:3px;
}

.file-link{
  display:inline-block;
  margin-top:6px;
  margin-bottom:16px;
  color:var(--blue);
  font-size:14px;
  text-decoration:none;
}

.file-link:hover{
  text-decoration:underline;
}

.refus-box{
  background:var(--red-bg);
  color:var(--red-dk);
  padding:14px;
  border-radius:10px;
  margin-top:14px;
  font-size:14px;
  line-height:1.5;
}

.actions{
  display:flex;
  align-items:center;
  gap:10px;
  margin-top:20px;
  flex-wrap:wrap;
}

.btn{
  background:var(--blue);
  color:#fff;
  border:none;
  padding:12px 18px;
  border-radius:var(--r8);
  font-size:14px;
  font-weight:600;
  cursor:pointer;
  font-family:inherit;
  text-decoration:none;
  display:inline-flex;
  align-items:center;
  gap:7px;
}

.btn:hover{
  background:var(--blue-dk);
}

.btn-secondary{
  color:var(--blue);
  text-decoration:none;
  font-size:14px;
  padding:10px 0;
}

.btn-secondary:hover{
  text-decoration:underline;
}

@media(max-width:650px){
  .nav{
    padding:0 16px;
  }

  .container{
    margin:22px auto;
    padding:0 12px;
  }

  .card-head{
    padding:20px;
  }

  .card-body{
    padding:20px;
  }

  .info-grid{
    grid-template-columns:1fr;
  }

  .rc-upload-zone{
    min-height:160px;
  }
}
</style>
</head>

<body>

<nav class="nav">
  <a href="index.php" class="logo">
    <img src="images/logo.png" alt="AUTOMARKET">
  </a>

  <a href="index.php" class="nav-back">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M19 12H5M12 19l-7-7 7-7"/>
    </svg>
    Retour
  </a>
</nav>

<?php include 'notif_banner.php'; ?>

<div class="container">

  <h1 class="page-title">Vérification compte Pro</h1>
  <p class="page-sub">
    Envoyez votre justificatif du registre de commerce pour activer votre compte concessionnaire.
  </p>

  <div class="card">

    <div class="card-head">
      <div class="card-head-icon">
        <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M9 12l2 2 4-4"/>
          <path d="M21 12c.552 0 1.005-.449.95-.998a10 10 0 1 0-8.953 10.947c.55.055.998-.398.998-.95"/>
          <path d="M16 19l2 2 4-4"/>
        </svg>
      </div>

      <div>
        <h1>Validation professionnelle</h1>
        <p>
          Votre compte est créé comme utilisateur normal. Après validation par un administrateur,
          votre compte deviendra concessionnaire.
        </p>

        <?php if ($statutPro === 'en_attente_verification'): ?>
          <span class="badge wait-file">Justificatif demandé</span>
        <?php elseif ($statutPro === 'en_attente_admin'): ?>
          <span class="badge wait-admin">En attente de validation admin</span>
        <?php elseif ($statutPro === 'valide'): ?>
          <span class="badge valid">Compte Pro validé</span>
        <?php elseif ($statutPro === 'refuse'): ?>
          <span class="badge refused">Demande refusée</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="card-body">

      <?php if (!empty($message)): ?>
        <div class="alert <?= $typeMessage === 'error' ? 'error' : 'success' ?>">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <div class="info-grid">
        <div class="info">
          <strong>Nom</strong>
          <span><?= htmlspecialchars(($demande['prenom'] ?? '') . ' ' . ($demande['nom'] ?? '')) ?></span>
        </div>

        <div class="info">
          <strong>Email</strong>
          <span><?= htmlspecialchars($demande['email'] ?? '-') ?></span>
        </div>

        <div class="info">
          <strong>Entreprise</strong>
          <span><?= htmlspecialchars($demande['nomEntreprise'] ?? '-') ?></span>
        </div>

        <div class="info">
          <strong>Numéro RC / NIF</strong>
          <span><?= htmlspecialchars($demande['numRegistreCommerce'] ?? '-') ?></span>
        </div>

        <div class="info">
          <strong>Site web</strong>
          <span><?= htmlspecialchars($demande['siteWeb'] ?: '-') ?></span>
        </div>

        <div class="info">
          <strong>Statut</strong>
          <span><?= htmlspecialchars($statutPro) ?></span>
        </div>
      </div>

      <?php if (!empty($demande['justificatifRegistre'])): ?>
        <p>
          Justificatif déjà envoyé :
          <a class="file-link" href="<?= htmlspecialchars($demande['justificatifRegistre']) ?>" target="_blank">
            Voir le fichier
          </a>
        </p>
      <?php endif; ?>

      <?php if ($statutPro === 'refuse' && !empty($demande['motifRefus'])): ?>
        <div class="refus-box">
          <strong>Motif du refus :</strong><br>
          <?= nl2br(htmlspecialchars($demande['motifRefus'])) ?>
        </div>
      <?php endif; ?>

      <?php if ($statutPro === 'en_attente_verification' || $statutPro === 'refuse'): ?>

        <form method="POST" enctype="multipart/form-data">

          <div class="rc-upload-wrap">

            <input 
              type="file" 
              name="justificatif" 
              id="rc-file-input" 
              class="rc-file-input"
              accept=".pdf,.png,.jpg,.jpeg" 
              required
              onchange="showRcFileName(this)"
            >

            <div class="rc-upload-zone" onclick="document.getElementById('rc-file-input').click()">
              <div>
                <div class="rc-upload-icon">
                  <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="12" y1="12" x2="12" y2="18"/>
                    <line x1="9" y1="15" x2="12" y2="12"/>
                    <line x1="15" y1="15" x2="12" y2="12"/>
                  </svg>
                </div>

                <div class="rc-upload-title">Ajouter le justificatif RC</div>
                <div class="rc-upload-sub">
                  Cliquez ici pour importer votre fichier<br>
                  PDF, PNG, JPG ou JPEG · Maximum 5 Mo
                </div>
              </div>
            </div>

            <div id="rc-file-name" class="rc-file-name">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span id="rc-file-name-text"></span>
            </div>

            <div class="rc-info">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#185FA5" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="16" x2="12" y2="12"/>
                <line x1="12" y1="8" x2="12.01" y2="8"/>
              </svg>

              <div>
                <div class="rc-info-title">Conseil</div>
                <div>
                  Le fichier doit montrer clairement le numéro du registre de commerce et le nom de l’entreprise.
                </div>
              </div>
            </div>

          </div>

          <div class="actions">
            <button type="submit" class="btn">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M20 6L9 17l-5-5"/>
              </svg>
              Envoyer ma vérification
            </button>

            <a href="index.php" class="btn-secondary">Retour à l'accueil</a>
          </div>

        </form>

      <?php elseif ($statutPro === 'en_attente_admin'): ?>

        <div class="alert success">
          Votre justificatif a été envoyé. Un administrateur va vérifier votre demande.
        </div>

        <div class="actions">
          <a href="index.php" class="btn-secondary">Retour à l'accueil</a>
        </div>

      <?php elseif ($statutPro === 'valide'): ?>

        <div class="alert success">
          Votre compte professionnel est validé. Vous pouvez maintenant publier des annonces.
        </div>

        <div class="actions">
          <a href="publier.php" class="btn">
            Publier une annonce
          </a>
          <a href="index.php" class="btn-secondary">Retour à l'accueil</a>
        </div>

      <?php endif; ?>

    </div>

  </div>

</div>

<script>
function showRcFileName(input) {
  const box = document.getElementById('rc-file-name');
  const text = document.getElementById('rc-file-name-text');

  if (input.files && input.files.length > 0) {
    text.textContent = input.files[0].name;
    box.classList.add('show');
  } else {
    text.textContent = '';
    box.classList.remove('show');
  }
}
</script>

</body>
</html>
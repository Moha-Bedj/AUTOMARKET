<?php
/**
 * ════════════════════════════════════════════════════════════════════
 * AUTOMARKET — Bandeau de notifications importantes
 * ════════════════════════════════════════════════════════════════════
 * Composant à inclure JUSTE APRÈS la <nav> dans toutes les pages où 
 * l'utilisateur est connecté.
 * 
 * Affiche en bandeau permanent les notifications importantes :
 * - Pro en attente (orange)
 * - Pro validé (vert, peut être fermé)
 * - Pro rejeté (rouge, lien pour refaire)
 * - Autres notifs marquées "important"
 * 
 * USAGE :
 *   <?php include 'notif_banner.php'; ?>
 * 
 * PRÉREQUIS :
 *   - $conn (mysqli) doit exister
 *   - session_start() doit être appelé
 *   - L'utilisateur doit être connecté ($_SESSION['idUtilisateur'])
 * ════════════════════════════════════════════════════════════════════
 */

if (!isset($_SESSION['idUtilisateur']) || empty($_SESSION['idUtilisateur'])) {
    return; /* Pas de bandeau si pas connecté */
}

if (!isset($conn) || !$conn) {
    return; /* Connexion BDD requise */
}

/* Inclure le helper si pas déjà fait */
if (!function_exists('getNotifsImportantes')) {
    require_once __DIR__ . '/notification_helper.php';
}

$_banner_idUser = $_SESSION['idUtilisateur'];
$_banner_notifs = getNotifsImportantes($conn, $_banner_idUser);

if (count($_banner_notifs) === 0) {
    return; /* Pas de notifs importantes → rien à afficher */
}
?>

<style>
  /* ════════════════════════════════════════════════════════ */
  /* ═══   STYLES BANDEAU NOTIFICATIONS                    ═══ */
  /* ════════════════════════════════════════════════════════ */
  .nbanner-wrap {
    width: 100%;
    background: transparent;
    z-index: 90;
  }
  .nbanner {
    padding: 12px 28px;
    display: flex;
    align-items: center;
    gap: 14px;
    border-bottom: 0.5px solid;
    font-size: 13px;
    line-height: 1.5;
  }
  .nbanner-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: rgba(255,255,255,0.5);
  }
  .nbanner-content {
    flex: 1;
    min-width: 0;
  }
  .nbanner-title {
    font-weight: 600;
    margin-bottom: 1px;
  }
  .nbanner-text {
    font-size: 12px;
    opacity: 0.85;
  }
  .nbanner-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-shrink: 0;
  }
  .nbanner-btn {
    font-size: 12px;
    padding: 7px 14px;
    background: currentColor;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    font-weight: 500;
    transition: opacity .15s;
    white-space: nowrap;
    border: none;
    font-family: inherit;
    color: inherit;
  }
  .nbanner-btn span { color: #fff; }
  .nbanner-btn:hover { opacity: 0.85; }
  .nbanner-close {
    background: transparent;
    border: none;
    cursor: pointer;
    color: inherit;
    opacity: 0.6;
    padding: 4px 6px;
    display: flex;
    align-items: center;
    border-radius: 4px;
    font-family: inherit;
  }
  .nbanner-close:hover { opacity: 1; background: rgba(0,0,0,0.05); }

  /* Variantes couleurs */
  .nbanner.nbanner-warning {
    background: #FAEEDA;
    color: #854F0B;
    border-bottom-color: #FAC775;
  }
  .nbanner.nbanner-success {
    background: #EAF3DE;
    color: #27500A;
    border-bottom-color: #C0DD97;
  }
  .nbanner.nbanner-error {
    background: #FCEBEB;
    color: #791F1F;
    border-bottom-color: #F09595;
  }
  .nbanner.nbanner-info {
    background: #E6F1FB;
    color: #0C447C;
    border-bottom-color: #B5D4F4;
  }

  @media (max-width: 700px) {
    .nbanner {
      padding: 10px 14px;
      flex-wrap: wrap;
      gap: 10px;
    }
    .nbanner-content { flex-basis: calc(100% - 50px); }
    .nbanner-actions { width: 100%; justify-content: flex-end; }
  }
</style>

<div class="nbanner-wrap">
<?php foreach ($_banner_notifs as $_notif):
    $_type = $_notif['typeNoti'];
    $_texte = $_notif['texte'];
    $_lien = $_notif['lien'];
    $_idNotif = $_notif['idNotification'];
    
    /* Détermine la classe CSS */
    $_classCss = 'nbanner-info';
    $_titre = 'Notification';
    $_btnLabel = 'Voir détails';
    
    if ($_type === 'pro_attente') {
        $_classCss = 'nbanner-warning';
        $_titre = '⏳ Demande de compte Pro en cours';
        $_btnLabel = 'Voir ma demande';
    }
    elseif ($_type === 'pro_valide' || $_type === 'pro_active') {
        $_classCss = 'nbanner-success';
        $_titre = '✓ Compte Pro activé !';
        $_btnLabel = 'Aller au dashboard';
    }
    elseif ($_type === 'pro_rejete') {
        $_classCss = 'nbanner-error';
        $_titre = '✗ Demande Pro rejetée';
        $_btnLabel = 'Refaire ma demande';
    }
    elseif ($_type === 'warning' || strpos($_type, 'warning_important') !== false) {
        $_classCss = 'nbanner-warning';
        $_titre = 'Attention';
    }
    elseif ($_type === 'success' || strpos($_type, 'success_important') !== false) {
        $_classCss = 'nbanner-success';
        $_titre = 'Succès';
    }
    elseif ($_type === 'error' || strpos($_type, 'error_important') !== false) {
        $_classCss = 'nbanner-error';
        $_titre = 'Erreur';
    }
    
    /* Choisir l'icône */
    $_icone = '';
    if ($_classCss === 'nbanner-warning') {
        $_icone = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
    } elseif ($_classCss === 'nbanner-success') {
        $_icone = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>';
    } elseif ($_classCss === 'nbanner-error') {
        $_icone = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
    } else {
        $_icone = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>';
    }
?>
  <div class="nbanner <?= $_classCss ?>" data-notif-id="<?= htmlspecialchars($_idNotif) ?>">
    <div class="nbanner-icon"><?= $_icone ?></div>
    <div class="nbanner-content">
      <div class="nbanner-title"><?= htmlspecialchars($_titre) ?></div>
      <div class="nbanner-text"><?= htmlspecialchars($_texte) ?></div>
    </div>
    <div class="nbanner-actions">
      <?php if ($_lien): ?>
        <a href="<?= htmlspecialchars($_lien) ?>" class="nbanner-btn" 
           onclick="nbannerMarkRead('<?= htmlspecialchars($_idNotif) ?>')">
          <span><?= $_btnLabel ?></span>
        </a>
      <?php endif; ?>
      <?php if ($_type === 'pro_valide' || $_type === 'success' || $_type === 'pro_active'): ?>
        <button type="button" class="nbanner-close" 
                onclick="nbannerClose(this, '<?= htmlspecialchars($_idNotif) ?>')" 
                title="Fermer">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>

<script>
  /* ════════════════════════════════════════════════════════ */
  /* ═══   GESTION CÔTÉ CLIENT                             ═══ */
  /* ════════════════════════════════════════════════════════ */
  function nbannerMarkRead(idNotif) {
    /* Marque comme lue côté serveur (silencieux) */
    fetch('mark_notification_read.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'idNotification=' + encodeURIComponent(idNotif)
    }).catch(() => {});
  }
  
  function nbannerClose(btn, idNotif) {
    const banner = btn.closest('.nbanner');
    if (!banner) return;
    banner.style.transition = 'opacity .2s, max-height .3s, padding .3s';
    banner.style.opacity = '0';
    setTimeout(() => {
      banner.style.maxHeight = '0';
      banner.style.padding = '0 28px';
      banner.style.overflow = 'hidden';
    }, 200);
    setTimeout(() => banner.remove(), 500);
    nbannerMarkRead(idNotif);
  }
</script>
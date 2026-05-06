<?php
/**
 * ════════════════════════════════════════════════════════════════════
 * AUTOMARKET — Cloche de notifications (dropdown navbar)
 * ════════════════════════════════════════════════════════════════════
 * Composant à inclure DANS la navbar de chaque page connectée.
 * 
 * Affiche une cloche 🔔 avec badge rouge du nombre de non lues,
 * et un dropdown au clic avec les 10 dernières notifications.
 * 
 * USAGE :
 *   <nav class="nav">
 *     ...
 *     <?php include 'notif_dropdown.php'; ?>
 *     <a href="monprofil.php">Avatar</a>
 *   </nav>
 * 
 * PRÉREQUIS :
 *   - $conn (mysqli)
 *   - session_start()
 *   - $_SESSION['idUtilisateur']
 * ════════════════════════════════════════════════════════════════════
 */

if (!isset($_SESSION['idUtilisateur']) || empty($_SESSION['idUtilisateur'])) return;
if (!isset($conn) || !$conn) return;

if (!function_exists('getNotifs')) {
    require_once __DIR__ . '/notification_helper.php';
}

$_drop_idUser = $_SESSION['idUtilisateur'];
$_drop_notifs = getNotifs($conn, $_drop_idUser, 10);
$_drop_unread = compterNotifsNonLues($conn, $_drop_idUser);
?>

<style>
  /* ════════════════════════════════════════════════════════ */
  /* ═══   STYLES CLOCHE DROPDOWN                          ═══ */
  /* ════════════════════════════════════════════════════════ */
  .ndrop-wrap {
    position: relative;
    display: inline-block;
  }
  .ndrop-bell {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: transparent;
    border: 0.5px solid transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #5f5e5a;
    position: relative;
    transition: all .15s;
  }
  .ndrop-bell:hover {
    background: #f5f4f0;
    color: #1a1a18;
  }
  .ndrop-bell.active {
    background: #E6F1FB;
    color: #185FA5;
  }
  .ndrop-badge {
    position: absolute;
    top: 4px;
    right: 4px;
    min-width: 16px;
    height: 16px;
    background: #E24B4A;
    color: #fff;
    font-size: 10px;
    font-weight: 600;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    border: 1.5px solid #fff;
    line-height: 1;
  }
  .ndrop-badge.hidden { display: none; }

  /* DROPDOWN */
  .ndrop-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 380px;
    max-width: calc(100vw - 24px);
    background: #ffffff;
    border: 0.5px solid rgba(0,0,0,0.12);
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    overflow: hidden;
    display: none;
    z-index: 1000;
    animation: ndropOpen .15s ease-out;
  }
  @keyframes ndropOpen {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .ndrop-menu.show { display: block; }

  .ndrop-head {
    padding: 14px 16px;
    border-bottom: 0.5px solid rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fafaf8;
  }
  .ndrop-head-title {
    font-size: 14px;
    font-weight: 600;
    color: #1a1a18;
  }
  .ndrop-mark-all {
    font-size: 12px;
    color: #185FA5;
    cursor: pointer;
    text-decoration: none;
    background: none;
    border: none;
    font-family: inherit;
    padding: 0;
  }
  .ndrop-mark-all:hover { text-decoration: underline; }

  .ndrop-body {
    max-height: 420px;
    overflow-y: auto;
    background: #fff;
  }

  .ndrop-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 16px;
    border-bottom: 0.5px solid rgba(0,0,0,0.06);
    cursor: pointer;
    transition: background .15s;
    text-decoration: none;
    color: inherit;
    position: relative;
  }
  .ndrop-item:last-child { border-bottom: none; }
  .ndrop-item:hover { background: #fafaf8; }
  .ndrop-item.unread::before {
    content: '';
    position: absolute;
    left: 5px;
    top: 50%;
    transform: translateY(-50%);
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #185FA5;
  }

  .ndrop-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 1px;
  }
  .ndrop-icon.message { background: #EAF3DE; color: #639922; }
  .ndrop-icon.favori { background: #FCEBEB; color: #E24B4A; }
  .ndrop-icon.vente { background: #E6F1FB; color: #185FA5; }
  .ndrop-icon.alerte { background: #E6F1FB; color: #185FA5; }
  .ndrop-icon.pro_valide,
  .ndrop-icon.pro_active { background: #EAF3DE; color: #639922; }
  .ndrop-icon.pro_attente { background: #FAEEDA; color: #BA7517; }
  .ndrop-icon.pro_rejete { background: #FCEBEB; color: #E24B4A; }
  .ndrop-icon.success { background: #EAF3DE; color: #639922; }
  .ndrop-icon.warning { background: #FAEEDA; color: #BA7517; }
  .ndrop-icon.error { background: #FCEBEB; color: #E24B4A; }
  .ndrop-icon.info { background: #E6F1FB; color: #185FA5; }

  .ndrop-content { flex: 1; min-width: 0; }
  .ndrop-text {
    font-size: 13px;
    color: #1a1a18;
    line-height: 1.4;
    margin-bottom: 3px;
    word-wrap: break-word;
  }
  .ndrop-item.unread .ndrop-text { font-weight: 500; }
  .ndrop-time {
    font-size: 11px;
    color: #888780;
  }

  .ndrop-empty {
    padding: 40px 20px;
    text-align: center;
    color: #888780;
    font-size: 13px;
  }
  .ndrop-empty-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 12px;
    border-radius: 50%;
    background: #f5f4f0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #888780;
  }

  .ndrop-foot {
    padding: 12px 16px;
    border-top: 0.5px solid rgba(0,0,0,0.08);
    text-align: center;
    background: #fafaf8;
  }
  .ndrop-foot a {
    font-size: 13px;
    color: #185FA5;
    text-decoration: none;
    font-weight: 500;
  }
  .ndrop-foot a:hover { text-decoration: underline; }

  /* Scrollbar */
  .ndrop-body::-webkit-scrollbar { width: 6px; }
  .ndrop-body::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.15);
    border-radius: 3px;
  }

  @media (max-width: 480px) {
    .ndrop-menu {
      width: calc(100vw - 24px);
      right: -4px;
    }
  }
</style>

<div class="ndrop-wrap" id="ndrop-wrap">
  <button type="button" class="ndrop-bell" id="ndrop-bell" onclick="ndropToggle(event)" title="Notifications">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
      <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
    </svg>
    <span class="ndrop-badge <?= $_drop_unread === 0 ? 'hidden' : '' ?>" id="ndrop-badge">
      <?= $_drop_unread > 99 ? '99+' : $_drop_unread ?>
    </span>
  </button>

  <div class="ndrop-menu" id="ndrop-menu">
    <div class="ndrop-head">
      <div class="ndrop-head-title">Notifications</div>
      <?php if ($_drop_unread > 0): ?>
        <button type="button" class="ndrop-mark-all" onclick="ndropMarkAll(event)">
          Tout marquer comme lu
        </button>
      <?php endif; ?>
    </div>

    <div class="ndrop-body">
      <?php if (count($_drop_notifs) === 0): ?>
        <div class="ndrop-empty">
          <div class="ndrop-empty-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
          </div>
          Aucune notification
        </div>
      <?php else: ?>
        <?php foreach ($_drop_notifs as $_n):
          $_t = strtolower($_n['typeNoti'] ?? 'info');
          $_unread = (int)$_n['statutLecture'] === 0;
          $_href = $_n['lien'] ?: '#';
          $_text = $_n['texte'];
          $_time = function_exists('notifTimeAgo') ? notifTimeAgo($_n['dateNoti']) : $_n['dateNoti'];
        ?>
          <a href="<?= htmlspecialchars($_href) ?>"
             class="ndrop-item <?= $_unread ? 'unread' : '' ?>"
             data-notif-id="<?= htmlspecialchars($_n['idNotification']) ?>"
             onclick="ndropItemClick(event, '<?= htmlspecialchars($_n['idNotification']) ?>')">
            <div class="ndrop-icon <?= htmlspecialchars($_t) ?>">
              <?= notifIconeSvg($_t) ?>
            </div>
            <div class="ndrop-content">
              <div class="ndrop-text"><?= htmlspecialchars($_text) ?></div>
              <div class="ndrop-time"><?= htmlspecialchars($_time) ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="ndrop-foot">
      <a href="notifications.php">Voir toutes les notifications →</a>
    </div>
  </div>
</div>

<script>
  /* ════════════════════════════════════════════════════════ */
  /* ═══   GESTION DROPDOWN                                ═══ */
  /* ════════════════════════════════════════════════════════ */
  
  function ndropToggle(e) {
    e.stopPropagation();
    const menu = document.getElementById('ndrop-menu');
    const bell = document.getElementById('ndrop-bell');
    const open = menu.classList.toggle('show');
    bell.classList.toggle('active', open);
  }
  
  function ndropClose() {
    document.getElementById('ndrop-menu')?.classList.remove('show');
    document.getElementById('ndrop-bell')?.classList.remove('active');
  }
  
  /* Fermer si clic ailleurs */
  document.addEventListener('click', function(e) {
    const wrap = document.getElementById('ndrop-wrap');
    if (wrap && !wrap.contains(e.target)) {
      ndropClose();
    }
  });
  
  /* Échap pour fermer */
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') ndropClose();
  });
  
  function ndropItemClick(e, idNotif) {
    /* Marquer comme lue puis laisser la nav se faire */
    fetch('mark_notification_read.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'idNotification=' + encodeURIComponent(idNotif)
    }).catch(() => {});
    /* Si pas de lien (href="#"), on empêche la navigation */
    const item = e.currentTarget;
    if (item.getAttribute('href') === '#') {
      e.preventDefault();
      item.classList.remove('unread');
      ndropDecrementBadge();
    }
  }
  
  function ndropMarkAll(e) {
    e.stopPropagation();
    const btn = e.currentTarget;
    btn.disabled = true;
    btn.style.opacity = '0.5';
    
    const fd = new FormData();
    fd.append('action', 'all');
    
    fetch('mark_notification_read.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(json => {
        if (json.success) {
          /* Retirer la classe unread sur toutes les items */
          document.querySelectorAll('.ndrop-item.unread').forEach(i => i.classList.remove('unread'));
          /* Cacher le badge */
          document.getElementById('ndrop-badge')?.classList.add('hidden');
          /* Cacher le bouton "Tout marquer" */
          btn.style.display = 'none';
        }
      })
      .catch(() => {
        btn.disabled = false;
        btn.style.opacity = '1';
      });
  }
  
  function ndropDecrementBadge() {
    const badge = document.getElementById('ndrop-badge');
    if (!badge) return;
    let n = parseInt(badge.textContent) || 0;
    n = Math.max(0, n - 1);
    if (n === 0) {
      badge.classList.add('hidden');
    } else {
      badge.textContent = n > 99 ? '99+' : n;
    }
  }
</script>
<?php
session_start();
require_once 'connexion.php';
header('Content-Type: application/json');


?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AUTOMARKET — Connexion / Inscription</title>
  <script type="module" src="firebase.js"></script>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg-primary:    #ffffff;
      --bg-secondary:  #f5f5f3;
      --bg-tertiary:   #eeede8;
      --text-primary:  #1a1a18;
      --text-secondary:#5f5e5a;
      --text-tertiary: #888780;
      --border-light:  rgba(0,0,0,0.12);
      --border-mid:    rgba(0,0,0,0.25);
      --blue:          #185FA5;
      --blue-dark:     #0C447C;
      --blue-bg:       #E6F1FB;
      --blue-border:   #B5D4F4;
      --green:         #639922;
      --green-bg:      #EAF3DE;
      --green-border:  #C0DD97;
      --green-dark:    #27500A;
      --red:           #E24B4A;
      --red-bg:        #FCEBEB;
      --red-border:    #F7C1C1;
      --red-dark:      #791F1F;
      --amber:         #BA7517;
      --radius-sm:     6px;
      --radius-md:     8px;
      --radius-lg:     12px;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      font-size: 14px;
      background: var(--bg-tertiary);
      color: var(--text-primary);
      min-height: 100vh;
    }

    /* ── NAVBAR ─────────────────────────────────────────── */
    .nav {
      background: var(--bg-primary);
      border-bottom: 0.5px solid var(--border-light);
      padding: 0 24px;
      height: 52px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .logo {
      font-size: 18px;
      font-weight: 500;
      color: var(--blue);
      letter-spacing: -0.5px;
      display: flex;
      align-items: center;
      gap: 6px;
      text-decoration: none;
      cursor: pointer;
    }
    .logo-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--blue);
    }
    .nav-links {
      display: flex;
      gap: 20px;
      align-items: center;
    }
    .nav-link {
      font-size: 13px;
      color: var(--text-secondary);
      cursor: pointer;
      text-decoration: none;
      transition: color 0.15s;
    }
    .nav-link:hover { color: var(--text-primary); }

    /* ── PAGE BODY ──────────────────────────────────────── */
    .page-body {
      max-width: 460px;
      margin: 0 auto;
      padding: 36px 16px 56px;
    }

    .logo-big {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-bottom: 20px;
    }
    .logo-big-dot {
      width: 11px;
      height: 11px;
      border-radius: 50%;
      background: var(--blue);
    }
    .logo-big-text {
      font-size: 20px;
      font-weight: 500;
      color: var(--blue);
      letter-spacing: -0.5px;
    }

    /* ── MAIN TABS (Connexion / Inscription) ────────────── */
    .main-tabs {
      display: flex;
      border-bottom: 0.5px solid var(--border-light);
      margin-bottom: 24px;
    }
    .main-tab {
      flex: 1;
      text-align: center;
      padding: 12px 0;
      font-size: 15px;
      font-weight: 400;
      color: var(--text-secondary);
      cursor: pointer;
      border-bottom: 2px solid transparent;
      margin-bottom: -0.5px;
      transition: all 0.15s;
    }
    .main-tab.active {
      color: var(--blue);
      font-weight: 500;
      border-bottom-color: var(--blue);
    }

    /* ── CARD ───────────────────────────────────────────── */
    .card {
      background: var(--bg-primary);
      border: 0.5px solid var(--border-light);
      border-radius: var(--radius-lg);
      padding: 28px 32px;
    }

    /* ── TYPE TABS (Particulier / Pro) ──────────────────── */
    .type-tabs {
      display: flex;
      background: var(--bg-secondary);
      border-radius: var(--radius-md);
      padding: 3px;
      margin-bottom: 20px;
      gap: 2px;
    }
    .type-tab {
      flex: 1;
      text-align: center;
      padding: 6px 8px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      cursor: pointer;
      color: var(--text-secondary);
      transition: all 0.15s;
      border: 0.5px solid transparent;
    }
    .type-tab.active {
      background: var(--bg-primary);
      color: var(--text-primary);
      font-weight: 500;
      border-color: var(--border-light);
    }
    .badge-pro {
      background: var(--blue-bg);
      color: var(--blue);
      font-size: 10px;
      padding: 1px 6px;
      border-radius: 20px;
      border: 0.5px solid var(--blue-border);
      margin-left: 3px;
    }

    /* ── STEP BAR ───────────────────────────────────────── */
    .step-bar {
      display: flex;
      gap: 5px;
      margin-bottom: 6px;
    }
    .step-seg {
      flex: 1;
      height: 3px;
      border-radius: 2px;
      background: var(--blue);
      transition: background 0.3s;
    }
    .step-seg.off { background: var(--border-light); }
    .step-label-text {
      font-size: 12px;
      color: var(--text-tertiary);
      margin-bottom: 18px;
    }

    /* ── SECTION LABEL ──────────────────────────────────── */
    .section-label {
      font-size: 11px;
      font-weight: 500;
      color: var(--text-tertiary);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin: 16px 0 10px;
    }

    /* ── FORM ELEMENTS ──────────────────────────────────── */
    .row2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-bottom: 10px;
    }
    .field { margin-bottom: 10px; }
    .label {
      font-size: 12px;
      color: var(--text-secondary);
      margin-bottom: 4px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="tel"],
    input[type="url"],
    select {
      width: 100%;
      height: 38px;
      border: 0.5px solid var(--border-mid);
      border-radius: var(--radius-md);
      padding: 0 12px;
      font-size: 14px;
      background: var(--bg-primary);
      color: var(--text-primary);
      outline: none;
      font-family: inherit;
      transition: border-color 0.15s, box-shadow 0.15s;
      appearance: none;
    }
    input:focus, select:focus {
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(24,95,165,0.1);
    }
    input.error {
      border-color: var(--red);
    }
    input.error:focus {
      box-shadow: 0 0 0 3px rgba(226,75,74,0.1);
    }
    select {
      background-image: url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23888' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
      padding-right: 28px;
      cursor: pointer;
    }
    .tel-row { display: flex; gap: 8px; }
    .tel-row select { width: 76px; flex-shrink: 0; }
    .tel-row input  { flex: 1; }

    /* ── PASS WRAP ──────────────────────────────────────── */
    .pass-wrap { position: relative; }
    .pass-wrap input { padding-right: 40px; }
    .eye-btn {
      position: absolute;
      right: 11px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: var(--text-tertiary);
      background: none;
      border: none;
      padding: 0;
      display: flex;
      align-items: center;
    }

    /* ── STRENGTH ───────────────────────────────────────── */
    .strength-bars { display: flex; gap: 4px; margin-top: 5px; }
    .sbar {
      flex: 1;
      height: 3px;
      border-radius: 2px;
      background: var(--border-light);
      transition: background 0.2s;
    }
    .slabel {
      font-size: 11px;
      color: var(--text-tertiary);
      margin-top: 3px;
      text-align: right;
    }

    /* ── CHECKBOX ───────────────────────────────────────── */
    .check-row {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      margin-bottom: 8px;
      font-size: 12px;
      color: var(--text-secondary);
      line-height: 1.5;
    }
    .check {
      width: 15px;
      height: 15px;
      border: 0.5px solid var(--border-mid);
      border-radius: 3px;
      flex-shrink: 0;
      margin-top: 1px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--bg-primary);
      transition: all 0.15s;
    }
    .check.checked {
      background: var(--blue);
      border-color: var(--blue);
    }
    .check.checked::after {
      content: '';
      width: 4px;
      height: 7px;
      border: 1.5px solid white;
      border-top: none;
      border-left: none;
      transform: rotate(45deg) translateY(-1px);
      display: block;
    }

    /* ── LINKS ──────────────────────────────────────────── */
    a, .link {
      color: var(--blue);
      cursor: pointer;
      font-size: 12px;
      text-decoration: none;
    }
    a:hover, .link:hover { text-decoration: underline; }

    /* ── BUTTONS ────────────────────────────────────────── */
    .btn-primary {
      width: 100%;
      height: 42px;
      background: var(--blue);
      color: white;
      border: none;
      border-radius: var(--radius-md);
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      font-family: inherit;
      transition: background 0.15s;
      margin-top: 16px;
    }
    .btn-primary:hover { background: var(--blue-dark); }
    .btn-primary:active { transform: scale(0.99); }
    .btn-primary:disabled {
      background: var(--border-light);
      color: var(--text-tertiary);
      cursor: not-allowed;
    }
    .btn-ghost {
      width: 100%;
      height: 38px;
      background: transparent;
      color: var(--text-secondary);
      border: 0.5px solid var(--border-mid);
      border-radius: var(--radius-md);
      font-size: 13px;
      cursor: pointer;
      font-family: inherit;
      transition: all 0.15s;
      margin-top: 8px;
    }
    .btn-ghost:hover {
      border-color: var(--text-secondary);
      color: var(--text-primary);
    }

    /* ── ALERTS ─────────────────────────────────────────── */
    .alert {
      background: var(--red-bg);
      border: 0.5px solid var(--red-border);
      border-radius: var(--radius-md);
      padding: 10px 14px;
      margin-bottom: 12px;
      font-size: 13px;
      color: var(--red-dark);
      display: flex;
      align-items: flex-start;
      gap: 8px;
    }
    .alert-icon {
      width: 16px;
      height: 16px;
      border-radius: 50%;
      background: var(--red);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      font-weight: 500;
      flex-shrink: 0;
      margin-top: 1px;
    }
    .success-box {
      background: var(--green-bg);
      border: 0.5px solid var(--green-border);
      border-radius: var(--radius-md);
      padding: 10px 14px;
      margin-bottom: 12px;
      font-size: 13px;
      color: var(--green-dark);
      display: flex;
      align-items: flex-start;
      gap: 8px;
    }
    .success-icon {
      width: 16px;
      height: 16px;
      border-radius: 50%;
      background: var(--green);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      flex-shrink: 0;
      margin-top: 1px;
    }

    /* ── FORGOT PANEL ───────────────────────────────────── */
    .forgot-panel {
      background: var(--bg-secondary);
      border-radius: var(--radius-md);
      padding: 14px;
      margin-top: 10px;
      border: 0.5px solid var(--border-light);
    }
    .forgot-title { font-size: 14px; font-weight: 500; margin-bottom: 4px; }
    .forgot-sub   { font-size: 12px; color: var(--text-secondary); margin-bottom: 12px; }
    .forgot-row   { display: flex; gap: 8px; align-items: center; }
    .forgot-row input { flex: 1; }
    .btn-send {
      height: 38px;
      padding: 0 14px;
      background: var(--blue);
      color: white;
      border: none;
      border-radius: var(--radius-md);
      font-size: 13px;
      cursor: pointer;
      white-space: nowrap;
      font-family: inherit;
      transition: background 0.15s;
    }
    .btn-send:hover { background: var(--blue-dark); }
    .forgot-ok {
      font-size: 12px;
      color: #3B6D11;
      margin-top: 8px;
    }

    /* ── ATTEMPTS ───────────────────────────────────────── */
    .attempts {
      font-size: 12px;
      color: var(--text-tertiary);
      text-align: center;
      margin-top: 6px;
    }
    .attempts span { color: var(--red); font-weight: 500; }

    /* ── NOTE ───────────────────────────────────────────── */
    .note { font-size: 11px; color: var(--text-tertiary); margin-top: 4px; }

    /* ── DIVIDER ────────────────────────────────────────── */
    .divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 18px 0;
    }
    .div-line { flex: 1; height: 0.5px; background: var(--border-light); }
    .div-text  { font-size: 12px; color: var(--text-tertiary); white-space: nowrap; }

    /* ── SOCIAL BUTTONS ─────────────────────────────────── */
    .social-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .social-btn {
      height: 38px;
      border: 0.5px solid var(--border-mid);
      border-radius: var(--radius-md);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      font-size: 13px;
      color: var(--text-secondary);
      cursor: pointer;
      background: var(--bg-primary);
      transition: border-color 0.15s, color 0.15s;
      font-family: inherit;
    }
    .social-btn:hover {
      border-color: var(--text-secondary);
      color: var(--text-primary);
    }

    /* ── FOOTER ─────────────────────────────────────────── */
    .footer-link-row {
      text-align: center;
      font-size: 13px;
      color: var(--text-secondary);
      margin-top: 20px;
    }
    .page-footer {
      text-align: center;
      margin-top: 16px;
      font-size: 11px;
      color: var(--text-tertiary);
    }
    .page-footer span { color: var(--blue); cursor: pointer; }
    .page-footer span:hover { text-decoration: underline; }

    /* ── UTILS ──────────────────────────────────────────── */
    .hidden { display: none !important; }
    .mt4 { margin-top: 4px; }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="nav">
    <div class="logo">
      <img src="images\id.png" alt="" style="height:34px;width:auto; display:inline;">
      
    </div>
    
  </nav>

  <!-- PAGE BODY -->
  <div class="page-body">

    <div class="logo-big">
      <img src="images\logo.png" alt="AUTOMARKET" style="height:160px;width:auto;display:block;">
    </div>

    <!-- MAIN TABS -->
    <div class="main-tabs">
      <div class="main-tab active" id="main-tab-login"    onclick="switchMain('login')">Se connecter</div>
      <div class="main-tab"        id="main-tab-register" onclick="switchMain('register')">Créer un compte</div>
    </div>

    <!-- ══════════════════════════════════════════════════ -->
    <!--                   CONNEXION                       -->
    <!-- ══════════════════════════════════════════════════ -->
    <div id="panel-login">
      <div class="card">

        <div id="login-alert" class="alert hidden">
          <div class="alert-icon">!</div>
          <span id="login-alert-text">E-mail ou mot de passe incorrect.</span>
        </div>
        <div id="login-success" class="success-box hidden">
          <div class="success-icon">✓</div>
          <span>Connexion réussie ! Redirection en cours…</span>
        </div>

        <!-- Email / Téléphone -->
        <div class="type-tabs">
          <div class="type-tab active" id="ltab-email" onclick="switchLoginTab('email')">E-mail</div>
          <div class="type-tab"        id="ltab-tel"   onclick="switchLoginTab('tel')">Téléphone</div>
        </div>

        <div id="lfield-email" class="field">
          <label class="label">Adresse e-mail</label>
          <input type="email" id="l-email" placeholder="exemple@email.com" oninput="clearLoginError()">
        </div>
       <div class="tel-row">
  <div style="display:flex;align-items:center;padding:0 10px;border:1px solid #ccc;border-radius:6px;background:#eee;">
    +213
  </div>
<input type="tel" id="r-tel" placeholder="5XXXXXXXX" maxlength="9"
oninput="validatePhoneInput(this)">
</div>

        <div class="field">
          <label class="label">
            Mot de passe
            <span class="link" onclick="toggleForgot()">Mot de passe oublié ?</span>
          </label>
          <div class="pass-wrap">
            <input type="password" id="l-pass" placeholder="Votre mot de passe" oninput="clearLoginError()">
            <button class="eye-btn" onclick="togglePass('l-pass', this)" type="button">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <!-- Mot de passe oublié inline -->
        <div id="forgot-panel" class="forgot-panel hidden">
          <div class="forgot-title">Réinitialiser le mot de passe</div>
          <div class="forgot-sub">Un lien sera envoyé à votre adresse e-mail</div>
          <div class="forgot-row">
            <input type="email" id="forgot-email" placeholder="votre@email.com">
            <button class="btn-send" onclick="sendReset()">Envoyer</button>
          </div>
          <div id="forgot-ok" class="forgot-ok hidden">Lien envoyé ! Vérifiez votre boîte mail.</div>
        </div>

        <div class="check-row" style="margin-top:12px">
          <div class="check checked" id="l-remember" onclick="toggleChk('l-remember')"></div>
          <span>Rester connecté sur cet appareil</span>
        </div>

        <button class="btn-primary" id="l-btn" onclick="tryLogin()">Se connecter</button>
        <div class="attempts hidden" id="l-attempts">
          <span id="l-count">2</span> tentatives restantes avant blocage temporaire
        </div>

      </div>

      <div class="divider">
        <div class="div-line"></div>
        <div class="div-text">ou continuer avec</div>
        <div class="div-line"></div>
      </div>
      <div class="social-grid">
        <button class="social-btn">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
          </svg>
          Google
        </button>
        <button class="social-btn">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="#1877F2">
            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
          </svg>
          Facebook
        </button>
      </div>

      <div class="footer-link-row">
        Pas encore de compte ?
        <span class="link" style="font-size:13px" onclick="switchMain('register')">Créer un compte</span>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════ -->
    <!--                  INSCRIPTION                      -->
    <!-- ══════════════════════════════════════════════════ -->
    <div id="panel-register" class="hidden">
      <div class="card">

        <!-- Barre de progression (2 étapes) -->
        <div class="step-bar">
          <div class="step-seg" id="rs1"></div>
          <div class="step-seg off" id="rs2"></div>
        </div>
        <div class="step-label-text" id="step-label">Étape 1 sur 2 — Informations personnelles</div>

        <!-- ── ÉTAPE 1 : Informations personnelles ── -->
        <div id="reg-step1">

          <div class="type-tabs">
            <div class="type-tab active" id="rtab-part" onclick="switchRegTab('part')">Particulier</div>
            <div class="type-tab"        id="rtab-pro"  onclick="switchRegTab('pro')">
              Professionnel <span class="badge-pro">Pro</span>
            </div>
          </div>

          <div class="section-label">Coordonnées</div>

          <div class="row2">
            <div>
              <label class="label">Prénom</label>
              <input type="text" id="r-prenom" placeholder="Prénom">
            </div>
            <div>
              <label class="label">Nom</label>
              <input type="text" id="r-nom" placeholder="Nom">
            </div>
          </div>

          <div class="field">
            <label class="label">Adresse e-mail</label>
            <input type="email" id="r-email" placeholder="exemple@email.com">
          </div>

          <div class="field">
  <label class="label">Téléphone</label>
  <div class="tel-row">
    <div style="display:flex;align-items:center;padding:0 10px;border:1px solid #ccc;border-radius:6px;background:#eee;">
      +213
    </div>
    <input type="tel" id="r-tel" placeholder="5XXXXXXXX" maxlength="9" oninput="validatePhoneInput(this)">
  </div>
</div>
          <div class="field">
            <label class="label">Wilaya</label>
            <select id="r-wilaya">
              <option value="">Sélectionner votre wilaya</option>
              <option value="01">Adrar</option>
              <option value="02">Aïn Defla</option>
              <option value="03">Aïn Témouchent</option>
              <option value="04">Alger</option>
              <option value="05">Annaba</option>
              <option value="06">Batna</option>
              <option value="07">Béchar</option>
              <option value="08">Béjaïa</option>
              <option value="09">Biskra</option>
              <option value="10">Blida</option>
              <option value="11">Bordj Bou Arreridj</option>
              <option value="12">Bouira</option>
              <option value="13">Boumerdès</option>
              <option value="14">Chlef</option>
              <option value="15">Constantine</option>
              <option value="16">Djelfa</option>
              <option value="17">Djanet</option>
              <option value="18">El Bayadh</option>
              <option value="19">El Oued</option>
              <option value="20">El Tarf</option>
              <option value="21">Essenia</option>
              <option value="22">Guelma</option>
              <option value="23">Ghardaïa</option>
              <option value="24">Gouraya</option>
              <option value="25">Illizi</option>
              <option value="26">Jijel</option>
              <option value="27">Khenchela</option>
              <option value="28">Laghouat</option>
              <option value="29">Mila</option>
              <option value="30">Mascara</option>
              <option value="31">Médéa</option>
              <option value="32">Mila</option>
              <option value="33">Mostaganem</option>
              <option value="34">M'Sila</option>
              <option value="35">Naâma</option>
              <option value="36">Oran</option>
              <option value="37">Ouargla</option>
              <option value="38">Oum El Bouaghi</option>
              <option value="39">Relizane</option>
              <option value="40">Saïda</option>
              <option value="41">Sétif</option>
              <option value="42">Sidi Bel Abbès</option>
              <option value="43">Skikda</option>
              <option value="44">Souk Ahras</option>
              <option value="45">Tamanrasset</option>
              <option value="46">Tébessa</option>
              <option value="47">Tiaret</option>
              <option value="48">Tindouf</option>
            </select>
          </div>
           
          <!-- Champs professionnels (masqués par défaut) -->
          <div id="pro-extra" class="hidden">
            <div class="section-label">Informations professionnelles</div>
            <div class="field">
              <label class="label">Nom de l'entreprise</label>
              <input type="text" id="pro-nom-entreprise"placeholder="Ex : Auto Elite Alger">
            </div>
            <div class="row2">
              <div>
                <label class="label">N° RC / NIF</label>
                <input type="text" id="pro-rc" placeholder="Registre commerce">
              </div>
              <div>
                <label class="label">Site web</label>
                <input type="url" id="pro-siteweb" placeholder="www.site.dz">
              </div>
            </div>
          </div>
          <div id="step1-alert" class="alert hidden" style="margin-top:14px">
          <div class="alert-icon">!</div>
          <span id="step1-alert-text">Veuillez remplir tous les champs obligatoires.</span>
          </div>

          <button type="button" class="btn-primary" onclick="goStep(2)">Continuer →</button>
        </div>

        <!-- ── ÉTAPE 2 : Sécurité + CGU ── -->
        <div id="reg-step2" class="hidden">

          <div class="section-label">Sécurité</div>

          <div class="field">
            <label class="label">Mot de passe</label>
            <div class="pass-wrap">
              <input type="password" id="r-pass" placeholder="Minimum 8 caractères" oninput="updateStrength(this.value)">
              <button class="eye-btn" onclick="togglePass('r-pass', this)" type="button">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div class="strength-bars">
              <div class="sbar" id="sb1"></div>
              <div class="sbar" id="sb2"></div>
              <div class="sbar" id="sb3"></div>
              <div class="sbar" id="sb4"></div>
              <div class="sbar" id="sb5"></div>
            </div>
            <div class="slabel" id="s-label">Entrez un mot de passe</div>
          </div>

          <div class="field">
            <label class="label">Confirmer le mot de passe</label>
            <div class="pass-wrap">
              <input type="password" id="r-pass2" placeholder="Répétez votre mot de passe" oninput="checkMatch()">
              <button class="eye-btn" id="match-eye" onclick="togglePass('r-pass2', this)" type="button">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
          </div>

          <div class="section-label" style="margin-top:20px">Conditions</div>

          <div class="check-row">
            <div class="check checked" id="chk-cgu" onclick="toggleChk('chk-cgu')"></div>
            <span>
              J'accepte les <a class="link" href="#">conditions générales d'utilisation</a>
              et la <a class="link" href="#">politique de confidentialité</a> d'AUTOMARKET
            </span>
          </div>
          <div class="check-row">
            <div class="check" id="chk-alertes" onclick="toggleChk('chk-alertes')"></div>
            <span>Recevoir des alertes sur les nouvelles annonces correspondant à mes critères</span>
          </div>

          <div id="reg-success" class="success-box hidden" style="margin-top:14px">
            <div class="success-icon">✓</div>
            <span>Compte créé ! Un e-mail de vérification a été envoyé.</span>
          </div>
          <div id="reg-alert" class="alert hidden" style="margin-top:14px">
          <div class="alert-icon">!</div>
          <span id="reg-alert-text">Veuillez remplir tous les champs obligatoires.</span>
          </div>

          <button type="button" class="btn-primary" id="reg-btn" onclick="finishReg()">Créer mon compte</button>
          <button type="button" class="btn-ghost" onclick="goStep(1)">← Retour</button>

        </div>
      </div>

      <div class="divider">
        <div class="div-line"></div>
        <div class="div-text">ou s'inscrire avec</div>
        <div class="div-line"></div>
      </div>
      <div class="social-grid">
        <button class="social-btn">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
          </svg>
          Google
        </button>
        <button class="social-btn">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="#1877F2">
            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
          </svg>
          Facebook
        </button>
      </div>

      <div class="footer-link-row">
        Déjà inscrit ?
        <span class="link" style="font-size:13px" onclick="switchMain('login')">Se connecter</span>
      </div>
    </div>

    <div class="page-footer">
      © 2025 AUTOMARKET — Marketplace automobile algérienne &nbsp;·&nbsp;
      <span>Aide</span> &nbsp;·&nbsp; <span>Confidentialité</span>
    </div>

  </div><!-- end page-body -->

  <script >
    /* ── STATE ───────────────────────────────────── */
    let loginAttempts = 3;
    let currentStep   = 1;

    /* ── MAIN PANEL SWITCH ───────────────────────── */
    function switchMain(t) {
      const isLogin = t === 'login';
      document.getElementById('main-tab-login').classList.toggle('active', isLogin);
      document.getElementById('main-tab-register').classList.toggle('active', !isLogin);
      document.getElementById('panel-login').classList.toggle('hidden', !isLogin);
      document.getElementById('panel-register').classList.toggle('hidden', isLogin);
    }

    /* ── LOGIN TAB (email / tel) ─────────────────── */
    function switchLoginTab(t) {
      document.getElementById('ltab-email').classList.toggle('active', t === 'email');
      document.getElementById('ltab-tel').classList.toggle('active',   t === 'tel');
      document.getElementById('lfield-email').classList.toggle('hidden', t !== 'email');
      document.getElementById('lfield-tel').classList.toggle('hidden',   t !== 'tel');
      clearLoginError();
    }

    /* ── REGISTER TYPE TAB (part / pro) ──────────── */
    function switchRegTab(t) {
      document.getElementById('rtab-part').classList.toggle('active', t === 'part');
      document.getElementById('rtab-pro').classList.toggle('active',  t === 'pro');
      document.getElementById('pro-extra').classList.toggle('hidden', t !== 'pro');
    }

    /* ── STEP NAVIGATION ─────────────────────────── */
    const stepLabels = {
      1: 'Étape 1 sur 2 — Informations personnelles',
      2: 'Étape 2 sur 2 — Sécurité & Conditions'
    };
   function goStep(n) {
  if (n === 2) {
    const prenom = document.getElementById('r-prenom').value.trim();
    const nom = document.getElementById('r-nom').value.trim();
    const email = document.getElementById('r-email').value.trim();
    const tel = document.getElementById('r-tel').value.trim();
    const wilaya = document.getElementById('r-wilaya').value;

    const step1Alert = document.getElementById('step1-alert');
    const step1AlertText = document.getElementById('step1-alert-text');

    const isPro = document.getElementById('rtab-pro').classList.contains('active');
    const nomEntreprise = document.getElementById('pro-nom-entreprise')?.value.trim() || "";
    const rc = document.getElementById('pro-rc')?.value.trim() || "";

    step1Alert.classList.add('hidden');

    if (!prenom || !nom || !email || !tel || !wilaya) {
      step1AlertText.textContent = "Veuillez remplir tous les champs obligatoires.";
      step1Alert.classList.remove('hidden');
      return;
    }

    if (!email.includes('@') || !email.includes('.')) {
      step1AlertText.textContent = "Veuillez saisir une adresse e-mail valide.";
      step1Alert.classList.remove('hidden');
      return;
    }

    const telRegex = /^[567][0-9]{8}$/;
    if (!telRegex.test(tel)) {
      step1AlertText.textContent = "Numéro invalide (ex: +213 5XXXXXXXX).";
      step1Alert.classList.remove('hidden');
      return;
    }

    if (isPro && (!nomEntreprise || !rc)) {
      step1AlertText.textContent = "Veuillez remplir les informations professionnelles obligatoires.";
      step1Alert.classList.remove('hidden');
      return;
    }
  }

  document.getElementById('reg-step' + currentStep).classList.add('hidden');
  currentStep = n;
  document.getElementById('reg-step' + n).classList.remove('hidden');
  document.getElementById('step-label').textContent = stepLabels[n];
  document.getElementById('rs1').classList.toggle('off', n < 1);
  document.getElementById('rs2').classList.toggle('off', n < 2);
}

    /* ── CHECKBOX TOGGLE ─────────────────────────── */
    function toggleChk(id) {
      document.getElementById(id).classList.toggle('checked');
    }

    /* ── PASSWORD VISIBILITY ─────────────────────── */
    function togglePass(inputId, btn) {
      const inp = document.getElementById(inputId);
      const show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      btn.style.color = show ? 'var(--blue)' : 'var(--text-tertiary)';
    }
    function validatePhoneInput(input) {
  // Supprimer tout sauf chiffres
  let value = input.value.replace(/[^0-9]/g, '');

  // Si premier chiffre ≠ 5,6,7 → supprimer
  if (value.length > 0 && !['5','6','7'].includes(value[0])) {
    value = value.substring(1);
  }

  // Limiter à 9 chiffres
  value = value.substring(0, 9);

  input.value = value;
}

    /* ── LOGIN LOGIC ─────────────────────────────── */
    function clearLoginError() {
      document.getElementById('login-alert').classList.add('hidden');
      document.getElementById('l-pass').classList.remove('error');
    }

    function tryLogin() {
  const email = document.getElementById('l-email').value.trim();
  const pass  = document.getElementById('l-pass').value;

  if (!email || !pass) {
    document.getElementById('l-pass').classList.add('error');
    document.getElementById('login-alert').classList.remove('hidden');
    document.getElementById('login-alert-text').textContent = 'Veuillez remplir tous les champs.';
    return;
  }

  const fd = new FormData();
  fd.append('action', 'login');
  fd.append('email',  email);
  fd.append('pass',   pass);

  fetch('inscription.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        document.getElementById('login-success').classList.remove('hidden');
        document.getElementById('login-alert').classList.add('hidden');
        document.getElementById('l-btn').disabled = true;
        setTimeout(() => window.location.href = 'index.php', 1000);
      } else {
        loginAttempts--;
        document.getElementById('l-pass').classList.add('error');
        document.getElementById('login-alert').classList.remove('hidden');
        document.getElementById('login-alert-text').textContent = json.message;
        if (loginAttempts > 0) {
          document.getElementById('l-attempts').classList.remove('hidden');
          document.getElementById('l-count').textContent = loginAttempts;
        } else {
          document.getElementById('l-btn').disabled = true;
          document.getElementById('l-attempts').classList.add('hidden');
          loginAttempts = 3;
        }
      }
    });
}

    /* ── FORGOT PASSWORD ─────────────────────────── */
    function toggleForgot() {
      const p = document.getElementById('forgot-panel');
      p.classList.toggle('hidden');
      document.getElementById('forgot-ok').classList.add('hidden');
    }
    function sendReset() {
      document.getElementById('forgot-ok').classList.remove('hidden');
    }

    /* ── PASSWORD STRENGTH ───────────────────────── */
    function updateStrength(v) {
      let s = 0;
      if (v.length >= 8)            s++;
      if (v.length >= 12)           s++;
      if (/[A-Z]/.test(v))          s++;
      if (/[0-9]/.test(v))          s++;
      if (/[^A-Za-z0-9]/.test(v))   s++;
      const colors = ['', '#E24B4A', '#E24B4A', '#BA7517', '#639922', '#639922'];
      const labels = ['', 'Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];
      for (let i = 1; i <= 5; i++) {
        document.getElementById('sb' + i).style.background =
          i <= s ? colors[s] : 'var(--border-light)';
      }
      const lbl = document.getElementById('s-label');
      lbl.textContent = v.length ? (labels[s] || 'Très faible') : 'Entrez un mot de passe';
      lbl.style.color  = v.length ? colors[s] : 'var(--text-tertiary)';
    }

    /* ── PASSWORD MATCH ──────────────────────────── */
    function checkMatch() {
      const p1  = document.getElementById('r-pass').value;
      const p2  = document.getElementById('r-pass2').value;
      const eye = document.getElementById('match-eye');
      if (!p2) { eye.style.color = 'var(--text-tertiary)'; return; }
      eye.style.color = p1 === p2 ? '#639922' : '#E24B4A';
    }

   function finishReg() {
  const prenom = document.getElementById('r-prenom').value.trim();
  const nom    = document.getElementById('r-nom').value.trim();
  const email  = document.getElementById('r-email').value.trim();
  const tel    = document.getElementById('r-tel').value.trim();
  const wilaya = document.getElementById('r-wilaya').value;
  const pass   = document.getElementById('r-pass').value;
  const pass2  = document.getElementById('r-pass2').value;
  const cgu    = document.getElementById('chk-cgu').classList.contains('checked');

  const regAlert    = document.getElementById('reg-alert');
  const regAlertText= document.getElementById('reg-alert-text');
  const regSuccess  = document.getElementById('reg-success');

  regAlert.classList.add('hidden');
  regSuccess.classList.add('hidden');

  if (!prenom || !nom || !email || !pass || !pass2) {
    regAlertText.textContent = "Veuillez remplir tous les champs.";
    regAlert.classList.remove('hidden');
    return;
  }
  if (pass.length < 8) {
    regAlertText.textContent = "Mot de passe trop court (8 caractères minimum).";
    regAlert.classList.remove('hidden');
    return;
  }
  if (pass !== pass2) {
    regAlertText.textContent = "Les mots de passe ne correspondent pas.";
    regAlert.classList.remove('hidden');
    return;
  }
  if (!cgu) {
    regAlertText.textContent = "Vous devez accepter les conditions.";
    regAlert.classList.remove('hidden');
    return;
  }

  const fd = new FormData();
  fd.append('action',  'register');
  fd.append('email',   email);
  fd.append('nom',     nom);
  fd.append('prenom',  prenom);
  fd.append('pass',    pass);
  fd.append('tel',     tel);
  fd.append('wilaya',  wilaya);

  fetch('inscription.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        regSuccess.classList.remove('hidden');
        document.getElementById('reg-btn').disabled = true;
        setTimeout(() => window.location.href = 'index.php', 1500);
      } else {
        regAlertText.textContent = json.message || 'Erreur serveur';
        regAlert.classList.remove('hidden');
      }
    });
}
</script>

</body>
</html>

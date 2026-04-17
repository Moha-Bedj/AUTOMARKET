<?php
session_start();
require_once 'connexion.php';

$counts_type = ['voiture' => 0, 'moto' => 0, 'camion' => 0];

$r = mysqli_query($conn, "
    SELECT v.typeVehicule, COUNT(*) as nb 
    FROM Annonce a, Vehicule v 
    WHERE a.idVehicule = v.idVehicule 
    AND a.statutAnnonce = 'active' 
    GROUP BY v.typeVehicule
");
while ($row = mysqli_fetch_assoc($r)) {
    $type = strtolower($row['typeVehicule']);
    if (isset($counts_type[$type])) {
        $counts_type[$type] = $row['nb'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AUTOMARKET — Marketplace Automobile Algérienne</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue:       #185FA5;
      --blue-dk:    #0C447C;
      --blue-bg:    #E6F1FB;
      --blue-bd:    #B5D4F4;
      --bg0:        #ffffff;
      --bg1:        #f5f4f0;
      --bg2:        #eceae4;
      --t1:         #1a1a18;
      --t2:         #5f5e5a;
      --t3:         #888780;
      --bd:         rgba(0,0,0,0.11);
      --bd2:        rgba(0,0,0,0.22);
      --green:      #639922;
      --green-bg:   #EAF3DE;
      --green-dk:   #27500A;
      --green-bd:   #C0DD97;
      --red:        #E24B4A;
      --red-bg:     #FCEBEB;
      --amber:      #BA7517;
      --amber-bg:   #FAEEDA;
      --amber-bd:   #FAC775;
      --r6:  6px;
      --r8:  8px;
      --r10: 10px;
      --r12: 12px;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      font-size: 14px;
      background: var(--bg1);
      color: var(--t1);
      min-height: 100vh;
    }

    /* ═══════════════════════════════════════════
       NAVBAR
    ═══════════════════════════════════════════ */
    .nav {
      background: var(--bg0);
      border-bottom: 0.5px solid var(--bd);
      height: 52px;
      display: flex;
      align-items: center;
      padding: 0 20px;
      gap: 16px;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .logo {
      font-size: 17px;
      font-weight: 500;
      color: var(--blue);
      letter-spacing: -0.4px;
      display: flex;
      align-items: center;
      gap: 5px;
      white-space: nowrap;
      cursor: pointer;
      text-decoration: none;
    }
    .logo-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--blue);
      flex-shrink: 0;
    }
    .nav-search {
      flex: 1;
      max-width: 340px;
      position: relative;
    }
    .nav-search input {
      width: 100%;
      height: 34px;
      border: 0.5px solid var(--bd2);
      border-radius: 20px;
      padding: 0 12px 0 34px;
      font-size: 13px;
      background: var(--bg1);
      color: var(--t1);
      outline: none;
      font-family: inherit;
      transition: border-color .15s, background .15s;
    }
    .nav-search input:focus {
      border-color: var(--blue);
      background: var(--bg0);
      box-shadow: 0 0 0 3px rgba(24,95,165,.1);
    }
    .nav-search-icon {
      position: absolute;
      left: 10px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--t3);
      pointer-events: none;
    }
    .nav-links {
      display: flex;
      gap: 4px;
      align-items: center;
      margin-left: auto;
    }
    

    .nav-btn {
      font-size: 13px;
      padding: 6px 14px;
      border-radius: var(--r6);
      cursor: pointer;
      font-family: inherit;
      transition: all .15s;
    }
    .btn-outline {
      background: transparent;
      color: var(--blue);
      border: 0.5px solid var(--blue);
    }
    .btn-outline:hover { background: var(--blue-bg); }
    .btn-fill {
      background: var(--blue);
      color: #fff;
      border: none;
    }
    .btn-fill:hover { background: var(--blue-dk); }

    .nav-fav {
      position: relative;
      cursor: pointer;
      color: var(--t2);
      padding: 6px;
      display: flex;
      align-items: center;
      border-radius: var(--r6);
      transition: color .15s;
    }
    .nav-fav:hover { color: var(--blue); }
    .nav-badge {
      position: absolute;
      top: 2px; right: 2px;
      width: 14px; height: 14px;
      border-radius: 50%;
      background: var(--red);
      color: #fff;
      font-size: 9px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 500;
    }

    /* ═══════════════════════════════════════════
       HERO + SEARCH BOX
    ═══════════════════════════════════════════ */

    /* ══ HERO bleu — couleurs originales ══ */
    .hero {
      background: var(--blue);
      padding: 28px 0 0;
      text-align: center;
    }
    .hero-title {
      font-size: 22px;
      font-weight: 500;
      color: #fff;
      margin-bottom: 4px;
      letter-spacing: -.3px;
    }
    .hero-sub {
      font-size: 13px;
      color: rgba(255,255,255,.75);
      margin-bottom: 20px;
    }

    /* ── Search container : sidebar icônes + panneau blanc ── */
    .search-wrap {
      display: flex;
      max-width: 1100px;
      margin: 0 auto;
      align-items: stretch;
    }

    /* Sidebar icônes — fond bleu foncé */
    .vtype-sidebar {
      width: 68px;
      background: var(--blue-dk);
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 40px 0;
      gap: 2px;
      flex-shrink: 0;
      border-radius: 12px 0 0 0;
    }
    .vtype-icon-btn {
      width: 52px;
      height: 54px;
      border-radius: 10px;
      border: none;
      background: transparent;
      color: rgba(255,255,255,.5);
      cursor: pointer;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 4px;
      font-size: 9px;
      font-family: inherit;
      transition: all .15s;
      position: relative;
    }
    .vtype-icon-btn:hover {
      color: rgba(255,255,255,.85);
      background: rgba(255,255,255,.08);
    }
    .vtype-icon-btn.active {
      color: #fff;
      background: rgba(255,255,255,.15);
    }
    .vtype-icon-btn.active::before {
      content: '';
      position: absolute;
      left: 0; top: 10px; bottom: 10px;
      width: 3px;
      background: #fff;
      border-radius: 0 3px 3px 0;
    }
    .vtype-svg { width: 26px; height: 26px; }
    .vtype-label { font-size: 9px; line-height: 1; }

    @keyframes vtypePop {
      0%   { transform: scale(1); }
      40%  { transform: scale(1.28); }
      70%  { transform: scale(0.92); }
      100% { transform: scale(1); }
    }
    .vtype-icon-btn.animating .vtype-svg {
      animation: vtypePop 0.35s ease forwards;
    }

    /* Panneau de recherche blanc */
    .search-box {
      flex: 1;
      background: #fff;
      border-radius: 0 12px 0 0;
      padding: 16px 20px 14px;
    }

    /* Onglets */
    .search-tabs {
      display: flex;
      gap: 0;
      margin-bottom: 14px;
      border-bottom: 0.5px solid var(--bd);
    }
    .search-tab {
      padding: 6px 16px;
      font-size: 13px;
      cursor: pointer;
      color: var(--t2);
      border-bottom: 2px solid transparent;
      margin-bottom: -0.5px;
      transition: all .15s;
      white-space: nowrap;
    }
    .search-tab.active {
      color: var(--blue);
      font-weight: 500;
      border-bottom-color: var(--blue);
    }
    .search-tab:hover { color: var(--t1); }

    /* Grille de champs */
    .sf-grid { display: grid; gap: 10px; margin-bottom: 10px; }
    .sf-grid-row1 { grid-template-columns: 1fr 1fr 1fr 1fr; }
    .sf-grid-row2 { grid-template-columns: auto 1fr 1fr auto; align-items: end; }

    .sf-label {
      font-size: 11px;
      color: var(--t3);
      margin-bottom: 5px;
      font-weight: 500;
      letter-spacing: .2px;
    }
    .sf-select, .sf-input {
      width: 100%;
      height: 40px;
      border: 0.5px solid var(--bd2);
      border-radius: var(--r8);
      padding: 0 28px 0 10px;
      font-size: 13px;
      background: var(--bg0);
      color: var(--t1);
      outline: none;
      appearance: none;
      -webkit-appearance: none;
      font-family: inherit;
      cursor: pointer;
      background-image: url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23888' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 9px center;
      transition: border-color .15s;
    }
    .sf-select:focus, .sf-input:focus {
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(24,95,165,.1);
    }
    .sf-select option { background: #fff; color: var(--t1); }
    .sf-input { padding: 0 32px 0 10px; background-image: none; }

    /* Toggle Acheter / Crédit */
    .sf-toggle {
      display: flex;
      height: 40px;
      border: 0.5px solid var(--bd2);
      border-radius: var(--r8);
      overflow: hidden;
    }
    .sf-toggle-btn {
      padding: 0 14px;
      background: transparent;
      color: var(--t2);
      border: none;
      font-size: 13px;
      cursor: pointer;
      font-family: inherit;
      white-space: nowrap;
      transition: all .15s;
    }
    .sf-toggle-btn.active {
      color: var(--blue);
      font-weight: 500;
      box-shadow: inset 0 0 0 1.5px var(--blue);
      border-radius: 6px;
      background: var(--blue-bg);
    }
    .sf-toggle-sep { width: 0.5px; background: var(--bd); }

    /* Localisation */
    .sf-loc-wrap { position: relative; }
    .sf-loc-icon {
      position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
      color: var(--t3); pointer-events: none;
    }

    /* Checkbox électrique */
    .sf-elec-row {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 4px;
    }
    .sf-chk {
      width: 15px; height: 15px;
      border: 0.5px solid var(--bd2);
      border-radius: 3px;
      background: var(--bg0);
      cursor: pointer;
      flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      transition: all .15s;
    }
    .sf-chk.on { background: var(--blue); border-color: var(--blue); }
    .sf-chk.on::after {
      content: '';
      width: 4px; height: 7px;
      border: 1.5px solid #fff;
      border-top: none; border-left: none;
      transform: rotate(45deg) translateY(-1px);
      display: block;
    }
    .sf-elec-label { font-size: 13px; color: var(--t2); cursor: pointer; }
    .sf-elec-badge {
      width: 17px; height: 17px; border-radius: 50%;
      background: #2563eb;
      display: flex; align-items: center; justify-content: center;
    }

    /* Bouton recherche bleu */
    .btn-search {
      height: 40px;
      background: var(--blue);
      color: #fff;
      border: none;
      border-radius: var(--r8);
      padding: 0 18px;
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      font-family: inherit;
      display: flex;
      align-items: center;
      gap: 6px;
      white-space: nowrap;
      transition: background .15s;
      min-width: 170px;
      justify-content: center;
    }
    .btn-search:hover { background: var(--blue-dk); }
    .btn-search:active { transform: scale(0.99); }

    /* Footer row */
    .sf-footer-row {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-top: 10px;
    }
    .sf-footer-link {
      font-size: 12px;
      color: var(--t3);
      cursor: pointer;
      display: flex; align-items: center; gap: 5px;
      transition: color .15s;
    }
    .sf-footer-link:hover { color: var(--blue); }
    .price-input { width: 120px; }
    .km-input    { width: 110px; }
    .price-input:focus, .km-input:focus { border-color: var(--blue); }
    .price-sep { font-size: 12px; color: var(--t3); }

    .link-more {
      font-size: 13px;
      color: var(--blue);
      cursor: pointer;
      white-space: nowrap;
      margin-left: auto;
      text-decoration: none;
    }
    .link-more:hover { text-decoration: underline; }

    /* ═══════════════════════════════════════════
       QUICK FILTER PILLS
    ═══════════════════════════════════════════ */
    .qf-bar {
      background: var(--bg0);
      border-bottom: 0.5px solid var(--bd);
      padding: 10px 20px;
      display: flex;
      gap: 8px;
      overflow-x: auto;
      scrollbar-width: none;
    }
    .qf-bar::-webkit-scrollbar { display: none; }

    .qf-pill {
      height: 30px;
      padding: 0 12px;
      border-radius: 20px;
      border: 0.5px solid var(--bd2);
      background: var(--bg0);
      font-size: 12px;
      color: var(--t2);
      cursor: pointer;
      white-space: nowrap;
      display: flex;
      align-items: center;
      gap: 5px;
      transition: all .15s;
      font-family: inherit;
    }
    .qf-pill:hover {
      border-color: var(--blue);
      color: var(--blue);
      background: var(--blue-bg);
    }
    .qf-pill.active {
      border-color: var(--blue);
      color: var(--blue);
      background: var(--blue-bg);
      font-weight: 500;
    }
    .qf-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
    }

    /* ═══════════════════════════════════════════
       BODY LAYOUT
    ═══════════════════════════════════════════ */
    .body-wrap {
      max-width: 1100px;
      margin: 0 auto;
      padding: 20px 16px 48px;
      display: grid;
      grid-template-columns: 224px 1fr;
      gap: 20px;
    }

    /* ═══════════════════════════════════════════
       SIDEBAR
    ═══════════════════════════════════════════ */
    .sidebar {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .filter-card {
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r10);
      overflow: hidden;
    }
    .filter-head {
      padding: 11px 14px;
      font-size: 13px;
      font-weight: 500;
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
      user-select: none;
      transition: background .15s;
    }
    .filter-head:hover { background: var(--bg1); }
    .filter-arrow {
      color: var(--t3);
      font-size: 10px;
      transition: transform .2s;
    }
    .filter-arrow.open { transform: rotate(180deg); }
    .filter-body {
      padding: 2px 14px 12px;
      border-top: 0.5px solid var(--bd);
    }
    .filter-option {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 5px 0;
      font-size: 13px;
      color: var(--t2);
      cursor: pointer;
      transition: color .15s;
    }
    .filter-option:hover { color: var(--t1); }
    .fchk {
      width: 14px;
      height: 14px;
      border: 0.5px solid var(--bd2);
      border-radius: 3px;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--bg0);
      transition: all .15s;
    }
    .fchk.on {
      background: var(--blue);
      border-color: var(--blue);
    }
    .fchk.on::after {
      content: '';
      width: 3px;
      height: 6px;
      border: 1.5px solid #fff;
      border-top: none;
      border-left: none;
      transform: rotate(45deg) translateY(-1px);
      display: block;
    }
    .fcount {
      margin-left: auto;
      font-size: 11px;
      color: var(--t3);
    }
    .hero-search{display: none;}
    .range-row {
      gap: 6px;
      align-items: center;
      padding-top: 8px;
    }
    .range-in {
      flex: 1;
      height: 32px;
      border: 0.5px solid var(--bd2);
      border-radius: var(--r6);
      padding: 0 8px;
      font-size: 12px;
      background: var(--bg0);
      color: var(--t1);
      outline: none;
      font-family: inherit;
      transition: border-color .15s;
    }
    .range-in:focus { border-color: var(--blue); }
    .range-sep { font-size: 11px; color: var(--t3); }

    .sidebar-reset {
      font-size: 12px;
      color: var(--blue);
      cursor: pointer;
      text-align: center;
      padding: 6px;
    }
    .sidebar-reset:hover { text-decoration: underline; }

    .ad-card {
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r10);
      padding: 16px;
      text-align: center;
    }
    .ad-card-title { font-size: 14px; font-weight: 500; margin-bottom: 4px; }
    .ad-card-sub   { font-size: 12px; color: var(--t2); margin-bottom: 12px; line-height: 1.5; }

    /* ═══════════════════════════════════════════
       MAIN — RESULTS
    ═══════════════════════════════════════════ */
    .sell-banner {
      background: var(--blue);
      border-radius: var(--r10);
      padding: 16px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 12px;
    }
    .sell-banner-text { color: #fff; }
    .sell-banner-title { font-size: 14px; font-weight: 500; margin-bottom: 2px; }
    .sell-banner-sub   { font-size: 12px; opacity: .8; }
    .btn-white {
      background: #fff;
      color: var(--blue);
      border: none;
      border-radius: var(--r6);
      padding: 7px 16px;
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      white-space: nowrap;
      font-family: inherit;
      flex-shrink: 0;
      transition: background .15s;
    }
    .btn-white:hover { background: var(--blue-bg); }

    .results-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 12px;
      flex-wrap: wrap;
      gap: 8px;
    }
    .results-count { font-size: 14px; color: var(--t1); }
    .results-count span { font-weight: 500; }
    .sort-row { display: flex; align-items: center; gap: 8px; }
    .sort-label { font-size: 13px; color: var(--t2); }
    .sort-sel {
      height: 32px;
      border: 0.5px solid var(--bd2);
      border-radius: var(--r6);
      padding: 0 24px 0 8px;
      font-size: 13px;
      background: var(--bg0);
      color: var(--t1);
      outline: none;
      appearance: none;
      -webkit-appearance: none;
      font-family: inherit;
      cursor: pointer;
      background-image: url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23888' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 7px center;
    }
    .view-btns {
      display: flex;
      border: 0.5px solid var(--bd2);
      border-radius: var(--r6);
      overflow: hidden;
    }
    .view-btn {
      padding: 5px 9px;
      cursor: pointer;
      color: var(--t3);
      background: var(--bg0);
      border: none;
      display: flex;
      align-items: center;
      transition: all .15s;
    }
    .view-btn + .view-btn { border-left: 0.5px solid var(--bd); }
    .view-btn.active { background: var(--bg1); color: var(--t1); }
    .view-btn:hover  { background: var(--bg1); }

    /* ═══════════════════════════════════════════
       LISTING CARDS
    ═══════════════════════════════════════════ */
    .listings { display: flex; flex-direction: column; gap: 10px; }

    .lcard {
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r10);
      display: flex;
      overflow: hidden;
      cursor: pointer;
      transition: border-color .15s, box-shadow .15s;
    }
    .lcard:hover {
      border-color: var(--blue);
      box-shadow: 0 0 0 2px rgba(24,95,165,.08);
    }
    .lcard-highlight {
      border-left: 3px solid var(--amber);
    }

    .lcard-img {
      width: 210px;
      flex-shrink: 0;
      background: var(--bg1);
      position: relative;
      overflow: hidden;
      height: 152px;
    }
    .lcard-img img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .lcard-img-ph {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--t3);
    }
    .img-badge {
      position: absolute;
      bottom: 6px; left: 6px;
      background: rgba(0,0,0,.55);
      color: #fff;
      font-size: 10px;
      padding: 2px 7px;
      border-radius: 4px;
    }
    .fav-btn {
      position: absolute;
      top: 6px; right: 6px;
      width: 28px; height: 28px;
      border-radius: 50%;
      background: rgba(255,255,255,.9);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      border: 0.5px solid rgba(0,0,0,.1);
      transition: all .15s;
    }
    .fav-btn:hover { background: #fff; transform: scale(1.05); }

    .lcard-body {
      flex: 1;
      padding: 14px 16px;
      display: flex;
      flex-direction: column;
      min-width: 0;
    }
    .lcard-top {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 8px;
      margin-bottom: 7px;
    }
    .lcard-title {
      font-size: 15px;
      font-weight: 500;
      color: var(--t1);
      line-height: 1.3;
    }
    .lcard-price {
      font-size: 18px;
      font-weight: 500;
      color: var(--blue);
      white-space: nowrap;
      flex-shrink: 0;
    }
    .lcard-specs {
      display: flex;
      gap: 10px;
      margin-bottom: 8px;
      flex-wrap: wrap;
      align-items: center;
    }
    .lspec { font-size: 12px; color: var(--t2); }
    .lspec-dot {
      width: 3px; height: 3px;
      border-radius: 50%;
      background: var(--t3);
      flex-shrink: 0;
    }
    .lcard-tags {
      display: flex;
      gap: 6px;
      margin-bottom: 10px;
      flex-wrap: wrap;
    }
    .ltag {
      font-size: 11px;
      padding: 2px 8px;
      border-radius: 20px;
      border: 0.5px solid;
    }
    .ltag-green  { background: var(--green-bg);   color: var(--green-dk); border-color: var(--green-bd); }
    .ltag-blue   { background: var(--blue-bg);    color: var(--blue-dk);  border-color: var(--blue-bd); }
    .ltag-amber  { background: var(--amber-bg);   color: #633806;         border-color: var(--amber-bd); }
    .ltag-gray   { background: var(--bg1);        color: var(--t2);       border-color: var(--bd2); }

    .lcard-foot {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: auto;
      padding-top: 10px;
      border-top: 0.5px solid var(--bd);
    }
    .lseller {
      font-size: 12px;
      color: var(--t2);
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .seller-badge {
      font-size: 10px;
      background: var(--blue-bg);
      color: var(--blue);
      padding: 1px 6px;
      border-radius: 10px;
      border: 0.5px solid var(--blue-bd);
    }
    .ldate { font-size: 11px; color: var(--t3); }

    /* ═══════════════════════════════════════════
       PAGINATION
    ═══════════════════════════════════════════ */
    .pagination {
      display: flex;
      justify-content: center;
      gap: 4px;
      margin-top: 20px;
    }
    .pg-btn {
      width: 32px; height: 32px;
      border-radius: var(--r6);
      border: 0.5px solid var(--bd2);
      background: var(--bg0);
      color: var(--t2);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-family: inherit;
      transition: all .15s;
    }
    .pg-btn:hover     { border-color: var(--blue); color: var(--blue); }
    .pg-btn.active    { background: var(--blue); color: #fff; border-color: var(--blue); }
    .pg-btn-wide      { width: auto; padding: 0 10px; }

    /* ═══════════════════════════════════════════
       BRANDS SECTION
    ═══════════════════════════════════════════ */
    .brands-section { margin-top: 28px; }
    .section-title  { font-size: 15px; font-weight: 500; margin-bottom: 12px; }

    .brands-grid {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 8px;
    }
    .brand-card {
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r8);
      padding: 12px 8px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      cursor: pointer;
      transition: border-color .15s;
    }
    .brand-card:hover { border-color: var(--blue); }
    .brand-logo {
      width: 38px; height: 38px;
      border-radius: 50%;
      background: var(--bg1);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-weight: 500;
      color: var(--t2);
    }
    .brand-name { font-size: 11px; color: var(--t2); }

    /* ═══════════════════════════════════════════
       FOOTER
    ═══════════════════════════════════════════ */
    .footer {
      background: var(--bg0);
      border-top: 0.5px solid var(--bd);
      padding: 24px 20px;
      text-align: center;
      font-size: 12px;
      color: var(--t3);
      margin-top: 40px;
    }
    .footer a { color: var(--blue); text-decoration: none; }
    .footer a:hover { text-decoration: underline; }
    .user-menu {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 10px 4px 4px;
  border-radius: 20px;
  cursor: pointer;
  transition: background .15s;
}
.user-menu:hover { background: var(--bg1); }

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
  border: 0.5px solid var(--bd);
}

.user-avatar-initial {
  background: var(--blue);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 500;
  border: none;
}

.user-name {
  font-size: 13px;
  font-weight: 500;
}
.dropdown {
  position: absolute;
  right: 20px;
  top: 48px;
  background: #fff;
  border: 0.5px solid var(--bd);
  border-radius: 8px;
  padding: 6px 0;
  min-width: 160px;
  z-index: 200;
}
.dropdown-item {
  display: block;
  padding: 8px 14px;
  font-size: 13px;
  color: var(--t1);
  text-decoration: none;
  transition: background .15s;
}
.dropdown-item:hover {
  background: var(--bg1);
}

  @media (max-width: 600px) {
    #logo-id{
      display: none;
    }

    /* Cache la navbar search et les liens */
    .nav-search { display: none; }
    .nav-fav { display: none; }
    .nav { padding: 0 16px; justify-content: space-between; }

    /* Hero — fond blanc comme mobile.de */
    .hero { background: #fff; padding: 20px 16px 0; text-align: left; }
    .hero-title { color: #1a1a18; font-size: 24px; font-weight: 500; margin-bottom: 16px; }
    .hero-sub { display: none; }

    /* Grande barre de recherche */
    .search-wrap { 
      flex-direction: column; max-width: 100%; 
      border: 0.5px solid var(--blue-dk); border-radius: var(--r10);
      
      }
    .search-box { border-radius: 8px; padding: 0; background: transparent; }
    .search-tabs { display: none; }
    .sf-grid-row2 > div:last-child { display: none; } /* cache le bouton */

    /* Barre de recherche principale */
    .nav-search {
      display: none;
      
    }
      .hero-search {
      display: flex;
      width: 100%;
      max-width: 100%;
      margin-bottom: 14px;
      position: relative;
    }
    .hero-search input {
      width: 100%;
      height: 48px;
      border: 1.5px solid rgba(0,0,0,.2);
      border-radius: 10px;
      padding: 0 12px 0 40px;
      font-size: 15px;
      outline: none;
      font-family: inherit;
    }
    .hero-search svg {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #888;
      pointer-events: none;
    }
    /* Icônes véhicules en ligne style mobile.de */
    .vtype-sidebar {
      flex-direction: row;
      width: 100%;
      border-radius: 0;
      padding: 8px 0 0;
      background: var(--blue-dk);
      border-radius: var(--r10) var(--r10) 0 0;
      border-top: 0.5px solid rgba(0,0,0,.1);
      gap: 0;
    }
    .vtype-icon-btn {
      flex: 1;
      height: 56px;
      border-radius: 0;
      background: transparent;
      color: rgba(255,255,255,.5);
      border-bottom: 2.5px solid transparent;
      flex-direction: column;
      gap: 3px;
    }
    .vtype-icon-btn.active {
      color: #fff;
      background: rgba(255,255,255,.15);
      border-bottom-color: #185FA5;
    }
    .vtype-icon-btn.active::before { display: none; }
    .vtype-label { font-size: 10px; }
    .vtype-svg { width: 22px; height: 22px; }

    /* Filtres */
    .search-box { padding: 14px 16px; }
    .sf-grid-row1 { grid-template-columns: 1fr 1fr; }
    .sf-grid-row2 { grid-template-columns: 1fr 1fr; }
    .sf-elec-row { margin-bottom: 10px; }

    /* Bouton recherche pleine largeur */
    .btn-search {
      width: 100%;
      height: 48px;
      font-size: 15px;
      border-radius: 10px;
      margin-top: 6px;
    }
    .sf-grid-row2 { grid-template-columns: 1fr 1fr; }
    #col-paiement { display: none; }

    /* Layout body */
    .body-wrap {
      grid-template-columns: 1fr;
      padding: 10px 10px 32px;
    }
    .sidebar { display: none; }
    .qf-bar { display: none; }

    /* Cards */
    .lcard { flex-direction: column; }
    .lcard-img { width: 100%; height: 190px; }
    .sell-banner { flex-direction: column; text-align: center; }
    .results-head { flex-direction: column; gap: 8px; }
  }
  </style>
</head>
<body>

  <!-- ══════════════════════════════════════════
       NAVBAR
  ══════════════════════════════════════════ -->
  <nav class="nav">
    <a class="logo" href="#">
      <img src="images/logo.png" alt="AUTOMARKET" style="height:34px;width:auto;display:block;" >
      <img src="images/id.png" alt="" style="height:34px;width:auto;display:block;" id="logo-id">
    </a>

    <div class="nav-search">
   

      <svg class="nav-search-icon" width="14" height="14" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
      </svg>
      <input type="text" placeholder="Marque, modèle, wilaya…">
    </div>
    

    <div class="nav-links">
       <div class="nav-fav" title="Notifications">
    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" 
         fill="currentColor" viewBox="0 0 24 24">
      <path d="M19 12.59V10c0-3.22-2.18-5.93-5.14-6.74C13.57 2.52 12.85 2 12 2s-1.56.52-1.86 1.26C7.18 4.08 5 6.79 5 10v2.59L3.29 14.3a1 1 0 0 0-.29.71v2c0 .55.45 1 1 1h16c.55 0 1-.45 1-1v-2c0-.27-.11-.52-.29-.71zM19 16H5v-.59l1.71-1.71a1 1 0 0 0 .29-.71v-3c0-2.76 2.24-5 5-5s5 2.24 5 5v3c0 .27.11.52.29.71L19 15.41zm-4.18 4H9.18c.41 1.17 1.51 2 2.82 2s2.41-.83 2.82-2"/>
    </svg>
  </div>

     <?php if (isset($_SESSION['idUtilisateur'])): ?>
  <a class="nav-fav" href="favoris.php" title="Mes favoris">
    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"  
    fill="currentColor" viewBox="0 0 24 24">
      <path d="M11.29 20.66c.2.2.45.29.71.29s.51-.1.71-.29l7.5-7.5c2.35-2.35 2.35-6.05 0-8.41-2.3-2.28-5.85-2.35-8.21-.2-2.36-2.15-5.91-2.09-8.21.2-2.35 2.36-2.35 6.06 0 8.41zM5.21 6.16C6 5.38 7 4.99 8.01 4.99s2.01.39 2.79 1.17l.5.5c.39.39 1.02.39 1.41 0l.5-.5c1.56-1.56 4.02-1.56 5.59 0 1.56 1.57 1.56 4.02 0 5.58l-6.79 6.79-6.79-6.79a3.91 3.91 0 0 1 0-5.58Z"></path>
    </svg>
  </a>
<?php endif; ?>
      
</svg>
        <!--<div class="nav-badge"></div>-->
      </div>

      
<?php if (isset($_SESSION['idUtilisateur'])): ?>
  <div class="user-menu">
    
    
      <?php if (isset($_SESSION['idUtilisateur'])): ?>
  <div class="user-menu" onclick="toggleMenu()">
    <?php if (!empty($_SESSION['photo'])): ?>
  <img src="<?= htmlspecialchars($_SESSION['photo']) ?>" 
       class="user-avatar" 
       alt=""
       referrerpolicy="no-referrer"
       onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
  <div class="user-avatar user-avatar-initial" style="display:none">
    <?= strtoupper(substr($_SESSION['prenom'] ?? 'U', 0, 1)) ?>
  </div>
<?php else: ?>
  <div class="user-avatar user-avatar-initial">
    <?= strtoupper(substr($_SESSION['prenom'] ?? 'U', 0, 1)) ?>
  </div>
<?php endif; ?>
    <span class="user-name"><?= htmlspecialchars($_SESSION['prenom']) ?></span>
  </div>

  <!-- Menu dropdown -->
  <div id="user-dropdown" class="dropdown" style="display:none">
    <a href="monprofil.php" class="dropdown-item">Mon profil</a>
    <a href="mes_annonces.php" class="dropdown-item">Mes annonces</a>
    <a href="favoris.php" class="dropdown-item">Mes favoris</a>
    <hr style="border:none;border-top:0.5px solid var(--bd);margin:4px 0">
    <a href="deconnexion.php" class="dropdown-item" style="color:var(--red)">Se déconnecter</a>
  </div>
<?php endif; ?>
<?php else: ?>
  <button class="nav-btn btn-fill" onclick="location.href='inscription.php'">Connexion</button>
<?php endif; ?>    </div>
  </nav>

  <!-- ══════════════════════════════════════════
       HERO + SEARCH BOX style mobile.de
  ══════════════════════════════════════════ -->
  <section class="hero">
    <h1 class="hero-title">Trouvez votre prochain véhicule en Algérie</h1>
    <p class="hero-sub" id="hero-sub">Marketplace automobile algérienne</p>

  <!-- Barre de recherche principale -->
  <div class="hero-search">
    <svg class="nav-search-icon" width="16" height="16" viewBox="0 0 24 24"
        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
      <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
    </svg>
    <input type="text" placeholder="Marque, modèle, wilaya…">
  </div>

  
    <div class="search-wrap">

      <!-- Sidebar icônes type véhicule -->
      <div class="vtype-sidebar">
        <button class="vtype-icon-btn active" id="vt-voiture" onclick="setVType('voiture')" title="Voiture">
          <svg class="vtype-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="m20.77 9.16-1.37-4.1a2.99 2.99 0 0 0-2.85-2.05H7.44a3 3 0 0 0-2.85 2.05l-1.37 4.1c-.72.3-1.23 1.02-1.23 1.84v5c0 .74.41 1.38 1 1.72V20c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-2h12v2c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-2.28a2 2 0 0 0 1-1.72v-5c0-.83-.51-1.54-1.23-1.84ZM7.44 5h9.12a1 1 0 0 1 .95.68L18.62 9H5.39L6.5 5.68A1 1 0 0 1 7.45 5ZM4 16v-5h16v5z"/>
            <path d="M6.5 12a1.5 1.5 0 1 0 0 3 1.5 1.5 0 1 0 0-3m11 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 1 0 0-3"/>
          </svg>
          <span class="vtype-label">Voiture</span>
        </button>
        <button class="vtype-icon-btn" id="vt-moto" onclick="setVType('moto')" title="Moto">
          <svg class="vtype-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M20.26 14.47s-.06-.04-.1-.05c-.5-.27-1.07-.42-1.66-.42h-.06l-2.19-5.01h1.26c.28 0 .5-.22.5-.5v-1c0-.28-.22-.5-.5-.5h-2.13l-.09-.2a3.01 3.01 0 0 0-2.75-1.8h-1.53v2h1.53c.4 0 .76.24.92.6l1.05 2.4h-3.93c-.29 0-.56.12-.75.33L7.44 13l-2.72-2.72a1 1 0 0 0-.71-.29H1.84v2h1.75L5.6 14s-.06-.01-.1-.01c-1.11 0-2.13.51-2.79 1.38-.3.39-.53.87-.65 1.43-.04.22-.07.45-.07.68 0 1.93 1.57 3.5 3.5 3.5s3.5-1.57 3.5-3.5v-.1l1.3 1.3c.19.19.44.29.71.29h2c.25 0 .5-.1.68-.27L15 17.47c0 1.93 1.57 3.49 3.5 3.49s3.5-1.57 3.5-3.5c0-1.24-.67-2.4-1.74-3.03ZM5.5 19a1.498 1.498 0 0 1-1.47-1.79c.05-.23.14-.45.27-.61a1.506 1.506 0 0 1 1.94-.41l.06.03c.35.23.59.58.67.95.02.1.03.21.03.32 0 .83-.67 1.5-1.5 1.5Zm7.11-2h-1.19l-2.57-2.57L11.02 12h4.36l.76 1.73L12.62 17Zm5.89 2a1.498 1.498 0 0 1-1.2-2.4 1.506 1.506 0 0 1 1.94-.41 1.53 1.53 0 0 1 .77 1.31c0 .83-.67 1.5-1.5 1.5Z"/>
          </svg>
          <span class="vtype-label">Moto</span>
        </button>
        <button class="vtype-icon-btn" id="vt-camion" onclick="setVType('camion')" title="Camion">
          <svg class="vtype-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M19.1 7.8c-.38-.5-.97-.8-1.6-.8H15V6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2 0 1.65 1.35 3 3 3s3-1.35 3-3h4c0 1.65 1.35 3 3 3s3-1.35 3-3c1.1 0 2-.9 2-2v-3.67c0-.43-.14-.86-.4-1.2zM17.5 9l1.5 2h-4V9zM7 19a1.003 1.003 0 0 1-.87-1.5c.37-.63 1.36-.63 1.73 0 .09.15.13.32.13.49 0 .55-.45 1-1 1Zm2.23-3s-.05-.05-.08-.07c-.06-.06-.12-.11-.17-.16-.12-.11-.25-.21-.38-.29a3 3 0 0 0-.67-.32c-.07-.02-.14-.05-.21-.07Q7.375 15 7 15c-.375 0-.49.04-.72.09-.07.02-.14.05-.21.07-.16.05-.31.11-.45.19-.07.04-.15.08-.22.13-.13.09-.26.18-.38.29-.06.05-.12.1-.18.16-.02.03-.05.04-.08.07h-.77V6h9v10H9.22ZM17 19a1.003 1.003 0 0 1-.87-1.5c.37-.63 1.36-.63 1.73 0 .09.15.13.32.13.49 0 .55-.45 1-1 1Zm3-3h-.77s-.05-.05-.08-.07c-.06-.06-.12-.11-.17-.16-.12-.11-.25-.21-.38-.29a3 3 0 0 0-.67-.32c-.07-.02-.14-.05-.21-.07Q17.375 15 17 15c-.375 0-.47.04-.7.09-.06.01-.12.03-.18.05-.18.06-.36.13-.52.22l-.12.06c-.17.1-.33.21-.48.35v-2.76h5v3Z"/>
          </svg>
          <span class="vtype-label">Camion</span>
        </button>
      </div>

      <!-- Panneau de recherche blanc -->
      <div class="search-box">

        <div class="search-tabs">
          <div class="search-tab active" id="st-buy"  onclick="setSTab('buy')">Acheter</div>
          <div class="search-tab"        id="st-rent" onclick="setSTab('rent')">Louer</div>
        </div>

        <!-- Champs row 1 : Marque / Modèle / Année depuis / Km jusqu'à -->
        <div class="sf-grid sf-grid-row1" id="sf-row1">
          <div>
            <div class="sf-label" id="lbl-marque">Marque</div>
            <select class="sf-select" id="sel-marque" onchange="updateModels()">
              <option value="">Quelconque</option>
            </select>
          </div>
          <div>
            <div class="sf-label" id="lbl-modele">Modèle</div>
            <select class="sf-select" id="sel-modele">
              <option value="">Quelconque</option>
            </select>
          </div>
          <div id="col-annee">
            <div class="sf-label">Année depuis</div>
            <select class="sf-select" id="sel-annee">
              <option value="">Quelconque</option>
              <option>2024</option><option>2023</option><option>2022</option>
              <option>2021</option><option>2020</option><option>2019</option>
              <option>2018</option><option>≤ 2017</option>
            </select>
          </div>
          <div id="col-km">
            <div class="sf-label" id="lbl-km">Kilomètres jusqu'à</div>
            <select class="sf-select" id="sel-km">
              <option value="">Quelconque</option>
              <option>10 000 km</option><option>30 000 km</option>
              <option>50 000 km</option><option>80 000 km</option>
              <option>100 000 km</option><option>150 000 km</option>
              <option>200 000 km</option>
            </select>
          </div>
        </div>

        <!-- Champs row 2 : Mode paiement / Prix / Lieu / Bouton -->
        <div class="sf-grid sf-grid-row2" id="sf-row2">
          <div id="col-paiement">
            <div class="sf-label">Mode</div>
            <div class="sf-toggle">
              <button class="sf-toggle-btn active" id="pay-achat" onclick="setPayMode('achat')">Acheter</button>
              <div class="sf-toggle-sep"></div>
              <button class="sf-toggle-btn" id="pay-credit" onclick="setPayMode('credit')">Crédit</button>
            </div>
          </div>
          <div>
            <div class="sf-label" id="lbl-prix">Prix jusqu'à</div>
            <select class="sf-select" id="sel-prix">
              <option value="">Quelconque</option>
              <option>500 000 DA</option><option>1 000 000 DA</option>
              <option>2 000 000 DA</option><option>3 000 000 DA</option>
              <option>5 000 000 DA</option><option>8 000 000 DA</option>
              <option>10 000 000 DA</option>
            </select>
          </div>
          <div>
            <div class="sf-label">Wilaya</div>
            <div class="sf-loc-wrap">
              <input class="sf-input" type="text" placeholder="Quelconque" id="inp-wilaya" list="wilayas-list">
              <svg class="sf-loc-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
              <datalist id="wilayas-list">
                <option value="01">Adrar</option>v>
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
              </datalist>
            </div>
          </div>
          <div style="display:flex;align-items:flex-end">
            <button class="btn-search" id="btn-search">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
              </svg>
              <span id="btn-search-count">Rechercher</span>
            </button>
          </div>
        </div>

        <!-- Footer row -->
        <div class="sf-footer-row">
          <div class="sf-elec-row" id="sf-elec-row">
            <div class="sf-chk" id="chk-elec" onclick="toggleElec()"></div>
            <span class="sf-elec-label" onclick="toggleElec()">Uniquement électrique</span>
            <div class="sf-elec-badge">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="#fff"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
            </div>
          </div>
          <div style="flex:1"></div>
          <span class="sf-footer-link" onclick="resetSearch()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
            Réinitialiser
          </span>
          <span class="sf-footer-link">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="10" y1="18" x2="14" y2="18"/></svg>
            Filtres avancés
          </span>
        </div>

      </div><!-- end search-box -->
    </div><!-- end search-wrap -->
  </section>

  

  <!-- ══════════════════════════════════════════
       BODY : SIDEBAR + MAIN
  ══════════════════════════════════════════ -->
  <div class="body-wrap">

    <!-- ── SIDEBAR ── -->
    <aside class="sidebar">

      <!-- Filtre Carburant —  -->
      <div class="filter-card">
        <div class="filter-head" onclick="toggleFilter(this)">
          Carburant
          <span class="filter-arrow open">▼</span>
        </div>
        <div class="filter-body">
          <div class="filter-option" onclick="toggleFChk(this)">
            <div class="fchk"></div>Diesel<span class="fcount"></span>
          </div>
          <div class="filter-option" onclick="toggleFChk(this)">
            <div class="fchk"></div>Essence<span class="fcount"></span>
          </div>
          <div class="filter-option" onclick="toggleFChk(this)">
            <div class="fchk"></div>GPL<span class="fcount"></span>
          </div>
          <div class="filter-option" onclick="toggleFChk(this)">
            <div class="fchk"></div>Hybride<span class="fcount"></span>
          </div>
          <div class="filter-option" onclick="toggleFChk(this)">
            <div class="fchk"></div>Électrique<span class="fcount"></span>
          </div>
        </div>
      </div>

      <!-- Filtre Transmission -->
      <div class="filter-card">
        <div class="filter-head" onclick="toggleFilter(this)">
          Transmission
          <span class="filter-arrow open">▼</span>
        </div>
        <div class="filter-body">
          <div class="filter-option" onclick="toggleFChk(this)">
            <div class="fchk"></div>Manuelle<span class="fcount"></span>
          </div>
          <div class="filter-option" onclick="toggleFChk(this)">
            <div class="fchk"></div>Automatique<span class="fcount"></span>
          </div>
          <div class="filter-option" onclick="toggleFChk(this)">
            <div class="fchk"></div>Semi-automatique<span class="fcount"></span>
          </div>
        </div>
      </div>

      <!-- Filtre Prix -->
      <div class="filter-card">
        <div class="filter-head" onclick="toggleFilter(this)">
          Prix (DA)
          <span class="filter-arrow open">▼</span>
        </div>
        <div class="filter-body">
          <div class="range-row">
            <input class="range-in" type="number" name="prix_min" placeholder="Min"><br><br>
            <input class="range-in" type="number" name="prix_max" placeholder="Max">
          </div>
        </div>
      </div>

      <!-- Filtre Kilométrage -->
      <div class="filter-card">
        <div class="filter-head" onclick="toggleFilter(this)">
          Kilométrage
          <span class="filter-arrow open">▼</span>
        </div>
        <div class="filter-body">
          <div class="filter-option" onclick="toggleFChk(this)">
            <div class="fchk"></div>Moins de 30 000 km<span class="fcount"></span>
          </div>
          <div class="filter-option" onclick="toggleFChk(this)">
            <div class="fchk"></div>30 000 – 80 000 km<span class="fcount"></span>
          </div>
          <div class="filter-option" onclick="toggleFChk(this)">
            <div class="fchk"></div>80 000 – 150 000 km<span class="fcount"></span>
          </div>
          <div class="filter-option" onclick="toggleFChk(this)">
            <div class="fchk"></div>Plus de 150 000 km<span class="fcount"></span>
          </div>
        </div>
      </div>

      <!-- Filtre État -->
      <div class="filter-card">
        <div class="filter-head" onclick="toggleFilter(this)">
          État du véhicule
          <span class="filter-arrow open">▼</span>
        </div>
        <div class="filter-body">
          <div class="filter-option" onclick="toggleFChk(this)">
            <div class="fchk"></div>Occasion<span class="fcount"></span>
          </div>
          <div class="filter-option" onclick="toggleFChk(this)">
            <div class="fchk"></div>Neuf<span class="fcount"></span>
          </div>
          <div class="filter-option" onclick="toggleFChk(this)">
            <div class="fchk"></div>Accidenté / Pièces<span class="fcount"></span>
          </div>
        </div>
      </div>

      <div class="sidebar-reset" onclick="resetFilters()">Réinitialiser les filtres</div>

      <div class="ad-card">
        <div class="ad-card-title">Vendez votre véhicule</div>
        <div class="ad-card-sub">Publiez une annonce gratuite en moins de 5 minutes</div>
        <button class="nav-btn btn-fill" style="width:100%" onclick="location.href='publier.php'">Publier une annonce</button>
      </div>

    </aside>

    <!-- ── MAIN ── -->
    <main>

      <!-- Banner CTA vendeur -->
      <div class="sell-banner">
        <div class="sell-banner-text">
          <div class="sell-banner-title">Vous souhaitez vendre votre véhicule ?</div>
          <div class="sell-banner-sub">Annonce gratuite · Visible par des milliers d'acheteurs · Réponse rapide</div>
        </div>
        <button class="btn-white" onclick="location.href='publier.php'">Déposer une annonce</button>
      </div>

      <!-- En-tête résultats -->
      <div class="results-head">
        <div class="results-count">
          <span id="total-count">
            <?php
            $sql_count = "SELECT COUNT(*) AS total FROM Annonce WHERE statutAnnonce='active'";
            $res_count = mysqli_query($conn, $sql_count);
            $total = mysqli_fetch_assoc($res_count)['total'] ?? 0;
            echo number_format($total, 0, ',', ' ');
            ?>
          </span> annonces trouvées
        </div>
        <div class="sort-row">
          <span class="sort-label">Trier :</span>
          <select class="sort-sel" name="tri" onchange="this.form && this.form.submit()">
            <option value="date_desc">Les plus récentes</option>
            <option value="prix_asc">Prix croissant</option>
            <option value="prix_desc">Prix décroissant</option>
            <option value="km_asc">Kilométrage ↑</option>
            <option value="annee_desc">Année décroissante</option>
          </select>
          <div class="view-btns">
            <button class="view-btn active" title="Vue liste" onclick="setView(this)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
              </svg>
            </button>
            <button class="view-btn" title="Vue grille" onclick="setView(this)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"/>
                <rect x="14" y="3" width="7" height="7"/>
                <rect x="3" y="14" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Zone d'annonces — sera remplie par PHP/AJAX -->
      <div class="listings" id="listings-container">
      <?php
        $sql_annonces = "
            SELECT 
                a.idAnnonce, a.titre, a.prix, a.localisation, 
                a.datePublication, a.vendeurVerif,
                v.annee, v.kilometrage, v.carburant, v.transmission,
                u.nom AS vendeur_nom, u.prenom AS vendeur_prenom
            FROM Annonce a, Vehicule v, Utilisateur u
            WHERE a.idVehicule = v.idVehicule
            AND a.idVendeur = u.idUtilisateur
            AND a.statutAnnonce = 'active'
            ORDER BY a.datePublication DESC
            LIMIT 20
        ";
        $res_annonces = mysqli_query($conn, $sql_annonces);

        if (!$res_annonces || mysqli_num_rows($res_annonces) == 0) {
            echo '<div style="padding:40px;text-align:center;color:#888">Aucune annonce.</div>';
        } else {
            /* Mettre à jour le compteur */
            $total = mysqli_num_rows($res_annonces);
            echo "<script>document.getElementById('total-count').textContent = '$total';</script>";

            while ($a = mysqli_fetch_assoc($res_annonces)) {
                $titre = htmlspecialchars($a['titre']);
                $prix  = number_format($a['prix'], 0, ',', ' ');
                $loc   = htmlspecialchars($a['localisation']);
                $nom   = htmlspecialchars($a['vendeur_nom'] . ' ' . $a['vendeur_prenom']);
                $km    = number_format($a['kilometrage'], 0, ',', ' ');
                $annee = $a['annee'];
                $carbu = $a['carburant'];
                $trans = $a['transmission'];
                $pro   = $a['vendeurVerif'] == 1;

                $date = new DateTime($a['datePublication']);
                $diff = (new DateTime())->diff($date)->days;
                if ($diff == 0)     $dl = "Aujourd'hui";
                elseif ($diff == 1) $dl = "Hier";
                else                $dl = "Il y a $diff jours";

                echo "
                <div class='lcard' onclick=\"location.href='fiche_annonce.php?id={$a['idAnnonce']}'\">
                  <div class='lcard-img'>
                    <div class='lcard-img-ph'>
                      <svg width='44' height='44' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='0.8'>
                        <rect x='1' y='6' width='22' height='13' rx='3'/>
                        <circle cx='7' cy='16' r='1.5'/><circle cx='17' cy='16' r='1.5'/>
                      </svg>
                    </div>
                  </div>
                  <div class='lcard-body'>
                    <div class='lcard-top'>
                      <div class='lcard-title'>$titre</div>
                      <div class='lcard-price'>$prix DA</div>
                    </div>
                    <div class='lcard-specs'>
                      <span class='lspec'>$annee</span>
                      <span class='lspec-dot'></span>
                      <span class='lspec'>$km km</span>
                      <span class='lspec-dot'></span>
                      <span class='lspec'>$carbu</span>
                      <span class='lspec-dot'></span>
                      <span class='lspec'>$trans</span>
                      <span class='lspec-dot'></span>
                      <span class='lspec'>$loc</span>
                    </div>
                    <div class='lcard-foot'>
                      <div class='lseller'>
                        $nom
                        " . ($pro ? "<span class='seller-badge'>Pro vérifié</span>" : "") . "
                      </div>
                      <div class='ldate'>$dl</div>
                    </div>
                  </div>
                </div>";
            }
        }
        ?>
      </div>

      <!-- Pagination — générée par PHP -->
      <div class="pagination" id="pagination">
        <!-- <?php for($i=1; $i<=$total_pages; $i++): ?>
        <button class="pg-btn <?= $i==$page?'active':'' ?>" onclick="goToPage(<?=$i?>)"><?=$i?></button>
        <?php endfor; ?> -->
      </div>

    </main>
  </div><!-- end .body-wrap -->

  <!-- FOOTER -->
  <footer class="footer">
    © 2025 AUTOMARKET — Marketplace automobile algérienne &nbsp;·&nbsp;
    <a href="#">Aide</a> &nbsp;·&nbsp;
    <a href="#">Confidentialité</a> &nbsp;·&nbsp;
    <a href="#">Conditions d'utilisation</a>
  </footer>

  <script>
    /* ══ DONNÉES PAR TYPE DE VÉHICULE ══════════════ */
    const DATA = {
      voiture: {
        marques: {
          Toyota:['Corolla','Yaris','Camry','RAV4','Hilux','Land Cruiser'],
          Hyundai:['Tucson','Elantra','Santa Fe','i10','i30','Creta'],
          Renault:['Clio','Megane','Duster','Kadjar','Logan','Talisman'],
          Peugeot:['208','308','3008','5008','Partner','Rifter'],
          Volkswagen:['Golf','Polo','Passat','Tiguan','T-Roc','Caddy'],
          BMW:['Série 1','Série 3','Série 5','X1','X3','X5'],
          Mercedes:['Classe A','Classe C','Classe E','GLC','GLE','Vito'],
          Kia:['Sportage','Cerato','Picanto','Sorento','Stonic','Carnival'],
          Dacia:['Duster','Logan','Sandero','Dokker','Lodgy','Spring'],
          Ford:['Focus','Fiesta','Kuga','Puma','Ranger','Transit'],
          Suzuki:['Swift','Vitara','Jimny','S-Cross','Ignis','Baleno'],
          Mitsubishi:['Outlander','ASX','Eclipse Cross','L200','Pajero','Space Star'],
        },
        labels: { marque:'Marque', modele:'Modèle', km:'Kilomètres jusqu\'à', prix:'Prix jusqu\'à' },
        count: '<?= $counts_type["voiture"] ?> annonces',
        subtitle: 'Marketplace voitures — Algérie',
        showElec: true, showPaiement: true, showAnnee: true,
        kmOptions: ['10 000 km','30 000 km','50 000 km','80 000 km','100 000 km','150 000 km','200 000 km'],
      },
      moto: {
        marques: {
          Honda:['CB500','CBR600','CB125R','Africa Twin','Forza 300','PCX 125'],
          Yamaha:['MT-07','MT-09','YZF-R3','Ténéré 700','TMAX','NMAX'],
          Kawasaki:['Z650','Ninja 400','Z900','Versys 650','W800','Eliminator'],
          Suzuki:['GSX-S750','V-Strom 650','Bandit 650','Burgman','GSX-R600','Intruder'],
          KTM:['Duke 390','Duke 790','Adventure 390','SMC-R','RC 390','Enduro'],
          Bajaj:['Pulsar 150','Pulsar 200','Avenger','Dominar 400','NS 200','RS 200'],
          Sym:['Wolf 150','Jet 14','GTS 300','Cruisym 300','Citycom','Mio'],
          Lifan:['LF150','LF200','KP 150','LF250','SR 200','X-Pect'],
        },
        labels: { marque:'Marque moto', modele:'Modèle', km:'Kilomètres jusqu\'à', prix:'Prix jusqu\'à' },
        count: '<?= $counts_type["moto"] ?> annonces',
        subtitle: 'Marketplace motos — Algérie',
        showElec: false, showPaiement: false, showAnnee: true,
        kmOptions: ['5 000 km','10 000 km','20 000 km','40 000 km','60 000 km','80 000 km'],
      },
      camion: {
        marques: {
          Mercedes:['Actros','Axor','Atego','Sprinter','Vito','Citan'],
          Volvo:['FH','FM','FMX','FE','FL','VNL'],
          MAN:['TGX','TGS','TGM','TGL','TGE'],
          Scania:['R Series','S Series','P Series','G Series','XT'],
          Iveco:['Stralis','Hi-Way','Daily','Eurocargo','Trakker','S-Way'],
          Renault:['T Series','C Series','K Series','D Series','Master','Trafic'],
          Isuzu:['NLR','NMR','NPR','NQR','FVR','GXR'],
          Hyundai:['HD72','HD78','HD120','HD160','HD250','Xcient'],
        },
        labels: { marque:'Fabricant', modele:'Modèle / Série', km:'Km jusqu\'à', prix:'Prix jusqu\'à' },
        count: '<?= $counts_type["camion"] ?> annonces',
        subtitle: 'Marketplace poids-lourds — Algérie',
        showElec: false, showPaiement: true, showAnnee: true,
        kmOptions: ['50 000 km','100 000 km','200 000 km','300 000 km','500 000 km','700 000 km'],
      },
    };

    let currentVType = 'voiture';

    function setVType(type) {
      currentVType = type;
      ['voiture','moto','camion'].forEach(t => {
        const btn = document.getElementById('vt-' + t);
        btn.classList.toggle('active', t === type);
        if (t === type) {
          btn.classList.remove('animating');
          void btn.offsetWidth; /* force reflow pour relancer l'animation */
          btn.classList.add('animating');
          btn.addEventListener('animationend', () => btn.classList.remove('animating'), { once: true });
        }
      });
      const d = DATA[type];
      /* subtitle */
      document.getElementById('hero-sub').textContent = d.subtitle;
      /* labels */
      document.getElementById('lbl-marque').textContent = d.labels.marque;
      document.getElementById('lbl-modele').textContent = d.labels.modele;
      document.getElementById('lbl-km').textContent     = d.labels.km;
      document.getElementById('lbl-prix').textContent   = d.labels.prix;
      /* count button */
      document.getElementById('btn-search-count').textContent = d.count;
      /* km options */
      const selKm = document.getElementById('sel-km');
      selKm.innerHTML = '<option value="">Quelconque</option>';
      d.kmOptions.forEach(k => {
        const o = document.createElement('option'); o.value = k; o.textContent = k;
        selKm.appendChild(o);
      });
      /* show/hide cols */
      document.getElementById('col-paiement').style.display = d.showPaiement ? '' : 'none';
      document.getElementById('sf-elec-row').style.display  = d.showElec     ? '' : 'none';
      /* rebuild marques */
      buildMarques();
    }

    function buildMarques() {
      const sel = document.getElementById('sel-marque');
      sel.innerHTML = '<option value="">Quelconque</option>';
      Object.keys(DATA[currentVType].marques).forEach(m => {
        const o = document.createElement('option'); o.value = m; o.textContent = m;
        sel.appendChild(o);
      });
      document.getElementById('sel-modele').innerHTML = '<option value="">Quelconque</option>';
    }

    function updateModels() {
      const marque = document.getElementById('sel-marque').value;
      const sel    = document.getElementById('sel-modele');
      sel.innerHTML = '<option value="">Quelconque</option>';
      const list = DATA[currentVType].marques[marque];
      if (list) list.forEach(m => {
        const o = document.createElement('option'); o.value = m; o.textContent = m;
        sel.appendChild(o);
      });
    }

    /* ── Search tabs ── */
    function setSTab(t) {
      ['buy','rent'].forEach(id => {
        document.getElementById('st-' + id).classList.toggle('active', id === t);
      });
    }

    /* ── Mode paiement toggle ── */
    function setPayMode(m) {
      document.getElementById('pay-achat').classList.toggle('active',  m==='achat');
      document.getElementById('pay-credit').classList.toggle('active', m==='credit');
    }

    /* ── Checkbox électrique ── */
    function toggleElec() {
      document.getElementById('chk-elec').classList.toggle('on');
    }

    /* ── Reset search ── */
    function resetSearch() {
      document.getElementById('sel-marque').value = '';
      document.getElementById('sel-modele').innerHTML = '<option value="">Quelconque</option>';
      document.getElementById('sel-annee').value  = '';
      document.getElementById('sel-km').value     = '';
      document.getElementById('sel-prix').value   = '';
      document.getElementById('inp-wilaya').value = '';
      document.getElementById('chk-elec').classList.remove('on');
      setPayMode('achat');
    }

    /* init */
    buildMarques();

    /* ── Quick filter pills ────────────────────────── */
    function togglePill(el) {
      document.querySelectorAll('.qf-pill').forEach(p => p.classList.remove('active'));
      el.classList.add('active');
    }

    /* ── Sidebar filter accordions ─────────────────── */
    function toggleFilter(head) {
      const body  = head.nextElementSibling;
      const arrow = head.querySelector('.filter-arrow');
      const isOpen = body.style.display !== 'none' && body.style.display !== '';
      body.style.display  = isOpen ? 'none' : 'block';
      arrow.classList.toggle('open', !isOpen);
    }

    /* ── Sidebar checkboxes ────────────────────────── */
    function toggleFChk(row) {
      row.querySelector('.fchk').classList.toggle('on');
    }

    /* ── Reset filters ─────────────────────────────── */
    function resetFilters() {
      document.querySelectorAll('.fchk.on').forEach(c => c.classList.remove('on'));
    }

    /* ── Favourite toggle ──────────────────────────── */
    function toggleFav(id) {
      const btn    = document.getElementById(id);
      const isFaved = btn.classList.toggle('faved');
      const svg    = btn.querySelector('svg');
      svg.setAttribute('fill',   isFaved ? '#E24B4A' : 'none');
      svg.setAttribute('stroke', isFaved ? '#E24B4A' : '#888');
    }

    /* ── View toggle (list / grid) ─────────────────── */
    function setView(btn) {
      btn.closest('.view-btns').querySelectorAll('.view-btn')
         .forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    }

    /* ── Pagination ────────────────────────────────── */
    document.querySelectorAll('.pg-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.pg-btn').forEach(b => b.classList.remove('active'));
        if (this.textContent !== '…' && this.textContent !== '›') {
          this.classList.add('active');
        }
      });
    });
    function toggleMenu() {
  const d = document.getElementById('user-dropdown');
  d.style.display = d.style.display === 'none' ? 'block' : 'none';
}

/* Fermer si on clique ailleurs */
document.addEventListener('click', function(e) {
  if (!e.target.closest('.user-menu') && !e.target.closest('#user-dropdown')) {
    const d = document.getElementById('user-dropdown');
    if (d) d.style.display = 'none';
  }
});
  </script>
</body>
</html>

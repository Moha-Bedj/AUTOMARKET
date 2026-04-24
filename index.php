<?php
session_start();
require_once 'connexion.php';
$publie = isset($_GET['publie']) && $_GET['publie'] == '1';
$counts_type = ['voiture' => 0, 'moto' => 0, 'camion' => 0];

$r = mysqli_query($conn, "
    SELECT v.typeVehicule, COUNT(*) as nb 
    FROM Annonce a, Vehicule v 
    WHERE a.idVehicule = v.idVehicule 
    AND a.statutAnnonce = 'active' 
    GROUP BY v.typeVehicule
");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $type = strtolower($row['typeVehicule']);
        if (isset($counts_type[$type])) {
            $counts_type[$type] = $row['nb'];
        }
    }
}

$total_annonces = 0;
$rT = mysqli_query($conn, "SELECT COUNT(*) AS n FROM Annonce WHERE statutAnnonce='active'");
if ($rT) $total_annonces = (int)mysqli_fetch_assoc($rT)['n'];
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
      --deal-orange: #FFA366;
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

    /* NAVBAR */
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
      display: flex;
      align-items: center;
      gap: 5px;
      text-decoration: none;
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
      outline: none;
      font-family: inherit;
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
    }
    .btn-fill {
      background: var(--blue);
      color: #fff;
      border: none;
    }
    .btn-fill:hover { background: var(--blue-dk); }
    .nav-fav {
      cursor: pointer;
      color: var(--t2);
      padding: 6px;
      display: flex;
      align-items: center;
      border-radius: var(--r6);
      text-decoration: none;
    }
    .nav-fav:hover { color: var(--blue); }

    /* HERO */
    .hero-banner {
      position: relative;
      width: 100%;
      height: 360px;
      background: url('images/hero.png') center/cover no-repeat;
    }

    .ai-search-overlap {
      position: absolute;
      left: 50%;
      bottom: -42px;
      transform: translateX(-50%);
      width: 100%;
      max-width: 1100px;
      padding: 30px 16px;
      z-index: 10;
      height: 104px;
    }
    .ai-search {
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r12);
      padding: 18px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.18);
    }
    .ai-search-box {
      display: flex;
      align-items: center;
      background: var(--bg1);
      border: 0.5px solid var(--bd2);
      border-radius: var(--r10);
      padding: 6px 6px 6px 16px;
      gap: 10px;
      transition: all .15s;
    }
    .ai-search-box:focus-within {
      border-color: var(--blue);
      background: var(--bg0);
      box-shadow: 0 0 0 3px rgba(24,95,165,.1);
    }
    .ai-search-icon {
      color: var(--blue);
      flex-shrink: 0;
    }
    .ai-search-box input {
      flex: 1;
      background: transparent;
      border: none;
      outline: none;
      color: var(--t1);
      font-size: 15px;
      font-family: inherit;
      height: 44px;
    }
    .ai-search-box input::placeholder { color: var(--t3); }
    .ai-search-btn {
      width: 42px;
      height: 42px;
      border-radius: var(--r8);
      background: var(--blue);
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      transition: background .15s;
      flex-shrink: 0;
    }
    .ai-search-btn:hover { background: var(--blue-dk); }

    /* NOUVELLE SECTION FILTRES */
    .filters-section {
      max-width: 1100px;
      margin: 0 auto;
      padding: 70px 16px 20px;
    }

    .search-wrap {
      display: flex;
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r12);
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }

    .vtype-sidebar {
      background: var(--bg1);
      padding: 16px 10px;
      display: flex;
      flex-direction: column;
      gap: 6px;
      border-right: 0.5px solid var(--bd);
      flex-shrink: 0;
    }

    .vtype-icon-btn {
      width: 56px;
      height: 56px;
      border-radius: var(--r10);
      background: transparent;
      border: none;
      color: var(--t3);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all .15s;
    }

    .vtype-icon-btn:hover {
      background: rgba(0,0,0,.04);
      color: var(--t1);
    }

    .vtype-icon-btn.active {
      background: var(--blue-bg);
      color: var(--blue);
    }

    .vtype-icon-btn svg {
      width: 28px;
      height: 28px;
    }

    .search-box {
      flex: 1;
      padding: 22px 22px 18px;
    }

    .sf-grid {
      display: grid;
      gap: 14px;
      margin-bottom: 14px;
    }

    .sf-grid-row1 {
      grid-template-columns: 1fr 1fr 1fr 1fr;
    }

    .sf-grid-row2 {
      grid-template-columns: 1fr 1fr 2fr;
      align-items: end;
    }

    .sf-label {
      font-size: 13px;
      color: var(--t1);
      font-weight: 500;
      margin-bottom: 6px;
      display: block;
    }

    .sf-select,
    .sf-input {
      width: 100%;
      height: 44px;
      background: var(--bg0);
      border: 0.5px solid var(--bd2);
      border-radius: var(--r8);
      color: var(--t1);
      padding: 0 32px 0 14px;
      font-size: 14px;
      font-family: inherit;
      outline: none;
      appearance: none;
      -webkit-appearance: none;
      cursor: pointer;
      background-image: url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23888780' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      transition: border-color .15s;
    }

    .sf-input {
      background-image: none;
      padding-right: 36px;
    }

    .sf-select:focus,
    .sf-input:focus {
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(24,95,165,.1);
    }

    .sf-select option {
      background: #fff;
      color: var(--t1);
    }

    .sf-loc-wrap {
      position: relative;
    }

    .sf-loc-icon {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--t3);
      pointer-events: none;
    }

    .btn-search {
      height: 44px;
      background: var(--blue);
      color: #fff;
      border: none;
      border-radius: var(--r8);
      padding: 0 20px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      font-family: inherit;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: background .15s;
      width: 100%;
    }

    .btn-search:hover {
      background: var(--blue-dk);
    }

    .sf-footer-row {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 20px;
      margin-top: 14px;
    }

    .sf-elec-row {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-right: auto;
    }

    .sf-chk {
      width: 16px;
      height: 16px;
      border: 0.5px solid var(--bd2);
      border-radius: 3px;
      background: var(--bg0);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .sf-chk.on {
      background: var(--blue);
      border-color: var(--blue);
    }

    .sf-chk.on::after {
      content: '';
      width: 4px;
      height: 7px;
      border: 1.5px solid #fff;
      border-top: none;
      border-left: none;
      transform: rotate(45deg) translateY(-1px);
    }

    .sf-elec-label {
      font-size: 13px;
      color: var(--t2);
      cursor: pointer;
    }

    .sf-footer-link {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 13px;
      color: var(--t2);
      cursor: pointer;
      background: none;
      border: none;
      font-family: inherit;
      transition: color .15s;
    }

    .sf-footer-link:hover {
      color: var(--blue);
    }

    /* TOP DEALS */
    .top-deals {
      background: var(--bg1);
      padding: 30px 0 30px;
      overflow: hidden;
    }
    .deals-wrap {
      max-width: 1100px;
      margin: 0 auto;
      padding: 0 16px;
      position: relative;
    }
    .deals-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 18px;
    }
    .deals-title {
      font-size: 22px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .deals-badge {
      background: var(--deal-orange);
      color: #fff;
      font-size: 14px;
      font-weight: 700;
      padding: 4px 12px;
      border-radius: 20px;
      letter-spacing: 0.5px;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }
    .deals-badge::before {
      content: '';
      width: 6px; height: 6px;
      border-radius: 50%;
      background: #fff;
    }
    .deals-view-all {
      font-size: 14px;
      color: var(--t1);
      text-decoration: underline;
      font-weight: 500;
      cursor: pointer;
    }
    .deals-view-all:hover { color: var(--blue); }

    .deals-carousel-wrap {
      position: relative;
      padding: 8px 0;
    }
    .deals-scroll {
      display: flex;
      gap: 14px;
      overflow-x: auto;
      scroll-behavior: smooth;
      scrollbar-width: none;
      padding: 4px 4px 16px 4px;
      scroll-snap-type: x mandatory;
    }
    .deals-scroll::-webkit-scrollbar { display: none; }

    .deals-nav {
      position: absolute;
      top: 45%;
      transform: translateY(-50%);
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--t1);
      z-index: 5;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      transition: all .15s;
    }
    .deals-nav:hover {
      background: var(--blue);
      color: #fff;
      border-color: var(--blue);
      transform: translateY(-50%) scale(1.05);
    }
    .deals-nav-left  { left: 6px; }
    .deals-nav-right { right: 6px; }

    .deal-card {
      flex: 0 0 260px;
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: 14px;
      overflow: hidden;
      cursor: pointer;
      scroll-snap-align: start;
      transition: transform .2s, box-shadow .2s, border-color .2s;
    }
    .deal-card:hover {
      transform: translateY(-3px);
      border-color: var(--blue);
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }
    .deal-img {
      position: relative;
      width: 100%;
      height: 170px;
      background: linear-gradient(135deg, #f5f4f0, #eceae4);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--t3);
      overflow: hidden;
    }
    .deal-fav {
      position: absolute;
      top: 10px; right: 10px;
      width: 36px; height: 36px;
      border-radius: 50%;
      background: rgba(255,255,255,0.95);
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--t2);
      transition: all .15s;
      z-index: 2;
    }
    .deal-fav:hover {
      background: var(--red);
      color: #fff;
      transform: scale(1.08);
    }
    .deal-fav.faved { color: var(--red); }
    .deal-fav.faved svg { fill: var(--red); stroke: var(--red); }
    .deal-placeholder { opacity: 0.4; }

    .deal-body { padding: 14px 14px 16px; }
    .deal-title {
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 8px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .deal-price {
      display: flex;
      align-items: baseline;
      gap: 4px;
      margin-bottom: 4px;
    }
    .deal-price-val { font-size: 19px; font-weight: 700; }
    .deal-price-unit { font-size: 12px; color: var(--t2); font-weight: 500; }
    .deal-year { font-size: 11px; color: var(--t2); margin-bottom: 10px; }
    .deal-badge-row { margin-bottom: 10px; }
    .deal-badge-tag {
      background: var(--deal-orange);
      color: #fff;
      font-size: 11px;
      font-weight: 700;
      padding: 3px 10px;
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }
    .deal-badge-tag::before {
      content: '';
      width: 5px; height: 5px;
      border-radius: 50%;
      background: #fff;
    }
    .deal-chips {
      display: flex;
      gap: 6px;
      margin-bottom: 6px;
      flex-wrap: wrap;
    }
    .deal-chip {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 11px;
      background: var(--bg1);
      border: 0.5px solid var(--bd);
      padding: 4px 9px;
      border-radius: 8px;
      white-space: nowrap;
    }
    .deal-chip-full {
      display: inline-flex;
      margin-bottom: 10px;
    }
    .deal-location {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 12px;
      color: var(--t2);
      padding-top: 10px;
      border-top: 0.5px solid var(--bd);
    }

    /* BODY */
    .body-wrap {
      max-width: 1100px;
      margin: 0 auto;
      padding: 10px 16px 48px;
    }
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
      font-family: inherit;
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
    .results-count { font-size: 14px; }
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
      outline: none;
      appearance: none;
      font-family: inherit;
      cursor: pointer;
      background-image: url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23888' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
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
    }
    .view-btn + .view-btn { border-left: 0.5px solid var(--bd); }
    .view-btn.active { background: var(--bg1); color: var(--t1); }

    /* LISTINGS */
    .listings { display: flex; flex-direction: column; gap: 10px; }
    .lcard {
      background: var(--bg0);
      border: 0.5px solid var(--bd);
      border-radius: var(--r10);
      display: flex;
      overflow: hidden;
      cursor: pointer;
      transition: border-color .15s;
    }
    .lcard:hover { border-color: var(--blue); }
    .lcard-img {
      width: 210px;
      flex-shrink: 0;
      background: var(--bg1);
      height: 152px;
    }
    .lcard-img-ph {
      width: 100%; height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--t3);
    }
    .lcard-body {
      flex: 1;
      padding: 14px 16px;
      display: flex;
      flex-direction: column;
    }
    .lcard-top {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 8px;
      margin-bottom: 7px;
    }
    .lcard-title { font-size: 15px; font-weight: 500; }
    .lcard-price {
      font-size: 18px;
      font-weight: 500;
      color: var(--blue);
      white-space: nowrap;
    }
    .lcard-specs {
      display: flex;
      gap: 10px;
      margin-bottom: 8px;
      flex-wrap: wrap;
    }
    .lspec { font-size: 12px; color: var(--t2); }
    .lspec-dot {
      width: 3px; height: 3px;
      border-radius: 50%;
      background: var(--t3);
      align-self: center;
    }
    .lcard-foot {
      display: flex;
      justify-content: space-between;
      margin-top: auto;
      padding-top: 10px;
      border-top: 0.5px solid var(--bd);
    }
    .lseller { font-size: 12px; color: var(--t2); }
    .seller-badge {
      font-size: 10px;
      background: var(--blue-bg);
      color: var(--blue);
      padding: 1px 6px;
      border-radius: 10px;
      margin-left: 5px;
    }
    .ldate { font-size: 11px; color: var(--t3); }

    /* FOOTER */
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

    /* USER MENU */
    .user-menu {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 4px 10px 4px 4px;
      border-radius: 20px;
      cursor: pointer;
      position: relative;
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
    .user-name { font-size: 13px; font-weight: 500; }
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
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .dropdown-item {
      display: block;
      padding: 8px 14px;
      font-size: 13px;
      color: var(--t1);
      text-decoration: none;
    }
    .dropdown-item:hover { background: var(--bg1); }

    .publish-success {
      background: var(--green);
      color: #fff;
      padding: 12px 16px;
      animation: slideDown .4s ease;
    }
    .publish-success-inner {
      max-width: 1100px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-100%); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
      .hero-banner { height: 240px; }
      .ai-search-overlap { bottom: -36px; }
      .ai-search { padding: 12px; }
      .ai-search-box input { font-size: 14px; }

      .filters-section { padding-top: 56px; }
      .search-wrap { flex-direction: column; }
      .vtype-sidebar {
        flex-direction: row;
        border-right: none;
        border-bottom: 0.5px solid var(--bd);
        padding: 10px;
        justify-content: center;
      }
      .vtype-icon-btn { width: 48px; height: 48px; }
      .search-box { padding: 16px; }
      .sf-grid-row1,
      .sf-grid-row2 { grid-template-columns: 1fr 1fr; gap: 10px; }
      .sf-footer-row { flex-wrap: wrap; gap: 12px; }
      .sf-elec-row { margin-right: 0; width: 100%; }

      .top-deals { padding: 20px 0; }
      .deals-title { font-size: 18px; }
      .deal-card { flex: 0 0 240px; }
      .deals-nav { display: none; }

      .body-wrap { padding: 10px 10px 32px; }
      .lcard { flex-direction: column; }
      .lcard-img { width: 100%; height: 190px; }
      .sell-banner { flex-direction: column; text-align: center; }
      .results-head { flex-direction: column; gap: 8px; }
      .nav-search, .nav-fav, #logo-id { display: none; }
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="nav">
    <a class="logo" href="#">
      <img src="images/logo.png" alt="AUTOMARKET" style="height:34px;">
      <img src="images/id.png" alt="" style="height:34px;" id="logo-id">
    </a>

    <div class="nav-search">
      <svg class="nav-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
      </svg>
      <input type="text" placeholder="Marque, Modèle…">
    </div>

    <div class="nav-links">
      <div class="nav-fav" title="Notifications">
        <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24">
          <path d="M19 12.59V10c0-3.22-2.18-5.93-5.14-6.74C13.57 2.52 12.85 2 12 2s-1.56.52-1.86 1.26C7.18 4.08 5 6.79 5 10v2.59L3.29 14.3a1 1 0 0 0-.29.71v2c0 .55.45 1 1 1h16c.55 0 1-.45 1-1v-2c0-.27-.11-.52-.29-.71zM19 16H5v-.59l1.71-1.71a1 1 0 0 0 .29-.71v-3c0-2.76 2.24-5 5-5s5 2.24 5 5v3c0 .27.11.52.29.71L19 15.41zm-4.18 4H9.18c.41 1.17 1.51 2 2.82 2s2.41-.83 2.82-2"/>
        </svg>
      </div>

      <?php if (isset($_SESSION['idUtilisateur'])): ?>
        <a class="nav-fav" href="favoris.php">
          <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24">
            <path d="M11.29 20.66c.2.2.45.29.71.29s.51-.1.71-.29l7.5-7.5c2.35-2.35 2.35-6.05 0-8.41-2.3-2.28-5.85-2.35-8.21-.2-2.36-2.15-5.91-2.09-8.21.2-2.35 2.36-2.35 6.06 0 8.41z"/>
          </svg>
        </a>

        <div class="user-menu" onclick="toggleMenu()">
          <?php if (!empty($_SESSION['photo'])): ?>
            <img src="<?= htmlspecialchars($_SESSION['photo']) ?>" class="user-avatar" alt=""
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

        <div id="user-dropdown" class="dropdown" style="display:none">
          <a href="monprofil.php" class="dropdown-item">Mon profil</a>
          <a href="mesannonces.php" class="dropdown-item">Mes annonces</a>
          <a href="favoris.php" class="dropdown-item">Mes favoris</a>
          <hr style="border:none;border-top:0.5px solid var(--bd);margin:4px 0">
          <a href="deconnexion.php" class="dropdown-item" style="color:var(--red)">Se déconnecter</a>
        </div>
      <?php else: ?>
        <button class="nav-btn btn-fill" onclick="location.href='inscription.php'">Connexion</button>
      <?php endif; ?>
    </div>
  </nav>

  <?php if ($publie): ?>
    <div class="publish-success">
      <div class="publish-success-inner">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24">
          <path d="M20 6L9 17l-5-5"/>
        </svg>
        <div>
          <strong>Annonce publiée avec succès !</strong>
          <div style="font-size:12px;opacity:.9">Visible par tous les acheteurs.</div>
        </div>
        <button onclick="this.parentElement.parentElement.remove()" style="background:none;border:none;color:#fff;cursor:pointer;margin-left:auto;font-size:20px">×</button>
      </div>
    </div>
  <?php endif; ?>

  <!-- HERO -->
  <div class="hero-banner">
    <div class="ai-search-overlap">
      <div class="ai-search">
        <div class="ai-search-box">
          <svg class="ai-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 0l1.5 4.5L18 6l-4.5 1.5L12 12l-1.5-4.5L6 6l4.5-1.5L12 0zm6 9l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3zM6 14l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/>
          </svg>
          <input type="text" placeholder="Peugeot 208, BMW X3, Toyota Corolla…" id="ai-search-input">
          <button class="ai-search-btn" onclick="doAISearch()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
              <path d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- NOUVELLE SECTION FILTRES -->
  <section class="filters-section">
    <div class="search-wrap">

      <div class="vtype-sidebar">
        <button class="vtype-icon-btn active" id="vt-voiture" onclick="setVType('voiture')" title="Voiture">
          <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="m20.77 9.16-1.37-4.1a2.99 2.99 0 0 0-2.85-2.05H7.44a3 3 0 0 0-2.85 2.05l-1.37 4.1c-.72.3-1.23 1.02-1.23 1.84v5c0 .74.41 1.38 1 1.72V20c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-2h12v2c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-2.28a2 2 0 0 0 1-1.72v-5c0-.83-.51-1.54-1.23-1.84ZM7.44 5h9.12a1 1 0 0 1 .95.68L18.62 9H5.39L6.5 5.68A1 1 0 0 1 7.45 5ZM4 16v-5h16v5z"/>
            <path d="M6.5 12a1.5 1.5 0 1 0 0 3 1.5 1.5 0 1 0 0-3m11 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 1 0 0-3"/>
          </svg>
        </button>

        <button class="vtype-icon-btn" id="vt-moto" onclick="setVType('moto')" title="Moto">
          <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M20.26 14.47s-.06-.04-.1-.05c-.5-.27-1.07-.42-1.66-.42h-.06l-2.19-5.01h1.26c.28 0 .5-.22.5-.5v-1c0-.28-.22-.5-.5-.5h-2.13l-.09-.2a3.01 3.01 0 0 0-2.75-1.8h-1.53v2h1.53c.4 0 .76.24.92.6l1.05 2.4h-3.93c-.29 0-.56.12-.75.33L7.44 13l-2.72-2.72a1 1 0 0 0-.71-.29H1.84v2h1.75L5.6 14c-1.11 0-2.13.51-2.79 1.38-.3.39-.53.87-.65 1.43-.04.22-.07.45-.07.68 0 1.93 1.57 3.5 3.5 3.5s3.5-1.57 3.5-3.5v-.1l1.3 1.3c.19.19.44.29.71.29h2c.25 0 .5-.1.68-.27L15 17.47c0 1.93 1.57 3.49 3.5 3.49s3.5-1.57 3.5-3.5c0-1.24-.67-2.4-1.74-3.03Z"/>
          </svg>
        </button>

        <button class="vtype-icon-btn" id="vt-camion" onclick="setVType('camion')" title="Camion">
          <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M19.1 7.8c-.38-.5-.97-.8-1.6-.8H15V6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2 0 1.65 1.35 3 3 3s3-1.35 3-3h4c0 1.65 1.35 3 3 3s3-1.35 3-3c1.1 0 2-.9 2-2v-3.67c0-.43-.14-.86-.4-1.2zM17.5 9l1.5 2h-4V9z"/>
          </svg>
        </button>
      </div>

      <div class="search-box">
        <div class="sf-grid sf-grid-row1">
          <div>
            <label class="sf-label">Marque</label>
            <select class="sf-select" id="sel-marque" onchange="updateModels()">
              <option value="">Quelconque</option>
            </select>
          </div>

          <div>
            <label class="sf-label">Modèle</label>
            <select class="sf-select" id="sel-modele">
              <option value="">Quelconque</option>
            </select>
          </div>

          <div>
            <label class="sf-label">Année depuis</label>
            <select class="sf-select" id="sel-annee">
              <option value="">Quelconque</option>
              <option>2024</option><option>2023</option><option>2022</option>
              <option>2021</option><option>2020</option><option>2019</option>
              <option>2018</option><option>≤ 2017</option>
            </select>
          </div>

          <div>
            <label class="sf-label">Kilomètres jusqu'à</label>
            <select class="sf-select" id="sel-km">
              <option value="">Quelconque</option>
            </select>
          </div>
        </div>

        <div class="sf-grid sf-grid-row2">
          <div>
            <label class="sf-label">Prix jusqu'à</label>
            <select class="sf-select" id="sel-prix">
              <option value="">Quelconque</option>
              <option>500 000 DA</option><option>1 000 000 DA</option>
              <option>2 000 000 DA</option><option>3 000 000 DA</option>
              <option>5 000 000 DA</option><option>8 000 000 DA</option>
            </select>
          </div>

          <div>
            <label class="sf-label">Wilaya</label>
            <div class="sf-loc-wrap">
              <input class="sf-input" type="text" id="inp-wilaya" placeholder="Quelconque" list="wilayas-list">
              <svg class="sf-loc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                <circle cx="12" cy="9" r="2.5"/>
              </svg>
              <datalist id="wilayas-list">
                <option value="Adrar"><option value="Alger"><option value="Annaba">
                <option value="Batna"><option value="Béchar"><option value="Béjaïa">
                <option value="Biskra"><option value="Blida"><option value="Bouira">
                <option value="Boumerdès"><option value="Chlef"><option value="Constantine">
                <option value="Djelfa"><option value="El Oued"><option value="Ghardaïa">
                <option value="Jijel"><option value="Khenchela"><option value="Laghouat">
                <option value="Mascara"><option value="Médéa"><option value="Mostaganem">
                <option value="Oran"><option value="Ouargla"><option value="Relizane">
                <option value="Saïda"><option value="Sétif"><option value="Skikda">
                <option value="Tamanrasset"><option value="Tébessa"><option value="Tiaret">
                <option value="Tipaza"><option value="Tizi Ouzou"><option value="Tlemcen">
              </datalist>
            </div>
          </div>

          <div>
            <button class="btn-search" onclick="doSearch()">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
              </svg>
              <span><?= number_format($total_annonces, 0, ',', ' ') ?> offres</span>
            </button>
          </div>
        </div>

        <div class="sf-footer-row">
          <div class="sf-elec-row" id="sf-elec-row">
            <div class="sf-chk" id="chk-elec" onclick="toggleElec()"></div>
            <span class="sf-elec-label" onclick="toggleElec()">Uniquement électrique</span>
          </div>

          <button class="sf-footer-link" onclick="resetSearch()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>
            </svg>
            Réinitialiser
          </button>

          <button class="sf-footer-link" type="button">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <line x1="4" y1="6" x2="20" y2="6"/>
              <line x1="8" y1="12" x2="16" y2="12"/>
              <line x1="10" y1="18" x2="14" y2="18"/>
            </svg>
            Filtres avancés
          </button>
        </div>
      </div>
    </div>
  </section>

  <!-- TOP DEALS -->
  <section class="top-deals">
    <div class="deals-wrap">
      <div class="deals-head">
        <h2 class="deals-title">
          Top <span class="deals-badge">DEALS</span> pour vous
        </h2>
        <a href="#" class="deals-view-all">Tout afficher →</a>
      </div>

      <div class="deals-carousel-wrap">
        <button class="deals-nav deals-nav-left" onclick="scrollDeals(-1)">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <path d="m15 18-6-6 6-6"/>
          </svg>
        </button>

        <div class="deals-scroll" id="deals-scroll">
          <?php
          $sql_deals = "
              SELECT a.idAnnonce, a.titre, a.prix, a.localisation,
                     v.annee, v.kilometrage, v.carburant, v.transmission, v.puissance
              FROM Annonce a, Vehicule v
              WHERE a.idVehicule = v.idVehicule
              AND a.statutAnnonce = 'active'
              ORDER BY a.datePublication DESC
              LIMIT 10
          ";
          $res_deals = mysqli_query($conn, $sql_deals);

          if ($res_deals && mysqli_num_rows($res_deals) > 0) {
              while ($d = mysqli_fetch_assoc($res_deals)) {
                  $titre = htmlspecialchars($d['titre']);
                  $prix  = number_format($d['prix'], 0, ',', ' ');
                  $loc   = htmlspecialchars($d['localisation']);
                  $km    = number_format($d['kilometrage'], 0, ',', ' ');
                  $annee = $d['annee'];
                  $carbu = htmlspecialchars($d['carburant']);
                  $trans = htmlspecialchars($d['transmission']);
                  $puiss = $d['puissance'];
                  $idAnn = $d['idAnnonce'];
          ?>
          <div class="deal-card" onclick="location.href='fiche_annonce.php?id=<?= $idAnn ?>'">
            <div class="deal-img">
              <button class="deal-fav" onclick="event.stopPropagation();toggleFav(<?= $idAnn ?>, this)">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M11.29 20.66c.2.2.45.29.71.29s.51-.1.71-.29l7.5-7.5c2.35-2.35 2.35-6.05 0-8.41-2.3-2.28-5.85-2.35-8.21-.2-2.36-2.15-5.91-2.09-8.21.2-2.35 2.36-2.35 6.06 0 8.41z"/>
                </svg>
              </button>
              <svg class="deal-placeholder" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.8">
                <rect x="1" y="6" width="22" height="13" rx="3"/>
                <circle cx="7" cy="16" r="1.5"/>
                <circle cx="17" cy="16" r="1.5"/>
              </svg>
            </div>
            <div class="deal-body">
              <div class="deal-title"><?= $titre ?></div>
              <div class="deal-price">
                <span class="deal-price-val"><?= $prix ?></span>
                <span class="deal-price-unit">DA</span>
              </div>
              <div class="deal-year"><?= $annee ?> · <?= $km ?> km</div>
              <div class="deal-badge-row">
                <span class="deal-badge-tag">DEAL</span>
              </div>
              <div class="deal-chips">
                <div class="deal-chip"><?= $carbu ?></div>
                <?php if ($puiss): ?><div class="deal-chip"><?= $puiss ?> CV</div><?php endif; ?>
              </div>
              <div class="deal-chip deal-chip-full"><?= $trans ?></div>
              <div class="deal-location">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                  <circle cx="12" cy="9" r="2.5"/>
                </svg>
                <?= $loc ?>
              </div>
            </div>
          </div>
          <?php }} else { ?>
            <div style="padding:40px;text-align:center;color:var(--t3);width:100%">Aucun deal disponible</div>
          <?php } ?>
        </div>

        <button class="deals-nav deals-nav-right" onclick="scrollDeals(1)">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <path d="m9 18 6-6-6-6"/>
          </svg>
        </button>
      </div>
    </div>
  </section>

  <!-- BODY -->
  <div class="body-wrap">
    <main>
      <div class="sell-banner">
        <div class="sell-banner-text">
          <div class="sell-banner-title">Vous souhaitez vendre votre véhicule ?</div>
          <div class="sell-banner-sub">Annonce gratuite · Visible par des milliers d'acheteurs</div>
        </div>
        <button class="btn-white" onclick="location.href='publier.php'">Déposer une annonce</button>
      </div>

      <div class="results-head">
        <div class="results-count">
          <span><?= number_format($total_annonces, 0, ',', ' ') ?></span> annonces trouvées
        </div>
        <div class="sort-row">
          <span class="sort-label">Trier :</span>
          <select class="sort-sel">
            <option>Les plus récentes</option>
            <option>Prix croissant</option>
            <option>Prix décroissant</option>
          </select>
          <div class="view-btns">
            <button class="view-btn active" onclick="setView(this)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
              </svg>
            </button>
            <button class="view-btn" onclick="setView(this)">
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

      <div class="listings">
        <?php
        $sql_annonces = "
            SELECT a.idAnnonce, a.titre, a.prix, a.localisation, a.datePublication, a.vendeurVerif,
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
                $dl = $diff == 0 ? "Aujourd'hui" : ($diff == 1 ? "Hier" : "Il y a $diff jours");

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
                      <span class='lspec'>$annee</span><span class='lspec-dot'></span>
                      <span class='lspec'>$km km</span><span class='lspec-dot'></span>
                      <span class='lspec'>$carbu</span><span class='lspec-dot'></span>
                      <span class='lspec'>$trans</span><span class='lspec-dot'></span>
                      <span class='lspec'>$loc</span>
                    </div>
                    <div class='lcard-foot'>
                      <div class='lseller'>$nom" . ($pro ? "<span class='seller-badge'>Pro vérifié</span>" : "") . "</div>
                      <div class='ldate'>$dl</div>
                    </div>
                  </div>
                </div>";
            }
        }
        ?>
      </div>
    </main>
  </div>

  <footer class="footer">
    © 2026 AUTOMARKET — Marketplace automobile algérienne &nbsp;·&nbsp;
    <a href="#">Aide</a> &nbsp;·&nbsp;
    <a href="#">Confidentialité</a> &nbsp;·&nbsp;
    <a href="#">Conditions</a>
  </footer>

  <script>
    const DATA = {
      voiture: {
        marques: {
          Toyota:['Corolla','Yaris','Camry','RAV4','Hilux'],
          Hyundai:['Tucson','Elantra','Santa Fe','i10','i30'],
          Renault:['Clio','Megane','Duster','Kadjar','Logan'],
          Peugeot:['208','308','3008','5008','Partner'],
          Volkswagen:['Golf','Polo','Passat','Tiguan','T-Roc'],
          BMW:['Série 1','Série 3','Série 5','X1','X3','X5'],
          Mercedes:['Classe A','Classe C','Classe E','GLC','GLE'],
          Kia:['Sportage','Cerato','Picanto','Sorento'],
          Dacia:['Duster','Logan','Sandero','Dokker'],
          Ford:['Focus','Fiesta','Kuga','Puma','Ranger'],
        },
        kmOptions: ['10 000 km','30 000 km','50 000 km','80 000 km','100 000 km','150 000 km'],
        showElec: true,
      },
      moto: {
        marques: {
          Honda:['CB500','CBR600','Africa Twin'],
          Yamaha:['MT-07','MT-09','YZF-R3','TMAX'],
          Kawasaki:['Z650','Ninja 400','Z900'],
          KTM:['Duke 390','Duke 790','Adventure 390'],
        },
        kmOptions: ['5 000 km','10 000 km','20 000 km','40 000 km','60 000 km'],
        showElec: false,
      },
      camion: {
        marques: {
          Mercedes:['Actros','Axor','Atego','Sprinter'],
          Volvo:['FH','FM','FMX','FE'],
          MAN:['TGX','TGS','TGM'],
          Scania:['R Series','S Series','P Series'],
        },
        kmOptions: ['50 000 km','100 000 km','200 000 km','300 000 km','500 000 km'],
        showElec: false,
      },
    };

    let currentVType = 'voiture';

    function setVType(type) {
      currentVType = type;
      ['voiture','moto','camion'].forEach(t => {
        document.getElementById('vt-' + t).classList.toggle('active', t === type);
      });
      const d = DATA[type];
      const selKm = document.getElementById('sel-km');
      selKm.innerHTML = '<option value="">Quelconque</option>';
      d.kmOptions.forEach(k => {
        const o = document.createElement('option');
        o.value = k; o.textContent = k;
        selKm.appendChild(o);
      });
      document.getElementById('sf-elec-row').style.display = d.showElec ? 'flex' : 'none';
      buildMarques();
    }

    function buildMarques() {
      const sel = document.getElementById('sel-marque');
      sel.innerHTML = '<option value="">Quelconque</option>';
      Object.keys(DATA[currentVType].marques).forEach(m => {
        const o = document.createElement('option');
        o.value = m; o.textContent = m;
        sel.appendChild(o);
      });
      document.getElementById('sel-modele').innerHTML = '<option value="">Quelconque</option>';
    }

    function updateModels() {
      const marque = document.getElementById('sel-marque').value;
      const sel = document.getElementById('sel-modele');
      sel.innerHTML = '<option value="">Quelconque</option>';
      const list = DATA[currentVType].marques[marque];
      if (list) list.forEach(m => {
        const o = document.createElement('option');
        o.value = m; o.textContent = m;
        sel.appendChild(o);
      });
    }

    function toggleElec() {
      document.getElementById('chk-elec').classList.toggle('on');
    }

    function resetSearch() {
      document.getElementById('sel-marque').value = '';
      document.getElementById('sel-modele').innerHTML = '<option value="">Quelconque</option>';
      document.getElementById('sel-annee').value = '';
      document.getElementById('sel-km').value = '';
      document.getElementById('sel-prix').value = '';
      document.getElementById('inp-wilaya').value = '';
      document.getElementById('chk-elec').classList.remove('on');
    }

    function doSearch() { alert('Lancer la recherche avec filtres'); }
    function doAISearch() {
      const q = document.getElementById('ai-search-input').value.trim();
      if (q) alert('Recherche IA : ' + q);
    }

    buildMarques();
    setVType('voiture');

    function scrollDeals(direction) {
      const scroll = document.getElementById('deals-scroll');
      scroll.scrollBy({ left: direction * 274 * 2, behavior: 'smooth' });
    }

    function toggleFav(id, btn) {
      const fd = new FormData();
      fd.append('action', 'toggle');
      fd.append('idAnnonce', id);
      fetch('toggle_favori.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
          if (json.needLogin) { location.href = 'inscription.php'; return; }
          btn.classList.toggle('faved', json.favori);
        });
    }

    function setView(btn) {
      btn.closest('.view-btns').querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    }

    function toggleMenu() {
      const d = document.getElementById('user-dropdown');
      d.style.display = d.style.display === 'none' ? 'block' : 'none';
    }
    document.addEventListener('click', function(e) {
      if (!e.target.closest('.user-menu') && !e.target.closest('#user-dropdown')) {
        const d = document.getElementById('user-dropdown');
        if (d) d.style.display = 'none';
      }
    });
  </script>
</body>
</html>
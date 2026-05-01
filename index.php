<?php
session_start();
require_once 'connexion.php';
$publie = isset($_GET['publie']) && $_GET['publie'] == '1';
$counts_type = ['voiture' => 0, 'moto' => 0, 'camion' => 0];
$marquesDB = [];

$sql = "
SELECT 
  ma.idMarque,
  ma.nomMarque,
  mo.idModele,
  mo.nomModele,
  ve.idVersion,
  ve.nomVersion
FROM marque ma
LEFT JOIN modele mo ON mo.idMarque = ma.idMarque
LEFT JOIN version ve ON ve.idModele = mo.idModele
ORDER BY ma.nomMarque, mo.nomModele, ve.nomVersion
";

$res = mysqli_query($conn, $sql);

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $marque = $row['nomMarque'];
        $modele = $row['nomModele'];
        $version = $row['nomVersion'];

        if (!isset($marquesDB[$marque])) {
            $marquesDB[$marque] = [];
        }

        if ($modele && !isset($marquesDB[$marque][$modele])) {
            $marquesDB[$marque][$modele] = [];
        }

        if ($modele && $version) {
            $marquesDB[$marque][$modele][] = $version;
        }
    }
}

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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AUTOMARKET — Marketplace Automobile Algérienne</title>
  <link rel="icon" href="images/logo.png">
  <style>
    *, *::before, *::after { 
  box-sizing: border-box; 
  margin: 0; 
  padding: 0; 
}

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

.btn-fill {
  background: var(--blue);
  color: #fff;
  border: none;
}

.btn-fill:hover { 
  background: var(--blue-dk); 
}

.nav-fav {
  position: relative;
  cursor: pointer;
  color: var(--t2);
  padding: 6px;
  display: flex;
  align-items: center;
  border-radius: var(--r6);
  transition: color .15s;
  text-decoration: none;
}

.nav-fav:hover { 
  color: var(--blue); 
}

/* HERO */
/* HERO */
.hero {
  background:
    linear-gradient(135deg, rgba(24, 94, 165, 0.26), rgba(12,68,124,0.90)),
    url('images/hero.png') center/cover no-repeat;
  height: 400px;
  padding: 80px 0 0;
  position: relative;
  margin-bottom: 205px;
  text-align: start;
}

.hero-title {
  width: min(1100px, calc(100% - 48px));
  margin: 0 auto 8px auto;
  font-size: 38px;
  font-weight: 500;
  color: #fff;
  letter-spacing: -.3px;
  line-height: 1.15;
}

.hero-sub {
  width: min(1100px, calc(100% - 48px));
  margin: 0 auto;
  font-size: 20px;
  color: rgba(255,255,255,.78);
  line-height: 1.3;
}

/* ═══ AI SEARCH BAR EXACTEMENT STYLE MOBILE.DE ═══ */
.ai-search-overlap {
  position: absolute;
  left: 50%;
  bottom: -135px;
  transform: translateX(-50%);
  width: min(1200px, calc(100% - 48px));
}

.ai-search {
  background: var(--bg0);
  border: 0.5px solid var(--bd);
  border-radius: 18px;
  padding: 28px 34px 30px;
  box-shadow: 0 18px 45px rgba(0,0,0,0.22);
}

.ai-search-title {
  font-size: 28px;
  font-weight: 700;
  color: var(--t1);
  text-align: center;
  margin-bottom: 20px;
  letter-spacing: -0.4px;
}

.ai-search-box {
  height: 58px;
  border: 1px solid var(--bd2);
  border-radius: 14px;
  display: flex;
  align-items: center;
  gap: 0;
  padding: 0;
  background: var(--bg1);
  transition: border-color .15s, box-shadow .15s;
  overflow: hidden;
}

.ai-search-box:hover,
.ai-search-box:focus-within {
  background: var(--bg0);
  border-color: var(--blue);
  box-shadow: 0 0 0 3px rgba(24,95,165,.10);
}

.ai-search-box:hover .ai-search-icon,
.ai-search-box:focus-within .ai-search-icon,
.ai-search-box:hover input,
.ai-search-box:focus-within input {
  background: var(--bg0);
}

.ai-search-icon {
  width: 58px;
  height: 58px;
  padding: 18px;
  color: var(--blue);
  flex-shrink: 0;
  background: var(--bg1);
}

.ai-search-box input {
  flex: 1;
  height: 100%;
  background: var(--bg1);
  border: none;
  outline: none;
  color: var(--t1);
  font-size: 18px;
  font-family: inherit;
  padding: 0 16px;
}

.ai-search-box input::placeholder {
  color: var(--t3);
}

.ai-search-btn {
  width: 48px;
  height: 48px;
  margin-right: 8px;
  border-radius: 12px;
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

.ai-search-btn:hover {
  background: var(--blue-dk);
}

/* TON ANCIEN BLOC FILTRE */
.search-wrap {
  display: flex;
    width: min(1200px, calc(100% - 48px));

  margin: 0 auto;
  align-items: stretch;
}

.search-box {
  flex: 1;
  background: #fff;
  border-radius: 0 12px 0 0;
  padding: 16px 20px 14px;
}

.vtype-sidebar {
  width: 68px;
  background: var(--blue-dk);
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px 0;
  gap: 2px;
  flex-shrink: 0;
  border-radius: 12px 0 0 12px;
   box-shadow: 0 18px 45px rgba(0,0,0,0.22);
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
  left: 0;
  top: 10px;
  bottom: 10px;
  width: 3px;
  background: #fff;
  border-radius: 0 3px 3px 0;
}

.vtype-svg { 
  width: 26px; 
  height: 26px; 
}

.vtype-label { 
  font-size: 9px; 
  line-height: 1; 
}

@keyframes vtypePop {
  0%   { transform: scale(1); }
  40%  { transform: scale(1.28); }
  70%  { transform: scale(0.92); }
  100% { transform: scale(1); }
}

.vtype-icon-btn.animating .vtype-svg {
  animation: vtypePop 0.35s ease forwards;
}

.search-box {
  flex: 1;
  background: #fff;
  border-radius: 0 var(--r10) var(--r10) 0;
  box-shadow: 0 18px 45px rgba(0,0,0,0.22);
  padding: 42px 20px 14px; /* ← plus de padding-top pour éviter que la barre IA chevauche le contenu */
}

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

.search-tab:hover { 
  color: var(--t1); 
}

.sf-grid { 
  display: grid; 
  gap: 10px; 
  margin-bottom: 10px; 
}

.sf-grid-row1 { 
  grid-template-columns: repeat(4, 1fr);
  align-items: start;
}

.sf-grid-row2 { 
  grid-template-columns: auto 1fr 1fr auto; 
  align-items: end; 
}

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
  background-image: url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23888' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 9px center;
  transition: border-color .15s;
}

.sf-select:hover, .sf-input:hover {
  border-color: var(--blue);
  box-shadow: 0 0 0 3px rgba(24,95,165,.1);
}

.sf-input { 
  padding: 0 32px 0 10px; 
  background-image: none; 
}

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

.sf-toggle-sep { 
  width: 0.5px; 
  background: var(--bd); 
}

.sf-loc-wrap { 
  position: relative; 
}

.sf-loc-icon {
  position: absolute; 
  right: 10px; 
  top: 50%; 
  transform: translateY(-50%);
  color: var(--t3); 
  pointer-events: none;
}

.sf-elec-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 4px;
}

.sf-chk {
  width: 15px; 
  height: 15px;
  border: 0.5px solid var(--bd2);
  border-radius: 3px;
  background: var(--bg0);
  cursor: pointer;
  flex-shrink: 0;
  display: flex; 
  align-items: center; 
  justify-content: center;
  transition: all .15s;
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
  display: block;
}

.sf-elec-label { 
  font-size: 13px; 
  color: var(--t2); 
  cursor: pointer; 
}

.sf-elec-badge {
  width: 17px; 
  height: 17px; 
  border-radius: 50%;
  background: #2563eb;
  display: flex; 
  align-items: center; 
  justify-content: center;
}

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

.btn-search:hover { 
  background: var(--blue-dk); 
}

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
  display: flex; 
  align-items: center; 
  gap: 5px;
  transition: color .15s;
}

.sf-footer-link:hover { 
  color: var(--blue); 
}

/* BODY */
.body-wrap {
  max-width: 1100px;
  margin: 0 auto;
  padding: 20px 16px 48px;
}

.hero-search { 
  display: none; 
}

/* SELL BANNER */
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

.sell-banner-text { 
  color: #fff; 
}

.sell-banner-title { 
  font-size: 14px; 
  font-weight: 500; 
  margin-bottom: 2px; 
}

.sell-banner-sub { 
  font-size: 12px; 
  opacity: .8; 
}

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

.btn-white:hover { 
  background: var(--blue-bg); 
}

.results-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
  flex-wrap: wrap;
  gap: 8px;
}

.results-count { 
  font-size: 14px; 
  color: var(--t1); 
}

.results-count span { 
  font-weight: 500; 
}

.sort-row { 
  display: flex; 
  align-items: center; 
  gap: 8px; 
}

.sort-label { 
  font-size: 13px; 
  color: var(--t2); 
}

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

.view-btn + .view-btn { 
  border-left: 0.5px solid var(--bd); 
}

.view-btn.active { 
  background: var(--bg1); 
  color: var(--t1); 
}

/* LISTINGS */
.listings { 
  display: flex; 
  flex-direction: column; 
  gap: 10px; 
}

/* ═══ TOP DEALS HOLDER STYLE MOBILE.DE ═══ */
.top-deals {
  width: min(1200px, calc(100% - 48px));
  margin: 40px auto;
  background: var(--bg0);
  border: 0.5px solid var(--bd);
  border-radius: 18px;
  padding: 28px 32px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.10);
  overflow: hidden;
}

.deals-wrap {
  width: 100%;
  margin: 0 auto;
  padding: 0;
}

.deals-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 22px;
}

.deals-title {
  font-size: 24px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--t1);
}

.deals-badge {
  background: #FFA366;
  color: #fff;
  font-size: 15px;
  font-weight: 800;
  padding: 5px 13px;
  border-radius: 20px;
  letter-spacing: 0.5px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.deals-badge::before {
  content: '';
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #fff;
}

.deals-view-all {
  font-size: 15px;
  color: var(--t1);
  text-decoration: underline;
  font-weight: 600;
  cursor: pointer;
}

.deals-view-all:hover { 
  color: var(--blue); 
}

.deals-carousel-wrap {
  position: relative;
  overflow: visible;
}

.deals-scroll {
  display: flex;
  gap: 24px;
  overflow-x: auto;
  scroll-behavior: smooth;
  scrollbar-width: none;
  padding: 4px 2px;
  scroll-snap-type: x mandatory;
}

.deals-scroll::-webkit-scrollbar { 
  display: none; 
}

.deals-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background: var(--bg0);
  border: 0.5px solid var(--bd);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--t1);
  z-index: 10;
  box-shadow: 0 4px 14px rgba(0,0,0,0.16);
  transition: all .15s;
}

.deals-nav:hover {
  background: var(--blue);
  color: #fff;
  border-color: var(--blue);
}

.deals-nav-left { 
  left: -24px; 
}

.deals-nav-right { 
  right: -24px; 
}
.deals-nav.is-hidden {
  opacity: 0;
  pointer-events: none;
  transform: translateY(-50%) scale(0.85);
}

.deal-card {
  flex: 0 0 270px;
  width: 270px;
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
}

.deal-placeholder { 
  opacity: 0.4; 
}

.deal-fav {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 36px;
  height: 36px;
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

.deal-fav.faved {
  color: var(--red);
}

.deal-fav.faved svg {
  fill: var(--red);
  stroke: var(--red);
}

.deal-body {
  padding: 14px 16px 16px;
}

.deal-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--t1);
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

.deal-price-val {
  font-size: 20px;
  font-weight: 700;
  color: var(--t1);
}

.deal-price-unit {
  font-size: 13px;
  color: var(--t2);
  font-weight: 500;
}

.deal-year {
  font-size: 12px;
  color: var(--t2);
  margin-bottom: 10px;
}

.deal-badge-row {
  margin-bottom: 10px;
}

.deal-badge-tag {
  background: #FFA366;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  letter-spacing: 0.3px;
}

.deal-badge-tag::before {
  content: '';
  width: 5px;
  height: 5px;
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
  color: var(--t1);
  background: var(--bg1);
  border: 0.5px solid var(--bd);
  padding: 5px 10px;
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

/* LISTING CARD */
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



.hidden {
  display: none;
}

.lspec { 
  font-size: 12px; 
  color: var(--t2); 
}

.lspec-dot {
  width: 3px; 
  height: 3px;
  border-radius: 50%;
  background: var(--t3);
  flex-shrink: 0;
}

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

.ldate { 
  font-size: 11px; 
  color: var(--t3); 
}

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

.footer a { 
  color: var(--blue); 
  text-decoration: none; 
}

.footer a:hover { 
  text-decoration: underline; 
}

/* USER MENU */
.user-menu {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 10px 4px 4px;
  border-radius: 20px;
  cursor: pointer;
  position: relative;
  transition: background .15s;
}

.user-menu:hover { 
  background: var(--bg1); 
}

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
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
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
  from { 
    opacity: 0; 
    transform: translateY(-100%); 
  }
  to { 
    opacity: 1; 
    transform: translateY(0); 
  }
}

#ai-search-input::placeholder {
  color: var(--t3);
  opacity: 1;
}

.typing-cursor {
  font-size: 20px;
  color: var(--t3);
  margin-left: -10px;
  margin-right: 8px;
  animation: blinkCursor 0.8s infinite;
  pointer-events: none;
}

@keyframes blinkCursor {
  0%, 45% {
    opacity: 1;
  }
  46%, 100% {
    opacity: 0;
  }
}

.ai-search-box:hover .typing-cursor,
.ai-search-box:focus-within .typing-cursor {
  color: var(--t2);
}

/* RESPONSIVE */
@media (max-width: 900px) {
  .top-deals {
    width: calc(100% - 24px);
    padding: 22px 18px;
    border-radius: 16px;
  }

  .deal-card {
    flex: 0 0 250px;
    width: 250px;
  }

  .deal-img {
    height: 160px;
  }

  .deals-nav {
    display: none;
  }
}

@media (max-width: 600px) {
  #logo-id { 
    display: none; 
  }

  .nav-search { 
    display: none; 
  }

  .nav-fav { 
    display: none; 
  }

  .nav { 
    padding: 0 16px; 
    justify-content: space-between; 
  }

  .hero { 
    background: #fff; 
    padding: 20px 16px 0; 
    text-align: left; 
  }

  .hero-title { 
    color: #1a1a18; 
    font-size: 24px; 
    font-weight: 500; 
    margin-bottom: 16px; 
  }

  .hero-sub { 
    display: none; 
  }

  /* Mobile : AI search normale, pas de chevauchement */
  .ai-search-overlap {
    margin-top: 0;
    margin-bottom: 14px;
    padding: 0;
  }
  .ai-search {
    padding: 10px;
  }
  .ai-search-box input {
    font-size: 14px;
    height: 40px;
  }
  .ai-search-btn {
    width: 38px;
    height: 38px;
  }

  .search-wrap {
    flex-direction: column; 
    max-width: 100%;
    border: 0.5px solid var(--blue-dk); 
    border-radius: var(--r10);
  }

  .search-box { 
    border-radius: 8px; 
    padding: 0; 
    background: transparent; 
  }

  .search-tabs { 
    display: none; 
  }

  .sf-grid-row2 > div:last-child { 
    display: none; 
  }

  .hero-search {
    display: none; /* désactivé car remplacé par ai-search */
  }

  .vtype-sidebar {
    flex-direction: row;
    width: 100%;
    border-radius: var(--r10) var(--r10) 0 0;
    padding: 8px 0 0;
    background: var(--blue-dk);
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

  .vtype-icon-btn.active::before { 
    display: none; 
  }

  .vtype-label { 
    font-size: 10px; 
  }

  .vtype-svg { 
    width: 22px; 
    height: 22px; 
  }

  .search-box { 
    padding: 14px 16px; 
  }

  .sf-grid-row1 { 
    grid-template-columns: 1fr 1fr; 
  }

  .sf-grid-row2 { 
    grid-template-columns: 1fr 1fr; 
  }

  .sf-elec-row { 
    margin-bottom: 10px; 
  }

  .btn-search {
    width: 100%;
    height: 48px;
    font-size: 15px;
    border-radius: 10px;
    margin-top: 6px;
  }

  #col-paiement { 
    display: none; 
  }

  .body-wrap { 
    padding: 10px 10px 32px; 
  }

  .lcard { 
    flex-direction: column; 
  }

  .lcard-img { 
    width: 100%; 
    height: 190px; 
  }

  .sell-banner { 
    flex-direction: column; 
    text-align: center; 
  }

  .results-head { 
    flex-direction: column; 
    gap: 8px; 
  }

  .top-deals {
    width: calc(100% - 20px);
    margin: 24px auto;
    padding: 18px 14px;
  }

  .deals-head {
    margin-bottom: 16px;
  }

  .deals-title {
    font-size: 19px;
  }

  .deals-badge {
    font-size: 12px;
    padding: 4px 10px;
  }

  .deals-view-all {
    font-size: 13px;
  }

  .deals-scroll {
    gap: 14px;
  }

  .deal-card {
    flex: 0 0 245px;
    width: 245px;
  }

  .deal-img {
    height: 155px;
  }
}
  </style>
  
</head>
<body>

  <!-- NAVBAR -->
  <nav class="nav">
    <a class="logo" href="#">
      <img src="images/logo.png" alt="AUTOMARKET" style="height:34px;width:auto;display:block;">
      <img src="images/id.png" alt="" style="height:34px;width:auto;display:block;" id="logo-id">
    </a>

    <div class="nav-links">
      <div class="nav-fav" title="Notifications">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 24 24">
          <path d="M19 12.59V10c0-3.22-2.18-5.93-5.14-6.74C13.57 2.52 12.85 2 12 2s-1.56.52-1.86 1.26C7.18 4.08 5 6.79 5 10v2.59L3.29 14.3a1 1 0 0 0-.29.71v2c0 .55.45 1 1 1h16c.55 0 1-.45 1-1v-2c0-.27-.11-.52-.29-.71zM19 16H5v-.59l1.71-1.71a1 1 0 0 0 .29-.71v-3c0-2.76 2.24-5 5-5s5 2.24 5 5v3c0 .27.11.52.29.71L19 15.41zm-4.18 4H9.18c.41 1.17 1.51 2 2.82 2s2.41-.83 2.82-2"/>
        </svg>
      </div>

      <?php if (isset($_SESSION['idUtilisateur'])): ?>
        <a class="nav-fav" href="favoris.php" title="Mes favoris">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 24 24">
            <path d="M11.29 20.66c.2.2.45.29.71.29s.51-.1.71-.29l7.5-7.5c2.35-2.35 2.35-6.05 0-8.41-2.3-2.28-5.85-2.35-8.21-.2-2.36-2.15-5.91-2.09-8.21.2-2.35 2.36-2.35 6.06 0 8.41zM5.21 6.16C6 5.38 7 4.99 8.01 4.99s2.01.39 2.79 1.17l.5.5c.39.39 1.02.39 1.41 0l.5-.5c1.56-1.56 4.02-1.56 5.59 0 1.56 1.57 1.56 4.02 0 5.58l-6.79 6.79-6.79-6.79a3.91 3.91 0 0 1 0-5.58Z"/>
          </svg>
        </a>

        <div class="user-menu" onclick="toggleMenu()">
          <?php if (!empty($_SESSION['photo'])): ?>
            <img src="<?= htmlspecialchars($_SESSION['photo']) ?>"
                 class="user-avatar" alt=""
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

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
  <a href="admin_dashboard.php" class="dropdown-item" style="color:var(--blue);font-weight:600">
    Dashboard admin
  </a>
<?php endif; ?>

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
          <div style="font-size:12px;opacity:.9">Votre annonce est maintenant visible par tous les acheteurs.</div>
        </div>
        <button onclick="this.parentElement.parentElement.remove()" style="background:none;border:none;color:#fff;cursor:pointer;margin-left:auto;font-size:20px">×</button>
      </div>
    </div>
  <?php endif; ?>

  <!-- HERO -->
  <!-- HERO -->
<section class="hero">
  <h1 class="hero-title">Trouvez votre prochain véhicule en Algérie</h1>
  <p class="hero-sub" id="hero-sub">Marketplace automobile algérienne</p>

  <!-- BARRE IA STYLE MOBILE.DE -->
  <div class="ai-search-overlap">
    <div class="ai-search">
      <h2 class="ai-search-title">Millions des vehicules. Une simple recherche</h2>

      <div class="ai-search-box">
        <svg class="ai-search-icon" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 0l1.5 4.5L18 6l-4.5 1.5L12 12l-1.5-4.5L6 6l4.5-1.5L12 0zm6 9l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3zM6 14l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/>
        </svg>

        <input type="text" placeholder="" id="ai-search-input">

        <button class="ai-search-btn" onclick="doAISearch()" aria-label="Rechercher">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <path d="M5 12h14"></path>
            <path d="M13 6l6 6-6 6"></path>
          </svg>
        </button>
      </div>
    </div>
  </div>
</section>

  <!-- SEARCH WRAP -->
  <div class="search-wrap">
    <div class="vtype-sidebar">
      <button class="vtype-icon-btn active" id="vt-voiture" onclick="setVType('voiture')" title="Voiture">
        <svg class="vtype-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
          <path d="m20.77 9.16-1.37-4.1a2.99 2.99 0 0 0-2.85-2.05H7.44a3 3 0 0 0-2.85 2.05l-1.37 4.1c-.72.3-1.23 1.02-1.23 1.84v5c0 .74.41 1.38 1 1.72V20c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-2h12v2c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-2.28a2 2 0 0 0 1-1.72v-5c0-.83-.51-1.54-1.23-1.84ZM7.44 5h9.12a1 1 0 0 1 .95.68L18.62 9H5.39L6.5 5.68A1 1 0 0 1 7.45 5ZM4 16v-5h16v5z"/>
          <path d="M6.5 12a1.5 1.5 0 1 0 0 3 1.5 1.5 0 1 0 0-3m11 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 1 0 0-3"/>
        </svg>
        <span class="vtype-label">Voiture</span>
      </button>
      <button class="vtype-icon-btn" id="vt-moto" onclick="setVType('moto')" title="Moto">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
          <path d="M20.26 14.47s-.06-.04-.1-.05c-.5-.27-1.07-.42-1.66-.42h-.06l-2.19-5.01h1.26c.28 0 .5-.22.5-.5v-1c0-.28-.22-.5-.5-.5h-2.13l-.09-.2a3.01 3.01 0 0 0-2.75-1.8h-1.53v2h1.53c.4 0 .76.24.92.6l1.05 2.4h-3.93c-.29 0-.56.12-.75.33L7.44 13l-2.72-2.72a1 1 0 0 0-.71-.29H1.84v2h1.75L5.6 14s-.06-.01-.1-.01c-1.11 0-2.13.51-2.79 1.38-.3.39-.53.87-.65 1.43-.04.22-.07.45-.07.68 0 1.93 1.57 3.5 3.5 3.5s3.5-1.57 3.5-3.5v-.1l1.3 1.3c.19.19.44.29.71.29h2c.25 0 .5-.1.68-.27L15 17.47c0 1.93 1.57 3.49 3.5 3.49s3.5-1.57 3.5-3.5c0-1.24-.67-2.4-1.74-3.03ZM5.5 19a1.498 1.498 0 0 1-1.47-1.79c.05-.23.14-.45.27-.61a1.506 1.506 0 0 1 1.94-.41l.06.03c.35.23.59.58.67.95.02.1.03.21.03.32 0 .83-.67 1.5-1.5 1.5Zm7.11-2h-1.19l-2.57-2.57L11.02 12h4.36l.76 1.73L12.62 17Zm5.89 2a1.498 1.498 0 0 1-1.2-2.4 1.506 1.506 0 0 1 1.94-.41 1.53 1.53 0 0 1 .77 1.31c0 .83-.67 1.5-1.5 1.5Z"/>
        </svg>
        <span class="vtype-label">Moto</span>
      </button>
      <button class="vtype-icon-btn" id="vt-camion" onclick="setVType('camion')" title="Camion">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
          <path d="M19.1 7.8c-.38-.5-.97-.8-1.6-.8H15V6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2 0 1.65 1.35 3 3 3s3-1.35 3-3h4c0 1.65 1.35 3 3 3s3-1.35 3-3c1.1 0 2-.9 2-2v-3.67c0-.43-.14-.86-.4-1.2zM17.5 9l1.5 2h-4V9zM7 19a1.003 1.003 0 0 1-.87-1.5c.37-.63 1.36-.63 1.73 0 .09.15.13.32.13.49 0 .55-.45 1-1 1Zm2.23-3s-.05-.05-.08-.07c-.06-.06-.12-.11-.17-.16-.12-.11-.25-.21-.38-.29a3 3 0 0 0-.67-.32c-.07-.02-.14-.05-.21-.07Q7.375 15 7 15c-.375 0-.49.04-.72.09-.07.02-.14.05-.21.07-.16.05-.31.11-.45.19-.07.04-.15.08-.22.13-.13.09-.26.18-.38.29-.06.05-.12.1-.18.16-.02.03-.05.04-.08.07h-.77V6h9v10H9.22ZM17 19a1.003 1.003 0 0 1-.87-1.5c.37-.63 1.36-.63 1.73 0 .09.15.13.32.13.49 0 .55-.45 1-1 1Zm3-3h-.77s-.05-.05-.08-.07c-.06-.06-.12-.11-.17-.16-.12-.11-.25-.21-.38-.29a3 3 0 0 0-.67-.32c-.07-.02-.14-.05-.21-.07Q17.375 15 17 15c-.375 0-.47.04-.7.09-.06.01-.12.03-.18.05-.18.06-.36.13-.52.22l-.12.06c-.17.1-.33.21-.48.35v-2.76h5v3Z"/>
        </svg>
        <span class="vtype-label">Camion</span>
      </button>
    </div>

    <div class="search-box">
      <div class="search-tabs">
        <div class="search-tab active" id="st-buy" onclick="setSTab('buy')">Acheter</div>
        <div class="search-tab" id="st-rent" onclick="setSTab('rent')">Louer</div>
      </div>

    <div class="sf-grid sf-grid-row1">
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
    </select>
  </div>
</div>

      <div class="sf-grid sf-grid-row2">
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
              <option value="Adrar"><option value="Aïn Defla"><option value="Aïn Témouchent">
              <option value="Alger"><option value="Annaba"><option value="Batna">
              <option value="Béchar"><option value="Béjaïa"><option value="Biskra">
              <option value="Blida"><option value="Bordj Bou Arreridj"><option value="Bouira">
              <option value="Boumerdès"><option value="Chlef"><option value="Constantine">
              <option value="Djelfa"><option value="El Bayadh"><option value="El Oued">
              <option value="El Tarf"><option value="Ghardaïa"><option value="Guelma">
              <option value="Illizi"><option value="Jijel"><option value="Khenchela">
              <option value="Laghouat"><option value="Mascara"><option value="Médéa">
              <option value="Mila"><option value="Mostaganem"><option value="M'Sila">
              <option value="Naâma"><option value="Oran"><option value="Ouargla">
              <option value="Oum El Bouaghi"><option value="Relizane"><option value="Saïda">
              <option value="Sétif"><option value="Sidi Bel Abbès"><option value="Skikda">
              <option value="Souk Ahras"><option value="Tamanrasset"><option value="Tébessa">
              <option value="Tiaret"><option value="Tindouf"><option value="Tipaza">
              <option value="Tissemsilt"><option value="Tizi Ouzou"><option value="Tlemcen">
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
        <span class="sf-footer-link" onclick="openAdvancedFilters()">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="10" y1="18" x2="14" y2="18"/></svg>
          Filtres avancés
        </span>
      </div>
    </div>
  </div>


  <!-- ══ TOP DEALS CARROUSEL ══ -->
<section class="top-deals">
  <div class="deals-wrap">
    
    <div class="deals-head">
      <h2 class="deals-title">
        Top <span class="deals-badge">DEALS</span> pour vous
      </h2>
      <a href="recherche.php" class="deals-view-all">Tout afficher →</a>
    </div>

    <div class="deals-carousel-wrap">
      <button class="deals-nav deals-nav-left" onclick="scrollDeals(-3)" aria-label="Précédent">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <path d="m15 18-6-6 6-6"/>
        </svg>
      </button>

      <div class="deals-scroll" id="deals-scroll">
        <?php
        $sql_deals = "
            SELECT
                a.idAnnonce, a.titre, a.prix, a.localisation, a.datePublication,
                v.annee, v.kilometrage, v.carburant, v.transmission, v.puissance,
                (SELECT urlPhoto FROM Photos WHERE idAnnonce = a.idAnnonce ORDER BY ordrePhoto ASC LIMIT 1) AS photo_principale
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
                $photoD = $d['photo_principale'] ?? null;
        ?>
        <div class="deal-card" onclick="location.href='ficheAnnonces.php?id=<?= $idAnn ?>'">
          <div class="deal-img" <?= $photoD ? 'style="background-image:url(\''.htmlspecialchars($photoD).'\');background-size:cover;background-position:center"' : '' ?>>
            <button class="deal-fav" onclick="event.stopPropagation();toggleFav('<?= $idAnn ?>', this)">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M11.29 20.66c.2.2.45.29.71.29s.51-.1.71-.29l7.5-7.5c2.35-2.35 2.35-6.05 0-8.41-2.3-2.28-5.85-2.35-8.21-.2-2.36-2.15-5.91-2.09-8.21.2-2.35 2.36-2.35 6.06 0 8.41z"/>
              </svg>
            </button>
            <?php if (!$photoD): ?>
            <svg class="deal-placeholder" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.8">
              <rect x="1" y="6" width="22" height="13" rx="3"/>
              <circle cx="7" cy="16" r="1.5"/>
              <circle cx="17" cy="16" r="1.5"/>
            </svg>
            <?php endif; ?>
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
              <div class="deal-chip">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                  <path d="M3 22h12M4 9h10M4 5v17M14 5v17M4 5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2M18 8v8a2 2 0 0 0 4 0V9l-3-3"/>
                </svg>
                <?= $carbu ?>
              </div>
              <?php if ($puiss): ?>
              <div class="deal-chip">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                  <circle cx="12" cy="12" r="10"/>
                  <polyline points="12 6 12 12 16 14"/>
                </svg>
                <?= $puiss ?> CV
              </div>
              <?php endif; ?>
            </div>

            <div class="deal-chip deal-chip-full">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
              </svg>
              <?= $trans ?>
            </div>

            <div class="deal-location">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                <circle cx="12" cy="9" r="2.5"/>
              </svg>
              <?= $loc ?>
            </div>
          </div>
        </div>
        <?php }} ?>
      </div>

      <button class="deals-nav deals-nav-right" onclick="scrollDeals(3)" aria-label="Suivant">
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
          <div class="sell-banner-sub">Annonce gratuite · Visible par des milliers d'acheteurs · Réponse rapide</div>
        </div>
        <button class="btn-white" onclick="location.href='publier.php'">Déposer une annonce</button>
      </div>

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
          <select class="sort-sel">
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

      <div class="listings" id="listings-container">
        <?php
        $sql_annonces = "
            SELECT
                a.idAnnonce, a.titre, a.prix, a.localisation,
                a.datePublication, a.vendeurVerif,
                v.annee, v.kilometrage, v.carburant, v.transmission,
                u.nom AS vendeur_nom, u.prenom AS vendeur_prenom,
                (SELECT urlPhoto FROM Photos WHERE idAnnonce = a.idAnnonce ORDER BY ordrePhoto ASC LIMIT 1) AS photo_principale
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
                if ($diff == 0)     $dl = "Aujourd'hui";
                elseif ($diff == 1) $dl = "Hier";
                else                $dl = "Il y a $diff jours";

                $photo = $a['photo_principale'] ?? null;
                if ($photo) {
                    $photoEsc = htmlspecialchars($photo);
                    $imgBlock = "<img src='$photoEsc' alt='$titre' onerror=\"this.style.display='none';this.nextElementSibling.style.display='flex';\">
                                 <div class='lcard-img-ph' style='display:none'>
                                   <svg width='44' height='44' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='0.8'>
                                     <rect x='1' y='6' width='22' height='13' rx='3'/>
                                     <circle cx='7' cy='16' r='1.5'/><circle cx='17' cy='16' r='1.5'/>
                                   </svg>
                                 </div>";
                } else {
                    $imgBlock = "<div class='lcard-img-ph'>
                                   <svg width='44' height='44' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='0.8'>
                                     <rect x='1' y='6' width='22' height='13' rx='3'/>
                                     <circle cx='7' cy='16' r='1.5'/><circle cx='17' cy='16' r='1.5'/>
                                   </svg>
                                 </div>";
                }

                echo "
                <div class='lcard' onclick=\"location.href='fiche_annonce.php?id={$a['idAnnonce']}'\">
                  <div class='lcard-img'>
                    $imgBlock
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
    </main>
  </div>

  <footer class="footer">
    © 2026 AUTOMARKET — Marketplace automobile algérienne &nbsp;·&nbsp;
    <a href="#">Aide</a> &nbsp;·&nbsp;
    <a href="#">Confidentialité</a> &nbsp;·&nbsp;
    <a href="#">Conditions d'utilisation</a>
  </footer>

  <script>
    const MARQUES_DB = <?= json_encode($marquesDB, JSON_UNESCAPED_UNICODE); ?>;
   function buildAdvMarques() {

  const list = document.getElementById('adv-marques-list');

  if (!list) return;

  list.innerHTML = '';

  Object.keys(MARQUES_DB).forEach(marque => {

    const opt = document.createElement('option');

    opt.value = marque;

    list.appendChild(opt);

  });
}
window.addEventListener('load', buildAdvMarques);
function updateAdvModeles() {

  const marqueInput = document.getElementById('adv-marque');

  const modeleInput = document.getElementById('adv-modele');

  const versionInput = document.getElementById('adv-version');

  const modelesList = document.getElementById('adv-modeles-list');

  const versionsList = document.getElementById('adv-versions-list');

  if (!marqueInput || !modeleInput || !versionInput || !modelesList || !versionsList) return;

  const marque = marqueInput.value.trim();

  modeleInput.value = '';

  versionInput.value = '';

  modelesList.innerHTML = '';

  versionsList.innerHTML = '';

  const modeles = MARQUES_DB[marque];

  if (!modeles) return;

  Object.keys(modeles).forEach(modele => {

    const opt = document.createElement('option');

    opt.value = modele;

    modelesList.appendChild(opt);

  });
}
function updateAdvVersions() {

  const marque = document.getElementById('adv-marque').value.trim();

  const modele = document.getElementById('adv-modele').value.trim();

  const versionInput = document.getElementById('adv-version');

  const versionsList = document.getElementById('adv-versions-list');

  if (!versionInput || !versionsList) return;

  versionInput.value = '';

  versionsList.innerHTML = '';

  const versions = MARQUES_DB[marque]?.[modele];

  if (!versions) return;

  versions.forEach(version => {

    const opt = document.createElement('option');

    opt.value = version;

    versionsList.appendChild(opt);

  });

}
    const DATA = {
      voiture: {
        marques:MARQUES_DB,
        labels: { marque:'Marque', modele:'Modèle', km:'Kilomètres jusqu\'à', prix:'Prix jusqu\'à' },
        count: '<?= $counts_type["voiture"] ?> annonces',
        subtitle: 'Marketplace voitures — Algérie',
        showElec: true, showPaiement: true,
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
        showElec: false, showPaiement: false,
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
        showElec: false, showPaiement: true,
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
          void btn.offsetWidth;
          btn.classList.add('animating');
          btn.addEventListener('animationend', () => btn.classList.remove('animating'), { once: true });
        }
      });
      const d = DATA[type];
      document.getElementById('hero-sub').textContent = d.subtitle;
      document.getElementById('lbl-marque').textContent = d.labels.marque;
      document.getElementById('lbl-modele').textContent = d.labels.modele;
      document.getElementById('lbl-km').textContent     = d.labels.km;
      document.getElementById('lbl-prix').textContent   = d.labels.prix;
      document.getElementById('btn-search-count').textContent = d.count;

      const selKm = document.getElementById('sel-km');
      selKm.innerHTML = '<option value="">Quelconque</option>';
      d.kmOptions.forEach(k => {
        const o = document.createElement('option');
        o.value = k; o.textContent = k;
        selKm.appendChild(o);
      });

      document.getElementById('col-paiement').style.display = d.showPaiement ? '' : 'none';
      document.getElementById('sf-elec-row').style.display  = d.showElec     ? '' : 'none';
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

  const modeles = DATA[currentVType].marques[marque];

  if (modeles) {
    Object.keys(modeles).forEach(modele => {
      const o = document.createElement('option');
      o.value = modele;
      o.textContent = modele;
      sel.appendChild(o);
    });
  }
}
    function setSTab(t) {
      ['buy','rent'].forEach(id => {
        document.getElementById('st-' + id).classList.toggle('active', id === t);
      });
    }

    function setPayMode(m) {
      document.getElementById('pay-achat').classList.toggle('active', m==='achat');
      document.getElementById('pay-credit').classList.toggle('active', m==='credit');
    }

    function toggleElec() {
      document.getElementById('chk-elec').classList.toggle('on');
    }
function updateModels() {
  const marque = document.getElementById('sel-marque').value;
  const sel = document.getElementById('sel-modele');

  sel.innerHTML = '<option value="">Quelconque</option>';

  const modeles = DATA[currentVType].marques[marque];

  if (modeles) {
    Object.keys(modeles).forEach(modele => {
      const o = document.createElement('option');
      o.value = modele;
      o.textContent = modele;
      sel.appendChild(o);
    });
  }
}
    function doAISearch() {
      const q = document.getElementById('ai-search-input').value.trim();
      if (q) alert('Recherche IA : ' + q);
    }
 function openAdvancedFilters() {
  document.getElementById('advancedFilters').classList.add('show');
  document.body.style.overflow = 'hidden';
  buildAdvMarques();
}

function closeAdvancedFilters() {
  document.getElementById('advancedFilters').classList.remove('show');
  document.body.style.overflow = '';
}

    buildMarques();

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        const d = document.getElementById('user-dropdown');
        if (d) d.style.display = 'none';
      }
    });

    function setView(btn) {
      btn.closest('.view-btns').querySelectorAll('.view-btn')
         .forEach(b => b.classList.remove('active'));
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

    function updateDealsArrows() {
    const scrollBox = document.getElementById("deals-scroll");
    const leftBtn = document.querySelector(".deals-nav-left");
    const rightBtn = document.querySelector(".deals-nav-right");

    if (!scrollBox || !leftBtn || !rightBtn) return;

    const maxScroll = scrollBox.scrollWidth - scrollBox.clientWidth;

    if (scrollBox.scrollLeft <= 5) {
      leftBtn.classList.add("is-hidden");
    } else {
      leftBtn.classList.remove("is-hidden");
    }

    if (scrollBox.scrollLeft >= maxScroll - 5) {
      rightBtn.classList.add("is-hidden");
    } else {
      rightBtn.classList.remove("is-hidden");
    }
  }

  function scrollDeals(direction) {
    const scrollBox = document.getElementById("deals-scroll");
    if (!scrollBox) return;

    scrollBox.scrollBy({
      left: direction * 300,
      behavior: "smooth"
    });

    setTimeout(updateDealsArrows, 350);
  }

  window.addEventListener("load", () => {
    updateDealsArrows();

    const scrollBox = document.getElementById("deals-scroll");
    if (scrollBox) {
      scrollBox.addEventListener("scroll", updateDealsArrows);
    }
  });

  window.addEventListener("resize", updateDealsArrows); 
    function openAdvancedFilters() {
  document.getElementById('advancedFilters').classList.add('show');
  document.body.style.overflow = 'hidden';
}

function closeAdvancedFilters() {
  document.getElementById('advancedFilters').classList.remove('show');
  document.body.style.overflow = '';
}

    function toggleFav(id, btn) {
      const fd = new FormData();
      fd.append('action', 'toggle');
      fd.append('idAnnonce', id);
      
      fetch('toggle_favori.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
          if (json.needLogin) {
            location.href = 'inscription.php';
            return;
          }
          btn.classList.toggle('faved', json.favori);
        })
        .catch(() => console.error('Erreur favori'));
    }

  const aiSearchTexts = [
    "Peugeot 208",
    "BMW X3 automatique",
    "Toyota Corolla hybride",
    "Hyundai Tucson diesel",
    "Mercedes Classe C AMG Line",
    "Golf 8 GTI",
    "Range Rover Evoque",
    "Renault Clio 5 essence"
  ];

  const aiInput = document.getElementById("ai-search-input");

  let textIndex = 0;
  let charIndex = 0;
  let isDeleting = false;
  let cursorVisible = true;
  let typingTimeout;

 function typeAiSearch() {
  if (!aiInput) return;

  if (document.activeElement === aiInput || aiInput.value.trim() !== "") {
    aiInput.setAttribute("placeholder", "");
    return;
  }

  const currentText = aiSearchTexts[textIndex];
  const cursor = cursorVisible ? "|" : "";

  if (!isDeleting) {
    charIndex++;
    aiInput.setAttribute("placeholder", currentText.substring(0, charIndex) + cursor);

    if (charIndex < currentText.length) {
      typingTimeout = setTimeout(typeAiSearch, 85);
    } else {
      isDeleting = true;
      typingTimeout = setTimeout(typeAiSearch, 1400);
    }
  } else {
    charIndex--;
    aiInput.setAttribute("placeholder", currentText.substring(0, charIndex) + cursor);

    if (charIndex > 0) {
      typingTimeout = setTimeout(typeAiSearch, 40);
    } else {
      isDeleting = false;
      textIndex = (textIndex + 1) % aiSearchTexts.length;
      typingTimeout = setTimeout(typeAiSearch, 300);
    }
  }
}

  function blinkPlaceholderCursor() {
    if (!aiInput) return;

    if (document.activeElement === aiInput || aiInput.value.trim() !== "") return;

    cursorVisible = !cursorVisible;

    const currentText = aiSearchTexts[textIndex];
    aiInput.setAttribute(
      "placeholder",
      currentText.substring(0, charIndex) + (cursorVisible ? "|" : "")
    );
  }
  function onlyNumbers(input) {

  input.value = input.value.replace(/[^0-9]/g, '');

}

function checkRangePair(minId, maxId, label) {

  const minInput = document.getElementById(minId);

  const maxInput = document.getElementById(maxId);

  if (!minInput || !maxInput) return null;

  const min = minInput.value !== '' ? Number(minInput.value) : null;

  const max = maxInput.value !== '' ? Number(maxInput.value) : null;

  minInput.classList.remove('error');

  maxInput.classList.remove('error');

  if (min !== null && min < 0) minInput.value = 0;

  if (max !== null && max < 0) maxInput.value = 0;

  if (min !== null && max !== null && min > max) {

    minInput.classList.add('error');

    maxInput.classList.add('error');

    return label + " : la valeur minimum doit être inférieure à la valeur maximum.";

  }

  return null;

}

function checkRanges() {

  const errorBox = document.getElementById('range-error');

  const errors = [

    checkRangePair('annee-min', 'annee-max', 'Année'),

    checkRangePair('prix-min', 'prix-max', 'Prix'),

    checkRangePair('km-min', 'km-max', 'Kilométrage'),

    checkRangePair('perf-min', 'perf-max', 'Performance'),

    checkRangePair('cylindree-min', 'cylindree-max', 'Cylindrée moteur'),

    checkRangePair('reservoir-min', 'reservoir-max', 'Taille du réservoir'),

    checkRangePair('poids-min', 'poids-max', 'Poids')

  ].filter(Boolean);

  if (errors.length > 0) {

    if (errorBox) {

      errorBox.textContent = errors[0];

      errorBox.classList.remove('hidden');

    }

    return false;

  }

  if (errorBox) errorBox.classList.add('hidden');

  return true;

}

function resetAdvancedFilters() {

  const box = document.getElementById('advancedFilters');

  if (!box) return;

  box.querySelectorAll('input').forEach(input => {

    if (input.type === 'checkbox' || input.type === 'radio') {

      input.checked = false;

    } else {

      input.value = '';

    }

    input.classList.remove('error');

  });

  box.querySelectorAll('select').forEach(select => {

    select.selectedIndex = 0;

  });

  const vendeurDefault = box.querySelector('input[name="vendeur"]');

  if (vendeurDefault) vendeurDefault.checked = true;

  const errorBox = document.getElementById('range-error');

  if (errorBox) {

    errorBox.textContent = '';

    errorBox.classList.add('hidden');

  }
}
  window.addEventListener("load", () => {
    typeAiSearch();
    setInterval(blinkPlaceholderCursor, 500);
  });

  aiInput.addEventListener("focus", () => {
    clearTimeout(typingTimeout);
    aiInput.setAttribute("placeholder", "");
  });

  aiInput.addEventListener("blur", () => {
    if (aiInput.value.trim() === "") {
      clearTimeout(typingTimeout);
      charIndex = 0;
      isDeleting = false;
      typingTimeout = setTimeout(typeAiSearch, 300);
    }
  });
  </script>
  <?php include 'filtresAvances.php'; ?>
</body>
</html>
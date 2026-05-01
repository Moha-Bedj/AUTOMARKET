
    
<style>
.advanced-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.35);
  z-index: 999;
  display: none;
  overflow-y: auto;
  padding: 40px 16px;
}

.advanced-overlay.show {
  display: block;
}

.advanced-panel {
  max-width: 980px;
  margin: 0 auto;
  background: #fff;
  border-radius: 14px;
  padding: 24px 28px;
  box-shadow: 0 10px 35px rgba(0,0,0,.25);
}

.advanced-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.advanced-title {
  font-size: 24px;
  font-weight: 600;
}

.close-advanced {
  border: none;
  background: transparent;
  font-size: 28px;
  cursor: pointer;
  color: var(--t2);
}

.adv-section {
  border: 0.5px solid var(--bd);
  border-radius: 12px;
  padding: 22px;
  margin-bottom: 18px;
}

.adv-section h3 {
  font-size: 18px;
  margin-bottom: 18px;
}

.adv-checks {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px 20px;
}

.adv-check {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
}

.adv-check input {
  width: 16px;
  height: 16px;
}

.range-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 22px 28px;
}

.range-field label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 8px;
}

.range-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.range-row input {
  height: 40px;
  border: 0.5px solid var(--bd2);
  border-radius: var(--r8);
  padding: 0 12px;
  font-size: 14px;
  font-family: inherit;
  outline: none;
}

.range-row input:focus {
  border-color: var(--blue);
  box-shadow: 0 0 0 3px rgba(24,95,165,.1);
}

.range-row input.error {
  border-color: var(--red);
  background: var(--red-bg);
}

.input-unit {
  position: relative;
  flex: 1;
}

.input-unit input {
  width: 100%;
  height: 44px;
  padding: 0 55px 0 12px;
  border: 1px solid var(--bd2);
  border-radius: 8px;
  font-size: 14px;
}

.input-unit .unit {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 13px;
  color: #555;
  pointer-events: none;
}

input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type=number] {
  -moz-appearance: textfield;
}

.range-error {
  margin-top: 16px;
  background: var(--red-bg);
  color: #791F1F;
  border: 0.5px solid rgba(226,75,74,.3);
  border-radius: var(--r8);
  padding: 10px 14px;
  font-size: 13px;
}

.color-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px 14px;
  margin-top: 12px;
}

.color-option {
  position: relative;
  display: flex;
  align-items: center;
  gap: 9px;
  font-size: 13px;
  cursor: pointer;
  padding: 8px 10px;
  border: 0.5px solid var(--bd);
  border-radius: 10px;
  background: #fff;
  transition: all .2s ease;
}
.sf-input {
  width: 100%;
  height: 42px;
  border: 0.5px solid var(--bd2);
  border-radius: var(--r8);
  padding: 0 12px;
  font-size: 14px;
  background: #fff;
  outline: none;
}

.color-option:hover {
  transform: translateY(-2px);
  border-color: var(--blue);
  box-shadow: 0 6px 16px rgba(24,95,165,.12);
}

.color-option input {
  display: none;
}

.color-dot {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 1px solid rgba(0,0,0,.2);
  transition: all .25s ease;
  flex-shrink: 0;
}

.color-option input:checked + .color-dot {
  transform: scale(1.18);
  box-shadow: 0 0 0 3px #fff, 0 0 0 5px var(--blue);
}

.color-option:has(input:checked) {
  border-color: var(--blue);
  background: var(--blue-bg);
  font-weight: 600;
}

.black { background: #111; }
.white { background: #fff; }
.gray { background: #8f8f8f; }
.silver { background: linear-gradient(135deg, #f7f7f7, #aaa); }
.red { background: #e53935; }
.blue { background: #2563eb; }
.green { background: #16a34a; }
.brown { background: #8b5a2b; }
.beige { background: #d6b47b; }
.orange { background: #f97316; }
.yellow { background: #facc15; }
.purple { background: #8b5cf6; }

.advanced-bottom {
  position: sticky;
  bottom: 0;
  background: #fff;
  border-top: 0.5px solid var(--bd);
  padding: 16px 0 0;
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.btn-reset {
  background: transparent;
  border: none;
  color: var(--red);
  font-size: 14px;
  cursor: pointer;
}

.btn-apply {
  background: var(--blue);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 12px 26px;
  font-weight: 600;
  cursor: pointer;
}

.hidden {
  display: none;
}

@media (max-width: 700px) {
  .range-grid,
  .adv-checks {
    grid-template-columns: 1fr;
  }

  .range-row {
    grid-template-columns: 1fr;
  }

  .color-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .sf-select {
  width: 100%;
  height: 42px;
  border: 0.5px solid var(--bd2);
  border-radius: var(--r8);
  padding: 0 12px;
  font-size: 14px;
  background: #fff;
  outline: none;
}
}
</style>

    
<!-- FILTRES AVANCÉS -->
<div class="advanced-overlay" id="advancedFilters">
  <div class="advanced-panel">

    <div class="advanced-head">
      <div class="advanced-title">Recherche avancée : Voitures</div>
      <button class="close-advanced" onclick="closeAdvancedFilters()">×</button>
    </div>

<div class="adv-section">
  <h3>Identification du véhicule</h3>

  <div class="range-grid">
    <div class="range-field">
      <label>Marque</label>
     <input class="sf-input" type="text" id="adv-marque" placeholder="Ex: Peugeot" list="adv-marques-list" oninput="updateAdvModeles()">
    </div>

    <div class="range-field">
      <label>Modèle</label>
    <input class="sf-input" type="text" id="adv-modele" placeholder="Ex: 208" list="adv-modeles-list" oninput="updateAdvVersions()">
    </div>

    <div class="range-field">
      <label>Variante</label>
   <input class="sf-input" type="text" id="adv-version" placeholder="Ex: GT Line" list="adv-versions-list">
    </div>
  </div>
</div>

<datalist id="adv-marques-list"></datalist>
<datalist id="adv-modeles-list"></datalist>
<datalist id="adv-versions-list"></datalist>
    <div class="adv-section">
      <h3>Équipements & confort</h3>

      <div class="adv-checks">
        <label class="adv-check"><input type="checkbox"> Climatisation</label>
        <label class="adv-check"><input type="checkbox"> Climatisation automatique</label>
        <label class="adv-check"><input type="checkbox"> Bluetooth</label>
        <label class="adv-check"><input type="checkbox"> GPS / Navigation</label>
        <label class="adv-check"><input type="checkbox"> Caméra de recul</label>
        <label class="adv-check"><input type="checkbox"> Radar avant</label>
        <label class="adv-check"><input type="checkbox"> Radar arrière</label>
        <label class="adv-check"><input type="checkbox"> Écran tactile</label>
        <label class="adv-check"><input type="checkbox"> Apple CarPlay</label>
        <label class="adv-check"><input type="checkbox"> Android Auto</label>
        <label class="adv-check"><input type="checkbox"> Sièges chauffants</label>
        <label class="adv-check"><input type="checkbox"> Sièges électriques</label>
        <label class="adv-check"><input type="checkbox"> Volant multifonction</label>
        <label class="adv-check"><input type="checkbox"> Volant cuir</label>
        <label class="adv-check"><input type="checkbox"> Toit ouvrant</label>
        <label class="adv-check"><input type="checkbox"> Démarrage sans clé</label>
        <label class="adv-check"><input type="checkbox"> Accès mains libres</label>
        <label class="adv-check"><input type="checkbox"> Régulateur de vitesse</label>
      </div>
    </div>

    <div class="adv-section">
      <h3>Plages de valeurs</h3>

      <div class="range-grid">

        <div class="range-field">
          <label>Année</label>
          <div class="range-row">
            <input type="number" id="annee-min" placeholder="depuis" min="1900" max="2026" list="annees-list" oninput="checkRanges()">
            <input type="number" id="annee-max" placeholder="jusqu'à" min="1900" max="2026" list="annees-list" oninput="checkRanges()">
          </div>
        </div>

        <div class="range-field">
          <label>Prix</label>
          <div class="range-row">
            <input type="number" id="prix-min" placeholder="depuis" min="0" list="prix-list" oninput="checkRanges()">
            <input type="number" id="prix-max" placeholder="jusqu'à" min="0" list="prix-list" oninput="checkRanges()">
          </div>
        </div>

        <div class="range-field">
          <label>Kilométrage</label>
          <div class="range-row">
            <input type="number" id="km-min" placeholder="depuis" min="0" list="km-list" oninput="checkRanges()">
            <input type="number" id="km-max" placeholder="jusqu'à" min="0" list="km-list" oninput="checkRanges()">
          </div>
        </div>

        <div class="range-field">
          <label>Performance</label>
          <div class="range-row">
            <div class="input-unit">
              <input type="text" inputmode="numeric" id="perf-min" placeholder="depuis" list="perf-list" oninput="onlyNumbers(this); checkRanges();">
              <span class="unit">HP</span>
            </div>

            <div class="input-unit">
              <input type="text" inputmode="numeric" id="perf-max" placeholder="jusqu'à" list="perf-list" oninput="onlyNumbers(this); checkRanges();">
              <span class="unit">HP</span>
            </div>
          </div>
        </div>

        <div class="range-field">
          <label>Cylindrée moteur</label>
          <div class="range-row">
            <div class="input-unit">
              <input type="number" id="cylindree-min" placeholder="depuis" min="0" list="cylindree-list" oninput="checkRanges()">
              <span class="unit">cm³</span>
            </div>

            <div class="input-unit">
              <input type="number" id="cylindree-max" placeholder="jusqu'à" min="0" list="cylindree-list" oninput="checkRanges()">
              <span class="unit">cm³</span>
            </div>
          </div>
        </div>

        <div class="range-field">
          <label>Taille du réservoir</label>
          <div class="range-row">
            <div class="input-unit">
              <input type="number" id="reservoir-min" placeholder="depuis" min="0" list="reservoir-list" oninput="checkRanges()">
              <span class="unit">L</span>
            </div>

            <div class="input-unit">
              <input type="number" id="reservoir-max" placeholder="jusqu'à" min="0" list="reservoir-list" oninput="checkRanges()">
              <span class="unit">L</span>
            </div>
          </div>
        </div>

        <div class="range-field">
          <label>Poids</label>
          <div class="range-row">
            <div class="input-unit">
              <input type="number" id="poids-min" placeholder="depuis" min="0" list="poids-list" oninput="checkRanges()">
              <span class="unit">kg</span>
            </div>

            <div class="input-unit">
              <input type="number" id="poids-max" placeholder="jusqu'à" min="0" list="poids-list" oninput="checkRanges()">
              <span class="unit">kg</span>
            </div>
          </div>
        </div>

      </div>

      <div id="range-error" class="range-error hidden"></div>
    </div>

    <datalist id="annees-list">
      <option value="2010"><option value="2012"><option value="2015">
      <option value="2018"><option value="2020"><option value="2022">
      <option value="2024"><option value="2026">
    </datalist>

    <datalist id="prix-list">
      <option value="500000"><option value="1000000"><option value="1500000">
      <option value="2000000"><option value="3000000"><option value="5000000">
      <option value="8000000"><option value="10000000"><option value="15000000">
    </datalist>

    <datalist id="km-list">
      <option value="0"><option value="5000"><option value="10000">
      <option value="30000"><option value="50000"><option value="80000">
      <option value="100000"><option value="150000"><option value="200000">
    </datalist>

    <datalist id="perf-list">
      <option value="75"><option value="90"><option value="110">
      <option value="130"><option value="150"><option value="180">
      <option value="200"><option value="250"><option value="300">
      <option value="400">
    </datalist>

    <datalist id="cylindree-list">
      <option value="1000"><option value="1200"><option value="1400">
      <option value="1600"><option value="1800"><option value="2000">
      <option value="2500"><option value="3000"><option value="4000">
    </datalist>

    <datalist id="reservoir-list">
      <option value="30"><option value="40"><option value="50">
      <option value="60"><option value="70"><option value="80">
      <option value="100">
    </datalist>

    <datalist id="poids-list">
      <option value="800"><option value="1000"><option value="1200">
      <option value="1500"><option value="1800"><option value="2000">
      <option value="2500"><option value="3000">
    </datalist>

    <div class="adv-section">
      <h3>Couleur extérieure</h3>

      <div class="color-grid">
        <label class="color-option"><input type="radio" name="couleur_exterieure" value="noir"><span class="color-dot black"></span>Noir</label>
        <label class="color-option"><input type="radio" name="couleur_exterieure" value="blanc"><span class="color-dot white"></span>Blanc</label>
        <label class="color-option"><input type="radio" name="couleur_exterieure" value="gris"><span class="color-dot gray"></span>Gris</label>
        <label class="color-option"><input type="radio" name="couleur_exterieure" value="argent"><span class="color-dot silver"></span>Argent</label>
        <label class="color-option"><input type="radio" name="couleur_exterieure" value="rouge"><span class="color-dot red"></span>Rouge</label>
        <label class="color-option"><input type="radio" name="couleur_exterieure" value="bleu"><span class="color-dot blue"></span>Bleu</label>
        <label class="color-option"><input type="radio" name="couleur_exterieure" value="vert"><span class="color-dot green"></span>Vert</label>
        <label class="color-option"><input type="radio" name="couleur_exterieure" value="marron"><span class="color-dot brown"></span>Marron</label>
        <label class="color-option"><input type="radio" name="couleur_exterieure" value="beige"><span class="color-dot beige"></span>Beige</label>
        <label class="color-option"><input type="radio" name="couleur_exterieure" value="orange"><span class="color-dot orange"></span>Orange</label>
        <label class="color-option"><input type="radio" name="couleur_exterieure" value="jaune"><span class="color-dot yellow"></span>Jaune</label>
        <label class="color-option"><input type="radio" name="couleur_exterieure" value="violet"><span class="color-dot purple"></span>Violet</label>
      </div>
    </div>

    <div class="adv-section">
      <h3>Couleur intérieure</h3>

      <div class="color-grid interior-colors">
        <label class="color-option"><input type="radio" name="couleur_interieure" value="noir"><span class="color-dot black"></span>Noir</label>
        <label class="color-option"><input type="radio" name="couleur_interieure" value="gris"><span class="color-dot gray"></span>Gris</label>
        <label class="color-option"><input type="radio" name="couleur_interieure" value="blanc"><span class="color-dot white"></span>Blanc</label>
        <label class="color-option"><input type="radio" name="couleur_interieure" value="beige"><span class="color-dot beige"></span>Beige</label>
        <label class="color-option"><input type="radio" name="couleur_interieure" value="marron"><span class="color-dot brown"></span>Marron</label>
        <label class="color-option"><input type="radio" name="couleur_interieure" value="rouge"><span class="color-dot red"></span>Rouge</label>
      </div>
    </div>

    <div class="adv-section">
      <h3>Sécurité</h3>

      <div class="adv-checks">
        <label class="adv-check"><input type="checkbox"> ABS</label>
        <label class="adv-check"><input type="checkbox"> ESP</label>
        <label class="adv-check"><input type="checkbox"> Airbags</label>
        <label class="adv-check"><input type="checkbox"> Aide au freinage</label>
        <label class="adv-check"><input type="checkbox"> Aide au démarrage en côte</label>
        <label class="adv-check"><input type="checkbox"> Détecteur d’angle mort</label>
        <label class="adv-check"><input type="checkbox"> Alerte franchissement de ligne</label>
        <label class="adv-check"><input type="checkbox"> Freinage automatique</label>
        <label class="adv-check"><input type="checkbox"> Capteur de pluie</label>
        <label class="adv-check"><input type="checkbox"> Capteur de lumière</label>
        <label class="adv-check"><input type="checkbox"> Phares LED</label>
        <label class="adv-check"><input type="checkbox"> Antibrouillard</label>
      </div>
    </div>

    <div class="adv-section">
      <h3>État & historique</h3>

      <div class="adv-checks">
        <label class="adv-check"><input type="checkbox"> Première main</label>
        <label class="adv-check"><input type="checkbox"> Carnet d’entretien</label>
        <label class="adv-check"><input type="checkbox"> Garantie</label>
        <label class="adv-check"><input type="checkbox"> Non accidenté</label>
        <label class="adv-check"><input type="checkbox"> Véhicule non-fumeur</label>
        <label class="adv-check"><input type="checkbox"> Contrôle technique valide</label>
        <label class="adv-check"><input type="checkbox"> Paiement par crédit possible</label>
        <label class="adv-check"><input type="checkbox"> Prix négociable</label>
      </div>
    </div>

    <div class="adv-section">
      <h3>Vendeur</h3>

      <div class="adv-checks">
        <label class="adv-check"><input type="radio" name="vendeur" checked> N’importe lequel</label>
        <label class="adv-check"><input type="radio" name="vendeur"> Particulier</label>
        <label class="adv-check"><input type="radio" name="vendeur"> Professionnel</label>
        <label class="adv-check"><input type="checkbox"> Vendeur vérifié</label>
        <label class="adv-check"><input type="checkbox"> Avec numéro de téléphone</label>
        <label class="adv-check"><input type="checkbox"> Avec photos</label>
      </div>
    </div>

    <div class="advanced-bottom">
      <button class="btn-reset" onclick="resetAdvancedFilters()">Réinitialiser le filtre</button>
      <button class="btn-apply" onclick="if(checkRanges()) closeAdvancedFilters()">Appliquer les filtres</button>
    </div>

  </div>
</div>
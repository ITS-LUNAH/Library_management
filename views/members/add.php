<?php
// views/members/add.php
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ajouter un membre — Bibliothèque</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .password-wrap { position: relative; }
    .password-wrap input { padding-right: 44px; width: 100%; }
    .toggle-pwd {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      color: var(--ink-muted); font-size: 1rem; padding: 4px;
      transition: color .15s;
    }
    .toggle-pwd:hover { color: var(--ink); }
    .section-label {
      font-size: .7rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .1em; color: var(--ink-muted);
      grid-column: 1/-1; margin-top: 8px;
      padding-bottom: 6px; border-bottom: 1px solid var(--border);
    }
    .strength-bar { height: 4px; border-radius: 2px; background: var(--border); margin-top: 6px; overflow: hidden; }
    .strength-fill { height: 100%; border-radius: 2px; width: 0; transition: width .3s, background .3s; }
  </style>
</head>
<body>

<header class="site-header">
  <div class="inner">
    <a href="index.php" class="brand">Biblio<span>thèque</span></a>
    <nav class="nav">
      <a href="index.php?page=books">Livres</a>
      <a href="index.php?page=members" class="active">Membres</a>
      <a href="index.php?page=borrows">Emprunts</a>
    </nav>
  </div>
</header>

<main class="page">
  <div class="wrapper">

    <div class="page-head">
      <div>
        <h1>Ajouter un membre</h1>
        <p class="sub">Créez un nouveau compte membre</p>
      </div>
      <a href="index.php?page=members" class="btn btn-secondary">← Retour</a>
    </div>

    <?php if (!empty($errors)): ?>
      <ul class="error-list">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <div class="form-card">
      <form method="POST" action="index.php?page=members&action=add" novalidate>

        <div class="form-grid">

          <p class="section-label">Informations personnelles</p>

          <div class="form-group full">
            <label for="nom">Nom complet *</label>
            <input type="text" id="nom" name="nom" required
                   placeholder="Ex : Alice Dupont"
                   value="<?= htmlspecialchars($old['nom'] ?? '') ?>">
          </div>

          <div class="form-group full">
            <label for="email">Adresse email *</label>
            <input type="email" id="email" name="email" required
                   placeholder="alice@exemple.com"
                   value="<?= htmlspecialchars($old['email'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label for="telephone">Téléphone</label>
            <input type="tel" id="telephone" name="telephone"
                   placeholder="06 12 34 56 78"
                   value="<?= htmlspecialchars($old['telephone'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label for="dateInscription">Date d'inscription</label>
            <input type="date" id="dateInscription" name="dateInscription"
                   value="<?= htmlspecialchars($old['dateInscription'] ?? date('Y-m-d')) ?>">
          </div>

          <div class="form-group full">
            <label for="adresse">Adresse postale</label>
            <input type="text" id="adresse" name="adresse"
                   placeholder="12 rue de la Paix, 75001 Paris"
                   value="<?= htmlspecialchars($old['adresse'] ?? '') ?>">
          </div>

          <p class="section-label">Accès & sécurité</p>

          <div class="form-group full">
            <label for="motDePasse">Mot de passe *</label>
            <div class="password-wrap">
              <input type="password" id="motDePasse" name="motDePasse" required
                     placeholder="6 caractères minimum"
                     autocomplete="new-password">
              <button type="button" class="toggle-pwd" onclick="togglePwd('motDePasse', this)" title="Afficher/masquer">👁</button>
            </div>
            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
            <span class="field-hint" id="strengthLabel">Entrez un mot de passe</span>
          </div>

        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">✓ Créer le membre</button>
          <a href="index.php?page=members" class="btn btn-secondary">Annuler</a>
        </div>

      </form>
    </div>

  </div>
</main>

<script>
function togglePwd(fieldId, btn) {
  const f = document.getElementById(fieldId);
  f.type  = f.type === 'password' ? 'text' : 'password';
  btn.textContent = f.type === 'password' ? '👁' : '🙈';
}

// Indicateur de force du mot de passe
document.getElementById('motDePasse').addEventListener('input', function() {
  const v = this.value;
  let score = 0;
  if (v.length >= 6)  score++;
  if (v.length >= 10) score++;
  if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
  if (/\d/.test(v))   score++;
  if (/[^a-zA-Z0-9]/.test(v)) score++;

  const fill   = document.getElementById('strengthFill');
  const label  = document.getElementById('strengthLabel');
  const levels = [
    { pct:'0%',   color:'transparent', txt:'Entrez un mot de passe' },
    { pct:'25%',  color:'#e74c3c',     txt:'Très faible' },
    { pct:'50%',  color:'#e67e22',     txt:'Faible' },
    { pct:'75%',  color:'#f1c40f',     txt:'Moyen' },
    { pct:'90%',  color:'#2ecc71',     txt:'Fort' },
    { pct:'100%', color:'#27ae60',     txt:'Très fort' },
  ];
  const l = levels[Math.min(score, 5)];
  fill.style.width      = v.length ? l.pct  : '0%';
  fill.style.background = l.color;
  label.textContent     = v.length ? l.txt  : levels[0].txt;
});
</script>
</body>
</html>
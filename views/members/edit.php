<?php
// views/members/edit.php
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Modifier un membre — Bibliothèque</title>
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
    }
    .section-label {
      font-size: .7rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .1em; color: var(--ink-muted);
      grid-column: 1/-1; margin-top: 8px;
      padding-bottom: 6px; border-bottom: 1px solid var(--border);
    }
    .info-banner {
      display: flex; align-items: center; gap: 14px;
      background: var(--paper); border: 1px solid var(--border);
      border-radius: 10px; padding: 14px 18px; margin-bottom: 28px;
    }
    .info-banner .avatar {
      width: 46px; height: 46px; border-radius: 50%;
      background: var(--ink); color: var(--white);
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem; font-weight: 700; flex-shrink: 0;
    }
    .info-banner .meta { display: flex; flex-direction: column; gap: 2px; }
    .info-banner .meta strong { font-size: .95rem; }
    .info-banner .meta span  { font-size: .8rem; color: var(--ink-muted); }
    .info-banner .badges { margin-left: auto; display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
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
        <h1>Modifier un membre</h1>
        <p class="sub">ID #<?= $member['idMembre'] ?></p>
      </div>
      <a href="index.php?page=members" class="btn btn-secondary">← Retour</a>
    </div>

    <!-- Carte résumé membre -->
    <?php
      $words    = explode(' ', trim($member['nom']));
      $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
    ?>
    <div class="info-banner">
      <div class="avatar"><?= $initials ?></div>
      <div class="meta">
        <strong><?= htmlspecialchars($member['nom']) ?></strong>
        <span>Inscrit le <?= date('d/m/Y', strtotime($member['dateInscription'])) ?></span>
      </div>
      <div class="badges">
        <?php if ((int)($member['emprunts_en_cours'] ?? 0) > 0): ?>
          <span class="badge badge-amber"><?= (int)$member['emprunts_en_cours'] ?> emprunt(s) en cours</span>
        <?php endif; ?>
        <span class="badge" style="background:#ececf5;color:var(--ink-soft)">
          <?= (int)($member['total_emprunts'] ?? 0) ?> emprunt(s) au total
        </span>
      </div>
    </div>

    <?php if (!empty($errors)): ?>
      <ul class="error-list">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <div class="form-card">
      <form method="POST"
            action="index.php?page=members&action=edit&id=<?= $member['idMembre'] ?>"
            novalidate>

        <div class="form-grid">

          <p class="section-label">Informations personnelles</p>

          <div class="form-group full">
            <label for="nom">Nom complet *</label>
            <input type="text" id="nom" name="nom" required
                   value="<?= htmlspecialchars($member['nom']) ?>">
          </div>

          <div class="form-group full">
            <label for="email">Adresse email *</label>
            <input type="email" id="email" name="email" required
                   value="<?= htmlspecialchars($member['email']) ?>">
          </div>

          <div class="form-group">
            <label for="telephone">Téléphone</label>
            <input type="tel" id="telephone" name="telephone"
                   value="<?= htmlspecialchars($member['telephone'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label for="dateInscription">Date d'inscription</label>
            <input type="date" id="dateInscription" name="dateInscription"
                   value="<?= htmlspecialchars($member['dateInscription']) ?>" readonly
                   style="background:var(--paper);cursor:not-allowed;color:var(--ink-muted)">
            <span class="field-hint">Non modifiable après création</span>
          </div>

          <div class="form-group full">
            <label for="adresse">Adresse postale</label>
            <input type="text" id="adresse" name="adresse"
                   value="<?= htmlspecialchars($member['adresse'] ?? '') ?>">
          </div>

          <p class="section-label">Changer le mot de passe</p>

          <div class="form-group full">
            <label for="motDePasse">Nouveau mot de passe <span style="font-weight:400;text-transform:none;letter-spacing:0">(laisser vide = inchangé)</span></label>
            <div class="password-wrap">
              <input type="password" id="motDePasse" name="motDePasse"
                     placeholder="Laisser vide pour ne pas changer"
                     autocomplete="new-password">
              <button type="button" class="toggle-pwd" onclick="togglePwd('motDePasse', this)" title="Afficher/masquer">👁</button>
            </div>
            <span class="field-hint">6 caractères minimum si renseigné</span>
          </div>

        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">✓ Enregistrer les modifications</button>
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
</script>
</body>
</html>
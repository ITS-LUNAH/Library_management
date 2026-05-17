<?php
// views/borrows/borrow.php

// Date d'emprunt par défaut = aujourd'hui
$defaultDateEmprunt = date('Y-m-d');
// Date retour suggérée = +14 jours
$defaultDateRetour  = date('Y-m-d', strtotime('+14 days'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nouvel emprunt — Bibliothèque</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .section-label {
      font-size: .7rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .1em; color: var(--ink-muted);
      grid-column: 1/-1; margin-top: 8px;
      padding-bottom: 6px; border-bottom: 1px solid var(--border);
    }
    .preview-card {
      display: none;
      grid-column: 1/-1;
      background: var(--paper); border: 1px solid var(--border);
      border-radius: 10px; padding: 14px 18px;
      gap: 16px; align-items: flex-start;
    }
    .preview-card.visible { display: flex; }
    .preview-card .icon-circle {
      width: 42px; height: 42px; border-radius: 50%;
      background: var(--ink); color: var(--white);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem; flex-shrink: 0;
    }
    .preview-card .details { flex: 1; }
    .preview-card .details strong { display: block; font-size: .95rem; }
    .preview-card .details span   { font-size: .82rem; color: var(--ink-muted); }
    .preview-card .avail-badge {
      font-size: .75rem; font-weight: 700; padding: 3px 10px;
      border-radius: 12px; align-self: center; flex-shrink: 0;
    }

    .duration-row {
      grid-column: 1/-1;
      display: flex; gap: 10px; flex-wrap: wrap;
    }
    .quick-duration {
      padding: 6px 14px; border-radius: 20px; font-size: .8rem; font-weight: 600;
      border: 1.5px solid var(--border); background: var(--white); color: var(--ink-soft);
      cursor: pointer; transition: all .15s;
    }
    .quick-duration:hover {
      border-color: var(--accent); color: var(--accent);
    }
    .summary-box {
      grid-column: 1/-1;
      background: #edfaf3; border: 1px solid #b2dfcb;
      border-radius: 10px; padding: 14px 18px;
      font-size: .875rem; color: #1a6b3c;
      display: none;
    }
    .summary-box.visible { display: block; }
    .summary-box strong { display: block; margin-bottom: 4px; font-size: .95rem; }
  </style>
</head>
<body>

<header class="site-header">
  <div class="inner">
    <a href="index.php" class="brand">Biblio<span>thèque</span></a>
    <nav class="nav">
      <a href="index.php?page=books">Livres</a>
      <a href="index.php?page=members">Membres</a>
      <a href="index.php?page=borrows" class="active">Emprunts</a>
    </nav>
  </div>
</header>

<main class="page">
  <div class="wrapper">

    <div class="page-head">
      <div>
        <h1>Nouvel emprunt</h1>
        <p class="sub">Enregistrer un emprunt de livre</p>
      </div>
      <a href="index.php?page=borrows" class="btn btn-secondary">← Retour</a>
    </div>

    <?php if (!empty($errors)): ?>
      <ul class="error-list">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <div class="form-card" style="max-width:700px">
      <form method="POST" action="index.php?page=borrows&action=borrow" novalidate>

        <div class="form-grid">

          <!-- ── Sélection membre ── -->
          <p class="section-label">Membre emprunteur</p>

          <div class="form-group full">
            <label for="idMembre">Membre *</label>
            <select id="idMembre" name="idMembre" required onchange="updateMemberPreview()">
              <option value="">— Sélectionner un membre —</option>
              <?php foreach ($members as $m): ?>
                <option value="<?= $m['idMembre'] ?>"
                  data-email="<?= htmlspecialchars($m['email']) ?>"
                  <?= (int)($old['idMembre'] ?? 0) === (int)$m['idMembre'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($m['nom']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Prévisualisation membre -->
          <div class="preview-card" id="memberPreview">
            <div class="icon-circle">👤</div>
            <div class="details">
              <strong id="previewMemberName">—</strong>
              <span id="previewMemberEmail">—</span>
            </div>
          </div>

          <!-- ── Sélection livre ── -->
          <p class="section-label">Livre à emprunter</p>

          <div class="form-group full">
            <label for="idLivre">Livre disponible *</label>
            <select id="idLivre" name="idLivre" required onchange="updateBookPreview()">
              <option value="">— Sélectionner un livre —</option>
              <?php foreach ($books as $l): ?>
                <option value="<?= $l['idLivre'] ?>"
                  data-auteur="<?= htmlspecialchars($l['auteur']) ?>"
                  <?= (int)($old['idLivre'] ?? 0) === (int)$l['idLivre'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($l['titre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (empty($books)): ?>
              <span class="field-hint" style="color:var(--accent)">
                ⚠ Aucun livre disponible actuellement.
              </span>
            <?php endif; ?>
          </div>

          <!-- Prévisualisation livre -->
          <div class="preview-card" id="bookPreview">
            <div class="icon-circle">📖</div>
            <div class="details">
              <strong id="previewBookTitle">—</strong>
              <span id="previewBookAuteur">—</span>
            </div>
            <span class="avail-badge badge badge-green">Disponible</span>
          </div>

          <!-- ── Dates ── -->
          <p class="section-label">Dates</p>

          <div class="form-group">
            <label for="dateEmprunt">Date d'emprunt *</label>
            <input type="date" id="dateEmprunt" name="dateEmprunt" required
                   value="<?= htmlspecialchars($old['dateEmprunt'] ?? $defaultDateEmprunt) ?>"
                   onchange="updateSummary()">
          </div>

          <div class="form-group">
            <label for="dateRetourPrevue">Date de retour prévue *</label>
            <input type="date" id="dateRetourPrevue" name="dateRetourPrevue" required
                   value="<?= htmlspecialchars($old['dateRetourPrevue'] ?? $defaultDateRetour) ?>"
                   onchange="updateSummary()">
          </div>

          <!-- Raccourcis durée -->
          <div class="duration-row">
            <span style="font-size:.8rem;color:var(--ink-muted);align-self:center">Durée rapide :</span>
            <button type="button" class="quick-duration" onclick="setDuration(7)">7 jours</button>
            <button type="button" class="quick-duration" onclick="setDuration(14)">14 jours</button>
            <button type="button" class="quick-duration" onclick="setDuration(21)">21 jours</button>
            <button type="button" class="quick-duration" onclick="setDuration(30)">1 mois</button>
          </div>

          <!-- Récapitulatif -->
          <div class="summary-box" id="summaryBox">
            <strong>📋 Récapitulatif de l'emprunt</strong>
            <span id="summaryText"></span>
          </div>

        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary"
                  <?= empty($books) ? 'disabled style="opacity:.5;cursor:not-allowed"' : '' ?>>
            ✓ Enregistrer l'emprunt
          </button>
          <a href="index.php?page=borrows" class="btn btn-secondary">Annuler</a>
        </div>

      </form>
    </div>

  </div>
</main>

<script>
// ── Prévisualisation membre ───────────────────────────────────────────
function updateMemberPreview() {
  const sel = document.getElementById('idMembre');
  const opt = sel.options[sel.selectedIndex];
  const card = document.getElementById('memberPreview');
  if (sel.value) {
    document.getElementById('previewMemberName').textContent  = opt.textContent.trim();
    document.getElementById('previewMemberEmail').textContent = opt.dataset.email || '';
    card.classList.add('visible');
  } else {
    card.classList.remove('visible');
  }
  updateSummary();
}

// ── Prévisualisation livre ────────────────────────────────────────────
function updateBookPreview() {
  const sel = document.getElementById('idLivre');
  const opt = sel.options[sel.selectedIndex];
  const card = document.getElementById('bookPreview');
  if (sel.value) {
    document.getElementById('previewBookTitle').textContent  = opt.textContent.trim();
    document.getElementById('previewBookAuteur').textContent = opt.dataset.auteur || '';
    card.classList.add('visible');
  } else {
    card.classList.remove('visible');
  }
  updateSummary();
}

// ── Raccourcis durée ──────────────────────────────────────────────────
function setDuration(days) {
  const base   = document.getElementById('dateEmprunt').value;
  if (!base) return;
  const retour = new Date(base);
  retour.setDate(retour.getDate() + days);
  document.getElementById('dateRetourPrevue').value = retour.toISOString().split('T')[0];
  updateSummary();
}

// ── Récapitulatif dynamique ───────────────────────────────────────────
function updateSummary() {
  const membre  = document.getElementById('idMembre').value;
  const livre   = document.getElementById('idLivre').value;
  const depart  = document.getElementById('dateEmprunt').value;
  const retour  = document.getElementById('dateRetourPrevue').value;
  const box     = document.getElementById('summaryBox');
  const txt     = document.getElementById('summaryText');

  if (!membre || !livre || !depart || !retour) {
    box.classList.remove('visible'); return;
  }

  const membreName = document.getElementById('idMembre')
                       .options[document.getElementById('idMembre').selectedIndex]
                       .textContent.trim();
  const livreName  = document.getElementById('idLivre')
                       .options[document.getElementById('idLivre').selectedIndex]
                       .textContent.trim();

  const d1 = new Date(depart), d2 = new Date(retour);
  if (d2 <= d1) {
    txt.textContent = '⚠ La date de retour doit être après la date d\'emprunt.';
    box.style.background = '#fef0f0';
    box.style.borderColor = '#fcd4d4';
    box.style.color = 'var(--accent)';
  } else {
    const jours = Math.round((d2 - d1) / 86400000);
    txt.textContent = membreName + ' empruntera « ' + livreName + ' » '
      + 'du ' + formatDate(d1) + ' au ' + formatDate(d2)
      + ' (' + jours + ' jour' + (jours > 1 ? 's' : '') + ').';
    box.style.background  = '#edfaf3';
    box.style.borderColor = '#b2dfcb';
    box.style.color       = '#1a6b3c';
  }
  box.classList.add('visible');
}

function formatDate(d) {
  return d.toLocaleDateString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric' });
}

// Init si valeurs déjà saisies (erreur de validation)
window.addEventListener('DOMContentLoaded', () => {
  updateMemberPreview();
  updateBookPreview();
  updateSummary();
});
</script>
</body>
</html>
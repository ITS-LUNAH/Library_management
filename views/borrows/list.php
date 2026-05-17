<?php
// views/borrows/list.php
$flash = flash();

// Couleurs et labels par statut
$statutConfig = [
    'en_cours'  => ['badge' => 'badge-green',  'label' => 'En cours',   'icon' => '📖'],
    'en_retard' => ['badge' => 'badge-red',    'label' => 'En retard',  'icon' => '⚠️'],
    'retourne'  => ['badge' => 'badge-muted',  'label' => 'Retourné',   'icon' => '✓'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Emprunts — Bibliothèque</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .badge-muted { background: #f0f0f5; color: var(--ink-muted); }

    .filter-tabs {
      display: flex; gap: 6px; margin-bottom: 22px; flex-wrap: wrap;
    }
    .filter-tab {
      padding: 7px 16px; border-radius: 20px; font-size: .82rem; font-weight: 600;
      text-decoration: none; border: 1.5px solid var(--border);
      color: var(--ink-soft); background: var(--white);
      transition: all .15s;
    }
    .filter-tab:hover  { border-color: var(--ink-soft); color: var(--ink); }
    .filter-tab.active { background: var(--ink); color: var(--white); border-color: var(--ink); }
    .filter-tab.danger.active { background: var(--accent); border-color: var(--accent); }

    .book-cell  { display: flex; flex-direction: column; }
    .book-title { font-weight: 600; font-size: .9rem; }
    .book-author{ font-size: .78rem; color: var(--ink-muted); }

    .member-cell .name  { font-weight: 600; font-size: .9rem; }
    .member-cell .email { font-size: .78rem; color: var(--ink-muted); }

    .dates-cell { display: flex; flex-direction: column; gap: 2px; font-size: .82rem; }
    .dates-cell .lbl { color: var(--ink-muted); font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; }

    .retard-chip {
      display: inline-flex; align-items: center; gap: 4px;
      background: #fef0f0; color: var(--accent);
      font-size: .75rem; font-weight: 700;
      padding: 2px 8px; border-radius: 12px;
      margin-top: 4px;
    }
    .amende-chip {
      display: inline-flex; align-items: center; gap: 4px;
      background: #fff8e1; color: #b7770d;
      font-size: .75rem; font-weight: 700;
      padding: 2px 8px; border-radius: 12px; margin-top: 4px;
    }

    .stat-card { border-top: 3px solid var(--accent); }
    .stat-card.green { border-top-color: var(--green); }
    .stat-card.amber { border-top-color: var(--amber); }
    .stat-card.muted { border-top-color: var(--ink-muted); }

    .return-btn {
      white-space: nowrap;
    }

    @media (max-width: 780px) {
      .col-dates, .col-member { display: none; }
    }
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
      <a href="index.php?page=fines">Amendes</a>
    </nav>
  </div>
</header>

<main class="page">
  <div class="wrapper">

    <?php if ($flash): ?>
      <div class="flash flash-<?= htmlspecialchars($flash['type']) ?>">
        <?= $flash['type'] === 'success' ? '✓' : '⚠' ?>
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <div class="page-head">
      <div>
        <h1>Gestion des emprunts</h1>
        <p class="sub"><?= count($borrows) ?> emprunt(s) affiché(s)</p>
      </div>
      <a href="index.php?page=borrows&action=borrow" class="btn btn-primary">+ Nouvel emprunt</a>
    </div>

    <!-- Stats -->
    <div class="stats">
      <div class="stat-card">
        <div class="num"><?= (int)$stats['total'] ?></div>
        <div class="lbl">Total</div>
      </div>
      <div class="stat-card green">
        <div class="num"><?= (int)$stats['en_cours'] ?></div>
        <div class="lbl">En cours</div>
      </div>
      <div class="stat-card" style="border-top-color:var(--accent)">
        <div class="num"><?= (int)$stats['en_retard'] ?></div>
        <div class="lbl">En retard</div>
      </div>
      <div class="stat-card muted">
        <div class="num"><?= (int)$stats['retournes'] ?></div>
        <div class="lbl">Retournés</div>
      </div>
    </div>

    <!-- Filtres par statut + recherche -->
    <div style="display:flex; gap:16px; flex-wrap:wrap; align-items:center; margin-bottom:22px;">
      <div class="filter-tabs" style="margin-bottom:0">
        <?php
          $currentStatut = $_GET['statut'] ?? '';
          $currentSearch = $_GET['search'] ?? '';
          $qs = $currentSearch ? '&search=' . urlencode($currentSearch) : '';
        ?>
        <a href="index.php?page=borrows<?= $qs ?>"
           class="filter-tab <?= $currentStatut === '' ? 'active' : '' ?>">Tous</a>
        <a href="index.php?page=borrows&statut=en_cours<?= $qs ?>"
           class="filter-tab <?= $currentStatut === 'en_cours' ? 'active' : '' ?>">En cours</a>
        <a href="index.php?page=borrows&statut=en_retard<?= $qs ?>"
           class="filter-tab danger <?= $currentStatut === 'en_retard' ? 'active' : '' ?>">En retard</a>
        <a href="index.php?page=borrows&statut=retourne<?= $qs ?>"
           class="filter-tab <?= $currentStatut === 'retourne' ? 'active' : '' ?>">Retournés</a>
      </div>

      <form method="GET" action="index.php" class="search-bar" style="margin:0;flex:1;min-width:200px">
        <input type="hidden" name="page"   value="borrows">
        <?php if ($currentStatut): ?>
          <input type="hidden" name="statut" value="<?= htmlspecialchars($currentStatut) ?>">
        <?php endif; ?>
        <input type="text" name="search" placeholder="Membre ou titre du livre…"
               value="<?= htmlspecialchars($currentSearch) ?>">
        <button type="submit" class="btn btn-secondary">Chercher</button>
        <?php if ($currentSearch): ?>
          <a href="index.php?page=borrows<?= $currentStatut ? '&statut='.$currentStatut : '' ?>"
             class="btn btn-secondary">✕</a>
        <?php endif; ?>
      </form>
    </div>

    <?php if (empty($borrows)): ?>
      <div class="table-wrap">
        <div class="empty-state">
          <div class="icon">📋</div>
          <h3>Aucun emprunt trouvé</h3>
          <p>Modifiez vos filtres ou enregistrez un nouvel emprunt.</p>
        </div>
      </div>
    <?php else: ?>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Livre</th>
            <th class="col-member">Membre</th>
            <th class="col-dates">Dates</th>
            <th style="text-align:center">Statut</th>
            <th style="text-align:right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($borrows as $b): ?>
          <?php
            $cfg       = $statutConfig[$b['statut']] ?? $statutConfig['retourne'];
            $joursRet  = (int) $b['jours_retard'];
            $estRetard = $joursRet > 0 && $b['statut'] !== 'retourne';
          ?>
          <tr>
            <!-- ID -->
            <td style="color:var(--ink-muted);font-size:.8rem">#<?= $b['idEmprunt'] ?></td>

            <!-- Livre -->
            <td>
              <div class="book-cell">
                <span class="book-title"><?= htmlspecialchars($b['livre_titre']) ?></span>
                <span class="book-author"><?= htmlspecialchars($b['livre_auteur']) ?></span>
              </div>
            </td>

            <!-- Membre -->
            <td class="col-member">
              <div class="member-cell">
                <span class="name"><?= htmlspecialchars($b['membre_nom']) ?></span>
                <span class="email"><?= htmlspecialchars($b['membre_email']) ?></span>
              </div>
            </td>

            <!-- Dates -->
            <td class="col-dates">
              <div class="dates-cell">
                <span><span class="lbl">Emprunté</span> <?= date('d/m/Y', strtotime($b['dateEmprunt'])) ?></span>
                <span>
                  <span class="lbl">Retour prévu</span>
                  <?= date('d/m/Y', strtotime($b['dateRetourPrevue'])) ?>
                </span>
                <?php if ($b['dateRetourReelle']): ?>
                  <span><span class="lbl">Retourné le</span> <?= date('d/m/Y', strtotime($b['dateRetourReelle'])) ?></span>
                <?php endif; ?>
                <?php if ($estRetard): ?>
                  <span class="retard-chip">⚠ <?= $joursRet ?> j. de retard</span>
                <?php endif; ?>
                <?php if ($b['amende_id'] && $b['amende_statut'] === 'impayee'): ?>
                  <span class="amende-chip">💰 <?= number_format($b['amende_montant'], 2) ?> DH impayée</span>
                <?php endif; ?>
              </div>
            </td>

            <!-- Statut -->
            <td style="text-align:center">
              <span class="badge <?= $cfg['badge'] ?>">
                <?= $cfg['icon'] ?> <?= $cfg['label'] ?>
              </span>
            </td>

            <!-- Action -->
            <td style="text-align:right">
              <?php if (in_array($b['statut'], ['en_cours', 'en_retard'])): ?>
                <button type="button" class="btn btn-primary btn-sm return-btn"
                        onclick="confirmReturn(<?= $b['idEmprunt'] ?>, '<?= htmlspecialchars(addslashes($b['livre_titre'])) ?>', '<?= htmlspecialchars(addslashes($b['membre_nom'])) ?>', <?= $joursRet ?>)">
                  ↩ Retourner
                </button>
              <?php elseif ($b['amende_id'] && $b['amende_statut'] === 'impayee'): ?>
                <span class="badge badge-amber" style="font-size:.75rem">Amende due</span>
              <?php else: ?>
                <span style="color:var(--ink-muted);font-size:.82rem">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php endif; ?>

  </div>
</main>

<!-- ── Modale de confirmation retour ───────────────────────────── -->
<div class="modal-overlay" id="returnModal">
  <div class="modal">
    <h3>Confirmer le retour</h3>
    <p id="returnMsg"></p>

    <div id="retardWarn" style="display:none;
         background:#fef0f0; border:1px solid #fcd4d4; border-left:4px solid var(--accent);
         border-radius:8px; padding:12px 16px; margin: -4px 0 16px;
         font-size:.875rem; color:var(--accent);">
    </div>

    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="closeReturnModal()">Annuler</button>
      <form method="POST" action="index.php?page=borrows&action=return" id="returnForm">
        <input type="hidden" name="id" id="returnId">
        <button type="submit" class="btn btn-primary" id="returnBtn">✓ Confirmer le retour</button>
      </form>
    </div>
  </div>
</div>

<script>
function confirmReturn(id, titre, membre, joursRetard) {
  document.getElementById('returnId').value = id;
  document.getElementById('returnMsg').textContent =
    'Retour du livre « ' + titre + ' » emprunté par ' + membre + '.';

  const warn = document.getElementById('retardWarn');
  if (joursRetard > 0) {
    const montant = (joursRetard * 5).toFixed(2);
    warn.style.display = 'block';
    warn.innerHTML = '⚠️ <strong>' + joursRetard + ' jour(s) de retard</strong> — '
                   + 'Une amende de <strong>' + montant + ' DH</strong> sera générée automatiquement.';
  } else {
    warn.style.display = 'none';
  }

  document.getElementById('returnModal').classList.add('open');
}
function closeReturnModal() {
  document.getElementById('returnModal').classList.remove('open');
}
document.getElementById('returnModal').addEventListener('click', function(e) {
  if (e.target === this) closeReturnModal();
});
</script>
</body>
</html>
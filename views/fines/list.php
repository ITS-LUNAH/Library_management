<?php
// views/fines/list.php
$flash = flash();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Amendes — Bibliothèque</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .badge-muted  { background: #f0f0f5; color: var(--ink-muted); }
    .badge-payee  { background: #edfaf3; color: #1e8449; }
    .badge-impayee{ background: #fef0f0; color: var(--accent); }

    .filter-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
    .filter-tab {
      padding: 7px 16px; border-radius: 20px; font-size: .82rem; font-weight: 600;
      text-decoration: none; border: 1.5px solid var(--border);
      color: var(--ink-soft); background: var(--white); transition: all .15s;
    }
    .filter-tab:hover { border-color: var(--ink-soft); color: var(--ink); }
    .filter-tab.active { background: var(--ink); color: var(--white); border-color: var(--ink); }
    .filter-tab.danger.active { background: var(--accent); border-color: var(--accent); }
    .filter-tab.success.active { background: var(--green); border-color: var(--green); }

    .montant-cell { font-family: var(--font-head); font-size: 1.05rem; font-weight: 700; }
    .montant-cell.impayee { color: var(--accent); }
    .montant-cell.payee   { color: var(--green); }

    .breakdown {
      font-size: .75rem; color: var(--ink-muted);
      background: var(--paper); padding: 2px 7px;
      border-radius: 4px; display: inline-block; margin-top: 3px;
    }

    .book-cell  { display: flex; flex-direction: column; }
    .book-title { font-weight: 600; font-size: .9rem; }
    .book-author{ font-size: .78rem; color: var(--ink-muted); }
    .member-name{ font-weight: 600; font-size: .9rem; }
    .member-email{ font-size: .78rem; color: var(--ink-muted); }

    .stat-card.red    { border-top: 3px solid var(--accent); }
    .stat-card.green  { border-top: 3px solid var(--green); }
    .stat-card.amber  { border-top: 3px solid var(--amber); }
    .stat-card.default{ border-top: 3px solid var(--ink); }

    .total-banner {
      background: linear-gradient(135deg, var(--ink) 0%, #2c2c50 100%);
      color: var(--white); border-radius: var(--radius);
      padding: 20px 28px; margin-bottom: 28px;
      display: flex; align-items: center; gap: 32px; flex-wrap: wrap;
    }
    .total-banner .item { display: flex; flex-direction: column; }
    .total-banner .num  { font-family: var(--font-head); font-size: 1.7rem; font-weight: 700; line-height: 1; }
    .total-banner .lbl  { font-size: .72rem; opacity: .7; text-transform: uppercase; letter-spacing: .06em; margin-top: 3px; }
    .total-banner .sep  { width: 1px; height: 40px; background: rgba(255,255,255,.2); }
    .total-banner .due  { color: #ff8080; }
    .total-banner .recv { color: #7cf5a7; }

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
      <a href="index.php?page=borrows">Emprunts</a>
      <a href="index.php?page=fines" class="active">Amendes</a>
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
        <h1>Gestion des amendes</h1>
        <p class="sub"><?= count($fines) ?> amende(s) — tarif : <?= number_format(TARIF_JOURNALIER, 2) ?> DH / jour</p>
      </div>
    </div>

    <!-- Bannière financière -->
    <div class="total-banner">
      <div class="item">
        <span class="num"><?= (int)$stats['total'] ?></span>
        <span class="lbl">Total amendes</span>
      </div>
      <div class="sep"></div>
      <div class="item">
        <span class="num due"><?= number_format($stats['montant_du'], 2) ?> DH</span>
        <span class="lbl">Montant dû (<?= (int)$stats['impayees'] ?> impayée<?= $stats['impayees'] > 1 ? 's' : '' ?>)</span>
      </div>
      <div class="sep"></div>
      <div class="item">
        <span class="num recv"><?= number_format($stats['montant_percu'], 2) ?> DH</span>
        <span class="lbl">Montant perçu (<?= (int)$stats['payees'] ?> payée<?= $stats['payees'] > 1 ? 's' : '' ?>)</span>
      </div>
      <div class="sep"></div>
      <div class="item">
        <span class="num"><?= number_format($stats['total_montant'], 2) ?> DH</span>
        <span class="lbl">Total généré</span>
      </div>
    </div>

    <!-- Stats cartes -->
    <div class="stats" style="margin-bottom:22px">
      <div class="stat-card red">
        <div class="num"><?= (int)$stats['impayees'] ?></div>
        <div class="lbl">Impayées</div>
      </div>
      <div class="stat-card green">
        <div class="num"><?= (int)$stats['payees'] ?></div>
        <div class="lbl">Payées</div>
      </div>
    </div>

    <!-- Filtres + recherche -->
    <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:center;margin-bottom:22px">
      <div class="filter-tabs">
        <?php
          $currentStatut = $_GET['statut'] ?? '';
          $currentSearch = $_GET['search'] ?? '';
          $qs = $currentSearch ? '&search=' . urlencode($currentSearch) : '';
        ?>
        <a href="index.php?page=fines<?= $qs ?>"
           class="filter-tab <?= $currentStatut === '' ? 'active' : '' ?>">Toutes</a>
        <a href="index.php?page=fines&statut=impayee<?= $qs ?>"
           class="filter-tab danger <?= $currentStatut === 'impayee' ? 'active' : '' ?>">Impayées</a>
        <a href="index.php?page=fines&statut=payee<?= $qs ?>"
           class="filter-tab success <?= $currentStatut === 'payee' ? 'active' : '' ?>">Payées</a>
      </div>

      <form method="GET" action="index.php" class="search-bar" style="margin:0;flex:1;min-width:200px">
        <input type="hidden" name="page" value="fines">
        <?php if ($currentStatut): ?>
          <input type="hidden" name="statut" value="<?= htmlspecialchars($currentStatut) ?>">
        <?php endif; ?>
        <input type="text" name="search" placeholder="Membre ou titre du livre…"
               value="<?= htmlspecialchars($currentSearch) ?>">
        <button type="submit" class="btn btn-secondary">Chercher</button>
        <?php if ($currentSearch): ?>
          <a href="index.php?page=fines<?= $currentStatut ? '&statut='.$currentStatut : '' ?>"
             class="btn btn-secondary">✕</a>
        <?php endif; ?>
      </form>
    </div>

    <?php if (empty($fines)): ?>
      <div class="table-wrap">
        <div class="empty-state">
          <div class="icon">✅</div>
          <h3>Aucune amende trouvée</h3>
          <p>Aucune amende ne correspond aux filtres sélectionnés.</p>
        </div>
      </div>
    <?php else: ?>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Membre</th>
            <th>Livre</th>
            <th class="col-dates">Retard</th>
            <th style="text-align:right">Montant</th>
            <th style="text-align:center">Statut</th>
            <th style="text-align:right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($fines as $f): ?>
          <?php
            // Calcul d'affichage uniquement (dateRetourReelle jamais null ici car amendes liées à des retours)
          ?>
          <tr>
            <!-- ID -->
            <td style="color:var(--ink-muted);font-size:.8rem">#<?= $f['idAmende'] ?></td>

            <!-- Membre -->
            <td>
              <div class="book-cell">
                <span class="member-name"><?= htmlspecialchars($f['membre_nom']) ?></span>
                <span class="member-email"><?= htmlspecialchars($f['membre_email']) ?></span>
              </div>
            </td>

            <!-- Livre -->
            <td>
              <div class="book-cell">
                <span class="book-title"><?= htmlspecialchars($f['livre_titre']) ?></span>
                <span class="book-author"><?= htmlspecialchars($f['livre_auteur']) ?></span>
              </div>
            </td>

            <!-- Retard -->
            <td class="col-dates">
              <div style="display:flex;flex-direction:column;gap:3px;font-size:.82rem">
                <span>
                  <span style="color:var(--ink-muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.04em">Prévu</span>
                  <?= date('d/m/Y', strtotime($f['dateRetourPrevue'])) ?>
                </span>
                <span>
                  <span style="color:var(--ink-muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.04em">Rendu</span>
                  <?= $f['dateRetourReelle'] ? date('d/m/Y', strtotime($f['dateRetourReelle'])) : '<em style="color:var(--ink-muted)">Non retourné</em>' ?>
                </span>
                <span style="display:inline-flex;align-items:center;gap:4px;
                      background:#fef0f0;color:var(--accent);font-size:.75rem;
                      font-weight:700;padding:2px 8px;border-radius:12px;width:fit-content;margin-top:2px">
                  ⏱ <?= $f['nombreJoursRetard'] ?> jour<?= $f['nombreJoursRetard'] > 1 ? 's' : '' ?>
                </span>
              </div>
            </td>

            <!-- Montant -->
            <td style="text-align:right;vertical-align:middle">
              <div style="display:flex;flex-direction:column;align-items:flex-end;gap:3px">
                <span class="montant-cell <?= $f['statut'] ?>">
                  <?= number_format($f['montant'], 2) ?> DH
                </span>
                <span class="breakdown">
                  <?= $f['nombreJoursRetard'] ?> j × <?= number_format(TARIF_JOURNALIER, 2) ?> DH
                </span>
              </div>
            </td>

            <!-- Statut -->
            <td style="text-align:center;vertical-align:middle">
              <?php if ($f['statut'] === 'impayee'): ?>
                <span class="badge badge-impayee">Impayée</span>
              <?php else: ?>
                <div style="display:flex;flex-direction:column;align-items:center;gap:3px">
                  <span class="badge badge-payee">Payée</span>
                  <?php if ($f['datePaiement']): ?>
                    <span style="font-size:.72rem;color:var(--ink-muted)">
                      le <?= $f['datePaiement'] ? date('d/m/Y', strtotime($f['datePaiement'])) : '—' ?>
                    </span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </td>

            <!-- Actions -->
            <td style="text-align:right;vertical-align:middle">
              <div style="display:flex;gap:6px;justify-content:flex-end;align-items:center">
                <a href="index.php?page=fines&action=show&id=<?= $f['idAmende'] ?>"
                   class="btn btn-secondary btn-sm">Détail</a>
                <?php if ($f['statut'] === 'impayee'): ?>
                  <button type="button" class="btn btn-primary btn-sm"
                          onclick="confirmPay(<?= $f['idAmende'] ?>, '<?= htmlspecialchars(addslashes($f['membre_nom'])) ?>', '<?= number_format($f['montant'], 2) ?>')">
                    ✓ Payer
                  </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>

        <!-- Pied de tableau : total impayé -->
        <?php
          $totalDu = array_sum(array_column(
            array_filter($fines, fn($f) => $f['statut'] === 'impayee'),
            'montant'
          ));
        ?>
        <?php if ($totalDu > 0): ?>
        <tfoot>
          <tr style="background:var(--paper)">
            <td colspan="4" style="padding:12px 16px;font-weight:600;font-size:.85rem;color:var(--ink-soft)">
              Total restant dû (amendes impayées affichées)
            </td>
            <td style="text-align:right;padding:12px 16px">
              <span style="font-family:var(--font-head);font-size:1.1rem;font-weight:700;color:var(--accent)">
                <?= number_format($totalDu, 2) ?> DH
              </span>
            </td>
            <td colspan="2"></td>
          </tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>

    <?php endif; ?>
  </div>
</main>

<!-- Modale confirmation paiement -->
<div class="modal-overlay" id="payModal">
  <div class="modal">
    <h3>Enregistrer le paiement</h3>
    <p id="payMsg"></p>
    <div style="background:#edfaf3;border:1px solid #b2dfcb;border-radius:8px;
         padding:12px 16px;margin:0 0 20px;font-size:.875rem;color:#1a6b3c;">
      ✓ Cette action marquera l'amende comme payée à la date d'aujourd'hui.
    </div>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="closePayModal()">Annuler</button>
      <form method="POST" action="index.php?page=fines&action=pay" id="payForm">
        <input type="hidden" name="id" id="payId">
        <button type="submit" class="btn btn-primary">✓ Confirmer le paiement</button>
      </form>
    </div>
  </div>
</div>

<script>
function confirmPay(id, membre, montant) {
  document.getElementById('payId').value  = id;
  document.getElementById('payMsg').textContent =
    'Enregistrer le paiement de ' + montant + ' DH par ' + membre + ' ?';
  document.getElementById('payModal').classList.add('open');
}
function closePayModal() {
  document.getElementById('payModal').classList.remove('open');
}
document.getElementById('payModal').addEventListener('click', function(e) {
  if (e.target === this) closePayModal();
});
</script>
</body>
</html>
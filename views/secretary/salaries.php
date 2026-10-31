<?php
// views/secretary/salaries.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/FinanceService.php';

Auth::requireRole(['secretaire', 'directeur']);

$schoolId = Auth::schoolId();
$pdo = getDbConnection();

// Récupération de tous les professeurs de l'école
$stmtTeachers = $pdo->prepare("
    SELECT id, first_name, last_name, phone, email 
    FROM `users` 
    WHERE school_id = ? AND role = 'professeur' AND is_active = 1 
    ORDER BY last_name ASC
");
$stmtTeachers->execute([$schoolId]);
$teachers = $stmtTeachers->fetchAll();

$monthFilter = $_GET['month'] ?? null;
$yearFilter = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$salaries = FinanceService::getAllSalariesForSecretary($schoolId, $monthFilter, $yearFilter);

$totalPaid = 0;
$totalPending = 0;
foreach ($salaries as $s) {
    if ($s['payment_status'] === 'paye') {
        $totalPaid += (float)$s['net_salary'];
    } else {
        $totalPending += (float)$s['net_salary'];
    }
}

$pageTitle = "Gestion des Salaires des Professeurs";
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">💼 Gestion des Salaires des Professeurs</h1>
        <p class="page-subtitle">Établissement des fiches de paie, ordonnancement et règlement des émoluments</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="openModal('modal-new-salary')">
            ➕ Établir une Fiche de Paie
        </button>
    </div>
</div>

<!-- Modal Nouvelle Fiche de Paie -->
<div id="modal-new-salary" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); z-index:2000; align-items:center; justify-content:center; padding:16px;">
    <div style="background:var(--bg-card); border: 1px solid var(--border); border-radius:var(--radius-lg); width:100%; max-width:620px; padding:26px; box-shadow:var(--shadow-lg);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <h3 style="font-size:18px; font-weight:800; color:var(--text-main);">💼 Traitement du Salaire Enseignant</h3>
            <button onclick="closeModal('modal-new-salary')" style="background:none; border:none; font-size:22px; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>

        <form action="<?= baseUrl('index.php?action=manage_teacher_salary') ?>" method="POST" id="salaryForm">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <div class="form-group">
                <label class="form-label" for="teacher_id">Enseignant / Professeur Bénéficiaire *</label>
                <select name="teacher_id" id="teacher_id" class="form-control" required>
                    <option value="">-- Choisissez un enseignant --</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>">
                            M. <?= e($t['last_name']) ?> <?= e($t['first_name']) ?> (<?= e($t['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="month">Mois de Référence *</label>
                    <select name="month" id="month" class="form-control" required>
                        <?php foreach (['Octobre', 'Novembre', 'Décembre', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre'] as $m): ?>
                            <option value="<?= $m ?>"><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="year">Année *</label>
                    <input type="number" name="year" id="year" class="form-control" value="<?= date('Y') ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="base_salary">Salaire de Base (FCFA) *</label>
                    <input type="number" step="1000" name="base_salary" id="base_salary" class="form-control" value="380000" required oninput="calcNetSalary()">
                </div>

                <div class="form-group">
                    <label class="form-label" for="bonus">Primes & Heures Supp (FCFA)</label>
                    <input type="number" step="1000" name="bonus" id="bonus" class="form-control" value="0" oninput="calcNetSalary()">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="deductions">Retenues / Déductions (FCFA)</label>
                    <input type="number" step="1000" name="deductions" id="deductions" class="form-control" value="15000" oninput="calcNetSalary()">
                </div>

                <div class="form-group">
                    <label class="form-label">Salaire Net Calculé :</label>
                    <div id="net_salary_display" style="font-size:20px; font-weight:800; color:#1e3a8a; padding-top:6px;">
                        365 000 FCFA
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="payment_method">Mode de Paiement *</label>
                    <select name="payment_method" id="payment_method" class="form-control">
                        <option value="virement">Virement bancaire direct</option>
                        <option value="cheque">Chèque bancaire</option>
                        <option value="wave">Wave Pro Entreprise</option>
                        <option value="orange_money">Orange Money Pro</option>
                        <option value="especes">Espèces</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="payment_status">Statut du Règlement *</label>
                    <select name="payment_status" id="payment_status" class="form-control">
                        <option value="paye">Payé / Effectué</option>
                        <option value="en_attente">En cours / Ordonnancement</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="notes">Observations (N° Compte, Banque, etc.)</label>
                <input type="text" name="notes" id="notes" class="form-control" placeholder="ex: Compte CBAO n° 01293848102 - Virement exécuté">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:18px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-new-salary')">Annuler</button>
                <button type="submit" class="btn btn-primary">Valider & Enregistrer la Paie</button>
            </div>
        </form>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#ecfdf5; color:#059669;">💰</div>
        <div>
            <div class="stat-value"><?= formatMoney($totalPaid) ?></div>
            <div class="stat-label">Total Salaires Réglés</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#fffbeb; color:#d97706;">⏳</div>
        <div>
            <div class="stat-value"><?= formatMoney($totalPending) ?></div>
            <div class="stat-label">Salaires en Attente</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#eff6ff; color:#2563eb;">👨‍🏫</div>
        <div>
            <div class="stat-value"><?= count($teachers) ?></div>
            <div class="stat-label">Professeurs sous Contrat</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">📋 Récapitulatif des Fiches de Paie Enregistrées</h3>
    </div>
    <div class="card-body">
        <?php if (empty($salaries)): ?>
            <p style="text-align: center; color: var(--text-muted); padding: 30px;">Aucune fiche de paie enregistrée.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Réf. Fiche</th>
                            <th>Enseignant</th>
                            <th>Période</th>
                            <th>Base</th>
                            <th>Primes</th>
                            <th>Retenues</th>
                            <th>Net Perçu</th>
                            <th>Mode</th>
                            <th>Statut</th>
                            <th>Traité par</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salaries as $sal): ?>
                            <tr>
                                <td style="font-family: monospace; font-weight: 700;">
                                    <?= e($sal['payslip_reference']) ?>
                                </td>
                                <td>
                                    <strong>M. <?= e($sal['last_name']) ?></strong> <?= e($sal['first_name']) ?>
                                </td>
                                <td><strong><?= e($sal['month']) ?> <?= e($sal['year']) ?></strong></td>
                                <td><?= formatMoney($sal['base_salary']) ?></td>
                                <td style="color: #166534;">+ <?= formatMoney($sal['bonus']) ?></td>
                                <td style="color: #991b1b;">- <?= formatMoney($sal['deductions']) ?></td>
                                <td style="font-weight: 800; font-size: 15px; color: #1e3a8a;">
                                    <?= formatMoney($sal['net_salary']) ?>
                                </td>
                                <td><span class="badge badge-secondary"><?= strtoupper(e($sal['payment_method'])) ?></span></td>
                                <td>
                                    <span class="badge <?= $sal['payment_status'] === 'paye' ? 'badge-success' : 'badge-warning' ?>">
                                        <?= strtoupper(e($sal['payment_status'])) ?>
                                    </span>
                                </td>
                                <td style="font-size: 11px; color: var(--text-muted);">
                                    <?= $sal['proc_first_name'] ? 'Mme ' . e($sal['proc_last_name']) : 'Secrétariat' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function calcNetSalary() {
    const base = parseFloat(document.getElementById('base_salary').value) || 0;
    const bonus = parseFloat(document.getElementById('bonus').value) || 0;
    const deductions = parseFloat(document.getElementById('deductions').value) || 0;
    const net = Math.max(0, (base + bonus) - deductions);
    document.getElementById('net_salary_display').innerText = net.toLocaleString('fr-FR') + ' FCFA';
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

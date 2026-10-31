<?php
// views/teacher/salaries.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/FinanceService.php';

Auth::requireRole(['professeur']);

$teacherId = Auth::id();
$schoolId = Auth::schoolId();
$salaries = FinanceService::getTeacherAuthorizedSalaries($schoolId, $teacherId);
$pageTitle = "Ma Rémunération & Fiches de Paie Autorisées";

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">💰 Ma Rémunération Autorisée</h1>
        <p class="page-subtitle">Consultation confidentielle de vos bulletins de salaire et fiches de paie</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">📜 Historique de mes Bulletins de Salaire</h3>
    </div>
    <div class="card-body">
        <?php if (empty($salaries)): ?>
            <p style="text-align: center; color: var(--text-muted); padding: 30px;">
                Aucune fiche de paie n'a encore été enregistrée pour votre compte.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Référence Fiche</th>
                            <th>Période (Mois / Année)</th>
                            <th>Salaire de Base</th>
                            <th>Primes / Heures Supp</th>
                            <th>Retenues</th>
                            <th>Salaire Net Perçu</th>
                            <th>Date de Règlement</th>
                            <th>Mode</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salaries as $sal): ?>
                            <tr>
                                <td style="font-family: monospace; font-weight: 700;">
                                    <?= e($sal['payslip_reference']) ?>
                                </td>
                                <td>
                                    <strong><?= e($sal['month']) ?> <?= e($sal['year']) ?></strong>
                                </td>
                                <td><?= formatMoney($sal['base_salary'], $sal['currency']) ?></td>
                                <td style="color: #166534;">+ <?= formatMoney($sal['bonus'], $sal['currency']) ?></td>
                                <td style="color: #991b1b;">- <?= formatMoney($sal['deductions'], $sal['currency']) ?></td>
                                <td style="font-weight: 800; font-size: 16px; color: #1e3a8a;">
                                    <?= formatMoney($sal['net_salary'], $sal['currency']) ?>
                                </td>
                                <td><?= formatDate($sal['payment_date']) ?></td>
                                <td><span class="badge badge-secondary"><?= strtoupper(e($sal['payment_method'])) ?></span></td>
                                <td>
                                    <span class="badge <?= $sal['payment_status'] === 'paye' ? 'badge-success' : 'badge-warning' ?>">
                                        <?= strtoupper(e($sal['payment_status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="background: var(--bg-table-hover);">
    <div class="card-body">
        <div style="display: flex; gap: 14px; align-items: center;">
            <div style="font-size: 28px;">🔒</div>
            <div>
                <h4 style="font-size: 14px; font-weight: 700; color: var(--text-main);">Confidentialité & Cloisonnement Strict</h4>
                <p style="font-size: 13px; color: var(--text-muted); margin-top: 2px;">
                    Seules vos données salariales personnelles autorisées vous sont accessibles. Vous ne disposez d'aucun accès aux budgets généraux de l'établissement ou aux rémunérations d'autres collaborateurs.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

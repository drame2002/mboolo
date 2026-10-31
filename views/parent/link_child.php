<?php
// views/parent/link_child.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/ParentService.php';

Auth::requireRole(['parent']);

$parentId = Auth::id();
$schoolId = Auth::schoolId();
$school = Auth::school();
$children = ParentService::getChildren($schoolId, $parentId);
$pageTitle = "Associer un Enfant à mon Compte";

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">🔗 Associer un Enfant à votre Compte</h1>
        <p class="page-subtitle">Rapprochement sécurisé vérifié au sein de l'établissement : <strong><?= e($school['name']) ?></strong></p>
    </div>
    <div>
        <a href="<?= baseUrl('index.php?page=parent_children') ?>" class="btn btn-outline">
            &larr; Retour à la liste de mes enfants
        </a>
    </div>
</div>

<div style="max-width: 680px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🔍 Recherche & Association d'Élève</h3>
        </div>
        <div class="card-body" style="padding: 28px;">
            <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6; margin-bottom: 20px;">
                Saisissez le nom complet ou le numéro de matricule officiel de votre enfant. Le système effectuera une vérification de scolarité auprès de l'établissement.
            </p>

            <form action="<?= baseUrl('index.php?action=link_child') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <div class="form-group">
                    <label class="form-label" for="student_query">
                        Nom, Prénom ou Numéro de Matricule de l'élève *
                    </label>
                    <input type="text" name="student_query" id="student_query" class="form-control" 
                           placeholder="ex: Fatou Diop ou MB-2025-001" required autofocus>
                    <p style="font-size: 11px; color: var(--text-muted); margin-top: 5px;">
                        💡 Astuce : Le matricule garantit une association immédiate et sans homonymie.
                    </p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="relationship">Lien de parenté *</label>
                    <select name="relationship" id="relationship" class="form-control" required>
                        <option value="Pere">Père</option>
                        <option value="Mere">Mère</option>
                        <option value="Tuteur">Tuteur / Tutrice légale</option>
                        <option value="Autre">Autre représentant légal</option>
                    </select>
                </div>

                <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: var(--radius-sm); padding: 12px; margin-bottom: 20px; font-size: 12.5px; color: #f59e0b;">
                    ⚠️ <strong>Contrôle de sécurité :</strong> Si l'élève est introuvable ou inscrit dans un autre établissement scolaire, un message d'erreur explicite s'affichera afin de garantir la stricte confidentialité des données.
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <a href="<?= baseUrl('index.php?page=parent_children') ?>" class="btn btn-outline">Annuler</a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        🔍 Vérifier & Associer l'Enfant
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Enfants déjà associés -->
    <?php if (!empty($children)): ?>
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <h4 style="font-size: 14px; font-weight: 700;">Enfants actuellement rattachés à ce compte :</h4>
            </div>
            <div class="card-body" style="padding: 12px 20px;">
                <ul style="list-style: none;">
                    <?php foreach ($children as $c): ?>
                        <li style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border);">
                            <div>
                                <strong><?= e($c['first_name'] . ' ' . $c['last_name']) ?></strong> 
                                (<?= e($c['class_name']) ?> — Matricule : <?= e($c['student_matricule']) ?>)
                            </div>
                            <span class="badge badge-success">✓ Rattaché</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<?php
// views/director/audit.php — Journal d'Audit & Traçabilité Classé en Parties
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Audit.php';

Auth::requireRole(['directeur']);

$schoolId = Auth::schoolId();
$selectedCategory = $_GET['category'] ?? 'all';
$selectedRole = $_GET['role'] ?? 'all';

// Récupération des compteurs par catégorie
$counts = Audit::getCategoryCounts($schoolId);

// Récupération des logs filtrés
$logs = Audit::getFilteredLogs($schoolId, $selectedCategory, $selectedRole, 150);

// Définition des catégories officielles pour l'affichage
$categories = [
    'all' => [
        'label' => 'Toutes les opérations',
        'icon'  => '🌐',
        'count' => $counts['all']
    ],
    Audit::CATEGORY_SECURITY => [
        'label' => 'Sécurité & Accès',
        'icon'  => '🔐',
        'desc'  => 'Connexions, échecs de mot de passe, déconnexions et inscriptions',
        'count' => $counts[Audit::CATEGORY_SECURITY]
    ],
    Audit::CATEGORY_FINANCE => [
        'label' => 'Finances & Trésorerie',
        'icon'  => '💳',
        'desc'  => 'Règlements Wave/OM/CB, paiements guichet et ordonnancement des salaires',
        'count' => $counts[Audit::CATEGORY_FINANCE]
    ],
    Audit::CATEGORY_PEDAGOGY => [
        'label' => 'Pédagogie & Évaluations',
        'icon'  => '🎓',
        'desc'  => 'Création des devoirs, saisie des notes, résumés de cours et bulletins',
        'count' => $counts[Audit::CATEGORY_PEDAGOGY]
    ],
    Audit::CATEGORY_DISCIPLINE => [
        'label' => 'Vie Scolaire & Discipline',
        'icon'  => '⏱️',
        'desc'  => 'Pointages d\'assiduité, retards, justifications, sanctions et emplois du temps',
        'count' => $counts[Audit::CATEGORY_DISCIPLINE]
    ],
    Audit::CATEGORY_ADMIN => [
        'label' => 'Administration & Système',
        'icon'  => '🏛️',
        'desc'  => 'Communiqués officiels, convocations de réunions et paramètres d\'école',
        'count' => $counts[Audit::CATEGORY_ADMIN]
    ],
];

$pageTitle = "Journal d'Audit & Traçabilité des Opérations";
require_once __DIR__ . '/../../templates/header.php';
?>

<!-- EN-TÊTE PRINCIPAL -->
<div class="page-header no-print">
    <div>
        <h1 class="page-title">🛡️ Journal d'Audit & Traçabilité</h1>
        <p class="page-subtitle">Suivi chronologique et classification thématique de toutes les actions sensibles au sein de l'établissement</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <button onclick="window.print()" class="btn btn-outline btn-sm">
            🖨️ Imprimer le Rapport d'Audit
        </button>
        <a href="<?= baseUrl('index.php?page=director_backup') ?>" class="btn btn-primary btn-sm">
            💾 Sauvegarde de la Base de Données
        </a>
    </div>
</div>

<!-- 1. CARTES DE SYNTHÈSE / KPIS PAR PARTIE -->
<div class="stats-grid no-print" style="margin-bottom: 24px;">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #3b82f6;">🌐</div>
        <div>
            <div class="stat-value"><?= $counts['all'] ?></div>
            <div class="stat-label">Total Événements Enregistrés</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 3px;">
                Historique complet et traçable
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(59, 130, 246, 0.12); color: #2563eb;">🔐</div>
        <div>
            <div class="stat-value"><?= $counts[Audit::CATEGORY_SECURITY] ?></div>
            <div class="stat-label">Sécurité & Authentification</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 3px;">
                Connexions, échecs et inscriptions
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">💳</div>
        <div>
            <div class="stat-value"><?= $counts[Audit::CATEGORY_FINANCE] ?></div>
            <div class="stat-label">Transactions Financières</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 3px;">
                Paiements scolarité & salaires
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">🎓</div>
        <div>
            <div class="stat-value"><?= $counts[Audit::CATEGORY_PEDAGOGY] + $counts[Audit::CATEGORY_DISCIPLINE] ?></div>
            <div class="stat-label">Pédagogie & Vie Scolaire</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 3px;">
                Notes, bulletins, absences, sanctions
            </div>
        </div>
    </div>
</div>

<!-- 2. BARRE D'ONGLETS THÉMATIQUES (CLASSIFICATION EN PARTIES) -->
<div class="card no-print" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 16px 20px;">
        
        <!-- Onglets des parties -->
        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;">
            <?php foreach ($categories as $catKey => $catInfo): ?>
                <?php
                $isActive = ($selectedCategory === $catKey);
                $btnClass = $isActive ? 'btn-primary' : 'btn-outline';
                ?>
                <a href="<?= baseUrl('index.php?page=director_audit&category=' . $catKey . ($selectedRole !== 'all' ? '&role=' . $selectedRole : '')) ?>" 
                   class="btn <?= $btnClass ?> btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                    <span><?= $catInfo['icon'] ?></span>
                    <span><?= e($catInfo['label']) ?></span>
                    <span class="badge" style="background: <?= $isActive ? 'rgba(255,255,255,0.25)' : 'var(--bg-table-hover)' ?>; color: <?= $isActive ? '#fff' : 'var(--text-main)' ?>; font-size: 10.5px; padding: 2px 6px;">
                        <?= $catInfo['count'] ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Filtre secondaire par rôle & Recherche textuelle instantanée -->
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap; padding-top: 12px; border-top: 1px solid var(--border);">
            
            <!-- Sélecteur de rôle -->
            <form method="GET" action="<?= baseUrl('index.php') ?>" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <input type="hidden" name="page" value="director_audit">
                <input type="hidden" name="category" value="<?= e($selectedCategory) ?>">

                <span style="font-size: 13px; font-weight: 700; color: var(--text-muted);">👤 Filtrer par Rôle :</span>
                <select name="role" class="form-control" onchange="this.form.submit()" style="width: auto; height: 36px; font-size: 13px;">
                    <option value="all" <?= $selectedRole === 'all' ? 'selected' : '' ?>>Tous les utilisateurs</option>
                    <option value="directeur" <?= $selectedRole === 'directeur' ? 'selected' : '' ?>>Directeur</option>
                    <option value="surveillant" <?= $selectedRole === 'surveillant' ? 'selected' : '' ?>>Surveillant Général</option>
                    <option value="professeur" <?= $selectedRole === 'professeur' ? 'selected' : '' ?>>Professeur</option>
                    <option value="secretaire" <?= $selectedRole === 'secretaire' ? 'selected' : '' ?>>Secrétaire / Intendant</option>
                    <option value="parent" <?= $selectedRole === 'parent' ? 'selected' : '' ?>>Parent d'Élève</option>
                    <option value="eleve" <?= $selectedRole === 'eleve' ? 'selected' : '' ?>>Élève</option>
                </select>

                <?php if ($selectedCategory !== 'all' || $selectedRole !== 'all'): ?>
                    <a href="<?= baseUrl('index.php?page=director_audit') ?>" class="btn btn-outline btn-sm" title="Réinitialiser les filtres">
                        &times; Réinitialiser
                    </a>
                <?php endif; ?>
            </form>

            <!-- Recherche instantanée en JS au clavier -->
            <div style="position: relative; width: 280px;">
                <input type="text" id="auditSearchInput" class="form-control" 
                       placeholder="🔍 Rechercher action, nom, IP..." 
                       onkeyup="filterAuditRows(this.value)" 
                       style="padding-left: 12px; height: 36px; font-size: 13px;">
            </div>

        </div>

    </div>
</div>

<!-- 3. TABLEAU PRINCIPAL CLASSIFIÉ DES ENREGISTREMENTS -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 class="card-title">
                <?= $categories[$selectedCategory]['icon'] ?? '📜' ?> 
                Partie : <?= e($categories[$selectedCategory]['label'] ?? 'Toutes les opérations') ?>
            </h3>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                <?= count($logs) ?> événement(s) affiché(s) &bullet; Traçabilité avec horodatage, utilisateur et adresse IP
            </div>
        </div>

        <span class="badge badge-info" style="font-size: 11px;">
            Journal Sécurisé & Intègre
        </span>
    </div>

    <div class="card-body" style="padding: 0;">
        <?php if (empty($logs)): ?>
            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                <div style="font-size: 34px; margin-bottom: 8px;">🕊️</div>
                <strong>Aucun événement répertorié pour cette catégorie ou ce filtre.</strong>
                <p style="font-size: 13px; margin-top: 4px;">Les opérations correspondantes s'afficheront ici en temps réel dès leur exécution.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table" id="auditTable">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Horodatage</th>
                            <th style="width: 170px;">Partie / Thème</th>
                            <th>Action Effectuée</th>
                            <th>Utilisateur / Acteur</th>
                            <th>Rôle</th>
                            <th>Entité / Réf.</th>
                            <th>Détails & Motif</th>
                            <th>Adresse IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $catInfo = Audit::getActionCategory($log['action']);
                            ?>
                            <tr class="audit-log-row" 
                                data-action="<?= strtolower(e($log['action'] . ' ' . $catInfo['action_name'])) ?>" 
                                data-user="<?= strtolower(e(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '') . ' ' . ($log['username'] ?? ''))) ?>" 
                                data-role="<?= strtolower(e($log['role'] ?? '')) ?>" 
                                data-details="<?= strtolower(e($log['details'] ?? '')) ?>" 
                                data-ip="<?= strtolower(e($log['ip_address'] ?? '')) ?>">
                                
                                <!-- Horodatage -->
                                <td style="white-space: nowrap; font-size: 12.5px;">
                                    <div style="font-weight: 700; color: var(--text-main);"><?= date('d/m/Y', strtotime($log['created_at'])) ?></div>
                                    <div style="font-size: 11px; color: var(--text-muted); font-family: monospace;"><?= date('H:i:s', strtotime($log['created_at'])) ?></div>
                                </td>

                                <!-- Partie / Catégorie -->
                                <td>
                                    <span class="badge <?= $catInfo['badge_class'] ?>" style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px;">
                                        <span><?= $catInfo['icon'] ?></span>
                                        <span><?= e($catInfo['label']) ?></span>
                                    </span>
                                </td>

                                <!-- Action effectuée -->
                                <td>
                                    <div style="font-weight: 700; color: var(--text-main); font-size: 13.5px;">
                                        <?= e($catInfo['action_name']) ?>
                                    </div>
                                    <div style="font-family: monospace; font-size: 10.5px; color: var(--text-muted); margin-top: 1px;">
                                        <?= e($log['action']) ?>
                                    </div>
                                </td>

                                <!-- Utilisateur -->
                                <td>
                                    <?php if (!empty($log['first_name'])): ?>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <div style="width: 28px; height: 28px; border-radius: 50%; background: var(--bg-table-hover); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; color: var(--primary-light);">
                                                <?= strtoupper(substr($log['first_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <strong style="color: var(--text-main); font-size: 13px;"><?= e($log['first_name'] . ' ' . $log['last_name']) ?></strong><br>
                                                <span style="font-size: 11px; color: var(--text-muted);">@<?= e($log['username']) ?></span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-style: italic;">Système / Anonyme</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Rôle -->
                                <td>
                                    <?php if (!empty($log['role'])): ?>
                                        <span class="role-tag role-<?= e($log['role']) ?>" style="font-size: 11px;">
                                            <?= ucfirst(e($log['role'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Table / Enregistrement ciblé -->
                                <td style="font-size: 12px; color: var(--text-muted); white-space: nowrap;">
                                    <?php if (!empty($log['target_table'])): ?>
                                        <code style="background: var(--bg-table-hover); padding: 2px 5px; border-radius: 4px; font-size: 11px;">
                                            <?= e($log['target_table']) ?>#<?= e($log['record_id'] ?? '0') ?>
                                        </code>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <!-- Détails de l'opération -->
                                <td style="font-size: 12.5px; color: var(--text-main); line-height: 1.4; max-width: 320px;">
                                    <?= nl2br(e($log['details'] ?? '-')) ?>
                                </td>

                                <!-- Adresse IP -->
                                <td style="font-family: monospace; font-size: 11.5px; color: var(--text-muted); white-space: nowrap;">
                                    <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #10b981; margin-right: 4px;"></span>
                                    <?= e($log['ip_address'] ?? '127.0.0.1') ?>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- SCRIPT DE RECHERCHE INSTANTANÉE AU CLAVIER -->
<script>
function filterAuditRows(query) {
    query = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.audit-log-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const action = row.getAttribute('data-action') || '';
        const user = row.getAttribute('data-user') || '';
        const role = row.getAttribute('data-role') || '';
        const details = row.getAttribute('data-details') || '';
        const ip = row.getAttribute('data-ip') || '';

        const matches = action.includes(query) || 
                        user.includes(query) || 
                        role.includes(query) || 
                        details.includes(query) || 
                        ip.includes(query);

        if (matches) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

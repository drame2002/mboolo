<?php
// views/director/backup.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';

Auth::requireRole(['directeur']);

$school = Auth::school();
$pageTitle = "Sauvegarde & Exportation de la Base de Données";

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">💾 Sauvegarde & Sécurité des Données</h1>
        <p class="page-subtitle">Exportez et préservez l'intégralité des données et archives scolaires de votre établissement</p>
    </div>
</div>

<div style="max-width: 800px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📦 Générateur de Sauvegarde SQL Complète</h3>
        </div>
        <div class="card-body" style="padding: 30px;">
            <div style="display: flex; gap: 20px; align-items: flex-start; margin-bottom: 24px;">
                <div style="font-size: 48px; background: #eff6ff; padding: 16px; border-radius: var(--radius-md);">
                    🗄️
                </div>
                <div>
                    <h4 style="font-size: 17px; font-weight: 800; color: #0f172a; margin-bottom: 6px;">
                        Archive SQL Certifiée — <?= e($school['name']) ?>
                    </h4>
                    <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6;">
                        Cette fonction génère un instantané complet de la base de données relationnelle incluant les tables d'utilisateurs, classes, emplois du temps, notes, bulletins officiels, paiements et journaux de trésorerie.
                    </p>
                </div>
            </div>

            <div style="background: var(--bg-table-hover); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 18px; margin-bottom: 24px;">
                <h5 style="font-size: 13px; font-weight: 700; text-transform: uppercase; color: var(--primary); margin-bottom: 8px;">
                    Contenu du fichier de sauvegarde :
                </h5>
                <ul style="font-size: 13px; color: var(--text-muted); line-height: 1.8; margin-left: 20px;">
                    <li>Schéma complet des tables (DDL) avec contraintes et index</li>
                    <li>Utilisateurs et profils élèves avec matricules et séries de lycée</li>
                    <li>Évaluations, notes et bulletins semestriels calculés</li>
                    <li>Historique financier (mensualités, paiements en ligne et guichet, salaires)</li>
                    <li>Journal d'audit de sécurité et traçabilité</li>
                </ul>
            </div>

            <form action="<?= baseUrl('index.php?action=download_backup') ?>" method="POST" style="text-align: center;">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <button type="submit" class="btn btn-primary btn-lg" style="padding: 14px 32px; font-size: 16px;">
                    📥 Télécharger la Sauvegarde SQL (.sql)
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

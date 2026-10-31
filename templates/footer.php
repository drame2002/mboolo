<?php
// templates/footer.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Auth.php';

$currentSchool = Auth::school();
$schoolLogo = !empty($currentSchool['logo_path']) ? baseUrl($currentSchool['logo_path']) : baseUrl('assets/img/logos/mboolo_logo.png');
?>

<?php if (Auth::check()): ?>
        </main>

        <!-- Pied de page Sombre (pas tout noir mais sombre - Spécifications) -->
        <footer class="site-footer">
            <div class="footer-school-brand">
                <img src="<?= $schoolLogo ?>" alt="Logo <?= e($currentSchool['name'] ?? 'MBOOLO') ?>" class="footer-school-logo">
                <div>
                    <div class="footer-school-name"><?= e($currentSchool['name'] ?? 'MBOOLO Éducation') ?></div>
                    <div class="footer-school-coords">
                        📍 <?= e($currentSchool['address'] ?? 'Dakar, Sénégal') ?> &nbsp;|&nbsp; 
                        📞 <?= e($currentSchool['phone'] ?? '+221 33 000 00 00') ?> &nbsp;|&nbsp; 
                        ✉️ <?= e($currentSchool['email'] ?? 'contact@mboolo.sn') ?>
                    </div>
                </div>
            </div>

            <div style="text-align: right; font-size: 12px; line-height: 1.5;">
                <div><span class="footer-brand-tag">MBOOLO</span> &copy; <?= date('Y') ?> — Système Intégré de Gestion Scolaire Multi-Établissements</div>
                <div style="color: var(--footer-muted);">Plateforme sécurisée &bullet; Architecture Multi-Tenant cloisonnée</div>
            </div>
        </footer>
    </div>
</div>
<?php endif; ?>

<script src="<?= baseUrl('assets/js/main.js') ?>"></script>
</body>
</html>

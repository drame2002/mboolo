<?php
// templates/footer.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Auth.php';

$currentSchool = Auth::school();
$schoolLogo = !empty($currentSchool['logo_path']) ? baseUrl($currentSchool['logo_path']) : baseUrl('assets/img/logos/mboolo_logo.png');
?>

<?php if (Auth::check()): ?>
        </main>

        <!-- Pied de page Grand & Large Sombre -->
        <footer class="site-footer">
            <div class="footer-content-grid">
                <!-- Colonne 1 : Établissement Scolaire -->
                <div class="footer-school-brand">
                    <img src="<?= $schoolLogo ?>" alt="Logo <?= e($currentSchool['name'] ?? 'MBOOLO') ?>" class="footer-school-logo">
                    <div>
                        <div class="footer-school-name"><?= e($currentSchool['name'] ?? 'MBOOLO Éducation') ?></div>
                        <?php if (!empty($currentSchool['slogan'])): ?>
                            <div class="footer-school-slogan">« <?= e($currentSchool['slogan']) ?> »</div>
                        <?php endif; ?>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px;">
                            <span class="badge badge-success" style="font-size: 11px;">Établissement Homologué</span>
                            <span class="badge badge-secondary" style="font-size: 11px;">Code : <?= e($currentSchool['code'] ?? 'MBL') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Colonne 2 : Coordonnées & Contact Officiel -->
                <div>
                    <div class="footer-section-title">Coordonnées Officielles</div>
                    <div class="footer-school-coords">
                        <div>
                            <span>📍</span>
                            <span><?= e($currentSchool['address'] ?? 'Dakar, Sénégal') ?></span>
                        </div>
                        <div>
                            <span>📞</span>
                            <span>Secrétariat : <?= e($currentSchool['phone'] ?? '+221 33 000 00 00') ?></span>
                        </div>
                        <div>
                            <span>✉️</span>
                            <span>Courriel : <?= e($currentSchool['email'] ?? 'contact@mboolo.sn') ?></span>
                        </div>
                        <?php if (!empty($currentSchool['website'])): ?>
                            <div>
                                <span>🌐</span>
                                <span>Site Web : <a href="<?= e($currentSchool['website']) ?>" target="_blank" style="color: var(--primary-light); text-decoration: underline;"><?= e($currentSchool['website']) ?></a></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Colonne 3 : Sécurité & Garanties MBOOLO -->
                <div>
                    <div class="footer-section-title">Plateforme SaaS MBOOLO</div>
                    <p style="font-size: 12.5px; color: var(--footer-muted); line-height: 1.6; margin-bottom: 12px;">
                        Gestion intégrée et communication en temps réel pour l'Élémentaire, le Collège et le Lycée.
                    </p>
                    <div style="display: flex; flex-direction: column; gap: 6px; font-size: 12px; color: var(--footer-muted);">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span style="color: #10b981; font-weight: bold;">✓</span> Architecture Multi-Tenant isolée
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span style="color: #10b981; font-weight: bold;">✓</span> Encaissements Wave & OM certifiés
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span style="color: #10b981; font-weight: bold;">✓</span> Conforme aux normes MEN Sénégal
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barre inférieure Copyright & Mentions -->
            <div class="footer-bottom-bar">
                <div>
                    <span class="footer-brand-tag">MBOOLO</span> &copy; <?= date('Y') ?> — Système Intégré de Gestion Scolaire Multi-Établissements. Tous droits réservés.
                </div>
                <div>
                    <span>Direction &bullet; Professeurs &bullet; Surveillants &bullet; Secrétaires &bullet; Parents &bullet; Élèves</span>
                </div>
            </div>
        </footer>
    </div>
</div>
<?php endif; ?>

<script src="<?= baseUrl('assets/js/main.js') ?>"></script>
</body>
</html>

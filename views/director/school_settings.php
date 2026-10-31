<?php
// views/director/school_settings.php — Personnalisation & Identité de l'Établissement (Logo & Coordonnées)
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/SchoolService.php';

Auth::requireRole(['directeur']);

$schoolId = Auth::schoolId();
$school = SchoolService::getSchoolById($schoolId);
$pageTitle = "Personnalisation & Identité de l'Établissement";

$schoolLogo = !empty($school['logo_path']) ? baseUrl($school['logo_path']) : baseUrl('assets/img/logos/mboolo_logo.png');

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">⚙️ Personnalisation de l'Établissement</h1>
        <p class="page-subtitle">Configurez le logo officiel, les coordonnées et l'identité visuelle de votre établissement scolaire</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <!-- Formulaire de modification -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🏫 Informations Institutionnelles & Identité Visuelle</h3>
        </div>
        <div class="card-body">
            <form action="<?= baseUrl('index.php?action=update_school_settings') ?>" method="POST" enctype="multipart/form-data" id="schoolSettingsForm">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <div class="form-group">
                    <label class="form-label" for="name">Nom Officiel de l'Établissement *</label>
                    <input type="text" name="name" id="name" class="form-control" value="<?= e($school['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="slogan">Devise ou Slogan Pédagogique</label>
                    <input type="text" name="slogan" id="slogan" class="form-control" value="<?= e($school['slogan']) ?>" placeholder="ex: L'excellence et le travail au service de la réussite">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="phone">Numéro de Téléphone Officiel *</label>
                        <input type="text" name="phone" id="phone" class="form-control" value="<?= e($school['phone']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Adresse E-mail Institutionnelle *</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?= e($school['email']) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Adresse Physique & Boîte Postale *</label>
                    <textarea name="address" id="address" class="form-control" rows="2" required><?= e($school['address']) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="website">Site Web Officiel (optionnel)</label>
                    <input type="url" name="website" id="website" class="form-control" value="<?= e($school['website']) ?>" placeholder="https://...">
                </div>

                <!-- Upload du Logo (PNG, JPG, JPEG, WEBP, SVG) (Spécification 8) -->
                <div class="form-group" style="background: var(--bg-main); border: 2px dashed var(--primary-light); padding: 20px; border-radius: var(--radius-md);">
                    <label class="form-label" for="logo" style="color: var(--primary-light); font-size: 15px; font-weight: 800; display: block; margin-bottom: 8px;">
                        🖼️ Importer un Nouveau Logo pour l'Établissement
                    </label>
                    
                    <input type="file" name="logo" id="logo" class="form-control" accept=".png, .jpg, .jpeg, .webp, .svg, image/png, image/jpeg, image/webp, image/svg+xml" onchange="previewSelectedLogo(this)">
                    
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 8px; line-height: 1.5;">
                        Formats acceptés : <strong>PNG, JPG, JPEG, WEBP, SVG</strong> &bullet; Taille max : <strong>10 Mo</strong>.<br>
                        <em>Privilégiez un logo haute définition sur fond transparent pour un rendu optimal sur fond clair ou sombre.</em>
                    </p>

                    <!-- Message d'alerte dynamique lors de la sélection -->
                    <div id="logo-preview-alert" style="display: none; margin-top: 12px; padding: 10px 14px; background: rgba(16, 185, 129, 0.12); border: 1px solid #10b981; border-radius: 8px; font-size: 13px; color: #047857; font-weight: 600;">
                        ✨ Nouveau logo chargé dans l'aperçu ! Cliquez impérativement sur le bouton <strong>« Enregistrer les Modifications »</strong> ci-dessous pour l'appliquer définitivement.
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
                    <button type="submit" class="btn btn-primary btn-lg" style="padding: 12px 28px; font-size: 16px;">
                        💾 Enregistrer les Modifications
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Aperçu dynamique du Logo et des Emplacements -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">👁️ Aperçu en Direct du Logo</h3>
            </div>
            <div class="card-body" style="text-align: center;">
                <div style="border: 1px solid var(--border); padding: 24px; border-radius: var(--radius-md); background: var(--bg-main); margin-bottom: 16px;">
                    <img id="preview-logo-img" src="<?= $schoolLogo ?>" alt="Logo Actuel" style="max-width: 150px; max-height: 150px; object-fit: contain; margin-bottom: 12px; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.1));">
                    <h4 id="preview-school-name" style="font-size: 16px; font-weight: 800; margin-bottom: 4px;"><?= e($school['name']) ?></h4>
                    <p id="preview-school-slogan" style="font-size: 12px; color: var(--text-muted);"><?= e($school['slogan']) ?></p>
                </div>

                <div style="text-align: left; font-size: 13px;">
                    <strong style="color: var(--primary-light);">📌 Emplacements automatiques du logo :</strong>
                    <ul style="margin: 8px 0 0 20px; color: var(--text-muted); line-height: 1.6;">
                        <li><strong>En-tête de navigation</strong> (barre supérieure et barre latérale fixe)</li>
                        <li><strong>Pied de page</strong> avec coordonnées complètes et téléphone</li>
                        <li><strong>Haut des bulletins scolaires</strong> semestriels officiels en PDF</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card" style="background: rgba(59, 130, 246, 0.06); border: 1px solid rgba(59, 130, 246, 0.2);">
            <div class="card-body">
                <h4 style="font-size: 14px; font-weight: 800; color: var(--primary-light); margin-bottom: 8px;">💡 Guide de remplacement du logo</h4>
                <ol style="font-size: 12px; color: var(--text-muted); line-height: 1.6; padding-left: 18px; margin: 0;">
                    <li>Cliquez sur <strong>« Choisir un fichier »</strong> ci-contre.</li>
                    <li>Sélectionnez votre fichier image (PNG, JPG, SVG...).</li>
                    <li>L'image s'affiche instantanément dans le cadre d'aperçu.</li>
                    <li>Cliquez sur <strong>« Enregistrer les Modifications »</strong> pour valider l'enregistrement dans la base de données.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
// Prévisualisation instantanée du logo sélectionné avant enregistrement
function previewSelectedLogo(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Vérification de la taille (max 10 Mo)
        if (file.size > 10 * 1024 * 1024) {
            alert("Ce fichier dépasse la taille maximale autorisée de 10 Mo.");
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const previewImg = document.getElementById('preview-logo-img');
            previewImg.src = e.target.result;
            document.getElementById('logo-preview-alert').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

// Mise à jour de l'aperçu du nom et du slogan en temps réel
document.getElementById('name').addEventListener('input', function() {
    document.getElementById('preview-school-name').textContent = this.value || "Nom de l'établissement";
});
document.getElementById('slogan').addEventListener('input', function() {
    document.getElementById('preview-school-slogan').textContent = this.value;
});
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

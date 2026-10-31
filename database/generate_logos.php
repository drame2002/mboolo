<?php
// Créateur de logos PNG pour les établissements
$logosDir = __DIR__ . '/../public/assets/img/logos';
if (!is_dir($logosDir)) {
    mkdir($logosDir, 0777, true);
}

function createSchoolLogo($filename, $title, $subtitle, $bgColor, $accentColor) {
    $width = 240;
    $height = 240;
    $img = imagecreatetruecolor($width, $height);
    
    // Transparence ou fond circulaire
    imagealphablending($img, false);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 255, 255, 255, 127);
    imagefilledrectangle($img, 0, 0, $width, $height, $transparent);

    // Couleurs
    $bg = imagecolorallocate($img, $bgColor[0], $bgColor[1], $bgColor[2]);
    $accent = imagecolorallocate($img, $accentColor[0], $accentColor[1], $accentColor[2]);
    $white = imagecolorallocate($img, 255, 255, 255);
    $dark = imagecolorallocate($img, 30, 41, 59);

    // Cercle d'arrière-plan
    imagefilledellipse($img, 120, 120, 220, 220, $bg);
    imageellipse($img, 120, 120, 222, 222, $accent);
    imageellipse($img, 120, 120, 214, 214, $white);

    // Texte centré (en utilisant imagestring)
    $text1 = strtoupper($title);
    $text2 = $subtitle;
    $symbol = "M";
    
    // Dessiner le symbole central
    imagefilledrectangle($img, 80, 80, 160, 125, $accent);
    imagestring($img, 5, 95, 95, $symbol, $white);
    
    // Titre
    $x1 = max(10, (int)((240 - strlen($text1) * 9) / 2));
    imagestring($img, 4, $x1, 140, $text1, $white);

    // Sous-titre
    $x2 = max(10, (int)((240 - strlen($text2) * 7) / 2));
    imagestring($img, 2, $x2, 165, $text2, $white);

    imagepng($img, $filename);
    imagedestroy($img);
    echo "Logo créé: $filename\n";
}

createSchoolLogo(
    $logosDir . '/mboolo_logo.png',
    'MBOOLO',
    'EXCELLENCE ACADÉMIQUE',
    [30, 58, 138], // Deep navy blue
    [245, 158, 11]  // Gold
);

createSchoolLogo(
    $logosDir . '/teranga_logo.png',
    'TERANGA',
    'LYCÉE MODERNE',
    [13, 148, 136], // Emerald/Teal
    [249, 115, 22]  // Orange
);

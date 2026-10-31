<?php
/**
 * MBOOLO — Migration Pédagogique Complète
 * 1. Élémentaire (CI, CP, CE1, CE2, CM1, CM2) avec matières dédiées
 * 2. Collège (6ème à 3ème) avec matières dont PC à partir de la 4ème, Latin, Musique, Espagnol...
 * 3. Lycée (Seconde, Première, Terminale) avec TOUTES LES SÉRIES (S1, S2, S3, L1, L2, L', G/STEG, T1, T2...)
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();
$defaultHash = password_hash('pass123', PASSWORD_BCRYPT);

echo "=== MIGRATION PÉDAGOGIQUE MBOOLO ===\n";

// 1. Ajouter des professeurs spécialisés pour l'école 1 (Langues, Musique/Arts, Gestion/Technique)
$teachersToAdd = [
    [20, 1, 'prof_langues', 'prof.langues@mboolo.sn', $defaultHash, 'professeur', 'Awa', 'DIALLO', '+221 77 510 11 22', 'F', '1986-09-12', 'Sacré-Cœur, Dakar'],
    [21, 1, 'prof_arts_musique', 'prof.musique@mboolo.sn', $defaultHash, 'professeur', 'Moussa', 'KOUYATÉ', '+221 77 520 33 44', 'M', '1982-03-25', 'Point E, Dakar'],
    [22, 1, 'prof_gestion_tech', 'prof.tech@mboolo.sn', $defaultHash, 'professeur', 'Ibrahima', 'THIAM', '+221 77 530 55 66', 'M', '1979-11-18', 'Castors, Dakar']
];

$stmtProf = $pdo->prepare("
    INSERT INTO `users` (`id`, `school_id`, `username`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `phone`, `gender`, `date_of_birth`, `address`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE `first_name`=VALUES(`first_name`), `last_name`=VALUES(`last_name`)
");
foreach ($teachersToAdd as $t) {
    $stmtProf->execute($t);
}
echo " -> Professeurs spécialisés synchronisés.\n";

// 2. Matières Complètes par Cycle (pour School 1 et School 2)
// Élémentaire, Collège, Lycée
$subjectsData = [
    // --- MATIÈRES ÉLÉMENTAIRE ---
    ['Mathématiques (Élémentaire)', 'MATH-EL', '#2563eb', "Calcul, numération, géométrie et résolution de problèmes"],
    ['Français (Élémentaire)', 'FR-EL', '#0284c7', "Lecture, écriture, grammaire, vocabulaire et expression orale"],
    ['Initiation Scientifique et Technologique (IST)', 'IST', '#059669', "Découverte des sciences, observation, expériences simples et technologie"],
    ['Vivre ensemble', 'VIVRE-ENS', '#16a34a', "Éducation civique, valeurs morales, citoyenneté et respect mutuel"],
    ['Vivre dans son milieu', 'VIVRE-MIL', '#0d9488', "Hygiène, santé, écologie locale, milieu naturel et environnement"],
    ['Éducation Physique et Sportive (Élémentaire)', 'EPS-EL', '#ea580c', "Motricité, jeux collectifs et développement physique"],
    ['Histoire & Géographie (Élémentaire)', 'HG-EL', '#854d0e', "Repères historiques et découverte de l'espace sénégalais et mondial"],
    ['Éducation Artistique & Arts Plastiques', 'ART-EL', '#9333ea', "Dessin, peinture, travaux manuels et créativité"],

    // --- MATIÈRES COLLÈGE ---
    ['Français (Collège)', 'FR-COL', '#0284c7', "Littérature, grammaire avancée, orthographe et expression écrite"],
    ['Mathématiques (Collège)', 'MATH-COL', '#2563eb', "Arithmétique, calcul littéral, géométrie plane et théorèmes fondamentaux"],
    ['Sciences de la Vie et de la Terre (Collège)', 'SVT-COL', '#059669', "Biologie humaine, reproduction, géologie et écosystèmes"],
    ['Physique-Chimie (Collège - Dès la 4ème)', 'PC-COL', '#7c3aed', "Initiation aux sciences physiques, électricité, optique et chimie (dès la 4ème)"],
    ['Latin', 'LAT', '#b45309', "Langue et civilisation latine, étymologie et culture antique"],
    ['Éducation Musicale', 'MUS', '#ec4899', "Pratique vocale, solfège, rythmique et découverte des patrimoines musicaux"],
    ['Anglais (Collège - LV1)', 'ANG-COL', '#0284c7', "Première langue vivante, grammaire, vocabulaire et conversation"],
    ['Espagnol (Collège - LV2)', 'ESP-COL', '#d97706', "Deuxième langue vivante dès la 4ème, civilisation hispanique"],
    ['Histoire-Géographie (Collège)', 'HG-COL', '#854d0e', "Histoire de l'Afrique et du monde, géographie humaine et économique"],
    ['Éducation Physique & Sportive (Collège)', 'EPS-COL', '#16a34a', "Athlétisme, sports collectifs et endurance"],

    // --- MATIÈRES LYCÉE ---
    ['Français (Lycée)', 'FR-LYC', '#0284c7', "Étude des œuvres littéraires, dissertation, commentaire composé et contraction de texte"],
    ['Philosophie (Lycée)', 'PHILO-LYC', '#d97706', "Épistémologie, métaphysique, morale, esthétique et pensée critique"],
    ['Mathématiques (Lycée)', 'MATH-LYC', '#2563eb', "Analyse réelle, suites, fonctions, géométrie dans l'espace, probabilités"],
    ['Physique-Chimie (Lycée)', 'PC-LYC', '#7c3aed', "Mécanique newtonienne, électromagnétisme, chimie organique et cinétique"],
    ['Sciences de la Vie et de la Terre (Lycée)', 'SVT-LYC', '#059669', "Génétique, immunologie, tectonique des plaques et neurophysiologie"],
    ['Histoire-Géographie (Lycée)', 'HG-LYC', '#854d0e', "Relations internationales, mondialisation et développement durable"],
    ['Anglais (Lycée)', 'ANG-LYC', '#0284c7', "Approfondissement linguistique, commentaire de texte et civilisation anglophone"],
    ['Espagnol (Lycée)', 'ESP-LYC', '#d97706', "Maîtrise de la langue vivante 2, littérature et société hispanophone"],
    ['Sciences Économiques & Gestion / Comptabilité', 'ECO-GEST', '#0d9488', "Comptabilité générale, droit des affaires, économie d'entreprise (Séries G)"],
    ['Sciences de l\'Ingénieur & Génie Industriel', 'GENIE-INDUS', '#475569', "Dessin technique, fabrication mécanique, électronique et automatique (Séries T)"],
    ['Éducation Physique & Sportive (Lycée)', 'EPS-LYC', '#16a34a', "Gymnastique, sports de balle, course de fond et évaluation au baccalauréat"]
];

$stmtInsertSubj = $pdo->prepare("
    INSERT INTO `subjects` (`school_id`, `name`, `code`, `color`, `description`)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `description`=VALUES(`description`), `color`=VALUES(`color`)
");

foreach ([1, 2] as $schId) {
    foreach ($subjectsData as $s) {
        $stmtInsertSubj->execute([$schId, $s[0], $s[1], $s[2], $s[3]]);
    }
}
echo " -> Matières par cycle synchronisées pour les établissements.\n";

// 3. Classes Complètes par Cycle
// Élémentaire : CI, CP, CE1, CE2, CM1, CM2
// Collège : 6ème, 5ème, 4ème, 3ème
// Lycée : Seconde (S, L, G, T), Première (S1, S2, S3, L1, L2, L', G, T1, T2), Terminale (S1, S2, S3, L1, L2, L', G, T1, T2)

$classesToInsert = [
    // --- ÉLÉMENTAIRE (School 1) ---
    [101, 1, 'Cours d\'Initiation (CI)', 'CI', 'Elementaire', NULL, 'Salle E1 (RDC)', 35],
    [102, 1, 'Cours Préparatoire (CP)', 'CP', 'Elementaire', NULL, 'Salle E2 (RDC)', 35],
    [103, 1, 'Cours Élémentaire 1 (CE1)', 'CE1', 'Elementaire', NULL, 'Salle E3 (RDC)', 38],
    [104, 1, 'Cours Élémentaire 2 (CE2)', 'CE2', 'Elementaire', NULL, 'Salle E4 (1er étage)', 38],
    [105, 1, 'Cours Moyen 1 (CM1)', 'CM1', 'Elementaire', NULL, 'Salle E5 (1er étage)', 40],
    [106, 1, 'Cours Moyen 2 (CM2)', 'CM2', 'Elementaire', NULL, 'Salle E6 (1er étage)', 40],

    // --- COLLÈGE (School 1) ---
    [110, 1, 'Sixième A (6ème A)', '6eme', 'College', NULL, 'Salle C1 (Bâtiment Collège)', 40],
    [111, 1, 'Cinquième A (5ème A)', '5eme', 'College', NULL, 'Salle C2 (Bâtiment Collège)', 40],
    [112, 1, 'Quatrième A (4ème A)', '4eme', 'College', NULL, 'Salle C3 (Bâtiment Collège)', 40],
    // Troisième A (id=3 déjà existant, voir seed_db)

    // --- LYCÉE : SECONDE (Troncs Communs) ---
    [120, 1, 'Seconde Scientifique (2nde S)', 'Seconde', 'Lycee', 'Scientifique (S)', 'Salle L01 (Bâtiment Lycée)', 38],
    [121, 1, 'Seconde Littéraire (2nde L)', 'Seconde', 'Lycee', 'Littéraire (L)', 'Salle L02 (Bâtiment Lycée)', 38],
    [122, 1, 'Seconde Tertiaire & Gestion (2nde G)', 'Seconde', 'Lycee', 'Économie & Gestion (G)', 'Salle L03 (Bâtiment Lycée)', 35],
    [123, 1, 'Seconde Technique & Industrielle (2nde T)', 'Seconde', 'Lycee', 'Technique Industrielle (T)', 'Atelier T1', 32],

    // --- LYCÉE : PREMIÈRE (Toutes les séries disponibles : S1, S2, S3, L1, L2, L', G, T1, T2) ---
    [130, 1, 'Première S1 (Maths & Sciences Physiques)', 'Première', 'Lycee', 'Scientifique (S1)', 'Salle S101', 35],
    // Première L2-B (id=2 déjà existant, voir seed_db)
    [131, 1, 'Première S2 (Sciences Expérimentales)', 'Première', 'Lycee', 'Scientifique (S2)', 'Salle S102', 38],
    [132, 1, 'Première S3 (Sciences & Technologies)', 'Première', 'Lycee', 'Scientifique (S3)', 'Salle S103', 35],
    [133, 1, 'Première L1 (Langues & Littérature)', 'Première', 'Lycee', 'Littéraire (L1)', 'Salle L101', 38],
    [134, 1, 'Première L\' (Lettres Classiques)', 'Première', 'Lycee', 'Littéraire (L\')', 'Salle L102', 32],
    [135, 1, 'Première G (Gestion & Comptabilité / STEG)', 'Première', 'Lycee', 'Économie & Gestion (G)', 'Salle G101', 35],
    [136, 1, 'Première T1 (Génie Mécanique)', 'Première', 'Lycee', 'Génie Mécanique (T1)', 'Atelier Mécanique', 30],
    [137, 1, 'Première T2 (Génie Électrique)', 'Première', 'Lycee', 'Génie Électrique (T2)', 'Laboratoire Électrique', 30],

    // --- LYCÉE : TERMINALE (Toutes les séries disponibles : S1, S2, S3, L1, L2, L', G, T1, T2) ---
    [140, 1, 'Terminale S1 (Mathématiques Pures & PC)', 'Terminale', 'Lycee', 'Scientifique (S1)', 'Salle T201', 35],
    // Terminale S2-A (id=1 déjà existant, voir seed_db)
    [141, 1, 'Terminale S3 (Sciences & Technologies)', 'Terminale', 'Lycee', 'Scientifique (S3)', 'Salle T203', 35],
    [142, 1, 'Terminale L1 (Langues & Civilisations)', 'Terminale', 'Lycee', 'Littéraire (L1)', 'Salle T204', 38],
    [143, 1, 'Terminale L2 (Philosophie & Sciences Humaines)', 'Terminale', 'Lycee', 'Littéraire (L2)', 'Salle T205', 40],
    [144, 1, 'Terminale L\' (Lettres Classiques & Latin)', 'Terminale', 'Lycee', 'Littéraire (L\')', 'Salle T206', 30],
    [145, 1, 'Terminale G (Sciences Économiques & Gestion)', 'Terminale', 'Lycee', 'Économie & Gestion (G)', 'Salle G201', 35],
    [146, 1, 'Terminale T1 (Fabrication Mécanique & Conception)', 'Terminale', 'Lycee', 'Génie Mécanique (T1)', 'Atelier T1 Master', 30],
    [147, 1, 'Terminale T2 (Électronique & Électrotechnique)', 'Terminale', 'Lycee', 'Génie Électrique (T2)', 'Laboratoire T2 Master', 30]
];

$stmtClassIns = $pdo->prepare("
    INSERT INTO `classes` (`id`, `school_id`, `name`, `level`, `cycle`, `track`, `academic_year`, `main_room`, `capacity`)
    VALUES (?, ?, ?, ?, ?, ?, '2025-2026', ?, ?)
    ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `level`=VALUES(`level`), `cycle`=VALUES(`cycle`), `track`=VALUES(`track`), `main_room`=VALUES(`main_room`), `capacity`=VALUES(`capacity`)
");

foreach ($classesToInsert as $c) {
    $stmtClassIns->execute($c);
}
echo " -> Classes créées pour tous les cycles (Élémentaire, Collège, Lycée - séries S, L, G, T).\n";

// 4. Affectation des matières aux classes (class_subjects)
// Récupérer la cartographie des matières par code pour School 1
$subMap = $pdo->query("SELECT code, id FROM `subjects` WHERE `school_id` = 1")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fonction helper pour insérer ou mettre à jour un class_subject
function linkSubject($pdo, $schoolId, $classId, $subjectId, $teacherId, $coeff, $hours) {
    if (!$subjectId) return;
    $stmt = $pdo->prepare("
        INSERT INTO `class_subjects` (`school_id`, `class_id`, `subject_id`, `teacher_id`, `coefficient`, `weekly_hours`, `academic_year`)
        VALUES (?, ?, ?, ?, ?, ?, '2025-2026')
        ON DUPLICATE KEY UPDATE `coefficient`=VALUES(`coefficient`), `weekly_hours`=VALUES(`weekly_hours`), `teacher_id`=VALUES(`teacher_id`)
    ");
    $stmt->execute([$schoolId, $classId, $subjectId, $teacherId, $coeff, $hours]);
}

// 4.1 Enseignements Élémentaire (CI, CP, CE1, CE2, CM1, CM2)
// Matières requises : Mathématiques, Français, IST, Vivre ensemble, Vivre dans son milieu, EPS, HG, Art
$elemClassIds = [101, 102, 103, 104, 105, 106];
foreach ($elemClassIds as $clsId) {
    linkSubject($pdo, 1, $clsId, $subMap['MATH-EL'] ?? null, 3, 5.0, 5.0); // Maths
    linkSubject($pdo, 1, $clsId, $subMap['FR-EL'] ?? null, 5, 6.0, 6.0);   // Français
    linkSubject($pdo, 1, $clsId, $subMap['IST'] ?? null, 4, 2.0, 2.0);     // IST
    linkSubject($pdo, 1, $clsId, $subMap['VIVRE-ENS'] ?? null, 5, 2.0, 2.0); // Vivre ensemble
    linkSubject($pdo, 1, $clsId, $subMap['VIVRE-MIL'] ?? null, 4, 2.0, 2.0); // Vivre dans son milieu
    linkSubject($pdo, 1, $clsId, $subMap['EPS-EL'] ?? null, 22, 1.0, 2.0);  // EPS
    linkSubject($pdo, 1, $clsId, $subMap['HG-EL'] ?? null, 5, 2.0, 2.0);   // HG
    linkSubject($pdo, 1, $clsId, $subMap['ART-EL'] ?? null, 21, 1.0, 1.5);  // Arts Plastiques
}
echo " -> Enseignements Élémentaire affectés.\n";

// 4.2 Enseignements Collège 6ème & 5ème
// Matières : Français, Maths, SVT, Latin, Musique, Anglais, HG, EPS (PAS DE PC NI ESPAGNOL)
$col65Ids = [110, 111]; // 6ème, 5ème
foreach ($col65Ids as $clsId) {
    linkSubject($pdo, 1, $clsId, $subMap['FR-COL'] ?? null, 5, 4.0, 5.0);
    linkSubject($pdo, 1, $clsId, $subMap['MATH-COL'] ?? null, 3, 4.0, 5.0);
    linkSubject($pdo, 1, $clsId, $subMap['SVT-COL'] ?? null, 4, 2.0, 2.0);
    linkSubject($pdo, 1, $clsId, $subMap['LAT'] ?? null, 5, 1.0, 2.0);
    linkSubject($pdo, 1, $clsId, $subMap['MUS'] ?? null, 21, 1.0, 2.0);
    linkSubject($pdo, 1, $clsId, $subMap['ANG-COL'] ?? null, 20, 2.0, 3.0);
    linkSubject($pdo, 1, $clsId, $subMap['HG-COL'] ?? null, 5, 2.0, 3.0);
    linkSubject($pdo, 1, $clsId, $subMap['EPS-COL'] ?? null, 22, 1.0, 2.0);
}

// 4.3 Enseignements Collège 4ème & 3ème
// Matières : Français, Maths, SVT, PC (DÈS LA 4ème !), Latin, Musique, Anglais, Espagnol (LV2), HG, EPS
$col43Ids = [112, 3]; // 4ème, 3ème A (id=3)
foreach ($col43Ids as $clsId) {
    linkSubject($pdo, 1, $clsId, $subMap['FR-COL'] ?? null, 5, 4.0, 5.0);
    linkSubject($pdo, 1, $clsId, $subMap['MATH-COL'] ?? null, 3, 4.0, 5.0);
    linkSubject($pdo, 1, $clsId, $subMap['SVT-COL'] ?? null, 4, 2.0, 2.0);
    linkSubject($pdo, 1, $clsId, $subMap['PC-COL'] ?? null, 4, 3.0, 3.0); // PHYSIQUE-CHIMIE DÈS LA 4EME !
    linkSubject($pdo, 1, $clsId, $subMap['LAT'] ?? null, 5, 1.0, 2.0);
    linkSubject($pdo, 1, $clsId, $subMap['MUS'] ?? null, 21, 1.0, 2.0);
    linkSubject($pdo, 1, $clsId, $subMap['ANG-COL'] ?? null, 20, 2.0, 3.0);
    linkSubject($pdo, 1, $clsId, $subMap['ESP-COL'] ?? null, 20, 2.0, 3.0); // ESPAGNOL
    linkSubject($pdo, 1, $clsId, $subMap['HG-COL'] ?? null, 5, 2.0, 3.0);
    linkSubject($pdo, 1, $clsId, $subMap['EPS-COL'] ?? null, 22, 1.0, 2.0);
}
echo " -> Enseignements Collège affectés (avec PC et Espagnol dès la 4ème).\n";

// 4.4 Enseignements Lycée (Seconde, Première, Terminale selon séries)
// Séries S (S1, S2, S3)
$lycScientifics = [120, 130, 131, 132, 1, 140, 141];
foreach ($lycScientifics as $clsId) {
    linkSubject($pdo, 1, $clsId, $subMap['MATH-LYC'] ?? null, 3, 6.0, 6.0);
    linkSubject($pdo, 1, $clsId, $subMap['PC-LYC'] ?? null, 4, 6.0, 6.0);
    linkSubject($pdo, 1, $clsId, $subMap['SVT-LYC'] ?? null, 4, 4.0, 4.0);
    linkSubject($pdo, 1, $clsId, $subMap['FR-LYC'] ?? null, 5, 3.0, 3.0);
    linkSubject($pdo, 1, $clsId, $subMap['PHILO-LYC'] ?? null, 5, 2.0, 2.0);
    linkSubject($pdo, 1, $clsId, $subMap['ANG-LYC'] ?? null, 20, 2.0, 3.0);
    linkSubject($pdo, 1, $clsId, $subMap['HG-LYC'] ?? null, 5, 2.0, 2.0);
    linkSubject($pdo, 1, $clsId, $subMap['EPS-LYC'] ?? null, 22, 1.0, 2.0);
}

// Séries L (L1, L2, L')
$lycLits = [121, 2, 133, 134, 142, 143, 144];
foreach ($lycLits as $clsId) {
    linkSubject($pdo, 1, $clsId, $subMap['FR-LYC'] ?? null, 5, 6.0, 6.0);
    linkSubject($pdo, 1, $clsId, $subMap['PHILO-LYC'] ?? null, 5, 6.0, 6.0);
    linkSubject($pdo, 1, $clsId, $subMap['HG-LYC'] ?? null, 5, 4.0, 4.0);
    linkSubject($pdo, 1, $clsId, $subMap['ANG-LYC'] ?? null, 20, 4.0, 4.0);
    linkSubject($pdo, 1, $clsId, $subMap['ESP-LYC'] ?? null, 20, 4.0, 4.0);
    linkSubject($pdo, 1, $clsId, $subMap['MATH-LYC'] ?? null, 3, 2.0, 2.0);
    linkSubject($pdo, 1, $clsId, $subMap['LAT'] ?? null, 5, 2.0, 2.0);
    linkSubject($pdo, 1, $clsId, $subMap['EPS-LYC'] ?? null, 22, 1.0, 2.0);
}

// Séries G (Gestion, Économie, Comptabilité)
$lycGestion = [122, 135, 145];
foreach ($lycGestion as $clsId) {
    linkSubject($pdo, 1, $clsId, $subMap['ECO-GEST'] ?? null, 22, 6.0, 6.0);
    linkSubject($pdo, 1, $clsId, $subMap['MATH-LYC'] ?? null, 3, 5.0, 5.0);
    linkSubject($pdo, 1, $clsId, $subMap['FR-LYC'] ?? null, 5, 3.0, 3.0);
    linkSubject($pdo, 1, $clsId, $subMap['PHILO-LYC'] ?? null, 5, 2.0, 2.0);
    linkSubject($pdo, 1, $clsId, $subMap['ANG-LYC'] ?? null, 20, 3.0, 3.0);
    linkSubject($pdo, 1, $clsId, $subMap['HG-LYC'] ?? null, 5, 2.0, 2.0);
    linkSubject($pdo, 1, $clsId, $subMap['EPS-LYC'] ?? null, 22, 1.0, 2.0);
}

// Séries T (Génie Industriel, Mécanique, Électrique)
$lycTech = [123, 136, 137, 146, 147];
foreach ($lycTech as $clsId) {
    linkSubject($pdo, 1, $clsId, $subMap['GENIE-INDUS'] ?? null, 22, 6.0, 7.0);
    linkSubject($pdo, 1, $clsId, $subMap['MATH-LYC'] ?? null, 3, 6.0, 6.0);
    linkSubject($pdo, 1, $clsId, $subMap['PC-LYC'] ?? null, 4, 5.0, 5.0);
    linkSubject($pdo, 1, $clsId, $subMap['FR-LYC'] ?? null, 5, 2.0, 2.0);
    linkSubject($pdo, 1, $clsId, $subMap['PHILO-LYC'] ?? null, 5, 2.0, 2.0);
    linkSubject($pdo, 1, $clsId, $subMap['ANG-LYC'] ?? null, 20, 2.0, 2.0);
    linkSubject($pdo, 1, $clsId, $subMap['EPS-LYC'] ?? null, 22, 1.0, 2.0);
}
echo " -> Enseignements Lycée (S, L, G, T) affectés avec coefficients conformes.\n";

echo "=== MIGRATION PÉDAGOGIQUE TERMINÉE AVEC SUCCÈS ===\n";

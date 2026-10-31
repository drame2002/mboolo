<?php
/**
 * Seeder de données de démonstration réalistes pour MBOOLO
 */
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();
echo "[*] Début du peuplement des données de test...\n";

// Hash de mot de passe standard pour tous les comptes de test : 'pass123'
$defaultPasswordHash = password_hash('pass123', PASSWORD_BCRYPT);

// 1. Établissements scolaires
$schools = [
    [
        'id' => 1,
        'name' => 'Groupe Scolaire d\'Excellence MBOOLO',
        'code' => 'MBOOLO-DKR',
        'slogan' => 'L\'excellence académique, l\'éthique et l\'avenir au cœur de notre pédagogie',
        'address' => 'Route des Almadies, BP 12450 Dakar, Sénégal',
        'phone' => '+221 33 820 40 50',
        'email' => 'contact@mboolo-excellence.sn',
        'website' => 'https://mboolo-excellence.sn',
        'logo_path' => 'assets/img/logos/mboolo_logo.png',
        'currency' => 'FCFA'
    ],
    [
        'id' => 2,
        'name' => 'Lycée d\'Enseignement Général et Technique TERANGA',
        'code' => 'TERANGA-SL',
        'slogan' => 'Le savoir éclaire le monde et bâtit l\'humanité',
        'address' => 'Avenue Dodds, Quartier Nord, Saint-Louis, Sénégal',
        'phone' => '+221 33 961 10 20',
        'email' => 'administration@teranga-edu.sn',
        'website' => 'https://teranga-edu.sn',
        'logo_path' => 'assets/img/logos/teranga_logo.png',
        'currency' => 'FCFA'
    ]
];

$stmtSchool = $pdo->prepare("INSERT INTO `schools` (`id`, `name`, `code`, `slogan`, `address`, `phone`, `email`, `website`, `logo_path`, `currency`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`)");
foreach ($schools as $s) {
    $stmtSchool->execute([$s['id'], $s['name'], $s['code'], $s['slogan'], $s['address'], $s['phone'], $s['email'], $s['website'], $s['logo_path'], $s['currency']]);
}
echo " -> 2 établissements créés.\n";

// 2. Utilisateurs
$users = [
    // --- ÉTABLISSEMENT 1 (MBOOLO) ---
    // Directeur
    [1, 1, 'directeur_mboolo', 'directeur@mboolo.sn', $defaultPasswordHash, 'directeur', 'Cheikh Anta', 'DIOP', '+221 77 600 11 22', 'M', '1975-04-12', 'Almadies, Dakar'],
    // Secrétaire
    [2, 1, 'secretaire_mboolo', 'secretaire@mboolo.sn', $defaultPasswordHash, 'secretaire', 'Aïssatou', 'NDIAYE', '+221 77 600 33 44', 'F', '1985-08-24', 'Mermoz, Dakar'],
    // Surveillant Général (Responsable de la vie scolaire et des emplois du temps)
    [15, 1, 'surveillant_mboolo', 'surveillant@mboolo.sn', $defaultPasswordHash, 'surveillant', 'Ousmane', 'SÈNE', '+221 77 650 44 33', 'M', '1982-07-14', 'Grand Yoff, Dakar'],
    // Professeurs
    [3, 1, 'prof_maths', 'prof.maths@mboolo.sn', $defaultPasswordHash, 'professeur', 'Babacar', 'FALL', '+221 77 500 01 02', 'M', '1980-02-15', 'Sacré-Cœur 3, Dakar'],
    [4, 1, 'prof_pc', 'prof.pc@mboolo.sn', $defaultPasswordHash, 'professeur', 'Mariama', 'BÂ', '+221 77 500 03 04', 'F', '1983-11-05', 'Ouakam, Dakar'],
    [5, 1, 'prof_philo', 'prof.philo@mboolo.sn', $defaultPasswordHash, 'professeur', 'Ousmane', 'SEMBÈNE', '+221 77 500 05 06', 'M', '1978-06-20', 'Fann Résidence, Dakar'],
    // Élèves
    [6, 1, 'fatou_diop', 'fatou.diop@mboolo.sn', $defaultPasswordHash, 'eleve', 'Fatou', 'DIOP', '+221 78 100 11 11', 'F', '2008-03-14', 'Mamelles, Dakar'],
    [7, 1, 'moussa_fall', 'moussa.fall@mboolo.sn', $defaultPasswordHash, 'eleve', 'Moussa', 'FALL', '+221 78 100 22 22', 'M', '2009-07-22', 'Yoff Tonghor, Dakar'],
    [8, 1, 'aminata_sow', 'aminata.sow@mboolo.sn', $defaultPasswordHash, 'eleve', 'Aminata', 'SOW', '+221 78 100 33 33', 'F', '2011-10-09', 'Ngor, Dakar'],
    // Parents
    [9, 1, 'parent_diop', 'parent.diop@mboolo.sn', $defaultPasswordHash, 'parent', 'Ibrahima', 'DIOP', '+221 77 400 55 66', 'M', '1972-01-10', 'Mamelles, Dakar'],
    [10, 1, 'parent_fall', 'parent.fall@mboolo.sn', $defaultPasswordHash, 'parent', 'Coumba', 'FALL', '+221 77 400 77 88', 'F', '1976-09-18', 'Yoff Tonghor, Dakar'],

    // --- ÉTABLISSEMENT 2 (TERANGA) ---
    [11, 2, 'directeur_teranga', 'directeur@teranga.sn', $defaultPasswordHash, 'directeur', 'Amadou', 'KANE', '+221 77 300 11 22', 'M', '1973-12-01', 'Quartier Nord, Saint-Louis'],
    [16, 2, 'surveillant_teranga', 'surveillant@teranga.sn', $defaultPasswordHash, 'surveillant', 'Pape', 'FALL', '+221 77 340 55 66', 'M', '1984-03-22', 'Balacos, Saint-Louis'],
    [12, 2, 'prof_svt_teranga', 'prof.svt@teranga.sn', $defaultPasswordHash, 'professeur', 'Khady', 'DIOUF', '+221 77 300 33 44', 'F', '1987-05-19', 'Sor, Saint-Louis'],
    [13, 2, 'ibrahima_sall', 'ibrahima.sall@teranga.sn', $defaultPasswordHash, 'eleve', 'Ibrahima', 'SALL', '+221 78 200 44 55', 'M', '2009-04-10', 'Bango, Saint-Louis'],
    [14, 2, 'parent_sall', 'parent.sall@teranga.sn', $defaultPasswordHash, 'parent', 'Abdoulaye', 'SALL', '+221 77 200 88 99', 'M', '1970-08-30', 'Bango, Saint-Louis']
];

$stmtUser = $pdo->prepare("INSERT INTO `users` (`id`, `school_id`, `username`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `phone`, `gender`, `date_of_birth`, `address`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `email`=VALUES(`email`)");
foreach ($users as $u) {
    $stmtUser->execute($u);
}
echo " -> " . count($users) . " utilisateurs créés (directeurs, profs, secrétaires, élèves, parents).\n";

// 3. Classes
$classes = [
    // School 1 (MBOOLO)
    [1, 1, 'Terminale S2-A', 'Terminale', 'Lycee', 'Scientifique (S2)', '2025-2026', 'Salle 12 (Bâtiment B)', 35],
    [2, 1, 'Première L2-B', 'Première', 'Lycee', 'Littéraire (L2)', '2025-2026', 'Salle 08 (Bâtiment A)', 38],
    [3, 1, 'Troisième A', '3ème', 'College', NULL, '2025-2026', 'Salle 03 (Bâtiment C)', 40],
    // School 2 (TERANGA)
    [4, 2, 'Seconde S1', 'Seconde', 'Lycee', 'Scientifique (S1)', '2025-2026', 'Salle Polyvalente', 36]
];

$stmtClass = $pdo->prepare("INSERT INTO `classes` (`id`, `school_id`, `name`, `level`, `cycle`, `track`, `academic_year`, `main_room`, `capacity`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`)");
foreach ($classes as $c) {
    $stmtClass->execute($c);
}
echo " -> Classes créées.\n";

// 4. Profils élèves
$studentProfiles = [
    // user_id, school_id, class_id, matricule, admission_date, previous_school, blood_group, emergency_contact, emergency_phone
    [6, 1, 1, 'MB-2025-001', '2023-10-02', 'Collège Catholique Saint-Louis', 'O+', 'Ibrahima DIOP (Père)', '+221 77 400 55 66'],
    [7, 1, 2, 'MB-2025-002', '2024-10-01', 'CEM Martin Luther King', 'A+', 'Coumba FALL (Mère)', '+221 77 400 77 88'],
    [8, 1, 3, 'MB-2025-003', '2025-10-01', 'École Primaire Almadies', 'B+', 'Mamadou SOW (Tuteur)', '+221 77 500 99 00'],
    [13, 2, 4, 'TER-2025-101', '2024-10-05', 'Collège André Peytavin', 'O+', 'Abdoulaye SALL (Père)', '+221 77 200 88 99']
];

$stmtSP = $pdo->prepare("INSERT INTO `student_profiles` (`user_id`, `school_id`, `class_id`, `student_matricule`, `admission_date`, `previous_school`, `blood_group`, `emergency_contact`, `emergency_phone`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `class_id`=VALUES(`class_id`)");
foreach ($studentProfiles as $sp) {
    $stmtSP->execute($sp);
}
echo " -> Profils élèves enregistrés.\n";

// 5. Matières
$subjects = [
    // School 1
    [1, 1, 'Mathématiques', 'MATH', '#2563eb', 'Algèbre, Analyse, Géométrie et Probabilités'],
    [2, 1, 'Sciences Physiques & Chimie', 'SPHYS', '#7c3aed', 'Mécanique, Électromagnétisme et Chimie générale'],
    [3, 1, 'Français & Philosophie', 'PHILO', '#d97706', 'Littérature, Dissertation, Commentaire et Pensée critique'],
    [4, 1, 'Sciences de la Vie et de la Terre', 'SVT', '#059669', 'Biologie cellulaire, Génétique et Écologie'],
    [5, 1, 'Anglais', 'ANG', '#0284c7', 'Langue, Civilisation et Expression orale'],
    [6, 1, 'Histoire & Géographie', 'HIST-GEO', '#854d0e', 'Histoire contemporaine et Géographie humaine'],
    [7, 1, 'Éducation Physique & Sportive', 'EPS', '#16a34a', 'Pratique sportive et endurance'],
    // School 2
    [8, 2, 'Sciences de la Vie et de la Terre', 'SVT', '#059669', 'SVT Tronc Commun et Spécialité']
];

$stmtSubj = $pdo->prepare("INSERT INTO `subjects` (`id`, `school_id`, `name`, `code`, `color`, `description`) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`)");
foreach ($subjects as $sub) {
    $stmtSubj->execute($sub);
}
echo " -> Matières créées.\n";

// 6. Affectations Enseignements (class_subjects)
$classSubjects = [
    // id, school_id, class_id, subject_id, teacher_id, coefficient, weekly_hours
    // Terminale S2 (Class 1)
    [1, 1, 1, 1, 3, 6.00, 6.0], // Maths par Prof Babacar Fall
    [2, 1, 1, 2, 4, 6.00, 6.0], // Physique-Chimie par Prof Mariama Ba
    [3, 1, 1, 3, 5, 2.00, 3.0], // Philo par Prof Ousmane Sembène
    // Première L2 (Class 2)
    [4, 1, 2, 3, 5, 6.00, 5.0], // Français/Philo par Prof Ousmane Sembène
    [5, 1, 2, 1, 3, 2.00, 2.0], // Maths par Prof Babacar Fall
    // Troisième A (Class 3)
    [6, 1, 3, 1, 3, 4.00, 4.0], // Maths par Prof Babacar Fall
    [7, 1, 3, 2, 4, 3.00, 3.0], // PC par Prof Mariama Ba
    // Teranga Seconde S1 (Class 4)
    [8, 2, 4, 8, 12, 4.00, 4.0] // SVT Teranga
];

$stmtCS = $pdo->prepare("INSERT INTO `class_subjects` (`id`, `school_id`, `class_id`, `subject_id`, `teacher_id`, `coefficient`, `weekly_hours`, `academic_year`) VALUES (?, ?, ?, ?, ?, ?, ?, '2025-2026') ON DUPLICATE KEY UPDATE `coefficient`=VALUES(`coefficient`)");
foreach ($classSubjects as $cs) {
    $stmtCS->execute($cs);
}
echo " -> Affectations matières-profs créées.\n";

// 7. Emploi du temps (Timetables) avec durée en minutes
$timetables = [
    // id, school_id, class_subject_id, day, start, end, duration_minutes, room
    [1, 1, 1, 'Lundi', '08:00:00', '10:00:00', 120, 'Salle 12'],
    [2, 1, 1, 'Mercredi', '10:15:00', '12:15:00', 120, 'Salle 12'],
    [3, 1, 1, 'Vendredi', '08:00:00', '10:00:00', 120, 'Salle 12'],
    [4, 1, 2, 'Mardi', '08:00:00', '11:00:00', 180, 'Labo Physique B'],
    [5, 1, 2, 'Jeudi', '10:15:00', '13:15:00', 180, 'Labo Chimie A'],
    [6, 1, 3, 'Lundi', '10:15:00', '12:15:00', 120, 'Salle 12'],
    [7, 1, 3, 'Vendredi', '10:15:00', '11:15:00', 60, 'Salle 12'],
    // Pour classe L2
    [8, 1, 4, 'Lundi', '14:00:00', '17:00:00', 180, 'Salle 08'],
    [9, 1, 5, 'Jeudi', '08:00:00', '10:00:00', 120, 'Salle 08']
];

$stmtTT = $pdo->prepare("INSERT INTO `timetables` (`id`, `school_id`, `class_subject_id`, `day_of_week`, `start_time`, `end_time`, `duration_minutes`, `room`) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `room`=VALUES(`room`)");
foreach ($timetables as $tt) {
    $stmtTT->execute($tt);
}
echo " -> Emplois du temps configurés.\n";

// 8. Résumés de leçons (Lesson summaries)
$summaries = [
    [
        1, 1, 1, 3, '2025-10-13',
        'Chapitre 1 : Limites et Continuité des Fonctions Numériques',
        'Étude approfondie des théorèmes de comparaison, formes indéterminées et théorème des valeurs intermédiaires (TVI). Démonstration de l\'unicité de la solution.',
        'Exercices n° 14, 18 et 22 page 48 du manuel CIAM Terminale SM/SE.',
        '2025-10-17'
    ],
    [
        2, 1, 1, 3, '2025-10-17',
        'Chapitre 2 : Dérivation et Étude Globale de Fonctions',
        'Rappels sur le nombre dérivé, interprétation géométrique (tangentes). Dérivation des fonctions composées et réciproques.',
        'Préparer le problème récapitulatif distribué en polycopié.',
        '2025-10-24'
    ],
    [
        3, 1, 2, 4, '2025-10-14',
        'Mécanique Newtonienne : Lois du Mouvement et Cinématique Vectorielle',
        'Vecteur position, vitesse et accélération en repère de Frenet et cartésien. Première et deuxième lois de Newton appliquées au plan incliné.',
        'Résoudre le problème n° 4 de la fiche de travaux dirigés.',
        '2025-10-21'
    ],
    [
        4, 1, 3, 5, '2025-10-13',
        'Introduction à la Philosophie : Raison et Réel',
        'Analyse du passage du Mythe au Logos dans la Grèce antique. Problématique de la vérité et critique des préjugés selon Platon.',
        'Rédiger une introduction de dissertation sur le sujet : "Peut-on douter de tout ?".',
        '2025-10-20'
    ]
];

$stmtSum = $pdo->prepare("INSERT INTO `lesson_summaries` (`id`, `school_id`, `class_subject_id`, `teacher_id`, `lesson_date`, `title`, `summary_content`, `homework`, `homework_due_date`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `title`=VALUES(`title`)");
foreach ($summaries as $sm) {
    $stmtSum->execute($sm);
}
echo " -> Résumés de leçons publiés.\n";

// 9. Évaluations et Notes (Style EcoleDirecte)
$evaluations = [
    // id, school_id, class_subject_id, title, eval_type, semester, max_score, weight, eval_date
    [1, 1, 1, 'Devoir Surveillé n°1 (Limites & Dérivées)', 'devoir', 'Semestre 1', 20.00, 1.00, '2025-10-25'],
    [2, 1, 1, 'Composition Harmonisée du Semestre 1 (Maths)', 'composition', 'Semestre 1', 20.00, 2.00, '2025-12-15'],
    [3, 1, 2, 'Devoir n°1 de Physique (Cinématique)', 'devoir', 'Semestre 1', 20.00, 1.00, '2025-10-28'],
    [4, 1, 2, 'Composition du Semestre 1 (Physique-Chimie)', 'composition', 'Semestre 1', 20.00, 2.00, '2025-12-18'],
    [5, 1, 3, 'Devoir sur table n°1 : Dissertation Philosophique', 'devoir', 'Semestre 1', 20.00, 1.00, '2025-11-04'],
    [6, 1, 3, 'Composition de Français & Philosophie Semestre 1', 'composition', 'Semestre 1', 20.00, 2.00, '2025-12-12']
];

$stmtEval = $pdo->prepare("INSERT INTO `evaluations` (`id`, `school_id`, `class_subject_id`, `title`, `eval_type`, `semester`, `max_score`, `weight`, `eval_date`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `title`=VALUES(`title`)");
foreach ($evaluations as $ev) {
    $stmtEval->execute($ev);
}

// Notes de l'élève Fatou Diop (id = 6)
$grades = [
    // eval_id, student_id, teacher_id, score, is_absent, appreciation
    [1, 6, 3, 17.50, 0, 'Excellente maîtrise des théorèmes, rédaction rigoureuse.'],
    [2, 6, 3, 18.00, 0, 'Très brillante copie d\'analyse mathématique. Félicitations !'],
    [3, 6, 4, 16.00, 0, 'Très bon travail en cinématique, démarche méthodique.'],
    [4, 6, 4, 15.50, 0, 'Bonne épreuve globale, attention aux incertitudes de mesure.'],
    [5, 6, 5, 15.00, 0, 'Belle réflexion conceptuelle et argumentation soignée.'],
    [6, 6, 5, 16.50, 0, 'Analyse philosophique remarquable et style soigné.']
];

$stmtGrade = $pdo->prepare("INSERT INTO `grades` (`evaluation_id`, `school_id`, `student_id`, `entered_by_teacher_id`, `score`, `is_absent`, `appreciation`) VALUES (?, 1, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `score`=VALUES(`score`)");
foreach ($grades as $g) {
    $stmtGrade->execute($g);
}
echo " -> Évaluations et notes saisies.\n";

// 10. Communiqués du Directeur
$announcements = [
    [
        1, 1, 1,
        'Calendrier officiel des Compositions du 1er Semestre 2025-2026',
        "Chers collègues, chers élèves et chers parents,\n\nNous vous informons que les épreuves harmonisées du premier semestre se dérouleront du lundi 15 au vendredi 19 décembre 2025. Les cours reprendront selon l'emploi du temps habituel le 5 janvier 2026 après les congés de fin d'année.\n\nNous exhortons les élèves à intensifier leurs révisions dès à présent.",
        'all',
        'urgent',
        1,
        '2025-11-20 09:00:00'
    ],
    [
        2, 1, 1,
        'Ouverture du guichet de règlement des mensualités & Paiement Digital Wave / Orange Money',
        "Afin de simplifier vos démarches, l'établissement MBOOLO rappelle aux parents d'élèves que les mensualités scolaires peuvent désormais être réglées directement en ligne depuis votre Espace Parent via Wave, Orange Money et Carte Bancaire avec délivrance automatique et instantanée du reçu officiel.",
        'parents',
        'normal',
        0,
        '2025-10-05 10:30:00'
    ],
    [
        3, 1, 1,
        'Dispositif de soutien scolaire et tutorat pour les classes d\'examen (Terminale & 3ème)',
        "Des séances hebdomadaires de renforcement pédagogique en Mathématiques et Sciences Physiques sont organisées tous les samedis matins de 09h00 à 12h00 pour tous les élèves de Terminale S2.",
        'eleves',
        'normal',
        0,
        '2025-10-10 14:00:00'
    ]
];

$stmtAnn = $pdo->prepare("INSERT INTO `announcements` (`id`, `school_id`, `author_id`, `title`, `content`, `target_audience`, `urgency_level`, `is_pinned`, `published_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `title`=VALUES(`title`)");
foreach ($announcements as $an) {
    $stmtAnn->execute($an);
}
echo " -> Communiqués du directeur publiés.\n";

// 11. Convocations de réunions pour les professeurs (Ordinaires et Urgentes)
$meetings = [
    [
        1, 1, 1,
        'Conseil Pédagogique Ordinaire — Bilan d\'Étape & Suivi des Programmes',
        'ordinaire',
        '2025-11-15 15:30:00',
        'Salle des Professeurs (Bâtiment Direction)',
        "Ordre du jour :\n1. Évaluation de l'état d'avancement des progressions pédagogiques par discipline.\n2. Organisation des épreuves blanches et harmonisées du Semestre 1.\n3. Analyse des besoins en matériel pédagogique et consommables scientifiques.\n4. Questions diverses.",
        'terminee'
    ],
    [
        2, 1, 1,
        'URGENT : Réunion Extraordinaire des Professeurs de Terminale — Harmonisation des Compositions',
        'urgente',
        '2025-12-08 17:00:00',
        'Amphithéâtre Cheikh Anta Diop',
        "Ordre du jour :\n1. Validation définitive des sujets d'examen du Semestre 1.\n2. Grille de correction et barèmes officiels.\n3. Dispositif de surveillance et respect scrupuleux de l'anonymat.",
        'planifiee'
    ]
];

$stmtMeet = $pdo->prepare("INSERT INTO `meetings` (`id`, `school_id`, `organizer_id`, `title`, `meeting_type`, `meeting_date`, `location`, `agenda`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `title`=VALUES(`title`)");
foreach ($meetings as $m) {
    $stmtMeet->execute($m);
}

// Participations aux réunions
$participants = [
    // meeting_id, teacher_id, read_receipt, read_at, attendance_status, response_comment
    [1, 3, 1, '2025-11-12 10:14:00', 'present', 'Je serai présent avec les fiches de progression en Maths.'],
    [1, 4, 1, '2025-11-12 11:30:00', 'present', 'Présente.'],
    [1, 5, 1, '2025-11-13 09:00:00', 'present', 'Confirmé.'],
    // Réunion urgente 2
    [2, 3, 1, '2025-12-05 08:45:00', 'present', 'Sujet de Maths Terminale S2 prêt et sous pli confidentiel.'],
    [2, 4, 1, '2025-12-05 09:12:00', 'present', 'Présente avec la grille de physique-chimie.'],
    [2, 5, 0, NULL, 'en_attente', NULL]
];

$stmtPart = $pdo->prepare("INSERT INTO `meeting_participants` (`meeting_id`, `teacher_id`, `read_receipt`, `read_at`, `attendance_status`, `response_comment`) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `attendance_status`=VALUES(`attendance_status`)");
foreach ($participants as $pt) {
    $stmtPart->execute($pt);
}
echo " -> Convocations de réunions enregistrées.\n";

// 12. Rapprochement Parents - Élèves
$parentStudents = [
    // id, school_id, parent_id, student_id, relation, is_primary
    [1, 1, 9, 6, 'Pere', 1],  // M. Ibrahima Diop est le père de Fatou Diop (Terminale S2)
    [2, 1, 10, 7, 'Mere', 1], // Mme Coumba Fall est la mère de Moussa Fall (1ère L2)
    [3, 2, 14, 13, 'Pere', 1] // Abdoulaye Sall est le père de Ibrahima Sall (Teranga)
];

$stmtPS = $pdo->prepare("INSERT INTO `parent_students` (`id`, `school_id`, `parent_id`, `student_id`, `relationship_type`, `is_primary_guardian`) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `relationship_type`=VALUES(`relationship_type`)");
foreach ($parentStudents as $ps) {
    $stmtPS->execute($ps);
}
echo " -> Liens parents-élèves établis.\n";

// 13. Échéancier des frais scolaires (fee_schedules)
$feeSchedules = [
    // id, school_id, class_id, fee_title, month, year, amount_due, due_date
    [1, 1, 1, 'Frais d\'inscription & Assurance', 'Octobre', 2025, 50000.00, '2025-10-05'],
    [2, 1, 1, 'Mensualité Octobre 2025', 'Octobre', 2025, 45000.00, '2025-10-10'],
    [3, 1, 1, 'Mensualité Novembre 2025', 'Novembre', 2025, 45000.00, '2025-11-10'],
    [4, 1, 1, 'Mensualité Décembre 2025', 'Décembre', 2025, 45000.00, '2025-12-10'],
    [5, 1, 1, 'Mensualité Janvier 2026', 'Janvier', 2026, 45000.00, '2026-01-10'],
    [6, 1, 1, 'Mensualité Février 2026', 'Février', 2026, 45000.00, '2026-02-10'],
    // Pour la classe de 1ère L2 (Class 2)
    [7, 1, 2, 'Mensualité Octobre 2025', 'Octobre', 2025, 40000.00, '2025-10-10'],
    [8, 1, 2, 'Mensualité Novembre 2025', 'Novembre', 2025, 40000.00, '2025-11-10'],
    [9, 1, 2, 'Mensualité Décembre 2025', 'Décembre', 2025, 40000.00, '2025-12-10']
];

$stmtFS = $pdo->prepare("INSERT INTO `fee_schedules` (`id`, `school_id`, `class_id`, `fee_title`, `month`, `year`, `amount_due`, `due_date`) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `amount_due`=VALUES(`amount_due`)");
foreach ($feeSchedules as $fs) {
    $stmtFS->execute($fs);
}
echo " -> Échéancier de scolarité configuré.\n";

// 14. Paiements des élèves (En ligne & Guichet)
$payments = [
    // Fatou Diop a réglé Octobre par Wave en ligne
    [
        1, 1, 6, 2, NULL, 45000.00, 'wave', 'paye', 'WAVE-TX-84920491',
        json_encode(['provider' => 'Wave SN', 'status' => 'SUCCESS', 'phone' => '+221 77 400 55 66', 'fee' => 0]),
        '2025-10-08 14:22:10', 'REC-2025-0012', 'Paiement en ligne par le parent via Wave'
    ],
    // Fatou Diop a réglé Novembre au guichet du secrétariat en espèces
    [
        2, 1, 6, 3, 2, 45000.00, 'especes', 'paye', 'CASH-REC-2025-0089',
        json_encode(['agent' => 'Aissatou Ndiaye (Secrétaire)', 'mode' => 'Espèces']),
        '2025-11-06 10:15:00', 'REC-2025-0089', 'Règlement au comptoir délivré par la secrétaire'
    ],
    // Moussa Fall a réglé Octobre par Orange Money en ligne
    [
        3, 1, 7, 7, NULL, 40000.00, 'orange_money', 'paye', 'OM-SN-29384721',
        json_encode(['provider' => 'Orange Money', 'status' => 'CONFIRMED', 'phone' => '+221 77 400 77 88']),
        '2025-10-09 16:40:00', 'REC-2025-0034', 'Paiement en ligne via Orange Money'
    ]
];

$stmtPay = $pdo->prepare("INSERT INTO `student_payments` (`id`, `school_id`, `student_id`, `fee_schedule_id`, `received_by_user_id`, `amount_paid`, `payment_method`, `payment_status`, `transaction_ref`, `gateway_response`, `payment_date`, `receipt_number`, `notes`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `amount_paid`=VALUES(`amount_paid`)");
foreach ($payments as $p) {
    $stmtPay->execute($p);
}
echo " -> Paiements enregistrés (Wave, Orange Money, Guichet).\n";

// 15. Salaires des professeurs
$salaries = [
    // id, school_id, teacher_id, processor_id, month, year, base, bonus, deductions, net, status, date, method, ref, notes
    [
        1, 1, 3, 2, 'Octobre', 2025, 380000.00, 45000.00, 18000.00, 407000.00,
        'paye', '2025-10-30', 'virement', 'PAYSLIP-2025-10-FALL', 'Salaire mensuel Octobre + 6h supplémentaires'
    ],
    [
        2, 1, 3, 2, 'Novembre', 2025, 380000.00, 30000.00, 18000.00, 392000.00,
        'paye', '2025-11-28', 'virement', 'PAYSLIP-2025-11-FALL', 'Salaire mensuel Novembre viré sur compte CBAO'
    ],
    [
        3, 1, 3, 2, 'Décembre', 2025, 380000.00, 60000.00, 18000.00, 422000.00,
        'en_attente', NULL, 'virement', 'PAYSLIP-2025-12-FALL', 'En cours d\'ordonnancement pour fin décembre'
    ],
    // Prof Mariama Ba (Physique-Chimie)
    [
        4, 1, 4, 2, 'Octobre', 2025, 360000.00, 35000.00, 16000.00, 379000.00,
        'paye', '2025-10-30', 'virement', 'PAYSLIP-2025-10-BA', 'Salaire mensuel + prime responsable de laboratoire'
    ],
    [
        5, 1, 4, 2, 'Novembre', 2025, 360000.00, 35000.00, 16000.00, 379000.00,
        'paye', '2025-11-28', 'virement', 'PAYSLIP-2025-11-BA', 'Salaire mensuel Novembre viré sur compte Ecobank'
    ],
    // Prof Ousmane Sembene (Philo)
    [
        6, 1, 5, 2, 'Octobre', 2025, 370000.00, 20000.00, 17000.00, 373000.00,
        'paye', '2025-10-30', 'virement', 'PAYSLIP-2025-10-SEMBENE', 'Salaire mensuel Octobre'
    ]
];

$stmtSal = $pdo->prepare("INSERT INTO `teacher_salaries` (`id`, `school_id`, `teacher_id`, `processed_by_user_id`, `month`, `year`, `base_salary`, `bonus`, `deductions`, `net_salary`, `payment_status`, `payment_date`, `payment_method`, `payslip_reference`, `notes`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `net_salary`=VALUES(`net_salary`)");
foreach ($salaries as $sal) {
    $stmtSal->execute($sal);
}
echo " -> Fiches de paie et salaires des professeurs configurés.\n";

// 16. Bulletins Scolaires Automatiques (Semestre 1)
// Fatou Diop : Moyenne Maths = (17.5*1 + 18.0*2)/3 = 53.5/3 = 17.83 (coef 6 => 107.0)
// Physique = (16.0*1 + 15.5*2)/3 = 47.0/3 = 15.67 (coef 6 => 94.0)
// Philo = (15.0*1 + 16.5*2)/3 = 48.0/3 = 16.00 (coef 2 => 32.0)
// Total Coef = 14. Total points = 233. Moyenne générale = 233 / 14 = 16.64 / 20. Rang : 1ère / 35.
$reportCards = [
    [
        1, 1, 6, 1, 'Semestre 1', '2025-2026', 16.64, 1, 09.20, 16.64, 12.85, 14.00, 233.00,
        'Excellente élève, travail régulier, remarquable assiduité et grande rigueur intellectuelle. Félicitations du Conseil de Classe !',
        'Comportement exemplaire', 0, 'valide'
    ]
];

$stmtRC = $pdo->prepare("INSERT INTO `report_cards` (`id`, `school_id`, `student_id`, `class_id`, `semester`, `academic_year`, `general_average`, `class_rank`, `class_min_average`, `class_max_average`, `class_average`, `total_coefficients`, `total_points`, `principal_appreciation`, `conduct`, `absences_count`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `general_average`=VALUES(`general_average`)");
foreach ($reportCards as $rc) {
    $stmtRC->execute($rc);
}

// Détails des moyennes par matière du bulletin
$rcDetails = [
    // report_card_id, subject_id, teacher_id, subject_average, coef, weighted_avg, class_subj_avg, appreciation
    [1, 1, 3, 17.83, 6.00, 107.00, 12.50, 'Excellent travail tout au long du semestre. Raisonnement mathématique très structuré.'],
    [1, 2, 4, 15.67, 6.00, 94.00, 11.80, 'Très bon semestre en sciences physiques. Démarche expérimentale de qualité.'],
    [1, 3, 5, 16.00, 2.00, 32.00, 13.10, 'Brillante réflexion philosophique et excellente finesse d\'analyse.']
];

$stmtRCD = $pdo->prepare("INSERT INTO `report_card_details` (`report_card_id`, `subject_id`, `teacher_id`, `subject_average`, `coefficient`, `weighted_average`, `class_subject_average`, `teacher_appreciation`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($rcDetails as $rcd) {
    $stmtRCD->execute($rcd);
}
echo " -> Bulletin officiel du Semestre 1 généré avec succès.\n";

// 17. Audit Logs (Traçabilité des opérations sensibles)
$auditLogs = [
    [1, 1, 'LOGIN', 'users', 1, 'Connexion réussie du Directeur Cheikh Anta Diop', '127.0.0.1'],
    [1, 3, 'NOTE_SAISIE', 'grades', 1, 'Saisie de la note de Devoir n°1 de Maths pour Fatou Diop (Note: 17.5/20)', '127.0.0.1'],
    [1, 2, 'PAIEMENT_VALIDE', 'student_payments', 2, 'Validation manuelle du reçu de scolarité Novembre REC-2025-0089', '127.0.0.1'],
    [1, 1, 'CONVOCATION_CREEE', 'meetings', 2, 'Diffusion de la convocation urgente pour les professeurs de Terminale', '127.0.0.1']
];

$stmtAudit = $pdo->prepare("INSERT INTO `audit_logs` (`school_id`, `user_id`, `action`, `target_table`, `record_id`, `details`, `ip_address`) VALUES (?, ?, ?, ?, ?, ?, ?)");
foreach ($auditLogs as $al) {
    $stmtAudit->execute($al);
}
echo " -> Journaux d'audit et de traçabilité initialisés.\n";

// 18. Synchronisation Pédagogique Intégrale (Élémentaire, Collège, Lycée S, L, G, T)
require_once __DIR__ . '/migration_pedagogy.php';

echo "[OK] Peuplement complet de la base de données MBOOLO terminé avec succès !\n";

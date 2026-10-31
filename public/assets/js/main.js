/**
 * MBOOLO — Scripts front-end interactifs
 * - Gestion de la Sidebar fixe et rétractable (Collapse / Expand)
 * - Gestion du Thème (Lumineux / Sombre) avec persistance
 * - Impression / Export PDF
 * - Modales et menu mobile
 */

// Initialisation immédiate du thème et de la sidebar pour éviter tout clignotement
(function() {
    // 1. Thème
    const savedTheme = localStorage.getItem('mboolo_theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);

    // 2. État rétracté de la barre latérale sur grand écran
    if (localStorage.getItem('mboolo_sidebar_collapsed') === '1' && window.innerWidth > 992) {
        document.documentElement.classList.add('sidebar-is-collapsed');
    }
})();

document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
    const sidebarCloseBtn = document.getElementById('sidebar-close-btn');
    const sidebarBackdrop = document.getElementById('sidebar-backdrop');
    const themeToggleBtn = document.getElementById('theme-toggle-btn');

    // 1. Appliquer l'état sauvegardé de la barre latérale sur grand écran
    if (sidebar && window.innerWidth > 992) {
        const isCollapsed = localStorage.getItem('mboolo_sidebar_collapsed') === '1';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            document.documentElement.classList.add('sidebar-is-collapsed');
            document.body.classList.add('sidebar-is-collapsed');
        } else {
            sidebar.classList.remove('collapsed');
            document.documentElement.classList.remove('sidebar-is-collapsed');
            document.body.classList.remove('sidebar-is-collapsed');
        }
    }

    // 2. UN SEUL BOUTON SANS ÉCRITURE pour afficher ou masquer la barre latérale
    if (sidebarToggleBtn && sidebar) {
        sidebarToggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (window.innerWidth > 992) {
                // Desktop : on bascule l'état rétracté pour agrandir la page à 100% ou la remettre
                const isCurrentlyCollapsed = sidebar.classList.contains('collapsed');
                if (isCurrentlyCollapsed) {
                    sidebar.classList.remove('collapsed');
                    document.documentElement.classList.remove('sidebar-is-collapsed');
                    document.body.classList.remove('sidebar-is-collapsed');
                    localStorage.setItem('mboolo_sidebar_collapsed', '0');
                } else {
                    sidebar.classList.add('collapsed');
                    document.documentElement.classList.add('sidebar-is-collapsed');
                    document.body.classList.add('sidebar-is-collapsed');
                    localStorage.setItem('mboolo_sidebar_collapsed', '1');
                }
            } else {
                // Mobile / Tablette : tiroir ouvrant avec fond d'atténuation
                const isOpen = sidebar.classList.contains('open');
                if (isOpen) {
                    closeMobileSidebar();
                } else {
                    openMobileSidebar();
                }
            }
        });
    }

    // 3. Bouton croix pour fermer directement depuis l'intérieur de la barre
    if (sidebarCloseBtn && sidebar) {
        sidebarCloseBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (window.innerWidth > 992) {
                sidebar.classList.add('collapsed');
                document.documentElement.classList.add('sidebar-is-collapsed');
                document.body.classList.add('sidebar-is-collapsed');
                localStorage.setItem('mboolo_sidebar_collapsed', '1');
            } else {
                closeMobileSidebar();
            }
        });
    }

    // 4. Clic sur le fond semi-transparent pour fermer sur mobile
    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', function () {
            closeMobileSidebar();
        });
    }

    function openMobileSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('open');
        if (sidebarBackdrop) sidebarBackdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('open');
        if (sidebarBackdrop) sidebarBackdrop.classList.remove('active');
        document.body.style.overflow = '';
    }

    // 5. Gestion du Thème (Lumineux / Sombre)
    updateThemeButton();

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', function () {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('mboolo_theme', newTheme);
            document.cookie = "mboolo_theme=" + newTheme + "; path=/; max-age=" + (365 * 24 * 60 * 60);

            updateThemeButton();
        });
    }

    function updateThemeButton() {
        if (!themeToggleBtn) return;
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const icon = themeToggleBtn.querySelector('.theme-icon');
        const label = themeToggleBtn.querySelector('.theme-label');
        
        if (currentTheme === 'dark') {
            if (icon) icon.textContent = '☀️';
            if (label) label.textContent = 'Mode Clair';
            themeToggleBtn.setAttribute('title', 'Activer le mode lumineux');
        } else {
            if (icon) icon.textContent = '🌙';
            if (label) label.textContent = 'Mode Sombre';
            themeToggleBtn.setAttribute('title', 'Activer le mode sombre');
        }
    }

    // 4. Gestion des modales simples
    const modalTriggers = document.querySelectorAll('[data-modal-target]');
    modalTriggers.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-modal-target');
            const modal = document.getElementById(targetId);
            if (modal) modal.style.display = 'flex';
        });
    });

    const modalCloses = document.querySelectorAll('[data-modal-close]');
    modalCloses.forEach(btn => {
        btn.addEventListener('click', function () {
            this.closest('.modal-overlay').style.display = 'none';
        });
    });

    // 5. Impression / Téléchargement PDF du bulletin
    const printBtn = document.getElementById('print-report-card-btn');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.print();
        });
    }
});

function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'flex';
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

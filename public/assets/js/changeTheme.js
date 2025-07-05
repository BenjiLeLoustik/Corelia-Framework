// Fonction pour appliquer un thème (clair ou sombre)
function setTheme(theme) {
    // Modifie l'attribut 'data-theme' de la balise <html> pour appliquer le thème via CSS
    document.documentElement.setAttribute('data-theme', theme);
    // Enregistre le thème choisi dans le localStorage pour conserver le choix de l'utilisateur
    localStorage.setItem('theme', theme);
}

// Fonction pour basculer entre le thème clair et le thème sombre
function toggleTheme() {
    // Récupère le thème actuel depuis l'attribut 'data-theme', ou 'light' par défaut
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    // Applique le thème opposé : si 'light', passe à 'dark', et inversement
    setTheme(current === 'light' ? 'dark' : 'light');
}

// Fonction auto-exécutée pour initialiser le thème au chargement de la page
(function () {
    // Récupère le thème sauvegardé dans le localStorage, s'il existe
    const saved = localStorage.getItem('theme');
    if (saved)
        // Si un thème est sauvegardé, l'applique
        setTheme(saved);
    else if (window.matchMedia("(prefers-color-scheme: dark)").matches)
        // Sinon, si l'utilisateur préfère le mode sombre selon les préférences système, l'applique
        setTheme("dark");
    // Sinon, aucun thème n'est appliqué explicitement : le CSS par défaut sera utilisé
})();

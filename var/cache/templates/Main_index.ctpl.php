<?php /* Compilé par CoreliaTemplate, ne pas éditer */ ?>
<!-- /src/Views/base.ctpl -->

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <title>CoreliaTemplate - Dashboard Moderne 🚀</title>
    <link rel="stylesheet" href="/assets/css/default.css" />
    <script>
        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
        }
        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme') || 'light';
            setTheme(current === 'light' ? 'dark' : 'light');
        }
        (function () {
            const saved = localStorage.getItem('theme');
            if (saved) setTheme(saved);
            else if (window.matchMedia("(prefers-color-scheme: dark)").matches) setTheme("dark");
        })();
    </script>
</head>

<body>
    <header class="header">
        <span class="logo">🚀 CoreliaPHP</span>
        <nav class="nav">
            <a href="/" class="active"><span>🏠</span>Accueil</a>
            <a href="/admin"><span>🛠️</span>Admin</a>
            <a href="/mes-sites"><span>🌍</span>Mes sites</a>
            <a href="/modules"><span>📦</span>Modules</a>
            <a href="/contact"><span>📬</span>Contact</a>
            <button class="theme-toggle-btn" onclick="toggleTheme()" title="Changer de thème">🌓</button>
        </nav>
    </header>
    
<section class="hero">
    <h1>Bienvenue sur <span>CoreliaPHP</span> !</h1>
    <p>
        Un framework PHP modulaire et clair, pensé pour la productivité.<br>
        <b>Votre point de départ pour des applications web robustes et élégantes.</b>
    </p>
    <a href="/docs" class="cta">Voir la documentation</a>
</section>
<section class="docs">
    <div class="card">
        <div class="icon">📖</div>
        <h2>Présentation</h2>
        <p>
            <b>CoreliaTemplate</b> facilite le développement PHP moderne : code clair, structure modulaire, et
            configuration simple.
        </p>
    </div>
    <div class="card">
        <div class="icon">🗂️</div>
        <h2>Structure du projet</h2>
        <p>
            <code>core/</code> : cœur du framework<br>
            <code>modules/</code> : modules<br>
            <code>templates/</code> : vues <code>.ctpl</code><br>
            <code>public/</code> : fichiers publics<br>
            <code>config/</code> : configuration
        </p>
    </div>
    <div class="card">
        <div class="icon">🔌</div>
        <h2>Système de modules</h2>
        <p>
            Ajoutez ou retirez des fonctionnalités en déposant vos modules dans <code>modules/</code> et en les
            déclarant dans la config.
        </p>
    </div>
    <div class="card">
        <div class="icon">📝</div>
        <h2>Bonnes pratiques</h2>
        <p>
            Documentez avec <code>PHPDoc</code>, organisez avec des namespaces, et utilisez <code>.env</code> ou
            <code>config.json</code> pour la configuration.
        </p>
    </div>
    <div class="card">
        <div class="icon">💡</div>
        <h2>Pour aller plus loin</h2>
        <p>
            Explorez <code>docs/</code>, personnalisez vos templates et modules, et profitez d’une architecture
            claire et maintenable.
        </p>
    </div>
</section>

</body>

</html>
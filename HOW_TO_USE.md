# 📖 Guide d’utilisation du framework Corelia

Ce guide explique comment utiliser chaque partie du framework Corelia :  
structure des fichiers, modules, contrôleurs, templates, CLI, bonnes pratiques, etc.

---

## 📁 1. Structure du projet

```
/src
├── /Controllers
│   ├── HomeController.php
│   └── AdminController.php
├── /Modules
│   ├── /Logger
│   │   ├── LoggerModule.php
│   │   ├── config.json
│   │   └── views/
│   ├── /Cache
│   │   ├── CacheModule.php
│   │   ├── config.json
│   │   └── views/
│   └── ... (autres modules)
├── /Template
│   └── CoreliaTemplate.php
├── /Views
│   ├── base.html.twig
│   ├── welcome.ctpl
│   └── /admin
│       └── modules.ctpl
│       └── ... (autres templates admin)
├── /cli
│   └── corelia
/public
└── /assets
    ├── /css
    └── /js
```

### 📂 Détail des dossiers/fichiers

- 🧑‍💻 **/src/Controllers/** : Contrôleurs PHP (une classe par page ou groupe de routes)
- 🧩 **/src/Modules/** : Modules autonomes (fonctionnalités réutilisables, activables/désactivables)
- 🖋️ **/src/Template/CoreliaTemplate.php** : Moteur de template principal
- 🖼️ **/src/Views/** : Templates globaux (base, accueil, admin, etc.)
- 🖥️ **/cli/corelia** : Script CLI pour la gestion du framework
- 📦 **/public/assets/** : Fichiers statiques (CSS, JS, images)

---

## 🔧 2. Les modules

Chaque module est un dossier dans `/src/Modules/` contenant :

- 📄 **config.json** : Métadonnées, statut, dépendances
- 🗂️ **NomModule.php** : Classe principale du module
- 🖼️ **views/** : Templates spécifiques au module

**Exemple de config.json** :

```
{
    "name": "Logger",
    "version": "1.2.0",
    "description": "Composant de journalisation.",
    "status": "enabled"
}
```

**Exemple de classe module** :

```
class LoggerModule {
    public function boot() {
        // Initialisation du module
    }
}
```

---

## 🛣️ 3. Les contrôleurs

Un contrôleur gère une route.  
Exemple : `/src/Controllers/HomeController.php`

```
use Corelia\Template\CoreliaTemplate;

class HomeController {
    public function index() {
        $tpl = new CoreliaTemplate(DIR . '/../Views/welcome.ctpl');
        echo $tpl->render([
            'user' => ['name' => 'Alice', 'role' => 'admin'],
            'modules' => $this->getModules()
        ]);
    }
}
```

---

## 🖋️ 4. Les templates (CoreliaTemplate)

### Syntaxe Twig-like

- 🔤 **Variables** : `{{ user.name }}`
- 🔄 **Boucles** :

```
{% for module in modules %}
    {{ module.name }}
    {% if loop.first %}[Premier]{% endif %}
{% endfor %}
```

- 🔀 **Conditions** :

```
{% if module.status in ["enabled", "available"] %}{% endif %}
```

- 📝 **Définition de variables** :

```
{% set titre = "Bienvenue" %}
{% set modules = [
    {"name": "Logger", "status": "enabled"},
    {"name": "Cache", "status": "disabled"}
] %}
```

- 🎨 **Filtres** : `upper`, `lower`, `date`, `raw`

```
{{ user.name|upper }}
{{ now|date("d/m/Y") }}
```

- 📚 **Blocs et héritage** :

```
{% extends 'base.html.twig' %}
{% block content %}...{% endblock %}
```

- ➕ **Include** :

```
{% include 'admin/sidebar.ctpl' %}
```

- 💬 **Commentaires** :

```
{# Ceci est un commentaire #}
```

**Important** : Les objets/tableaux dans `{% set ... = ... %}` doivent être du JSON valide.

---

## 🖥️ 5. Interface d’administration

- 🧭 **Templates** dans `/src/Views/admin/`
- 📋 **Sidebar, statuts, actions modules** : tout est dynamique via le template
- 🎛️ **Exemple** :

```
{% for module in modules %}
    <div class="module-card {{ module.status }}">
    <h3>{{ module.name }}</h3>
    <span>{{ module.status|upper }}</span>
    {% if module.status == "enabled" %}
        <button>Désactiver</button>
    {% elseif module.status == "available" %}
        <button>Installer</button>
    {% endif %}
    </div>
{% endfor %}
```

---

## 🖥️ 6. Utilisation de la CLI

Le script `/cli/corelia` permet de gérer le framework en ligne de commande.

**Commandes disponibles** :

- 📋 `corelia module:list` — Liste tous les modules et leurs statuts
- ✅ `corelia module:enable Logger` — Active le module Logger
- ❌ `corelia module:disable Logger` — Désactive le module Logger
- 🧹 `corelia cache:clear` — Vide le cache
- 🚀 `corelia migrate` — Exécute les migrations

**Exemple d’utilisation** :

```
php cli/corelia module:list
php cli/corelia module:enable Logger
```

---

## 🧑‍💼 7. Bonnes pratiques

- 🧩 **Modules** : autonomes, bien documentés, pas de dépendance cyclique
- 🔤 **Templates** : objets dans `{% set ... = ... %}` toujours en JSON valide
- 🛠️ **Contrôleurs** : une classe par page/groupe de routes, code clair
- 💬 **CLI** : commandes explicites, messages clairs
- 🗂️ **Organisation** : dossiers logiques, noms de fichiers explicites

---

## 🧪 8. Exemple de projet minimal

```
/src
├── /Controllers
│   └── HomeController.php
├── /Modules
│   └── /Logger
│       ├── LoggerModule.php
│       ├── config.json
│       └── views/
│           └── info.ctpl
├── /Template
│   └── CoreliaTemplate.php
├── /Views
│   └── welcome.ctpl
/cli
└── corelia
/public
└── /assets
    └── /css
        └── style.css
```

---

## ⚠️ 9. Limitations connues

- 🚫 Les macros Twig, imports, filtres personnalisés ne sont pas supportés nativement.
- 📜 Les objets dans `{% set ... = ... %}` doivent être du JSON valide.
- 🧩 Les modules doivent être bien isolés.

---

Pour toute question ou contribution, consultez le code source ou contactez l’équipe Corelia.  
✨ Bon développement avec Corelia !
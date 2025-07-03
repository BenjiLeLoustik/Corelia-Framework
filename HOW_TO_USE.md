# 📚 Documentation CoreliaPHP

## 📖 Table des matières

1. [🚀 Présentation générale](#présentation-générale)
2. [📁 Structure des dossiers](#structure-des-dossiers)
3. [🔄 Cycle de vie d’une requête](#cycle-de-vie-dune-requête)
4. [🧩 Modules](#modules)
5. [🛣️ Routing](#routing)
6. [🧑‍💻 Contrôleurs](#contrôleurs)
7. [🎨 Templates](#templates)
8. [🎯 Gestion des événements](#gestion-des-événements)
9. [📦 Réponses HTTP](#réponses-http)
10. [💻 CLI (Command Line Interface)](#cli-command-line-interface)
11. [📝 Exemples pratiques](#exemples-pratiques)
12. [💡 Bonnes pratiques & extensions](#bonnes-pratiques--extensions)

## 🚀 Présentation générale

CoreliaPHP est un micro-framework PHP modulaire, moderne et extensible.  
Il propose :
- Un système de modules activables/désactivables
- Un routing avancé (attributs PHP 8+, config JSON)
- Un moteur de templates simple et efficace
- Un CLI pour automatiser la gestion des modules et contrôleurs
- Une architecture claire et commentée, idéale pour apprendre ou démarrer rapidement un projet web.

## 📁 Structure des dossiers

L’arborescence d’un projet CoreliaPHP ressemble à ceci :

- `/core/` : Cœur du framework (événements, HTTP, modules, routing, templates, kernel)
- `/modules/` : Vos modules personnalisés (un dossier par module)
- `/src/Controller/` : Contrôleurs principaux de l’application
- `/src/Views/` : Templates `.ctpl`
- `/src/routes.php` : Déclaration des routes
- `/vendor/` : Dépendances Composer
- `.env` : Variables d’environnement
- `corelia` : CLI principal

## 🔄 Cycle de vie d’une requête

1. **Entrée** : Le Kernel reçoit la requête HTTP.
2. **Chargement des modules** : Activation, résolution des dépendances, chargement des routes.
3. **Routing** : Recherche de la route correspondante (attributs ou config).
4. **Contrôleur** : Exécution de la méthode du contrôleur.
5. **Réponse** : Génération de la réponse (HTML, JSON, redirection…).
6. **Templates** : Si besoin, rendu du template avec les données.
7. **Sortie** : Envoi de la réponse au client.

## 🧩 Modules

- Un module est un dossier dans `/modules/` contenant un `config.json`, des contrôleurs, et des vues.
- L’activation/désactivation se fait via le CLI ou en modifiant la clé `enabled` dans `config.json`.
- Les dépendances sont automatiquement vérifiées et résolues entre modules.
- Les routes d’un module peuvent être déclarées dans `config.json` ou via des attributs dans les contrôleurs.

## 🛣️ Routing

- **Par attributs** (recommandé) : Utilisez les attributs PHP 8+ dans vos contrôleurs pour déclarer les routes.
- **Par configuration** : Ajoutez les routes dans le `config.json` d’un module ou dans `src/routes.php`.

## 🧑‍💻 Contrôleurs

- Les contrôleurs sont placés dans `/src/Controller/` ou dans le dossier de votre module.
- Ils héritent généralement de `BaseController`.
- Les méthodes publiques annotées avec `RouteAttribute` deviennent des actions accessibles via une URL.

## 🎨 Templates

- Les templates `.ctpl` sont placés dans `/src/Views/` ou dans le dossier `Views` d’un module.
- Utilisez l’héritage de templates pour factoriser votre HTML (ex : `base.ctpl`).
- Les blocs principaux sont : `title`, `meta_description`, `style`, `h1`, `content`, `script`, etc.
- Les templates sont compatibles avec les bonnes pratiques SEO (balises meta, title, canonical, etc.).

## 🎯 Gestion des événements

- Utilisez le gestionnaire d’événements (`EventDispatcher`) pour ajouter des listeners et déclencher des événements personnalisés dans vos modules ou contrôleurs.
- Permet d’étendre le comportement du framework sans modifier son cœur.

## 📦 Réponses HTTP

- Plusieurs types de réponses sont disponibles :
  - **Response** : réponse HTML classique
  - **JsonResponse** : réponse JSON pour les APIs
  - **RedirectResponse** : redirection HTTP

## 💻 CLI (Command Line Interface)

Lancez la commande suivante pour afficher l’aide :

```
php corelia help
```

Commandes principales :
- `module:list` — Liste les modules
- `module:enable <module>` — Active un module
- `module:disable <module>` — Désactive un module
- `make:module <name>` — Crée un nouveau module
- `make:controller <module> <name>` — Crée un contrôleur

## 📝 Exemples pratiques

### Créer une nouvelle page

1. Créez un contrôleur dans `/src/Controller/`
2. Déclarez la route correspondante (attribut ou config)
3. Créez le template dans `/src/Views/`

### Créer un module

1. Utilisez la commande CLI `php corelia make:module Blog`
2. Personnalisez le contrôleur et les vues du module
3. Activez le module avec `php corelia module:enable Blog` si besoin

## 💡 Bonnes pratiques & extensions

- **Sécurité** : Validez et nettoyez toutes les entrées utilisateur.
- **Tests** : Ajoutez des tests unitaires pour vos modules et routes critiques.
- **Performance** : Utilisez le cache pour les routes et la configuration si nécessaire.
- **Extensibilité** : Ajoutez des middlewares, services, ou étendez le moteur de template selon vos besoins.

## 🚦 Pour aller plus loin

- Ajoutez des middlewares pour la gestion des accès ou des logs.
- Créez vos propres filtres pour le moteur de template.
- Intégrez un ORM ou un système de migration si besoin.

**Besoin d’un exemple ou d’un guide sur une fonctionnalité précise ?**  
N’hésitez pas à demander ! 🚀

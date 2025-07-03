# Corelia Framework

**Corelia** est un framework PHP modulaire et moderne conçu pour créer rapidement des applications web robustes, extensibles et maintenables.  
Il intègre un moteur de template avancé (CoreliaTemplate) à la syntaxe Twig-like, un système de modules autonome, un routeur simple et une interface d’administration élégante.

## Points forts

- **Architecture modulaire** : chaque fonctionnalité est un module indépendant, facile à activer/désactiver.
- **Templates Twig-like** : écriture rapide, héritage, boucles, conditions, filtres et includes.
- **Contrôleurs clairs** : chaque page ou action est une classe dédiée.
- **CLI intégré** : automatisez la gestion des modules, du cache, des migrations, etc.
- **Interface d’administration responsive** : gérez vos modules et configurations en toute simplicité.
- **Organisation du code** : tout est rangé par logique métier (contrôleurs, modules, vues, assets).

## Exemples de fonctionnalités

- Activation/désactivation de modules à chaud
- Définition de variables et tableaux dans les templates
- Boucles avancées avec variables spéciales (`loop.first`, `loop.last`, etc.)
- Conditions complexes (`in`, `not in`, `==`, `!=`)
- Filtres intégrés (`upper`, `lower`, `date`, `raw`)
- Commandes CLI pour la gestion quotidienne

## Pour qui ?

- Développeurs PHP souhaitant un framework simple, extensible et documenté
- Équipes qui veulent séparer clairement logique, présentation et configuration
- Projets nécessitant des modules réutilisables et une interface d’admin efficace

---

Pour une documentation complète et un guide pas-à-pas, consultez le fichier [HOW_TO_USE.md](HOW_TO_USE.md).

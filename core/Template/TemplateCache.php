<?php

/* ===== /Core/Template/TemplateCache.php ===== */

namespace Corelia\Template;

/**
 * Classe TemplateCache
 *
 * Gestion de la fraîcheur du cache des templates compilés.
 * Permet de vérifier si le fichier cache d'un template est à jour
 * par rapport à toutes ses dépendances (extends, includes).
 */
class TemplateCache
{
    /**
     * Vérifie si le cache compilé d'un template est frais.
     *
     * Un cache est considéré comme frais si :
     *   - Le fichier cache existe.
     *   - Aucune des dépendances du template (y compris lui-même, ses parents et ses includes)
     *     n'a été modifiée depuis la génération du cache.
     *
     * @param string $templatePath Chemin du template principal.
     * @param string $cacheFile    Chemin du fichier cache compilé.
     * @return bool                true si le cache est à jour, false sinon.
     */
    public function isCacheFresh(string $templatePath, string $cacheFile): bool
    {
        // Si le fichier cache n'existe pas, il n'est pas frais
        if (!file_exists($cacheFile)) return false;

        // Date de dernière modification du cache
        $cacheMTime = filemtime($cacheFile);

        // Récupère toutes les dépendances (extends, includes) du template
        $dependencies = TemplateUtils::getTemplateDependencies($templatePath);

        // Vérifie si l'une des dépendances a été modifiée après le cache
        foreach ($dependencies as $dep) {
            if (file_exists($dep) && filemtime($dep) > $cacheMTime) {
                return false;
            }
        }

        // Le cache est frais
        return true;
    }
}

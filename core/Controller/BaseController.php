<?php

namespace Corelia\Controller;

use Corelia\Template\CoreliaTemplate;
use Corelia\Http\JsonResponse;

/**
 * Contrôleur de base pour l'application Corelia.
 * Fournit des méthodes utilitaires pour le rendu de templates et les réponses JSON.
 */
abstract class BaseController
{
    /**
     * Rend un template avec les variables fournies.
     * 
     * @param string $template              Nom du template (ex: 'dashboard.ctpl' ou 'welcome.ctpl')
     * @param array $vars                   Variables à injecter dans le template
     * @return string                       HTML généré
     *
     * Convention :
     *   - 'dashboard.ctpl' => /src/Views/dashboard.ctpl
     *   - 'welcome.ctpl'   => /src/Views/welcome.ctpl
     */
    protected function render(string $template, array $vars = []): string
    {
        error_log("[DEBUG] DEBUT render() avec template : $template");
        try {
            $templatePath = $this->resolveTemplatePath($template);
            error_log("[DEBUG] Chemin template : $templatePath");
            $tpl = new CoreliaTemplate($templatePath);
            $result = $tpl->render($vars);
            error_log("[DEBUG] Fin de render(), rendu effectué");
            return $result;
        } catch (\Throwable $e) {
            error_log("[ERREUR] Exception dans render : " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Résout le chemin absolu du template selon la convention globale Corelia.
     * 
     * @param string $template              Nom du template (sans module)
     * @return string                       Chemin absolu du fichier template
     */
    protected function resolveTemplatePath(string $template): string
    {
        $ds = DIRECTORY_SEPARATOR;
        $projectRoot = dirname(__DIR__, 3);

        error_log("[DEBUG] resolveTemplatePath appelée avec : $template");

        // Uniquement la recherche dans /src/Views/
        $globalPath = "{$projectRoot}{$ds}src{$ds}Views{$ds}{$template}";
        error_log("[DEBUG] Chemin testé : $globalPath");
        if (file_exists($globalPath)) {
            error_log("[DEBUG] Trouvé : $globalPath");
            return $globalPath;
        }

        error_log("[DEBUG] Template introuvable : $template");
        throw new \InvalidArgumentException("Template introuvable : {$template} (chemin testé : {$globalPath})");
    }

    /**
     * Retourne une réponse JSON HTTP.
     * 
     * @param mixed $data                   Données à encoder en JSON
     * @param int $status                   Code HTTP de la réponse (par défaut 200)
     * @return JsonResponse
     */
    protected function json($data, int $status = 200): JsonResponse
    {
        return new JsonResponse($data, $status);
    }
}

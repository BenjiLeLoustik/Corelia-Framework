<?php

/* ===== /core/Controller/BaseController.php ===== */

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
     * @param string $template              Nom du template (ex: 'Admin::dashboard.ctpl' ou 'welcome.ctpl')
     * @param array $vars                   Variables à injecter dans le template
     * @return string                       HTML généré
     *
     * Convention :
     *   - 'Admin::dashboard.ctpl' => /modules/Admin/Views/dashboard.ctpl
     *   - 'welcome.ctpl'          => /src/Views/welcome.ctpl
     */
    protected function render(string $template, array $vars = []): string
    {
        $templatePath = $this->resolveTemplatePath($template);
        $tpl = new CoreliaTemplate($templatePath);
        return $tpl->render($vars);
    }

    /**
     * Résout le chemin absolu du template selon la convention Corelia.
     * 
     * @param string $template              Nom du template (avec ou sans module)
     * @return string                       Chemin absolu du fichier template
     */
    protected function resolveTemplatePath(string $template): string
    {
        if (strpos($template, '::') !== false) {
            // Template module : ex 'Admin::dashboard.ctpl'
            [$module, $tpl] = explode('::', $template, 2);
            return __DIR__ . "/../../modules/{$module}/Views/{$tpl}";
        }
        // Template app : ex 'welcome.ctpl'
        return __DIR__ . "/../../src/Views/{$template}";
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

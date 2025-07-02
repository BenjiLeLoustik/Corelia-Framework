<?php

/* ===== /core/Controller/BaseController.php ===== */

namespace Corelia\Controller;

use Corelia\Template\CoreliaTemplate;
use Corelia\Http\JsonResponse;

abstract class BaseController
{
    /**
     * Rend un template avec les variables fournies.
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
     * Résout le chemin absolu du template selon la convention.
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
     * Retourne une réponse JSON.
     */
    protected function json($data, int $status = 200): JsonResponse
    {
        return new JsonResponse($data, $status);
    }
}

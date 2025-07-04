<?php

namespace Corelia\Controller;

use Corelia\Template\CoreliaTemplate;
use Corelia\Http\Response;
use Corelia\Http\JsonResponse;
use Corelia\Http\RedirectResponse;

/**
 * Contrôleur de base pour l'application Corelia.
 * Fournit des méthodes utilitaires pour le rendu de templates,
 * les réponses JSON, les redirections, et les réponses brutes.
 */
abstract class BaseController
{
    /**
     * Rend un template avec les variables fournies et retourne un objet Response.
     *
     * @param string $template  Nom du template (ex: 'dashboard.ctpl' ou 'home/index.ctpl')
     * @param array  $vars      Variables à injecter dans le template
     * @return Response         Réponse HTTP contenant le HTML généré
     *
     * Convention :
     *   - 'dashboard.ctpl'    => /src/Views/dashboard.ctpl
     *   - 'home/index.ctpl'   => /src/Views/home/index.ctpl
     */
    protected function render(string $template, array $vars = []): Response
    {
        $templatePath = $this->resolveTemplatePath($template);
        $tpl = new CoreliaTemplate($templatePath);
        $html = $tpl->render($vars);

        return (new Response())->setContent($html);
    }

    /**
     * Résout le chemin absolu du template selon la convention globale Corelia.
     *
     * @param string $template  Nom du template (ex: 'home/index.ctpl')
     * @return string           Chemin absolu du fichier template
     */
    protected function resolveTemplatePath(string $template): string
    {
        $ds = DIRECTORY_SEPARATOR;
        // On part du dossier /core/Controller/ => on veut la racine du projet
        $projectRoot = dirname(__DIR__, 2); // remonte de 2 niveaux depuis /core/Controller/
        $globalPath = "{$projectRoot}{$ds}src{$ds}Views{$ds}{$template}";

        if (file_exists($globalPath)) {
            return $globalPath;
        }

        // Message d'erreur explicite si le template est introuvable
        throw new \InvalidArgumentException(
            "Template introuvable : {$template} (chemin testé : {$globalPath})"
        );
    }

    /**
     * Retourne une réponse JSON HTTP.
     *
     * @param mixed $data       Données à encoder en JSON
     * @param int   $status     Code HTTP de la réponse (par défaut 200)
     * @return JsonResponse
     */
    protected function json($data, int $status = 200): JsonResponse
    {
        return new JsonResponse($data, $status);
    }

    /**
     * Retourne une réponse HTTP de redirection.
     *
     * @param string $url       URL de redirection
     * @param int    $status    Code HTTP de redirection (par défaut 302)
     * @return RedirectResponse
     */
    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Retourne une réponse HTTP brute personnalisée.
     *
     * @param string $content   Contenu de la réponse
     * @param int    $status    Code HTTP de la réponse (par défaut 200)
     * @param array  $headers   Tableau associatif des headers HTTP
     * @return Response
     */
    protected function response(string $content, int $status = 200, array $headers = []): Response
    {
        $response = (new Response())->setStatusCode($status)->setContent($content);
        foreach ($headers as $name => $value) {
            $response->addHeader($name, $value);
        }
        return $response;
    }
}

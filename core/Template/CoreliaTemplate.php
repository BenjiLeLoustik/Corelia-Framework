<?php

/* ===== /Core/Template/CoreliaTemplate.php ===== */

namespace Corelia\Template;

/**
 * Classe CoreliaTemplate
 *
 * Façade principale du moteur de template Corelia.
 * Orchestration du rendu, gestion du cache, héritage et blocs.
 */
class CoreliaTemplate
{
    /**
     * Chemin absolu du template principal à utiliser.
     * Exemple : /src/Views/home/index.ctpl
     * @var string
     */
    protected string $templatePath;

    /**
     * Dossier de cache pour les templates compilés en PHP.
     * Exemple : /var/cache/templates/
     * @var string
     */
    protected string $cacheDir;

    /**
     * Blocs définis dans les templates (pour la gestion de l’héritage).
     * Tableau associatif : nom du bloc => contenu du bloc.
     * @var array<string, string>
     */
    protected array $blocks = [];

    /**
     * Chemin du template parent si le template courant utilise {% extends ... %}.
     * Null si pas d’héritage.
     * @var string|null
     */
    protected ?string $parentTemplate = null;

    /**
     * Constructeur du moteur de template.
     *
     * @param string $templatePath Chemin du template principal à utiliser.
     * @param string|null $cacheDir Dossier de cache compilé (par défaut : var/cache/templates).
     */
    public function __construct(string $templatePath, ?string $cacheDir = null)
    {
        $this->templatePath = $templatePath;
        $this->cacheDir = $cacheDir ?? dirname(__DIR__, 2) . '/var/cache/templates/';
    }

    /**
     * Rend le template avec les variables fournies, en utilisant le cache compilé.
     *
     * @param array $vars Variables à injecter dans le template.
     * @return string HTML généré.
     */
    public function render(array $vars = []): string
    {
        // Variables globales accessibles dans tous les templates
        $globals = [
            'now' => new \DateTime(),
            'app' => ['name' => 'CoreliaPHP'],
        ];
        $vars = array_merge($globals, $vars);

        // Détermine le nom du fichier cache compilé à partir du chemin du template
        $viewsDir    = realpath(dirname(__DIR__, 2) . '/src/Views/');
        $tplRealPath = realpath($this->templatePath);
        $relPath     = ltrim(str_replace(['/', '\\'], '_', str_replace($viewsDir, '', $tplRealPath)), '_');
        $cacheFile   = $this->cacheDir . $relPath . '.php';

        // Instancie le gestionnaire de cache
        $cache = new TemplateCache();

        // Vérifie si le cache est frais (à jour avec toutes les dépendances)
        if (!$cache->isCacheFresh($this->templatePath, $cacheFile)) {
            // Réinitialise les blocs et le parent
            $this->blocks = [];
            $this->parentTemplate = null;

            // Analyse le template pour collecter les blocs et l'héritage éventuel
            $parser = new TemplateParser();
            $this->blocks = $parser->collectBlocks(
                $this->templatePath,
                $vars,
                $this->blocks,
                $this->parentTemplate
            );

            // Compile le template (ou son parent en cas d'héritage)
            $compiler = new TemplateCompiler();
            if ($this->parentTemplate) {
                $compiled = $compiler->compileTemplate($this->parentTemplate, $vars, $this->blocks);
            } else {
                $compiled = $compiler->compileTemplate($this->templatePath, $vars, $this->blocks);
            }

            // Crée le dossier de cache si besoin
            if (!is_dir(dirname($cacheFile))) {
                mkdir(dirname($cacheFile), 0777, true);
            }

            // Log la compilation et écrit le cache
            error_log("[CoreliaTemplate] Compilation du template : $cacheFile");
            file_put_contents($cacheFile, $compiled);
        }

        // Injecte les variables dans la portée locale pour le template compilé
        extract($vars, EXTR_SKIP);

        // Capture le rendu du fichier compilé
        ob_start();
        include $cacheFile;
        return ob_get_clean();
    }
}

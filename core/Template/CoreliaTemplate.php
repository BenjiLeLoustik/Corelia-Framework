<?php

/* ===== /Core/Template/CoreliaTemplate.php ===== */

namespace Corelia\Template;

/**
 * Moteur de template minimal pour CoreliaPHP.
 * Utilisation : 
 *  - $tpl = new CoreliaTemplate('/chemin/vers/vue.ctpl');
 *  - echo $tpl->render( [ 'nom' => 'Alice' ] );
 */
class CoreliaTemplate
{

    protected string $templatePath;
    protected ?string $layoutPath = null;

    public function __construct( string $templatePath )
    {
        $this->templatePath = $templatePath;
    }

    /**
     * Définit un layout à utiliser pour ce rendu.
     */
    public function setLayout( string $layoutPath ): self
    {
        $this->layoutPath = $layoutPath;
        return $this;
    }

    /**
     * Rendu du template avec les variables fournies.
     * Remplace les {variables} par leur valeur.
     */
    public function render( array $vars = [] ): string
    {
        $content = $this->renderFile($this->templatePath, $vars);

        if ($this->layoutPath && file_exists($this->layoutPath)) {
            // On injecte $content dans le layout via la variable {content}
            $vars['content'] = $content;
            return $this->renderFile($this->layoutPath, $vars);
        }
        return $content;
    }

    /**
     * Rendu d'un fichier template avec variables.
     */
    public function renderFile( string $file, array $vars ): string
    {
        if (!file_exists($file)) {
            return "<!-- Template non trouvé : $file -->";
        }
        $output = file_get_contents($file);

        // Inclusion de partials : {include:partials/header.ctpl}
        $output = preg_replace_callback('/\{include:([^\}]+)\}/', function ($matches) use ($vars) {
            $partialPath = dirname($this->templatePath) . '/' . $matches[1];
            return $this->renderFile($partialPath, $vars);
        }, $output);

        // Remplacement des variables {var}
        foreach ($vars as $key => $value) {
            $output = str_replace('{' . $key . '}', $value, $output);
        }
        return $output;
    }

}
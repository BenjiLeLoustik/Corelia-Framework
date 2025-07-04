<?php

/* ===== /Core/Template/TemplateCompiler.php ===== */

namespace Corelia\Template;

/**
 * Classe TemplateCompiler
 *
 * Compile un template Corelia en code PHP exécutable.
 * Transforme la syntaxe du moteur de template (Twig-like) en instructions PHP réelles,
 * prêtes à être incluses et exécutées.
 */
class TemplateCompiler
{
    /**
     * Compile un template en code PHP prêt à être inclus.
     *
     * @param string $file   Chemin du template à compiler.
     * @param array  $vars   Variables à injecter dans le template.
     * @param array  $blocks Blocs hérités à utiliser pour le rendu.
     * @return string        Code PHP compilé (prêt à être écrit dans le cache).
     */
    public function compileTemplate(string $file, array $vars, array $blocks): string
    {
        // Utilise le parser pour obtenir le contenu du template après héritage/blocs
        $parser = new TemplateParser();
        $tpl = $parser->renderTemplate($file, $vars, $blocks);

        // Transforme les balises variables {{ ... }} en PHP avec échappement HTML
        $php = preg_replace_callback('/\{\{\s*(.*?)\s*\}\}/', function ($m) {
            return '<?= htmlspecialchars(' . TemplateUtils::twigToPhp($m[1]) . ', ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") ?>';
        }, $tpl);

        // Transforme les balises raw {{ ...|raw }} en PHP sans échappement
        $php = preg_replace_callback('/\{\{\s*(.*?)\|raw\s*\}\}/', function ($m) {
            return '<?= ' . TemplateUtils::twigToPhp($m[1]) . ' ?>';
        }, $php);

        // Transforme les instructions {% ... %} en instructions PHP (if, for, etc.)
        $php = preg_replace_callback('/\{% (.*?) %\}/s', function ($m) {
            return '<?php ' . TemplateUtils::twigToPhp($m[1], true) . ' ?>';
        }, $php);

        // Ajoute un commentaire d'avertissement en tête du fichier compilé
        return "<?php /* Compilé par CoreliaTemplate, ne pas éditer */ ?>\n" . $php;
    }
}

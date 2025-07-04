<?php

/* ===== /Core/Template/TemplateParser.php ===== */

namespace Corelia\Template;

/**
 * Classe TemplateParser
 *
 * Gère le parsing des blocs, l'héritage et la préparation du rendu intermédiaire
 * (avant compilation PHP) pour le moteur de template Corelia.
 */
class TemplateParser
{
    /**
     * Collecte tous les blocs d'un template (et de ses parents en cas d'héritage).
     *
     * @param string      $file           Chemin du template à analyser.
     * @param array       $vars           Variables disponibles lors de l’analyse.
     * @param array       $blocks         Blocs déjà collectés (utilisé pour la récursivité).
     * @param string|null &$parentTemplate Référence sur le chemin du template parent (modifié si héritage).
     * @return array                      Tableau associatif nom du bloc => contenu du bloc.
     */
    public function collectBlocks(string $file, array $vars, array $blocks, ?string &$parentTemplate = null): array
    {
        // Si le fichier n'existe pas, retourne les blocs déjà collectés
        if (!file_exists($file)) return $blocks;

        // Charge le contenu du template
        $tpl = file_get_contents($file);

        // Détection de l’héritage via {% extends ... %}
        if (preg_match('/\{% extends [\'"]([^\'"]+)[\'"] %\}/', $tpl, $m)) {
            // Résout le chemin du parent et le stocke par référence
            $parentTemplate = TemplateUtils::resolvePath($m[1], $file);
            $tpl = str_replace($m[0], '', $tpl); // Retire la déclaration d'héritage du template courant
        }

        // Extraction des blocs définis dans ce template
        preg_replace_callback('/\{% block (\w+) %\}(.*?)\{% endblock %\}/s', function ($m) use (&$blocks) {
            $blockName = $m[1];
            $blockContent = $m[2];
            // N'écrase pas un bloc déjà défini (priorité à l'enfant)
            if (!isset($blocks[$blockName])) {
                $blocks[$blockName] = $blockContent;
            }
            return '';
        }, $tpl);

        // Si héritage, collecte récursivement les blocs du parent
        if ($parentTemplate) {
            $blocks = array_merge(
                $this->collectBlocks($parentTemplate, $vars, $blocks, $parentTemplate),
                $blocks // Les blocs de l'enfant écrasent ceux du parent
            );
        }

        return $blocks;
    }

    /**
     * Prépare le rendu intermédiaire d'un template (avant compilation PHP).
     * Remplace les blocs hérités, supprime l'héritage, et parse toutes les instructions.
     *
     * @param string $file   Chemin du template à traiter.
     * @param array  $vars   Variables à injecter.
     * @param array  $blocks Blocs hérités à utiliser.
     * @return string        Chaîne de template prête à être compilée en PHP.
     */
    public function renderTemplate(string $file, array $vars, array $blocks): string
    {
        // Gestion des templates manquants
        if (!file_exists($file)) {
            if (getenv('APP_ENV') === 'dev') {
                throw new \RuntimeException("Template non trouvé : $file");
            }
            return "<!-- Template non trouvé pour : $file -->";
        }

        // Charge le contenu du template
        $tpl = file_get_contents($file);

        // Supprime la déclaration d’héritage (déjà gérée lors de la collecte des blocs)
        $tpl = preg_replace('/\{% extends [\'"][^\'"]+[\'"] %\}/', '', $tpl);

        // Remplace les blocs par leur contenu hérité ou par défaut
        $tpl = preg_replace_callback('/\{% block (\w+) %\}(.*?)\{% endblock %\}/s', function ($m) use ($blocks, $vars, $file) {
            $blockName = $m[1];
            $blockContent = $blocks[$blockName] ?? $m[2];
            // Parse récursivement le contenu du bloc (pour inclure instructions, includes, etc.)
            return TemplateUtils::parseString(TemplateUtils::parseAll($blockContent, $vars, $file), $vars);
        }, $tpl);

        // Parse toutes les autres instructions du template (set, include, for, if, ...)
        return TemplateUtils::parseAll($tpl, $vars, $file);
    }
}

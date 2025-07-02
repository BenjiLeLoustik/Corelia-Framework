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

    public function __construct( string $templatePath )
    {
        $this->templatePath = $templatePath;
    }

    /**
     * Rendu du template avec les variables fournies.
     * Remplace les {variables} par leur valeur.
     */
    public function render( array $vars = [] ): string
    {
        if( !file_exists( $this->templatePath ) ){
            return "<!-- Template non trouvé : {$this->templatePath} -->";
        }

        $output = file_get_contents( $this->templatePath );

        // Remplacement simple des variables {var}
        foreach( $vars as $key => $value ){
            $output = str_replace( '{' . $key . '}', htmlspecialchars( (string)$value ), $value );
        }

        return $output;
    }

}
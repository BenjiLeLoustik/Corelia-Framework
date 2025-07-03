<?php

/* ===== /Core/CLI/ModuleManager.php ===== */

namespace Corelia\CLI;

/**
 * Gestionnaire CLI pour les modules Corelia.
 * Permet de lister, activer et désactiver les modules via la ligne de commande.
 */
class ModuleManager
{

    /**
     * Chemin vers le dossier des modules.
     * @var string
     */
    protected string $modulesPath;

    /**
     * Constructeur.
     * @param string $modulesPath       Chemin du dossier contenant les modules
     */
    public function __construct( string $modulesPath )
    {
        $this->modulesPath = $modulesPath;
    }

    /**
     * Affiche la liste des modules avec leur statut (activé/désactivé).
     */
    public function list()
    {
        foreach (scandir($this->modulesPath) as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            $configFile = "{$this->modulesPath}/$dir/config.json";
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                $status = !empty($config['enabled']) ? 'Activé' : 'Désactivé';
                echo "$dir : $status\n";
            }
        }
    }

    /**
     * Active un module donné.
     * @param string|null $name         Nom du module à activer
     */
    public function enable( ?string $name )
    {
        if( !$name ){
            echo "Nom du module require.\n";
            return;
        }
        $this->setStatus( $name, true );
    }

    /**
     * Désactive un module donné.
     * @param string|null $name         Nom du module à désactiver
     */
    public function disable(?string $name)
    {
        if (!$name) {
            echo "Nom du module requis.\n";
            return;
        }
        $this->setStatus($name, false);
    }

    /**
     * Modifie le statut (activé/désactivé) d'un module dans son fichier de configuration.
     * @param string $name              Nom du module
     * @param bool $status              true pour activer, false pour désactiver
     */
    protected function setStatus(string $name, bool $status)
    {
        $configFile = "{$this->modulesPath}/$name/config.json";
        if (!file_exists($configFile)) {
            echo "Module $name introuvable.\n";
            return;
        }
        $config = json_decode(file_get_contents($configFile), true);
        $config['enabled'] = $status;
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "Module $name " . ($status ? "activé" : "désactivé") . ".\n";
    }


}
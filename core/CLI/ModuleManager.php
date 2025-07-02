<?php

/* ===== /Core/CLI/ModuleManager.php ===== */

namespace Corelia\CLI;

class ModuleManager
{

    protected string $modulesPath;

    public function __construct( string $modulesPath )
    {
        $this->modulesPath = $modulesPath;
    }

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

    public function enable( ?string $name )
    {
        if( !$name ){
            echo "Nom du module require.\n";
            return;
        }
        $this->setStatus( $name, true );
    }

    public function disable(?string $name)
    {
        if (!$name) {
            echo "Nom du module requis.\n";
            return;
        }
        $this->setStatus($name, false);
    }

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
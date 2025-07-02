<?php

/* ===== /core/CLI/Generator.php ===== */

namespace Corelia\CLI;

class Generator
{
    protected string $basePath;

    public function __construct(string $basePath) { $this->basePath = $basePath; }

    public function makeModule(?string $name)
    {
        if( !$name ){
            echo "Nom du module requis.\n";
            return;
        }

        $modulePath = "{$this->basePath}/modules/$name";
        if( is_dir( $modulePath ) ){
            echo "Le module $name existe déjà.\n";
            return;
        }

        mkdir( "$modulePath/Views", 0777, true );
        mkdir( "$modulePath/Commands", 0777, true );

        // config.json
        $config = [
            "name" => $name,
            "description" => "Module $name généré automatiquement",
            "enabled" => true,
            "autoload" => [
                "psr-4" => [
                    "Modules\\$name\\" => "modules/$name/"
                ]
            ],
            "routes" => []
        ];

        file_put_contents( "$modulePath/config.json", json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

        // Contrôleur de base
        $ctrl = "<?php\nnamespace Modules\\$name;\n\nclass {$name}Controller\n{\n    public function index()\n    {\n        echo 'Bienvenue dans le module $name';\n    }\n}\n";
        file_put_contents( "$modulePath/{$name}Controller.php", $ctrl );

        // Vue de base
        file_put_contents( "$modulePath/Views/index.ctpl", "<h1>Bienvenue dans le module $name</h1>\n" );

        echo "Module $name généré avec succès.\n";
    }

    public function makeController(?string $moduleOrName, ?string $controllerName = null)
    {
        // Si $controllerName est null, on crée dans l'app principale
        if (!$controllerName) {

            $name = $moduleOrName;

            if( !$name ){ 
                echo "Nom du contrôleur requis.\n"; 
                return; 
            }

            $ctrlPath = "{$this->basePath}/src/Controller/{$name}.php";
            
            if( file_exists( $ctrlPath ) ){ 
                echo "Le contrôleur $name existe déjà.\n"; 
                return; 
            }
            
            $code = "<?php\nnamespace App\Controller;\n\nclass $name\n{\n    public function index()\n    {\n        echo 'Bienvenue dans $name';\n    }\n}\n";
            file_put_contents($ctrlPath, $code);
            
            echo "Contrôleur $name généré dans src/Controller.\n";

        }else{

            // Cas module + contrôleur
            $module     = $moduleOrName;
            $name       = $controllerName;
            $ctrlPath   = "{$this->basePath}/modules/$module/{$name}.php";

            if( !is_dir( !"{$this->basePath}/modules/$module" ) ){ 
                echo "Module $module introuvable.\n"; 
                return; 
            }
           
            if( file_exists( $ctrlPath ) ){ 
                echo "Le contrôleur $name existe déjà dans $module.\n"; 
                return; 
            }
            
            $code = "<?php\nnamespace Modules\\$module;\n\nclass $name\n{\n    public function index()\n    {\n        echo 'Bienvenue dans $name du module $module';\n    }\n}\n";
            file_put_contents($ctrlPath, $code);
            
            echo "Contrôleur $name généré dans le module $module.\n";
        
        }
    }
}
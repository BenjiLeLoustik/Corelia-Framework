<?php

namespace Workspace\Test\Controllers;

use Corelia\Controller\BaseController;
use Corelia\Routing\RouteAttribute;
use Corelia\Http\Response;

class WelcomeController extends BaseController
{
    #[RouteAttribute(path: '/', name: 'workspace.welcome', methods: ['GET'])]
    public function index(): Response
    {
        // Lecture de la config du workspace
        $configPath = dirname(__DIR__, 3) . '/config.json';
        $config = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : [];
        $name = $config['name'] ?? 'Test';
        $port = $config['port'] ?? '8001';

        return $this->render('Welcome/index.ctpl', [
            'name' => $name,
            'port' => $port
        ]);
    }
}
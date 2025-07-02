<?php

/* ===== /modules/Audit/AuditController.php ===== */

namespace Modules\Audit;

use Corelia\Event\EventDispatcher;


class AuditController
{
    protected EventDispatcher $dispatcher;

    // Injection du dispatcher d'événements
    public function setEventDispatcher(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->registerListeners();
    }

    // Enregistre les listeners pour les événements
    protected function registerListeners(): void
    {
        $this->dispatcher->addListener('user.registered', function($user) {
            $logEntry = date('Y-m-d H:i:s') . " - Nouvel utilisateur : {$user['name']} ({$user['email']})\n";
            file_put_contents(__DIR__ . '/audit.log', $logEntry, FILE_APPEND);
        });
    }

    public function index()
    {
        echo "Module Audit actif. Consultez audit.log pour les événements.";
    }
}

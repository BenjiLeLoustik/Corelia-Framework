<?php

/* ===== /modules/User/UserController.php ===== */

namespace Modules\User;

use Corelia\Event\EventDispatcher;

class UserController
{
    protected EventDispatcher $dispatcher;

    // Injection du dispatcher d'événements
    public function setEventDispatcher(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    // Simule l'enregistrement d'un utilisateur
    public function register()
    {
        // Logique d'enregistrement (simplifiée)
        $user = ['name' => 'Jean Dupont', 'email' => 'jean@example.com'];

        // Déclenchement de l'événement user.registered
        $this->dispatcher->dispatch('user.registered', $user);

        echo "Utilisateur enregistré : {$user['name']}";
    }
}

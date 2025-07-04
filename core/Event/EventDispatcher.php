<?php 

namespace Corelia\Event;

/**
 * Gestionnaire d'événements simple pour Corelia.
 * Permet d'enregistrer des listeners et de dispatcher des événements.
 */
class EventDispatcher
{
    /**
     * Tableau des listeners enregistrés, indexé par nom d'événement.
     * @var array<string, callable[]>
     */
    protected array $listeners = [];

    /**
     * Ajoute un listener (callback) pour un événement donné.
     *
     * @param string   $eventName   Nom de l'événement à écouter
     * @param callable $listener    Fonction à appeler lors du dispatch de l'événement
     */
    public function addListener(string $eventName, callable $listener): void
    {
        $this->listeners[$eventName][] = $listener;
    }

    /**
     * Déclenche un événement et appelle tous les listeners associés.
     *
     * @param string $eventName     Nom de l'événement à dispatcher
     * @param mixed  $eventData     Données à transmettre aux listeners (optionnel)
     */
    public function dispatch(string $eventName, $eventData = null): void
    {
        if (!empty($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $listener) {
                call_user_func($listener, $eventData);
            }
        }
    }
}

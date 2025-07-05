<?php 

/* ===== /Core/Event/EventDispatcher.php ===== */

namespace Corelia\Event;

/**
 * Gestionnaire d'événements simple pour Corelia.
 *
 * Cette classe permet d'enregistrer des listeners (fonctions ou méthodes)
 * pour des événements nommés et de les déclencher à la demande.
 * 
 * Usage typique :
 *   $dispatcher = new EventDispatcher();
 *   $dispatcher->addListener('user.registered', function($user) { ... });
 *   $dispatcher->dispatch('user.registered', $user);
 */
class EventDispatcher
{
    /**
     * Tableau des listeners enregistrés, indexé par nom d'événement.
     * Chaque événement est associé à un tableau de callbacks.
     *
     * @var array<string, callable[]>
     */
    protected array $listeners = [];

    /**
     * Ajoute un listener (callback) pour un événement donné.
     *
     * Lorsque l'événement sera dispatché, ce listener sera appelé.
     *
     * @param string   $eventName   Nom de l'événement à écouter (ex : 'user.registered')
     * @param callable $listener    Fonction à appeler lors du dispatch de l'événement.
     *                             Signature attendue : function($eventData)
     * @return void
     */
    public function addListener(string $eventName, callable $listener): void
    {
        $this->listeners[$eventName][] = $listener;
    }

    /**
     * Déclenche un événement et appelle tous les listeners associés.
     *
     * Tous les callbacks enregistrés pour ce nom d'événement sont appelés dans l'ordre
     * d'enregistrement, et reçoivent en paramètre les données de l'événement.
     *
     * @param string $eventName     Nom de l'événement à dispatcher (ex : 'user.registered')
     * @param mixed  $eventData     Données à transmettre aux listeners (optionnel, ex : objet User)
     * @return void
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

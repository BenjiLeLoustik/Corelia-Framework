<?php 

/* ===== /core/Event/EventDispatcher.php ===== */

namespace Corelia\Event;

class EventDispatcher
{
    protected array $listeners = [];

    public function addListener(string $eventName, callable $listener): void
    {
        $this->listeners[$eventName][] = $listener;
    }

    public function dispatch(string $eventName, $eventData = null): void
    {
        if (!empty($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $listener) {
                call_user_func($listener, $eventData);
            }
        }
    }
}
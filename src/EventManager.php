<?php

namespace Eventloop;

/**
 * Handles event actions and triggers.
 */
class EventManager
{
    private $events = [];

    /**
     * Add an event to the event loop.
     *
     * @param string $event
     * @param callable $callback
     * @param int $priority
     */
    public function addEvent(string $event, callable $callback, int $priority = 10): void
    {
        $this->events[$event][$priority][] = $callback;
        ksort($this->events[$event]);
    }

    /**
     * Execute all callbacks associated with an event.
     *
     * @param string $event
     * @param mixed $data
     */
    public function executeEvent(string $event, $data = null): void
    {
        if (isset($this->events[$event])) {
            foreach ($this->events[$event] as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    call_user_func($callback, $data);
                }
            }
        }
    }
}
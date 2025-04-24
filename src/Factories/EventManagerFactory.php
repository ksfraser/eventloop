<?php

namespace Eventloop\Factories;

use Eventloop\EventManager;

/**
 * Factory for creating EventManager instances.
 */
class EventManagerFactory
{
    public static function create(): EventManager
    {
        return new EventManager();
    }
}
<?php

namespace Eventloop\Factories;

use Eventloop\ObserverManager;

/**
 * Factory for creating ObserverManager instances.
 */
class ObserverManagerFactory
{
    public static function create(): ObserverManager
    {
        return new ObserverManager();
    }
}
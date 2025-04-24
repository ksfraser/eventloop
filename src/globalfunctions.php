<?php
namespace Eventloop;

$global_eventloop = new EventManager();

/**
 * WordPress-style function to add an action.
 *
 * @param string $event The event to associate the action with.
 * @param callable $callback The callback function to execute.
 * @param int $priority The priority of the action (lower numbers execute first).
 */
function add_action($event, callable $callback, $priority = 10)
{
    global $global_eventloop;
    $global_eventloop->addEvent($event, $callback, $priority);
}

/**
 * WordPress-style function to execute an action.
 *
 * @param string $event The event to execute actions for.
 * @param mixed $data Optional data to pass to the actions.
 */
function do_action($event, $data = null)
{
    global $global_eventloop;
    $global_eventloop->executeEvent($event, $data);
}

/**
 * SuiteCRM-style function to register a trigger.
 *
 * @param string $event The event to associate the trigger with.
 * @param callable $trigger A callable function to execute as the trigger.
 */
function register_trigger($event, callable $trigger)
{
    global $global_eventloop;
    $global_eventloop->addEvent($event, $trigger);
}

/**
 * SuiteCRM-style function to process a workflow.
 *
 * @param string $event The event to handle.
 * @param mixed $data Optional data to pass to triggers, conditions, and actions.
 */
function process_workflow($event, $data = null)
{
    global $global_eventloop;
    $global_eventloop->executeEvent($event, $data);
}
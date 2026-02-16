# Functional Requirements

## FR-001: SPL Observer Registration

**Description**: The system shall allow registration of SPL observers for specific events.

**Inputs**:
- `$observer`: Object implementing observer interface with `notified()` method
- `$event`: String event name (supports wildcards `*`, `**`)
- `$value`: Optional callback function name (legacy parameter, not used)

**Outputs**:
- Observer registered in both SPL array and PSR-14 EventManager
- No return value (void)

**Rules/Constraints**:
- Event name must be non-empty string
- Observer must have `notified($trigger, $event, $message)` method
- Wildcards `*` and `**` are supported for event matching

**Error Handling**:
- No explicit error handling (silent failure if observer lacks `notified()` method)

**Acceptance Tests**:
```php
$observer = new MyObserver();
$eventloop->ObserverRegister($observer, 'user.created');
// Observer receives both SPL and PSR-14 events
```

---

## FR-002: Event Notification (SPL Style)

**Description**: The system shall notify all registered observers when an event occurs.

**Inputs**:
- `$trigger_class`: Object that triggered the event
- `$event`: String event name
- `$msg`: Mixed event payload/message

**Outputs**:
- All registered observers for the event are notified
- Both SPL observers and PSR-14 listeners receive notification
- Returns boolean (legacy, always returns true)

**Rules/Constraints**:
- Event must match registered observer's event name (exact or wildcard)
- Observers called in registration order (SPL) or priority order (PSR-14)
- Event propagation can be stopped via `LegacyEvent->stopPropagation()`

**Error Handling**:
- Logs warning if no observers registered for non-logging events
- Continues execution if observer throws exception (no propagation)

**Acceptance Tests**:
```php
$eventloop->ObserverNotify($this, 'user.created', $userData);
// All observers for 'user.created', '*', and '**' are notified
```

---

## FR-003: Bidirectional SPL ↔ PSR-14 Integration

**Description**: The system shall provide seamless integration between SPL Observer pattern and PSR-14 Event Dispatcher.

**Inputs**:
- SPL observer registration via `ObserverRegister()`
- Event notification via `ObserverNotify()`

**Outputs**:
- SPL observers automatically registered as PSR-14 listeners
- Events dispatched to both SPL and PSR-14 systems
- Single point of registration ensures consistency

**Rules/Constraints**:
- SPL observers registered via `eventloop` receive PSR-14 events
- PSR-14 listeners registered via `eventloop` receive SPL notifications
- Direct `EventManager` registration bypasses SPL (documented limitation)

**Error Handling**:
- No special error handling (relies on underlying systems)

**Acceptance Tests**:
```php
// SPL observer
$eventloop->ObserverRegister($splObserver, 'test.event');

// Fire event
$eventloop->ObserverNotify($this, 'test.event', 'data');

// Verify: SPL observer receives event via PSR-14 dispatch
```

---

## FR-004: Wildcard Event Matching

**Description**: The system shall support wildcard event listeners using `*` and `**` patterns.

**Inputs**:
- Event name with wildcards: `*` (single-level) or `**` (multi-level)

**Outputs**:
- Observers registered for `*` receive all events
- Observers registered for `**` receive all events
- Specific event listeners take precedence over wildcards

**Rules/Constraints**:
- `*` matches any single event
- `**` matches all events (same as `*` in current implementation)
- Priority determines execution order when multiple matches exist

**Error Handling**:
- No special error handling

**Acceptance Tests**:
```php
$eventloop->ObserverRegister($wildcardObserver, '*');
$eventloop->ObserverNotify($this, 'any.event', 'data');
// Wildcard observer receives notification
```

---

## FR-005: Logging via Composition

**Description**: The system shall provide logging functionality through a composed `kfLog` instance rather than inheritance.

**Inputs**:
- `$message`: Log message (string or mixed)
- `$level`: PEAR log level constant (PEAR_LOG_EMERG, PEAR_LOG_DEBUG, etc.)

**Outputs**:
- Message logged via composed `kfLog` instance
- No return value (void)

**Rules/Constraints**:
- All logging methods (`Log`, `stampLog`, `log_0` through `log_7`) available
- Logger initialized in constructor with default file and debug level
- Logging behavior identical to inheritance-based approach

**Error Handling**:
- Relies on `kfLog` error handling

**Acceptance Tests**:
```php
$eventloop->Log('Test message', PEAR_LOG_DEBUG);
$eventloop->stampLog('Timestamped message', PEAR_LOG_INFO);
// Messages logged successfully via composed logger
```

---

## FR-006: Module Loading

**Description**: The system shall load and initialize modules from a specified directory.

**Inputs**:
- `$moduledir`: Path to directory containing module configuration files

**Outputs**:
- Modules loaded and initialized
- Module observers registered with eventloop
- Returns boolean (true on success)

**Rules/Constraints**:
- Modules loaded only once (prevents duplicate loading)
- Module directory must exist and be readable
- Module config files follow naming convention `config.*.php`

**Error Handling**:
- Returns true if modules already loaded (idempotent)
- Logs error if module directory doesn't exist
- Continues loading other modules if one fails

**Acceptance Tests**:
```php
$eventloop = new eventloop('/path/to/modules');
// Modules loaded and registered
$eventloop->load_modules();
// Returns true, no duplicate loading
```

---

## FR-007: Event Propagation Control

**Description**: The system shall support stopping event propagation via PSR-14 `StoppableEventInterface`.

**Inputs**:
- `LegacyEvent` object with `stopPropagation()` method

**Outputs**:
- Subsequent listeners not called after propagation stopped
- Event dispatch returns early

**Rules/Constraints**:
- Only works for PSR-14 listeners (SPL observers always called)
- Listener must call `$event->stopPropagation()`

**Error Handling**:
- No special error handling

**Acceptance Tests**:
```php
$eventloop->eventManager->addListener('test', function($event) {
    $event->stopPropagation();
});
$eventloop->eventManager->addListener('test', function($event) {
    // This listener not called
});
```

---

## FR-008: Backward Compatibility

**Description**: The system shall maintain full backward compatibility with existing SPL-based code.

**Inputs**:
- Existing code using `attach()`, `detach()`, `notify()` (SPL interface)
- Existing code using `ObserverRegister()`, `ObserverNotify()` (legacy interface)

**Outputs**:
- All existing code continues to work without modification
- All inherited `origin` methods remain accessible
- All logging methods work via composition

**Rules/Constraints**:
- No breaking changes to public API
- Method signatures match base class (no type hints for PHP 7.3 compatibility)
- All tests pass

**Error Handling**:
- Same error handling as previous implementation

**Acceptance Tests**:
```php
// All existing tests pass
vendor/bin/phpunit
// OK (14 tests, 24 assertions)
```

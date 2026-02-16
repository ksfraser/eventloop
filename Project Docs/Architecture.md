# Architecture

## Overview

### Purpose of the Package
The `eventloop` package provides an event-driven architecture for PHP applications, supporting both legacy SPL Observer pattern and modern PSR-14 Event Dispatcher interfaces. It serves as a bridge between legacy code and modern event-driven design patterns.

### Key Modules/Components

#### 1. **eventloop Class**
- **Extends**: `origin` (base class for data handling)
- **Implements**: `\SplSubject` (SPL Observer pattern)
- **Purpose**: Central event dispatcher and module loader

**Key Responsibilities:**
- Observer registration and notification (SPL pattern)
- Event dispatching (PSR-14 integration)
- Module loading and lifecycle management
- Logging via composed `kfLog` instance

#### 2. **LegacyEvent Adapter**
- **Implements**: `EventInterface` (PSR-14)
- **Purpose**: Bridge legacy 3-argument notification system to PSR-14

**Structure:**
```php
class LegacyEvent implements EventInterface
{
    private $trigger;    // Triggering object
    private $name;       // Event name
    private $message;    // Event payload
}
```

#### 3. **Integration Components**
- **EventManager** (from `ksfraser/event`): PSR-14 event dispatcher
- **kfLog** (composed): Logging functionality
- **origin** (inherited): Base data handling and access control

### Primary Data Flows

#### Observer Registration Flow
```
Legacy Code
    ↓
eventloop->ObserverRegister($observer, $event)
    ↓
    ├─→ Store in $this->observers[$event][]     (SPL array)
    └─→ EventManager->addListener($event, ...)  (PSR-14)
```

#### Event Notification Flow
```
eventloop->ObserverNotify($trigger, $event, $msg)
    ↓
    ├─→ Create LegacyEvent($trigger, $event, $msg)
    ├─→ EventManager->dispatch($legacyEvent)    (PSR-14 listeners)
    └─→ Iterate $this->observers[$event]        (SPL observers)
```

## Dependencies

### Direct Dependencies
- **ksfraser/kflog** (`dev-main`): Logging functionality (composed, not inherited)
- **ksfraser/exceptions** (`*`): Custom exception classes
- **ksfraser/event** (`*`): PSR-14 event dispatcher implementation
- **ksfraser/origin** (transitive): Base class for data handling

### Optional/Dev Dependencies
- **phpunit/phpunit** (`^9.5`): Testing framework

### Version Constraints / PHP Compatibility
- **PHP**: 7.3+ (no type hints on method signatures for compatibility)
- **PSR-14**: Compatible via `ksfraser/event`
- **SPL**: Built-in PHP support

## Key Decisions

### Decision 1: Composition Over Inheritance for Logging
**Date**: 2026-02-16  
**Status**: Implemented

**Context**: Originally `eventloop extends kfLog extends origin`, creating a deep inheritance chain.

**Decision**: Changed to `eventloop extends origin` with `kfLog` as a composed dependency.

**Rationale**:
- Separation of concerns (logging is a dependency, not core functionality)
- Flexibility to swap logger implementations
- Clearer dependency graph
- Follows SOLID principles (Dependency Inversion)

**Implementation**:
```php
class eventloop extends origin implements \SplSubject
{
    private $logger; // kfLog instance
    
    public function __construct() {
        $this->logger = new kfLog(__FILE__, PEAR_LOG_DEBUG);
    }
    
    // Proxy methods
    public function Log($msg, $level) {
        return $this->logger->Log($msg, $level);
    }
}
```

### Decision 2: Bidirectional SPL ↔ PSR-14 Integration
**Date**: 2026-02-16  
**Status**: Implemented

**Context**: Need to support both legacy SPL observers and modern PSR-14 listeners.

**Decision**: Implement wrapper pattern where SPL registration automatically creates PSR-14 listeners.

**Rationale**:
- Backward compatibility with existing code
- Gradual migration path to PSR-14
- Leverage PSR-14 features (priority, wildcards, propagation control)
- Single point of registration (`eventloop`)

**Trade-offs**:
- ✅ SPL observers registered via `eventloop` receive both SPL and PSR-14 events
- ✅ Events fired via `eventloop` trigger both systems
- ❌ Direct `EventManager` registration bypasses SPL (documented limitation)

### Decision 3: Remove Type Hints for PHP 7.3 Compatibility
**Date**: 2026-02-16  
**Status**: Implemented

**Context**: PHP 8.4 strict type checking revealed method signature incompatibilities.

**Decision**: Remove all type hints from method signatures, preserve in docblocks.

**Rationale**:
- Support PHP 7.3+ (client requirement)
- Avoid LSP violations with `origin` base class
- Maintain type information via docblocks for IDE support

**Example**:
```php
/**
 * @param object $observer
 * @param string $event
 * @param mixed $value
 */
public function ObserverRegister($observer, $event, $value = null)
```

### Decision 4: LegacyEvent Adapter Pattern
**Date**: 2026-02-16  
**Status**: Implemented

**Context**: Legacy code uses 3-argument notification: `notified($trigger, $event, $msg)`

**Decision**: Create `LegacyEvent` class implementing `EventInterface` to wrap legacy arguments.

**Rationale**:
- Minimal changes to existing code
- PSR-14 compliance
- Enables propagation control
- Type-safe event handling

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────┐
│                    eventloop                             │
│  ┌────────────┐  ┌──────────────┐  ┌────────────────┐  │
│  │   origin   │  │    kfLog     │  │ EventManager   │  │
│  │ (inherited)│  │  (composed)  │  │   (PSR-14)     │  │
│  └────────────┘  └──────────────┘  └────────────────┘  │
│                                                          │
│  Implements: \SplSubject (SPL Observer Pattern)         │
└─────────────────────────────────────────────────────────┘
                          │
        ┌─────────────────┴─────────────────┐
        │                                   │
   ┌────▼─────┐                      ┌─────▼──────┐
   │   SPL    │                      │   PSR-14   │
   │ Observers│◄─────────────────────┤  Listeners │
   └──────────┘   LegacyEvent        └────────────┘
                   Adapter
```

## Testing Strategy

### Unit Tests
- **EventloopInheritanceTest**: Verifies composition pattern and inherited methods
- **EventloopTest**: Tests core event functionality

### Integration Tests
- Verify SPL observers receive PSR-14 events
- Verify PSR-14 listeners receive SPL notifications
- Test wildcard event handling (`*`, `**`)
- Test priority ordering

### Backward Compatibility Tests
- Ensure existing SPL code continues to work
- Verify all inherited `origin` methods accessible
- Confirm logging methods work via composition

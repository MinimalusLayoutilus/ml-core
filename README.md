# mnhcc/ml-core

Core package of the Minimalus Layoutilus PHP framework.

Provides the custom SPL autoloader (`BootstrapHandler`), base object model (`MNHcC`, `Bootstrap`),
scalar box types (`AutoBox\Scalar`), array/string helpers, the constant/config bootstrap, and the
event bus (`EventManager` + `Event` + `EventParms`).

## Requirements

- PHP ≥ 5.4

## Installation

```bash
composer require mnhcc/ml-core
```

## Bootstrap

```php
namespace mnhcc\ml { const INDEX = true; }

require_once __DIR__ . '/vendor/autoload.php';

mnhcc\ml\classes\BootstrapHandler::initial(__DIR__);
```

`BootstrapHandler::initial()` registers the SPL autoloader, defines global constants (`NSS`, `n`, `br`, `php`, `DS`) and path constants (`MNHCC_PATH`, `ROOT_PATH`).

## Autoloader

The autoloader maps namespace segments to files using non-standard extensions:

| Extension | Type |
|---|---|
| `.class.php` | class |
| `.interface.php` | interface |
| `.trait.php` | trait |

## Event bus

A small static event dispatcher.  Listeners register against a normalised
event name (leading `on` stripped, `ucfirst`); `raise()` dispatches a name +
`EventParms` payload to every registered listener in order.

```php
use mnhcc\ml\classes\EventManager;
use mnhcc\ml\classes\Event;
use mnhcc\ml\classes\EventParms;

EventManager::register(new Event(function (EventParms $p) {
    // do something with $p->getParms() / $p->get('key')
}, 'myEvent'));

EventManager::raise('myEvent', new EventParms(['key' => 'value']));
```

Today's bus is **fire-and-forget** — listeners observe but cannot vote on the
result.  See [`docs/EVENT_SYSTEM.md`](docs/EVENT_SYSTEM.md) for the current
behaviour, the gaps, and the planned interventional/filter extensions.

## License

[LGPL-2.1-only](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html)

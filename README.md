# mnhcc/ml-core

Core package of the Minimalus Layoutilus PHP framework.

Provides the custom SPL autoloader (`BootstrapHandler`), base object model (`MNHcC`, `Bootstrap`),
scalar box types (`AutoBox\Scalar`), array/string helpers, and the constant/config bootstrap.

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

## License

[LGPL-2.1-only](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html)

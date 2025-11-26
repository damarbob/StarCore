# StarCore

**The HMVC and Hook Orchestration Kernel for CodeIgniter 4**

StarCore is a lightweight, powerful library designed to bring Hierarchical Model-View-Controller (HMVC) architecture and a robust Hook Orchestration system to CodeIgniter 4 applications. It enables modular application development and provides a flexible event-driven architecture.

## Features

- **HMVC Architecture**: Organize your application into reusable modules with their own routes, controllers, models, and views.
- **Hook Orchestration**: A powerful event system supporting Actions, Filters, and Triggers with priority management.
- **Module Autoloading**: Automatically discovers and loads modules from `modules/` directory.
- **Zero Configuration**: Works out of the box with sensible defaults, but fully configurable.

## Installation

Install via Composer:

```bash
composer require damarbob/starcore
```

## Configuration

### Active Modules

By default, StarCore looks for modules in the `modules/` directory. You can configure active modules in `Config/Star.php` (publish this file to your application's `app/Config` directory if needed, or modify it in place for the library).

```php
// app/Config/Star.php

public string $activeModules = 'Blog,Auth,Shop';
```

Alternatively, you can set this in your `.env` file:

```dotenv
Star.activeModules = 'Blog,Auth,Shop'
```

### Development Modules

Development modules are located in the `modules/.star-dev/` directory. These modules are either experimental or intended for development tools and features, and should not be enabled in production.

You can configure active development modules in `Config/Star.php`:

```php
public string $activeDevModules = 'DebugToolbar,Generator';
```

Alternatively, you can set this in your `.env` file:

```dotenv
Star.activeDevModules = 'DebugToolbar,Generator'
```

> **Note:** Development modules are automatically disabled when `safeMode` is enabled.

### Safe Mode

Safe mode disables all modules, useful for debugging or maintenance.

```php
public bool $safeMode = true;
```

## Usage

### Hooks

StarCore provides a global helper function `hook()` and a service `HyperHooks` to manage events.

#### Registering a Hook

You can register hooks in your module's `init.php` or any other loaded file.

```php
use StarCore\Service\HyperHooks;

// Register an action (side-effect)
HyperHooks::getInstance()->register('user.login', function($user) {
    log_message('info', 'User logged in: ' . $user->id);
});

// Register a filter (modify value)
HyperHooks::getInstance()->register('content.render', function($content) {
    return strtoupper($content);
});
```

#### Triggering a Hook

```php
// Trigger an action
$hooks = service('hooks');
$hooks->action('user.login', [$currentUser]);

// Apply filters
$content = "hello world";
$content = $hooks->filter('content.render', $content); // Returns "HELLO WORLD"
```

#### Using the `hook()` Helper

The `hook()` helper is used to retrieve hook configurations or values from `Hooks/` files in your modules.

```php
// Returns the value of 'header' from 'Hooks/Frontend.php'
$headerHook = hook('Frontend.header');
```

#### Creating a Hook

To create a hook that can be retrieved via the `hook()` helper, create a file in your module's `Hooks/` directory (e.g., `modules/Blog/Hooks/Frontend.php`). This file should return an associative array of `Star\HyperHook` objects.

```php
<?php

use StarCore\Star\HyperHook;

return [
    'header' => new HyperHook('header', 'Header Hook', 'This hook is used to display the header.'),
    'footer' => new HyperHook('footer', 'Footer Hook', 'This hook is used to display the footer.'),
];
```

You can then access these hooks using `hook('Frontend.header')` or `hook('Frontend.footer')`.

## Module Structure

A typical module structure looks like this:

```
modules/
  Blog/
    Config/
      Routes.php
    Controllers/
    Models/
    Views/
    Hooks/
    init.php
```

### Development Module Structure

A typical development module structure looks like this:

```
modules/
  .star-dev/
    DebugToolbar/
      Config/
        Routes.php
      Controllers/
      Models/
      Views/
      Hooks/
      init.php
```

## Module Autoloading

StarCore automatically discovers and loads modules located in the `modules/` directory (and `modules/.star-dev/` for development modules). This process happens during the `pre_system` event.

1.  **Namespace Registration**: The autoloader automatically registers the module's namespace based on its directory name. For example, a module in `modules/Blog` will have the `Blog` namespace registered.
2.  **Initialization**: If an `init.php` file exists in the module's root, it is executed. This is the ideal place to register hooks or perform other setup tasks.

## Composer Compatibility

**StarCore is designed to work seamlessly with Composer.**

- **Library Support**: You can use any StarCore-supported module as a Composer package. Simply require it in your project's `composer.json` and use it as usual. StarCore prioritizes Composer packages over its own modules.
- **Local Modules**: Unlike Composer’s autoloader, StarCore’s autoloader automatically registers namespaces for modules in the `modules/` directory. This means you don’t need to run `composer dump-autoload` every time you create a new module or class, helping speed up your development workflow.

> Choose the option that best fits your needs.

## License

This project is licensed under the MIT License.

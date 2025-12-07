<?php

namespace Config;

use CodeIgniter\Events\Events;

/*
 * --------------------------------------------------------------------
 * StarCore Modules autoloading on pre_system
 * --------------------------------------------------------------------
 * StarCore Modules are loaded on the pre_system event.
 * This allows for modules to be loaded and hooks to be registered
 * before the system starts.
 */

Events::on('pre_system', function (): void {

    // Load the autoloader service and StarCore configuration.
    /** @var \CodeIgniter\Autoloader\Autoloader $autoloader */
    $autoloader = service('autoloader');
    /** @var \StarCore\Config\Star $star */
    $star = config('Star');

    $modulesPath = $star->modulesPath;
    $devModulesPath = $star->devModulesPath;

    // Log safe mode as a string.
    if ($star->log) {
        log_message('info', 'StarCore safe mode: ' . ($star->safeMode ? 'true' : 'false'));
    }

    // Array to collect init file paths.
    $initFiles = [];

    // Helper closure to autoload modules.
    // $subFolder should be empty for regular modules, or e.g. '.star-dev' for development modules.
    $autoloadModules = function (array $modules, bool $isDev = false) use ($star, $autoloader, $modulesPath, $devModulesPath, &$initFiles): void {
        foreach ($modules as $module) {
            // Skip any accidental dot entries.
            if ($module === '.' || $module === '..') {
                continue;
            }

            // Check if the namespace is already registered (e.g. via Composer).
            $existingPaths = $autoloader->getNamespace($module);

            if (! empty($existingPaths)) {
                // Use the first registered path to look for init.php.
                // Note: getNamespace returns an array of paths.
                $modulePath = rtrim(reset($existingPaths), '\\/ ') . DIRECTORY_SEPARATOR;
                if ($star->log && $star->logLevel >= 1) {
                    log_message('debug', 'Module path found for module: ' . $module . ' at ' . $modulePath);
                }
            } else {
                // Build the full module path from local modules directory.
                $modulePath = ($isDev ? $devModulesPath : $modulesPath)
                    . $module . DIRECTORY_SEPARATOR;
                if ($star->log && $star->logLevel >= 1) {
                    log_message('debug', 'Module path built for module: ' . $module . ' at ' . $modulePath);
                }
                // Register the module's namespace.
                $autoloader->addNamespace($module, $modulePath);
                if ($star->log && $star->logLevel >= 1) {
                    log_message('debug', 'Module namespace registered for module: ' . $module . ' at ' . $modulePath);
                }
            }

            // Build the path to the init file.
            $initFile = $modulePath . 'init.php';
            // Instead of requiring the init file immediately, store its path.
            if (file_exists($initFile)) {
                $initFiles[] = $initFile;
                if ($star->log && $star->logLevel >= 1) {
                    log_message('debug', 'Init file found for module: ' . $module . ' at ' . $initFile);
                }
            } else {
                if ($star->log && $star->logLevel >= 1) {
                    log_message('debug', 'Init file not found for module: ' . $module . ' at ' . $initFile);
                }
            }
        }
    };

    // Autoload regular modules.
    $activeModules = $star->getActiveModules();
    $autoloadModules($activeModules);
    if ($star->log) {
        log_message('info', 'Active Modules: ' . implode(', ', $activeModules));
    }

    // Autoload development modules.
    $activeDevModules = $star->getActiveDevModules();
    $autoloadModules($activeDevModules, true);
    if ($star->log) {
        log_message('info', 'Active dev Modules: ' . implode(', ', $activeDevModules));
    }

    if ($star->log) {
        // Display the namespaces added to the autoloader.
        log_message('info', 'Namespaces autoloaded: ' . implode(', ', array_keys($autoloader->getNamespace())));
    }

    // Now run all collected init files.
    foreach ($initFiles as $file) {
        require_once $file;
    }

    // Trigger module initialization hooks so that modules can register hooks on pre_system.
    /** @var \StarCore\Service\HyperHooks $hooks */
    $hooks = service('hooks');

    $hooks->trigger(hook('Core.modules:init'));
});

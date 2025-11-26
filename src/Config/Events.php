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
    log_message('info', 'StarCore safe mode: ' . ($star->safeMode ? 'true' : 'false'));

    // Array to collect init file paths.
    $initFiles = [];

    // Helper closure to autoload modules.
    // $subFolder should be empty for regular modules, or e.g. '.star-dev' for development modules.
    $autoloadModules = function (array $modules, bool $isDev = false) use ($autoloader, $modulesPath, $devModulesPath, &$initFiles): void {
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
            } else {
                // Build the full module path from local modules directory.
                $modulePath = ($isDev ? $devModulesPath : $modulesPath)
                    . $module . DIRECTORY_SEPARATOR;
                // Register the module's namespace.
                $autoloader->addNamespace($module, $modulePath);
            }

            // Build the path to the init file.
            $initFile = $modulePath . 'init.php';
            // Instead of requiring the init file immediately, store its path.
            if (file_exists($initFile)) {
                $initFiles[] = $initFile;
            }
        }
    };

    // Autoload regular modules.
    $activeModules = $star->getActiveModules();
    log_message('info', 'Active Modules: ' . implode(', ', $activeModules));
    $autoloadModules($activeModules);

    // Autoload development modules.
    $activeDevModules = $star->getActiveDevModules();
    log_message('info', 'Active dev Modules: ' . implode(', ', $activeDevModules));
    $autoloadModules($activeDevModules, true);

    // Display the namespaces added to the autoloader.
    log_message('info', 'Namespaces autoloaded: ' . implode(', ', array_keys($autoloader->getNamespace())));

    // Now run all collected init files.
    foreach ($initFiles as $file) {
        require_once $file;
    }

    // Trigger module initialization hooks so that modules can register hooks on pre_system.
    /** @var \StarCore\Service\HyperHooks $hooks */
    $hooks = service('hooks');

    $hooks->trigger(hook('Core.modules:init'));
});

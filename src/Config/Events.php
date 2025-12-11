<?php

namespace StarCore\Config;

use CodeIgniter\Events\Events;
use CodeIgniter\Config\Services;

/*
 * --------------------------------------------------------------------
 * StarCore Immediate Autoloading
 * --------------------------------------------------------------------
 * NOTE: This logic runs IMMEDIATELY when this file is included.
 * We do not wrap this in 'pre_system' because we need to register
 * namespaces and events BEFORE 'pre_system' fires.
 */

// Load Services & Config directly
/** @var \CodeIgniter\Autoloader\Autoloader $autoloader */
$autoloader = Services::autoloader();

/** @var \StarCore\Config\Star $star */
// Use full class name to ensure safe loading during early boot
$star = config('StarCore\Config\Star');

// Log Safe Mode
if ($star->log) {
    log_message('info', 'StarCore safe mode: ' . ($star->safeMode ? 'true' : 'false'));
}

// Stop if Safe Mode is On
if ($star->safeMode) {
    return;
}

$modulesPath = $star->modulesPath;
$devModulesPath = $star->devModulesPath;

// Array to collect init file paths.
$initFiles = [];

// Array to collect event file paths.
$eventFiles = [];

// Helper closure to autoload modules.
$autoloadModules = function (array $modules, bool $isDev = false) use ($star, $autoloader, $modulesPath, $devModulesPath, &$initFiles, &$eventFiles): void {
    foreach ($modules as $module) {
        // Skip any accidental dot entries.
        if ($module === '.' || $module === '..') {
            continue;
        }

        // Check if the namespace is already registered (e.g. via Composer).
        $existingPaths = $autoloader->getNamespace($module);

        if (!empty($existingPaths)) {
            // Use the first registered path to look for init.php.
            $modulePath = rtrim(reset($existingPaths), '\\/ ') . DIRECTORY_SEPARATOR;

            if ($star->log && $star->logLevel >= 1) {
                log_message('debug', 'Module path found for module: ' . $module . ' at ' . $modulePath);
            }
        } else {
            // Build the full module path from local modules directory.
            $modulePath = ($isDev ? $devModulesPath : $modulesPath) . $module . DIRECTORY_SEPARATOR;

            if ($star->log && $star->logLevel >= 1) {
                log_message('debug', 'Module path built for module: ' . $module . ' at ' . $modulePath);
            }

            // Register the module's namespace IMMEDIATELY.
            $autoloader->addNamespace($module, $modulePath);

            if ($star->log && $star->logLevel >= 1) {
                log_message('debug', 'Module namespace registered for module: ' . $module . ' at ' . $modulePath);
            }
        }

        // A. Build the path to the init file.
        $initFile = $modulePath . 'init.php';
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

        // B. Build the path to the events file.
        $eventFile = $modulePath . 'Config' . DIRECTORY_SEPARATOR . 'Events.php';
        if (file_exists($eventFile)) {
            $eventFiles[] = $eventFile;
            if ($star->log && $star->logLevel >= 1) {
                log_message('debug', 'Events file found for module: ' . $module . ' at ' . $eventFile);
            }
        }
    }
};

// Execute Autoloading Logic
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
    // Display the namespaces added to the autoloader.
    log_message('info', 'Namespaces autoloaded: ' . implode(', ', array_keys($autoloader->getNamespace())));
}

// Run Collected Files IMMEDIATELY
// This ensures that constants are defined and Event listeners are registered
// BEFORE the system triggers 'pre_system'.

// Run init files.
foreach ($initFiles as $file) {
    require_once $file;
}

// Run event files.
foreach ($eventFiles as $file) {
    require_once $file;
}

/*
 * --------------------------------------------------------------------
 * StarCore Hook Trigger
 * --------------------------------------------------------------------
 * Now that all modules are loaded and their events are registered,
 * we can safely wait for 'pre_system' to trigger the StarCore init hook.
 */

Events::on('pre_system', function () {
    // Trigger module initialization hooks so that modules can register hooks on pre_system.
    /** @var \StarCore\Service\HyperHooks $hooks */
    $hooks = \StarCore\Service\HyperHooks::getInstance();

    $hooks->trigger(hook('Core.modules:init'));
});
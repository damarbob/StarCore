<?php
// star_helper.php

use StarCore\Star\HyperHook;

if (!function_exists('hook')) {
    /**
     * Retrieve a hook value for the given key from any registered namespace.
     *
     * Usage: hook('Frontend.header') will search all namespaces for a file
     * named 'Hooks/Frontend.php' and return the value for the key 'header'.
     *
     * @param string $key The hook key (e.g., "Frontend.header")
     * @param array  $params Optional parameters to replace any placeholders in the hook's value.
     *
     * @return string The hook value, or an empty string if not found.
     */
    function hook(string $key, array $params = []): string
    {
        // Split the key ("Frontend.header") into $group and $lineKey.
        $parts = explode('.', $key, 2);
        if (count($parts) !== 2) {
            return ''; // Invalid format.
        }
        list($group, $lineKey) = $parts;

        // Security Check: Prevent Path Traversal
        // Only allow alphanumeric, underscore, dash, and directory separators.
        // This blocks dots, preventing ".." traversal.
        if (!preg_match('/^[a-zA-Z0-9_\-\/\\\\]+$/', $group)) {
            log_message('error', 'Security: Invalid hook group name detected: ' . $group);
            return '';
        }

        // Use service('locator') to locate the hook file in all namespaces
        /** @var CodeIgniter\Autoloader\FileLocatorInterface */
        $locator = service('locator');
        $files = $locator->search('Hooks/' . $group . '.php');

        if (empty($files)) {
            // Retrieve the stack trace to help locate the caller.
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            log_message('error', 'Hook file not found for group ' . $group . '. Stack trace: ' . json_encode($trace, JSON_PRETTY_PRINT));
            return ''; // Hook group file not found in any namespace
        }

        // Use the first found file (you might want to implement priority logic here)
        $file = reset($files);

        // Use a static cache to prevent loading the same file multiple times.
        static $hooksCache = [];
        if (! isset($hooksCache[$group])) {
            // The hook file must return an array.
            $hooksCache[$group] = require $file;
        }

        $hooks = $hooksCache[$group];

        // Check if the key exists and that it's an instance of HyperHook.
        if (! isset($hooks[$lineKey]) || ! $hooks[$lineKey] instanceof HyperHook) {
            return '';
        }

        $hookValue = $hooks[$lineKey];
        $result = $hookValue->getName();

        // Replace placeholders if any are provided.
        if (! empty($params)) {
            foreach ($params as $find => $replace) {
                // For example, if the hook value contains "{name}", it will be replaced.
                $result = str_replace('{' . $find . '}', $replace, $result);
            }
        }

        // Return the hook's name
        return $result;
    }
}

if (!function_exists('dump_hooks')) {
    /**
     * Dump hooks from files in the Hooks directories across all namespaces.
     *
     * @param string|null $group If specified, only returns hooks from this group (e.g., 'Frontend')
     * @return array<string, array> An associative array of hooks
     */
    function dump_hooks(?string $group = null): array
    {
        static $allHooks = [];
        static $isFullCache = false;

        // Security Check: If group is specified, validate it
        if ($group !== null && !preg_match('/^[a-zA-Z0-9_\-\/\\\\]+$/', $group)) {
            log_message('error', 'Security: Invalid hook group name detected in dump_hooks: ' . $group);
            return [];
        }

        // If no group specified and we have fully cached all hooks, return them
        if ($group === null && $isFullCache) {
            return $allHooks;
        }

        // If a group is specified and we have it cached, return just that group
        if ($group !== null && isset($allHooks[$group])) {
            return [$group => $allHooks[$group]];
        }

        /** @var CodeIgniter\Autoloader\FileLocatorInterface */
        $locator = service('locator');

        if ($group === null) {
            // Get all hook files in all namespaces
            $files = $locator->listFiles('Hooks/');
            $hooks = [];

            foreach ($files as $file) {
                // Ensure we get the correct group name relative to Hooks/ directory if needed, 
                // but standard practice here seems to be filename as group.
                $currentGroup = pathinfo($file, PATHINFO_FILENAME);

                // We re-require to ensure freshness if not fully cached, or we could check isset.
                // Given the issue was incomplete cache, we should populate what's missing or just rebuild.
                // To be safe and consistent with "dump", let's reload or check cache.
                if (isset($allHooks[$currentGroup])) {
                    $hooks[$currentGroup] = $allHooks[$currentGroup];
                } else {
                    $loadedHooks = require $file;
                    if (is_array($loadedHooks)) {
                        $hooks[$currentGroup] = $loadedHooks;
                        $allHooks[$currentGroup] = $loadedHooks;
                    }
                }
            }

            // Mark as fully cached
            $isFullCache = true;
            // Ensure $allHooks contains everything we just found (in case of updates)
            $allHooks = $hooks; // Rely on the fresh scan

            return $hooks;
        } else {
            // Search specifically for the requested group
            // We already checked cache above, so this is a miss.
            $files = $locator->search('Hooks/' . $group . '.php');

            if (!empty($files)) {
                $file = reset($files); // Get the first found file
                $loadedHooks = require $file;
                if (is_array($loadedHooks)) {
                    $allHooks[$group] = $loadedHooks;
                    return [$group => $loadedHooks];
                }
            }
        }

        return [];
    }
}

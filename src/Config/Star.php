<?php

namespace StarCore\Config;

use CodeIgniter\Config\BaseConfig;

class Star extends BaseConfig
{
    // Enable logging for StarCore.
    public bool $log = true;

    // Log level for StarCore. Currently supported levels are 0 (high level) and 1 (low level). Setting to 1 will log more information including the level 0.
    public int $logLevel = 0;

    public string $kernelName = 'StarCore';
    public string $kernelVersion = '0.1.0-alpha.1';

    public string $modulesPath = ROOTPATH . 'modules' . DIRECTORY_SEPARATOR;
    public string $devModulesPath = ROOTPATH . 'modules' . DIRECTORY_SEPARATOR . '.star-dev' . DIRECTORY_SEPARATOR;

    // Default modules as a comma-separated list.
    protected string $defaultActiveModules = '';

    // Active modules as a comma-separated list.
    protected string $activeModules = '';

    // Active development modules as a comma-separated list.
    protected string $activeDevModules = '';

    // If true, disables ALL modules.
    public bool $safeMode = false;

    public function __construct()
    {
        parent::__construct();
        if ($this->log) {
            log_message('info', 'StarCore Kernel Config loaded.');
        }

        if (!$this->safeMode) {
            if (empty($this->activeModules)) {
                $this->activeModules = $this->defaultActiveModules;
            } else {
                // Merge default with custom active modules.
                $defaultModules = array_filter(array_map('trim', explode(',', $this->defaultActiveModules)));
                $customModules  = array_filter(array_map('trim', explode(',', $this->activeModules)));
                $mergedModules  = array_unique(array_merge($defaultModules, $customModules));
                $this->activeModules = implode(',', $mergedModules);
            }
        } else {
            // In safe mode, all modules are disabled.
            $this->activeModules = '';
            $this->activeDevModules = '';
        }
    }

    /**
     * Returns the active modules as an array.
     *
     * @return array The list of active module names.
     */
    public function getActiveModules(): array
    {
        return array_filter(array_map('trim', explode(',', $this->activeModules)));
    }

    /**
     * Returns the active development modules as an array.
     *
     * @return array The list of active development module names.
     */
    public function getActiveDevModules(): array
    {
        return array_filter(array_map('trim', explode(',', $this->activeDevModules)));
    }
}

<?php

use StarCore\Star\HyperHook;

return [

    // Hooks
    'modules:init' => new HyperHook('core:modules:init', 'Core Init Modules', ''),
];

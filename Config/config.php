<?php

return [
    'name' => 'DevelDashboard',

    'display_name' => 'Devel Dashboard',

    'dashboard_uri' => '/dashboard',

    /**
     * You can set a custom namespace for the auth controllers here if you want
     * to override them.
     */
    'auth_controllers_namespace' => '\Modules\DevelDashboard\Http\Controllers\Auth',

    /**
     * You can set a custom controller for the main site's SettingsControllers
     * here if you need customization.
     */
    'site_settings_controller' => '\Modules\DevelDashboard\Http\Controllers\SettingsController',
];

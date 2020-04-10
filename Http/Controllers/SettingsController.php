<?php

namespace Modules\DevelDashboard\Http\Controllers;

use Modules\DevelDashboard\Http\Controllers\BaseSettingsController;

class SettingsController extends BaseSettingsController
{
    protected $groups = [
        'site' => true,
    ];
}

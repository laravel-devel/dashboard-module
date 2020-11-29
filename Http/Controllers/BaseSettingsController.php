<?php

namespace Modules\DevelDashboard\Http\Controllers;

use Devel\Models\Settings;
use Illuminate\Http\Request;
use Devel\Http\Controllers\Controller;

class BaseSettingsController extends Controller
{
    protected $groups = [
        // 'site' => [
        //     'name',
        // ],
    ];

    protected $form = [];

    protected $values = [];

    public function __construct()
    {
        // Input fields for the form
        foreach ($this->groups as $group => $keys) {
            $groupName = ucwords(str_replace('_', ' ', $group));
            $form[$groupName] = [];

            // Fetch the whole group of settings
            if ($keys === true) {
                $keys = Settings::where('group', $group)
                    ->get()
                    ->pluck('key')
                    ->toArray();
            }

            foreach ($keys as $key) {
                $key = "{$group}-{$key}";
                $setting = Settings::getObject($key);

                // A form field
                $this->form[$groupName][] = [
                    'type' => $setting->field_type,
                    'name' => "$key",
                    'label' => isset($setting)
                        ? $setting->name
                        : Settings::keyToName("$key"),
                ];

                // A value for the field
                $this->values[$key] = Settings::read($key);
            }
        }
    }

    /**
     * Show the settings form
     *
     * @return void
     */
    public function edit()
    {
        $this->setMeta('title', 'Dashboard');
        $this->setMeta('title', 'Settings');

        return view('develdashboard::settings.edit', [
            'form' => $this->form,
            'values' => $this->values,
        ]);
    }

    /**
     * Update the site settings
     *
     * @return void
     */
    public function update(Request $request)
    {
        foreach ($request->all() as $key => $value) {
            if (substr($key, 0, 1) === '_') {
                continue;
            }

            Settings::set($key, $value);
        }

        // Boolean values are a special case. If they are checkboxes/switches,
        // then the field will not be present when it's set to "false".
        foreach ($this->groups as $group => $keys) {
            foreach ($keys as $key) {
                if (!$request->has("{$group}-{$key}")) {
                    Settings::set("{$group}-{$key}", 'false');
                }
            }
        }

        return response()->json([
            'notification' => [
                'message' => 'Settings were updated!',
                'type' => 'success',
            ],
        ]);
    }
}

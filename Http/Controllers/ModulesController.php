<?php

namespace Modules\DevelDashboard\Http\Controllers;

use Devel\Http\Controllers\Controller;
use Devel\Services\ModuleService;
use Devel\Modules\Facades\Module;

class ModulesController extends Controller
{
    protected $protectedModules = [
        'DevelDashboard',
    ];

    /**
     * Modules list page
     *
     * @return void
     */
    public function index()
    {
        $this->setMeta('title', 'Manage Modules');

        $modules = Module::getInstalledOrdered();

        foreach ($modules as $key => $module) {
            // Some modules could not be disabled and shouldn't be visible
            if (in_array($key, $this->protectedModules)) {
                unset($modules[$key]);

                continue;
            }

            $modules[$key] = [
                'displayName' => config($module->getLowerName() . '.display_name'),
                'name' => $module->getName(),
                'alias' => $module->getAlias(),
                'description' => $module->getDescription(),
                'enabled' => $module->isEnabled(),
            ];
        }

        return view('develdashboard::modules.index', [
            'modules' => $modules,
        ]);
    }

    public function toggleEnabled(string $alias)
    {
        try {
            $module = Module::findOrFail($alias);
        } catch (\Exception $e) {
            return response()->json([
                'message' => "Module with alias \"{$alias}\" not found.",
            ], 404);
        }

        $name = $module->getName();

        // Certain modules cannot be disabled
        if (in_array($module->getName(), $this->protectedModules)) {
            return response()->json([
                'message' => "Module \"{$name}\" cannot be disabled.",
            ], 422);
        }

        if (!$module->isEnabled()) { 
            // A module cannot be enabled if its dependencies are not met
            $depenencyErrors = ModuleService::checkDependencies($module);

            if (count($depenencyErrors)) {
                $msg = "Module \"{$name}\" has unmet dependencies and cannot be enabled:";

                foreach ($depenencyErrors as $error) {
                    $msg .= "\n- {$error}";
                }

                return response()->json([
                    'message' => $msg,
                ], 422);
            }

            // A module cannot be enabled if it's not properly installed
            if (!$module->isInstalled()) {
                return response()->json([
                    'message' => "Module \"{$name}\" is not installed.",
                ], 422);
            }
        }

        if ($module->isEnabled()) {
            $module->disable();
        } else {
            $module->enable();
        }

        return response()->json([]);
    }
}

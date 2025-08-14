<?php

namespace App\Http\Controllers\module_menu;

use App\Http\Controllers\Controller;
use App\Helpers\HS\ModuleHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ModuleMenuController extends Controller
{
    public function getModuleMenus()
    {
        $modules = ModuleHelper::getSettingsModules();
        $moduleMenus = [];
info($modules);
        foreach ($modules as $module) {
            $controllerClass = "\\Modules\\" . $module->slug .  "\\App\\Http\\Controllers\\MenuController";

            if (class_exists($controllerClass) && method_exists($controllerClass, 'getMenu')) {
                $menuData = (new $controllerClass)->getMenu();

                if (!isset($menuData['is_active']) || !$menuData['is_active']) {
                    continue;
                }
                $filteredMenu = $this->filterMenuByPermissions($menuData['menu'] ?? []);

                if (!empty($filteredMenu)) {
                    $moduleMenus[] = [
                        'module' => $module,
                        'menu' => $filteredMenu
                    ];
                }
            }
        }



        return $moduleMenus;
    }

private function filterMenuByPermissions(array $menus)
{
    $user = Auth::user();
    $filteredMenus = [];

    // 🔹 If super admin, skip permission checks entirely
    if ($user->is_supreme_admin == 1) {
        return $menus;
    }

    foreach ($menus as $menu) {
        $hasPermission = true;

        if (isset($menu['permissions'])) {
            $hasPermission = $user->can($menu['permissions']);
        }

        if (!empty($menu['submenu']) && is_array($menu['submenu'])) {
            $menu['submenu'] = array_filter($menu['submenu'], function ($submenu) use ($user) {
                return !isset($submenu['permissions']) || $user->can($submenu['permissions']);
            });

            if (empty($menu['submenu']) && !$hasPermission) {
                continue;
            }
        }

        if ($hasPermission || !empty($menu['submenu'])) {
            $filteredMenus[] = $menu;
        }
    }

    return $filteredMenus;
}


}

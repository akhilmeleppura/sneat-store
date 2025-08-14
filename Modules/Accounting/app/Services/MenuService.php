<?php

namespace Modules\Accounting\Services;

class MenuService
{
    public static function getMenu($currentRoute)
{
    $menuJson = file_get_contents(module_path('Accounting', 'Resources/json/submenu.json'));
    $menuData = json_decode($menuJson, true);
    
    return collect($menuData['menu'])->map(function($item) use ($currentRoute) {
        $item['active'] = request()->is(trim($item['url'], '/'));
        
        // Ensure identifier exists
        if (!isset($item['identifier'])) {
            $item['identifier'] = strtolower(str_replace(' ', '_', $item['label']));
        }
        
        return $item;
    })->toArray();
}
}

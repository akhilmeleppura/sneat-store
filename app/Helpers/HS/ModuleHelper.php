<?php

namespace App\Helpers\HS;

class ModuleHelper
{
    public static function getSettingsModules()
    {
        $modules = [];
        $modulesPath = base_path('Modules');

        foreach (scandir($modulesPath) as $moduleName) {
            if ($moduleName === '.' || $moduleName === '..') continue;

            $jsonPath = $modulesPath . '/' . $moduleName . '/module.json';

            if (file_exists($jsonPath)) {
                $json = json_decode(file_get_contents($jsonPath));

                $slug = $json->slug ?? $json->alias ?? $moduleName;

if (isset($json->enabled) && $json->enabled) {
    $modules[] = (object)[
        'name' => $json->name ?? $moduleName,
        'url'  => url($slug),
        'slug' => $slug
    ];


                }
            }
        }

        return $modules;
    }
}

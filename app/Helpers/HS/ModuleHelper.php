<?php

namespace App\Helpers\HS;

use Illuminate\Support\Str;

class ModuleHelper
{
    public static function getSettingsModules()
{
    $modules = [];
    $modulesPath = base_path('Modules');

    foreach (scandir($modulesPath) as $moduleFolder) {
        if ($moduleFolder === '.' || $moduleFolder === '..') continue;

        $jsonPath = $modulesPath . '/' . $moduleFolder . '/module.json';
        if (!file_exists($jsonPath)) continue;

        $json = json_decode(file_get_contents($jsonPath));
        if (!isset($json->enabled) || !$json->enabled) continue;

        $modules[] = (object)[
            'name' => $json->name ?? $moduleFolder,
            'url'  => url($json->slug ?? Str::slug($moduleFolder)),
            'slug' => $moduleFolder, // Store folder name, exact case
        ];
    }

    return $modules;
}

}

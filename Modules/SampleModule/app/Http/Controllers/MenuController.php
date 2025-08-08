<?php

namespace Modules\SampleModule\App\Http\Controllers;

use App\Http\Controllers\Controller;

class MenuController extends Controller
{
    public function getMenu()
    {
        return [
            'is_active' => true,
            'menu' => [
                [
                    'name' => 'Sample Module',
                    'slug' => 'samplemodule.test.index',
                    'url'  => 'samplemodule/test',
                    'icon' => 'bx bx-book',
                    'permissions' => 'user.view',
                    'submenu' => [
                        [
                            'name' => 'Demo Submenu',
                            'slug' => 'samplemodule.demo.index',
                            'url'  => 'samplemodule/demo',
                            'permission' => 'user.view'
                        ]
                    ]
                ]
            ]
        ];
    }
}

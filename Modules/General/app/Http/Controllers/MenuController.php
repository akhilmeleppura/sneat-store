<?php

namespace Modules\General\App\Http\Controllers;

use App\Http\Controllers\Controller;

class MenuController extends Controller
{
    public function getMenu()
    {
        return [
            'is_active' => true,
            'menu' => [
                [
                    'name' => 'General',
                    'slug' => 'General',
                    'url'  => '#',
                    'icon' => 'bx bx-layout',
                    'permissions' => 'user.view',
                    'submenu' => [
                        [
                            'name' => 'Document Templates',
                            'slug' => 'templates.index',
                            'url'  => 'general/document/templates',
                            'permissions' => 'user.view'
                        ]

                    ]
                ]
            ]
        ];
    }
}

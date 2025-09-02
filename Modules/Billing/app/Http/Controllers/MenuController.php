<?php

namespace Modules\Billing\App\Http\Controllers;

use App\Http\Controllers\Controller;

class MenuController extends Controller
{
    public function getMenu()
    {
        return [
            'is_active' => true,
            'menu' => [
                [
                    'name' => 'Billings',
                    'slug' => 'Billings',
                    'url'  => '#',
                    'icon' => 'bx bx-calculator',
                    'permissions' => 'user.view',
                    'submenu' => [
                        [
                            'name' => 'Billings',
                            'slug' => 'accounting.billings.index',
                            'url'  => 'accounting/billings',
                            'permissions' => 'user.view'

                        ]

                    ]
                ]
            ]
        ];
    }
}

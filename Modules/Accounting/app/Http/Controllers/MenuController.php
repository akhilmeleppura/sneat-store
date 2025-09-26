<?php

namespace Modules\Accounting\App\Http\Controllers;

use App\Http\Controllers\Controller;

class MenuController extends Controller
{
    public function getMenu()
    {
        return [
            'is_active' => true,
            'menu' => [
                [
                    'name' => 'Accounting',
                    'slug' => 'accounting.index',
                    'url'  => '/accounting',
                    'icon' => 'bx bx-book',
                    'permissions' => 'user.view',
                    'submenu' => [
                        [
                            'name' => 'Accounting',
                            'slug' => 'accounting.index',
                            'url'  => '/accounting/chart-of-accounts',
                            'permissions' => 'user.view'
                        ],
                        [
                            'name' => 'Journal',
                            'slug' => 'accounting.Journal.index',
                            'url'  => 'accounting/journal',
                            'permissions' => 'user.view'
                        ],
                        [
                            'name' => 'Ledger',
                            'slug' => 'accounting.ledger.index',
                            'url'  => 'accounting/ledger',
                            'permissions' => 'user.view'
                        ],
                        [
                            'name' => 'Trial Balance',
                            'slug' => 'accounting.trial-balance.index',
                            'url'  => 'accounting/trial-balance',
                            'permissions' => 'user.view'
                        ]
                    ]
                ]
            ]
        ];
    }
}

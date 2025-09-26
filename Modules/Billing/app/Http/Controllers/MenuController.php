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
                            'name' => 'Invoices',
                            'slug' => 'accounting.billings.index',
                            'url'  => 'accounting/billings',
                            'permissions' => 'user.view'
                        ],
                        [
                            'name' => 'Debit Notes',
                            'slug' => 'accounting.debit-notes.index',
                            'url'  => 'accounting/billings/debit-notes',
                            'permissions' => 'user.view'
                        ],
                        [
                            'name' => 'Credit Notes',
                            'slug' => 'accounting.credit-notes.index',
                            'url'  => 'accounting/billings/credit-notes',
                            'permissions' => 'user.view'
                        ]
                    ]
                ]
            ]
        ];
    }
}

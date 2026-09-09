<?php

namespace Modules\Context\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Currency;
use Modules\Context\Models\Tenant;

class AdminTenantSettingController extends Controller
{
    /**
     * Display the tenant settings management view.
     */
    public function index(): View|RedirectResponse
    {
        $tenant = Context::currentTenant() ?? Tenant::active()->first();

        if (! $tenant) {
            return redirect()->route('dashboard-analytics')
                ->with('error', 'No active tenant found to configure settings.');
        }

        // Fetch all active currencies for dropdown selection
        $currencies = Currency::where('is_active', true)->get();

        // Retrieve current settings grouped for easy display
        $settings = [
            'general' => [
                'company_name'     => $tenant->getSetting('company_name', $tenant->name, 'general'),
                'contact_email'    => $tenant->getSetting('contact_email', 'support@' . ($tenant->domain ?? 'sneatstore.com'), 'general'),
                'contact_phone'    => $tenant->getSetting('contact_phone', '+1 (555) 019-2834', 'general'),
                'address'          => $tenant->getSetting('address', '100 Innovation Way, Suite 400, New York, NY', 'general'),
                'timezone'         => $tenant->getSetting('timezone', $tenant->timezone ?? 'UTC', 'general'),
                'default_currency' => $tenant->getSetting('default_currency', $tenant->currency ?? 'USD', 'general'),
            ],
            'ecommerce' => [
                'order_prefix'            => $tenant->getSetting('order_prefix', 'SNT-', 'ecommerce'),
                'tax_mode'                => $tenant->getSetting('tax_mode', 'exclusive', 'ecommerce'),
                'enable_guest_checkout'   => (bool) $tenant->getSetting('enable_guest_checkout', true, 'ecommerce'),
                'low_stock_threshold'     => (int) $tenant->getSetting('low_stock_threshold', 5, 'ecommerce'),
                'free_shipping_threshold' => (float) $tenant->getSetting('free_shipping_threshold', 150.00, 'ecommerce'),
            ],
            'branding' => [
                'store_tagline'      => $tenant->getSetting('store_tagline', 'Premium Modular Commerce Experience', 'branding'),
                'invoice_footer'     => $tenant->getSetting('invoice_footer', 'Thank you for shopping with Sneat Store. For queries, contact support@sneatstore.com', 'branding'),
                'support_hours'      => $tenant->getSetting('support_hours', 'Mon - Fri: 9:00 AM - 6:00 PM EST', 'branding'),
                'return_policy_days' => (int) $tenant->getSetting('return_policy_days', 30, 'branding'),
            ],
            'integrations' => [
                'webhook_secret'          => $tenant->getSetting('webhook_secret', '', 'integrations'),
                'payment_gateway_api_key' => $tenant->getSetting('payment_gateway_api_key', '', 'integrations'),
            ],
        ];

        return view('context::admin.settings.index', compact('tenant', 'settings', 'currencies'));
    }

    /**
     * Update tenant settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $tenant = Context::currentTenant() ?? Tenant::active()->first();

        if (! $tenant) {
            return redirect()->back()->with('error', 'No active tenant found.');
        }

        $validated = $request->validate([
            // General
            'company_name'            => 'required|string|max:255',
            'contact_email'           => 'required|email|max:255',
            'contact_phone'           => 'nullable|string|max:50',
            'address'                 => 'nullable|string|max:500',
            'timezone'                => 'required|string|max:100',
            'default_currency'        => 'required|string|max:10',

            // E-Commerce
            'order_prefix'            => 'required|string|max:20',
            'tax_mode'                => 'required|in:inclusive,exclusive',
            'enable_guest_checkout'   => 'nullable|boolean',
            'low_stock_threshold'     => 'required|integer|min:0|max:1000',
            'free_shipping_threshold' => 'required|numeric|min:0',

            // Branding
            'store_tagline'           => 'nullable|string|max:255',
            'invoice_footer'          => 'nullable|string|max:1000',
            'support_hours'           => 'nullable|string|max:100',
            'return_policy_days'      => 'required|integer|min:0|max:365',

            // Integrations
            'webhook_secret'          => 'nullable|string|max:255',
            'payment_gateway_api_key' => 'nullable|string|max:255',
        ]);

        // 1. General Group
        $tenant->setSetting('company_name', $validated['company_name'], 'general');
        $tenant->setSetting('contact_email', $validated['contact_email'], 'general');
        $tenant->setSetting('contact_phone', $validated['contact_phone'] ?? '', 'general');
        $tenant->setSetting('address', $validated['address'] ?? '', 'general');
        $tenant->setSetting('timezone', $validated['timezone'], 'general');
        $tenant->setSetting('default_currency', $validated['default_currency'], 'general');

        // Sync tenant model properties if changed
        $tenant->update([
            'timezone' => $validated['timezone'],
            'currency' => $validated['default_currency'],
        ]);

        // 2. E-Commerce Group
        $tenant->setSetting('order_prefix', $validated['order_prefix'], 'ecommerce');
        $tenant->setSetting('tax_mode', $validated['tax_mode'], 'ecommerce');
        $tenant->setSetting('enable_guest_checkout', $request->has('enable_guest_checkout') ? '1' : '0', 'ecommerce');
        $tenant->setSetting('low_stock_threshold', (string) $validated['low_stock_threshold'], 'ecommerce');
        $tenant->setSetting('free_shipping_threshold', (string) $validated['free_shipping_threshold'], 'ecommerce');

        // 3. Branding Group
        $tenant->setSetting('store_tagline', $validated['store_tagline'] ?? '', 'branding');
        $tenant->setSetting('invoice_footer', $validated['invoice_footer'] ?? '', 'branding');
        $tenant->setSetting('support_hours', $validated['support_hours'] ?? '', 'branding');
        $tenant->setSetting('return_policy_days', (string) $validated['return_policy_days'], 'branding');

        // 4. Integrations & Security (Encrypted Storage)
        if (! empty($validated['webhook_secret'])) {
            $tenant->setSetting('webhook_secret', $validated['webhook_secret'], 'integrations', true);
        }
        if (! empty($validated['payment_gateway_api_key'])) {
            $tenant->setSetting('payment_gateway_api_key', $validated['payment_gateway_api_key'], 'integrations', true);
        }

        return redirect()->route('admin.tenant.settings')
            ->with('success', "Tenant configuration for '{$tenant->name}' saved successfully.");
    }
}

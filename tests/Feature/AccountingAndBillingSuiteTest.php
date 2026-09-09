<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingAndBillingSuiteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'email'            => 'supreme.admin@sneat.test',
            'is_supreme_admin' => 1,
        ]);
    }

    public function test_chart_of_accounts_page_renders(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/accounting/chart-of-accounts');
        $response->assertStatus(200);
    }

    public function test_journal_entries_page_renders(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/accounting/journal');
        $response->assertStatus(200);
    }

    public function test_general_ledger_page_renders(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/accounting/ledger');
        $response->assertStatus(200);
    }

    public function test_trial_balance_page_renders(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/accounting/trial-balance');
        $response->assertStatus(200);
    }

    public function test_customer_ledger_page_renders(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/accounting/customer-ledger');
        $response->assertStatus(200);
    }

    public function test_billing_invoices_page_renders(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/accounting/billings');
        $response->assertStatus(200);
    }

    public function test_billing_debit_notes_page_renders(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/accounting/billings/debit-notes');
        $response->assertStatus(200);
    }

    public function test_billing_credit_notes_page_renders(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/accounting/billings/credit-notes');
        $response->assertStatus(200);
    }

    public function test_payment_options_page_renders(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/payment-options');
        $response->assertStatus(200);
    }
}

<?php

namespace Modules\Rewards\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Rewards\Models\CustomerReward;
use Modules\Rewards\Models\LoyaltyTransaction;
use Modules\Rewards\Models\Reward;

class RewardController extends Controller
{
    /**
     * Display a listing of reward campaigns & KPIs.
     */
    public function index(Request $request)
    {
        $query = Reward::latest();

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $rewards = $query->paginate(15)->withQueryString();

        // High-level loyalty metrics
        $totalCampaigns = Reward::count();
        $activeCampaigns = Reward::active()->count();
        $totalMembers = CustomerReward::count();
        $totalPointsIssued = (int) LoyaltyTransaction::where('type', 'earned')->sum('points');
        $totalPointsRedeemed = abs((int) LoyaltyTransaction::where('type', 'redeemed')->sum('points'));

        return view('rewards::admin.index', compact(
            'rewards',
            'totalCampaigns',
            'activeCampaigns',
            'totalMembers',
            'totalPointsIssued',
            'totalPointsRedeemed'
        ));
    }

    /**
     * Show form to create new reward campaign.
     */
    public function create()
    {
        return view('rewards::admin.create');
    }

    /**
     * Store newly created reward campaign.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:rewards,code',
            'description' => 'nullable|string|max:500',
            'tier' => 'required|string|in:standard,bronze,silver,gold,vip',
            'earn_rate' => 'required|numeric|min:0.01|max:100',
            'redeem_rate' => 'required|numeric|min:0.0001|max:1',
            'min_points_to_redeem' => 'required|integer|min:1',
            'max_points_per_order' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Reward::create($validated);

        return redirect()->route('admin.rewards.index')
            ->with('success', 'Reward campaign created successfully!');
    }

    /**
     * Show edit form for campaign.
     */
    public function edit($id)
    {
        $reward = Reward::findOrFail($id);
        return view('rewards::admin.edit', compact('reward'));
    }

    /**
     * Update campaign.
     */
    public function update(Request $request, $id)
    {
        $reward = Reward::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => "required|string|max:50|unique:rewards,code,{$reward->id}",
            'description' => 'nullable|string|max:500',
            'tier' => 'required|string|in:standard,bronze,silver,gold,vip',
            'earn_rate' => 'required|numeric|min:0.01|max:100',
            'redeem_rate' => 'required|numeric|min:0.0001|max:1',
            'min_points_to_redeem' => 'required|integer|min:1',
            'max_points_per_order' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $reward->update($validated);

        return redirect()->route('admin.rewards.index')
            ->with('success', 'Reward campaign updated successfully!');
    }

    /**
     * Toggle active state.
     */
    public function toggle($id)
    {
        $reward = Reward::findOrFail($id);
        $reward->is_active = ! $reward->is_active;
        $reward->save();

        return response()->json([
            'success' => true,
            'is_active' => $reward->is_active,
            'message' => 'Status updated successfully.',
        ]);
    }

    /**
     * Delete campaign.
     */
    public function destroy($id)
    {
        $reward = Reward::findOrFail($id);
        $reward->delete();

        return redirect()->route('admin.rewards.index')
            ->with('success', 'Reward campaign deleted successfully!');
    }

    /**
     * View customer loyalty balances and tiers.
     */
    public function customers(Request $request)
    {
        $query = CustomerReward::with(['user'])->latest();

        if ($request->filled('tier')) {
            $query->where('tier', $request->tier);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('customer_email', 'like', "%{$search}%");
        }

        $customers = $query->paginate(20)->withQueryString();

        return view('rewards::admin.customers', compact('customers'));
    }
}

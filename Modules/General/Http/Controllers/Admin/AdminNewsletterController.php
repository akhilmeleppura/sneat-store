<?php

namespace Modules\General\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\General\Models\NewsletterSubscriber;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminNewsletterController extends Controller
{
    /**
     * Display all newsletter subscribers.
     */
    public function index(Request $request)
    {
        $query = NewsletterSubscriber::withoutTenancy()->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($request->expectsJson() && !$request->hasHeader('X-Inertia')) {
            return response()->json([
                'status' => 'success',
                'data'   => $query->paginate(20),
            ]);
        }

        $subscribers = $query->paginate(20)->withQueryString();

        $stats = [
            'total'        => NewsletterSubscriber::withoutTenancy()->count(),
            'subscribed'   => NewsletterSubscriber::withoutTenancy()->where('status', 'subscribed')->count(),
            'unsubscribed' => NewsletterSubscriber::withoutTenancy()->where('status', 'unsubscribed')->count(),
            'verified'     => NewsletterSubscriber::withoutTenancy()->whereNotNull('verified_at')->count(),
        ];

        return view('general::admin.newsletter.index', compact('subscribers', 'stats'));
    }

    /**
     * Toggle subscriber status.
     */
    public function toggle(int $id)
    {
        $subscriber = NewsletterSubscriber::withoutTenancy()->findOrFail($id);
        $subscriber->status = ($subscriber->status === 'subscribed') ? 'unsubscribed' : 'subscribed';
        $subscriber->save();

        return redirect()->back()
            ->with('success', "Subscriber {$subscriber->email} status updated to {$subscriber->status}.");
    }

    /**
     * Delete subscriber.
     */
    public function destroy(int $id)
    {
        $subscriber = NewsletterSubscriber::withoutTenancy()->findOrFail($id);
        $email = $subscriber->email;
        $subscriber->delete();

        return redirect()->back()
            ->with('success', "Subscriber {$email} removed successfully.");
    }

    /**
     * Export active subscribers to CSV.
     */
    public function exportCsv(): StreamedResponse
    {
        $fileName = 'newsletter_subscribers_' . date('Y_m_d_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Email', 'Status', 'Verified At', 'IP Address', 'Created At']);

            NewsletterSubscriber::withoutTenancy()
                ->where('status', 'subscribed')
                ->chunk(200, function ($subscribers) use ($handle) {
                    foreach ($subscribers as $sub) {
                        fputcsv($handle, [
                            $sub->id,
                            $sub->email,
                            $sub->status,
                            $sub->verified_at?->toDateTimeString() ?? 'N/A',
                            $sub->ip_address ?? 'N/A',
                            $sub->created_at->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}

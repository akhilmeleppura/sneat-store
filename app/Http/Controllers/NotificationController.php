<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get recent notifications and unread count for the authenticated user.
     */
    public function feed(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['unread_count' => 0, 'notifications' => []]);
        }

        $unreadCount = $user->unreadNotifications()->count();
        $notifications = $user->notifications()->take(10)->get()->map(function ($n) {
            return [
                'id'         => $n->id,
                'data'       => $n->data,
                'read_at'    => $n->read_at ? $n->read_at->diffForHumans() : null,
                'created_at' => $n->created_at->diffForHumans(),
                'is_unread'  => is_null($n->read_at),
            ];
        });

        return response()->json([
            'unread_count'  => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false], 401);
        }

        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json([
            'success'      => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark all notifications for the authenticated user as read.
     */
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'unread_count' => 0]);
        }

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Customer notification center page.
     */
    public function customerCenter(Request $request)
    {
        $user = Auth::user();
        $notifications = $user->notifications()->paginate(15);

        return view('notifications.center', [
            'notifications' => $notifications,
            'layout'        => 'layouts/layoutFront',
            'title'         => 'My Notifications',
        ]);
    }

    /**
     * Admin/Vendor notification center page.
     */
    public function adminCenter(Request $request)
    {
        $user = Auth::user();
        $notifications = $user->notifications()->paginate(20);

        return view('notifications.center', [
            'notifications' => $notifications,
            'layout'        => 'layouts/contentNavbarLayout',
            'title'         => 'System Notifications',
        ]);
    }
}

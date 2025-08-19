<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Notifications",
 *     description="API Endpoints for user notifications"
 * )
 */
class NotificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     summary="Get user notifications",
     *     tags={"Notifications"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of user notifications",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="New Content Available"),
     *                 @OA\Property(property="message", type="string", example="New video available"),
     *                 @OA\Property(property="type", type="string", example="general"),
     *                 @OA\Property(property="is_read", type="boolean", example=false),
     *                 @OA\Property(property="expires_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $userId = auth()->id();
        
        // Get user-specific notifications
        $userNotifications = Notification::query()
            ->where('user_id', $userId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();
            
        // Get global notifications that are not expired
        $globalNotifications = Notification::query()
            ->whereNull('user_id')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get()
            ->map(function ($notification) use ($userId) {
                // Check if user has a personalized version of this notification
                $userVersion = Notification::where('user_id', $userId)
                    ->where('title', $notification->title)
                    ->where('message', $notification->message)
                    ->where('type', $notification->type)
                    ->first();
                    
                // If user has already marked this as read, set is_read to true
                if ($userVersion && $userVersion->is_read) {
                    $notification->is_read = true;
                }
                
                return $notification;
            });
            
        // Combine and sort notifications by created_at in descending order
        $notifications = $userNotifications->concat($globalNotifications)
            ->sortByDesc('created_at')
            ->values();

        return response()->json($notifications);
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/unread-count",
     *     summary="Get unread notifications count",
     *     tags={"Notifications"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Number of unread notifications",
     *         @OA\JsonContent(
     *             @OA\Property(property="count", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function unreadCount(): JsonResponse
    {
        // Count user-specific unread notifications
        $userSpecificCount = Notification::query()
            ->where('user_id', auth()->id())
            ->where('is_read', false)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();
            
        // Count global notifications that haven't been marked as read by this user
        $userId = auth()->id();
        $globalCount = Notification::whereNull('user_id')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->whereNotExists(function ($query) use ($userId) {
                $query->select(\DB::raw(1))
                    ->from('notifications as n')
                    ->whereRaw('n.user_id = ? AND n.is_read = ? AND n.title = notifications.title AND n.message = notifications.message', [$userId, true]);
            })
            ->count();

        return response()->json(['count' => $userSpecificCount + $globalCount]);
    }

    /**
     * @OA\Post(
     *     path="/api/notifications/{id}/mark-as-read",
     *     summary="Mark notification as read",
     *     tags={"Notifications"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the notification",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification marked as read",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Notification marked as read")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found"
     *     )
     * )
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        // Check if the notification is for the authenticated user or for all users (user_id is null)
        if ($notification->user_id !== null && $notification->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Mark as read only for the current user by creating a user-specific read status
        // if it's a global notification (user_id is null)
        if ($notification->user_id === null) {
            // Create a copy of this notification specifically for this user
            // with is_read set to true
            $userNotification = Notification::firstOrCreate(
                [
                    'user_id' => auth()->id(),
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'expires_at' => $notification->expires_at
                ],
                ['is_read' => true]
            );
            
            if (!$userNotification->is_read) {
                $userNotification->update(['is_read' => true]);
            }
            
            return response()->json(['message' => 'Notification marked as read']);
        }
        
        // If it's a user-specific notification, simply mark it as read
        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notification marked as read']);
    }

    /**
     * @OA\Post(
     *     path="/api/notifications/mark-all-as-read",
     *     summary="Mark all notifications as read",
     *     tags={"Notifications"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="All notifications marked as read",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="All notifications marked as read")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function markAllAsRead(): JsonResponse
    {
        // Mark user-specific notifications as read
        auth()->user()->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);
            
        // Find global notifications (where user_id is null) that are not expired
        $globalNotifications = Notification::whereNull('user_id')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();
            
        // For each global notification, create or update a user-specific copy
        foreach ($globalNotifications as $notification) {
            Notification::firstOrCreate(
                [
                    'user_id' => auth()->id(),
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'expires_at' => $notification->expires_at
                ],
                ['is_read' => true]
            );
        }

        return response()->json(['message' => 'All notifications marked as read']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\Like;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Likes",
 *     description="API Endpoints for video likes management"
 * )
 */
class LikeController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/videos/{id}/toggle-like",
     *     summary="Toggle like status for a video",
     *     tags={"Likes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the video",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Like status toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="liked", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Video not found"
     *     )
     * )
     */
    public function toggle(Video $video): JsonResponse
    {
        $user = auth()->user();
        $like = $video->likes()->where('user_id', $user->id)->first();

        if ($like) {
            $like->delete();
            $video->decrement('likes_count');
            return response()->json(['liked' => false]);
        }

        $video->likes()->create(['user_id' => $user->id]);
        $video->increment('likes_count');
        return response()->json(['liked' => true]);
    }

    /**
     * @OA\Get(
     *     path="/api/videos/{id}/like-status",
     *     summary="Get like status for a video",
     *     tags={"Likes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the video",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Like status retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="liked", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Video not found"
     *     )
     * )
     */
    public function status(Video $video): JsonResponse
    {
        $isLiked = $video->likes()
            ->where('user_id', auth()->id())
            ->exists();

        return response()->json(['liked' => $isLiked]);
    }
}

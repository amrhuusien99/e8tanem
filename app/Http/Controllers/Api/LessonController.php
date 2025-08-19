<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Lessons",
 *     description="API Endpoints for viewing lessons"
 * )
 */
class LessonController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/lessons/{lesson}",
     *     summary="Get a specific lesson",
     *     tags={"Lessons"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="lesson",
     *         in="path",
     *         required=true,
     *         description="ID of lesson",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lesson details",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="subject_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Introduction to Calculus"),
     *             @OA\Property(property="description", type="string", example="Learn the basics of calculus"),
     *             @OA\Property(property="video_url", type="string"),
     *             @OA\Property(property="thumbnail_url", type="string", nullable=true),
     *             @OA\Property(property="duration", type="integer", example=3600),
     *             @OA\Property(property="order", type="integer", example=1),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="views_count", type="integer", example=100),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(
     *                 property="subject",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Mathematics"),
     *                 @OA\Property(property="description", type="string", example="Learn advanced mathematics"),
     *                 @OA\Property(property="thumbnail", type="string", nullable=true),
     *                 @OA\Property(property="is_active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lesson not found or inactive"
     *     )
     * )
     */
    public function show(Lesson $lesson): JsonResponse
    {
        if (!$lesson->is_active || !$lesson->subject->is_active) {
            return response()->json(['message' => 'Lesson not found'], 404);
        }

        $lesson->load('subject:id,name');
        $lesson->incrementViews();

        return response()->json([
            'id' => $lesson->id,
            'title' => $lesson->title,
            'description' => $lesson->description,
            'video_url' => $lesson->video_url,
            'thumbnail_url' => $lesson->thumbnail_url,
            'duration' => $lesson->duration,
            'views_count' => $lesson->views_count,
            'order' => $lesson->order,
            'subject' => $lesson->subject,
            'created_at' => $lesson->created_at,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/lessons/{lesson}/stream",
     *     summary="Stream a specific lesson video",
     *     description="Stream the lesson video content with support for range requests and caching for mobile optimization",
     *     tags={"Lessons"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="lesson",
     *         in="path",
     *         required=true,
     *         description="ID of the lesson to stream",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="Range",
     *         in="header",
     *         required=false,
     *         description="Range header for partial content requests (e.g., bytes=0-1000)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Full video content stream",
     *         @OA\Header(
     *             header="Content-Type",
     *             description="video/mp4",
     *             @OA\Schema(type="string")
     *         ),
     *         @OA\Header(
     *             header="Accept-Ranges",
     *             description="bytes",
     *             @OA\Schema(type="string")
     *         ),
     *         @OA\Header(
     *             header="Content-Length",
     *             description="Size of the video in bytes",
     *             @OA\Schema(type="integer")
     *         ),
     *         @OA\Header(
     *             header="Cache-Control",
     *             description="public, max-age=86400",
     *             @OA\Schema(type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=206,
     *         description="Partial video content (for range requests)",
     *         @OA\Header(
     *             header="Content-Type",
     *             description="video/mp4",
     *             @OA\Schema(type="string")
     *         ),
     *         @OA\Header(
     *             header="Content-Range",
     *             description="bytes start-end/size",
     *             @OA\Schema(type="string")
     *         ),
     *         @OA\Header(
     *             header="Content-Length",
     *             description="Size of the requested range in bytes",
     *             @OA\Schema(type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lesson not found, inactive, or video file not available",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundError")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     */
    public function stream(Lesson $lesson): StreamedResponse|JsonResponse
    {
        if (!$lesson->is_active || !$lesson->subject->is_active) {
            return response()->json(['message' => 'Lesson not found'], 404);
        }

        $path = storage_path('app/public/' . $lesson->video_url);
        
        if (!file_exists($path)) {
            return response()->json(['message' => 'Video file not found'], 404);
        }

        $size = filesize($path);
        $start = 0;
        $end = $size - 1;

        // Cache control headers for mobile optimization
        $headers = [
            'Content-Type' => 'video/mp4',
            'Accept-Ranges' => 'bytes',
            'Content-Length' => $size,
            'Cache-Control' => 'public, max-age=86400', // 24-hour cache
            'Content-Disposition' => 'inline',
        ];

        // Handle range request
        if (request()->header('Range')) {
            $range = str_replace('bytes=', '', request()->header('Range'));
            [$start, $end] = explode('-', $range);
            $end = $end ?: $size - 1;
            $length = $end - $start + 1;
            
            $headers['Content-Length'] = $length;
            $headers['Content-Range'] = "bytes $start-$end/$size";
            
            return response()->stream(
                function () use ($path, $start, $length) {
                    $handle = fopen($path, 'rb');
                    fseek($handle, $start);
                    echo fread($handle, $length);
                    fclose($handle);
                },
                206,
                $headers
            );
        }

        return response()->stream(
            function () use ($path) {
                $handle = fopen($path, 'rb');
                while (!feof($handle)) {
                    echo fread($handle, 8192); // Stream in 8KB chunks
                    flush();
                }
                fclose($handle);
            },
            200,
            $headers
        );
    }
}

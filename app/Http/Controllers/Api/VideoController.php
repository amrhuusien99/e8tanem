<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @OA\Tag(
 *     name="Videos",
 *     description="API Endpoints for video management"
 * )
 */
class VideoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/videos",
     *     summary="List all active videos",
     *     description="Returns a paginated list of active videos with pagination metadata",
     *     tags={"Videos"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination (starts from 1)",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page (default: 20)",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term to filter videos by title or description",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Field to sort by (created_at, views_count, likes_count, comments_count)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"created_at", "views_count", "likes_count", "comments_count"}, default="created_at")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Sort order (asc or desc)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of active videos with pagination metadata",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="data", type="array", 
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Sample Video"),
     *                     @OA\Property(property="description", type="string", example="Video description"),
     *                     @OA\Property(property="video_url", type="string", example="/storage/videos/sample-video.mp4"),
     *                     @OA\Property(property="thumbnail_url", type="string", example="/storage/thumbnails/sample-thumbnail.jpg"),
     *                     @OA\Property(property="views_count", type="integer", example=100),
     *                     @OA\Property(property="likes_count", type="integer", example=50),
     *                     @OA\Property(property="comments_count", type="integer", example=25),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="John Doe")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="first_page_url", type="string", example="http://e8tanem.com/api/videos?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="last_page_url", type="string", example="http://e8tanem.com/api/videos?page=5"),
     *             @OA\Property(
     *                 property="links",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="url", type="string", nullable=true),
     *                     @OA\Property(property="label", type="string", example="&laquo; Previous"),
     *                     @OA\Property(property="active", type="boolean")
     *                 )
     *             ),
     *             @OA\Property(property="next_page_url", type="string", example="http://e8tanem.com/api/videos?page=2", nullable=true),
     *             @OA\Property(property="path", type="string", example="http://e8tanem.com/api/videos"),
     *             @OA\Property(property="per_page", type="integer", example=20),
     *             @OA\Property(property="prev_page_url", type="string", example="http://e8tanem.com/api/videos?page=1", nullable=true),
     *             @OA\Property(property="to", type="integer", example=20),
     *             @OA\Property(property="total", type="integer", example=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $videos = Video::where('is_active', true)
            ->with('user:id,name')
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate(20);

        return response()->json($videos);
    }

    /**
     * @OA\Get(
     *     path="/api/videos/{id}",
     *     summary="Get video details",
     *     tags={"Videos"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the video",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Video details",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Sample Video"),
     *             @OA\Property(property="description", type="string", example="Video description"),
     *             @OA\Property(property="url", type="string", example="https://example.com/video.mp4"),
     *             @OA\Property(property="views_count", type="integer", example=100),
     *             @OA\Property(property="likes_count", type="integer", example=50),
     *             @OA\Property(property="comments_count", type="integer", example=25),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Video not found"
     *     )
     * )
     */
    public function show(Video $video): JsonResponse
    {
        if (!$video->is_active) {
            return response()->json(['message' => 'Video not found'], 404);
        }

        $video->load('user:id,name');
        $video->loadCount(['likes', 'comments']);
        $video->increment('views_count');

        return response()->json($video);
    }

    /**
     * @OA\Get(
     *     path="/api/videos/{id}/stream",
     *     summary="Stream video content",
     *     description="Stream the video content with support for range requests and caching for mobile optimization",
     *     tags={"Videos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the video to stream",
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
     *         description="Video not found or file not available",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundError")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     */
    public function streamVideo(Video $video): StreamedResponse
    {
        if (!$video->is_active) {
            abort(404);
        }

        $path = storage_path('app/public/videos/' . basename($video->video_url));

        if (!file_exists($path)) {
            abort(404);
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

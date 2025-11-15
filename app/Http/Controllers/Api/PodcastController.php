<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Podcast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PodcastController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/podcasts",
     *     summary="List all podcasts",
     *     description="Returns a paginated list of active podcasts with pagination metadata",
     *     tags={"Podcasts"},
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
     *         description="Number of items per page (default: 10)",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term to filter podcasts by title or description",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Field to sort by (created_at, plays_count)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"created_at", "plays_count"}, default="created_at")
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
     *         description="List of podcasts with pagination metadata",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="data", type="array", 
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Sample Podcast"),
     *                     @OA\Property(property="description", type="string", example="Podcast description"),
     *                     @OA\Property(property="audio_url", type="string", example="/storage/podcasts/sample-podcast.mp3"),
     *                     @OA\Property(property="thumbnail_url", type="string", example="/storage/thumbnails/podcast-thumbnail.jpg"),
     *                     @OA\Property(property="category", type="string", example="Technology"),
     *                     @OA\Property(property="plays_count", type="integer", example=50),
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
     *             @OA\Property(property="first_page_url", type="string", example="http://e8tanem.com/api/podcasts?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="last_page_url", type="string", example="http://e8tanem.com/api/podcasts?page=5"),
     *             @OA\Property(
     *                 property="links",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="url", type="string", nullable=true),
     *                     @OA\Property(property="label", type="string", example="&laquo; Previous"),
     *                     @OA\Property(property="active", type="boolean")
     *                 )
     *             ),
     *             @OA\Property(property="next_page_url", type="string", example="http://e8tanem.com/api/podcasts?page=2", nullable=true),
     *             @OA\Property(property="path", type="string", example="http://e8tanem.com/api/podcasts"),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="prev_page_url", type="string", example="http://e8tanem.com/api/podcasts?page=1", nullable=true),
     *             @OA\Property(property="to", type="integer", example=10),
     *             @OA\Property(property="total", type="integer", example=50)
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Podcast::query()->with('user:id,name')->where('is_active', true);

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $podcasts = $query->latest()->paginate(10);

        return response()->json($podcasts);
    }

    /**
     * @OA\Get(
     *     path="/api/podcasts/{podcast}",
     *     summary="Get a specific podcast",
     *     tags={"Podcasts"},
     *     @OA\Parameter(
     *         name="podcast",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Podcast details",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="audio_url", type="string"),
     *             @OA\Property(property="thumbnail_url", type="string"),
     *             @OA\Property(property="category", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="plays_count", type="integer"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Podcast not found")
     * )
     */
    public function show(Podcast $podcast): JsonResponse
    {
        // Load the user relationship
        $podcast->load('user:id,name');

        $relativeAudioPath = ltrim($podcast->audio_url, '/');
        $audioFullPath = storage_path('app/public/' . $relativeAudioPath);

        $podcast->audio_url = Storage::url($relativeAudioPath);
        $podcast->audio_mime = file_exists($audioFullPath) ? mime_content_type($audioFullPath) : 'audio/mpeg';
        $podcast->audio_size = file_exists($audioFullPath) ? filesize($audioFullPath) : null;

        if ($podcast->thumbnail_url) {
            $podcast->thumbnail_url = Storage::url($podcast->thumbnail_url);
        }

        return response()->json($podcast);
    }

    /**
     * @OA\Post(
     *     path="/api/podcasts/{podcast}/play",
     *     summary="Increment play count for a podcast",
     *     tags={"Podcasts"},
     *     @OA\Parameter(
     *         name="podcast",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Play count incremented",
     *         @OA\JsonContent(
     *             @OA\Property(property="plays_count", type="integer")
     *         )
     *     )
     * )
     */
    public function incrementPlays(Podcast $podcast): JsonResponse
    {
        $podcast->increment('plays_count');
        return response()->json(['plays_count' => $podcast->plays_count]);
    }

    /**
     * @OA\Get(
     *     path="/api/podcasts/{podcast}/stream",
     *     summary="Get podcast audio file URL",
     *     description="Returns the direct URL to the podcast audio file for regular playback",
     *     tags={"Podcasts"},
     *     @OA\Parameter(
     *         name="podcast",
     *         in="path",
     *         required=true,
     *         description="ID of the podcast to get audio URL for",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Audio file URL",
     *         @OA\JsonContent(
     *             @OA\Property(property="audio_url", type="string", example="https://example.com/storage/podcasts/sample.mp3"),
     *             @OA\Property(property="title", type="string", example="Podcast Title"),
     *             @OA\Property(property="podcast_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Podcast not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundError")
     *     )
     * )
     */
    public function stream(Podcast $podcast): StreamedResponse|JsonResponse
    {
        if (!$podcast->is_active) {
            return response()->json(['message' => 'Podcast not found'], 404);
        }

        $relativePath = ltrim($podcast->audio_url, '/');
        $fullPath = storage_path('app/public/' . $relativePath);

        if (!file_exists($fullPath)) {
            return response()->json(['message' => 'Audio file not found'], 404);
        }

        $size = filesize($fullPath);
        $start = 0;
        $end = $size - 1;
        $mimeType = mime_content_type($fullPath) ?: 'audio/mpeg';
        $fileName = basename($relativePath);

        $headers = [
            'Content-Type' => $mimeType,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=86400',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ];

        if ($range = request()->header('Range')) {
            $range = str_replace('bytes=', '', $range);
            [$start, $rangeEnd] = array_pad(explode('-', $range), 2, null);
            $start = (int) $start;
            $end = $rangeEnd !== null ? (int) $rangeEnd : $end;
            $end = min($end, $size - 1);
            $length = $end - $start + 1;

            $headers['Content-Length'] = $length;
            $headers['Content-Range'] = "bytes $start-$end/$size";

            return response()->stream(function () use ($fullPath, $start, $length) {
                $handle = fopen($fullPath, 'rb');
                fseek($handle, $start);
                echo fread($handle, $length);
                fclose($handle);
            }, 206, $headers);
        }

        $headers['Content-Length'] = $size;

        return response()->stream(function () use ($fullPath) {
            $handle = fopen($fullPath, 'rb');
            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * @OA\Get(
     *     path="/api/podcasts/{podcast}/download",
     *     summary="Download podcast audio file",
     *     tags={"Podcasts"},
     *     @OA\Parameter(
     *         name="podcast",
     *         in="path",
     *         required=true,
     *         description="ID of the podcast to download",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File download response",
     *         @OA\Header(header="Content-Disposition", description="attachment; filename=...", @OA\Schema(type="string"))
     *     ),
     *     @OA\Response(response=404, description="Podcast not found or file missing")
     * )
     */
    public function download(Podcast $podcast)
    {
        if (!$podcast->is_active) {
            return response()->json(['message' => 'Podcast not found'], 404);
        }

        $path = $podcast->audio_url;
        if (!$path || !Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'Audio file not found'], 404);
        }

        $fullPath = Storage::disk('public')->path($path);
        $fileName = basename($path);
        $mimeType = mime_content_type($fullPath) ?: 'audio/mpeg';

        return response()->download($fullPath, $fileName, [
            'Content-Type' => $mimeType,
        ]);
    }
}
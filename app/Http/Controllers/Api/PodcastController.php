<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Podcast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        
        // Create the full URL to the audio file
        $podcast->audio_url = url('storage/' . $podcast->audio_url);
        
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
    public function stream(Podcast $podcast): JsonResponse
    {
        if (!$podcast->is_active) {
            return response()->json(['message' => 'Podcast not found'], 404);
        }
        
        // Check if file exists
        $relativePath = $podcast->audio_url;
        $fullPath = storage_path('app/public/' . $relativePath);
        
        if (!file_exists($fullPath)) {
            return response()->json(['message' => 'Audio file not found'], 404);
        }
        
        // Create a full URL to the audio file in storage
        $audioUrl = url('storage/' . $relativePath);
        
        return response()->json([
            'audio_url' => $audioUrl,
            'title' => $podcast->title,
            'podcast_id' => $podcast->id
        ])->header('Access-Control-Allow-Origin', '*')
          ->header('Access-Control-Allow-Methods', 'GET')
          ->header('Content-Type', 'application/json')
          ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
}
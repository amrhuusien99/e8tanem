<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Subjects",
 *     description="API Endpoints for subjects and their lessons"
 * )
 */
class SubjectController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/subjects",
     *     summary="Get all active subjects",
     *     tags={"Subjects"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of active subjects",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Mathematics"),
     *                 @OA\Property(property="description", type="string", example="Learn advanced mathematics"),
     *                 @OA\Property(property="thumbnail", type="string", nullable=true),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="lessons_count", type="integer", example=5),
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
        $subjects = Subject::query()
            ->active()
            ->withCount('lessons')
            ->get();

        return response()->json($subjects);
    }

    /**
     * @OA\Get(
     *     path="/api/subjects/{subject}",
     *     summary="Get a subject and its lessons",
     *     tags={"Subjects"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="subject",
     *         in="path",
     *         required=true,
     *         description="ID of subject",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subject details with lessons",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Mathematics"),
     *             @OA\Property(property="description", type="string", example="Learn advanced mathematics"),
     *             @OA\Property(property="thumbnail", type="string", nullable=true),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(
     *                 property="lessons",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Introduction to Calculus"),
     *                     @OA\Property(property="description", type="string", example="Learn the basics of calculus"),
     *                     @OA\Property(property="video_path", type="string"),
     *                     @OA\Property(property="thumbnail", type="string", nullable=true),
     *                     @OA\Property(property="duration", type="integer", example=3600),
     *                     @OA\Property(property="order", type="integer", example=1),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="views_count", type="integer", example=100),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Subject not found"
     *     )
     * )
     */
    public function show(Subject $subject): JsonResponse
    {
        if (!$subject->is_active) {
            return response()->json(['message' => 'Subject not found'], 404);
        }

        $subject->load(['lessons' => function($query) {
            $query->active()->orderBy('order');
        }]);

        return response()->json($subject);
    }
}

# Stop re-showing already-seen videos in the feed, with auto-reset on exhaustion

## Context

The main video feed endpoint (`GET /videos` → `VideoController::index()`) used to show the
same videos every time a user opened the app. Root cause: `Video::scopeFeedRanked()` derives its
ordering from a **deterministic seed** (`user-{id}`), so the "personalized" ranking is stable
across requests — page 1 was always the same set of videos for a given user. There was no
per-user "have I seen this" concept anywhere in the codebase (only a global
`videos.views_count` counter and a `likes` pivot table existed).

Goal: once a user has actually watched a video, it should stop appearing in their feed. When
they've worked through every active video in the system, the feed resets and starts showing
videos again from scratch (full cycle repeat), rather than showing an empty feed forever.

Two design decisions were confirmed with the product owner before implementation:
- A video counts as "seen" only when the client actually requests to **play** it (hits
  `show()` or `streamVideo()`), not merely when it's returned in a feed page. This avoids
  marking scrolled-past-but-unwatched videos as seen.
- The seen-exclusion filter applies to the main feed listing in all its non-search modes
  (default personalized `feedRanked`, `mode=chronological`, explicit `sort=`), but is skipped
  whenever a `search` term is present, so users can always find/rewatch a specific video by
  searching for it.

## Schema: `video_views` table / `VideoView` model

New table mirroring the existing `likes` pivot pattern (see `app/Models/Like.php` and
`database/migrations/2025_04_24_095946_create_likes_table.php`):

- Migration `database/migrations/2026_07_02_000000_create_video_views_table.php`:
  `id`, `user_id` (FK, cascade), `video_id` (FK, cascade), unique(`user_id`,`video_id`),
  timestamps.
- Model `app/Models/VideoView.php`: `fillable = ['user_id', 'video_id']`, `user()`/`video()`
  `belongsTo` relations — same shape as `Like`.
- Factory `database/factories/VideoViewFactory.php`, mirroring
  `database/factories/LikeFactory.php` (swap `Like` → `VideoView`).

Purely additive — no backfill needed, since no "seen" concept existed before; an empty table
is the correct starting state for every user.

## Marking a video as seen

Both call sites are behind `auth:sanctum`, so `$request->user()` is always present.

Helper on `VideoController`:

```php
private function markVideoAsSeen(int $userId, int $videoId): void
{
    VideoView::query()->insertOrIgnore([
        'user_id' => $userId,
        'video_id' => $videoId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
```

`insertOrIgnore` relies on the DB unique constraint, compiles portably to `INSERT IGNORE`
(MySQL) / `INSERT OR IGNORE` (SQLite, used in tests) — no read-before-write race, and cheap
no-op on repeat calls.

Called in:
- `VideoController::show()` — alongside the existing `views_count` increment.
- `VideoController::streamVideo()` — right after the `is_active`/file-exists checks. Since
  range requests call this method repeatedly per playback, `insertOrIgnore` keeps this a cheap
  no-op after the first hit.

## Filtering the feed in `index()`

Right after the existing search-filter block and before the `mode`/`sort`/`feedRanked`
branching:

```php
$userId = optional($request->user())->id;
$excludeSeen = $userId && !$search;

if ($excludeSeen) {
    $query->whereNotIn('id', function ($sub) use ($userId) {
        $sub->select('video_id')->from('video_views')->where('user_id', $userId);
    });
}
```

This compiles to a single correlated `NOT IN` subquery — no ID list pulled into PHP, portable
across MySQL/SQLite, composes fine with `feedRanked()`'s `addSelect`/`orderByDesc`.

After `$videos = $query->paginate($perPage);`, exhaustion detection and reset:

```php
if ($excludeSeen && $videos->total() === 0 && Video::query()->where('is_active', true)->exists()) {
    VideoView::query()->where('user_id', $userId)->delete();
    $videos = $query->paginate($perPage);
}
```

- `$videos->total()` is already computed by `paginate()`'s count query — no extra query for
  the zero-check.
- The `exists()` check distinguishes "system genuinely has zero active videos" (leave the empty
  page as-is) from "user has seen everything active" (reset and re-run).
- Delete is scoped to `user_id` only — a full reset. Re-running `paginate()` on the same
  builder is safe; the `whereNotIn` subquery now matches nothing, so all active videos come
  back.
- No changes needed to the existing `is_liked_by_viewer`/`engagement_overview`/`last_comment`
  decoration logic — it operates on whatever collection `$videos` ends up holding.

## Testing

Added to `tests/Feature/Api/VideoControllerTest.php` (matches existing conventions:
`RefreshDatabase`, factories, `actingAs`, `getJson`):

1. Watching a video (via `show()`) removes it from subsequent `GET /videos` responses.
2. Repeated `streamVideo()` calls (simulating range requests) produce only one `video_views`
   row — proves `insertOrIgnore` avoids duplicate-key errors.
3. `GET /videos?search=...` still returns a video the user has already seen.
4. Once all active videos are marked seen, the next `GET /videos` call returns a full page
   again (not empty), and `video_views` for that user has been cleared — confirms the
   auto-reset actually fires.
5. One user marking a video seen does not affect another user's feed (no cross-user leakage).

## Bonus fix (unrelated pre-existing bug, discovered while verifying)

`Video::scopeFeedRanked()` used `LEAST()` unconditionally in its baseline-reach expression.
`LEAST()` is a MySQL-only function and doesn't exist in SQLite (the project's local/dev
database per `RUN.md`), so the entire default feed 500'd whenever running on SQLite — this
was blocking verification of the feature above. Fixed by branching to SQLite's multi-arg
`MIN()` for that expression, matching the existing driver-branch pattern already used two
lines above it in the same method (`app/Models/Video.php`).

## Verification performed

- `php artisan test --filter=VideoControllerTest` — all new tests pass; all pre-existing tests
  in this file pass except `feed_ranking_prioritizes_engagement_but_respects_chronology_mode`,
  confirmed via `git stash` to already be broken on `main` for an unrelated reason (a ranking
  weight/formula issue, not something touched by this change).
- `php artisan test` (full suite) — `AuthControllerTest`/`IpValidationTest` failures also
  confirmed pre-existing on `main` via `git stash`, unrelated to this work.
- Manually drove the real flow against the running app (Docker + seeded `test@example.com`
  user): fetched the feed, watched videos one by one via `GET /videos/{id}`, confirmed each
  disappeared from subsequent feed calls, and confirmed the feed automatically repopulated
  once all videos were watched.

## Files touched

**New:**
- `database/migrations/2026_07_02_000000_create_video_views_table.php`
- `app/Models/VideoView.php`
- `database/factories/VideoViewFactory.php`

**Modified:**
- `app/Http/Controllers/Api/VideoController.php`
- `app/Models/Video.php` (bonus `LEAST`/`MIN` fix)
- `tests/Feature/Api/VideoControllerTest.php`

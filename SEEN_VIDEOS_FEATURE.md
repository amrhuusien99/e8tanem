# Stop re-showing already-seen videos first in the feed, with auto-reset on exhaustion

## Context

The main video feed endpoint (`GET /videos` → `VideoController::index()`) used to show the
same videos every time a user opened the app. Root cause: `Video::scopeFeedRanked()` derives its
ordering from a **deterministic seed** (`user-{id}`), so the "personalized" ranking is stable
across requests — page 1 was always the same set of videos for a given user. There was no
per-user "have I seen this" concept anywhere in the codebase (only a global
`videos.views_count` counter and a `likes` pivot table existed).

Goal: once a user has actually watched a video, it should stop showing up at the top of their
feed. When they've worked through every active video in the system, the feed resets so
everything is treated as fresh again (full cycle repeat), rather than the feed staying
permanently stuck in "everything already seen" order.

**Design note — revised after initial implementation:** the first version of this feature did
a *hard exclusion* (`whereNotIn`), which shrank the returned page size as videos got watched
(e.g. 19 videos → 17 → 2) and forced a reset the moment the unseen pool hit zero. Real
usage through the mobile app surfaced that this breaks normal pagination UX — a feed page
should always return a full `per_page` batch, not a dwindling one. The confirmed final
behavior is a **soft reorder** instead: always return the full matching set (nothing is ever
hidden), but sort unseen videos first and already-seen videos last. Once literally everything
has been seen, the seen history resets so the next fetch naturally starts prioritizing
"unseen" again (which, right after a reset, is everything).

Two design decisions confirmed with the product owner:
- A video counts as "seen" only when the client actually requests to **play** it (hits
  `show()` or `streamVideo()`), not merely when it's returned in a feed page. This avoids
  marking scrolled-past-but-unwatched videos as seen.
- The seen-priority reordering applies to the main feed listing in all its non-search modes
  (default personalized `feedRanked`, `mode=chronological`, explicit `sort=`), but is skipped
  whenever a `search` term is present, so search results/order aren't disturbed by watch
  history.

## Schema: `video_views` table / `VideoView` model

Table mirroring the existing `likes` pivot pattern (see `app/Models/Like.php` and
`database/migrations/2025_04_24_095946_create_likes_table.php`):

- Migration `database/migrations/2026_07_02_000000_create_video_views_table.php`:
  `id`, `user_id` (FK, cascade), `video_id` (FK, cascade), unique(`user_id`,`video_id`),
  timestamps.
- Model `app/Models/VideoView.php`: `fillable = ['user_id', 'video_id']`, `user()`/`video()`
  `belongsTo` relations — same shape as `Like`.
- Factory `database/factories/VideoViewFactory.php`, mirroring
  `database/factories/LikeFactory.php` (swap `Like` → `VideoView`).

Purely additive — no backfill needed.

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

## Reordering the feed in `index()` (current, final behavior)

Right after the existing search-filter block and before the `mode`/`sort`/`feedRanked`
branching:

```php
$userId = optional($request->user())->id;
$prioritizeUnseen = $userId && !$search;

if ($prioritizeUnseen) {
    $hasUnseen = (clone $query)->whereNotIn('id', function ($sub) use ($userId) {
        $sub->select('video_id')->from('video_views')->where('user_id', $userId);
    })->exists();

    if (!$hasUnseen && (clone $query)->exists()) {
        VideoView::query()->where('user_id', $userId)->delete();
    }

    $query->selectRaw(
        'EXISTS (SELECT 1 FROM video_views WHERE video_views.video_id = videos.id AND video_views.user_id = ?) as has_seen',
        [$userId]
    )->orderBy('has_seen');
}
```

- `EXISTS(...)` returns `0`/`1` portably on both MySQL and SQLite (no `LEAST`/`GREATEST`-style
  dialect issue here).
- `orderBy('has_seen')` is added **before** the `mode`/`sort`/`feedRanked` branch further down,
  so it becomes the *primary* sort key — Laravel appends subsequent `orderBy`/`orderByDesc`
  calls (chronological, explicit sort, or `feedRanked`'s `ranking_score`) as secondary
  tie-breakers within each `has_seen` group.
- Exhaustion is detected **upfront**, before the final query/pagination runs: `(clone
  $query)->whereNotIn(...)->exists()` checks whether any unseen video remains under the
  current filters; `(clone $query)->exists()` guards against resetting when the system
  genuinely has zero matching videos (as opposed to the user having watched all of them).
  `clone` is safe here — Eloquent's `Builder::__clone()` deep-clones the underlying query
  builder, so branching off `$query` without mutating the original is the standard Laravel
  pattern.
- If exhausted, `video_views` for that user is deleted **before** the `has_seen` select/order
  is attached — so the very same response already reflects the fresh state (no second
  query/pagination pass needed, unlike the earlier hard-exclusion version).
- `paginate($perPage)` always returns a full page — nothing is filtered out, so page size
  never shrinks.

The response payload also exposes `is_seen_by_viewer` (boolean, `null` when the reorder wasn't
applied, e.g. search) next to the existing `is_liked_by_viewer`, so clients can show a
"watched" indicator if desired.

## Testing

`tests/Feature/Api/VideoControllerTest.php` (matches existing conventions: `RefreshDatabase`,
factories, `actingAs`, `getJson`):

1. `test_watched_video_is_moved_to_the_end_but_still_returned` — watching a newer video still
   returns both videos (count unchanged), with the watched one pushed after the unwatched one
   despite chronological order normally putting it first.
2. `test_streaming_video_marks_it_seen_and_is_idempotent_across_range_requests` — repeated
   `streamVideo()` calls (simulating range requests) produce only one `video_views` row.
3. `test_search_ignores_seen_status_ordering` — `search` bypasses the reorder entirely; normal
   chronological order is preserved even when one result has been seen.
4. `test_seen_history_resets_once_all_active_videos_have_been_seen` — once every active video
   is marked seen, the next fetch clears `video_views` for that user and the response already
   reflects `is_seen_by_viewer: false` for everything.
5. `test_seen_priority_is_scoped_per_user` — one user's watch history doesn't affect another
   user's feed ordering or `is_seen_by_viewer` flags.

## Bonus fix (unrelated pre-existing bug, discovered while verifying)

`Video::scopeFeedRanked()` used `LEAST()` unconditionally in its baseline-reach expression.
`LEAST()` is a MySQL-only function and doesn't exist in SQLite (the project's local/dev
database per `RUN.md`), so the entire default feed 500'd whenever running on SQLite. Fixed by
branching to SQLite's multi-arg `MIN()` for that expression, matching the existing
driver-branch pattern already used two lines above it in the same method (`app/Models/Video.php`).

## Verification performed

- `php artisan test --filter=VideoControllerTest` — all tests for this feature pass; the one
  pre-existing failure (`feed_ranking_prioritizes_engagement_but_respects_chronology_mode`) was
  confirmed via `git stash` to already be broken on `main` for an unrelated ranking-weight
  reason, not touched by this work.
- `php artisan test` (full suite) — `AuthControllerTest`/`IpValidationTest` failures also
  confirmed pre-existing on `main` via `git stash`.
- Manually drove the real flow against the running app (Docker + seeded `test@example.com`
  user, 4 active videos): confirmed the feed always returns all 4 videos regardless of watch
  state; confirmed a watched video moves to the back with `is_seen_by_viewer: true`; confirmed
  that once all 4 were watched, the very next fetch auto-reset and returned all 4 with
  `is_seen_by_viewer: false` again, in normal chronological order.

## Files touched

**New:**
- `database/migrations/2026_07_02_000000_create_video_views_table.php`
- `app/Models/VideoView.php`
- `database/factories/VideoViewFactory.php`

**Modified:**
- `app/Http/Controllers/Api/VideoController.php`
- `app/Models/Video.php` (bonus `LEAST`/`MIN` fix)
- `tests/Feature/Api/VideoControllerTest.php`

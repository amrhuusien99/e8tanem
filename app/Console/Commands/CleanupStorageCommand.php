<?php

namespace App\Console\Commands;

use App\Models\Podcast;
use App\Models\Video;
use App\Models\Lesson;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CleanupStorageCommand extends Command
{
    protected $signature = 'storage:cleanup {--force} {--type=all}';
    protected $description = 'Clean up orphaned files in storage that no longer have database records';

    public function handle()
    {
        $type = $this->option('type');
        $force = $this->option('force');

        if (!$force) {
            $this->warn('This will permanently delete orphaned files from storage.');
            if (!$this->confirm('Do you wish to continue?')) {
                return Command::SUCCESS;
            }
        }

        if ($type === 'all' || $type === 'podcasts') {
            $this->cleanupPodcasts();
        }

        if ($type === 'all' || $type === 'videos') {
            $this->cleanupVideos();
        }

        if ($type === 'all' || $type === 'lessons') {
            $this->cleanupLessons();
        }

        $this->info('Storage cleanup completed.');
        return Command::SUCCESS;
    }

    protected function cleanupPodcasts()
    {
        $this->info('Cleaning up podcast files...');
        
        // Get all podcast files in storage
        $podcastFiles = Storage::disk('public')->files('podcasts');
        $thumbnailFiles = Storage::disk('public')->files('podcast-thumbnails');
        
        // Get all valid podcast files from database
        $validAudioUrls = Podcast::pluck('audio_url')->filter()->all();
        $validThumbnailUrls = Podcast::pluck('thumbnail_url')->filter()->all();
        
        // Clean audio files
        $deletedCount = 0;
        foreach ($podcastFiles as $file) {
            $filename = basename($file);
            $found = false;
            
            foreach ($validAudioUrls as $validUrl) {
                if (str_contains($validUrl, $filename)) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                Storage::disk('public')->delete($file);
                $this->line("Deleted orphaned podcast file: {$file}");
                $deletedCount++;
            }
        }
        
        // Clean thumbnail files
        foreach ($thumbnailFiles as $file) {
            $filename = basename($file);
            $found = false;
            
            foreach ($validThumbnailUrls as $validUrl) {
                if (str_contains($validUrl, $filename)) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                Storage::disk('public')->delete($file);
                $this->line("Deleted orphaned podcast thumbnail: {$file}");
                $deletedCount++;
            }
        }
        
        $this->info("Podcast cleanup completed. Deleted {$deletedCount} orphaned files.");
    }

    protected function cleanupVideos()
    {
        $this->info('Cleaning up video files...');
        
        // Get all video files in storage
        $videoFiles = Storage::disk('public')->files('videos');
        $thumbnailFiles = Storage::disk('public')->files('thumbnails');
        
        // Get all valid video files from database
        $validVideoUrls = Video::pluck('video_url')->filter()->all();
        $validThumbnailUrls = Video::pluck('thumbnail_url')->filter()->all();
        
        // Clean video files
        $deletedCount = 0;
        foreach ($videoFiles as $file) {
            $filename = basename($file);
            $found = false;
            
            foreach ($validVideoUrls as $validUrl) {
                if (str_contains($validUrl, $filename)) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                Storage::disk('public')->delete($file);
                $this->line("Deleted orphaned video file: {$file}");
                $deletedCount++;
            }
        }
        
        // Clean thumbnail files
        foreach ($thumbnailFiles as $file) {
            $filename = basename($file);
            $found = false;
            
            foreach ($validThumbnailUrls as $validUrl) {
                if (str_contains($validUrl, $filename)) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                Storage::disk('public')->delete($file);
                $this->line("Deleted orphaned video thumbnail: {$file}");
                $deletedCount++;
            }
        }
        
        $this->info("Video cleanup completed. Deleted {$deletedCount} orphaned files.");
    }

    protected function cleanupLessons()
    {
        $this->info('Cleaning up lesson files...');
        
        // Get all lesson files in storage
        $videoFiles = Storage::disk('public')->files('lessons/videos');
        $thumbnailFiles = Storage::disk('public')->files('lessons/thumbnails');
        
        // Get all valid lesson files from database
        $validVideoPaths = Lesson::pluck('video_path')->filter()->map(function ($path) {
            return str_replace('public/', '', $path);
        })->all();
        
        $validThumbnails = Lesson::pluck('thumbnail')->filter()->map(function ($path) {
            return str_replace('public/', '', $path);
        })->all();
        
        // Clean video files
        $deletedCount = 0;
        foreach ($videoFiles as $file) {
            $filename = basename($file);
            $found = false;
            
            foreach ($validVideoPaths as $validPath) {
                if (str_contains($validPath, $filename)) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                Storage::disk('public')->delete($file);
                $this->line("Deleted orphaned lesson video file: {$file}");
                $deletedCount++;
            }
        }
        
        // Clean thumbnail files
        foreach ($thumbnailFiles as $file) {
            $filename = basename($file);
            $found = false;
            
            foreach ($validThumbnails as $validPath) {
                if (str_contains($validPath, $filename)) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                Storage::disk('public')->delete($file);
                $this->line("Deleted orphaned lesson thumbnail: {$file}");
                $deletedCount++;
            }
        }
        
        $this->info("Lesson cleanup completed. Deleted {$deletedCount} orphaned files.");
    }
}
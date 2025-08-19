<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\Podcast;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DebugStorageCommand extends Command
{
    protected $signature = 'debug:storage {action=check} {--model=} {--id=}';
    protected $description = 'Debug storage issues and handle file cleanup';

    public function handle()
    {
        $action = $this->argument('action');
        $model = $this->option('model');
        $id = $this->option('id');

        if ($action == 'check') {
            $this->checkStorage();
        } elseif ($action == 'clean' && $model && $id) {
            $this->cleanFiles($model, $id);
        } elseif ($action == 'clean-all') {
            $this->cleanOrphanedFiles();
        }

        return Command::SUCCESS;
    }

    private function checkStorage()
    {
        // Get all files in the storage directory
        $this->info('Checking storage directory structure:');
        
        // Check public disk storage
        $files = Storage::disk('public')->allFiles();
        $this->info("Public disk has " . count($files) . " files.");
        if (count($files) > 0) {
            $this->info("Sample files: " . implode(", ", array_slice($files, 0, 5)));
        }

        // Check models
        $videos = Video::all();
        $this->info("Videos in database: " . $videos->count());
        if ($videos->count() > 0) {
            $video = $videos->first();
            $this->info("Sample video paths:");
            $this->info("- video_url: " . $video->video_url);
            $this->info("- thumbnail_url: " . $video->thumbnail_url);
            
            // Check if the files exist
            $videoPath = str_replace(url('/storage'), '', $video->video_url);
            $videoPath = ltrim($videoPath, '/');
            $this->info("Checked video path: " . $videoPath);
            $this->info("File exists in public disk: " . (Storage::disk('public')->exists($videoPath) ? 'Yes' : 'No'));
        }

        $podcasts = Podcast::all();
        $this->info("Podcasts in database: " . $podcasts->count());
        if ($podcasts->count() > 0) {
            $podcast = $podcasts->first();
            $this->info("Sample podcast paths:");
            $this->info("- audio_url: " . $podcast->audio_url);
            $this->info("- thumbnail_url: " . $podcast->thumbnail_url);
            
            // Check if the files exist
            $audioPath = str_replace(url('/storage'), '', $podcast->audio_url);
            $audioPath = ltrim($audioPath, '/');
            $this->info("Checked audio path: " . $audioPath);
            $this->info("File exists in public disk: " . (Storage::disk('public')->exists($audioPath) ? 'Yes' : 'No'));
        }

        $lessons = Lesson::all();
        $this->info("Lessons in database: " . $lessons->count());
        if ($lessons->count() > 0) {
            $lesson = $lessons->first();
            $this->info("Sample lesson paths:");
            $this->info("- video_path: " . $lesson->video_path);
            $this->info("- thumbnail: " . $lesson->thumbnail);
            
            // Check if the files exist
            $videoPath = str_replace('public/', '', $lesson->video_path);
            $this->info("Checked video path: " . $videoPath);
            $this->info("File exists in public disk: " . (Storage::disk('public')->exists($videoPath) ? 'Yes' : 'No'));
        }
    }

    private function cleanFiles($model, $id)
    {
        $this->info("Cleaning files for $model #$id");
        
        $modelClass = "App\\Models\\" . ucfirst($model);
        if (!class_exists($modelClass)) {
            $this->error("Model class not found: $modelClass");
            return;
        }
        
        $instance = $modelClass::find($id);
        if (!$instance) {
            $this->error("Record not found: $model #$id");
            return;
        }
        
        // Delete files based on model type
        if ($model == 'Video') {
            $this->deleteFile($instance->video_url, 'video_url');
            $this->deleteFile($instance->thumbnail_url, 'thumbnail_url');
        } elseif ($model == 'Podcast') {
            $this->deleteFile($instance->audio_url, 'audio_url');
            $this->deleteFile($instance->thumbnail_url, 'thumbnail_url');
        } elseif ($model == 'Lesson') {
            $this->deleteFile($instance->video_path, 'video_path', true);
            $this->deleteFile($instance->thumbnail, 'thumbnail', true);
        }
    }

    private function deleteFile($path, $fieldName, $isStoragePath = false)
    {
        if (empty($path)) {
            $this->warn("No $fieldName provided");
            return false;
        }

        $this->info("Attempting to delete file: $path");
        
        // Handle URLs vs storage paths
        if (!$isStoragePath) {
            // For URL-based files (videos/podcasts)
            $storagePath = str_replace(url('/storage'), '', $path);
            $storagePath = ltrim($storagePath, '/');
            
            if (Storage::disk('public')->exists($storagePath)) {
                $result = Storage::disk('public')->delete($storagePath);
                $this->info("File deletion result: " . ($result ? 'Success' : 'Failed'));
                return $result;
            } else {
                $this->warn("File not found in storage: $storagePath");
            }
        } else {
            // For storage paths (lessons)
            if (Storage::exists($path)) {
                $result = Storage::delete($path);
                $this->info("File deletion result: " . ($result ? 'Success' : 'Failed'));
                return $result;
            } else {
                // Try without 'public/' prefix
                $cleanPath = str_replace('public/', '', $path);
                if (Storage::disk('public')->exists($cleanPath)) {
                    $result = Storage::disk('public')->delete($cleanPath);
                    $this->info("File deletion result (cleaned path): " . ($result ? 'Success' : 'Failed'));
                    return $result;
                } else {
                    $this->warn("File not found in storage: $path or $cleanPath");
                }
            }
        }
        
        return false;
    }    private function cleanOrphanedFiles()
    {
        $this->info("Cleaning up orphaned files from storage...");
        
        // Get all storage files
        $videoFiles = Storage::disk('public')->files('videos');
        $videoThumbnails = Storage::disk('public')->files('thumbnails');
        $podcastFiles = Storage::disk('public')->files('podcasts');
        $podcastThumbnails = Storage::disk('public')->files('podcast-thumbnails');
        $lessonVideos = Storage::disk('public')->files('lessons/videos');
        $lessonThumbnails = Storage::disk('public')->files('lessons/thumbnails');
        
        // Check for any broadcast-related directories and files
        $broadcastFiles = [];
        if (Storage::disk('public')->exists('broadcasts')) {
            $broadcastFiles = Storage::disk('public')->files('broadcasts');
        }
        $broadcastThumbnails = [];
        if (Storage::disk('public')->exists('broadcast-thumbnails')) {
            $broadcastThumbnails = Storage::disk('public')->files('broadcast-thumbnails');
        }
        
        // Get database files
        $validVideoUrls = Video::pluck('video_url')->filter()->all();
        $validVideoThumbs = Video::pluck('thumbnail_url')->filter()->all();
        $validPodcastUrls = Podcast::pluck('audio_url')->filter()->all();  
        $validPodcastThumbs = Podcast::pluck('thumbnail_url')->filter()->all();
        $validLessonVideos = Lesson::pluck('video_path')->filter()->map(function($p) {
            return str_replace('public/', '', $p);
        })->all();
        $validLessonThumbs = Lesson::pluck('thumbnail')->filter()->map(function($p) {
            return str_replace('public/', '', $p);
        })->all();
        
        $deletedCount = 0;
        
        // Clean videos
        foreach ($videoFiles as $file) {
            $filename = basename($file);
            $found = false;
            
            foreach ($validVideoUrls as $url) {
                if (str_contains($url, $filename)) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                Storage::disk('public')->delete($file);
                $this->line("Deleted orphaned video: {$file}");
                $deletedCount++;
            }
        }
        
        // Clean video thumbnails
        foreach ($videoThumbnails as $file) {
            $filename = basename($file);
            $found = false;
            
            foreach ($validVideoThumbs as $url) {
                if (str_contains($url, $filename)) {
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
        
        // Clean podcast files
        foreach ($podcastFiles as $file) {
            $filename = basename($file);
            $found = false;
            
            foreach ($validPodcastUrls as $url) {
                if (str_contains($url, $filename)) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                Storage::disk('public')->delete($file);
                $this->line("Deleted orphaned podcast: {$file}");
                $deletedCount++;
            }
        }
        
        // Clean podcast thumbnails
        foreach ($podcastThumbnails as $file) {
            $filename = basename($file);
            $found = false;
            
            foreach ($validPodcastThumbs as $url) {
                if (str_contains($url, $filename)) {
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
        
        // Clean lesson videos
        foreach ($lessonVideos as $file) {
            $filename = basename($file);
            $found = false;
            
            foreach ($validLessonVideos as $path) {
                if (str_contains($path, $filename)) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                Storage::disk('public')->delete($file);
                $this->line("Deleted orphaned lesson video: {$file}");
                $deletedCount++;
            }
        }
        
        // Clean lesson thumbnails
        foreach ($lessonThumbnails as $file) {
            $filename = basename($file);
            $found = false;
            
            foreach ($validLessonThumbs as $path) {
                if (str_contains($path, $filename)) {
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
        
        // Delete all broadcast files since broadcast model has been removed
        foreach ($broadcastFiles as $file) {
            Storage::disk('public')->delete($file);
            $this->line("Deleted broadcast file: {$file}");
            $deletedCount++;
        }
        
        foreach ($broadcastThumbnails as $file) {
            Storage::disk('public')->delete($file);
            $this->line("Deleted broadcast thumbnail: {$file}");
            $deletedCount++;
        }
        
        // Delete broadcast directories if they exist and are empty
        if (Storage::disk('public')->exists('broadcasts') && count(Storage::disk('public')->files('broadcasts')) === 0) {
            Storage::disk('public')->deleteDirectory('broadcasts');
            $this->line("Deleted empty broadcasts directory");
        }
        
        if (Storage::disk('public')->exists('broadcast-thumbnails') && count(Storage::disk('public')->files('broadcast-thumbnails')) === 0) {
            Storage::disk('public')->deleteDirectory('broadcast-thumbnails');
            $this->line("Deleted empty broadcast-thumbnails directory");
        }
        
        $this->info("Storage cleanup completed. Deleted {$deletedCount} orphaned files.");
    }
}
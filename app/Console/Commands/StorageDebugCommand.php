<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\Podcast;
use App\Models\Video;
use App\Services\FileDeleteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class StorageDebugCommand extends Command
{
    protected $signature = 'storage:debug {action=list} {--id=} {--model=}';
    protected $description = 'Debug and manage storage files';

    public function handle()
    {
        $action = $this->argument('action');
        $model = $this->option('model');
        $id = $this->option('id');

        if ($action === 'list') {
            $this->listStorageFiles();
        } elseif ($action === 'clean' && $model && $id) {
            $this->cleanModelFiles($model, $id);
        } elseif ($action === 'dump-model' && $model && $id) {
            $this->dumpModelFileInfo($model, $id);
        }

        return Command::SUCCESS;
    }

    private function listStorageFiles()
    {
        $this->info('Listing storage files:');
        
        // List files in public disk
        $files = Storage::disk('public')->allFiles();
        $this->info("Public disk has " . count($files) . " files.");
        if (count($files) > 0) {
            $this->info("Sample files:");
            foreach (array_slice($files, 0, 10) as $file) {
                $this->line(" - " . $file);
                
                // Check if file actually exists in filesystem
                $path = storage_path('app/public/' . $file);
                $this->line("   Exists: " . (File::exists($path) ? "Yes" : "No"));
                
                if (File::exists($path)) {
                    $this->line("   Size: " . round(File::size($path) / 1024, 2) . " KB");
                }
            }
        }
        
        // Count files by type
        $videoCount = count(Storage::disk('public')->files('videos'));
        $thumbnailCount = count(Storage::disk('public')->files('thumbnails'));
        $podcastCount = count(Storage::disk('public')->files('podcasts'));
        $podcastThumbnailCount = count(Storage::disk('public')->files('podcast-thumbnails'));
        $lessonVideoCount = count(Storage::disk('public')->files('lessons/videos'));
        $lessonThumbnailCount = count(Storage::disk('public')->files('lessons/thumbnails'));
        
        $this->info("File counts by directory:");
        $this->line(" - Videos: $videoCount");
        $this->line(" - Thumbnails: $thumbnailCount");
        $this->line(" - Podcasts: $podcastCount");
        $this->line(" - Podcast Thumbnails: $podcastThumbnailCount");
        $this->line(" - Lesson Videos: $lessonVideoCount");
        $this->line(" - Lesson Thumbnails: $lessonThumbnailCount");
    }

    private function cleanModelFiles($model, $id)
    {
        $this->info("Cleaning files for {$model} #{$id}");
        
        $class = "App\\Models\\" . ucfirst($model);
        if (!class_exists($class)) {
            $this->error("Model not found: {$class}");
            return;
        }
        
        $record = $class::find($id);
        if (!$record) {
            $this->error("Record not found: {$model} #{$id}");
            return;
        }
        
        // Delete files based on model type
        if ($model === 'Video') {
            $this->info("Deleting video file: " . $record->video_url);
            $result1 = FileDeleteService::deleteStorageFile($record->video_url, 'video');
            $this->info("Video deletion result: " . ($result1 ? "Success" : "Failed"));
            
            $this->info("Deleting thumbnail file: " . $record->thumbnail_url);
            $result2 = FileDeleteService::deleteStorageFile($record->thumbnail_url, 'thumbnail');
            $this->info("Thumbnail deletion result: " . ($result2 ? "Success" : "Failed"));
        }
        elseif ($model === 'Podcast') {
            $this->info("Deleting audio file: " . $record->audio_url);
            $result1 = FileDeleteService::deleteStorageFile($record->audio_url, 'audio');
            $this->info("Audio deletion result: " . ($result1 ? "Success" : "Failed"));
            
            $this->info("Deleting thumbnail file: " . $record->thumbnail_url);
            $result2 = FileDeleteService::deleteStorageFile($record->thumbnail_url, 'thumbnail');
            $this->info("Thumbnail deletion result: " . ($result2 ? "Success" : "Failed"));
        }
        elseif ($model === 'Lesson') {
            $this->info("Deleting video file: " . $record->video_path);
            $result1 = FileDeleteService::deleteStorageFile($record->video_path, 'video');
            $this->info("Video deletion result: " . ($result1 ? "Success" : "Failed"));
            
            $this->info("Deleting thumbnail file: " . $record->thumbnail);
            $result2 = FileDeleteService::deleteStorageFile($record->thumbnail, 'thumbnail');
            $this->info("Thumbnail deletion result: " . ($result2 ? "Success" : "Failed"));
        }
    }
    
    private function dumpModelFileInfo($model, $id)
    {
        $this->info("Dumping file info for {$model} #{$id}");
        
        $class = "App\\Models\\" . ucfirst($model);
        if (!class_exists($class)) {
            $this->error("Model not found: {$class}");
            return;
        }
        
        $record = $class::find($id);
        if (!$record) {
            $this->error("Record not found: {$model} #{$id}");
            return;
        }
        
        // Display file fields based on model type
        if ($model === 'Video') {
            $this->info("Video URL: " . $record->video_url);
            $this->info("Thumbnail URL: " . $record->thumbnail_url);
        }
        elseif ($model === 'Podcast') {
            $this->info("Audio URL: " . $record->audio_url);
            $this->info("Thumbnail URL: " . $record->thumbnail_url);
        }
        elseif ($model === 'Lesson') {
            $this->info("Video Path: " . $record->video_path);
            $this->info("Thumbnail: " . $record->thumbnail);
        }
        
        // Display complete record
        $this->info("Complete record data:");
        $this->line(json_encode($record->toArray(), JSON_PRETTY_PRINT));
    }
}
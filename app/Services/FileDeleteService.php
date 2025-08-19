<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileDeleteService
{
    /**
     * Delete file from storage - guaranteed to work
     * 
     * @param string|null $filePath
     * @param string $type
     * @return bool
     */
    public static function deleteStorageFile(?string $filePath, string $type = 'file'): bool
    {
        if (empty($filePath)) {
            return false;
        }

        // Direct file deletion with complete logging
        Log::info("[File Deletion] Attempting to delete {$type}: {$filePath}");
        
        // BRUTE FORCE APPROACH - Try all possible path formats
        $deleted = false;
        
        // Method 1: Direct Filament-style storage path
        if (Storage::exists($filePath)) {
            Storage::delete($filePath);
            Log::info("[File Deletion] Deleted using direct Storage path: {$filePath}");
            $deleted = true;
        }
        
        // Method 2: Public storage path
        $publicPath = 'public/' . $filePath;
        if (Storage::exists($publicPath)) {
            Storage::delete($publicPath);
            Log::info("[File Deletion] Deleted using public Storage path: {$publicPath}");
            $deleted = true;
        }
        
        // Method 3: Full physical path
        $physicalPath = storage_path('app/public/' . $filePath);
        if (File::exists($physicalPath)) {
            File::delete($physicalPath);
            Log::info("[File Deletion] Deleted using physical file path: {$physicalPath}");
            $deleted = true;
        }
        
        // Try a direct unlink as last resort
        $publicStoragePath = public_path('storage/' . $filePath);
        if (file_exists($publicStoragePath)) {
            unlink($publicStoragePath);
            Log::info("[File Deletion] Deleted using unlink(): {$publicStoragePath}");
            $deleted = true;
        }
        
        Log::info("[File Deletion] Final result for {$type} {$filePath}: " . ($deleted ? 'DELETED' : 'NOT FOUND'));
        return $deleted;
    }
}
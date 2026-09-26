<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HandlesMediaUploads
{
    /**
     * Store a newly uploaded file on the public disk, returning its path.
     *
     * Returns null when the request carries no file for that input, which lets callers leave an
     * existing value untouched on update.
     */
    protected function storeUploadedMedia(Request $request, string $inputName, string $directory): ?string
    {
        if (! $request->hasFile($inputName)) {
            return null;
        }

        return $request->file($inputName)->store($directory, 'public');
    }

    /**
     * Delete a previously stored file, ignoring absolute URLs (which this app does not own).
     */
    protected function deleteStoredMedia(?string $path): void
    {
        if (blank($path) || str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    /**
     * Copy a stored file into another directory and return the copy's path, so two records
     * never share one file and deleting either can't take the other's image with it.
     *
     * Absolute URLs are returned untouched; a path whose file is gone returns null.
     */
    protected function copyStoredMedia(?string $path, string $directory): ?string
    {
        if (blank($path) || str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $copy = $directory.'/'.Str::random(40).($extension !== '' ? '.'.$extension : '');
        $disk->copy($path, $copy);

        return $copy;
    }

    /**
     * Merge a freshly uploaded file path into validated data, replacing whatever the model held.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function withUploadedMedia(array $data, Request $request, string $inputName, string $column, string $directory, ?string $currentPath = null): array
    {
        $path = $this->storeUploadedMedia($request, $inputName, $directory);

        if ($path === null) {
            return $data;
        }

        $this->deleteStoredMedia($currentPath);
        $data[$column] = $path;

        return $data;
    }
}

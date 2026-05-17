<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    public function uploadVideo(UploadedFile $file, int $userId): string
    {
        $filename = $userId . '_' . time() . '_' . Str::random(10) . '.' . $file->extension();
        $path = $file->storeAs('user_' . $userId, $filename, 'videos');

        return $path;
    }

    public function uploadSignature(string $base64Data, int $userId, int $addressId): string
    {
        // Remove data URL prefix if present
        $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $base64Data);
        $image = base64_decode($imageData);

        if ($image === false) {
            throw new \InvalidArgumentException('Invalid base64 image data');
        }

        $filename = "signature_{$addressId}_" . time() . '.png';
        $path = "user_{$userId}/{$filename}";

        Storage::disk('signatures')->put($path, $image);

        return $path;
    }

    public function uploadAvatar(UploadedFile $file, int $userId): string
    {
        $filename = 'avatar_' . $userId . '_' . time() . '.' . $file->extension();

        return $file->storeAs('', $filename, 'avatars');
    }

    public function uploadCollectionLogo(UploadedFile $file, int $collectionId): string
    {
        $filename = 'collection_' . $collectionId . '_' . time() . '.' . $file->extension();

        return $file->storeAs('collections', $filename, 'public');
    }

    public function deleteFile(string $disk, string $path): bool
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }

    public function getVideoUrl(string $path): ?string
    {
        if (Storage::disk('videos')->exists($path)) {
            return Storage::disk('videos')->url($path);
        }

        return null;
    }

    public function getSignatureUrl(string $path): ?string
    {
        if (Storage::disk('signatures')->exists($path)) {
            return Storage::disk('signatures')->url($path);
        }

        return null;
    }

    public function getAvatarUrl(string $path): ?string
    {
        if (Storage::disk('avatars')->exists($path)) {
            return Storage::disk('avatars')->url($path);
        }

        return null;
    }
}

<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SyncUserPhoto
{
    public function handle(User $user, ?UploadedFile $photo, bool $removePhoto = false): void
    {
        if ($removePhoto && $user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
            $user->forceFill(['photo_path' => null])->save();
        }

        if ($photo === null) {
            return;
        }

        if ($user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
        }

        $path = $photo->store('users/photos', 'public');

        $user->forceFill(['photo_path' => $path])->save();
    }
}

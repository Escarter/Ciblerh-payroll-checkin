<?php

namespace App\Livewire\Traits;

use App\Models\User;
use App\Services\ProfilePhotoService;

trait ManagesEmployeePhoto
{
    public $photo = null;

    public function updatedPhoto(ProfilePhotoService $photos): void
    {
        $this->validate([
            'photo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        // In edit mode, save immediately just like SchoolOS
        if (!empty($this->isEditMode) && !empty($this->employee) && $this->employee instanceof User) {
            $this->authorizeAbility('employee-update');
            $oldPath = $this->employee->photo_path;
            $newPath = $photos->store($this->photo, 'profile-photos');
            $this->employee->update(['photo_path' => $newPath]);

            if ($oldPath && $oldPath !== $newPath) {
                $photos->delete($oldPath);
            }

            $this->employee->refresh();
            $this->photo = null;
            $this->showToast(__('common.photo_saved_successfully'), 'success');
        }
    }

    public function removePhoto(ProfilePhotoService $photos): void
    {
        if (!empty($this->employee) && $this->employee instanceof User && !empty($this->employee->photo_path)) {
            $this->authorizeAbility('employee-update');
            $photos->delete($this->employee->photo_path);
            $this->employee->update(['photo_path' => null]);
            $this->employee->refresh();
            $this->showToast(__('common.photo_removed_successfully'), 'success');
        }

        $this->photo = null;
    }

    protected function persistEmployeePhotoIfUploaded(User $user): void
    {
        if (!$this->photo) {
            return;
        }

        $this->validate([
            'photo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $photos = app(ProfilePhotoService::class);
        $oldPath = $user->photo_path;
        $newPath = $photos->store($this->photo, 'profile-photos');
        $user->update(['photo_path' => $newPath]);

        if ($oldPath && $oldPath !== $newPath) {
            $photos->delete($oldPath);
        }

        $this->photo = null;
    }
}

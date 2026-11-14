<?php

namespace App\Livewire\Portal;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Gate;
use App\Rules\CurrentPasswordCheckRule;
use App\Services\TrustedDeviceService;
use Illuminate\Support\Facades\Hash;

class ProfileSetting extends Component
{
    use \App\Livewire\Traits\AuthorizesAbilities;
    use \App\Livewire\Traits\ManagesOwnTwoFactor;
    use WithFileUploads;
    use WithPagination;
    public $first_name;
    public $last_name;
    public $email;
    public $phone_number;
    public $position;
    public $matricule;
    public $current_password;
    public $password;
    public $password_confirmation;
    public $signature;
    public $photo;
    public $date_of_birth;
    public $preferred_language;
    
    public function mount()
    {
        $this->first_name = auth()->user()->first_name;
        $this->last_name = auth()->user()->last_name;
        $this->email = auth()->user()->email;
        $this->matricule = auth()->user()->matricule;
        $this->position = auth()->user()->position;
        $this->phone_number = auth()->user()->phone_number;
        $this->date_of_birth = auth()->user()->date_of_birth;
        $this->preferred_language = auth()->user()->preferred_language;
        $this->initTwoFactorEnrolment();
    }

    public function updatedPhoto(\App\Services\ProfilePhotoService $photos): void
    {
        $this->authorizeAbility('profile-update');
        $this->validate([
            'photo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $user = auth()->user();
        $oldPath = $user->photo_path;
        $newPath = $photos->store($this->photo, 'profile-photos');

        $user->update(['photo_path' => $newPath]);
        if ($oldPath && $oldPath !== $newPath) {
            $photos->delete($oldPath);
        }

        $this->photo = null;
        $this->refresh(__('common.photo_saved_successfully'));
    }

    public function removePhoto(\App\Services\ProfilePhotoService $photos): void
    {
        $this->authorizeAbility('profile-update');
        $user = auth()->user();
        if (!empty($user->photo_path)) {
            $photos->delete($user->photo_path);
            $user->update(['photo_path' => null]);
        }

        $this->photo = null;
        $this->refresh(__('common.photo_removed_successfully'));
    }

    public function updateProfile()
    {
        $this->authorizeAbility('profile-update');
        auth()->user()->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'position' => $this->position,
            'matricule' => $this->matricule,
            'date_of_birth' => $this->date_of_birth ?: null,
            'preferred_language' => $this->preferred_language,
        ]);

        $this->refresh(__('auth.profile_updated_successfully'));
    }
    public function saveSignature()
    {
        $this->authorizeAbility('profile-update');
        if ($this->signature) {
            if(!empty(auth()->user()->signature_path)){

            }
            auth()->user()->update(['signature_path' => $this->signature->storePublicly('signatures', 'attachments')]);
        }

        $this->refresh(__('auth.signature_saved_successfully'));
    }
    public function passwordReset()
    {
        $this->validate([
            'current_password' => ['required', new CurrentPasswordCheckRule],
            'password' => 'required|min:8|different:current_password|confirmed',
            'password_confirmation' => 'required|min:8',
        ]);

        auth()->user()->update(['password' => Hash::make($this->password)]);
        app(TrustedDeviceService::class)->forgetAll(auth()->user());

        $this->reset(['current_password','password','password_confirmation']);

        $this->refresh(__('auth.password_reseted_successfully'));
    }
    public function refresh($message)
    {
        $this->dispatch("showToast", message: $message, type: "success");
    }
    public function render()
    {
        return view('livewire.portal.profile-setting', $this->twoFactorViewData())->layout('components.layouts.dashboard');
    }
}


<?php

namespace App\Livewire\Portal;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Gate;
use App\Rules\CurrentPasswordCheckRule;
use Illuminate\Support\Facades\Hash;

class ProfileSetting extends Component
{
    use WithFileUploads;
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
    public $preferred_language;
    
    public function mount()
    {
        $this->first_name = auth()->user()->first_name;
        $this->last_name = auth()->user()->last_name;
        $this->email = auth()->user()->email;
        $this->matricule = auth()->user()->matricule;
        $this->position = auth()->user()->position;
        $this->phone_number = auth()->user()->phone_number;
    }

    public function updateProfile()
    {
        if (!Gate::allows('profile-update')) {
            return abort(401);
        }
        auth()->user()->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'position' => $this->position,
            'matricule' => $this->matricule,
            'preferred_language' => $this->preferred_language,
        ]);

        $this->refresh(__('Profile updated successfully!'));
    }
    public function saveSignature()
    {
        if (!Gate::allows('profile-update')) {
            return abort(401);
        }
        if ($this->signature) {
            if(!empty(auth()->user()->signature_path)){

            }
            auth()->user()->update(['signature_path' => $this->signature->storePublicly('signatures', 'attachments')]);
        }

        $this->refresh(__('Signature saved successfully!'));
    }
    public function passwordReset()
    {
        $this->validate([
            'current_password' => ['required', new CurrentPasswordCheckRule],
            'password' => 'required|min:8|different:current_password|confirmed',
            'password_confirmation' => 'required|min:8',
        ]);

        auth()->user()->update(['password' => Hash::make($this->password)]);

        $this->reset(['current_password','password','password_confirmation']);

        $this->refresh(__('Password reseted successfully!'));
    }
    public function refresh($message)
    {
        session()->flash('message', $message);
    }
    public function render()
    {
        return view('livewire.portal.profile-setting')->layout('components.layouts.dashboard');
    }
}


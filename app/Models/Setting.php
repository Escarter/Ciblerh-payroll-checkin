<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;
    protected $guarded = [];
    
    protected $casts = [
        'sftp_matching_strategies' => 'json',
        'sftp_sync_enabled' => 'boolean',
        'inactivity_deactivation_enabled' => 'boolean',
    ];

}

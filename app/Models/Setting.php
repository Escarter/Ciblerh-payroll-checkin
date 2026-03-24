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
        'sftp_auto_match_enabled' => 'boolean',
        'inactivity_deactivation_enabled' => 'boolean',
        'sftp_push_archive_move_processed' => 'boolean',
        'sftp_push_archive_move_rejected' => 'boolean',
        'sftp_push_archive_move_failed' => 'boolean',
        'sftp_push_archive_require_successful_process' => 'boolean',
    ];

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SftpPushLog extends Model
{
    use HasFactory;

    protected $table = 'sftp_push_logs';
    
    protected $fillable = [
        'username',
        'filename',
        'stored_filename',
        'file_size',
        'remote_ip',
        'status',
        'error_message',
        'rejection_reason',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to get successful uploads
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope to get failed uploads
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get by username
     */
    public function scopeByUsername($query, $username)
    {
        return $query->where('username', $username);
    }

    /**
     * Scope to get recent uploads (last N days)
     */
    public function scopeRecentDays($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}

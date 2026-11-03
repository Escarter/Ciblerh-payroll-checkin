<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SftpUser extends Model
{
    protected $table = 'sftp_users';

    protected $fillable = [
        'username',
        'password',
        'home_directory',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Absolute filesystem path to this user's home directory.
     */
    public function absoluteHomePath(): string
    {
        return base_path($this->home_directory);
    }

    /**
     * Absolute path to the incoming/ subdirectory scanned by the scanner command.
     */
    public function absoluteIncomingPath(): string
    {
        return $this->absoluteHomePath() . '/incoming';
    }
}

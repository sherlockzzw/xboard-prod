<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class AdminRequestLog extends Model
{
    use \App\Scope\FilterScope;

    protected $table = 'admin_request_logs';
    protected $dateFormat = 'U';
    protected $guarded = ['id'];

    protected $casts = [
        'body' => 'array',
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];

    public $timestamps = false;

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id', 'id');
    }
}


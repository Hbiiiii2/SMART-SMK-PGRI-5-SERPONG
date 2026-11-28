<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DapodikSyncLog extends Model
{
    use HasFactory;

    protected $table = 'dapodik_sync_logs';

    protected $fillable = [
        'user_id',
        'source',
        'entity',
        'filename',
        'status',
        'total_rows',
        'inserted_count',
        'updated_count',
        'skipped_count',
        'warning_count',
        'error_count',
        'summary_notes',
        'anomalies',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'inserted_count' => 'integer',
        'updated_count' => 'integer',
        'skipped_count' => 'integer',
        'warning_count' => 'integer',
        'error_count' => 'integer',
        'anomalies' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

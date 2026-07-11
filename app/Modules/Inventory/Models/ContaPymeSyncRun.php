<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class ContaPymeSyncRun extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'contapyme_sync_runs';

    protected $fillable = [
        'id',
        'origin',
        'mode',
        'status',
        'warehouse',
        'started_at',
        'finished_at',
        'duration_ms',
        'processed',
        'updated',
        'unchanged',
        'no_sku',
        'confirmed_zero',
        'missing_contapyme',
        'unmapped',
        'skipped_variants',
        'failed',
        'summary',
        'error_groups',
        'error_details',
        'diagnostics',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'error_groups' => 'array',
            'error_details' => 'array',
            'diagnostics' => 'array',
        ];
    }
}

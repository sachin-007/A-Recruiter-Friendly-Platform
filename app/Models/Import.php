<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'imported_by',
        'file_name',
        'file_path',
        'status',
        'total_rows',
        'processed_rows',
        'error_log',
    ];

    protected $casts = [
        'error_log' => 'array',
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
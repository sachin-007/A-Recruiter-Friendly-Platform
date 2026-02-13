<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Attempt extends Model
{
    use HasApiTokens, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'invitation_id',
        'test_id',
        'candidate_email',
        'candidate_name',
        'started_at',
        'completed_at',
        'time_remaining',
        'status',
        'score_total',
        'score_percent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'time_remaining' => 'integer',
        'score_total' => 'decimal:2',
        'score_percent' => 'decimal:2',
    ];

    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function answers()
    {
        return $this->hasMany(AttemptAnswer::class);
    }
}

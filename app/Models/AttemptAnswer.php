<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttemptAnswer extends Model
{
    protected $fillable = [
        'attempt_id',
        'question_id',
        'answer_json',
        'is_correct',
        'marks_awarded',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'answer_json' => 'array',
        'is_correct' => 'boolean',
        'marks_awarded' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function attempt()
    {
        return $this->belongsTo(Attempt::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
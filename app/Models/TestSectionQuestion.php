<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TestSectionQuestion extends Pivot
{
    protected $table = 'test_section_question';

    protected $fillable = [
        'test_section_id',
        'question_id',
        'marks',
        'order',
        'is_optional',
    ];

    protected $casts = [
        'marks' => 'integer',
        'order' => 'integer',
        'is_optional' => 'boolean',
    ];

    public function testSection()
    {
        return $this->belongsTo(TestSection::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
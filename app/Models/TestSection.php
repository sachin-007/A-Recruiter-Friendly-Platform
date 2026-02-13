<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TestSection extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'test_id',
        'title',
        'description',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'test_section_question')
                    ->withPivot(['marks', 'order', 'is_optional'])
                    ->withTimestamps();
    }

    public function pivotQuestions()
    {
        return $this->hasMany(TestSectionQuestion::class);
    }
}
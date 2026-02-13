<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'name',
        'email',
        'role',
        'is_active',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function otps()
    {
        return $this->hasMany(Otp::class);
    }

    public function createdQuestions()
    {
        return $this->hasMany(Question::class, 'created_by');
    }

    public function updatedQuestions()
    {
        return $this->hasMany(Question::class, 'updated_by');
    }

    public function createdTests()
    {
        return $this->hasMany(Test::class, 'created_by');
    }

    public function updatedTests()
    {
        return $this->hasMany(Test::class, 'updated_by');
    }

    public function invitations()
    {
        return $this->hasMany(Invitation::class, 'created_by');
    }

    public function reviewedAnswers()
    {
        return $this->hasMany(AttemptAnswer::class, 'reviewed_by');
    }

    public function imports()
    {
        return $this->hasMany(Import::class, 'imported_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}
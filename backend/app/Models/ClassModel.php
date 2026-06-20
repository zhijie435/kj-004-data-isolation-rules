<?php

namespace App\Models;

use App\Scopes\DataIsolationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'tenant_id',
        'school_id',
        'course_id',
        'teacher_id',
        'name',
        'grade',
        'status',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new DataIsolationScope());
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'class_student', 'class_id', 'student_id');
    }
}

<?php

namespace App\Models;

use App\Scopes\DataIsolationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'school_id',
        'teacher_id',
        'name',
        'description',
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

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function classes()
    {
        return $this->hasMany(ClassModel::class);
    }
}

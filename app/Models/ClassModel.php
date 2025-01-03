<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    protected $table ='classes';
    protected $fillable = ['name', 'teacher_id'];
    
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
    
    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }
    
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'class_id');
    }
}

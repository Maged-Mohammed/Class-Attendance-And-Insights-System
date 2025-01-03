<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'class_id', 'user_id'];
    
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }
}

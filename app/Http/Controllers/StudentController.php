<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class StudentController extends Controller
{
    public function store (Request $request)
    {
        $rules = [
            'name'     => 'required|string|max:255',
            'class_id' => 'required|exists:classes,id',
        ];
        
        $validator = validator ()->make ($request->all (), $rules);
        
        if ($validator->fails ()) {
            return response ()->json (['message' => $validator->errors ()->first ()], 400);
        }
        $validatedData = $validator->validated ();
        
        
        $validatedData['user_id'] = auth ()->id ();
        
        Student::create ($validatedData);
        
        
        return response ()->json (['message' => 'Student added successfully'], 201);
    }
    
}

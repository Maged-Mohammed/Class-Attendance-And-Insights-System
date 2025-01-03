<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ClassController extends Controller
{
    
    public function store (Request $request)
    {
//        dd (auth()->id());
    
        $rules = [
            'name'       => 'required|string|max:255',
            'teacher_id' => 'required|exists:users,id|',
        ];
    
        $validator = validator()->make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }
        $validatedData = $validator->validated();
    
        // create new class
        $class = ClassModel::create ([
            'name'       => $validatedData['name'],
            'teacher_id' => $validatedData['teacher_id'],
        ]);
        
        return response ()->json (['message' => 'Class created successfully', 'class' => $class], 201);
    }
    
    /**
     * show all classes for the teacher
     */
    public function getClasses ()
    {
        // get all classes belong to the teacher
        $classes = auth ()->user ()->classes;
        
        return response ()->json (['classes' => $classes], 200);
    }
}

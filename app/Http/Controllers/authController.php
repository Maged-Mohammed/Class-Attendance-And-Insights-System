<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class authController extends Controller
{
    public function register (Request $request)
    {
        
        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'min:8'],
            'role'     => 'required|in:admin,teacher',
        ];
        
        $validator = validator ()->make ($request->all (), $rules, []);
        
        if ($validator->fails ()) {
            return response ()->json (['message' => $validator->errors ()->first ()], 400);
        }
        
        $user = User::create ([
            'name'     => $request->name,
            'email'    => $request->email,
            'role'     => $request->role,
            'password' => Hash::make ($request->string ('password')),
        ]);

//        Auth::login ($user);
        return response ()->json ([
            'message' => 'User Registered successful',
        ]);
    }
    
    public function login (Request $request)
    {
        $rules = [
            'email'    => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ];
        
        $validator = validator ()->make ($request->all (), $rules);
        
        if ($validator->fails ()) {
            return response ()->json (['message' => $validator->errors ()->first ()], 400);
        }
        
        if ( !Auth::guard ('web')->attempt ($request->only ('email', 'password'))) {
            return response ()->json (['message' => 'The provided credentials are incorrect.'], 401);
        }
        
        $user = Auth::guard ('web')->user ();
        
        $token = $user->createToken ('auth_token')->plainTextToken;
        
        return response ()->json ([
            'message' => 'Login successful',
            'token'   => $token,
        ]);
    }
    
}

<?php

namespace App\Domains\AppCore\Http\Controllers;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApiLoginController extends Controller
{
   public function login (Request $request) {

    try {      
        $user = User::where('email', $request->email)->first();
     
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
     
        return response()->json($user->createToken($request->device_name)->plainTextToken);
    } catch (Exception $e) {
        return response()->json([
            "status" => 402,
            "message" => $e->getMessage()
        ], 402);
    }
}
}

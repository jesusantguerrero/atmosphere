<?php

namespace App\Domains\AppCore\Http\Controllers;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AuthClientController extends Controller
{
   public function login (Request $request) {

    $data = $request->validate([
        'email' => ['required', 'email', 'exists:users,email'],
        'dni' => ['required', 'dni', 'exists:users,email'],
    ]);

    try {
        User::where([
            'email' => $data['email'],
            'dni' => $data['dni']
        ])->first()->sendLoginLink();

        session()->flash('success', true);
        return redirect()->back();
    } catch (Exception $e) {
        return response()->json([
            "status" => 402,
            "message" => $e->getMessage()
        ], 402);
    }
}
}

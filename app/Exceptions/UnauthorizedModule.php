<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;

class UnauthorizedModule extends Exception
{
    public function render(Request $request) {
            return inertia('Errors/UnauthorizedModule', [], 404);
    }
}

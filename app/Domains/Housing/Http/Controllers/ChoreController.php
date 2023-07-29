<?php

namespace App\Domains\Housing\Http\Controllers;

use App\Domains\Housing\Models\OccurrenceCheck;
use App\Http\Controllers\Controller;

class ChoreController extends Controller
{
    public function __invoke()
    {
        $user = request()->user();
        $date = request()->query('date') ?? now()->format('Y-m-d');

        return inertia('Housing/Plans', [
            'notebooks' =>  BoardResource::collection(Board::where([
                'team_id' => $user->current_team_id,
                'user_id' => $user->id,
                'board_type_id' => 2
            ])->get()),
        ]);
    }
}

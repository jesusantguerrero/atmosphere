<?php

namespace App\Domains\AppCore\Models;

use Illuminate\Database\Eloquent\Model;

class CoreModule extends Model
{
    protected $fillable = ['team_id', 'user_id', 'name', 'alias', 'enabled', 'created_from'];


    public static function updateBulk($data, $sessionData = null) {
        foreach ($data as $moduleData) {
            self::updateOrCreate([
                "name" => $moduleData["name"],
                "team_id" => $moduleData["team_id"],
                "user_id" => $moduleData["user_id"]
            ], [
                'enabled' => $moduleData['enabled'],
            ]);
        }
    }

}

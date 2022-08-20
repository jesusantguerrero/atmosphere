<?php

namespace Freesgen\Atmosphere\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Setting extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'team_id', 'name', 'value'];

    public static function getFormatted($where) {
        $settings = Setting::where($where)->get();

        $formattedSettings = [];

        foreach ($settings as $setting) {
            $formattedSettings[$setting->name] = $setting['value'];
        }

        return $formattedSettings;
    }

    public static function getBySection($teamId, string $sectionName)
    {
        $settings = Setting::
        select('name', 'value')
        ->where([
            'team_id' => $teamId
        ])->where('name', 'like', DB::raw("'{$sectionName}_%'"))->get()->toArray();

        return self::mapSettings($settings);
    }

    public static function getByTeam($teamId)
    {
        $settings = Setting::
        select('name', 'value')
        ->where([
            'team_id' => $teamId
        ])->get()->toArray();

        return self::mapSettings($settings);
    }

    public static function getSettingsByUser($teamId, $userId)
    {
        $settings = Setting::
        select('name', 'value')
        ->where([
            'user_id' =>  $userId,
            'team_id' => $teamId
        ])->get()->toArray();

        return self::mapSettings($settings);
    }

    public static function mapSettings($settings) {
        $settingData = [];

        foreach ($settings as $setting) {
            $settingData[$setting['name']] = $setting['value'];
        }

        return $settingData;
    }

    public static function storeBulk($settings, $sessionData) {
        foreach ($settings as $settingName => $setting) {
            $setting = array_merge($sessionData, [
                "value" => $setting ?? " ",
                "name" => $settingName
            ]);

            Setting::updateOrCreate([
                "name" => $settingName,
                "team_id" => $sessionData["team_id"],
                "user_id" => $sessionData["user_id"]
            ], [
                'value' => $setting['value'],
            ]);
        }

        return Setting::getFormatted($sessionData);
    }
}

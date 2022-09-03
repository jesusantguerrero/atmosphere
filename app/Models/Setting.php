<?php

namespace App\Models;

use Freesgen\Atmosphere\Models\Setting as ModelsSetting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Setting extends ModelsSetting
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
}

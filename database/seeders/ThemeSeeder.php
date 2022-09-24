<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Theme::truncate();

        $themes = [
            'defaultDark' => [
                "base" => Theme::DARK,
                "config" => json_encode([
                    "primary" => "#d527b7",
                    "secondary" => "#95b3f9",
                    "accent" => "#7c5bbf",
                    "neutral" => "#232130",
                    "base-deep-1" => "#1a2237",
                    "base" => '#161c2d',
                    "base-lvl-1"=> '#1a2237',
                    "base-lvl-2" => '#212b44',
                    "base-lvl-3" =>  '#283352',
                    "info" => "#3D68F5",
                    "success" => "#79E7AE",
                    "warning" => "#D39E17",
                    "error" => "#F61909",
                    "body" => "white",
                    "body-1" => "#caced6"
                ])
            ],
            "defaultLight" => [
                "base" => Theme::LIGHT,
               "config" => json_encode([
                "primary" => "#d527b7",
                "secondary" => "#8a00d4",
                "accent" => "#f782c2",
                "neutral" => "#F3F4F6",
                "base-deep-1" => '#6b748c',
                "base" => "#F3F4F6",
                "base-lvl-1" => '#f4f5f7',
                "base-lvl-2" =>  '#caced6',
                "base-lvl-3" => "#ffffff",
                "info" => "#3D68F5",
                "success" => "#79E7AE",
                "warning" => "#D39E17",
                "error" => "#F61909",
                "body" => '#161c2d',
                "body-1" =>  '#212b44'
               ])
            ],
            'BlueLight' => [
                "base" => Theme::LIGHT,
                "config" => json_encode([
                    "primary" => "#1f436e",
                    "secondary" => "#162f4d",
                    "accent" => "#a3cdff",
                    "neutral" => "#d1e6ff",
                    "base-deep-1" => '#6b748c',
                    "base" =>  "#e3e3e3",
                    "base-lvl-1" => '#f4f5f7',
                "base-lvl-2" =>  '#caced6',
                    "base-lvl-3" => "#ffffff",
                    "info" => "#3D68F5",
                    "success" => "#79E7AE",
                    "warning" => "#D39E17",
                    "error" => "#F61909",
                    "body" => "#2E384D",
                    "body-1" => "#9298AD"
                ])
            ]
        ];

        foreach ($themes as $themeName => $theme) {
            Theme::create([
                'name' => $themeName,
                'label' => $theme['label'] ?? $themeName,
                'config' => $theme['config'],
            ]);
        }
    }
}

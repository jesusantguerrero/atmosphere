<?php

use Illuminate\Database\Seeder;
use ErnySans\Laraworld\Models\Languages;

class LanguagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return  void
     */
    public function run()
    {
        // Empty the table
        Languages::truncate();

        // Get all from the JSON file
        $JSON_languages = Languages::allJSON();

        foreach ($JSON_languages as $language) {
            Languages::create([
                'alpha3'    => ((isset($language['alpha3'])) ? $language['alpha3'] : null),
                'alpha2'    => ((isset($language['alpha2'])) ? $language['alpha2'] : null),
                'english'   => ((isset($language['english'])) ? $language['english'] : null),
            ]);
        }
    }
}

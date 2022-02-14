<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AutomationServices extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('automation_services')->insert([
            'name' => 'Google',
            'description' => "Google Services",
            'logo' => '/images/calendar.png',
        ]);

        DB::table('automation_recipes')->insert([
            'automation_service_id' => 1,
            "team_id" => 0,
            'name' => 'createItemFromCalendar',
            'description' => "create a task from google calendar",
            'service_name' => 'Google:Calendar',
            'mapper' => json_encode(["input" => ["board_id", "stage_id"]]),
            'sentence' => "When event created in {calendar} create an item in {stage} of {board}"
        ]);

        DB::table('automation_recipes')->insert([
            'automation_service_id' => 1,
            "team_id" => 0,
            'name' => 'createItemFromGmail',
            'description' => "Create a task from gmail",
            'service_name' => 'Google:Gmail',
            'mapper' => json_encode(["input" => ["board_id", "stage_id"]]),
            'sentence' => "When email received with {conditions} create an item in {stage} of {board}"
        ]);
    }
}

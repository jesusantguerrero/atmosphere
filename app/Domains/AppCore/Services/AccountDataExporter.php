<?php

namespace App\Domains\AppCore\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Builds the "download all my data" archive: the profile plus one CSV per
 * table for every space the user owns. Credentials and share links stay out.
 */
class AccountDataExporter
{
    public const DIRECTORY = 'exports';

    public const DAYS_KEPT = 7;

    private const SKIPPED_TABLES = ['integrations', 'team_invitations'];

    private const HIDDEN_COLUMNS = '/password|secret|token|credential|api_key|remember|recovery|two_factor/i';

    public function __construct(private TeamDataPurger $teamTables) {}

    /**
     * @return string The archive's path on the local disk.
     */
    public function export(User $user): string
    {
        $path = self::DIRECTORY."/{$user->id}/".Str::uuid().'.zip';
        Storage::disk('local')->makeDirectory(dirname($path));

        $zip = new ZipArchive;
        if ($zip->open(Storage::disk('local')->path($path), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Could not create {$path}");
        }

        $zip->addFromString('README.txt', $this->readme($user));
        $zip->addFromString('profile.json', json_encode(
            $user->only(['id', 'name', 'email', 'language', 'email_verified_at', 'created_at']),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        ));

        $tables = array_diff($this->teamTables->teamTables(), self::SKIPPED_TABLES);

        foreach ($user->ownedTeams as $team) {
            $folder = Str::slug($team->name).'-'.$team->id;

            foreach ($tables as $table) {
                if (DB::table($table)->where('team_id', $team->id)->exists()) {
                    $zip->addFromString("{$folder}/{$table}.csv", $this->csv($table, $team->id));
                }
            }
        }

        $zip->close();

        return $path;
    }

    public function pathFor(User $user, string $exportId): string
    {
        return self::DIRECTORY."/{$user->id}/{$exportId}.zip";
    }

    private function csv(string $table, int $teamId): string
    {
        $columns = collect(Schema::getColumnListing($table))
            ->reject(fn (string $column) => preg_match(self::HIDDEN_COLUMNS, $column))
            ->values()
            ->all();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $columns);

        $rows = DB::table($table)->select($columns)->where('team_id', $teamId);
        $rows = in_array('id', $columns, true) ? $rows->lazyById(1000) : $rows->cursor();

        foreach ($rows as $row) {
            fputcsv($handle, array_values((array) $row));
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    private function readme(User $user): string
    {
        return implode("\n", [
            'Loger data export',
            'Generated: '.now()->toDateTimeString().' for '.$user->email,
            '',
            'profile.json  Your account.',
            '<space>/      One folder per space you own, one CSV per table (transactions, accounts, budgets, meals, planner...).',
            '',
            'Passwords, tokens and connected-app credentials are not included.',
            '',
        ]);
    }
}

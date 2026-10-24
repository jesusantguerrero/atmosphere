<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessedEmail extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'team_id',
        'gmail_id',
        'message_id',
        'automation_id',
        'transaction_id',
        'status',
    ];

    public static function wasProcessed(int $teamId, string $gmailId): bool
    {
        return static::where('team_id', $teamId)
            ->where('gmail_id', $gmailId)
            ->exists();
    }

    public static function record(int $teamId, string $gmailId, ?string $messageId, int $automationId, ?int $transactionId): static
    {
        return static::create([
            'team_id' => $teamId,
            'gmail_id' => $gmailId,
            'message_id' => $messageId,
            'automation_id' => $automationId,
            'transaction_id' => $transactionId,
            'status' => 'processed',
        ]);
    }
}

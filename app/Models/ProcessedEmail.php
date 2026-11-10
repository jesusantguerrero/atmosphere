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
        'subject',
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

    public static function record(int $teamId, string $gmailId, ?string $messageId, int $automationId, ?int $transactionId, string $status = 'processed', ?string $subject = null): static
    {
        return static::create([
            'team_id' => $teamId,
            'gmail_id' => $gmailId,
            'message_id' => $messageId,
            'subject' => $subject !== null ? mb_substr(trim($subject), 0, 255) : null,
            'automation_id' => $automationId,
            'transaction_id' => $transactionId,
            'status' => $status,
        ]);
    }

    /**
     * Whether a new bank email can be the same charge as this transaction.
     *
     * A transaction entered by hand takes one email. One that already has
     * emails takes another only of a different kind (BHD's alert and its
     * notification for one charge): the same kind of email twice is two
     * purchases, like two RD$150 coffees at the same place.
     */
    public static function canAbsorb(int $transactionId, ?string $subject): bool
    {
        $linked = static::where('transaction_id', $transactionId)->pluck('subject');
        $subject = trim((string) $subject);

        // Rows saved before subjects were kept, or an email without one, cannot be told apart: keep linking.
        if ($linked->isEmpty() || $linked->contains(null) || $subject === '') {
            return true;
        }

        return ! $linked->map(fn ($linkedSubject) => trim($linkedSubject))->contains($subject);
    }
}

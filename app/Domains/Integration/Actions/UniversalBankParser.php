<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Automation\Concerns\AutomationActionContract;
use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationTaskAction;
use App\Domains\Integration\Actions\APAP\APAP;
use App\Domains\Integration\Actions\BSC\BSC;
use App\Exceptions\UnsupportedBankException;
use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Universal Bank Parser
 *
 * Automatically detects which bank sent a transaction email and routes
 * to the appropriate parser (BHD, APAP, etc.). This allows a single
 * automation to handle emails from multiple banks instead of requiring
 * separate automations per bank.
 *
 * Detection is based on:
 * - Exact email address match
 * - Email domain match
 * - Optional subject pattern match
 *
 * Configuration is stored in automation->config['bank_patterns']:
 * [
 *     'BHD' => [
 *         'email_addresses' => ['alertas@bhd.com.do'],
 *         'email_domains' => ['@bhd.com.do'],
 *         'subject_patterns' => [],
 *     ],
 * ]
 *
 * Future Migration: This class is designed to be compatible with Option 2
 * (database-driven Bank Registry System). The match() expression in
 * getBankParser() can be replaced with a service call.
 */
class UniversalBankParser implements AutomationActionContract
{
    /**
     * Handle the automation action.
     *
     * This method detects which bank sent the email and routes it to the
     * appropriate bank-specific parser.
     *
     * @param  Automation  $automation  The automation instance
     * @param  mixed  $payload  Email data from GmailReceived trigger
     * @param  AutomationTaskAction  $task  Current task
     * @param  AutomationTaskAction  $previousTask  Previous task in chain
     * @param  AutomationTaskAction  $trigger  Trigger task
     * @return array|null Transaction data array or null on failure
     */
    public static function handle(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task,
        AutomationTaskAction $previousTask,
        AutomationTaskAction $trigger
    ) {
        try {
            // Validate email data
            self::validateEmailData($payload);

            // Extract email metadata
            $fromRaw = $payload['from'] ?? '';
            $subject = $payload['subject'] ?? '';

            // Extract clean email address from "Display Name <email@domain.com>" format
            $from = self::extractEmailAddress($fromRaw);

            // Detect which bank sent this email
            $bankCode = self::detectBank($automation, $from, $subject);

            if (! $bankCode) {
                Log::warning('Email from unknown bank received', [
                    'automation_id' => $automation->id,
                    'from' => $from,
                    'subject' => $subject,
                ]);

                return null; // Skip this email
            }

            // Get the bank-specific handler (which routes to alert/notification parsers)
            $bankHandler = self::getBankHandler($bankCode);

            // Handle the email using the bank handler (it will route to appropriate parser)
            // Note: Bank handlers return arrays (they call toArray() internally)
            $result = $bankHandler::handle($automation, $payload, $task, $previousTask, $trigger);

            // Log successful parsing
            if ($result) {
                Log::info('Bank email parsed successfully', [
                    'automation_id' => $automation->id,
                    'bank_code' => $bankCode,
                    'from' => $from,
                ]);

                return $result; // Already an array
            }

            return null;

        } catch (UnsupportedBankException $e) {
            Log::error('Unsupported bank detected', [
                'automation_id' => $automation->id,
                'from' => $from ?? 'unknown',
                'exception' => $e->getMessage(),
            ]);

            return null;

        } catch (Exception $e) {
            Log::error('Error processing bank email', [
                'automation_id' => $automation->id,
                'from' => $from ?? 'unknown',
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => json_encode($payload),
            ]);

            return null;
        }
    }

    /**
     * Detect which bank sent the email based on sender and subject.
     *
     * Detection priority:
     * 1. Exact email address match
     * 2. Domain match
     * 3. Subject pattern match (if configured)
     *
     * @param  string  $from  Email sender address
     * @param  string  $subject  Email subject (optional)
     * @return string|null Bank code (BHD, APAP, etc.) or null if unknown
     */
    protected static function detectBank(Automation $automation, string $from, string $subject = ''): ?string
    {
        $bankPatterns = self::getBankConfig($automation);

        foreach ($bankPatterns as $bankCode => $config) {
            // Check exact email address match
            if (isset($config['email_addresses'])) {
                foreach ($config['email_addresses'] as $email) {
                    if (strcasecmp($from, $email) === 0) {
                        return $bankCode;
                    }
                }
            }

            // Check domain match
            if (isset($config['email_domains'])) {
                foreach ($config['email_domains'] as $domain) {
                    if (str_ends_with(strtolower($from), strtolower($domain))) {
                        return $bankCode;
                    }
                }
            }

            // Optional: Check subject patterns
            if (! empty($subject) && ! empty($config['subject_patterns'])) {
                foreach ($config['subject_patterns'] as $pattern) {
                    if (str_contains($subject, $pattern)) {
                        return $bankCode;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get the bank handler class for a given bank code.
     *
     * This method uses a match expression to return the appropriate handler
     * for each bank. The handler class determines whether to route to
     * alert or notification parsers based on email content.
     *
     * @param  string  $bankCode  Bank identifier (BHD, APAP, etc.)
     * @return string Handler class name
     *
     * @throws UnsupportedBankException If bank code is not supported
     */
    protected static function getBankHandler(string $bankCode): string
    {
        return match ($bankCode) {
            'BHD' => BHD::class,
            'APAP' => APAP::class,
            'BSC' => BSC::class,
            // Future banks can be added here
            // 'POPULAR' => Popular::class,
            // 'BANRESERVAS' => Banreservas::class,
            default => throw new UnsupportedBankException($bankCode),
        };
    }

    /**
     * Get bank patterns configuration from automation.
     *
     * Bank patterns define how to identify emails from each bank:
     * - email_addresses: Exact sender addresses
     * - email_domains: Domain patterns (e.g., @bhd.com.do)
     * - subject_patterns: Subject line patterns (optional)
     *
     * @return array Bank patterns configuration
     */
    protected static function getBankConfig(Automation $automation): array
    {
        return $automation->config['bank_patterns'] ?? [];
    }

    /**
     * Validate email data structure.
     *
     * Ensures required fields are present and valid before processing.
     *
     * @param  array  $data  Email payload data
     *
     * @throws \InvalidArgumentException If validation fails
     */
    protected static function validateEmailData($data): void
    {
        if (! isset($data['from'])) {
            throw new InvalidArgumentException('Email sender (from) is required');
        }

        // Extract email from "Display Name <email@domain.com>" format if present
        $email = self::extractEmailAddress($data['from']);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format: '.$data['from']);
        }
    }

    /**
     * Extract email address from various formats.
     *
     * Handles formats like:
     * - plain: "email@domain.com"
     * - with display name: "Name <email@domain.com>"
     * - with quotes: "\"Name\" <email@domain.com>"
     *
     * @param  string  $emailString  Email string in any format
     * @return string Clean email address
     */
    protected static function extractEmailAddress(string $emailString): string
    {
        // Match email inside angle brackets: "Display Name <email@domain.com>"
        if (preg_match('/<(.+?)>/', $emailString, $matches)) {
            return trim($matches[1]);
        }

        // Already a plain email address
        return trim($emailString);
    }

    /**
     * Get the name of this action.
     */
    public function getName(): string
    {
        return 'UniversalBankParser';
    }

    /**
     * Get the label for this action.
     */
    public function label(): string
    {
        return 'Universal Bank Parser';
    }

    /**
     * Get the description of this action.
     */
    public function getDescription(): string
    {
        return 'Automatically detects and parses transaction emails from multiple banks (BHD, APAP, BSC, etc.) in a single automation';
    }

    /**
     * Get all bank patterns configuration for automation setup.
     *
     * This method serves as the single source of truth for bank email patterns,
     * pulling configuration from each bank's class.
     *
     * @return array All bank patterns indexed by bank code
     */
    public static function getAllBankPatterns(): array
    {
        return [
            'BHD' => BHD::getEmailPatterns(),
            'APAP' => \App\Domains\Integration\Actions\APAP\APAP::getEmailPatterns(),
            'BSC' => \App\Domains\Integration\Actions\BSC\BSC::getEmailPatterns(),
        ];
    }

    /**
     * Build Gmail query string from all bank email addresses.
     *
     * @return string Gmail query in format: from:(email1 OR email2 OR ...)
     */
    public static function buildGmailQuery(): string
    {
        $allEmails = collect(self::getAllBankPatterns())
            ->pluck('email_addresses')
            ->flatten()
            ->unique()
            ->values()
            ->all();

        return 'from:('.implode(' OR ', $allEmails).')';
    }
}

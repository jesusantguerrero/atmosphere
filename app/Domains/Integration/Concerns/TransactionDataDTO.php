<?php

namespace App\Domains\Integration\Concerns;

use Spatie\LaravelData\Data;

class TransactionDataDTO extends Data
{
    const TYPE_PURCHASE = 'PURCHASE';

    const TYPE_CASH_WITHDRAWAL = 'CASH_WITHDRAWAL';

    const TYPE_HOLD = 'HOLD';

    const TYPE_INTERNAL_TRANSFER = 'INTERNAL_TRANSFER';

    public int $id;

    public string $date;

    public string $payee;

    public string $description;

    public string $categoryGroup;

    public string $category;

    public string $amount;

    public string $currencyCode;

    public ?string $messageId;

    public ?string $productName;

    public ?string $productCode;

    public ?string $productBrand;

    public ?string $destinationProductName;

    public ?string $destinationProductCode;

    public ?string $transactionType;

    public function __construct($data)
    {
        $this->id = $data['id'];
        $this->date = $data['date'];
        $this->payee = self::redactCardNumbers($data['payee']);
        $this->description = self::redactCardNumbers($data['description']);
        $this->categoryGroup = $data['categoryGroup'];
        $this->category = $data['category'];
        $this->amount = $data['amount'];
        $this->currencyCode = $data['currencyCode'];
        $this->messageId = $data['messageId'] ?? null;
        $this->productName = $data['productName'] ?? null;
        $this->productCode = $data['productCode'] ?? null;
        $this->productBrand = $data['productBrand'] ?? null;
        $this->destinationProductName = $data['destinationProductName'] ?? null;
        $this->destinationProductCode = $data['destinationProductCode'] ?? null;
        $this->transactionType = $data['transactionType'] ?? null;
    }

    /**
     * Strip any 13-19 digit sequence (with optional spaces/dashes) from text.
     * Why: some banks occasionally leak the full PAN into merchant names/descriptions;
     * masked formats like "53*************3861" are untouched because they contain asterisks.
     */
    private static function redactCardNumbers(?string $text): string
    {
        if (! $text) {
            return '';
        }

        $cleaned = preg_replace('/\b(?:\d[ -]?){12,18}\d\b/', '', $text);

        return trim(preg_replace('/\s+/', ' ', $cleaned));
    }
}

<?php
namespace App\Domains\Integration\Concerns;

use Spatie\LaravelData\Data;

class TransactionDataDTO extends Data {
    public int $id;
    public string $date;
    public string $payee;
    public string $description;
    public string $categoryGroup;
    public string $category;
    public string $amount;
    public string $currencyCode;

    public function __construct($data)
    {
        $this->id = $data['id'];
        $this->date = $data['date'];
        $this->payee = $data['payee'];
        $this->description = $data['description'];;
        $this->categoryGroup = $data['categoryGroup'];
        $this->category = $data['category'];
        $this->amount = $data['amount'];
        $this->currencyCode = $data['currencyCode'];
    }
}

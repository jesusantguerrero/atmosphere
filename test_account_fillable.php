<?php

require_once 'vendor/autoload.php';

// Test if the Account model has the correct fillable fields
$account = new \App\Models\Account();

echo "Testing Account model fillable fields:\n";
echo "Fillable fields: " . implode(', ', $account->getFillable()) . "\n";

echo "\nTesting if multi-currency fields are fillable:\n";
echo "is_multi_currency fillable: " . (in_array('is_multi_currency', $account->getFillable()) ? 'YES' : 'NO') . "\n";
echo "secondary_currencies fillable: " . (in_array('secondary_currencies', $account->getFillable()) ? 'YES' : 'NO') . "\n";

echo "\nTesting casts:\n";
$casts = $account->getCasts();
echo "is_multi_currency cast: " . ($casts['is_multi_currency'] ?? 'NOT SET') . "\n";
echo "secondary_currencies cast: " . ($casts['secondary_currencies'] ?? 'NOT SET') . "\n";

echo "\nTesting methods:\n";
$account->setAttribute('currency_code', 'USD');
$account->setAttribute('is_multi_currency', true);
$account->setAttribute('secondary_currencies', ['DOP', 'EUR']);

echo "Primary currency: " . $account->getPrimaryCurrency() . "\n";
echo "Secondary currencies: " . implode(', ', $account->getSecondaryCurrencies()) . "\n";
echo "Is multi-currency: " . ($account->isMultiCurrency() ? 'YES' : 'NO') . "\n";
echo "All supported currencies: " . implode(', ', $account->getAllSupportedCurrencies()) . "\n";

echo "\nTesting currency support:\n";
echo "Supports USD: " . ($account->supportsCurrency('USD') ? 'YES' : 'NO') . "\n";
echo "Supports DOP: " . ($account->supportsCurrency('DOP') ? 'YES' : 'NO') . "\n";
echo "Supports JPY: " . ($account->supportsCurrency('JPY') ? 'YES' : 'NO') . "\n";

echo "\nTesting empty secondary currencies:\n";
$account->setAttribute('secondary_currencies', []);
echo "Empty secondary currencies - Supports JPY: " . ($account->supportsCurrency('JPY') ? 'YES' : 'NO') . "\n";

echo "\nTesting null secondary currencies:\n";
$account->setAttribute('secondary_currencies', null);
echo "Null secondary currencies - Supports JPY: " . ($account->supportsCurrency('JPY') ? 'YES' : 'NO') . "\n";

echo "\nTest completed successfully!\n";
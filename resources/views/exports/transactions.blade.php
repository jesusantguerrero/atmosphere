<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions Export</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #111;
            padding: 24px;
        }

        header {
            border-bottom: 2px solid #111;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        header h1 {
            font-size: 18px;
            font-weight: bold;
        }

        header p {
            font-size: 10px;
            color: #555;
            margin-top: 4px;
        }

        .summary {
            display: flex;
            gap: 24px;
            margin-bottom: 16px;
            padding: 10px 12px;
            background: #f5f5f5;
            border: 1px solid #ddd;
        }

        .summary-item {
            flex: 1;
            text-align: center;
        }

        .summary-item .label {
            font-size: 9px;
            text-transform: uppercase;
            color: #666;
            letter-spacing: 0.5px;
        }

        .summary-item .value {
            font-size: 13px;
            font-weight: bold;
            margin-top: 2px;
        }

        .income { color: #166534; }
        .expense { color: #991b1b; }
        .net { color: #1e40af; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        thead th {
            background: #111;
            color: #fff;
            text-align: left;
            padding: 6px 8px;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        tbody tr:hover {
            background: #f0f0f0;
        }

        tbody td {
            padding: 5px 8px;
            border-bottom: 1px solid #e5e5e5;
            vertical-align: top;
        }

        .amount-income {
            color: #166534;
            font-weight: 600;
        }

        .amount-expense {
            color: #991b1b;
            font-weight: 600;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        footer {
            margin-top: 16px;
            border-top: 1px solid #ddd;
            padding-top: 8px;
            font-size: 9px;
            color: #888;
            text-align: right;
        }
    </style>
</head>
<body>
    <header>
        <h1>Transaction Report{{ $accountName ? ' — ' . $accountName : '' }}</h1>
        @if($startDate && $endDate)
            <p>Period: {{ $startDate }} to {{ $endDate }}</p>
        @else
            <p>All transactions</p>
        @endif
        <p>Generated: {{ now()->format('Y-m-d H:i') }}</p>
    </header>

    <div class="summary">
        <div class="summary-item">
            <div class="label">Income</div>
            <div class="value income">{{ number_format($income, 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Expenses</div>
            <div class="value expense">{{ number_format($expenses, 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Net</div>
            <div class="value net">{{ number_format($net, 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Transactions</div>
            <div class="value">{{ count($transactions) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Payee</th>
                <th>Description</th>
                <th>Category</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td style="white-space: nowrap;">{{ $transaction->date }}</td>
                    <td>{{ $transaction->payee_name ?? '—' }}</td>
                    <td>{{ $transaction->description ?? '—' }}</td>
                    <td>{{ $transaction->category_name ?? '—' }}</td>
                    <td class="text-right {{ $transaction->direction === 'DEPOSIT' ? 'amount-income' : 'amount-expense' }}">
                        {{ $transaction->direction === 'DEPOSIT' ? '+' : '-' }}
                        {{ $transaction->currency_code ?? '' }}
                        {{ number_format(abs($transaction->total), 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 16px; color: #888;">
                        No transactions found for this period.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <footer>
        Exported from Loger &mdash; {{ now()->format('Y-m-d') }}
    </footer>
</body>
</html>

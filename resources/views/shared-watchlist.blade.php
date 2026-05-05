<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $name }} — Loger</title>
    <meta name="description" content="Shared spending report from Loger">
    <link rel="icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700&display=swap">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Nunito', 'sans-serif'] },
                    colors: {
                        primary: '#F37EA1',
                        'primary-dark': '#d4617f',
                    }
                }
            }
        }
    </script>
    <style>body { font-family: 'Nunito', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen">
    @php
        $monthTotal = (float) ($month['total'] ?? 0);
        $prevMonthTotal = (float) ($prevMonth->total ?? 0);
        $hasTarget = $target > 0;
        $ratio = $hasTarget ? $monthTotal / $target : 0;
        $statusClass = ! $hasTarget ? 'text-gray-700' : ($ratio > 1 ? 'text-red-500' : ($ratio > 0.7 ? 'text-amber-500' : 'text-emerald-500'));
        $statusBg = ! $hasTarget ? 'bg-gray-200' : ($ratio > 1 ? 'bg-red-500' : ($ratio > 0.7 ? 'bg-amber-500' : 'bg-emerald-500'));
        $variancePct = $prevMonthTotal > 0 ? round((($monthTotal - $prevMonthTotal) / $prevMonthTotal) * 100, 1) : null;
        $maxBarValue = max(collect($monthlySeries)->pluck('total')->map(fn($v) => (float)$v)->max() ?: 0, $hasTarget ? $target : 0, 1);
    @endphp

    <main class="max-w-2xl mx-auto px-4 py-10">
        <header class="mb-6">
            <p class="text-xs text-gray-400 uppercase tracking-wide font-medium">{{ ucfirst($type) }} report</p>
            <h1 class="text-3xl font-bold text-gray-900 mt-1">{{ $name }}</h1>
        </header>

        <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-4">
            <div class="flex items-baseline justify-between gap-4">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide font-medium">This month</p>
                    <p class="text-3xl font-extrabold {{ $statusClass }} mt-1">
                        DOP {{ number_format($monthTotal, 2) }}
                    </p>
                </div>
                @if ($hasTarget)
                    <div class="text-right">
                        <p class="text-xs text-gray-400 uppercase tracking-wide font-medium">Target</p>
                        <p class="text-lg font-semibold text-gray-700 mt-1">
                            DOP {{ number_format($target, 2) }}
                        </p>
                    </div>
                @endif
            </div>
            @if ($hasTarget)
                <div class="mt-4 h-2 rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full {{ $statusBg }} transition-all"
                         style="width: {{ min($ratio, 1) * 100 }}%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-2">{{ round($ratio * 100) }}% of target</p>
            @endif
            @if ($variancePct !== null)
                <p class="text-xs mt-3 {{ $variancePct >= 0 ? 'text-red-500' : 'text-emerald-500' }}">
                    {{ $variancePct >= 0 ? '+' : '' }}{{ $variancePct }}% vs last month
                    <span class="text-gray-400">(DOP {{ number_format($prevMonthTotal, 2) }})</span>
                </p>
            @endif
        </section>

        @if (count($monthlySeries))
            <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-4">
                <h2 class="text-sm font-bold text-gray-700 mb-3">Last 12 months</h2>
                <div class="flex items-end justify-between gap-1 h-32">
                    @foreach ($monthlySeries as $point)
                        @php
                            $pTotal = (float) $point['total'];
                            $pRatio = $hasTarget ? $pTotal / $target : 0;
                            $pColor = ! $hasTarget ? 'bg-indigo-300' : ($pRatio > 1 ? 'bg-red-400' : ($pRatio > 0.7 ? 'bg-amber-400' : 'bg-emerald-400'));
                            $h = $maxBarValue > 0 ? max(($pTotal / $maxBarValue) * 100, 2) : 2;
                        @endphp
                        <div class="flex-1 flex flex-col items-center gap-1">
                            <div class="w-full {{ $pColor }} rounded-t" style="height: {{ $h }}%"></div>
                            <p class="text-[10px] text-gray-400 truncate w-full text-center">
                                {{ \Carbon\Carbon::parse($point['month'])->format('M') }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <footer class="mt-8 text-center text-xs text-gray-400">
            <p>This is a shared report — read-only, no transactions exposed.</p>
            <p class="mt-2">
                Built with <a href="/" class="text-primary hover:text-primary-dark font-medium">Loger</a>
            </p>
        </footer>
    </main>
</body>
</html>

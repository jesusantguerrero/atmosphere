<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $plan->name }} — Loger</title>
    <meta name="description" content="Shared shopping list from Loger">
    <link rel="icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700&display=swap">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Nunito', 'sans-serif'],
                    },
                    colors: {
                        primary: '#F37EA1',
                        'primary-dark': '#d4617f',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Nunito', sans-serif; }
        .item-done .item-title { text-decoration: line-through; opacity: 0.45; }
        .checkbox-custom {
            width: 1.5rem;
            height: 1.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .checkbox-custom.checked {
            background-color: #F37EA1;
            border-color: #F37EA1;
        }
        .checkbox-custom.checked svg {
            display: block;
        }
        .checkbox-custom svg {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <header class="bg-white border-b border-gray-100 sticky top-0 z-10 shadow-sm">
        <div class="max-w-lg mx-auto px-4 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-gray-900">{{ $plan->name }}</h1>
                <p class="text-xs text-gray-400 mt-0.5">Tap items to check them off</p>
            </div>
            <div id="progress-badge" class="text-right">
                <span id="progress-text" class="text-sm font-semibold text-primary"></span>
            </div>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 py-6 pb-24">
        @forelse ($stages as $stage)
            <section class="mb-6">
                @if (count($stages) > 1)
                    <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3 px-1">
                        {{ $stage['name'] }}
                    </h2>
                @endif

                <ul class="space-y-2">
                    @forelse ($stage['items'] as $item)
                        <li
                            id="item-{{ $item['id'] }}"
                            class="flex items-center gap-3 bg-white rounded-xl px-4 py-3.5 shadow-sm cursor-pointer select-none {{ $item['is_done'] ? 'item-done' : '' }}"
                            onclick="toggleItem('{{ $token }}', {{ $item['id'] }}, this)"
                        >
                            <div class="checkbox-custom {{ $item['is_done'] ? 'checked' : '' }}">
                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <span class="item-title text-gray-800 font-medium text-base leading-snug">
                                {{ $item['title'] }}
                            </span>
                        </li>
                    @empty
                        <li class="text-center text-gray-400 py-6 text-sm">No items in this section.</li>
                    @endforelse
                </ul>
            </section>
        @empty
            <div class="text-center py-20">
                <p class="text-gray-400 text-lg">This list is empty.</p>
            </div>
        @endforelse
    </main>

    <footer class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100">
        <div class="max-w-lg mx-auto px-4 py-3 flex items-center justify-center gap-2">
            <img src="/logo.svg" alt="Loger" class="h-5 w-auto opacity-40">
            <span class="text-xs text-gray-400">Shared from <strong>Loger</strong></span>
        </div>
    </footer>

    <script>
        const toggleRoute = (token, itemId) => `/shared/list/${token}/toggle/${itemId}`;
        let totalItems = 0;
        let doneItems = 0;

        function updateProgress() {
            const all = document.querySelectorAll('[id^="item-"]');
            totalItems = all.length;
            doneItems = document.querySelectorAll('[id^="item-"].item-done').length;
            const el = document.getElementById('progress-text');
            if (el) {
                el.textContent = totalItems > 0 ? `${doneItems}/${totalItems}` : '';
            }
        }

        async function toggleItem(token, itemId, el) {
            const checkbox = el.querySelector('.checkbox-custom');

            // Optimistic UI update
            const isDone = el.classList.contains('item-done');
            el.classList.toggle('item-done', !isDone);
            checkbox.classList.toggle('checked', !isDone);
            updateProgress();

            try {
                const response = await fetch(toggleRoute(token, itemId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                });

                if (!response.ok) {
                    // Revert on failure
                    el.classList.toggle('item-done', isDone);
                    checkbox.classList.toggle('checked', isDone);
                    updateProgress();
                }
            } catch (e) {
                // Revert on network error
                el.classList.toggle('item-done', isDone);
                checkbox.classList.toggle('checked', isDone);
                updateProgress();
            }
        }

        document.addEventListener('DOMContentLoaded', updateProgress);
    </script>
</body>
</html>

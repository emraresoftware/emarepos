<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Emare POS') — Emare POS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50:  '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                            950: '#1e1b4b',
                        }
                    },
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .gradient-text {
            background: linear-gradient(135deg, #4f46e5, #7c3aed, #6d28d9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f8fafc; }
        ::-webkit-scrollbar-thumb { background: #c7d2fe; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #6366f1; }
        /* Print styles */
        @media print {
            .no-print { display: none !important; }
        }
    </style>
    @stack('styles')
</head>
<body class="h-full bg-gray-50 font-sans antialiased text-gray-800">
    <div x-data="{ sidebarOpen: true, sidebarMobile: false }" class="h-full flex">
        <!-- Sidebar -->
        <aside class="no-print transition-all duration-300 bg-white border-r border-gray-200 flex flex-col shadow-sm"
               :class="sidebarOpen ? 'w-60' : 'w-[68px]'"
               x-cloak>
            <!-- Logo -->
            <div class="p-3.5 border-b border-gray-100 flex items-center" :class="sidebarOpen ? 'justify-between' : 'justify-center'">
                <div x-show="sidebarOpen" class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-500 to-purple-600 flex items-center justify-center shadow-lg shadow-brand-500/30">
                        <span class="text-white font-bold text-sm">EP</span>
                    </div>
                    <span class="text-lg font-bold text-gray-900">Emare <span class="gradient-text">POS</span></span>
                </div>
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-400 hover:text-brand-600 p-1.5 rounded-lg hover:bg-brand-50 transition-colors">
                    <i class="fas text-xs" :class="sidebarOpen ? 'fa-chevron-left' : 'fa-chevron-right'"></i>
                </button>
            </div>

            <!-- Nav Links -->
            <nav class="flex-1 py-3 overflow-y-auto space-y-0.5">
                @php
                    $navItems = [
                        ['route' => 'pos.dashboard', 'icon' => 'fa-chart-line', 'label' => 'Özet'],
                        ['route' => 'pos.branches', 'icon' => 'fa-building', 'label' => 'Şubeler'],
                        ['route' => 'pos.orders', 'icon' => 'fa-clipboard-list', 'label' => 'Siparişler'],
                        ['route' => 'pos.sales', 'icon' => 'fa-bolt', 'label' => 'Hızlı Satış'],
                        ['route' => 'pos.tables', 'icon' => 'fa-utensils', 'label' => 'Masalar'],
                        ['route' => 'pos.kitchen', 'icon' => 'fa-fire-burner', 'label' => 'Mutfak'],
                        ['route' => 'pos.day-operations', 'icon' => 'fa-calendar-day', 'label' => 'Gün İşlemleri'],
                        ['route' => 'pos.cash-register', 'icon' => 'fa-cash-register', 'label' => 'Kasa'],
                        ['route' => 'pos.cash-report', 'icon' => 'fa-file-invoice-dollar', 'label' => 'Kasa Raporu'],
                        ['route' => 'pos.sales.list', 'icon' => 'fa-receipt', 'label' => 'Satışlar'],
                        ['route' => 'pos.customers', 'icon' => 'fa-users', 'label' => 'Müşteriler'],
                        ['route' => 'pos.firms', 'icon' => 'fa-handshake', 'label' => 'Firmalar'],
                        ['route' => 'pos.categories', 'icon' => 'fa-layer-group', 'label' => 'Kategoriler'],
                        ['route' => 'pos.products', 'icon' => 'fa-boxes-stacked', 'label' => 'Ürünler'],
                        ['route' => 'pos.users', 'icon' => 'fa-user-gear', 'label' => 'Kullanıcılar'],
                        ['route' => 'pos.reports', 'icon' => 'fa-chart-pie', 'label' => 'Raporlar'],
                        ['route' => 'pos.stock', 'icon' => 'fa-warehouse', 'label' => 'Depo'],
                        ['route' => 'pos.income-expense', 'icon' => 'fa-scale-balanced', 'label' => 'Gelir/Gider'],
                        ['route' => 'pos.staff', 'icon' => 'fa-id-badge', 'label' => 'Personel'],
                        ['route' => 'pos.hardware', 'icon' => 'fa-screwdriver-wrench', 'label' => 'Donanım'],
                        ['route' => 'pos.feedback', 'icon' => 'fa-comments', 'label' => 'Geri Bildirimler'],
                        ['route' => 'pos.settings', 'icon' => 'fa-gear', 'label' => 'Ayarlar'],
                    ];
                @endphp

                @foreach($navItems as $item)
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2.5 mx-2 rounded-xl text-sm font-medium transition-all duration-200
                              {{ request()->routeIs($item['route'].'*')
                                    ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-white shadow-md shadow-brand-500/25'
                                    : 'text-gray-600 hover:text-brand-700 hover:bg-brand-50' }}"
                       :class="sidebarOpen ? '' : 'justify-center'"
                       title="{{ $item['label'] }}">
                        <i class="fas {{ $item['icon'] }} w-5 text-center text-[13px]"></i>
                        <span x-show="sidebarOpen">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <!-- User & Logout -->
            <div class="border-t border-gray-100 p-3.5">
                <div x-show="sidebarOpen" class="mb-2">
                    <div class="text-sm text-gray-900 font-semibold">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-gray-500">{{ auth()->user()->role?->name ?? 'Yönetici' }}</div>
                </div>
                @if(auth()->user()->is_super_admin)
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center gap-2 text-brand-500 hover:text-brand-700 text-sm rounded-lg hover:bg-brand-50 px-2 py-1.5 transition-colors mb-2 border border-brand-200"
                       :class="sidebarOpen ? '' : 'justify-center'">
                        <i class="fas fa-shield-halved w-5 text-center text-xs"></i>
                        <span x-show="sidebarOpen">Admin Panel</span>
                    </a>
                @endif
                <form method="POST" action="{{ route('pos.logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-2 text-gray-400 hover:text-red-500 text-sm w-full rounded-lg hover:bg-red-50 px-2 py-1.5 transition-colors"
                            :class="sidebarOpen ? '' : 'justify-center'">
                        <i class="fas fa-sign-out-alt w-5 text-center"></i>
                        <span x-show="sidebarOpen">Çıkış</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            @yield('content')
        </main>
    </div>

    <!-- Toast Notification -->
    <div x-data="toast()" x-on:show-toast.window="show($event.detail)" x-cloak>
        <template x-for="t in toasts" :key="t.id">
            <div
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed top-4 right-4 z-50 px-5 py-3.5 rounded-2xl shadow-xl text-white text-sm max-w-sm"
                 :class="t.type === 'success' ? 'bg-green-500 shadow-green-500/30' : t.type === 'error' ? 'bg-red-500 shadow-red-500/30' : 'bg-brand-500 shadow-brand-500/30'">
                <div class="flex items-center gap-2">
                    <i class="fas" :class="t.type === 'success' ? 'fa-check-circle' : t.type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'"></i>
                    <span x-text="t.message"></span>
                </div>
            </div>
        </template>
    </div>

    <script>
        // CSRF token for AJAX
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        function posAjax(url, body = {}, method = 'POST') {
            // body bir fetch options objesi ise eski imzayı destekle
            const isOptions = body && (body.method || body.headers || body.body !== undefined);
            const fetchOpts = isOptions ? body : {
                method,
                body: method !== 'GET' ? JSON.stringify(body) : undefined,
            };
            return fetch(url, {
                ...fetchOpts,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    ...(fetchOpts.headers || {}),
                },
            }).then(async r => {
                const data = await r.json();
                if (!r.ok) throw { status: r.status, ...data };
                return data;
            });
        }

        function toast() {
            return {
                toasts: [],
                show(detail) {
                    const id = Date.now() + Math.random();
                    this.toasts.push({ id, message: detail.message, type: detail.type || 'success' });
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(x => x.id !== id);
                    }, 3000);
                }
            };
        }

        function showToast(message, type = 'success') {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message, type } }));
        }

        // Format currency
        function formatCurrency(amount) {
            return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(amount);
        }
    </script>
    @stack('scripts')

    {{-- Emare Geri Bildirim Widget --}}
    <script src="/feedback_widget.js" data-api="/api/feedback"></script>
</body>
</html>

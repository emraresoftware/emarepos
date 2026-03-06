<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Emare Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: { 50:'#eef2ff',100:'#e0e7ff',200:'#c7d2fe',300:'#a5b4fc',400:'#818cf8',500:'#6366f1',600:'#4f46e5',700:'#4338ca',800:'#3730a3',900:'#312e81',950:'#1e1b4b' }
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #4338ca; border-radius: 4px; }
    </style>
    @stack('styles')
</head>
<body class="h-full bg-slate-900 font-sans antialiased text-slate-200">
<div x-data="{ sidebarOpen: true }" class="h-full flex">

    {{-- ─── Sol Kenar Çubuğu ─── --}}
    <aside class="transition-all duration-300 bg-slate-950 border-r border-slate-800 flex flex-col shadow-xl"
           :class="sidebarOpen ? 'w-60' : 'w-16'">

        {{-- Logo --}}
        <div class="p-4 border-b border-slate-800 flex items-center"
             :class="sidebarOpen ? 'justify-between' : 'justify-center'">
            <div x-show="sidebarOpen" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-purple-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-shield-halved text-white text-xs"></i>
                </div>
                <span class="font-bold text-white text-sm">Emare <span class="text-brand-400">Admin</span></span>
            </div>
            <button @click="sidebarOpen = !sidebarOpen"
                    class="text-slate-400 hover:text-brand-400 p-1.5 rounded-lg hover:bg-slate-800 transition-colors">
                <i class="fas text-xs" :class="sidebarOpen ? 'fa-chevron-left' : 'fa-chevron-right'"></i>
            </button>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 py-4 space-y-1 overflow-y-auto px-2">
            @php
                $adminNav = [
                    ['route' => 'admin.dashboard',  'icon' => 'fa-gauge-high',    'label' => 'Genel Bakış'],
                    ['route' => 'admin.tenants',     'icon' => 'fa-building-user', 'label' => 'Tenantlar'],
                    ['route' => 'admin.users',       'icon' => 'fa-users',         'label' => 'Kullanıcılar'],
                    ['route' => 'admin.feedbacks',   'icon' => 'fa-comments',      'label' => 'Geri Bildirimler'],
                ];
            @endphp
            @foreach($adminNav as $n)
                <a href="{{ route($n['route']) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all
                          {{ request()->routeIs($n['route'].'*')
                              ? 'bg-gradient-to-r from-brand-600 to-purple-700 text-white shadow-lg shadow-brand-900/40'
                              : 'text-slate-400 hover:text-white hover:bg-slate-800' }}"
                   :class="sidebarOpen ? '' : 'justify-center'"
                   title="{{ $n['label'] }}">
                    <i class="fas {{ $n['icon'] }} w-4 text-center text-[12px]"></i>
                    <span x-show="sidebarOpen">{{ $n['label'] }}</span>
                </a>
            @endforeach

            {{-- POS'a dön --}}
            <div class="pt-4 mt-4 border-t border-slate-800">
                <a href="{{ route('pos.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500 hover:text-white hover:bg-slate-800 transition-all"
                   :class="sidebarOpen ? '' : 'justify-center'">
                    <i class="fas fa-arrow-left w-4 text-center text-[12px]"></i>
                    <span x-show="sidebarOpen">POS'a Dön</span>
                </a>
            </div>
        </nav>

        {{-- Kullanıcı --}}
        <div class="border-t border-slate-800 p-3">
            <div x-show="sidebarOpen" class="mb-2 px-1">
                <div class="text-xs font-semibold text-white">{{ auth()->user()->name }}</div>
                <div class="text-[10px] text-brand-400 font-medium">Süper Admin</div>
            </div>
            <form method="POST" action="{{ route('pos.logout') }}">
                @csrf
                <button type="submit"
                        class="flex items-center gap-2 text-slate-500 hover:text-red-400 text-xs w-full rounded-lg hover:bg-slate-800 px-2 py-1.5 transition-colors"
                        :class="sidebarOpen ? '' : 'justify-center'">
                    <i class="fas fa-sign-out-alt w-4 text-center"></i>
                    <span x-show="sidebarOpen">Çıkış</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- ─── İçerik ─── --}}
    <main class="flex-1 flex flex-col overflow-hidden">
        {{-- Topbar --}}
        <div class="bg-slate-900 border-b border-slate-800 px-6 py-3.5 flex items-center justify-between shrink-0">
            <div>
                <h1 class="text-base font-semibold text-white">@yield('page-title', 'Admin Panel')</h1>
                <p class="text-xs text-slate-500">@yield('page-sub', 'Emare Süper Yönetici Paneli')</p>
            </div>
            <div class="flex items-center gap-3">
                @if(session('success'))
                    <span class="text-xs bg-green-500/20 text-green-400 border border-green-500/30 px-3 py-1.5 rounded-lg">
                        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
                    </span>
                @endif
                <span class="text-xs text-slate-500">{{ now()->format('d.m.Y H:i') }}</span>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </div>
    </main>
</div>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    function posAjax(url, body = {}, method = 'POST') {
        return fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: method !== 'GET' ? JSON.stringify(body) : undefined,
        }).then(async r => { const d = await r.json(); if (!r.ok) throw d; return d; });
    }
</script>
@stack('scripts')
</body>
</html>

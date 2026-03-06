@extends('admin.layout')

@section('title', 'Genel Bakış')
@section('page-title', 'Genel Bakış')
@section('page-sub', 'Platform geneli istatistikler')

@section('content')

{{-- ─── İstatistik Kartları ─── --}}
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">

    @php
        $kartlar = [
            ['label' => 'Tenant',           'val' => $stats['tenant_count'],   'icon' => 'fa-building-user', 'color' => 'from-blue-600 to-indigo-700'],
            ['label' => 'Kullanıcı',        'val' => $stats['user_count'],     'icon' => 'fa-users',         'color' => 'from-purple-600 to-violet-700'],
            ['label' => 'Bugün Satış',      'val' => $stats['sale_today'],     'icon' => 'fa-receipt',       'color' => 'from-emerald-600 to-teal-700'],
            ['label' => 'Toplam Satış (₺)', 'val' => number_format($stats['sale_total'], 0, ',', '.'), 'icon' => 'fa-coins', 'color' => 'from-amber-500 to-orange-600'],
            ['label' => 'Açık Feedback',    'val' => $stats['feedback_open'],  'icon' => 'fa-comment-dots',  'color' => 'from-rose-600 to-pink-700'],
            ['label' => 'Toplam Feedback',  'val' => $stats['feedback_total'], 'icon' => 'fa-comments',      'color' => 'from-cyan-600 to-sky-700'],
        ];
    @endphp

    @foreach($kartlar as $k)
        <div class="bg-slate-800 border border-slate-700 rounded-xl p-4">
            <div class="w-9 h-9 rounded-lg bg-gradient-to-br {{ $k['color'] }} flex items-center justify-center mb-3 shadow-lg">
                <i class="fas {{ $k['icon'] }} text-white text-xs"></i>
            </div>
            <div class="text-2xl font-bold text-white">{{ $k['val'] }}</div>
            <div class="text-xs text-slate-400 mt-0.5">{{ $k['label'] }}</div>
        </div>
    @endforeach
</div>

{{-- ─── Alt Grid ─── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Plan Dağılımı --}}
    <div class="bg-slate-800 border border-slate-700 rounded-xl p-5">
        <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-layer-group text-brand-400"></i> Plan Dağılımı
        </h3>
        <div class="space-y-3">
            @forelse($planDagilim as $plan)
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                        <span class="text-sm text-slate-300">{{ $plan->name }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-32 h-1.5 bg-slate-700 rounded-full overflow-hidden">
                            @php
                                $total = $planDagilim->sum('tenants_count') ?: 1;
                                $pct = round($plan->tenants_count / $total * 100);
                            @endphp
                            <div class="h-full bg-gradient-to-r from-brand-500 to-purple-500 rounded-full"
                                 style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="text-sm font-semibold text-white w-6 text-right">{{ $plan->tenants_count }}</span>
                    </div>
                </div>
            @empty
                <p class="text-slate-500 text-sm">Plan verisi yok.</p>
            @endforelse
        </div>
    </div>

    {{-- Son Tenant Kayıtları --}}
    <div class="bg-slate-800 border border-slate-700 rounded-xl p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                <i class="fas fa-clock-rotate-left text-brand-400"></i> Son Kayıtlar
            </h3>
            <a href="{{ route('admin.tenants') }}"
               class="text-xs text-brand-400 hover:text-brand-300 font-medium">Tümünü gör →</a>
        </div>
        <div class="space-y-2.5">
            @forelse($sonTenantlar as $t)
                <div class="flex items-center justify-between py-2 border-b border-slate-700/50 last:border-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-slate-700 flex items-center justify-center text-xs font-bold text-brand-400">
                            {{ mb_substr($t->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-200">{{ $t->name }}</p>
                            <p class="text-[10px] text-slate-500">{{ $t->plan?->name ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-medium
                            {{ $t->status === 'active' ? 'bg-emerald-500/20 text-emerald-400' :
                               ($t->status === 'trial' ? 'bg-amber-500/20 text-amber-400' : 'bg-red-500/20 text-red-400') }}">
                            {{ ucfirst($t->status) }}
                        </span>
                        <p class="text-[10px] text-slate-600 mt-0.5">{{ $t->created_at->format('d.m.Y') }}</p>
                    </div>
                </div>
            @empty
                <p class="text-slate-500 text-sm">Kayıt yok.</p>
            @endforelse
        </div>
    </div>
</div>

@endsection

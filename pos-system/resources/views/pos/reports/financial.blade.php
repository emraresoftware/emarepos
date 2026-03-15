@extends('pos.layouts.app')
@section('title', 'Finansal Hareketler')

@section('content')
<div class="flex-1 overflow-y-auto p-4 space-y-4">

    {{-- Başlık + Filtreler --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Finansal Hareketler</h1>
            <p class="text-sm text-gray-500">Satış · Gelir · Gider · Tahsilat · Borç — tüm hareketler</p>
        </div>
        <form method="GET" action="{{ route('pos.reports.financial') }}" class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-1.5 bg-white border border-gray-200 rounded-xl px-3 py-2">
                <i class="fas fa-calendar-alt text-gray-400 text-xs"></i>
                <input type="date" name="start_date" value="{{ $startDate }}" class="bg-transparent text-sm text-gray-900 focus:outline-none w-32">
                <span class="text-gray-400 text-xs">—</span>
                <input type="date" name="end_date" value="{{ $endDate }}" class="bg-transparent text-sm text-gray-900 focus:outline-none w-32">
            </div>
            <select name="type" class="bg-white border border-gray-200 text-sm text-gray-700 rounded-xl px-3 py-2 focus:outline-none focus:border-brand-400">
                <option value="all" {{ $typeFilter === 'all' ? 'selected' : '' }}>Tüm İşlemler</option>
                <option value="sale" {{ $typeFilter === 'sale' ? 'selected' : '' }}>Yalnız Satışlar</option>
                <option value="income" {{ $typeFilter === 'income' ? 'selected' : '' }}>Yalnız Gelirler</option>
                <option value="expense" {{ $typeFilter === 'expense' ? 'selected' : '' }}>Yalnız Giderler</option>
                <option value="account" {{ $typeFilter === 'account' ? 'selected' : '' }}>Yalnız Hesap Hareketleri</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-xl text-sm font-medium transition-colors">
                <i class="fas fa-filter mr-1"></i> Filtrele
            </button>
            <button type="button" onclick="window.print()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-medium transition-colors">
                <i class="fas fa-print mr-1"></i> Yazdır
            </button>
        </form>
    </div>

    {{-- Özet Kartlar --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs text-gray-400">Satış Toplamı</p>
            <p class="text-lg font-bold text-gray-900 mt-1">{{ formatCurrency($summary['sale_total']) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $summary['sale_count'] }} adet</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs text-gray-400">İade</p>
            <p class="text-lg font-bold text-red-500 mt-1">-{{ formatCurrency($summary['refund_total']) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $summary['refund_count'] }} adet</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs text-gray-400">Manuel Gelir</p>
            <p class="text-lg font-bold text-emerald-600 mt-1">{{ formatCurrency($summary['income_total']) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs text-gray-400">Gider</p>
            <p class="text-lg font-bold text-red-500 mt-1">-{{ formatCurrency($summary['expense_total']) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs text-gray-400">Tahsilat</p>
            <p class="text-lg font-bold text-emerald-600 mt-1">{{ formatCurrency($summary['collected_total']) }}</p>
        </div>
        <div class="col-span-2 sm:col-span-1 bg-gradient-to-br from-brand-500 to-purple-600 rounded-2xl p-4 shadow-sm text-white">
            <p class="text-xs text-white/70">Net Bakiye</p>
            <p class="text-xl font-bold mt-1">{{ formatCurrency($summary['net']) }}</p>
            <p class="text-xs text-white/70 mt-0.5">{{ $startDate }} / {{ $endDate }}</p>
        </div>
    </div>

    {{-- Hareketler Tablosu --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-800"><i class="fas fa-list mr-1.5 text-brand-500"></i>İşlem Listesi ({{ count($movements) }} kayıt)</h2>
        </div>
        @if(count($movements) === 0)
            <div class="text-center py-16 text-gray-400">
                <i class="fas fa-inbox text-4xl mb-3"></i>
                <p class="text-sm">Bu tarih aralığında işlem bulunamadı.</p>
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3">Tarih</th>
                        <th class="px-4 py-3">Tür</th>
                        <th class="px-4 py-3">Açıklama</th>
                        <th class="px-4 py-3 hidden sm:table-cell">İlgili</th>
                        <th class="px-4 py-3 hidden md:table-cell">Ödeme</th>
                        <th class="px-4 py-3 text-right">Tutar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($movements as $m)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-2.5 text-xs text-gray-500 whitespace-nowrap">
                            {{ $m['date_label'] ?? $m['date'] }}
                        </td>
                        <td class="px-4 py-2.5">
                            @php
                                $badge = match($m['type']) {
                                    'sale'    => ['bg-blue-50 text-blue-700',   'fa-bolt'],
                                    'income'  => ['bg-emerald-50 text-emerald-700', 'fa-arrow-down'],
                                    'expense' => ['bg-red-50 text-red-700',    'fa-arrow-up'],
                                    'account' => $m['direction'] === 'in'
                                                 ? ['bg-teal-50 text-teal-700','fa-hand-holding-dollar']
                                                 : ['bg-orange-50 text-orange-700','fa-user-minus'],
                                    default   => ['bg-gray-50 text-gray-600',  'fa-circle'],
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $badge[0] }}">
                                <i class="fas {{ $badge[1] }} text-[10px]"></i>
                                {{ $m['type_label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-gray-800 max-w-xs truncate">{{ $m['description'] }}</td>
                        <td class="px-4 py-2.5 text-gray-500 text-xs hidden sm:table-cell">{{ $m['sub'] }}</td>
                        <td class="px-4 py-2.5 text-gray-500 text-xs hidden md:table-cell">{{ $m['payment'] }}</td>
                        <td class="px-4 py-2.5 text-right font-mono font-semibold whitespace-nowrap
                            {{ $m['direction'] === 'out' ? 'text-red-500' : 'text-emerald-600' }}">
                            {{ $m['direction'] === 'out' ? '-' : '+' }}{{ formatCurrency($m['amount']) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection

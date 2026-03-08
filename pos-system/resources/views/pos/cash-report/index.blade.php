@extends('pos.layouts.app')

@section('title', 'Kasa Raporu')

@section('content')
<div class="p-6 overflow-y-auto h-full">
    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500">Toplam Kasa</p>
            <p class="text-xl font-bold text-gray-900 mt-1">{{ $stats['total_registers'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500">Toplam Satış</p>
            <p class="text-xl font-bold text-emerald-400 mt-1">{{ formatCurrency($stats['total_sales_all']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500">Nakit Toplam</p>
            <p class="text-xl font-bold text-emerald-600 mt-1">{{ formatCurrency($stats['total_cash_all']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500">Kart Toplam</p>
            <p class="text-xl font-bold text-brand-500 mt-1">{{ formatCurrency($stats['total_card_all']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center border-l-4 border-l-amber-400">
            <p class="text-xs text-gray-500">Veresiye Toplam</p>
            <p class="text-xl font-bold text-amber-500 mt-1">{{ formatCurrency($stats['total_credit_all']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500">Ort. Fark</p>
            <p class="text-xl font-bold {{ $stats['avg_difference'] >= 0 ? 'text-emerald-600' : 'text-red-500' }} mt-1">{{ formatCurrency($stats['avg_difference']) }}</p>
        </div>
    </div>

    {{-- Header & Filters --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Kasa Raporları (Z Raporları)</h1>
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <select name="status" class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg px-3 py-2">
                <option value="">Tüm Durumlar</option>
                <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Açık</option>
                <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Kapalı</option>
            </select>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg px-3 py-2">
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg px-3 py-2">
            <button type="submit" class="bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 text-white text-sm font-medium px-4 py-2 rounded-lg">Filtrele</button>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3.5">Açılış</th>
                        <th class="px-4 py-3.5">Kapanış</th>
                        <th class="px-4 py-3.5">Personel</th>
                        <th class="px-4 py-3.5 text-right">Açılış Tutarı</th>
                        <th class="px-4 py-3.5 text-right">Satış</th>
                        <th class="px-4 py-3.5 text-right">Nakit</th>
                        <th class="px-4 py-3.5 text-right">Kart</th>
                        <th class="px-4 py-3.5 text-right text-amber-600">Veresiye</th>
                        <th class="px-4 py-3.5 text-right">Fark</th>
                        <th class="px-4 py-3.5 text-center">Durum</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($registers as $reg)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-xs">{{ $reg->opened_at ? \Carbon\Carbon::parse($reg->opened_at)->format('d.m.Y H:i') : '-' }}</td>
                            <td class="px-4 py-3 text-xs">{{ $reg->closed_at ? \Carbon\Carbon::parse($reg->closed_at)->format('d.m.Y H:i') : '-' }}</td>
                            <td class="px-4 py-3">{{ $reg->user->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-right font-mono">{{ formatCurrency($reg->opening_amount) }}</td>
                            <td class="px-4 py-3 text-right font-mono font-medium text-gray-900">{{ formatCurrency($reg->total_sales) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-emerald-600">{{ formatCurrency($reg->total_cash) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-brand-500">{{ formatCurrency($reg->total_card) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-amber-500">{{ formatCurrency($creditByRegister[$reg->id] ?? 0) }}</td>
                            <td class="px-4 py-3 text-right font-mono {{ ($reg->difference ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                {{ formatCurrency($reg->difference ?? 0) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($reg->status === 'open')
                                    <span class="text-xs bg-green-500/10 text-emerald-600 px-2.5 py-1 rounded-full border border-green-500/30">Açık</span>
                                @else
                                    <span class="text-xs bg-gray-500/10 text-gray-500 px-2.5 py-1 rounded-full border border-gray-500/30">Kapalı</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center">
                                <p class="text-gray-500 text-sm">Kasa raporu bulunamadı</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($registers->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $registers->links() }}</div>
        @endif
    </div>
</div>
@endsection

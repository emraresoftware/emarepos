@extends('pos.layouts.app')

@section('title', 'Gün İşlemleri')

@section('content')
<div class="p-3 sm:p-6 overflow-y-auto h-full">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Gün İşlemleri</h1>
            <p class="text-sm text-gray-500 mt-1">{{ \Carbon\Carbon::today()->translatedFormat('d F Y, l') }}</p>
        </div>
        @if($activeRegister)
            <span class="flex items-center gap-2 bg-green-500/10 text-emerald-600 border border-green-500/30 px-4 py-2 rounded-lg text-sm">
                <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                Kasa Açık
            </span>
        @else
            <span class="flex items-center gap-2 bg-red-500/10 text-red-500 border border-red-500/30 px-4 py-2 rounded-lg text-sm">
                <span class="w-2 h-2 bg-red-400 rounded-full"></span>
                Kasa Kapalı
            </span>
        @endif
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Günlük Ciro</p>
                    <p class="text-xl font-bold text-gray-900">{{ formatCurrency($stats['total_sales']) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-brand-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Satış Adedi</p>
                    <p class="text-xl font-bold text-gray-900">{{ $stats['sale_count'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Nakit</p>
                    <p class="text-xl font-bold text-emerald-600">{{ formatCurrency($stats['cash_total']) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Kart</p>
                    <p class="text-xl font-bold text-purple-600">{{ formatCurrency($stats['card_total']) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Secondary Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500">Sipariş</p>
            <p class="text-lg font-bold text-gray-900 mt-1">{{ $stats['order_count'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500">İptal Sipariş</p>
            <p class="text-lg font-bold text-red-500 mt-1">{{ $stats['cancelled_orders'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500">İade Toplam</p>
            <p class="text-lg font-bold text-yellow-400 mt-1">{{ formatCurrency($stats['refund_total']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500">Ort. Sepet</p>
            <p class="text-lg font-bold text-gray-900 mt-1">{{ formatCurrency($stats['avg_basket']) }}</p>
        </div>
    </div>

    {{-- Hourly Sales Chart --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-gray-900 font-semibold mb-4">Saatlik Satış Dağılımı</h3>
            <canvas id="hourlyChart" height="200"></canvas>
        </div>

        {{-- Z Raporları --}}
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-gray-900 font-semibold mb-4">Son Z Raporları</h3>
            <div class="space-y-2">
                @forelse($zReports as $z)
                    <div class="flex items-center justify-between py-2.5 px-3 bg-white/50 rounded-lg">
                        <div>
                            <p class="text-sm text-gray-900 font-medium">{{ $z->closed_at ? \Carbon\Carbon::parse($z->closed_at)->format('d.m.Y H:i') : '-' }}</p>
                            <p class="text-xs text-gray-500">{{ $z->user->name ?? 'Bilinmiyor' }} tarafından kapatıldı</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-mono font-bold text-gray-900">{{ formatCurrency($z->total_sales) }}</p>
                            @if($z->difference != 0)
                                <p class="text-xs font-mono {{ $z->difference > 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                    Fark: {{ formatCurrency($z->difference) }}
                                </p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm text-center py-4">Henüz Z raporu yok</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const hourlyData = @json($hourlySales);
    const hours = [];
    const values = [];
    for (let h = 8; h <= 23; h++) {
        const key = h.toString().padStart(2, '0');
        hours.push(key + ':00');
        values.push(parseFloat(hourlyData[key] || 0));
    }
    new Chart(document.getElementById('hourlyChart'), {
        type: 'bar',
        data: {
            labels: hours,
            datasets: [{
                label: 'Satış (₺)',
                data: values,
                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1, borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(75,85,99,0.3)' } },
                x: { ticks: { color: '#9ca3af' }, grid: { display: false } }
            }
        }
    });
});
</script>
@endpush

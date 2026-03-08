@extends('pos.layouts.app')
@section('title', 'Özet')

@section('content')
<div class="flex-1 overflow-y-auto p-3 sm:p-6 space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Günlük Özet</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ now()->locale('tr')->isoFormat('DD MMMM YYYY, dddd') }}</p>
        </div>
        <div class="flex items-center gap-2">
            @if($activeRegister)
                <span class="px-3 py-1.5 bg-emerald-50 text-emerald-600 rounded-xl text-xs font-semibold border border-emerald-200">
                    <i class="fas fa-circle text-[8px] mr-1"></i> Kasa Açık
                </span>
            @else
                <span class="px-3 py-1.5 bg-red-50 text-red-600 rounded-xl text-xs font-semibold border border-red-200">
                    <i class="fas fa-circle text-[8px] mr-1"></i> Kasa Kapalı
                </span>
            @endif
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-500 text-xs font-medium">Günlük Satış</span>
                <div class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center">
                    <i class="fas fa-receipt text-brand-500 text-sm"></i>
                </div>
            </div>
            <div class="text-xl font-bold text-gray-900">{{ number_format($todaySales->total ?? 0, 2, ',', '.') }} ₺</div>
            <div class="text-xs text-gray-400 mt-1">{{ $todaySales->count ?? 0 }} işlem</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-500 text-xs font-medium">Nakit</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-emerald-500 text-sm"></i>
                </div>
            </div>
            <div class="text-xl font-bold text-emerald-600">{{ number_format($todaySales->cash ?? 0, 2, ',', '.') }} ₺</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-500 text-xs font-medium">Kredi Kartı</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                    <i class="fas fa-credit-card text-purple-500 text-sm"></i>
                </div>
            </div>
            <div class="text-xl font-bold text-purple-600">{{ number_format($todaySales->card ?? 0, 2, ',', '.') }} ₺</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-500 text-xs font-medium">Dolu Masalar</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                    <i class="fas fa-utensils text-amber-500 text-sm"></i>
                </div>
            </div>
            <div class="text-xl font-bold text-amber-600">{{ $activeTables }}</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-500 text-xs font-medium">Mutfak Bekleyen</span>
                <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center">
                    <i class="fas fa-fire-burner text-red-500 text-sm"></i>
                </div>
            </div>
            <div class="text-xl font-bold text-red-600">{{ $pendingOrders }}</div>
        </div>
    </div>

    <!-- Charts & Recent Sales -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Weekly Chart -->
        <div class="lg:col-span-2 bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Son 7 Gün Gelir</h3>
            <canvas id="weeklyChart" height="200"></canvas>
        </div>

        <!-- Recent Sales -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Son Satışlar</h3>
            <div class="space-y-1 max-h-64 overflow-y-auto">
                @forelse($recentSales as $sale)
                    <div class="flex items-center justify-between py-2.5 border-b border-gray-100 last:border-0">
                        <div>
                            <div class="text-sm font-medium text-gray-800">{{ $sale->receipt_no }}</div>
                            <div class="text-xs text-gray-400">{{ $sale->sold_at?->format('H:i') }} • {{ ucfirst($sale->payment_method) }}</div>
                        </div>
                        <div class="text-sm font-semibold text-emerald-600">{{ number_format($sale->grand_total, 2, ',', '.') }} ₺</div>
                    </div>
                @empty
                    <p class="text-gray-400 text-sm text-center py-4">Henüz satış yok</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('pos.sales') }}" class="bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-500/30 hover:scale-[1.02] rounded-2xl p-5 text-center transition-all duration-300 text-white">
            <i class="fas fa-bolt text-2xl mb-2"></i>
            <div class="text-sm font-semibold">Hızlı Satış</div>
        </a>
        <a href="{{ route('pos.tables') }}" class="bg-gradient-to-r from-amber-500 to-orange-500 hover:shadow-lg hover:shadow-amber-500/30 hover:scale-[1.02] rounded-2xl p-5 text-center transition-all duration-300 text-white">
            <i class="fas fa-utensils text-2xl mb-2"></i>
            <div class="text-sm font-semibold">Masalar</div>
        </a>
        <a href="{{ route('pos.kitchen') }}" class="bg-gradient-to-r from-red-500 to-rose-500 hover:shadow-lg hover:shadow-red-500/30 hover:scale-[1.02] rounded-2xl p-5 text-center transition-all duration-300 text-white">
            <i class="fas fa-fire-burner text-2xl mb-2"></i>
            <div class="text-sm font-semibold">Mutfak</div>
        </a>
        <a href="{{ route('pos.cash-register') }}" class="bg-gradient-to-r from-emerald-500 to-teal-500 hover:shadow-lg hover:shadow-emerald-500/30 hover:scale-[1.02] rounded-2xl p-5 text-center transition-all duration-300 text-white">
            <i class="fas fa-cash-register text-2xl mb-2"></i>
            <div class="text-sm font-semibold">Kasa</div>
        </a>
    </div>

    @if($lowStockCount > 0)
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
            <i class="fas fa-exclamation-triangle text-amber-600"></i>
        </div>
        <span class="text-amber-700 text-sm font-medium">{{ $lowStockCount }} üründe düşük stok uyarısı var.</span>
        <a href="{{ route('pos.products') }}?low_stock=1" class="ml-auto text-amber-600 hover:text-amber-800 text-sm font-semibold underline">Görüntüle</a>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    const weeklyData = @json($weeklyData);
    new Chart(document.getElementById('weeklyChart'), {
        type: 'bar',
        data: {
            labels: weeklyData.map(d => d.day + '\n' + d.date),
            datasets: [{
                label: 'Gelir (₺)',
                data: weeklyData.map(d => d.total),
                backgroundColor: 'rgba(99, 102, 241, 0.15)',
                borderColor: 'rgb(99, 102, 241)',
                borderWidth: 2,
                borderRadius: 8,
                hoverBackgroundColor: 'rgba(99, 102, 241, 0.3)',
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: '#9ca3af', callback: v => '₺' + v.toLocaleString('tr-TR') },
                    grid: { color: 'rgba(243,244,246,1)' }
                },
                x: {
                    ticks: { color: '#9ca3af', font: { size: 10 } },
                    grid: { display: false }
                }
            }
        }
    });
</script>
@endpush

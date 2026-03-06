@extends('pos.layouts.app')
@section('title', 'Raporlar')

@section('content')
<div class="flex-1 overflow-y-auto p-4 space-y-4">

    {{-- Top Bar --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Raporlar</h1>
            <p class="text-sm text-gray-500">Satış performansı ve istatistikler</p>
        </div>
        <form method="GET" action="{{ route('pos.reports') }}" class="flex items-center gap-2 no-print">
            <div class="flex items-center gap-1.5 bg-white border border-gray-100 rounded-lg px-3 py-2">
                <i class="fas fa-calendar-alt text-gray-500 text-xs"></i>
                <input type="date" name="start_date" value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}"
                       class="bg-transparent text-sm text-gray-900 focus:outline-none w-32">
                <span class="text-gray-500 text-xs">—</span>
                <input type="date" name="end_date" value="{{ request('end_date', now()->format('Y-m-d')) }}"
                       class="bg-transparent text-sm text-gray-900 focus:outline-none w-32">
            </div>
            <button type="submit"
                    class="px-4 py-2 bg-brand-500 hover:bg-gradient-to-r from-brand-500 to-purple-600 text-gray-900 rounded-lg text-sm font-medium transition-colors">
                <i class="fas fa-filter mr-1"></i> Filtrele
            </button>
            <button type="button" onclick="window.print()"
                    class="px-4 py-2 bg-slate-600 hover:bg-gray-200 text-gray-900 rounded-lg text-sm font-medium transition-colors">
                <i class="fas fa-print mr-1"></i> Yazdır
            </button>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-500 text-xs">Toplam Satış</span>
                <div class="w-8 h-8 bg-brand-500/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-coins text-brand-500 text-sm"></i>
                </div>
            </div>
            <div class="text-xl font-bold text-gray-900">{{ number_format($stats['total_revenue'] ?? 0, 2, ',', '.') }} ₺</div>
            <div class="text-xs text-gray-500 mt-1">Toplam gelir</div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-500 text-xs">Satış Adedi</span>
                <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-receipt text-emerald-600 text-sm"></i>
                </div>
            </div>
            <div class="text-xl font-bold text-emerald-600">{{ number_format($stats['sale_count'] ?? 0) }}</div>
            <div class="text-xs text-gray-500 mt-1">Toplam fiş sayısı</div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-500 text-xs">Ortalama Sepet</span>
                <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-shopping-basket text-amber-600 text-sm"></i>
                </div>
            </div>
            <div class="text-xl font-bold text-amber-600">{{ number_format($stats['avg_basket'] ?? 0, 2, ',', '.') }} ₺</div>
            <div class="text-xs text-gray-500 mt-1">Fiş başına ortalama</div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-500 text-xs">Toplam KDV</span>
                <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-percent text-purple-600 text-sm"></i>
                </div>
            </div>
            <div class="text-xl font-bold text-purple-600">{{ number_format($stats['total_vat'] ?? 0, 2, ',', '.') }} ₺</div>
            <div class="text-xs text-gray-500 mt-1">Tahsil edilen KDV</div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Daily Sales Bar Chart --}}
        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <h3 class="text-sm font-medium text-gray-700 mb-3">
                <i class="fas fa-chart-bar text-brand-500 mr-1"></i> Günlük Satışlar
            </h3>
            <div style="position:relative; height:260px;">
                <canvas id="dailySalesChart"></canvas>
            </div>
        </div>

        {{-- Payment Methods Pie Chart --}}
        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <h3 class="text-sm font-medium text-gray-700 mb-3">
                <i class="fas fa-chart-pie text-emerald-600 mr-1"></i> Ödeme Yöntemleri
            </h3>
            <div class="flex items-center justify-center" style="height: 260px;">
                <canvas id="paymentChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Top Products & Category Stats --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Top Products Table --}}
        <div class="lg:col-span-2 bg-white rounded-xl p-4 border border-gray-100">
            <h3 class="text-sm font-medium text-gray-700 mb-3">
                <i class="fas fa-trophy text-amber-600 mr-1"></i> En Çok Satan Ürünler
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left text-gray-500 font-medium py-2 px-3 w-10">#</th>
                            <th class="text-left text-gray-500 font-medium py-2 px-3">Ürün Adı</th>
                            <th class="text-right text-gray-500 font-medium py-2 px-3">Satış Adedi</th>
                            <th class="text-right text-gray-500 font-medium py-2 px-3">Toplam Tutar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topProducts as $index => $product)
                            <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                <td class="py-2.5 px-3">
                                    @if($index < 3)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold
                                            {{ $index === 0 ? 'bg-amber-50 text-amber-600' : ($index === 1 ? 'bg-gray-400/20 text-gray-700' : 'bg-orange-500/20 text-orange-400') }}">
                                            {{ $index + 1 }}
                                        </span>
                                    @else
                                        <span class="text-gray-500 pl-1.5">{{ $index + 1 }}</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-gray-900 font-medium">{{ $product->product_name }}</td>
                                <td class="py-2.5 px-3 text-right text-gray-700">{{ number_format($product->quantity) }}</td>
                                <td class="py-2.5 px-3 text-right text-emerald-600 font-medium">{{ number_format($product->total, 2, ',', '.') }} ₺</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-gray-500">
                                    <i class="fas fa-box-open text-2xl mb-2"></i>
                                    <p>Bu dönemde satış verisi bulunamadı</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Category Revenue Breakdown --}}
        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <h3 class="text-sm font-medium text-gray-700 mb-3">
                <i class="fas fa-layer-group text-purple-600 mr-1"></i> Kategori Bazlı Gelir
            </h3>
            <div class="space-y-3">
                @php
                    $maxCategoryRevenue = $categoryStats->max('revenue') ?: 1;
                @endphp
                @forelse($categoryStats as $category)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-700">{{ $category->name }}</span>
                            <span class="text-sm text-gray-900 font-medium">{{ number_format($category->revenue, 2, ',', '.') }} ₺</span>
                        </div>
                        <div class="w-full bg-gray-100/40 rounded-full h-2">
                            <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full transition-all duration-500"
                                 style="width: {{ ($category->revenue / $maxCategoryRevenue) * 100 }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-6">
                        <i class="fas fa-folder-open text-2xl mb-2"></i>
                        <p class="text-sm">Kategori verisi bulunamadı</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Daily Sales Bar Chart
    const dailySalesData = @json($dailySales ?? []);
    const dailyLabels = Object.keys(dailySalesData);
    const dailyValues = Object.values(dailySalesData);

    new Chart(document.getElementById('dailySalesChart'), {
        type: 'bar',
        data: {
            labels: dailyLabels.map(d => {
                const date = new Date(d);
                return date.toLocaleDateString('tr-TR', { day: '2-digit', month: 'short' });
            }),
            datasets: [{
                label: 'Satış (₺)',
                data: dailyValues,
                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 1,
                borderRadius: 6,
                hoverBackgroundColor: 'rgba(59, 130, 246, 0.8)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => formatCurrency(ctx.parsed.y)
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#94a3b8',
                        callback: v => '₺' + v.toLocaleString('tr-TR')
                    },
                    grid: { color: 'rgba(148,163,184,0.08)' }
                },
                x: {
                    ticks: { color: '#94a3b8', font: { size: 10 }, maxRotation: 45 },
                    grid: { display: false }
                }
            }
        }
    });

    // Payment Methods Pie Chart
    const paymentData = @json($paymentStats ?? []);
    const paymentLabels = {
        'cash': 'Nakit',
        'card': 'Kart',
        'credit': 'Veresiye',
        'mixed': 'Karışık'
    };
    const paymentColors = {
        'cash': '#22c55e',
        'card': '#a855f7',
        'credit': '#f59e0b',
        'mixed': '#3b82f6'
    };

    const pLabels = Object.keys(paymentData).map(k => paymentLabels[k] || k);
    const pValues = Object.values(paymentData);
    const pColors = Object.keys(paymentData).map(k => paymentColors[k] || '#64748b');

    new Chart(document.getElementById('paymentChart'), {
        type: 'doughnut',
        data: {
            labels: pLabels,
            datasets: [{
                data: pValues,
                backgroundColor: pColors,
                borderColor: '#1e293b',
                borderWidth: 3,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '55%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#cbd5e1',
                        padding: 16,
                        usePointStyle: true,
                        pointStyleWidth: 10,
                        font: { size: 12 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                            return ctx.label + ': ' + formatCurrency(ctx.parsed) + ' (%' + pct + ')';
                        }
                    }
                }
            }
        }
    });
</script>
@endpush

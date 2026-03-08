@extends('pos.layouts.app')
@section('title', 'Raporlar')

@section('content')
<div class="flex-1 overflow-y-auto p-4 space-y-4" x-data="advancedReports()" x-cloak>

    {{-- Top Bar --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Raporlar</h1>
            <p class="text-sm text-gray-500">Satış performansı ve istatistikler</p>
        </div>
        <form method="GET" action="{{ route('pos.reports') }}" class="flex flex-wrap items-center gap-2 no-print">
            <div class="flex flex-wrap items-center gap-1.5 bg-white border border-gray-100 rounded-lg px-3 py-2">
                <i class="fas fa-calendar-alt text-gray-500 text-xs"></i>
                <input type="date" name="start_date" value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}"
                       class="bg-transparent text-sm text-gray-900 focus:outline-none w-full sm:w-32">
                <span class="text-gray-500 text-xs">—</span>
                <input type="date" name="end_date" value="{{ request('end_date', now()->format('Y-m-d')) }}"
                       class="bg-transparent text-sm text-gray-900 focus:outline-none w-full sm:w-32">
            </div>
            <button type="submit"
                    class="px-4 py-2 bg-brand-500 hover:bg-gradient-to-r from-brand-500 to-purple-600 text-white rounded-lg text-sm font-medium transition-colors">
                <i class="fas fa-filter mr-1"></i> Filtrele
            </button>
            <button type="button" onclick="window.print()"
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-200 text-gray-900 rounded-lg text-sm font-medium transition-colors">
                <i class="fas fa-print mr-1"></i> Yazdır
            </button>
        </form>
    </div>

    {{-- Tab Navigation --}}
    <div class="flex flex-wrap gap-2 no-print">
        <button @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'bg-brand-500 text-white shadow-md shadow-brand-500/25' : 'bg-white text-gray-600 hover:bg-brand-50'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-all">
            <i class="fas fa-chart-line mr-1.5"></i>Genel Bakış
        </button>
        <button @click="loadProfitLoss()" :class="activeTab === 'profit' ? 'bg-brand-500 text-white shadow-md shadow-brand-500/25' : 'bg-white text-gray-600 hover:bg-brand-50'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-all">
            <i class="fas fa-coins mr-1.5"></i>Kâr / Zarar
        </button>
        <button @click="loadStaffReport()" :class="activeTab === 'staff' ? 'bg-brand-500 text-white shadow-md shadow-brand-500/25' : 'bg-white text-gray-600 hover:bg-brand-50'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-all">
            <i class="fas fa-user-tie mr-1.5"></i>Personel Raporu
        </button>
        <button @click="loadCategoryReport()" :class="activeTab === 'category' ? 'bg-brand-500 text-white shadow-md shadow-brand-500/25' : 'bg-white text-gray-600 hover:bg-brand-50'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-all">
            <i class="fas fa-layer-group mr-1.5"></i>Kategori Raporu
        </button>
        <button @click="loadComparison()" :class="activeTab === 'comparison' ? 'bg-brand-500 text-white shadow-md shadow-brand-500/25' : 'bg-white text-gray-600 hover:bg-brand-50'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-all">
            <i class="fas fa-balance-scale mr-1.5"></i>Dönem Karşılaştırma
        </button>
        <button @click="loadSuspicious()" :class="activeTab === 'suspicious' ? 'bg-red-500 text-white shadow-md shadow-red-500/25' : 'bg-white text-gray-600 hover:bg-red-50'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-all">
            <i class="fas fa-exclamation-triangle mr-1.5"></i>Şüpheli İşlemler
        </button>
    </div>

    {{-- OVERVIEW TAB --}}
    <div x-show="activeTab === 'overview'">
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

    </div>{{-- /overview tab --}}

    {{-- PROFIT/LOSS TAB --}}
    <div x-show="activeTab === 'profit'" x-cloak>
        <template x-if="profitData">
        <div class="space-y-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <span class="text-gray-500 text-xs">Satış Geliri</span>
                    <p class="text-xl font-bold text-emerald-600 mt-1" x-text="fmt(profitData.sales_revenue)"></p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <span class="text-gray-500 text-xs">Satış Maliyeti</span>
                    <p class="text-xl font-bold text-red-500 mt-1" x-text="fmt(profitData.cost_of_goods)"></p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <span class="text-gray-500 text-xs">Brüt Kâr</span>
                    <p class="text-xl font-bold mt-1" :class="profitData.gross_profit >= 0 ? 'text-emerald-600' : 'text-red-500'" x-text="fmt(profitData.gross_profit)"></p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <span class="text-gray-500 text-xs">Net Kâr</span>
                    <p class="text-xl font-bold mt-1" :class="profitData.net_profit >= 0 ? 'text-emerald-600' : 'text-red-500'" x-text="fmt(profitData.net_profit)"></p>
                    <p class="text-xs mt-1" :class="profitData.net_profit >= 0 ? 'text-emerald-500' : 'text-red-400'" x-text="'Marj: %' + profitData.profit_margin"></p>
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <span class="text-gray-500 text-xs">İskontolar</span>
                    <p class="text-lg font-bold text-amber-600 mt-1" x-text="fmt(profitData.discounts)"></p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <span class="text-gray-500 text-xs">Giderler</span>
                    <p class="text-lg font-bold text-red-500 mt-1" x-text="fmt(profitData.expenses)"></p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <span class="text-gray-500 text-xs">Diğer Gelirler</span>
                    <p class="text-lg font-bold text-blue-600 mt-1" x-text="fmt(profitData.other_income)"></p>
                </div>
            </div>
            {{-- Günlük Kâr Grafiği --}}
            <div class="bg-white rounded-xl p-4 border border-gray-100">
                <h3 class="text-sm font-medium text-gray-700 mb-3"><i class="fas fa-chart-area text-emerald-500 mr-1"></i> Günlük Kâr Trendi</h3>
                <div style="position:relative; height:260px;"><canvas id="profitChart"></canvas></div>
            </div>
            {{-- Kârlı Ürünler --}}
            <div class="bg-white rounded-xl p-4 border border-gray-100" x-show="profitData.top_profitable && profitData.top_profitable.length > 0">
                <h3 class="text-sm font-medium text-gray-700 mb-3"><i class="fas fa-trophy text-amber-500 mr-1"></i> En Kârlı Ürünler</h3>
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-gray-100"><th class="text-left py-2 px-3 text-gray-500 font-medium">Ürün</th><th class="text-right py-2 px-3 text-gray-500 font-medium">Kâr</th></tr></thead>
                    <tbody>
                        <template x-for="p in profitData.top_profitable" :key="p.product_name">
                            <tr class="border-b border-gray-50"><td class="py-2 px-3 text-gray-900" x-text="p.product_name"></td><td class="py-2 px-3 text-right font-semibold text-emerald-600" x-text="fmt(p.profit)"></td></tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
        </template>
        <div x-show="!profitData" class="text-center py-20 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i><p class="mt-2 text-sm">Yükleniyor...</p></div>
    </div>

    {{-- STAFF REPORT TAB --}}
    <div x-show="activeTab === 'staff'" x-cloak>
        <template x-if="staffData">
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-100 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="text-left py-3 px-4 text-gray-500 font-medium">Personel</th>
                        <th class="text-right py-3 px-4 text-gray-500 font-medium">Satış</th>
                        <th class="text-right py-3 px-4 text-gray-500 font-medium">Gelir</th>
                        <th class="text-right py-3 px-4 text-gray-500 font-medium">Ürün</th>
                        <th class="text-right py-3 px-4 text-gray-500 font-medium">Ort. Sepet</th>
                        <th class="text-right py-3 px-4 text-gray-500 font-medium">İskonto</th>
                        <th class="text-right py-3 px-4 text-gray-500 font-medium">İade</th>
                    </tr></thead>
                    <tbody>
                        <template x-for="s in staffData.staff_stats" :key="s.staff_name">
                            <tr class="border-b border-gray-50 hover:bg-gray-50/50">
                                <td class="py-3 px-4 text-gray-900 font-medium" x-text="s.staff_name"></td>
                                <td class="py-3 px-4 text-right text-gray-700" x-text="s.sale_count"></td>
                                <td class="py-3 px-4 text-right text-emerald-600 font-semibold" x-text="fmt(s.total_revenue)"></td>
                                <td class="py-3 px-4 text-right text-gray-700" x-text="s.total_items"></td>
                                <td class="py-3 px-4 text-right text-gray-700" x-text="fmt(s.avg_basket)"></td>
                                <td class="py-3 px-4 text-right text-amber-600" x-text="fmt(s.total_discount)"></td>
                                <td class="py-3 px-4 text-right text-red-500" x-text="s.refund_count || 0"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
        </template>
        <div x-show="!staffData" class="text-center py-20 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i></div>
    </div>

    {{-- CATEGORY REPORT TAB --}}
    <div x-show="activeTab === 'category'" x-cloak>
        <template x-if="categoryData">
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-100 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="text-left py-3 px-4 text-gray-500 font-medium">Kategori</th>
                        <th class="text-right py-3 px-4 text-gray-500 font-medium">Satış Adedi</th>
                        <th class="text-right py-3 px-4 text-gray-500 font-medium">Miktar</th>
                        <th class="text-right py-3 px-4 text-gray-500 font-medium">Gelir</th>
                        <th class="text-right py-3 px-4 text-gray-500 font-medium">Kâr</th>
                        <th class="text-right py-3 px-4 text-gray-500 font-medium">Oran</th>
                    </tr></thead>
                    <tbody>
                        <template x-for="c in categoryData.categories" :key="c.category_name">
                            <tr class="border-b border-gray-50 hover:bg-gray-50/50">
                                <td class="py-3 px-4 text-gray-900 font-medium" x-text="c.category_name || 'Kategorisiz'"></td>
                                <td class="py-3 px-4 text-right text-gray-700" x-text="c.sale_count"></td>
                                <td class="py-3 px-4 text-right text-gray-700" x-text="c.total_quantity"></td>
                                <td class="py-3 px-4 text-right text-emerald-600 font-semibold" x-text="fmt(c.total_revenue)"></td>
                                <td class="py-3 px-4 text-right font-semibold" :class="c.total_profit >= 0 ? 'text-emerald-600' : 'text-red-500'" x-text="fmt(c.total_profit)"></td>
                                <td class="py-3 px-4 text-right text-brand-600 font-medium" x-text="'%' + c.percentage"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
        </template>
        <div x-show="!categoryData" class="text-center py-20 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i></div>
    </div>

    {{-- COMPARISON TAB --}}
    <div x-show="activeTab === 'comparison'" x-cloak>
        <template x-if="comparisonData">
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-100 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="text-left py-3 px-4 text-gray-500 font-medium">Metrik</th>
                        <th class="text-right py-3 px-4 text-gray-500 font-medium">Bu Dönem</th>
                        <th class="text-right py-3 px-4 text-gray-500 font-medium">Önceki Dönem</th>
                        <th class="text-right py-3 px-4 text-gray-500 font-medium">Değişim</th>
                    </tr></thead>
                    <tbody>
                        <template x-for="key in ['revenue', 'sale_count', 'avg_basket', 'total_discount', 'total_items']" :key="key">
                            <tr class="border-b border-gray-50">
                                <td class="py-3 px-4 text-gray-900 font-medium" x-text="metricLabel(key)"></td>
                                <td class="py-3 px-4 text-right text-gray-900 font-semibold" x-text="key.includes('count') || key.includes('items') ? comparisonData.period1[key] : fmt(comparisonData.period1[key])"></td>
                                <td class="py-3 px-4 text-right text-gray-500" x-text="key.includes('count') || key.includes('items') ? comparisonData.period2[key] : fmt(comparisonData.period2[key])"></td>
                                <td class="py-3 px-4 text-right font-bold"
                                    :class="comparisonData.changes[key] >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                    <span x-text="(comparisonData.changes[key] >= 0 ? '+' : '') + comparisonData.changes[key] + '%'"></span>
                                    <i class="fas ml-1" :class="comparisonData.changes[key] >= 0 ? 'fa-arrow-up' : 'fa-arrow-down'"></i>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
        </template>
        <div x-show="!comparisonData" class="text-center py-20 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i></div>
    </div>

    {{-- SUSPICIOUS TAB --}}
    <div x-show="activeTab === 'suspicious'" x-cloak>
        <template x-if="suspiciousData">
        <div class="space-y-4">
            {{-- Summary --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="bg-white rounded-xl p-4 border border-red-100">
                    <span class="text-gray-500 text-xs">Toplam Şüpheli</span>
                    <p class="text-2xl font-bold text-red-500 mt-1" x-text="suspiciousData.summary.total"></p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-red-100">
                    <span class="text-gray-500 text-xs">Yüksek Risk</span>
                    <p class="text-2xl font-bold text-red-600 mt-1" x-text="suspiciousData.summary.high"></p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-yellow-100">
                    <span class="text-gray-500 text-xs">Orta Risk</span>
                    <p class="text-2xl font-bold text-yellow-600 mt-1" x-text="suspiciousData.summary.medium"></p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <span class="text-gray-500 text-xs">Toplam Kayıp</span>
                    <p class="text-2xl font-bold text-red-500 mt-1" x-text="fmt(suspiciousData.summary.total_loss)"></p>
                </div>
            </div>
            {{-- List --}}
            <div class="bg-white rounded-xl border border-gray-100 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="text-center py-3 px-3 text-gray-500 font-medium w-10">Risk</th>
                        <th class="text-left py-3 px-4 text-gray-500 font-medium">Tür</th>
                        <th class="text-left py-3 px-4 text-gray-500 font-medium">Personel</th>
                        <th class="text-left py-3 px-4 text-gray-500 font-medium">Detay</th>
                        <th class="text-right py-3 px-4 text-gray-500 font-medium">Tutar</th>
                        <th class="text-left py-3 px-4 text-gray-500 font-medium">Tarih</th>
                    </tr></thead>
                    <tbody>
                        <template x-for="(item, idx) in suspiciousData.suspicious" :key="idx">
                            <tr class="border-b border-gray-50 hover:bg-gray-50/50">
                                <td class="py-3 px-3 text-center">
                                    <span class="inline-block w-3 h-3 rounded-full"
                                        :class="item.severity === 'high' ? 'bg-red-500' : item.severity === 'medium' ? 'bg-yellow-500' : 'bg-gray-400'"></span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 rounded text-xs font-medium"
                                        :class="item.type === 'refund' ? 'bg-red-50 text-red-600' : item.type === 'cancelled' ? 'bg-orange-50 text-orange-600' : item.type === 'below_cost' ? 'bg-purple-50 text-purple-600' : item.type === 'high_discount' ? 'bg-yellow-50 text-yellow-600' : 'bg-gray-50 text-gray-600'"
                                        x-text="item.type_label"></span>
                                </td>
                                <td class="py-3 px-4 text-gray-900 font-medium" x-text="item.staff"></td>
                                <td class="py-3 px-4 text-gray-500 text-xs" x-text="item.detail"></td>
                                <td class="py-3 px-4 text-right font-semibold text-red-500" x-text="fmt(item.amount)"></td>
                                <td class="py-3 px-4 text-gray-500 text-xs" x-text="item.date"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div class="p-6 text-center text-gray-400" x-show="suspiciousData.suspicious.length === 0">
                    <i class="fas fa-shield-halved text-3xl mb-2 text-green-400"></i>
                    <p>Şüpheli işlem bulunamadı — Tebrikler!</p>
                </div>
            </div>
        </div>
        </template>
        <div x-show="!suspiciousData" class="text-center py-20 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i></div>
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

    // Advanced Reports Alpine.js Component
    function advancedReports() {
        return {
            activeTab: 'overview',
            profitData: null,
            staffData: null,
            categoryData: null,
            comparisonData: null,
            suspiciousData: null,

            fmt(v) {
                return Number(v || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺';
            },

            metricLabel(key) {
                const labels = { revenue: 'Gelir', sale_count: 'Satış Adedi', avg_basket: 'Ort. Sepet', total_discount: 'İskonto', total_items: 'Ürün Adedi' };
                return labels[key] || key;
            },

            async loadProfitLoss() {
                this.activeTab = 'profit';
                if (this.profitData) return;
                try {
                    this.profitData = await posAjax('/reports/profit-loss', {}, 'GET');
                    this.$nextTick(() => {
                        if (this.profitData.daily_profit) {
                            const labels = Object.keys(this.profitData.daily_profit);
                            const values = Object.values(this.profitData.daily_profit);
                            const ctx = document.getElementById('profitChart');
                            if (ctx) {
                                new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: labels.map(d => new Date(d).toLocaleDateString('tr-TR', { day: '2-digit', month: 'short' })),
                                        datasets: [{ label: 'Kâr (₺)', data: values, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.4, pointRadius: 3 }]
                                    },
                                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { color: '#94a3b8', callback: v => '₺' + v.toLocaleString('tr-TR') } }, x: { ticks: { color: '#94a3b8', font: { size: 10 } } } } }
                                });
                            }
                        }
                    });
                } catch (e) { console.error(e); }
            },

            async loadStaffReport() {
                this.activeTab = 'staff';
                if (this.staffData) return;
                try { this.staffData = await posAjax('/reports/staff', {}, 'GET'); } catch (e) { console.error(e); }
            },

            async loadCategoryReport() {
                this.activeTab = 'category';
                if (this.categoryData) return;
                try { this.categoryData = await posAjax('/reports/categories', {}, 'GET'); } catch (e) { console.error(e); }
            },

            async loadComparison() {
                this.activeTab = 'comparison';
                if (this.comparisonData) return;
                try { this.comparisonData = await posAjax('/reports/comparison', {}, 'GET'); } catch (e) { console.error(e); }
            },

            async loadSuspicious() {
                this.activeTab = 'suspicious';
                if (this.suspiciousData) return;
                try { this.suspiciousData = await posAjax('/reports/suspicious', {}, 'GET'); } catch (e) { console.error(e); }
            },
        };
    }
</script>
@endpush

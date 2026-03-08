@extends('pos.layouts.app')
@section('title', 'Kasa İşlemleri')

@section('content')
<div x-data="cashRegisterScreen()" class="flex-1 flex flex-col overflow-hidden">

    {{-- Üst Bar --}}
    <div class="p-4 bg-gray-50 border-b border-gray-700 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h1 class="text-xl font-bold text-gray-900">
                <i class="fas fa-cash-register text-brand-500 mr-2"></i>Kasa İşlemleri
            </h1>
            @if($register)
            <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-xs font-semibold rounded-full">
                <i class="fas fa-circle text-[8px] mr-1 animate-pulse"></i>Kasa Açık
            </span>
            @else
            <span class="px-3 py-1 bg-red-50 text-red-500 text-xs font-semibold rounded-full">
                <i class="fas fa-circle text-[8px] mr-1"></i>Kasa Kapalı
            </span>
            @endif
        </div>
        <div class="text-sm text-gray-500">
            <i class="fas fa-calendar mr-1"></i>{{ now()->translatedFormat('d F Y, l') }}
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-6 space-y-6">

        @if(!$register)
        {{-- ═══════════════════════════════════════════ --}}
        {{-- KASA KAPALI — Kasa Aç Formu --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="max-w-lg mx-auto mt-12">
            <div class="bg-white rounded-2xl border border-gray-700 p-8 text-center">
                <div class="w-20 h-20 bg-slate-700 rounded-full mx-auto flex items-center justify-center mb-6">
                    <i class="fas fa-lock text-3xl text-gray-500"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Kasa Kapalı</h2>
                <p class="text-gray-500 mb-8 text-sm">Satış yapabilmek için kasayı açmanız gerekiyor.</p>

                <form method="POST" action="{{ route('pos.cash-register.open') }}" class="space-y-4 text-left">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Açılış Bakiyesi (₺)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-3.5 text-gray-500 font-medium">₺</span>
                            <input type="number" name="opening_amount" step="0.01" min="0" value="0" required
                                   class="w-full pl-10 pr-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-gray-900 text-lg font-semibold placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-transparent"
                                   placeholder="0.00">
                        </div>
                        <p class="text-xs text-gray-500 mt-1.5">Kasadaki mevcut nakit tutarını girin</p>
                        @error('opening_amount')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full py-3.5 bg-emerald-500 hover:bg-green-500 text-gray-900 font-bold rounded-xl transition-colors text-sm flex items-center justify-center gap-2">
                        <i class="fas fa-unlock"></i>
                        Kasayı Aç
                    </button>
                </form>
            </div>
        </div>

        @else
        {{-- ═══════════════════════════════════════════ --}}
        {{-- KASA AÇIK — Bilgiler + İşlemler --}}
        {{-- ═══════════════════════════════════════════ --}}

        {{-- Kasa Bilgileri --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Kasa Durumu --}}
            <div class="bg-white rounded-xl border border-gray-700 p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Kasa Durumu</span>
                    <span class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-cash-register text-emerald-600 text-sm"></i>
                    </span>
                </div>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Açılış:</span>
                        <span class="text-gray-900 font-medium">{{ $register->opened_at->format('H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Açan:</span>
                        <span class="text-gray-900 font-medium">{{ $register->user->name ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Açılış Bakiye:</span>
                        <span class="text-gray-900 font-bold">{{ number_format($register->opening_amount, 2) }} ₺</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Süre:</span>
                        <span class="text-brand-600 font-medium">{{ $register->opened_at->diffForHumans(null, true) }}</span>
                    </div>
                </div>
            </div>

            {{-- Nakit Satışlar --}}
            <div class="bg-white rounded-xl border border-gray-700 p-4 cursor-pointer hover:border-emerald-400 hover:shadow-md transition-all" @click="openSalesModal('cash')">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Nakit Satış</span>
                    <span class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-money-bill-wave text-emerald-600 text-sm"></i>
                    </span>
                </div>
                <div class="text-2xl font-bold text-emerald-600">{{ number_format($stats['cash_total'] ?? 0, 2) }} ₺</div>
                <div class="text-xs text-gray-400 mt-1">Nakit tahsilat toplamı <i class="fas fa-chevron-right ml-1"></i></div>
            </div>

            {{-- Kart Satışlar --}}
            <div class="bg-white rounded-xl border border-gray-700 p-4 cursor-pointer hover:border-brand-400 hover:shadow-md transition-all" @click="openSalesModal('card')">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Kart Satış</span>
                    <span class="w-8 h-8 bg-brand-500/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-credit-card text-brand-500 text-sm"></i>
                    </span>
                </div>
                <div class="text-2xl font-bold text-brand-500">{{ number_format($stats['card_total'] ?? 0, 2) }} ₺</div>
                <div class="text-xs text-gray-400 mt-1">Kredi/banka kartı toplamı <i class="fas fa-chevron-right ml-1"></i></div>
            </div>

            {{-- Veresiye Satışlar --}}
            <div class="bg-white rounded-xl border border-amber-200 p-4 cursor-pointer hover:border-amber-400 hover:shadow-md transition-all" @click="openSalesModal('credit')">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-amber-600 uppercase tracking-wider">Veresiye</span>
                    <span class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-user-clock text-amber-500 text-sm"></i>
                    </span>
                </div>
                <div class="text-2xl font-bold text-amber-500">{{ number_format($stats['credit_total'] ?? 0, 2) }} ₺</div>
                <div class="text-xs text-amber-400 mt-1">Cari hesaba yazılan satışlar <i class="fas fa-chevron-right ml-1"></i></div>
            </div>

            {{-- Toplam Satış --}}
            <div class="bg-white rounded-xl border border-gray-700 p-4 cursor-pointer hover:border-purple-400 hover:shadow-md transition-all" @click="openSalesModal('all')">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Toplam Satış</span>
                    <span class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-purple-600 text-sm"></i>
                    </span>
                </div>
                <div class="text-2xl font-bold text-gray-900">{{ number_format(($stats['cash_total'] ?? 0) + ($stats['card_total'] ?? 0) + ($stats['credit_total'] ?? 0), 2) }} ₺</div>
                <div class="text-xs text-gray-400 mt-1">{{ $stats['sale_count'] ?? 0 }} adet satış <i class="fas fa-chevron-right ml-1"></i></div>
            </div>
        </div>

        {{-- Beklenen Nakit Hesaplaması --}}
        <div class="bg-white rounded-xl border border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">
                <i class="fas fa-calculator text-brand-500 mr-2"></i>Nakit Özeti
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-slate-800/50 rounded-lg p-4 text-center">
                    <div class="text-xs text-gray-500 mb-1">Açılış Bakiye</div>
                    <div class="text-lg font-bold text-gray-900">{{ number_format($register->opening_amount, 2) }} ₺</div>
                </div>
                <div class="bg-slate-800/50 rounded-lg p-4 text-center">
                    <div class="text-xs text-gray-500 mb-1">+ Nakit Satışlar</div>
                    <div class="text-lg font-bold text-emerald-600">{{ number_format($stats['cash_total'] ?? 0, 2) }} ₺</div>
                </div>
                <div class="bg-blue-900/30 rounded-lg p-4 text-center border border-blue-500/30">
                    <div class="text-xs text-brand-600 mb-1">= Beklenen Nakit</div>
                    <div class="text-lg font-bold text-brand-600">{{ number_format($register->opening_amount + ($stats['cash_total'] ?? 0), 2) }} ₺</div>
                </div>
            </div>
        </div>

        {{-- Kasa İşlem Butonları --}}
        <div class="flex justify-center gap-3">
            <button @click="printXReport()"
                    class="px-6 py-3.5 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl transition-colors text-sm flex items-center gap-2 shadow-lg">
                <i class="fas fa-print"></i>
                X Raporu Yazdır
            </button>
            <button @click="showCloseModal = true"
                    class="px-8 py-3.5 bg-red-600 hover:bg-red-500 text-gray-900 font-bold rounded-xl transition-colors text-sm flex items-center gap-2 shadow-lg shadow-red-900/30">
                <i class="fas fa-lock"></i>
                Kasayı Kapat (Z Raporu)
            </button>
        </div>
        @endif

        {{-- ═══════════════════════════════════════════ --}}
        {{-- ÖNCEKİ Z RAPORLARI --}}
        {{-- ═══════════════════════════════════════════ --}}
        @if($zReports->isNotEmpty())
        <div class="bg-white rounded-xl border border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">
                    <i class="fas fa-file-lines text-amber-600 mr-2"></i>Önceki Z Raporları
                </h3>
                <span class="text-xs text-gray-500">{{ $zReports->count() }} rapor</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-800/50">
                        <tr class="text-gray-500 text-xs uppercase tracking-wider">
                            <th class="text-left py-3 px-4 font-medium">Tarih</th>
                            <th class="text-left py-3 px-4 font-medium">Açılış</th>
                            <th class="text-left py-3 px-4 font-medium">Kapanış</th>
                            <th class="text-right py-3 px-4 font-medium">Açılış Bakiye</th>
                            <th class="text-right py-3 px-4 font-medium">Kapanış Bakiye</th>
                            <th class="text-right py-3 px-4 font-medium">Toplam Satış</th>
                            <th class="text-right py-3 px-4 font-medium">Fark</th>
                            <th class="py-3 px-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($zReports as $report)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-3 px-4 text-gray-900 font-medium">
                                {{ $report->opened_at->format('d.m.Y') }}
                            </td>
                            <td class="py-3 px-4 text-gray-700">
                                {{ $report->opened_at->format('H:i') }}
                            </td>
                            <td class="py-3 px-4 text-gray-700">
                                {{ $report->closed_at ? $report->closed_at->format('H:i') : '-' }}
                            </td>
                            <td class="py-3 px-4 text-right text-gray-700">
                                {{ number_format($report->opening_amount, 2) }} ₺
                            </td>
                            <td class="py-3 px-4 text-right text-gray-700">
                                {{ number_format($report->closing_amount ?? 0, 2) }} ₺
                            </td>
                            <td class="py-3 px-4 text-right text-gray-900 font-semibold">
                                {{ number_format($report->total_sales ?? 0, 2) }} ₺
                            </td>
                            <td class="py-3 px-4 text-right font-semibold
                                {{ ($report->difference ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                @if(($report->difference ?? 0) != 0)
                                    {{ ($report->difference ?? 0) > 0 ? '+' : '' }}{{ number_format($report->difference ?? 0, 2) }} ₺
                                @else
                                    <span class="text-gray-500">0.00 ₺</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <button @click="printZReport({
                                    date: '{{ $report->opened_at->format('d.m.Y') }}',
                                    opened: '{{ $report->opened_at->format('H:i') }}',
                                    closed: '{{ $report->closed_at ? $report->closed_at->format('H:i') : '-' }}',
                                    opening: {{ $report->opening_amount }},
                                    closing: {{ $report->closing_amount ?? 0 }},
                                    total: {{ $report->total_sales ?? 0 }},
                                    diff: {{ $report->difference ?? 0 }}
                                })" class="text-gray-400 hover:text-brand-500 transition-colors" title="Z Raporu Yazdır">
                                    <i class="fas fa-print"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- KASA KAPAT MODAL --}}
    {{-- ═══════════════════════════════════════════ --}}
    @if($register)
    <div x-show="showCloseModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        {{-- Overlay --}}
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showCloseModal = false"></div>

        {{-- Modal İçeriği --}}
        <div class="relative bg-gray-50 rounded-2xl border border-gray-700 w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             @click.away="showCloseModal = false">

            {{-- Modal Başlık --}}
            <div class="p-5 border-b border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-file-invoice text-amber-600 mr-2"></i>Kasa Kapat — Z Raporu
                </h3>
                <button @click="showCloseModal = false" class="text-gray-500 hover:text-gray-800 transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            {{-- Z Rapor Özeti --}}
            <div class="p-5 space-y-4">
                <div class="bg-slate-800/50 rounded-xl p-4 space-y-2.5 text-sm">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Gün Sonu Özeti</div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Açılış Saati:</span>
                        <span class="text-gray-900">{{ $register->opened_at->format('H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Açan Personel:</span>
                        <span class="text-gray-900">{{ $register->user->name ?? '-' }}</span>
                    </div>

                    <div class="border-t border-gray-700 my-2"></div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Açılış Bakiye:</span>
                        <span class="text-gray-900 font-medium">{{ number_format($register->opening_amount, 2) }} ₺</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Nakit Satışlar:</span>
                        <span class="text-emerald-600 font-medium">+{{ number_format($stats['cash_total'] ?? 0, 2) }} ₺</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Kart Satışlar:</span>
                        <span class="text-brand-500 font-medium">{{ number_format($stats['card_total'] ?? 0, 2) }} ₺</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Toplam Satış Adedi:</span>
                        <span class="text-gray-900">{{ $stats['sale_count'] ?? 0 }}</span>
                    </div>

                    <div class="border-t border-gray-700 my-2"></div>

                    <div class="flex justify-between text-base">
                        <span class="text-gray-700 font-semibold">Beklenen Nakit:</span>
                        <span class="text-brand-600 font-bold">{{ number_format($register->opening_amount + ($stats['cash_total'] ?? 0), 2) }} ₺</span>
                    </div>
                </div>

                {{-- Sayılan Nakit Girişi --}}
                <form method="POST" action="{{ route('pos.cash-register.close') }}" class="space-y-4" id="closeRegisterForm">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sayılan Nakit (₺)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-3.5 text-gray-500 font-medium">₺</span>
                            <input type="number" name="actual_cash" step="0.01" min="0" required
                                   x-model.number="actualCash"
                                   class="w-full pl-10 pr-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-gray-900 text-lg font-semibold placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-transparent"
                                   placeholder="0.00">
                        </div>
                    </div>

                    {{-- Fark Gösterimi --}}
                    <div class="bg-slate-800/50 rounded-lg p-3 flex items-center justify-between"
                         x-show="actualCash !== null && actualCash !== ''">
                        <span class="text-sm text-gray-500">Fark:</span>
                        <span class="text-lg font-bold"
                              :class="cashDifference >= 0 ? 'text-emerald-600' : 'text-red-500'"
                              x-text="(cashDifference >= 0 ? '+' : '') + cashDifference.toFixed(2) + ' ₺'">
                        </span>
                    </div>
                    <div x-show="actualCash !== null && actualCash !== '' && cashDifference < 0"
                         class="text-xs text-red-500 flex items-center gap-1">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Kasada beklenen tutardan daha az nakit var!</span>
                    </div>

                    {{-- Notlar --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kapanış Notu (İsteğe bağlı)</label>
                        <textarea name="notes" rows="2"
                                  class="w-full px-4 py-2.5 bg-slate-700/50 border border-slate-600 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-sm resize-none"
                                  placeholder="Varsa not ekleyin..."></textarea>
                    </div>

                    {{-- Aksiyonlar --}}
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showCloseModal = false"
                                class="flex-1 py-3 bg-slate-700 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-colors text-sm">
                            İptal
                        </button>
                        <button type="submit"
                                class="flex-1 py-3 bg-red-600 hover:bg-red-500 text-gray-900 font-bold rounded-xl transition-colors text-sm flex items-center justify-center gap-2">
                            <i class="fas fa-lock"></i>
                            Kasayı Kapat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- ╔════════════════════════════════════════╗ --}}
    {{-- ║    SATIŞ LİSTESİ MODAL                ║ --}}
    {{-- ╚════════════════════════════════════════╝ --}}
    <div x-show="showSalesModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl shadow-2xl w-[720px] max-w-[96vw] max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between p-5 border-b border-gray-100">
                <div>
                    <h3 class="text-lg font-bold text-gray-900" x-text="salesModalTitle"></h3>
                    <p class="text-xs text-gray-400 mt-0.5" x-text="salesList.length + ' satış kaydı'"></p>
                </div>
                <button @click="showSalesModal = false" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times"></i></button>
            </div>
            <div x-show="salesLoading" class="flex-1 flex items-center justify-center py-12">
                <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
            </div>
            <div x-show="!salesLoading" class="flex-1 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100 sticky top-0">
                        <tr class="text-xs text-gray-500 uppercase tracking-wider">
                            <th class="text-left px-4 py-3 font-medium">Tarih/Saat</th>
                            <th class="text-left px-4 py-3 font-medium">Fiş No</th>
                            <th class="text-left px-4 py-3 font-medium">Müşteri</th>
                            <th class="text-left px-4 py-3 font-medium">Personel</th>
                            <th class="text-left px-4 py-3 font-medium">Ödeme</th>
                            <th class="text-right px-4 py-3 font-medium">Tutar</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="sale in salesList" :key="sale.id">
                            <tr class="hover:bg-gray-50 cursor-pointer" @click="openSaleDetail(sale.id)">
                                <td class="px-4 py-2.5 text-gray-500" x-text="sale.sold_at"></td>
                                <td class="px-4 py-2.5">
                                    <span class="text-xs font-mono text-gray-700 bg-gray-100 px-1.5 py-0.5 rounded" x-text="sale.receipt_no || '—'"></span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="font-medium text-gray-800" x-text="sale.customer_name"></span>
                                </td>
                                <td class="px-4 py-2.5 text-gray-500" x-text="sale.staff_name"></td>
                                <td class="px-4 py-2.5">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                          :class="{
                                            'bg-emerald-50 text-emerald-700': sale.payment_method === 'cash',
                                            'bg-brand-50 text-brand-700': sale.payment_method === 'card',
                                            'bg-amber-50 text-amber-700': sale.payment_method === 'credit',
                                            'bg-purple-50 text-purple-700': sale.payment_method === 'mixed',
                                          }"
                                          x-text="sale.payment_method === 'cash' ? 'Nakit' : sale.payment_method === 'card' ? 'Kart' : sale.payment_method === 'credit' ? 'Veresiye' : 'Karışık'"></span>
                                </td>
                                <td class="px-4 py-2.5 text-right font-bold text-gray-900" x-text="parseFloat(sale.grand_total).toFixed(2) + ' ₺'"></td>
                                <td class="px-4 py-2.5 text-gray-400"><i class="fas fa-chevron-right text-xs"></i></td>
                            </tr>
                        </template>
                        <tr x-show="salesList.length === 0">
                            <td colspan="7" class="text-center py-10 text-gray-400">Bu dönemde kayıt bulunamadı</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div x-show="!salesLoading && salesList.length > 0" class="border-t border-gray-100 px-5 py-3 flex items-center justify-between bg-gray-50 rounded-b-2xl">
                <span class="text-sm text-gray-500">Toplam</span>
                <span class="text-lg font-bold text-gray-900" x-text="salesList.reduce((s, x) => s + parseFloat(x.grand_total), 0).toFixed(2) + ' ₺'"></span>
            </div>
        </div>
    </div>

    {{-- ╔════════════════════════════════════════╗ --}}
    {{-- ║    SATIŞ DETAY MODAL                  ║ --}}
    {{-- ╚════════════════════════════════════════╝ --}}
    <div x-show="showSaleDetail" x-transition class="fixed inset-0 z-60 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl shadow-2xl w-[500px] max-w-[96vw] max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between p-5 border-b border-gray-100">
                <div>
                    <h3 class="text-md font-bold text-gray-900">Satış Detayı</h3>
                    <p class="text-xs text-gray-400" x-text="saleDetail ? 'Fiş: ' + saleDetail.receipt_no + ' — ' + saleDetail.sold_at : ''"></p>
                </div>
                <button @click="showSaleDetail = false" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times"></i></button>
            </div>
            <div x-show="saleDetailLoading" class="flex-1 flex items-center justify-center py-12">
                <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
            </div>
            <div x-show="saleDetail && !saleDetailLoading" class="flex-1 overflow-y-auto p-5 space-y-4">
                {{-- Özet --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 rounded-xl p-3">
                        <div class="text-xs text-gray-400">Müşteri</div>
                        <div class="font-semibold text-gray-800 mt-0.5" x-text="saleDetail?.customer_name"></div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <div class="text-xs text-gray-400">Personel</div>
                        <div class="font-semibold text-gray-800 mt-0.5" x-text="saleDetail?.staff_name"></div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <div class="text-xs text-gray-400">Ödeme Yöntemi</div>
                        <div class="font-semibold mt-0.5"
                             :class="{'text-emerald-600': saleDetail?.payment_method==='cash', 'text-brand-600': saleDetail?.payment_method==='card', 'text-amber-600': saleDetail?.payment_method==='credit', 'text-purple-600': saleDetail?.payment_method==='mixed'}"
                             x-text="saleDetail?.payment_method === 'cash' ? 'Nakit' : saleDetail?.payment_method === 'card' ? 'Kart' : saleDetail?.payment_method === 'credit' ? 'Veresiye' : 'Karışık'"></div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <div class="text-xs text-gray-400">Toplam Tutar</div>
                        <div class="text-lg font-bold text-gray-900 mt-0.5" x-text="parseFloat(saleDetail?.grand_total || 0).toFixed(2) + ' ₺'"></div>
                    </div>
                </div>
                {{-- Karışık ödeme dağılımı --}}
                <div x-show="saleDetail?.payment_method === 'mixed'" class="bg-purple-50 rounded-xl p-3 space-y-1.5">
                    <div class="text-xs font-semibold text-purple-600 mb-2">Ödeme Dağılımı</div>
                    <div class="flex justify-between text-sm" x-show="parseFloat(saleDetail?.cash_amount) > 0">
                        <span class="text-gray-600"><i class="fas fa-money-bill-wave text-emerald-500 mr-1"></i>Nakit</span>
                        <span class="font-medium" x-text="parseFloat(saleDetail?.cash_amount || 0).toFixed(2) + ' ₺'"></span>
                    </div>
                    <div class="flex justify-between text-sm" x-show="parseFloat(saleDetail?.card_amount) > 0">
                        <span class="text-gray-600"><i class="fas fa-credit-card text-brand-500 mr-1"></i>Kart</span>
                        <span class="font-medium" x-text="parseFloat(saleDetail?.card_amount || 0).toFixed(2) + ' ₺'"></span>
                    </div>
                    <div class="flex justify-between text-sm" x-show="parseFloat(saleDetail?.credit_amount) > 0">
                        <span class="text-gray-600"><i class="fas fa-user-clock text-amber-500 mr-1"></i>Veresiye</span>
                        <span class="font-medium" x-text="parseFloat(saleDetail?.credit_amount || 0).toFixed(2) + ' ₺'"></span>
                    </div>
                </div>
                {{-- Kalemler --}}
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase mb-2">Ürünler</div>
                    <div class="border border-gray-100 rounded-xl overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-xs text-gray-400">
                                    <th class="text-left px-3 py-2 font-medium">Ürün</th>
                                    <th class="text-center px-3 py-2 font-medium">Adet</th>
                                    <th class="text-right px-3 py-2 font-medium">Birim</th>
                                    <th class="text-right px-3 py-2 font-medium">Toplam</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <template x-for="item in saleDetail?.items" :key="item.product_name">
                                    <tr>
                                        <td class="px-3 py-2 text-gray-800" x-text="item.product_name"></td>
                                        <td class="px-3 py-2 text-center text-gray-500" x-text="item.quantity"></td>
                                        <td class="px-3 py-2 text-right text-gray-600" x-text="parseFloat(item.unit_price).toFixed(2) + ' ₺'"></td>
                                        <td class="px-3 py-2 text-right font-semibold text-gray-900" x-text="parseFloat(item.total).toFixed(2) + ' ₺'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div x-show="saleDetail?.notes" class="bg-amber-50 rounded-xl p-3">
                    <div class="text-xs text-amber-600 mb-1">Not</div>
                    <div class="text-sm text-gray-700" x-text="saleDetail?.notes"></div>
                </div>
            </div>
            {{-- Fiş Yazdır Butonu --}}
            <div x-show="saleDetail && !saleDetailLoading" class="p-4 border-t border-gray-100 flex justify-end">
                <button @click="printSaleReceipt()" class="px-5 py-2.5 bg-brand-500 hover:bg-brand-600 text-white font-semibold rounded-xl transition-colors text-sm flex items-center gap-2">
                    <i class="fas fa-print"></i> Fişi Yazdır
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function cashRegisterScreen() {
    return {
        showCloseModal: false,
        actualCash: null,

        expectedCash: {{ $register ? $register->opening_amount + ($stats['cash_total'] ?? 0) : 0 }},

        get cashDifference() {
            if (this.actualCash === null || this.actualCash === '') return 0;
            return parseFloat(this.actualCash) - this.expectedCash;
        },

        // Satış listesi modal
        showSalesModal: false,
        salesLoading: false,
        salesModalTitle: '',
        salesList: [],

        // Satış detay modal
        showSaleDetail: false,
        saleDetailLoading: false,
        saleDetail: null,

        async openSalesModal(type) {
            const titles = { cash: 'Nakit Satışlar', card: 'Kart Satışları', credit: 'Veresiye Satışlar', all: 'Tüm Satışlar' };
            this.salesModalTitle = titles[type] || 'Satışlar';
            this.showSalesModal = true;
            this.salesLoading = true;
            this.salesList = [];
            try {
                const data = await posAjax('{{ route("pos.cash-register.sales-detail") }}?type=' + type, {}, 'GET');
                this.salesList = data.sales || [];
            } catch(e) {
                showToast('Satışlar yüklenemedi', 'error');
            } finally {
                this.salesLoading = false;
            }
        },

        async openSaleDetail(saleId) {
            this.showSaleDetail = true;
            this.saleDetailLoading = true;
            this.saleDetail = null;
            try {
                const data = await posAjax('{{ url("/cash-register/sale-items") }}/' + saleId, {}, 'GET');
                this.saleDetail = data.sale;
            } catch(e) {
                showToast('Detay yüklenemedi', 'error');
            } finally {
                this.saleDetailLoading = false;
            }
        },

        // Fiş yazdır (satış detay modalından)
        printSaleReceipt() {
            if (!this.saleDetail) return;
            const s = this.saleDetail;
            let rows = '';
            (s.items || []).forEach(item => {
                rows += `<tr><td style="text-align:left">${item.product_name}</td><td style="text-align:center">${item.quantity}</td><td style="text-align:right">${parseFloat(item.unit_price).toFixed(2)}</td><td style="text-align:right">${parseFloat(item.total).toFixed(2)}</td></tr>`;
            });
            const w = window.open('', '_blank', 'width=320,height=600');
            if (!w) { showToast('Popup engelleyici aktif!', 'error'); return; }
            w.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>Fiş</title>
            <style>body{font-family:'Courier New',monospace;font-size:12px;margin:0;padding:8px;width:280px}.center{text-align:center}.bold{font-weight:bold}.line{border-top:1px dashed #000;margin:6px 0}table{width:100%;border-collapse:collapse}td{padding:2px 0;font-size:11px}.total-row td{font-weight:bold;font-size:13px;padding-top:4px}@media print{@page{margin:2mm;size:80mm auto}}</style></head><body>
                <div class="center bold" style="font-size:14px">{{ config('app.name', 'EMARE POS') }}</div>
                <div class="center" style="font-size:10px">${s.sold_at}</div>
                <div class="center" style="font-size:10px">Fiş: ${s.receipt_no}</div>
                <div class="line"></div>
                <table><tr style="font-weight:bold;border-bottom:1px solid #000"><td>Ürün</td><td style="text-align:center">Ad.</td><td style="text-align:right">Fiyat</td><td style="text-align:right">Tutar</td></tr>${rows}</table>
                <div class="line"></div>
                <table><tr class="total-row"><td>TOPLAM</td><td colspan="3" style="text-align:right">${parseFloat(s.grand_total).toFixed(2)} ₺</td></tr>
                <tr><td>Ödeme</td><td colspan="3" style="text-align:right;text-transform:capitalize">${s.payment_method === 'cash' ? 'Nakit' : s.payment_method === 'card' ? 'Kart' : s.payment_method === 'credit' ? 'Veresiye' : 'Karışık'}</td></tr></table>
                <div class="line"></div>
                <div class="center" style="font-size:10px;margin-top:8px">Teşekkür ederiz!</div>
            </body></html>`);
            w.document.close();
            w.onafterprint = () => w.close();
            setTimeout(() => { w.focus(); w.print(); }, 300);
        },

        // X Raporu yazdır (anlık kasa durumu)
        printXReport() {
            const w = window.open('', '_blank', 'width=320,height=500');
            if (!w) { showToast('Popup engelleyici aktif!', 'error'); return; }
            const now = new Date().toLocaleString('tr-TR');
            w.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>X Raporu</title>
            <style>body{font-family:'Courier New',monospace;font-size:12px;margin:0;padding:8px;width:280px}.center{text-align:center}.bold{font-weight:bold}.line{border-top:1px dashed #000;margin:6px 0}.row{display:flex;justify-content:space-between;padding:2px 0}@media print{@page{margin:2mm;size:80mm auto}}</style></head><body>
                <div class="center bold" style="font-size:14px">{{ config('app.name', 'EMARE POS') }}</div>
                <div class="center bold" style="font-size:12px;margin:4px 0">X RAPORU (Ara Rapor)</div>
                <div class="center" style="font-size:10px">${now}</div>
                <div class="line"></div>
                <div class="row"><span>Açılış Saati:</span><span>{{ $register ? $register->opened_at->format('H:i') : '-' }}</span></div>
                <div class="row"><span>Açan:</span><span>{{ $register ? ($register->user->name ?? '-') : '-' }}</span></div>
                <div class="line"></div>
                <div class="row"><span>Açılış Bakiye:</span><span>{{ number_format($register ? $register->opening_amount : 0, 2) }} ₺</span></div>
                <div class="row"><span>Nakit Satış:</span><span>{{ number_format($stats['cash_total'] ?? 0, 2) }} ₺</span></div>
                <div class="row"><span>Kart Satış:</span><span>{{ number_format($stats['card_total'] ?? 0, 2) }} ₺</span></div>
                <div class="row"><span>Veresiye:</span><span>{{ number_format($stats['credit_total'] ?? 0, 2) }} ₺</span></div>
                <div class="row"><span>Satış Adedi:</span><span>{{ $stats['sale_count'] ?? 0 }}</span></div>
                <div class="line"></div>
                <div class="row bold" style="font-size:13px"><span>Toplam Satış:</span><span>{{ number_format(($stats['cash_total'] ?? 0) + ($stats['card_total'] ?? 0) + ($stats['credit_total'] ?? 0), 2) }} ₺</span></div>
                <div class="row bold" style="font-size:13px"><span>Beklenen Nakit:</span><span>{{ number_format(($register ? $register->opening_amount : 0) + ($stats['cash_total'] ?? 0), 2) }} ₺</span></div>
                <div class="line"></div>
                <div class="center" style="font-size:9px;margin-top:6px">* Bu bir ara rapordur, kasa kapatılmamıştır.</div>
            </body></html>`);
            w.document.close();
            w.onafterprint = () => w.close();
            setTimeout(() => { w.focus(); w.print(); }, 300);
        },

        // Z Raporu yazdır (geçmiş rapor)
        printZReport(report) {
            const w = window.open('', '_blank', 'width=320,height=500');
            if (!w) { showToast('Popup engelleyici aktif!', 'error'); return; }
            w.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>Z Raporu</title>
            <style>body{font-family:'Courier New',monospace;font-size:12px;margin:0;padding:8px;width:280px}.center{text-align:center}.bold{font-weight:bold}.line{border-top:1px dashed #000;margin:6px 0}.row{display:flex;justify-content:space-between;padding:2px 0}@media print{@page{margin:2mm;size:80mm auto}}</style></head><body>
                <div class="center bold" style="font-size:14px">{{ config('app.name', 'EMARE POS') }}</div>
                <div class="center bold" style="font-size:12px;margin:4px 0">Z RAPORU</div>
                <div class="center" style="font-size:10px">${report.date}</div>
                <div class="line"></div>
                <div class="row"><span>Açılış:</span><span>${report.opened}</span></div>
                <div class="row"><span>Kapanış:</span><span>${report.closed}</span></div>
                <div class="line"></div>
                <div class="row"><span>Açılış Bakiye:</span><span>${report.opening.toFixed(2)} ₺</span></div>
                <div class="row"><span>Kapanış Bakiye:</span><span>${report.closing.toFixed(2)} ₺</span></div>
                <div class="line"></div>
                <div class="row bold" style="font-size:13px"><span>Toplam Satış:</span><span>${report.total.toFixed(2)} ₺</span></div>
                <div class="row"><span>Fark:</span><span style="color:${report.diff >= 0 ? '#059669' : '#ef4444'}">${report.diff >= 0 ? '+' : ''}${report.diff.toFixed(2)} ₺</span></div>
                <div class="line"></div>
                <div class="center" style="font-size:9px;margin-top:6px">Gün sonu Z raporu</div>
            </body></html>`);
            w.document.close();
            w.onafterprint = () => w.close();
            setTimeout(() => { w.focus(); w.print(); }, 300);
        },
    };
}
</script>
@endpush

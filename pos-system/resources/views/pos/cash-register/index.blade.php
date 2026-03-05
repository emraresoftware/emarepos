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
            <div class="bg-white rounded-xl border border-gray-700 p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Nakit Satış</span>
                    <span class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-money-bill-wave text-emerald-600 text-sm"></i>
                    </span>
                </div>
                <div class="text-2xl font-bold text-emerald-600">{{ number_format($stats['cash_total'] ?? 0, 2) }} ₺</div>
                <div class="text-xs text-gray-500 mt-1">Nakit tahsilat toplamı</div>
            </div>

            {{-- Kart Satışlar --}}
            <div class="bg-white rounded-xl border border-gray-700 p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Kart Satış</span>
                    <span class="w-8 h-8 bg-brand-500/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-credit-card text-brand-500 text-sm"></i>
                    </span>
                </div>
                <div class="text-2xl font-bold text-brand-500">{{ number_format($stats['card_total'] ?? 0, 2) }} ₺</div>
                <div class="text-xs text-gray-500 mt-1">Kredi/banka kartı toplamı</div>
            </div>

            {{-- Toplam Satış --}}
            <div class="bg-white rounded-xl border border-gray-700 p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Toplam Satış</span>
                    <span class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-purple-600 text-sm"></i>
                    </span>
                </div>
                <div class="text-2xl font-bold text-gray-900">{{ number_format(($stats['cash_total'] ?? 0) + ($stats['card_total'] ?? 0), 2) }} ₺</div>
                <div class="text-xs text-gray-500 mt-1">{{ $stats['sale_count'] ?? 0 }} adet satış</div>
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

        {{-- Kasa Kapat Butonu --}}
        <div class="flex justify-center">
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
    };
}
</script>
@endpush

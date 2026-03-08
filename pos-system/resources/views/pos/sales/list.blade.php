@extends('pos.layouts.app')
@section('title', 'Satış Geçmişi')

@section('content')
<div x-data="salesList()" class="flex-1 overflow-y-auto p-6 space-y-6">

    {{-- Top Bar --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Satış Geçmişi</h1>
            <p class="text-sm text-gray-500 mt-0.5">Tüm satış kayıtları ve detayları</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('pos.sales.list') }}" class="no-print">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block font-medium">Başlangıç</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                           class="bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 w-36 transition-all">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block font-medium">Bitiş</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                           class="bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 w-36 transition-all">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block font-medium">Ödeme Yöntemi</label>
                    <select name="payment_method"
                            class="bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 w-36 transition-all">
                        <option value="">Tümü</option>
                        <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Nakit</option>
                        <option value="card" {{ request('payment_method') === 'card' ? 'selected' : '' }}>Kredi Kartı</option>
                        <option value="mixed" {{ request('payment_method') === 'mixed' ? 'selected' : '' }}>Karışık</option>
                        <option value="credit" {{ request('payment_method') === 'credit' ? 'selected' : '' }}>Veresiye</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block font-medium">Fiş No</label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Fiş no ara..."
                               class="bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3 py-2 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 w-40 transition-all">
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="px-4 py-2 bg-gradient-to-r from-brand-500 to-purple-600 text-white rounded-xl text-sm font-semibold shadow-sm hover:shadow-md hover:shadow-brand-200 transition-all">
                        <i class="fas fa-filter mr-1"></i> Filtrele
                    </button>
                    <a href="{{ route('pos.sales.list') }}"
                       class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-medium transition-colors">
                        <i class="fas fa-times mr-1"></i> Temizle
                    </a>
                </div>
            </div>
        </div>
    </form>

    {{-- Summary Row --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-500 text-xs font-medium">Toplam Satış</span>
                <div class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center"><i class="fas fa-receipt text-brand-500 text-sm"></i></div>
            </div>
            <div class="text-lg font-bold text-gray-900">{{ number_format($summaryStats['total'] ?? 0, 2, ',', '.') }} ₺</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-500 text-xs font-medium">Nakit</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center"><i class="fas fa-money-bill-wave text-emerald-500 text-sm"></i></div>
            </div>
            <div class="text-lg font-bold text-emerald-600">{{ number_format($summaryStats['cash'] ?? 0, 2, ',', '.') }} ₺</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-500 text-xs font-medium">Kart</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center"><i class="fas fa-credit-card text-purple-500 text-sm"></i></div>
            </div>
            <div class="text-lg font-bold text-purple-600">{{ number_format($summaryStats['card'] ?? 0, 2, ',', '.') }} ₺</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-500 text-xs font-medium">İade</span>
                <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center"><i class="fas fa-undo text-red-500 text-sm"></i></div>
            </div>
            <div class="text-lg font-bold text-red-600">{{ number_format($summaryStats['refunded'] ?? 0, 2, ',', '.') }} ₺</div>
        </div>
    </div>

    {{-- Sales Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-lg shadow-gray-100/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="text-left text-gray-500 font-semibold py-3 px-4 w-8"></th>
                        <th class="text-left text-gray-500 font-semibold py-3 px-4">Fiş No</th>
                        <th class="text-left text-gray-500 font-semibold py-3 px-4">Tarih / Saat</th>
                        <th class="text-left text-gray-500 font-semibold py-3 px-4">Müşteri</th>
                        <th class="text-center text-gray-500 font-semibold py-3 px-4">Ürün Sayısı</th>
                        <th class="text-right text-gray-500 font-semibold py-3 px-4">Ara Toplam</th>
                        <th class="text-right text-gray-500 font-semibold py-3 px-4">İndirim</th>
                        <th class="text-right text-gray-500 font-semibold py-3 px-4">KDV</th>
                        <th class="text-right text-gray-500 font-semibold py-3 px-4">Genel Toplam</th>
                        <th class="text-center text-gray-500 font-semibold py-3 px-4">Ödeme</th>
                        <th class="text-center text-gray-500 font-semibold py-3 px-4">Durum</th>
                        <th class="text-center text-gray-500 font-semibold py-3 px-4">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        <tr class="border-b border-gray-50 hover:bg-gray-50/80 transition-colors cursor-pointer"
                            @click="toggle({{ $sale->id }})">
                            <td class="py-3 px-4">
                                <i class="fas fa-chevron-right text-gray-400 text-xs transition-transform duration-200"
                                   :class="expanded === {{ $sale->id }} ? 'rotate-90 text-brand-500' : ''"></i>
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-gray-900 font-mono font-medium">{{ $sale->receipt_no }}</span>
                            </td>
                            <td class="py-3 px-4 text-gray-700">
                                {{ $sale->sold_at?->format('d.m.Y') }}
                                <span class="text-gray-400 ml-1">{{ $sale->sold_at?->format('H:i') }}</span>
                            </td>
                            <td class="py-3 px-4">
                                @if($sale->customer)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 bg-brand-50 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-brand-500 text-[10px]"></i>
                                        </div>
                                        <span class="text-gray-700">{{ $sale->customer->name }}</span>
                                    </div>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center text-gray-700">
                                <span class="bg-gray-100 px-2 py-0.5 rounded-full text-xs font-medium">{{ $sale->items->count() }}</span>
                            </td>
                            <td class="py-3 px-4 text-right text-gray-700">{{ number_format($sale->subtotal, 2, ',', '.') }} ₺</td>
                            <td class="py-3 px-4 text-right">
                                @if($sale->discount_total > 0)
                                    <span class="text-amber-600">-{{ number_format($sale->discount_total, 2, ',', '.') }} ₺</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right text-gray-500">{{ number_format($sale->vat_total, 2, ',', '.') }} ₺</td>
                            <td class="py-3 px-4 text-right text-gray-900 font-bold">{{ number_format($sale->grand_total, 2, ',', '.') }} ₺</td>
                            <td class="py-3 px-4 text-center">
                                @switch($sale->payment_method)
                                    @case('cash')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-600 border border-emerald-200">
                                            <i class="fas fa-money-bill-wave text-[10px]"></i> Nakit
                                        </span>
                                        @break
                                    @case('card')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-purple-50 text-purple-600 border border-purple-200">
                                            <i class="fas fa-credit-card text-[10px]"></i> Kart
                                        </span>
                                        @break
                                    @case('mixed')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-brand-50 text-brand-600 border border-brand-200">
                                            <i class="fas fa-shuffle text-[10px]"></i> Karışık
                                        </span>
                                        @break
                                    @case('credit')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-50 text-amber-600 border border-amber-200">
                                            <i class="fas fa-clock text-[10px]"></i> Veresiye
                                        </span>
                                        @break
                                    @default
                                        <span class="text-gray-400 text-xs">{{ $sale->payment_method }}</span>
                                @endswitch
                            </td>
                            <td class="py-3 px-4 text-center">
                                @switch($sale->status)
                                    @case('completed')
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-600 border border-emerald-200">Tamamlandı</span>
                                        @break
                                    @case('refunded')
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-red-50 text-red-600 border border-red-200">İade Edildi</span>
                                        @break
                                    @case('cancelled')
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-500 border border-gray-200">İptal</span>
                                        @break
                                    @default
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-500 border border-gray-200">{{ $sale->status }}</span>
                                @endswitch
                            </td>
                            <td class="py-3 px-4 text-center" @click.stop>
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="toggle({{ $sale->id }})"
                                            class="px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 rounded-lg text-xs text-gray-600 hover:text-gray-800 transition-colors"
                                            title="Detay">
                                        <i class="fas fa-eye"></i> Detay
                                    </button>
                                    <button @click="printSaleReceipt({
                                        receipt_no: '{{ $sale->receipt_no }}',
                                        sold_at: '{{ $sale->sold_at?->format('d.m.Y H:i') }}',
                                        grand_total: {{ $sale->grand_total }},
                                        payment_method: '{{ $sale->payment_method }}',
                                        items: {{ json_encode($sale->items->map(fn($i) => ['product_name' => $i->product_name, 'quantity' => $i->quantity, 'unit_price' => $i->unit_price, 'total' => $i->total])) }}
                                    })"
                                            class="px-2.5 py-1.5 bg-brand-50 hover:bg-brand-100 rounded-lg text-xs text-brand-600 hover:text-brand-800 transition-colors border border-brand-200"
                                            title="Yaıdır">
                                        <i class="fas fa-print"></i>
                                    </button>
                                    @if($sale->status === 'completed')
                                        <button @click="openRefundModal({{ $sale->id }}, '{{ $sale->receipt_no }}')"
                                                class="px-2.5 py-1.5 bg-red-50 hover:bg-red-100 rounded-lg text-xs text-red-500 hover:text-red-700 transition-colors border border-red-200"
                                                title="İade">
                                            <i class="fas fa-undo"></i> İade
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <tr x-show="expanded === {{ $sale->id }}" x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0">
                            <td colspan="12" class="bg-gray-50/80 px-6 py-4">
                                <div class="text-xs text-gray-500 uppercase font-semibold mb-2 tracking-wide">
                                    <i class="fas fa-list mr-1"></i> Satış Kalemleri — {{ $sale->receipt_no }}
                                </div>
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-200">
                                            <th class="text-left text-gray-500 font-medium py-2 px-3 text-xs">Ürün</th>
                                            <th class="text-center text-gray-500 font-medium py-2 px-3 text-xs">Adet</th>
                                            <th class="text-right text-gray-500 font-medium py-2 px-3 text-xs">Birim Fiyat</th>
                                            <th class="text-right text-gray-500 font-medium py-2 px-3 text-xs">İndirim</th>
                                            <th class="text-right text-gray-500 font-medium py-2 px-3 text-xs">Toplam</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sale->items as $item)
                                            <tr class="border-b border-gray-100">
                                                <td class="py-2 px-3 text-gray-700">{{ $item->product_name }}</td>
                                                <td class="py-2 px-3 text-center text-gray-700">{{ $item->quantity }}</td>
                                                <td class="py-2 px-3 text-right text-gray-500">{{ number_format($item->unit_price, 2, ',', '.') }} ₺</td>
                                                <td class="py-2 px-3 text-right">
                                                    @if($item->discount > 0)
                                                        <span class="text-amber-600">-{{ number_format($item->discount, 2, ',', '.') }} ₺</span>
                                                    @else
                                                        <span class="text-gray-300">—</span>
                                                    @endif
                                                </td>
                                                <td class="py-2 px-3 text-right text-gray-900 font-medium">{{ number_format($item->total, 2, ',', '.') }} ₺</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="py-16 text-center text-gray-400">
                                <i class="fas fa-receipt text-4xl mb-3"></i>
                                <p class="text-sm">Satış kaydı bulunamadı</p>
                                <p class="text-xs text-gray-400 mt-1">Filtreleri değiştirerek tekrar deneyin</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $sales->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    {{-- Refund Modal --}}
    <div x-show="refundModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeRefundModal()"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-200 w-full max-w-md mx-4 p-6"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-undo text-red-500"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Satış İadesi</h3>
                    <p class="text-xs text-gray-500">Fiş: <span class="text-gray-700 font-mono" x-text="refundReceiptNo"></span></p>
                </div>
            </div>

            <div class="mb-4">
                <label class="text-sm text-gray-700 mb-1.5 block font-medium">İade Sebebi <span class="text-red-500">*</span></label>
                <textarea x-model="refundReason" rows="3"
                          placeholder="İade sebebini yazınız..."
                          class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 resize-none transition-all"></textarea>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4">
                <div class="flex items-start gap-2">
                    <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5"></i>
                    <p class="text-xs text-amber-700">Bu işlem geri alınamaz. Satış iade edildiğinde kasa hareketleri otomatik olarak güncellenir.</p>
                </div>
            </div>

            <div class="flex items-center gap-2 justify-end">
                <button @click="closeRefundModal()"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-medium transition-colors">
                    İptal
                </button>
                <button @click="submitRefund()" :disabled="refundLoading || !refundReason.trim()"
                        class="px-4 py-2 bg-red-500 hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl text-sm font-semibold transition-all flex items-center gap-2 shadow-sm hover:shadow-md">
                    <i class="fas fa-spinner fa-spin" x-show="refundLoading"></i>
                    <i class="fas fa-undo" x-show="!refundLoading"></i>
                    <span>İade Et</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function salesList() {
        return {
            expanded: null,
            refundModal: false,
            refundSaleId: null,
            refundReceiptNo: '',
            refundReason: '',
            refundLoading: false,

            toggle(saleId) {
                this.expanded = this.expanded === saleId ? null : saleId;
            },

            openRefundModal(saleId, receiptNo) {
                this.refundSaleId = saleId;
                this.refundReceiptNo = receiptNo;
                this.refundReason = '';
                this.refundLoading = false;
                this.refundModal = true;
            },

            closeRefundModal() {
                this.refundModal = false;
                this.refundSaleId = null;
                this.refundReceiptNo = '';
                this.refundReason = '';
            },

            printSaleReceipt(sale) {
                let rows = '';
                (sale.items || []).forEach(item => {
                    rows += `<tr><td style="text-align:left">${item.product_name}</td><td style="text-align:center">${item.quantity}</td><td style="text-align:right">${parseFloat(item.unit_price).toFixed(2)}</td><td style="text-align:right">${parseFloat(item.total).toFixed(2)}</td></tr>`;
                });
                const pmLabel = {cash:'Nakit',card:'Kart',credit:'Veresiye',mixed:'Karışık'}[sale.payment_method] || sale.payment_method;
                const w = window.open('', '_blank', 'width=320,height=600');
                if (!w) { showToast('Popup engelleyici aktif!', 'error'); return; }
                w.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>Fiş</title>
                <style>body{font-family:'Courier New',monospace;font-size:12px;margin:0;padding:8px;width:280px}.center{text-align:center}.bold{font-weight:bold}.line{border-top:1px dashed #000;margin:6px 0}table{width:100%;border-collapse:collapse}td{padding:2px 0;font-size:11px}.total-row td{font-weight:bold;font-size:13px;padding-top:4px}@media print{@page{margin:2mm;size:80mm auto}}</style></head><body>
                    <div class="center bold" style="font-size:14px">{{ config('app.name', 'EMARE POS') }}</div>
                    <div class="center" style="font-size:10px">${sale.sold_at}</div>
                    <div class="center" style="font-size:10px">Fiş: ${sale.receipt_no}</div>
                    <div class="line"></div>
                    <table><tr style="font-weight:bold;border-bottom:1px solid #000"><td>Ürün</td><td style="text-align:center">Ad.</td><td style="text-align:right">Fiyat</td><td style="text-align:right">Tutar</td></tr>${rows}</table>
                    <div class="line"></div>
                    <table><tr class="total-row"><td>TOPLAM</td><td colspan="3" style="text-align:right">${parseFloat(sale.grand_total).toFixed(2)} ₺</td></tr>
                    <tr><td>Ödeme</td><td colspan="3" style="text-align:right">${pmLabel}</td></tr></table>
                    <div class="line"></div>
                    <div class="center" style="font-size:10px;margin-top:8px">Teşekkür ederiz!</div>
                </body></html>`);
                w.document.close();
                w.onafterprint = () => w.close();
                setTimeout(() => { w.focus(); w.print(); }, 300);
            },

            async submitRefund() {
                if (!this.refundReason.trim()) {
                    showToast('Lütfen iade sebebini giriniz.', 'error');
                    return;
                }

                this.refundLoading = true;
                try {
                    const url = `{{ url('pos/sale') }}/${this.refundSaleId}/refund`;
                    const result = await posAjax(url, {
                        method: 'POST',
                        body: JSON.stringify({ reason: this.refundReason })
                    });
                    showToast(result.message || 'Satış başarıyla iade edildi.', 'success');
                    this.closeRefundModal();
                    setTimeout(() => window.location.reload(), 800);
                } catch (err) {
                    showToast(err.message || 'İade işlemi sırasında bir hata oluştu.', 'error');
                } finally {
                    this.refundLoading = false;
                }
            }
        };
    }
</script>
@endpush

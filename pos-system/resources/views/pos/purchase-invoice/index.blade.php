@extends('pos.layouts.app')
@section('title', 'Alış Faturaları')

@section('content')
<div class="p-6 overflow-y-auto h-full" x-data="purchaseInvoiceManager()" x-cloak>
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Alış Faturaları</h1>
            <p class="text-sm text-gray-500">Tedarikçi alış faturalarını yönetin</p>
        </div>
        <button @click="openNewForm()" class="px-4 py-2.5 bg-brand-500 hover:bg-gradient-to-r from-brand-500 to-purple-600 text-gray-900 rounded-lg text-sm font-semibold transition-all">
            <i class="fas fa-file-invoice mr-2"></i>Yeni Fatura
        </button>
    </div>

    {{-- Fatura Listesi --}}
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50">
                    <th class="text-left py-3 px-4 text-gray-500 font-medium">Fatura No</th>
                    <th class="text-left py-3 px-4 text-gray-500 font-medium">Firma</th>
                    <th class="text-left py-3 px-4 text-gray-500 font-medium">Tarih</th>
                    <th class="text-right py-3 px-4 text-gray-500 font-medium">Tutar</th>
                    <th class="text-center py-3 px-4 text-gray-500 font-medium">Ödeme</th>
                    <th class="text-center py-3 px-4 text-gray-500 font-medium">Durum</th>
                    <th class="text-right py-3 px-4 text-gray-500 font-medium">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                <tr class="border-b border-gray-50 hover:bg-gray-50/50">
                    <td class="py-3 px-4 font-mono text-xs text-gray-900">{{ $inv->invoice_no ?: 'PI-'.$inv->id }}</td>
                    <td class="py-3 px-4 text-gray-900 font-medium">{{ $inv->firm->name ?? '-' }}</td>
                    <td class="py-3 px-4 text-gray-500 text-xs">{{ \Carbon\Carbon::parse($inv->invoice_date)->format('d.m.Y') }}</td>
                    <td class="py-3 px-4 text-right font-semibold text-gray-900">{{ number_format($inv->grand_total, 2, ',', '.') }} ₺</td>
                    <td class="py-3 px-4 text-center">
                        @if($inv->payment_status === 'paid')
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-600">Ödendi</span>
                        @elseif($inv->payment_status === 'partial')
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-50 text-yellow-600">Kısmi</span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-500">Ödenmedi</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center">
                        @if($inv->status === 'received')
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-600">Alındı</span>
                        @elseif($inv->status === 'returned')
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-orange-50 text-orange-500">İade</span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">İptal</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button @click="viewInvoice({{ $inv->id }})" class="text-blue-500 hover:text-blue-700 text-xs" title="Detay">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button @click="editInvoice({{ $inv->id }})" class="text-amber-500 hover:text-amber-700 text-xs" title="Düzenle">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button @click="deleteInvoice({{ $inv->id }})" class="text-red-400 hover:text-red-600 text-xs" title="Sil">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-gray-500">
                        <i class="fas fa-file-invoice text-3xl mb-3 text-gray-300"></i>
                        <p>Henüz alış faturası bulunmuyor</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="px-4 py-3">
            {{ $invoices->links() }}
        </div>
    </div>

    {{-- Yeni/Düzenle Fatura Modal --}}
    <template x-if="showForm">
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showForm = false">
        <div class="bg-white rounded-2xl w-full max-w-5xl max-h-[90vh] flex flex-col">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900" x-text="editingId ? 'Fatura Düzenle' : 'Yeni Alış Faturası'"></h2>
                <button @click="showForm = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 overflow-y-auto flex-1 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Firma (Tedarikçi)</label>
                        <select x-model="form.firm_id" :disabled="!!editingId"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 disabled:opacity-50">
                            <option value="">Firma Seçin</option>
                            @foreach($firms as $firm)
                                <option value="{{ $firm->id }}">{{ $firm->name }} ({{ $firm->type === 'supplier' ? 'Tedarikçi' : $firm->type }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Fatura No</label>
                        <input x-model="form.invoice_no" type="text" placeholder="Fatura numarası"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Fatura Tarihi</label>
                        <input x-model="form.invoice_date" type="date"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 mb-1 block">Not</label>
                    <input x-model="form.notes" type="text" placeholder="İsteğe bağlı not"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500">
                </div>

                {{-- Ürün Ekleme --}}
                <div class="bg-gray-50 rounded-xl p-4" x-show="!editingId">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text" x-model="productSearch" @input="filterProducts()"
                            placeholder="Ürün ara (ad veya barkod)..."
                            class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div class="max-h-40 overflow-y-auto mt-2" x-show="productSearch.length > 0">
                        <template x-for="product in filteredProducts" :key="product.id">
                            <button @click="addItem(product)"
                                class="w-full flex items-center justify-between p-2 hover:bg-brand-500/10 rounded-lg text-sm transition">
                                <span>
                                    <span x-text="product.name" class="text-gray-900 font-medium"></span>
                                    <span x-text="product.barcode" class="text-gray-400 ml-2 text-xs"></span>
                                </span>
                                <span class="text-gray-500 text-xs">Alış: <span x-text="product.purchase_price || 0"></span> ₺</span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Fatura Kalemleri --}}
                <div class="overflow-x-auto" x-show="form.items.length > 0">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-2 px-3 text-gray-500 font-medium">Ürün</th>
                                <th class="text-center py-2 px-3 text-gray-500 font-medium w-20">Miktar</th>
                                <th class="text-center py-2 px-3 text-gray-500 font-medium w-28">Birim Fiyat</th>
                                <th class="text-center py-2 px-3 text-gray-500 font-medium w-20">KDV %</th>
                                <th class="text-center py-2 px-3 text-gray-500 font-medium w-24">İskonto</th>
                                <th class="text-right py-2 px-3 text-gray-500 font-medium">Satır Toplamı</th>
                                <th class="text-right py-2 px-3 w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, idx) in form.items" :key="idx">
                                <tr class="border-b border-gray-100">
                                    <td class="py-2 px-3 text-gray-900 font-medium" x-text="item.product_name"></td>
                                    <td class="py-2 px-3">
                                        <input type="number" x-model.number="item.quantity" min="0.01" step="1"
                                            class="w-full text-center px-2 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500">
                                    </td>
                                    <td class="py-2 px-3">
                                        <input type="number" x-model.number="item.unit_price" min="0" step="0.01"
                                            class="w-full text-center px-2 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500">
                                    </td>
                                    <td class="py-2 px-3">
                                        <input type="number" x-model.number="item.vat_rate" min="0" max="100" step="1"
                                            class="w-full text-center px-2 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500">
                                    </td>
                                    <td class="py-2 px-3">
                                        <input type="number" x-model.number="item.discount" min="0" step="0.01"
                                            class="w-full text-center px-2 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500">
                                    </td>
                                    <td class="py-2 px-3 text-right font-semibold text-gray-900"
                                        x-text="calcLineTotal(item).toFixed(2) + ' ₺'"></td>
                                    <td class="py-2 px-3 text-right" x-show="!editingId">
                                        <button @click="form.items.splice(idx, 1)" class="text-red-400 hover:text-red-600">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-200">
                                <td colspan="5" class="py-3 px-3 text-right text-gray-700 font-medium">Ara Toplam:</td>
                                <td class="py-3 px-3 text-right font-bold text-gray-900" x-text="calcSubtotal().toFixed(2) + ' ₺'"></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="py-1 px-3 text-right text-gray-700 font-medium">KDV Toplam:</td>
                                <td class="py-1 px-3 text-right font-semibold text-purple-600" x-text="calcVatTotal().toFixed(2) + ' ₺'"></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="py-1 px-3 text-right text-gray-700 font-bold">Genel Toplam:</td>
                                <td class="py-1 px-3 text-right font-bold text-lg text-emerald-600" x-text="(calcSubtotal() + calcVatTotal()).toFixed(2) + ' ₺'"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="p-6 border-t border-gray-100 flex justify-end gap-3">
                <button @click="showForm = false" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">İptal</button>
                <button @click="saveInvoice()" :disabled="saving"
                    class="px-5 py-2.5 bg-brand-500 hover:bg-gradient-to-r from-brand-500 to-purple-600 text-gray-900 rounded-lg text-sm font-semibold transition disabled:opacity-50">
                    <i class="fas fa-save mr-1"></i><span x-text="editingId ? 'Güncelle' : 'Kaydet'"></span>
                </button>
            </div>
        </div>
    </div>
    </template>

    {{-- Detay Modal --}}
    <template x-if="detailModal">
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="detailModal = false">
        <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[80vh] flex flex-col">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900" x-text="'Fatura: ' + (detailData?.invoice_no || detailData?.id)"></h2>
                    <p class="text-sm text-gray-500" x-text="detailData?.firm?.name"></p>
                </div>
                <button @click="detailModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <div class="grid grid-cols-3 gap-4 mb-4 text-sm">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <span class="text-gray-500 text-xs">Ara Toplam</span>
                        <p class="font-bold text-gray-900" x-text="Number(detailData?.subtotal || 0).toFixed(2) + ' ₺'"></p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <span class="text-gray-500 text-xs">KDV</span>
                        <p class="font-bold text-purple-600" x-text="Number(detailData?.vat_total || 0).toFixed(2) + ' ₺'"></p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <span class="text-gray-500 text-xs">Genel Toplam</span>
                        <p class="font-bold text-emerald-600" x-text="Number(detailData?.grand_total || 0).toFixed(2) + ' ₺'"></p>
                    </div>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-3 text-gray-500 font-medium">Ürün</th>
                            <th class="text-center py-2 px-3 text-gray-500 font-medium">Miktar</th>
                            <th class="text-center py-2 px-3 text-gray-500 font-medium">Birim Fiyat</th>
                            <th class="text-center py-2 px-3 text-gray-500 font-medium">KDV %</th>
                            <th class="text-right py-2 px-3 text-gray-500 font-medium">Toplam</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in detailData?.items || []" :key="item.id">
                            <tr class="border-b border-gray-50">
                                <td class="py-2 px-3 text-gray-900" x-text="item.product_name"></td>
                                <td class="py-2 px-3 text-center text-gray-700" x-text="item.quantity"></td>
                                <td class="py-2 px-3 text-center text-gray-700" x-text="Number(item.unit_price).toFixed(2) + ' ₺'"></td>
                                <td class="py-2 px-3 text-center text-gray-500" x-text="'%' + item.vat_rate"></td>
                                <td class="py-2 px-3 text-right font-semibold text-gray-900" x-text="Number(item.total).toFixed(2) + ' ₺'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
function purchaseInvoiceManager() {
    const allProducts = @json($products);
    return {
        showForm: false,
        editingId: null,
        detailModal: false,
        detailData: null,
        saving: false,
        productSearch: '',
        filteredProducts: [],
        form: { firm_id: '', invoice_no: '', invoice_date: new Date().toISOString().slice(0,10), notes: '', items: [] },

        openNewForm() {
            this.editingId = null;
            this.form = { firm_id: '', invoice_no: '', invoice_date: new Date().toISOString().slice(0,10), notes: '', items: [] };
            this.showForm = true;
        },

        filterProducts() {
            const s = this.productSearch.toLowerCase();
            if (!s) { this.filteredProducts = []; return; }
            this.filteredProducts = allProducts.filter(p =>
                p.name.toLowerCase().includes(s) || (p.barcode && p.barcode.includes(s))
            ).slice(0, 20);
        },

        addItem(product) {
            this.form.items.push({
                product_id: product.id,
                product_name: product.name,
                quantity: 1,
                unit_price: product.purchase_price || 0,
                vat_rate: 20,
                discount: 0
            });
            this.productSearch = '';
            this.filteredProducts = [];
        },

        calcLineTotal(item) {
            const base = (item.quantity * item.unit_price) - (item.discount || 0);
            const vat = base * ((item.vat_rate || 0) / 100);
            return base + vat;
        },

        calcSubtotal() {
            return this.form.items.reduce((sum, item) => {
                return sum + (item.quantity * item.unit_price) - (item.discount || 0);
            }, 0);
        },

        calcVatTotal() {
            return this.form.items.reduce((sum, item) => {
                const base = (item.quantity * item.unit_price) - (item.discount || 0);
                return sum + base * ((item.vat_rate || 0) / 100);
            }, 0);
        },

        async saveInvoice() {
            if (this.editingId) {
                this.saving = true;
                try {
                    const res = await posAjax('/purchase-invoices/' + this.editingId, {
                        invoice_no: this.form.invoice_no,
                        invoice_date: this.form.invoice_date,
                        notes: this.form.notes,
                        payment_status: this.form.payment_status,
                    }, 'PUT');
                    if (res.success) { showToast('Fatura güncellendi', 'success'); location.reload(); }
                } catch (e) { showToast('Hata oluştu', 'error'); }
                this.saving = false;
                return;
            }

            if (!this.form.firm_id) { showToast('Firma seçin', 'error'); return; }
            if (this.form.items.length === 0) { showToast('En az 1 ürün ekleyin', 'error'); return; }
            this.saving = true;
            try {
                const res = await posAjax('/purchase-invoices', {
                    firm_id: this.form.firm_id,
                    invoice_no: this.form.invoice_no,
                    invoice_date: this.form.invoice_date,
                    notes: this.form.notes,
                    items: this.form.items.map(i => ({
                        product_id: i.product_id,
                        quantity: i.quantity,
                        unit_price: i.unit_price,
                        vat_rate: i.vat_rate,
                        discount: i.discount
                    }))
                });
                if (res.success) { showToast('Fatura kaydedildi', 'success'); location.reload(); }
            } catch (e) { showToast('Hata oluştu', 'error'); }
            this.saving = false;
        },

        async viewInvoice(id) {
            try {
                const res = await posAjax('/purchase-invoices/' + id, {}, 'GET');
                this.detailData = res;
                this.detailModal = true;
            } catch (e) { showToast('Detay yüklenemedi', 'error'); }
        },

        async editInvoice(id) {
            try {
                const res = await posAjax('/purchase-invoices/' + id, {}, 'GET');
                this.editingId = id;
                this.form = {
                    firm_id: res.firm_id,
                    invoice_no: res.invoice_no || '',
                    invoice_date: res.invoice_date,
                    notes: res.notes || '',
                    payment_status: res.payment_status,
                    items: (res.items || []).map(i => ({
                        product_id: i.product_id,
                        product_name: i.product_name,
                        quantity: i.quantity,
                        unit_price: i.unit_price,
                        vat_rate: i.vat_rate,
                        discount: i.discount || 0
                    }))
                };
                this.showForm = true;
            } catch (e) { showToast('Veri yüklenemedi', 'error'); }
        },

        async deleteInvoice(id) {
            if (!confirm('Fatura silinecek ve stoklar geri alınacak. Emin misiniz?')) return;
            try {
                const res = await posAjax('/purchase-invoices/' + id, {}, 'DELETE');
                if (res.success) { showToast('Fatura silindi', 'success'); location.reload(); }
            } catch (e) { showToast('Hata oluştu', 'error'); }
        }
    };
}
</script>
@endpush

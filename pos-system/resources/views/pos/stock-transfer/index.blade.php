@extends('pos.layouts.app')
@section('title', 'Şubeler Arası Transfer')

@section('content')
<div class="p-6 overflow-y-auto h-full" x-data="stockTransferManager()" x-cloak>
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Şubeler Arası Transfer</h1>
            <p class="text-sm text-gray-500">Şubeler arası ürün transferlerini yönetin</p>
        </div>
        <button @click="showForm = true" class="px-4 py-2.5 bg-brand-500 hover:bg-gradient-to-r from-brand-500 to-purple-600 text-gray-900 rounded-lg text-sm font-semibold transition-all">
            <i class="fas fa-exchange-alt mr-2"></i>Yeni Transfer
        </button>
    </div>

    {{-- Transfer Listesi --}}
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50">
                    <th class="text-left py-3 px-4 text-gray-500 font-medium">Kod</th>
                    <th class="text-left py-3 px-4 text-gray-500 font-medium">Gönderen</th>
                    <th class="text-left py-3 px-4 text-gray-500 font-medium">Alan</th>
                    <th class="text-center py-3 px-4 text-gray-500 font-medium">Ürün</th>
                    <th class="text-center py-3 px-4 text-gray-500 font-medium">Durum</th>
                    <th class="text-left py-3 px-4 text-gray-500 font-medium">Tarih</th>
                    <th class="text-right py-3 px-4 text-gray-500 font-medium">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transfers as $transfer)
                <tr class="border-b border-gray-50 hover:bg-gray-50/50">
                    <td class="py-3 px-4 font-mono text-xs text-gray-900">{{ $transfer->code }}</td>
                    <td class="py-3 px-4 text-gray-900">{{ $transfer->fromBranch->name ?? '-' }}</td>
                    <td class="py-3 px-4 text-gray-900">{{ $transfer->toBranch->name ?? '-' }}</td>
                    <td class="py-3 px-4 text-center text-gray-700">{{ $transfer->items->count() }}</td>
                    <td class="py-3 px-4 text-center">
                        @if($transfer->status === 'pending')
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-50 text-yellow-600">Beklemede</span>
                        @elseif($transfer->status === 'completed')
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-600">Tamamlandı</span>
                        @elseif($transfer->status === 'rejected')
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-500">Reddedildi</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-gray-500 text-xs">{{ $transfer->created_at->format('d.m.Y H:i') }}</td>
                    <td class="py-3 px-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button @click="viewTransfer({{ $transfer->id }})" class="text-blue-500 hover:text-blue-700 text-xs" title="Detay">
                                <i class="fas fa-eye"></i>
                            </button>
                            @if($transfer->status === 'pending')
                            <button @click="approveTransfer({{ $transfer->id }})" class="text-green-500 hover:text-green-700 text-xs" title="Onayla">
                                <i class="fas fa-check-circle"></i>
                            </button>
                            <button @click="rejectTransfer({{ $transfer->id }})" class="text-red-400 hover:text-red-600 text-xs" title="Reddet">
                                <i class="fas fa-times-circle"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-gray-500">
                        <i class="fas fa-exchange-alt text-3xl mb-3 text-gray-300"></i>
                        <p>Henüz transfer bulunmuyor</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="px-4 py-3">
            {{ $transfers->links() }}
        </div>
    </div>

    {{-- Yeni Transfer Modal --}}
    <template x-if="showForm">
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showForm = false">
        <div class="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900">Yeni Stok Transferi</h2>
                <button @click="showForm = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 overflow-y-auto flex-1 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Hedef Şube</label>
                        <select x-model="form.to_branch_id"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500">
                            <option value="">Şube Seçin</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Not</label>
                        <input x-model="form.notes" type="text" placeholder="İsteğe bağlı not"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                {{-- Ürün Ekleme --}}
                <div class="bg-gray-50 rounded-xl p-4">
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
                                <span class="text-gray-500 text-xs">Stok: <span x-text="product.stock_quantity"></span></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Ürün Tablosu --}}
                <div class="overflow-x-auto" x-show="form.items.length > 0">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-2 px-3 text-gray-500 font-medium">Ürün</th>
                                <th class="text-center py-2 px-3 text-gray-500 font-medium">Mevcut Stok</th>
                                <th class="text-center py-2 px-3 text-gray-500 font-medium">Transfer Miktarı</th>
                                <th class="text-right py-2 px-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, idx) in form.items" :key="idx">
                                <tr class="border-b border-gray-100">
                                    <td class="py-2 px-3 text-gray-900 font-medium" x-text="item.product_name"></td>
                                    <td class="py-2 px-3 text-center text-gray-500" x-text="item.stock"></td>
                                    <td class="py-2 px-3 text-center">
                                        <input type="number" x-model.number="item.quantity" min="1" :max="item.stock"
                                            class="w-20 text-center px-2 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500">
                                    </td>
                                    <td class="py-2 px-3 text-right">
                                        <button @click="form.items.splice(idx, 1)" class="text-red-400 hover:text-red-600">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="p-6 border-t border-gray-100 flex justify-end gap-3">
                <button @click="showForm = false" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">İptal</button>
                <button @click="saveTransfer()" :disabled="saving || form.items.length === 0 || !form.to_branch_id"
                    class="px-5 py-2.5 bg-brand-500 hover:bg-gradient-to-r from-brand-500 to-purple-600 text-gray-900 rounded-lg text-sm font-semibold transition disabled:opacity-50">
                    <i class="fas fa-paper-plane mr-1"></i>Transfer Gönder
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
                    <h2 class="text-lg font-bold text-gray-900" x-text="detailData?.code"></h2>
                    <p class="text-sm text-gray-500">
                        <span x-text="detailData?.from_branch?.name"></span>
                        <i class="fas fa-arrow-right mx-2 text-brand-500"></i>
                        <span x-text="detailData?.to_branch?.name"></span>
                    </p>
                </div>
                <button @click="detailModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-3 text-gray-500 font-medium">Ürün</th>
                            <th class="text-center py-2 px-3 text-gray-500 font-medium">Miktar</th>
                            <th class="text-left py-2 px-3 text-gray-500 font-medium">Not</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in detailData?.items || []" :key="item.id">
                            <tr class="border-b border-gray-50">
                                <td class="py-2 px-3 text-gray-900" x-text="item.product_name"></td>
                                <td class="py-2 px-3 text-center font-semibold text-gray-700" x-text="item.quantity"></td>
                                <td class="py-2 px-3 text-gray-500 text-xs" x-text="item.note || '-'"></td>
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
function stockTransferManager() {
    const allProducts = @json($products);
    return {
        showForm: false,
        detailModal: false,
        detailData: null,
        saving: false,
        productSearch: '',
        filteredProducts: [],
        form: { to_branch_id: '', notes: '', items: [] },

        filterProducts() {
            const s = this.productSearch.toLowerCase();
            if (!s) { this.filteredProducts = []; return; }
            const addedIds = this.form.items.map(i => i.product_id);
            this.filteredProducts = allProducts.filter(p =>
                !addedIds.includes(p.id) &&
                (p.name.toLowerCase().includes(s) || (p.barcode && p.barcode.includes(s)))
            ).slice(0, 20);
        },

        addItem(product) {
            if (this.form.items.find(i => i.product_id === product.id)) return;
            this.form.items.push({
                product_id: product.id,
                product_name: product.name,
                stock: product.stock_quantity || 0,
                quantity: 1
            });
            this.productSearch = '';
            this.filteredProducts = [];
        },

        async saveTransfer() {
            if (!this.form.to_branch_id) { showToast('Hedef şube seçin', 'error'); return; }
            if (this.form.items.length === 0) { showToast('En az 1 ürün ekleyin', 'error'); return; }
            this.saving = true;
            try {
                const res = await posAjax('/stock-transfers', {
                    to_branch_id: this.form.to_branch_id,
                    notes: this.form.notes,
                    items: this.form.items.map(i => ({
                        product_id: i.product_id,
                        quantity: i.quantity
                    }))
                });
                if (res.success) {
                    showToast('Transfer oluşturuldu', 'success');
                    location.reload();
                }
            } catch (e) { showToast('Hata oluştu', 'error'); }
            this.saving = false;
        },

        async viewTransfer(id) {
            try {
                const res = await posAjax('/stock-transfers/' + id, {}, 'GET');
                this.detailData = res;
                this.detailModal = true;
            } catch (e) { showToast('Detay yüklenemedi', 'error'); }
        },

        async approveTransfer(id) {
            if (!confirm('Transfer onaylanacak ve stoklar güncellenecek. Devam?')) return;
            try {
                const res = await posAjax('/stock-transfers/' + id + '/approve');
                if (res.success) {
                    showToast('Transfer onaylandı', 'success');
                    location.reload();
                }
            } catch (e) { showToast('Hata oluştu', 'error'); }
        },

        async rejectTransfer(id) {
            if (!confirm('Transfer reddedilecek. Emin misiniz?')) return;
            try {
                const res = await posAjax('/stock-transfers/' + id + '/reject');
                if (res.success) {
                    showToast('Transfer reddedildi', 'success');
                    location.reload();
                }
            } catch (e) { showToast('Hata oluştu', 'error'); }
        }
    };
}
</script>
@endpush

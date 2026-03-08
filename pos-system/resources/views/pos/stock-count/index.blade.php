@extends('pos.layouts.app')
@section('title', 'Stok Sayımı')

@section('content')
<div class="p-6 overflow-y-auto h-full" x-data="stockCountManager()" x-cloak>
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Stok Sayımı</h1>
            <p class="text-sm text-gray-500">Fiziksel envanter sayımı ile sistem stoklarını eşitleyin</p>
        </div>
        <button @click="showForm = true" class="px-4 py-2.5 bg-brand-500 hover:bg-gradient-to-r from-brand-500 to-purple-600 text-white rounded-lg text-sm font-semibold transition-all">
            <i class="fas fa-plus mr-2"></i>Yeni Sayım
        </button>
    </div>

    {{-- Sayım Listesi --}}
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50">
                    <th class="text-left py-3 px-4 text-gray-500 font-medium">Kod</th>
                    <th class="text-left py-3 px-4 text-gray-500 font-medium">Başlık</th>
                    <th class="text-center py-3 px-4 text-gray-500 font-medium">Ürün Sayısı</th>
                    <th class="text-center py-3 px-4 text-gray-500 font-medium">Durum</th>
                    <th class="text-left py-3 px-4 text-gray-500 font-medium">Tarih</th>
                    <th class="text-right py-3 px-4 text-gray-500 font-medium">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($counts as $count)
                <tr class="border-b border-gray-50 hover:bg-gray-50/50">
                    <td class="py-3 px-4 font-mono text-xs text-gray-900">{{ $count->code }}</td>
                    <td class="py-3 px-4 text-gray-900 font-medium">{{ $count->title }}</td>
                    <td class="py-3 px-4 text-center text-gray-700">{{ $count->items->count() }}</td>
                    <td class="py-3 px-4 text-center">
                        @if($count->status === 'draft')
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-50 text-yellow-600">Taslak</span>
                        @elseif($count->status === 'applied')
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-600">Uygulandı</span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-500">İptal</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-gray-500 text-xs">{{ $count->created_at->format('d.m.Y H:i') }}</td>
                    <td class="py-3 px-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button @click="viewCount({{ $count->id }})" class="text-blue-500 hover:text-blue-700 text-xs">
                                <i class="fas fa-eye"></i>
                            </button>
                            @if($count->status === 'draft')
                            <button @click="applyCount({{ $count->id }})" class="text-green-500 hover:text-green-700 text-xs" title="Stoklara Uygula">
                                <i class="fas fa-check-circle"></i>
                            </button>
                            <button @click="deleteCount({{ $count->id }})" class="text-red-400 hover:text-red-600 text-xs">
                                <i class="fas fa-trash"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-gray-500">
                        <i class="fas fa-clipboard-list text-3xl mb-3 text-gray-300"></i>
                        <p>Henüz stok sayımı yapılmamış</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="px-4 py-3">
            {{ $counts->links() }}
        </div>
    </div>

    {{-- Yeni Sayım Modal --}}
    <template x-if="showForm">
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showForm = false">
        <div class="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900">Yeni Stok Sayımı</h2>
                <button @click="showForm = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 overflow-y-auto flex-1 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Sayım Başlığı</label>
                        <input x-model="form.title" type="text" placeholder="Ör: Mart ayı genel sayım"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Not</label>
                        <input x-model="form.notes" type="text" placeholder="İsteğe bağlı not"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>
                </div>

                {{-- Ürün Ekleme --}}
                <div class="bg-gray-50 rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex-1 relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="text" x-model="productSearch" @input="filterProducts()"
                                placeholder="Ürün ara (ad veya barkod)..."
                                class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500">
                        </div>
                        <button @click="addAllProducts()" class="px-4 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium transition">
                            <i class="fas fa-layer-group mr-1"></i>Tümünü Ekle
                        </button>
                    </div>
                    <div class="max-h-40 overflow-y-auto" x-show="productSearch.length > 0">
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

                {{-- Sayım Tablosu --}}
                <div class="overflow-x-auto" x-show="form.items.length > 0">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-2 px-3 text-gray-500 font-medium">Ürün</th>
                                <th class="text-center py-2 px-3 text-gray-500 font-medium">Barkod</th>
                                <th class="text-center py-2 px-3 text-gray-500 font-medium">Sistem Stok</th>
                                <th class="text-center py-2 px-3 text-gray-500 font-medium">Sayılan</th>
                                <th class="text-center py-2 px-3 text-gray-500 font-medium">Fark</th>
                                <th class="text-right py-2 px-3 text-gray-500 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, idx) in form.items" :key="idx">
                                <tr class="border-b border-gray-100">
                                    <td class="py-2 px-3 text-gray-900 font-medium" x-text="item.product_name"></td>
                                    <td class="py-2 px-3 text-center text-gray-500 text-xs" x-text="item.barcode"></td>
                                    <td class="py-2 px-3 text-center text-gray-700" x-text="item.system_quantity"></td>
                                    <td class="py-2 px-3 text-center">
                                        <input type="number" x-model.number="item.counted_quantity" min="0" step="1"
                                            @input="item.difference = item.counted_quantity - item.system_quantity"
                                            class="w-20 text-center px-2 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500">
                                    </td>
                                    <td class="py-2 px-3 text-center font-semibold"
                                        :class="item.difference > 0 ? 'text-green-600' : item.difference < 0 ? 'text-red-500' : 'text-gray-400'"
                                        x-text="(item.difference > 0 ? '+' : '') + item.difference">
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

                {{-- Özet --}}
                <div class="flex items-center gap-4 text-sm" x-show="form.items.length > 0">
                    <span class="text-gray-500">Toplam: <strong x-text="form.items.length" class="text-gray-900"></strong> ürün</span>
                    <span class="text-red-500">Eksik: <strong x-text="form.items.filter(i => i.difference < 0).length"></strong></span>
                    <span class="text-green-600">Fazla: <strong x-text="form.items.filter(i => i.difference > 0).length"></strong></span>
                    <span class="text-gray-400">Eşit: <strong x-text="form.items.filter(i => i.difference === 0).length"></strong></span>
                </div>
            </div>
            <div class="p-6 border-t border-gray-100 flex justify-end gap-3">
                <button @click="showForm = false" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">İptal</button>
                <button @click="saveCount()" :disabled="saving || form.items.length === 0"
                    class="px-5 py-2.5 bg-brand-500 hover:bg-gradient-to-r from-brand-500 to-purple-600 text-white rounded-lg text-sm font-semibold transition disabled:opacity-50">
                    <i class="fas fa-save mr-1"></i>Sayımı Kaydet
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
                    <p class="text-sm text-gray-500" x-text="detailData?.title"></p>
                </div>
                <button @click="detailModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-3 text-gray-500 font-medium">Ürün</th>
                            <th class="text-center py-2 px-3 text-gray-500 font-medium">Sistem</th>
                            <th class="text-center py-2 px-3 text-gray-500 font-medium">Sayılan</th>
                            <th class="text-center py-2 px-3 text-gray-500 font-medium">Fark</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in detailData?.items || []" :key="item.id">
                            <tr class="border-b border-gray-50">
                                <td class="py-2 px-3 text-gray-900" x-text="item.product_name"></td>
                                <td class="py-2 px-3 text-center text-gray-700" x-text="item.system_quantity"></td>
                                <td class="py-2 px-3 text-center text-gray-700" x-text="item.counted_quantity"></td>
                                <td class="py-2 px-3 text-center font-semibold"
                                    :class="item.difference > 0 ? 'text-green-600' : item.difference < 0 ? 'text-red-500' : 'text-gray-400'"
                                    x-text="(item.difference > 0 ? '+' : '') + item.difference">
                                </td>
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
function stockCountManager() {
    const allProducts = @json($products);
    return {
        showForm: false,
        detailModal: false,
        detailData: null,
        saving: false,
        productSearch: '',
        filteredProducts: [],
        form: { title: '', notes: '', items: [] },

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
                barcode: product.barcode || '',
                system_quantity: product.stock_quantity || 0,
                counted_quantity: product.stock_quantity || 0,
                difference: 0
            });
            this.productSearch = '';
            this.filteredProducts = [];
        },

        addAllProducts() {
            allProducts.forEach(p => {
                if (!this.form.items.find(i => i.product_id === p.id)) {
                    this.form.items.push({
                        product_id: p.id,
                        product_name: p.name,
                        barcode: p.barcode || '',
                        system_quantity: p.stock_quantity || 0,
                        counted_quantity: p.stock_quantity || 0,
                        difference: 0
                    });
                }
            });
        },

        async saveCount() {
            if (!this.form.title) { showToast('Başlık giriniz', 'error'); return; }
            if (this.form.items.length === 0) { showToast('En az 1 ürün ekleyin', 'error'); return; }
            this.saving = true;
            try {
                const res = await posAjax('/stock-count', {
                    title: this.form.title,
                    notes: this.form.notes,
                    items: this.form.items.map(i => ({
                        product_id: i.product_id,
                        counted_quantity: i.counted_quantity
                    }))
                });
                if (res.success) {
                    showToast('Stok sayımı kaydedildi', 'success');
                    location.reload();
                }
            } catch (e) {
                showToast('Hata oluştu', 'error');
            }
            this.saving = false;
        },

        async viewCount(id) {
            try {
                const res = await posAjax('/stock-count/' + id, {}, 'GET');
                this.detailData = res;
                this.detailModal = true;
            } catch (e) { showToast('Detay yüklenemedi', 'error'); }
        },

        async applyCount(id) {
            if (!confirm('Bu sayım stoklara uygulanacak. Onaylıyor musunuz?')) return;
            try {
                const res = await posAjax('/stock-count/' + id + '/apply');
                if (res.success) {
                    showToast('Sayım stoklara uygulandı', 'success');
                    location.reload();
                }
            } catch (e) { showToast('Hata oluştu', 'error'); }
        },

        async deleteCount(id) {
            if (!confirm('Sayım silinecek, emin misiniz?')) return;
            try {
                const res = await posAjax('/stock-count/' + id, {}, 'DELETE');
                if (res.success) {
                    showToast('Sayım silindi', 'success');
                    location.reload();
                }
            } catch (e) { showToast('Hata oluştu', 'error'); }
        }
    };
}
</script>
@endpush

@extends('pos.layouts.app')

@section('title', 'Ürünler')

@section('content')
<div x-data="productManager()" x-cloak>
    {{-- Top Bar --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Ürünler</h1>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1 md:justify-end">
            <div class="relative">
                <input type="text"
                       x-model="searchQuery"
                       @input.debounce.400ms="applyFilters()"
                       placeholder="Ürün ara (ad, barkod)..."
                       class="bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl pl-10 pr-4 py-2.5 w-full sm:w-72 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400 transition-all">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <select x-model="categoryFilter"
                    @change="applyFilters()"
                    class="bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                <option value="">Tüm Kategoriler</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <button @click="openCreate()"
                    class="bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 text-white font-semibold rounded-xl text-sm px-5 py-2.5 transition-all flex items-center gap-2 justify-center whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Yeni Ürün
            </button>
        </div>
    </div>

    {{-- Product Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-lg shadow-gray-100/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3.5 font-semibold">Görsel</th>
                        <th class="px-4 py-3.5 font-semibold">Barkod</th>
                        <th class="px-4 py-3.5 font-semibold">Ürün Adı</th>
                        <th class="px-4 py-3.5 font-semibold">Kategori</th>
                        <th class="px-4 py-3.5 text-right font-semibold">Alış Fiyatı</th>
                        <th class="px-4 py-3.5 text-right font-semibold">Satış Fiyatı</th>
                        <th class="px-4 py-3.5 text-center font-semibold">KDV%</th>
                        <th class="px-4 py-3.5 text-center font-semibold">Stok</th>
                        <th class="px-4 py-3.5 font-semibold">Birim</th>
                        <th class="px-4 py-3.5 text-center font-semibold">Durum</th>
                        <th class="px-4 py-3.5 text-center font-semibold">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($products as $product)
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="px-4 py-3">
                                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center border border-gray-200">
                                    @if($product->image_url)
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-10 h-10 rounded-lg object-cover">
                                    @else
                                        <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs text-gray-400">{{ $product->barcode ?? '-' }}</span>
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $product->name }}</td>
                            <td class="px-4 py-3">
                                @if($product->category)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium bg-brand-50 text-brand-600 border border-brand-200">
                                        {{ $product->category->name }}
                                    </span>
                                @else
                                    <span class="text-gray-300">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono">
                                {{ $product->purchase_price ? formatCurrency($product->purchase_price) : '-' }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-medium text-gray-900">
                                {{ formatCurrency($product->sale_price) }}
                            </td>
                            <td class="px-4 py-3 text-center">%{{ $product->vat_rate ?? 0 }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="{{ ($product->stock_quantity !== null && $product->stock_quantity < 5) ? 'text-red-500 font-semibold' : '' }}">
                                    {{ $product->stock_quantity ?? '-' }}
                                </span>
                                @if($product->stock_quantity !== null && $product->stock_quantity < 5)
                                    <svg class="inline w-4 h-4 text-red-500 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $product->unit ?? 'Adet' }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($product->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-600 border border-emerald-200">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium bg-red-50 text-red-600 border border-red-200">
                                        Pasif
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="openEdit({{ json_encode([
                                                'id' => $product->id,
                                                'name' => $product->name,
                                                'barcode' => $product->barcode,
                                                'category_id' => $product->category_id,
                                                'purchase_price' => $product->purchase_price,
                                                'sale_price' => $product->sale_price,
                                                'vat_rate' => $product->vat_rate,
                                                'stock_quantity' => $product->stock_quantity,
                                                'unit' => $product->unit,
                                                'is_active' => $product->is_active,
                                            ]) }})"
                                            class="p-2 text-gray-400 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors"
                                            title="Düzenle">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button @click="confirmDelete({{ $product->id }}, '{{ addslashes($product->name) }}')"
                                            class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Sil">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <p class="text-gray-400 text-sm">Henüz ürün eklenmemiş</p>
                                    <button @click="openCreate()" class="text-brand-500 hover:text-brand-700 text-sm font-medium">
                                        + İlk ürünü ekle
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    {{-- Side Panel / Modal --}}
    <div x-show="showPanel"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex justify-end"
         style="display: none;">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closePanel()"></div>

        <div x-show="showPanel"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="relative w-full max-w-lg bg-white border-l border-gray-200 shadow-2xl overflow-y-auto">

            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
                <h2 class="text-lg font-semibold text-gray-900" x-text="editingId ? 'Ürün Düzenle' : 'Yeni Ürün'"></h2>
                <button @click="closePanel()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitForm()" class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Ürün Adı <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.name" required
                           class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400 transition-all"
                           placeholder="Ürün adını girin">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Barkod</label>
                    <input type="text" x-model="form.barcode"
                           class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400 transition-all"
                           placeholder="Barkod numarası">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Kategori</label>
                    <select x-model="form.category_id"
                            class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                        <option value="">Kategori seçin</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Alış Fiyatı</label>
                        <div class="relative">
                            <input type="number" x-model="form.purchase_price" step="0.01" min="0"
                                   class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl pl-4 pr-8 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400 transition-all"
                                   placeholder="0.00">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₺</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Satış Fiyatı <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="number" x-model="form.sale_price" step="0.01" min="0" required
                                   class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl pl-4 pr-8 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400 transition-all"
                                   placeholder="0.00">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₺</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">KDV Oranı</label>
                    <select x-model="form.vat_rate"
                            class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                        <option value="1">%1</option>
                        <option value="10">%10</option>
                        <option value="20">%20</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Stok Miktarı</label>
                        <input type="number" x-model="form.stock_quantity" min="0" step="1"
                               class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400 transition-all"
                               placeholder="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Birim</label>
                        <select x-model="form.unit"
                                class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                            <option value="Adet">Adet</option>
                            <option value="Kg">Kg</option>
                            <option value="Lt">Lt</option>
                            <option value="Porsiyon">Porsiyon</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between py-2">
                    <label class="text-sm font-medium text-gray-700">Aktif</label>
                    <button type="button"
                            @click="form.is_active = !form.is_active"
                            :class="form.is_active ? 'bg-brand-500' : 'bg-gray-300'"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                        <span :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"
                              class="inline-block h-4 w-4 rounded-full bg-white transition-transform shadow-sm"></span>
                    </button>
                </div>

                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <button type="button" @click="closePanel()"
                            class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 border border-gray-200 rounded-xl transition-colors">
                        İptal
                    </button>
                    <button type="submit"
                            :disabled="saving"
                            class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <svg x-show="saving" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="editingId ? 'Güncelle' : 'Kaydet'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Confirm Modal --}}
    <div x-show="showDeleteModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showDeleteModal = false"></div>
        <div x-show="showDeleteModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white rounded-2xl border border-gray-200 shadow-2xl w-full max-w-md p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Ürün Sil</h3>
                    <p class="text-sm text-gray-500">Bu işlem geri alınamaz.</p>
                </div>
            </div>
            <p class="text-gray-600 text-sm mb-6">
                <span class="font-medium text-gray-900" x-text="deleteName"></span> ürününü silmek istediğinize emin misiniz?
            </p>
            <div class="flex gap-3">
                <button @click="showDeleteModal = false"
                        class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 border border-gray-200 rounded-xl transition-colors">
                    Vazgeç
                </button>
                <button @click="deleteProduct()"
                        :disabled="deleting"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 rounded-xl transition-all disabled:opacity-50 flex items-center justify-center gap-2 shadow-sm hover:shadow-md">
                    <svg x-show="deleting" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Evet, Sil
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function productManager() {
    return {
        showPanel: false,
        showDeleteModal: false,
        editingId: null,
        deleteId: null,
        deleteName: '',
        saving: false,
        deleting: false,
        searchQuery: new URLSearchParams(window.location.search).get('search') || '',
        categoryFilter: new URLSearchParams(window.location.search).get('category_id') || '',
        form: {
            name: '',
            barcode: '',
            category_id: '',
            purchase_price: '',
            sale_price: '',
            vat_rate: '10',
            stock_quantity: '',
            unit: 'Adet',
            is_active: true,
        },

        resetForm() {
            this.form = {
                name: '',
                barcode: '',
                category_id: '',
                purchase_price: '',
                sale_price: '',
                vat_rate: '10',
                stock_quantity: '',
                unit: 'Adet',
                is_active: true,
            };
            this.editingId = null;
        },

        openCreate() {
            this.resetForm();
            this.showPanel = true;
        },

        openEdit(product) {
            this.editingId = product.id;
            this.form = {
                name: product.name || '',
                barcode: product.barcode || '',
                category_id: product.category_id ? String(product.category_id) : '',
                purchase_price: product.purchase_price ?? '',
                sale_price: product.sale_price ?? '',
                vat_rate: product.vat_rate != null ? String(product.vat_rate) : '10',
                stock_quantity: product.stock_quantity ?? '',
                unit: product.unit || 'Adet',
                is_active: product.is_active ? true : false,
            };
            this.showPanel = true;
        },

        closePanel() {
            this.showPanel = false;
            setTimeout(() => this.resetForm(), 300);
        },

        async submitForm() {
            if (!this.form.name || !this.form.sale_price) {
                showToast('Ürün adı ve satış fiyatı zorunludur.', 'error');
                return;
            }
            this.saving = true;

            const url = this.editingId
                ? '{{ route("pos.products.update", ":id") }}'.replace(':id', this.editingId)
                : '{{ route("pos.products.store") }}';

            const method = this.editingId ? 'PUT' : 'POST';

            try {
                const response = await posAjax(url, {
                    method: method,
                    body: JSON.stringify({
                        ...this.form,
                        is_active: this.form.is_active ? 1 : 0,
                    }),
                });
                showToast(response.message || (this.editingId ? 'Ürün güncellendi.' : 'Ürün oluşturuldu.'), 'success');
                this.closePanel();
                window.location.reload();
            } catch (error) {
                showToast(error.message || 'Bir hata oluştu.', 'error');
            } finally {
                this.saving = false;
            }
        },

        confirmDelete(id, name) {
            this.deleteId = id;
            this.deleteName = name;
            this.showDeleteModal = true;
        },

        async deleteProduct() {
            this.deleting = true;
            const url = '{{ route("pos.products.destroy", ":id") }}'.replace(':id', this.deleteId);

            try {
                const response = await posAjax(url, { method: 'DELETE' });
                showToast(response.message || 'Ürün silindi.', 'success');
                this.showDeleteModal = false;
                window.location.reload();
            } catch (error) {
                showToast(error.message || 'Silme işlemi başarısız.', 'error');
            } finally {
                this.deleting = false;
            }
        },

        applyFilters() {
            const params = new URLSearchParams();
            if (this.searchQuery) params.set('search', this.searchQuery);
            if (this.categoryFilter) params.set('category_id', this.categoryFilter);
            window.location.href = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        },
    };
}
</script>
@endpush

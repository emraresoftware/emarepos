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
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->parent_id ? '└ ' : '' }}{{ $cat->name }}</option>
                    @foreach($cat->children->sortBy('name') as $sub)
                        <option value="{{ $sub->id }}">&nbsp;&nbsp;└ {{ $sub->name }}</option>
                        @foreach($sub->children->sortBy('name') as $sub2)
                            <option value="{{ $sub2->id }}">&nbsp;&nbsp;&nbsp;&nbsp;└ {{ $sub2->name }}</option>
                        @endforeach
                    @endforeach
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
                                                'critical_stock' => $product->critical_stock,
                                                'unit' => $product->unit,
                                                'country_of_origin' => $product->country_of_origin,
                                                'is_active' => $product->is_active,
                                            ]) }})"  
                                            class="p-2 text-gray-400 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors"
                                            title="Düzenle">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button @click="openHistory({{ $product->id }}, '{{ addslashes($product->name) }}')"
                                            class="p-2 text-gray-400 hover:text-purple-500 hover:bg-purple-50 rounded-lg transition-colors"
                                            title="İşlem Geçmişi">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                    <button @click="openPrices({{ $product->id }}, '{{ addslashes($product->name) }}')"
                                            class="p-2 text-gray-400 hover:text-amber-500 hover:bg-amber-50 rounded-lg transition-colors"
                                            title="Çoklu Fiyat">
                                        <i class="fas fa-tags w-4 h-4 text-xs flex items-center justify-center"></i>
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
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @foreach($cat->children->sortBy('name') as $sub)
                                <option value="{{ $sub->id }}">&nbsp;&nbsp;└ {{ $sub->name }}</option>
                                @foreach($sub->children->sortBy('name') as $sub2)
                                    <option value="{{ $sub2->id }}">&nbsp;&nbsp;&nbsp;&nbsp;└ {{ $sub2->name }}</option>
                                @endforeach
                            @endforeach
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Alış Fiyatı</label>
                        <div class="relative">
                            <input type="number" x-model="form.purchase_price" @input="calcProfit()" step="0.01" min="0"
                                   class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl pl-4 pr-8 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400 transition-all"
                                   placeholder="0.00">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₺</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Satış Fiyatı <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="number" x-model="form.sale_price" @input="calcProfit()" step="0.01" min="0" required
                                   class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl pl-4 pr-8 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400 transition-all"
                                   placeholder="0.00">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₺</span>
                        </div>
                    </div>
                </div>
                <div x-show="profitRate !== null" class="flex items-center gap-2 -mt-2 px-1">
                    <span class="text-xs text-gray-500">Kâr Oranı:</span>
                    <span class="text-xs font-semibold" :class="profitRate >= 0 ? 'text-emerald-600' : 'text-red-500'" x-text="profitRate !== null ? '%' + profitRate : ''"></span>
                    <span class="text-xs text-gray-400" x-show="profitAmount !== null" x-text="profitAmount !== null ? '(' + profitAmount + ' ₺ kâr)' : ''"></span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">KDV Oranı</label>
                    <select x-model="form.vat_rate"
                            class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                        <option value="0">%0</option>
                        <option value="1">%1</option>
                        <option value="10">%10</option>
                        <option value="20">%20</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Stok Miktarı</label>
                        <input type="number" x-model="form.stock_quantity" min="0" step="0.01"
                               class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400 transition-all"
                               placeholder="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Kritik Stok Uyarısı</label>
                        <input type="number" x-model="form.critical_stock" min="0" step="0.01"
                               class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400 transition-all"
                               placeholder="0">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Birim</label>
                        <select x-model="form.unit"
                                class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                            <option>Adet</option><option>Bardak</option><option>CL</option>
                            <option>Dilim</option><option>Fincan</option><option>Gram</option>
                            <option>Kaşık</option><option>Kavanoz</option><option>Kilo</option>
                            <option>Koli</option><option>Külah</option><option>Kutu</option>
                            <option>Litre</option><option>Miligram</option><option>Mililitre</option>
                            <option>ML</option><option>Paket</option><option>Porsiyon</option>
                            <option>Poşet</option><option>Şişe</option><option>Tabak</option>
                            <option>Tencere</option><option>Top</option><option>Tutam</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Menşe Ülke</label>
                        <input type="text" x-model="form.country_of_origin"
                               class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400 transition-all"
                               placeholder="örn. Türkiye, Almanya...">
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

                {{-- Şube Bilgileri --}}
                <div x-show="editingId" class="border-t border-gray-200 pt-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-900"><i class="fas fa-store text-brand-500 mr-1.5"></i>Şube Bilgileri</h3>
                        <button type="button" @click="showBranchSection = !showBranchSection" class="text-xs text-brand-500 hover:text-brand-700">
                            <span x-text="showBranchSection ? 'Gizle' : 'Göster'"></span>
                            <i class="fas ml-1" :class="showBranchSection ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        </button>
                    </div>
                    <div x-show="showBranchSection" x-transition class="space-y-2">
                        <div x-show="branchDataLoading" class="text-center py-4"><i class="fas fa-spinner fa-spin text-brand-500"></i></div>
                        <template x-for="(b, bi) in branchData" :key="b.branch_id">
                            <div class="bg-gray-50 rounded-xl border border-gray-100 p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" :checked="b.enabled" @change="b.enabled = $event.target.checked" class="w-4 h-4 text-brand-500 rounded border-gray-300">
                                        <span class="text-sm font-medium text-gray-900" x-text="b.branch_name"></span>
                                    </div>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full" :class="b.enabled ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-200 text-gray-500'" x-text="b.enabled ? 'Aktif' : 'Kapalı'"></span>
                                </div>
                                <div x-show="b.enabled" class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] text-gray-500 mb-0.5">Satış Fiyatı (₺)</label>
                                        <input type="number" x-model="b.sale_price" step="0.01" min="0"
                                               class="w-full text-sm px-2.5 py-1.5 border border-gray-200 rounded-lg bg-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500/20">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-gray-500 mb-0.5">Stok Miktarı</label>
                                        <input type="number" x-model="b.stock_quantity" step="0.01" min="0"
                                               class="w-full text-sm px-2.5 py-1.5 border border-gray-200 rounded-lg bg-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500/20">
                                    </div>
                                </div>
                            </div>
                        </template>
                        <p x-show="branchData.length === 0 && !branchDataLoading" class="text-xs text-gray-400 text-center py-3">Şube bulunamadı</p>
                        {{-- Fiyat Karşılaştırma --}}
                        <div x-show="branchData.filter(b => b.enabled).length > 1" class="bg-blue-50 rounded-xl border border-blue-100 p-3 mt-2">
                            <h4 class="text-xs font-semibold text-blue-700 mb-2"><i class="fas fa-chart-bar mr-1"></i>Fiyat Karşılaştırma</h4>
                            <template x-for="b in branchData.filter(b => b.enabled)" :key="'cmp-'+b.branch_id">
                                <div class="flex items-center justify-between text-xs py-1">
                                    <span class="text-gray-700" x-text="b.branch_name"></span>
                                    <span class="font-mono font-medium" :class="Number(b.sale_price) === Math.min(...branchData.filter(x=>x.enabled).map(x=>Number(x.sale_price||0))) ? 'text-emerald-600' : 'text-gray-900'" x-text="parseFloat(b.sale_price || 0).toFixed(2) + ' ₺'"></span>
                                </div>
                            </template>
                        </div>
                    </div>
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

    {{-- History Modal --}}
    <div x-show="showHistoryModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display:none">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showHistoryModal=false"></div>
        <div x-show="showHistoryModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[80vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">İşlem Geçmişi</h3>
                    <p class="text-sm text-gray-500" x-text="historyProductName"></p>
                </div>
                <button @click="showHistoryModal=false" class="text-gray-400 hover:text-gray-600 p-1"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 p-4">
                <div x-show="historyLoading" class="flex items-center justify-center py-12">
                    <i class="fas fa-spinner fa-spin text-2xl text-brand-500"></i>
                </div>
                <div x-show="!historyLoading && historyList.length === 0" class="text-center py-12 text-gray-400">
                    <i class="fas fa-history text-4xl mb-3 block"></i>
                    <p>Henüz hareket kaydı yok</p>
                </div>
                <table x-show="!historyLoading && historyList.length > 0" class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-3 py-2 text-left">Tarih</th>
                            <th class="px-3 py-2 text-left">Tür</th>
                            <th class="px-3 py-2 text-left">Firm/Müşteri</th>
                            <th class="px-3 py-2 text-left">Not</th>
                            <th class="px-3 py-2 text-right">Miktar</th>
                            <th class="px-3 py-2 text-right">Kalan</th>
                            <th class="px-3 py-2 text-right">Birim Fiyat</th>
                            <th class="px-3 py-2 text-right">Toplam</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="m in historyList" :key="m.movement_date + m.quantity">
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 text-gray-500 whitespace-nowrap" x-text="m.movement_date ? m.movement_date.slice(0,16).replace('T',' ') : '-'"></td>
                                <td class="px-3 py-2">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                          :class="m.type === 'in' ? 'bg-emerald-100 text-emerald-700' : m.type === 'out' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'"
                                          x-text="m.type === 'in' ? 'Giriş' : m.type === 'out' ? 'Çıkış' : (m.type || '-')"></span>
                                </td>
                                <td class="px-3 py-2 text-gray-600" x-text="m.firm_customer || '-'"></td>
                                <td class="px-3 py-2 text-gray-500 max-w-[160px] truncate" x-text="m.note || '-'"></td>
                                <td class="px-3 py-2 text-right font-mono" x-text="m.quantity"></td>
                                <td class="px-3 py-2 text-right font-mono text-gray-500" x-text="m.remaining ?? '-'"></td>
                                <td class="px-3 py-2 text-right font-mono" x-text="m.unit_price ? parseFloat(m.unit_price).toFixed(2) + ' ₺' : '-'"></td>
                                <td class="px-3 py-2 text-right font-mono font-medium" x-text="m.total ? parseFloat(m.total).toFixed(2) + ' ₺' : '-'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
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

    {{-- Çoklu Fiyat Modalı --}}
    <div x-show="showPricesModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display:none">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showPricesModal=false"></div>
        <div x-show="showPricesModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                <div>
                    <h3 class="text-lg font-bold text-gray-900"><i class="fas fa-tags text-amber-500 mr-2"></i>Çoklu Fiyat</h3>
                    <p class="text-sm text-gray-500" x-text="pricesProductName"></p>
                </div>
                <button @click="showPricesModal=false" class="text-gray-400 hover:text-gray-600 p-1"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 p-5">
                {{-- Mevcut Fiyatlar --}}
                <div class="space-y-2 mb-4">
                    <template x-for="(p, idx) in pricesList" :key="p.id">
                        <div class="flex items-center gap-3 bg-gray-50 rounded-xl px-4 py-2.5 border border-gray-100">
                            <template x-if="editingPriceId !== p.id">
                                <div class="flex items-center gap-3 w-full">
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-gray-900" x-text="p.label"></span>
                                    </div>
                                    <span class="text-sm font-bold text-blue-600" x-text="parseFloat(p.price).toFixed(2) + ' ₺'"></span>
                                    <button @click="editingPriceId = p.id; editPriceLabel = p.label; editPriceValue = p.price"
                                            class="p-1.5 text-gray-400 hover:text-brand-600 rounded-lg"><i class="fas fa-pen text-xs"></i></button>
                                    <button @click="deletePrice(p.id, idx)"
                                            class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg"><i class="fas fa-trash text-xs"></i></button>
                                </div>
                            </template>
                            <template x-if="editingPriceId === p.id">
                                <div class="flex items-center gap-2 w-full">
                                    <input type="text" x-model="editPriceLabel" class="flex-1 text-sm px-2 py-1.5 border border-gray-200 rounded-lg focus:border-brand-500 focus:ring-1 focus:ring-brand-500/20">
                                    <input type="number" x-model="editPriceValue" step="0.01" min="0" class="w-24 text-sm px-2 py-1.5 border border-gray-200 rounded-lg focus:border-brand-500 focus:ring-1 focus:ring-brand-500/20 text-right">
                                    <button @click="updatePrice(p.id, idx)" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg"><i class="fas fa-check text-xs"></i></button>
                                    <button @click="editingPriceId = null" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg"><i class="fas fa-times text-xs"></i></button>
                                </div>
                            </template>
                        </div>
                    </template>
                    <div x-show="pricesList.length === 0 && !pricesLoading" class="text-center py-6 text-gray-400 text-sm">
                        <i class="fas fa-tags text-2xl mb-2 block"></i>
                        <p>Henüz alternatif fiyat eklenmemiş</p>
                    </div>
                    <div x-show="pricesLoading" class="flex items-center justify-center py-6">
                        <i class="fas fa-spinner fa-spin text-xl text-brand-500"></i>
                    </div>
                </div>
                {{-- Yeni Fiyat Ekle --}}
                <div class="border-t border-gray-200 pt-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Yeni Fiyat Ekle</h4>
                    <div class="flex items-end gap-3">
                        <div class="flex-1">
                            <label class="block text-xs text-gray-500 mb-1">Fiyat Adı</label>
                            <input type="text" x-model="newPriceLabel" placeholder="Kredi Kartı, Nakit, Toptan..."
                                   class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 placeholder-gray-400">
                        </div>
                        <div class="w-28">
                            <label class="block text-xs text-gray-500 mb-1">Fiyat (₺)</label>
                            <input type="number" x-model="newPriceValue" step="0.01" min="0" placeholder="0.00"
                                   class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-right">
                        </div>
                        <button @click="addPrice()" :disabled="!newPriceLabel.trim() || !newPriceValue"
                                class="px-4 py-2 bg-gradient-to-r from-brand-500 to-purple-600 text-white text-sm font-medium rounded-xl hover:shadow-lg transition-all disabled:opacity-50 whitespace-nowrap">
                            <i class="fas fa-plus mr-1"></i>Ekle
                        </button>
                    </div>
                    {{-- Hızlı Önayarlar --}}
                    <div class="flex flex-wrap gap-1.5 mt-3">
                        <template x-for="preset in ['Kredi Kartı', 'Nakit', 'Toptan', 'Perakende', 'Online', 'Personel']" :key="preset">
                            <button @click="newPriceLabel = preset"
                                    class="px-2.5 py-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg transition-colors"
                                    x-text="preset"></button>
                        </template>
                    </div>
                </div>
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
        showHistoryModal: false,
        historyLoading: false,
        historyList: [],
        historyProductName: '',
        profitRate: null,
        profitAmount: null,
        editingId: null,
        deleteId: null,
        deleteName: '',
        saving: false,
        deleting: false,
        searchQuery: new URLSearchParams(window.location.search).get('search') || '',
        categoryFilter: new URLSearchParams(window.location.search).get('category_id') || '',
        // Çoklu Fiyat
        showPricesModal: false,
        pricesProductId: null,
        pricesProductName: '',
        pricesList: [],
        pricesLoading: false,
        newPriceLabel: '',
        newPriceValue: '',
        editingPriceId: null,
        editPriceLabel: '',
        editPriceValue: '',
        // Şube bilgileri
        showBranchSection: true,
        branchData: [],
        branchDataLoading: false,
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
                critical_stock: '',
                unit: 'Adet',
                country_of_origin: '',
                is_active: true,
            };
            this.editingId = null;
            this.profitRate = null;
            this.profitAmount = null;
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
                critical_stock: product.critical_stock ?? '',
                unit: product.unit || 'Adet',
                country_of_origin: product.country_of_origin || '',
                is_active: product.is_active ? true : false,
            };
            this.calcProfit();
            this.showPanel = true;
            this.loadBranches(product.id);
        },

        async loadBranches(productId) {
            this.branchDataLoading = true;
            this.branchData = [];
            try {
                const res = await posAjax('/products/' + productId + '/branches', {}, 'GET');
                this.branchData = res.branches || [];
            } catch {
                this.branchData = [];
            } finally {
                this.branchDataLoading = false;
            }
        },

        async saveBranches() {
            if (!this.editingId || this.branchData.length === 0) return;
            try {
                await posAjax('/products/' + this.editingId + '/branches', {
                    branches: this.branchData.map(b => ({
                        branch_id: b.branch_id,
                        enabled: b.enabled ? true : false,
                        sale_price: Number(b.sale_price) || 0,
                        stock_quantity: Number(b.stock_quantity) || 0,
                    }))
                }, 'POST');
            } catch(e) {
                showToast('Şube bilgileri kaydedilemedi', 'error');
            }
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
                // Şube bilgilerini de kaydet
                if (this.editingId && this.branchData.length > 0) {
                    await this.saveBranches();
                }
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

        calcProfit() {
            const buy = parseFloat(this.form.purchase_price);
            const sell = parseFloat(this.form.sale_price);
            if (buy > 0 && sell > 0) {
                const profit = sell - buy;
                this.profitRate = ((profit / buy) * 100).toFixed(2);
                this.profitAmount = profit.toFixed(2);
            } else {
                this.profitRate = null;
                this.profitAmount = null;
            }
        },

        async openHistory(productId, productName) {
            this.historyProductName = productName;
            this.historyList = [];
            this.historyLoading = true;
            this.showHistoryModal = true;
            try {
                const url = '{{ route("pos.products.history", ":id") }}'.replace(':id', productId);
                const res = await posAjax(url, {}, 'GET');
                this.historyList = res.movements || [];
            } catch {
                showToast('Geçmiş yüklenemedi', 'error');
                this.showHistoryModal = false;
            } finally {
                this.historyLoading = false;
            }
        },

        // ---- Çoklu Fiyat ----
        async openPrices(productId, productName) {
            this.pricesProductId = productId;
            this.pricesProductName = productName;
            this.pricesList = [];
            this.pricesLoading = true;
            this.showPricesModal = true;
            this.newPriceLabel = '';
            this.newPriceValue = '';
            this.editingPriceId = null;
            try {
                const url = '/products/' + productId + '/prices';
                const res = await posAjax(url, {}, 'GET');
                this.pricesList = res.prices || [];
            } catch {
                showToast('Fiyatlar yüklenemedi', 'error');
                this.showPricesModal = false;
            } finally {
                this.pricesLoading = false;
            }
        },

        async addPrice() {
            if (!this.newPriceLabel.trim() || !this.newPriceValue) return;
            try {
                const url = '/products/' + this.pricesProductId + '/prices';
                const res = await posAjax(url, {
                    method: 'POST',
                    body: JSON.stringify({ label: this.newPriceLabel, price: this.newPriceValue }),
                });
                if (res.success) {
                    this.pricesList.push(res.price);
                    this.newPriceLabel = '';
                    this.newPriceValue = '';
                    showToast('Fiyat eklendi', 'success');
                }
            } catch(e) {
                showToast(e.message || 'Fiyat eklenemedi', 'error');
            }
        },

        async updatePrice(priceId, idx) {
            try {
                const url = '/products/' + this.pricesProductId + '/prices/' + priceId;
                const res = await posAjax(url, {
                    method: 'PUT',
                    body: JSON.stringify({ label: this.editPriceLabel, price: this.editPriceValue }),
                });
                if (res.success) {
                    this.pricesList[idx] = res.price;
                    this.editingPriceId = null;
                    showToast('Fiyat güncellendi', 'success');
                }
            } catch(e) {
                showToast(e.message || 'Güncellenemedi', 'error');
            }
        },

        async deletePrice(priceId, idx) {
            if (!confirm('Bu fiyatı silmek istediğinize emin misiniz?')) return;
            try {
                const url = '/products/' + this.pricesProductId + '/prices/' + priceId;
                const res = await posAjax(url, { method: 'DELETE' });
                if (res.success) {
                    this.pricesList.splice(idx, 1);
                    showToast('Fiyat silindi', 'success');
                }
            } catch(e) {
                showToast(e.message || 'Silinemedi', 'error');
            }
        },
    };
}
</script>
@endpush

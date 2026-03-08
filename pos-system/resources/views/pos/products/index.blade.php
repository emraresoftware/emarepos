@extends('pos.layouts.app')

@section('title', 'Ürünler')

@section('content')
<div x-data="productManager()" x-cloak>
    {{-- Top Bar --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3">
        <h1 class="text-2xl font-bold text-gray-900">Ürünler</h1>
        <div class="flex items-center gap-2 flex-wrap">
            <button @click="showVariantTypeModal = true" class="px-3 py-2 text-xs font-medium bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl hover:bg-emerald-100 flex items-center gap-1.5 transition-colors">
                <i class="fas fa-layer-group text-[10px]"></i> Varyantlı Ürünler
            </button>
            {{-- Filtreleme seçeneği --}}
            <div class="relative" x-data="{ openFilter: false }">
                <button @click="openFilter = !openFilter" class="px-3 py-2 text-xs font-medium bg-green-50 border border-green-200 text-green-700 rounded-xl hover:bg-green-100 flex items-center gap-1.5 transition-colors">
                    <i class="fas fa-filter text-[10px]"></i> Filtreleme seçeneği
                    <i class="fas fa-caret-down text-[10px]"></i>
                </button>
                <div x-show="openFilter" @click.away="openFilter=false" x-transition class="absolute right-0 top-full mt-1 bg-white rounded-xl border border-gray-200 shadow-xl z-30 p-3 w-60 space-y-2">
                    <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                        <input type="checkbox" x-model="filters.low_stock" @change="applyFilters()" class="rounded text-brand-500 border-gray-300 w-3.5 h-3.5"> Kritik Stok Altı
                    </label>
                    <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                        <input type="checkbox" x-model="filters.is_service" @change="applyFilters()" class="rounded text-brand-500 border-gray-300 w-3.5 h-3.5"> Sadece Hizmetler
                    </label>
                    <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                        <input type="checkbox" x-model="filters.has_variant" @change="applyFilters()" class="rounded text-brand-500 border-gray-300 w-3.5 h-3.5"> Varyantlı Ürünler
                    </label>
                    <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                        <input type="checkbox" x-model="filters.show_on_pos_only" @change="applyFilters()" class="rounded text-brand-500 border-gray-300 w-3.5 h-3.5"> POS'ta Gösterilenler
                    </label>
                    <div class="pt-1 border-t border-gray-100">
                        <label class="block text-[10px] text-gray-500 mb-1">Stok Durumu</label>
                        <select x-model="filters.stock_status" @change="applyFilters()" class="w-full text-xs px-2 py-1.5 border border-gray-200 rounded-lg">
                            <option value="">Hepsi</option>
                            <option value="zero">Stok = 0</option>
                            <option value="positive">Stok > 0</option>
                        </select>
                    </div>
                </div>
            </div>
            {{-- Sıralama Ölçütü --}}
            <div class="relative" x-data="{ openSort: false }">
                <button @click="openSort = !openSort" class="px-3 py-2 text-xs font-medium bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl hover:bg-yellow-100 flex items-center gap-1.5 transition-colors">
                    <i class="fas fa-sort text-[10px]"></i> Sıralama ölçütü
                    <i class="fas fa-caret-down text-[10px]"></i>
                </button>
                <div x-show="openSort" @click.away="openSort=false" x-transition class="absolute right-0 top-full mt-1 bg-white rounded-xl border border-gray-200 shadow-xl z-30 w-48 overflow-hidden">
                    <template x-for="opt in [{v:'name',l:'Ada Göre'},{v:'sale_price',l:'Fiyata Göre'},{v:'stock_quantity',l:'Stoka Göre'},{v:'created_at',l:'Eklenme Tarihi'},{v:'sort_order',l:'Sıraya Göre'},{v:'barcode',l:'Barkoda Göre'}]" :key="opt.v">
                        <button @click="sortBy = opt.v; applyFilters(); openSort = false"
                                class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 transition-colors flex items-center justify-between"
                                :class="sortBy === opt.v ? 'text-brand-600 font-semibold bg-brand-50' : 'text-gray-700'">
                            <span x-text="opt.l"></span>
                            <i x-show="sortBy === opt.v" class="fas fa-check text-brand-500 text-[10px]"></i>
                        </button>
                    </template>
                </div>
            </div>
            {{-- Ürün Özet Dökümü --}}
            <button @click="openSummaryModal()" class="px-3 py-2 text-xs font-medium bg-red-50 border border-red-200 text-red-700 rounded-xl hover:bg-red-100 flex items-center gap-1.5 transition-colors">
                <i class="fas fa-chart-pie text-[10px]"></i> Ürün özet dökümü
            </button>
            {{-- Excel Aktar --}}
            <div class="relative" x-data="{ openExcel: false }">
                <button @click="openExcel = !openExcel" class="px-3 py-2 text-xs font-medium bg-blue-50 border border-blue-200 text-blue-700 rounded-xl hover:bg-blue-100 flex items-center gap-1.5 transition-colors">
                    <i class="fas fa-file-excel text-[10px]"></i> Excel Aktar
                    <i class="fas fa-caret-down text-[10px]"></i>
                </button>
                <div x-show="openExcel" @click.away="openExcel=false" x-transition class="absolute right-0 top-full mt-1 bg-white rounded-xl border border-gray-200 shadow-xl z-30 w-44 overflow-hidden">
                    <a href="/products-export" class="flex items-center gap-2 px-3 py-2.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-file-export text-emerald-500"></i> Dışa Aktar (CSV)
                    </a>
                    <button @click="showImportModal = true; openExcel = false" class="w-full flex items-center gap-2 px-3 py-2.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-file-import text-blue-500"></i> İçe Aktar (CSV)
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Detaylı Arama Çubuğu (BenimPOS Tarzı) --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-3 mb-3">
        <div class="flex flex-wrap items-center gap-2">
            {{-- Metin Arama --}}
            <div class="flex items-center gap-1 flex-1 min-w-0 sm:min-w-[200px]">
                <input type="text"
                       x-model="searchQuery"
                       @input.debounce.400ms="applyFilters()"
                       placeholder="Ürün adı, barkod, stok kodu, varyant kod"
                       class="bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-3 py-2 w-full focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400 transition-all">
                <button @click="applyFilters()" class="px-2.5 py-2 bg-gray-50 border border-gray-200 rounded-xl hover:bg-gray-100 transition-colors text-gray-500" title="Ara">
                    <i class="fas fa-search text-xs"></i> <span class="text-xs font-medium">Ara</span>
                </button>
            </div>

            {{-- Firma Filtresi --}}
            <div class="flex items-center gap-1">
                <select x-model="firmFilter" @change="applyFilters()"
                        class="bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-3 py-2 w-full sm:min-w-[160px] focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                    <option value="">Tüm firmalar</option>
                    @foreach($firms as $firm)
                        <option value="{{ $firm->id }}">{{ $firm->name }}</option>
                    @endforeach
                </select>
                <button @click="firmFilter = ''; applyFilters()" x-show="firmFilter" class="px-2 py-2 bg-gray-50 border border-gray-200 rounded-xl hover:bg-gray-100 transition-colors text-gray-400" title="Temizle">
                    <i class="fas fa-search text-xs"></i>
                </button>
            </div>

            {{-- Kategori (Grup) Filtresi --}}
            <div class="flex items-center gap-1">
                <select x-model="categoryFilter" @change="applyFilters()"
                        class="bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-3 py-2 w-full sm:min-w-[160px] focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                    <option value="">Tüm gruplar</option>
                    @php
                        if (!function_exists('renderCatOptions')) {
                            function renderCatOptions($cats, $depth = 0) {
                                foreach ($cats as $cat) {
                                    $prefix = str_repeat('&nbsp;&nbsp;', $depth) . ($depth > 0 ? '└ ' : '');
                                    echo '<option value="'.$cat->id.'">'.$prefix.e($cat->name).'</option>';
                                    if ($cat->relationLoaded('children') && $cat->children->count()) {
                                        renderCatOptions($cat->children->sortBy('name'), $depth + 1);
                                    }
                                }
                            }
                        }
                        renderCatOptions($categories);
                    @endphp
                </select>
                <button @click="categoryFilter = ''; applyFilters()" x-show="categoryFilter" class="px-2 py-2 bg-gray-50 border border-gray-200 rounded-xl hover:bg-gray-100 transition-colors text-gray-400" title="Temizle">
                    <i class="fas fa-search text-xs"></i>
                </button>
            </div>

            {{-- Varyant Filtresi --}}
            <div class="flex items-center gap-1">
                <select x-model="variantFilter" @change="applyFilters()"
                        class="bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-3 py-2 w-full sm:min-w-[160px] focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                    <option value="">Tüm varyantlar</option>
                    @foreach($variantTypes as $vt)
                        <option value="{{ $vt->id }}">{{ $vt->name }}</option>
                    @endforeach
                </select>
                <button @click="variantFilter = ''; applyFilters()" x-show="variantFilter" class="px-2 py-2 bg-gray-50 border border-gray-200 rounded-xl hover:bg-gray-100 transition-colors text-gray-400" title="Temizle">
                    <i class="fas fa-search text-xs"></i>
                </button>
            </div>

            {{-- Şablon --}}
            <div class="flex items-center gap-1">
                <select x-model="selectedTemplateId" @change="loadTemplate()"
                        class="bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-3 py-2 w-full sm:min-w-[130px] focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                    <option value="">Şablon</option>
                    @foreach($filterTemplates as $tpl)
                        <option value="{{ $tpl->id }}" data-filters="{{ json_encode($tpl->filters) }}">{{ $tpl->name }}</option>
                    @endforeach
                </select>
                {{-- Şablon Kaydet/Sil butonları --}}
                <button @click="showSaveTemplateModal = true" class="px-2 py-2 bg-blue-50 border border-blue-200 rounded-xl hover:bg-blue-100 transition-colors text-blue-500" title="Mevcut filtreleri şablon olarak kaydet">
                    <i class="fas fa-save text-xs"></i>
                </button>
                <button x-show="selectedTemplateId" @click="deleteTemplate()" class="px-2 py-2 bg-red-50 border border-red-200 rounded-xl hover:bg-red-100 transition-colors text-red-400" title="Şablonu sil">
                    <i class="fas fa-trash text-xs"></i>
                </button>
            </div>

            {{-- Yeni Ürün --}}
            <button @click="openCreate()"
                    class="bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 text-white font-semibold rounded-xl text-sm px-4 py-2 transition-all flex items-center gap-1.5 whitespace-nowrap ml-auto">
                <i class="fas fa-plus text-xs"></i> Yeni Ürün
            </button>
        </div>
        
        {{-- Aktif Filtre Etiketleri --}}
        <div x-show="hasActiveFilters()" class="flex flex-wrap items-center gap-1.5 mt-2 pt-2 border-t border-gray-100">
            <span class="text-[10px] text-gray-400 font-medium">AKTİF FİLTRELER:</span>
            <template x-if="searchQuery">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 rounded-full text-[10px] text-gray-600">
                    <i class="fas fa-search text-[8px]"></i> <span x-text="'\"' + searchQuery + '\"'"></span>
                    <button @click="searchQuery=''; applyFilters()" class="text-gray-400 hover:text-red-500 ml-0.5"><i class="fas fa-times text-[8px]"></i></button>
                </span>
            </template>
            <template x-if="firmFilter">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-orange-50 rounded-full text-[10px] text-orange-600">
                    <i class="fas fa-building text-[8px]"></i> Firma
                    <button @click="firmFilter=''; applyFilters()" class="text-orange-400 hover:text-red-500 ml-0.5"><i class="fas fa-times text-[8px]"></i></button>
                </span>
            </template>
            <template x-if="categoryFilter">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 rounded-full text-[10px] text-blue-600">
                    <i class="fas fa-folder text-[8px]"></i> Grup
                    <button @click="categoryFilter=''; applyFilters()" class="text-blue-400 hover:text-red-500 ml-0.5"><i class="fas fa-times text-[8px]"></i></button>
                </span>
            </template>
            <template x-if="variantFilter">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-50 rounded-full text-[10px] text-purple-600">
                    <i class="fas fa-layer-group text-[8px]"></i> Varyant
                    <button @click="variantFilter=''; applyFilters()" class="text-purple-400 hover:text-red-500 ml-0.5"><i class="fas fa-times text-[8px]"></i></button>
                </span>
            </template>
            <template x-if="filters.low_stock">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-50 rounded-full text-[10px] text-red-600">
                    <i class="fas fa-exclamation-triangle text-[8px]"></i> Kritik Stok
                    <button @click="filters.low_stock=false; applyFilters()" class="text-red-400 hover:text-red-600 ml-0.5"><i class="fas fa-times text-[8px]"></i></button>
                </span>
            </template>
            <button @click="clearAllFilters()" class="text-[10px] text-red-500 hover:text-red-700 font-medium ml-1">
                <i class="fas fa-times-circle"></i> Tümünü Temizle
            </button>
        </div>
    </div>

    {{-- Toplu İşlemler Toolbar --}}
    <div class="flex flex-wrap items-center gap-2 mb-3" x-show="selectedIds.length > 0" x-transition>
        <span class="text-xs text-gray-500 font-medium" x-text="selectedIds.length + ' seçili'"></span>
        <button @click="bulkDeleteProducts()" class="px-3 py-2 text-xs font-medium bg-red-50 border border-red-200 text-red-600 rounded-xl hover:bg-red-100 flex items-center gap-1.5">
            <i class="fas fa-trash-alt text-[10px]"></i> Toplu Sil
        </button>
        <button @click="showBulkCategoryModal = true" class="px-3 py-2 text-xs font-medium bg-blue-50 border border-blue-200 text-blue-600 rounded-xl hover:bg-blue-100 flex items-center gap-1.5">
            <i class="fas fa-folder text-[10px]"></i> Kategori Ata
        </button>
        <button @click="showBulkPriceModal = true" class="px-3 py-2 text-xs font-medium bg-amber-50 border border-amber-200 text-amber-600 rounded-xl hover:bg-amber-100 flex items-center gap-1.5">
            <i class="fas fa-dollar-sign text-[10px]"></i> Fiyat Güncelle
        </button>
        <button @click="openLabelModal()" class="px-3 py-2 text-xs font-medium bg-purple-50 border border-purple-200 text-purple-600 rounded-xl hover:bg-purple-100 flex items-center gap-1.5">
            <i class="fas fa-barcode text-[10px]"></i> Etiket Üret
        </button>
    </div>

    {{-- Product Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-lg shadow-gray-100/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-3 py-3.5 w-10">
                            <input type="checkbox" @change="toggleSelectAll($event)" class="rounded text-brand-500 border-gray-300 w-4 h-4">
                        </th>
                        <th class="px-3 py-3.5 font-semibold hidden md:table-cell">Görsel</th>
                        <th class="px-3 py-3.5 font-semibold hidden lg:table-cell">Barkod</th>
                        <th class="px-3 py-3.5 font-semibold hidden lg:table-cell">Stok Kodu</th>
                        <th class="px-3 py-3.5 font-semibold">Ürün Adı</th>
                        <th class="px-3 py-3.5 font-semibold hidden md:table-cell">Kategori</th>
                        <th class="px-3 py-3.5 font-semibold hidden lg:table-cell">Firma</th>
                        <th class="px-3 py-3.5 text-right font-semibold hidden md:table-cell">Alış</th>
                        <th class="px-3 py-3.5 text-right font-semibold">Satış</th>
                        <th class="px-3 py-3.5 text-center font-semibold hidden lg:table-cell">KDV%</th>
                        <th class="px-3 py-3.5 text-center font-semibold">Stok</th>
                        <th class="px-3 py-3.5 font-semibold hidden lg:table-cell">Birim</th>
                        <th class="px-3 py-3.5 text-center font-semibold hidden md:table-cell">POS</th>
                        <th class="px-3 py-3.5 text-center font-semibold">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($products as $product)
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="px-3 py-3">
                                <input type="checkbox" value="{{ $product->id }}" x-model="selectedIds" class="rounded text-brand-500 border-gray-300 w-4 h-4">
                            </td>
                            <td class="px-3 py-3 hidden md:table-cell">
                                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center border border-gray-200 overflow-hidden">
                                    @if($product->image_url)
                                        <img src="{{ asset('storage/' . $product->image_url) }}" alt="{{ $product->name }}" class="w-10 h-10 object-cover">
                                    @else
                                        <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-3 hidden lg:table-cell">
                                <span class="font-mono text-xs text-gray-400">{{ $product->barcode ?? '-' }}</span>
                            </td>
                            <td class="px-3 py-3 hidden lg:table-cell">
                                <span class="font-mono text-xs text-gray-400">{{ $product->stock_code ?? '-' }}</span>
                            </td>
                            <td class="px-3 py-3 font-medium text-gray-900">
                                {{ $product->name }}
                                @if($product->is_service)
                                    <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-600">Hizmet</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 hidden md:table-cell">
                                @if($product->category)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-brand-50 text-brand-600 border border-brand-200">
                                        {{ $product->category->name }}
                                    </span>
                                @else
                                    <span class="text-gray-300">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 hidden lg:table-cell">
                                @if($product->firm)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-orange-50 text-orange-600 border border-orange-200">
                                        {{ $product->firm->name }}
                                    </span>
                                @else
                                    <span class="text-gray-300">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right font-mono text-xs hidden md:table-cell">
                                {{ $product->purchase_price ? formatCurrency($product->purchase_price) : '-' }}
                            </td>
                            <td class="px-3 py-3 text-right font-mono font-medium text-gray-900">
                                {{ formatCurrency($product->sale_price) }}
                            </td>
                            <td class="px-3 py-3 text-center text-xs hidden lg:table-cell">%{{ $product->vat_rate ?? 0 }}</td>
                            <td class="px-3 py-3 text-center">
                                @php $isCritical = !$product->is_service && $product->critical_stock && $product->stock_quantity <= $product->critical_stock; @endphp
                                <span class="{{ $isCritical ? 'text-red-500 font-semibold' : '' }}">
                                    {{ $product->is_service ? '-' : ($product->stock_quantity ?? '0') }}
                                </span>
                                @if($isCritical)
                                    <i class="fas fa-exclamation-triangle text-red-400 text-xs ml-0.5"></i>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-xs hidden lg:table-cell">{{ $product->unit ?? 'Adet' }}</td>
                            <td class="px-3 py-3 text-center hidden md:table-cell">
                                @if($product->show_on_pos ?? true)
                                    <i class="fas fa-check-circle text-emerald-500 text-xs"></i>
                                @else
                                    <i class="fas fa-times-circle text-gray-300 text-xs"></i>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <div class="flex items-center justify-center gap-0.5">
                                    <button @click="openEdit({{ json_encode([
                                                'id' => $product->id,
                                                'name' => $product->name,
                                                'barcode' => $product->barcode,
                                                'stock_code' => $product->stock_code,
                                                'category_id' => $product->category_id,
                                                'purchase_price' => $product->purchase_price,
                                                'sale_price' => $product->sale_price,
                                                'vat_rate' => $product->vat_rate,
                                                'stock_quantity' => $product->stock_quantity,
                                                'critical_stock' => $product->critical_stock,
                                                'unit' => $product->unit,
                                                'country_of_origin' => $product->country_of_origin,
                                                'is_active' => $product->is_active,
                                                'is_service' => $product->is_service,
                                                'show_on_pos' => $product->show_on_pos ?? true,
                                                'description' => $product->description,
                                                'image_url' => $product->image_url,
                                                'firm_id' => $product->firm_id,
                                            ]) }})"  
                                            class="p-1.5 text-gray-400 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors"
                                            title="Düzenle">
                                        <i class="fas fa-pen text-xs"></i>
                                    </button>
                                    <button @click="openHistory({{ $product->id }}, '{{ addslashes($product->name) }}')"
                                            class="p-1.5 text-gray-400 hover:text-purple-500 hover:bg-purple-50 rounded-lg transition-colors"
                                            title="İşlem Geçmişi">
                                        <i class="fas fa-history text-xs"></i>
                                    </button>
                                    <button @click="openPrices({{ $product->id }}, '{{ addslashes($product->name) }}')"
                                            class="p-1.5 text-gray-400 hover:text-amber-500 hover:bg-amber-50 rounded-lg transition-colors"
                                            title="Çoklu Fiyat">
                                        <i class="fas fa-tags text-xs"></i>
                                    </button>
                                    <button @click="openProductVariants({{ $product->id }}, '{{ addslashes($product->name) }}')"
                                            class="p-1.5 text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 rounded-lg transition-colors"
                                            title="Varyantlar">
                                        <i class="fas fa-layer-group text-xs"></i>
                                    </button>
                                    <button @click="openSubDefs({{ $product->id }}, '{{ addslashes($product->name) }}')"
                                            class="p-1.5 text-gray-400 hover:text-teal-500 hover:bg-teal-50 rounded-lg transition-colors"
                                            title="Alt Ürünler">
                                        <i class="fas fa-boxes text-xs"></i>
                                    </button>
                                    <button @click="confirmDelete({{ $product->id }}, '{{ addslashes($product->name) }}')"
                                            class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Sil">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <p class="text-gray-400 text-sm">Henüz ürün eklenmemiş</p>
                                    <button @click="openCreate()" class="text-brand-500 hover:text-brand-700 text-sm font-medium">+ İlk ürünü ekle</button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $products->links() }}</div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- SIDE PANEL: Ürün Oluştur / Düzenle --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div x-show="showPanel"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex justify-end" style="display: none;">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closePanel()"></div>
        <div x-show="showPanel"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="relative w-full max-w-lg bg-white border-l border-gray-200 shadow-2xl overflow-y-auto">

            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
                <h2 class="text-lg font-semibold text-gray-900" x-text="editingId ? 'Ürün Düzenle' : 'Yeni Ürün'"></h2>
                <button @click="closePanel()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form @submit.prevent="submitForm()" class="p-6 space-y-4">
                {{-- Görsel Yükleme --}}
                <div class="flex items-center gap-4">
                    <div class="w-20 h-20 rounded-xl bg-gray-100 border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden relative group cursor-pointer"
                         @click="$refs.imageInput.click()">
                        <template x-if="imagePreview">
                            <img :src="imagePreview" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!imagePreview">
                            <div class="text-center">
                                <i class="fas fa-camera text-gray-300 text-lg"></i>
                                <p class="text-[9px] text-gray-400 mt-0.5">Görsel Ekle</p>
                            </div>
                        </template>
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <i class="fas fa-upload text-white"></i>
                        </div>
                    </div>
                    <input type="file" x-ref="imageInput" @change="handleImageSelect($event)" accept="image/*" class="hidden">
                    <div class="flex-1">
                        <p class="text-xs text-gray-500">JPEG, PNG, WebP — Max 2MB</p>
                        <button type="button" x-show="imagePreview" @click="imagePreview=null; imageFile=null" class="text-xs text-red-500 hover:text-red-700 mt-1">Görseli Kaldır</button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ürün Adı <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.name" required
                           class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400"
                           placeholder="Ürün adını girin">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Barkod</label>
                        <input type="text" x-model="form.barcode"
                               class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400"
                               placeholder="Barkod numarası">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stok Kodu</label>
                        <input type="text" x-model="form.stock_code"
                               class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400"
                               placeholder="STK-001">
                    </div>
                </div>

                <div x-data="categoryPicker()" x-init="initCategoryTree()">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    
                    {{-- Seçili kategori gösterimi + açma butonu --}}
                    <div class="relative">
                        <button type="button" @click="catOpen = !catOpen"
                                class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 flex items-center justify-between transition-all"
                                :class="catOpen ? 'ring-2 ring-brand-500/20 border-brand-500' : ''">
                            <span x-text="selectedCatName || 'Kategori seçin'" :class="selectedCatName ? 'text-gray-800' : 'text-gray-400'"></span>
                            <div class="flex items-center gap-1.5">
                                <template x-if="form.category_id">
                                    <button type="button" @click.stop="clearCat()" class="text-gray-400 hover:text-red-500 transition-colors">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                </template>
                                <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform" :class="catOpen ? 'rotate-180' : ''"></i>
                            </div>
                        </button>
                        
                        {{-- Dropdown panel --}}
                        <div x-show="catOpen" x-transition @click.outside="catOpen = false"
                             class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-72 overflow-hidden flex flex-col">
                            
                            {{-- Arama --}}
                            <div class="p-2 border-b border-gray-100">
                                <input type="text" x-model="catSearch" placeholder="Kategori ara..."
                                       class="w-full text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400">
                            </div>
                            
                            {{-- Ağaç --}}
                            <div class="overflow-y-auto p-1 flex-1" style="max-height: 200px">
                                <template x-for="cat in filteredTree()" :key="cat.id">
                                    <div>
                                        <div class="flex items-center gap-1.5 px-2 py-1.5 rounded-lg hover:bg-gray-50 cursor-pointer group"
                                             :class="form.category_id == cat.id ? 'bg-brand-50 text-brand-700' : ''">
                                            <button type="button" x-show="cat.children && cat.children.length"
                                                    @click.stop="toggleExpand(cat.id)"
                                                    class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-gray-600 shrink-0">
                                                <i class="fas text-[9px]" :class="isExpanded(cat.id) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                            </button>
                                            <span x-show="!cat.children || !cat.children.length" class="w-5"></span>
                                            <span @click="selectCat(cat)" class="flex-1 text-sm truncate" x-text="cat.name"></span>
                                            <button type="button" @click.stop="startAddSub(cat)"
                                                    class="sm:opacity-0 sm:group-hover:opacity-100 text-gray-400 hover:text-brand-600 transition-all shrink-0" title="Alt kategori ekle">
                                                <i class="fas fa-plus text-[9px]"></i>
                                            </button>
                                        </div>
                                        {{-- 2. Seviye --}}
                                        <template x-if="isExpanded(cat.id) && cat.children">
                                            <div class="ml-4">
                                                <template x-for="sub in cat.children" :key="sub.id">
                                                    <div>
                                                        <div class="flex items-center gap-1.5 px-2 py-1.5 rounded-lg hover:bg-gray-50 cursor-pointer group"
                                                             :class="form.category_id == sub.id ? 'bg-brand-50 text-brand-700' : ''">
                                                            <button type="button" x-show="sub.children && sub.children.length"
                                                                    @click.stop="toggleExpand(sub.id)"
                                                                    class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-gray-600 shrink-0">
                                                                <i class="fas text-[9px]" :class="isExpanded(sub.id) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                                            </button>
                                                            <span x-show="!sub.children || !sub.children.length" class="w-5"></span>
                                                            <span @click="selectCat(sub)" class="flex-1 text-sm truncate" x-text="sub.name"></span>
                                                            <button type="button" @click.stop="startAddSub(sub)"
                                                                    class="sm:opacity-0 sm:group-hover:opacity-100 text-gray-400 hover:text-brand-600 transition-all shrink-0" title="Alt kategori ekle">
                                                                <i class="fas fa-plus text-[9px]"></i>
                                                            </button>
                                                        </div>
                                                        {{-- 3+ Seviye (recursive render) --}}
                                                        <template x-if="isExpanded(sub.id) && sub.children">
                                                            <div class="ml-4">
                                                                <template x-for="deep in sub.children" :key="deep.id">
                                                                    <div>
                                                                        <div class="flex items-center gap-1.5 px-2 py-1.5 rounded-lg hover:bg-gray-50 cursor-pointer group"
                                                                             :class="form.category_id == deep.id ? 'bg-brand-50 text-brand-700' : ''">
                                                                            <button type="button" x-show="deep.children && deep.children.length"
                                                                                    @click.stop="toggleExpand(deep.id)"
                                                                                    class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-gray-600 shrink-0">
                                                                                <i class="fas text-[9px]" :class="isExpanded(deep.id) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                                                            </button>
                                                                            <span x-show="!deep.children || !deep.children.length" class="w-5"></span>
                                                                            <span @click="selectCat(deep)" class="flex-1 text-sm truncate" x-text="deep.name"></span>
                                                                            <button type="button" @click.stop="startAddSub(deep)"
                                                                                    class="sm:opacity-0 sm:group-hover:opacity-100 text-gray-400 hover:text-brand-600 transition-all shrink-0" title="Alt kategori ekle">
                                                                                <i class="fas fa-plus text-[9px]"></i>
                                                                            </button>
                                                                        </div>
                                                                        {{-- 4+ Seviye --}}
                                                                        <template x-if="isExpanded(deep.id) && deep.children && deep.children.length">
                                                                            <div class="ml-4">
                                                                                <template x-for="d4 in deep.children" :key="d4.id">
                                                                                    <div class="flex items-center gap-1.5 px-2 py-1.5 rounded-lg hover:bg-gray-50 cursor-pointer group"
                                                                                         :class="form.category_id == d4.id ? 'bg-brand-50 text-brand-700' : ''">
                                                                                        <span class="w-5"></span>
                                                                                        <span @click="selectCat(d4)" class="flex-1 text-sm truncate" x-text="d4.name"></span>
                                                                                        <button type="button" @click.stop="startAddSub(d4)"
                                                                                                class="sm:opacity-0 sm:group-hover:opacity-100 text-gray-400 hover:text-brand-600 transition-all shrink-0" title="Alt kategori ekle">
                                                                                            <i class="fas fa-plus text-[9px]"></i>
                                                                                        </button>
                                                                                    </div>
                                                                                </template>
                                                                            </div>
                                                                        </template>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <div x-show="filteredTree().length === 0" class="text-center text-gray-400 text-sm py-3">Sonuç bulunamadı</div>
                            </div>
                            
                            {{-- Yeni Kategori Oluştur --}}
                            <div class="border-t border-gray-100 p-2">
                                <template x-if="!addingCat">
                                    <button type="button" @click="addingCat = true; addingParent = null; newCatName = ''"
                                            class="w-full text-sm text-brand-600 hover:bg-brand-50 rounded-lg py-2 font-medium transition-colors flex items-center justify-center gap-1.5">
                                        <i class="fas fa-plus text-xs"></i> Yeni Kategori Oluştur
                                    </button>
                                </template>
                                <template x-if="addingCat">
                                    <div>
                                        <div x-show="addingParent" class="text-[11px] text-gray-500 mb-1 flex items-center gap-1">
                                            <i class="fas fa-level-down-alt text-gray-400"></i>
                                            <span x-text="addingParent?.name"></span> altına ekleniyor
                                            <button type="button" @click="addingParent = null" class="text-gray-400 hover:text-red-500 ml-1"><i class="fas fa-times text-[9px]"></i></button>
                                        </div>
                                        <div class="flex gap-2">
                                            <input type="text" x-model="newCatName" x-ref="newCatInput" @keydown.enter="saveCat()" @keydown.escape="addingCat = false"
                                                   placeholder="Kategori adı..." autofocus
                                                   class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400">
                                            <button type="button" @click="saveCat()" :disabled="!newCatName.trim() || savingCat"
                                                    class="px-3 py-1.5 bg-brand-600 text-white text-sm rounded-lg hover:bg-brand-700 disabled:opacity-50 transition-colors">
                                                <i class="fas" :class="savingCat ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                                            </button>
                                            <button type="button" @click="addingCat = false"
                                                    class="px-3 py-1.5 bg-gray-100 text-gray-600 text-sm rounded-lg hover:bg-gray-200 transition-colors">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Firma Seçimi --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Firma (Tedarikçi)</label>
                    <select x-model="form.firm_id"
                            class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <option value="">Firma seçin</option>
                        @foreach($firms as $firm)
                            <option value="{{ $firm->id }}">{{ $firm->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alış Fiyatı</label>
                        <div class="relative">
                            <input type="number" x-model="form.purchase_price" @input="calcProfit()" step="0.01" min="0"
                                   class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl pl-4 pr-8 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400"
                                   placeholder="0.00">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₺</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Satış Fiyatı <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="number" x-model="form.sale_price" @input="calcProfit()" step="0.01" min="0" required
                                   class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl pl-4 pr-8 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400"
                                   placeholder="0.00">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₺</span>
                        </div>
                    </div>
                </div>
                <div x-show="profitRate !== null" class="flex items-center gap-2 -mt-2 px-1">
                    <span class="text-xs text-gray-500">Kâr Oranı:</span>
                    <span class="text-xs font-semibold" :class="profitRate >= 0 ? 'text-emerald-600' : 'text-red-500'" x-text="'%' + profitRate"></span>
                    <span class="text-xs text-gray-400" x-text="'(' + profitAmount + ' ₺ kâr)'"></span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">KDV Oranı</label>
                    <select x-model="form.vat_rate" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <option value="0">%0</option><option value="1">%1</option><option value="10">%10</option><option value="20">%20</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stok Miktarı</label>
                        <input type="number" x-model="form.stock_quantity" min="0" step="0.01"
                               class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400"
                               placeholder="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kritik Stok</label>
                        <input type="number" x-model="form.critical_stock" min="0" step="0.01"
                               class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400"
                               placeholder="0">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Birim</label>
                        <select x-model="form.unit" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                            <option>Adet</option><option>Bardak</option><option>CL</option><option>Dilim</option><option>Fincan</option>
                            <option>Gram</option><option>Kaşık</option><option>Kavanoz</option><option>Kilo</option><option>Koli</option>
                            <option>Külah</option><option>Kutu</option><option>Litre</option><option>Miligram</option><option>Mililitre</option>
                            <option>ML</option><option>Paket</option><option>Porsiyon</option><option>Poşet</option><option>Şişe</option>
                            <option>Tabak</option><option>Tencere</option><option>Top</option><option>Tutam</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Menşe Ülke</label>
                        <input type="text" x-model="form.country_of_origin"
                               class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400"
                               placeholder="Türkiye">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Açıklama</label>
                    <textarea x-model="form.description" rows="2"
                              class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400"
                              placeholder="Ürün açıklaması..."></textarea>
                </div>

                <div class="flex items-center gap-6 py-2">
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <button type="button" @click="form.is_active = !form.is_active"
                                :class="form.is_active ? 'bg-brand-500' : 'bg-gray-300'"
                                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors">
                            <span :class="form.is_active ? 'translate-x-5' : 'translate-x-1'" class="inline-block h-3 w-3 rounded-full bg-white transition-transform shadow-sm"></span>
                        </button>
                        Aktif
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <button type="button" @click="form.show_on_pos = !form.show_on_pos"
                                :class="form.show_on_pos ? 'bg-emerald-500' : 'bg-gray-300'"
                                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors">
                            <span :class="form.show_on_pos ? 'translate-x-5' : 'translate-x-1'" class="inline-block h-3 w-3 rounded-full bg-white transition-transform shadow-sm"></span>
                        </button>
                        POS'ta Göster
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <button type="button" @click="form.is_service = !form.is_service"
                                :class="form.is_service ? 'bg-indigo-500' : 'bg-gray-300'"
                                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors">
                            <span :class="form.is_service ? 'translate-x-5' : 'translate-x-1'" class="inline-block h-3 w-3 rounded-full bg-white transition-transform shadow-sm"></span>
                        </button>
                        Hizmet
                    </label>
                </div>

                {{-- Şube Bilgileri --}}
                <div x-show="editingId" class="border-t border-gray-200 pt-3">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-900"><i class="fas fa-store text-brand-500 mr-1.5"></i>Şube Bilgileri</h3>
                        <button type="button" @click="showBranchSection = !showBranchSection" class="text-xs text-brand-500 hover:text-brand-700">
                            <span x-text="showBranchSection ? 'Gizle' : 'Göster'"></span>
                            <i class="fas ml-1" :class="showBranchSection ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        </button>
                    </div>
                    <div x-show="showBranchSection" x-transition class="space-y-2">
                        <div x-show="branchDataLoading" class="text-center py-3"><i class="fas fa-spinner fa-spin text-brand-500"></i></div>
                        <template x-for="(b, bi) in branchData" :key="b.branch_id">
                            <div class="bg-gray-50 rounded-xl border border-gray-100 p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" :checked="b.enabled" @change="b.enabled = $event.target.checked" class="w-4 h-4 text-brand-500 rounded border-gray-300">
                                        <span class="text-sm font-medium text-gray-900" x-text="b.branch_name"></span>
                                    </div>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full" :class="b.enabled ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-200 text-gray-500'" x-text="b.enabled ? 'Aktif' : 'Kapalı'"></span>
                                </div>
                                <div x-show="b.enabled" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] text-gray-500 mb-0.5">Satış Fiyatı (₺)</label>
                                        <input type="number" x-model="b.sale_price" step="0.01" min="0" class="w-full text-sm px-2.5 py-1.5 border border-gray-200 rounded-lg bg-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500/20">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-gray-500 mb-0.5">Stok Miktarı</label>
                                        <input type="number" x-model="b.stock_quantity" step="0.01" min="0" class="w-full text-sm px-2.5 py-1.5 border border-gray-200 rounded-lg bg-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500/20">
                                    </div>
                                </div>
                            </div>
                        </template>
                        <p x-show="branchData.length === 0 && !branchDataLoading" class="text-xs text-gray-400 text-center py-3">Şube bulunamadı</p>
                    </div>
                </div>

                <div class="flex gap-3 pt-3 border-t border-gray-200">
                    <button type="button" @click="closePanel()"
                            class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 border border-gray-200 rounded-xl transition-colors">İptal</button>
                    <button type="submit" :disabled="saving"
                            class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg rounded-xl transition-all disabled:opacity-50 flex items-center justify-center gap-2">
                        <svg x-show="saving" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="editingId ? 'Güncelle' : 'Kaydet'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- MODAL: İşlem Geçmişi --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div x-show="showHistoryModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showHistoryModal=false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[80vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                <div><h3 class="text-lg font-bold text-gray-900">İşlem Geçmişi</h3><p class="text-sm text-gray-500" x-text="historyProductName"></p></div>
                <button @click="showHistoryModal=false" class="text-gray-400 hover:text-gray-600 p-1"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 p-4">
                <div x-show="historyLoading" class="flex items-center justify-center py-12"><i class="fas fa-spinner fa-spin text-2xl text-brand-500"></i></div>
                <div x-show="!historyLoading && historyList.length === 0" class="text-center py-12 text-gray-400"><i class="fas fa-history text-4xl mb-3 block"></i><p>Henüz hareket kaydı yok</p></div>
                <table x-show="!historyLoading && historyList.length > 0" class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-3 py-2 text-left">Tarih</th><th class="px-3 py-2 text-left">Tür</th>
                            <th class="px-3 py-2 text-left">Firm/Müşteri</th><th class="px-3 py-2 text-left">Not</th>
                            <th class="px-3 py-2 text-right">Miktar</th><th class="px-3 py-2 text-right">Kalan</th>
                            <th class="px-3 py-2 text-right">Birim Fiyat</th><th class="px-3 py-2 text-right">Toplam</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="m in historyList" :key="m.movement_date + m.quantity">
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 text-gray-500 whitespace-nowrap" x-text="m.movement_date ? m.movement_date.slice(0,16).replace('T',' ') : '-'"></td>
                                <td class="px-3 py-2"><span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="m.type==='in'?'bg-emerald-100 text-emerald-700':m.type==='out'?'bg-red-100 text-red-700':'bg-gray-100 text-gray-600'" x-text="m.type==='in'?'Giriş':m.type==='out'?'Çıkış':(m.type||'-')"></span></td>
                                <td class="px-3 py-2 text-gray-600" x-text="m.firm_customer || '-'"></td>
                                <td class="px-3 py-2 text-gray-500 max-w-[160px] truncate" x-text="m.note || '-'"></td>
                                <td class="px-3 py-2 text-right font-mono" x-text="m.quantity"></td>
                                <td class="px-3 py-2 text-right font-mono text-gray-500" x-text="m.remaining ?? '-'"></td>
                                <td class="px-3 py-2 text-right font-mono" x-text="m.unit_price ? parseFloat(m.unit_price).toFixed(2)+' ₺' : '-'"></td>
                                <td class="px-3 py-2 text-right font-mono font-medium" x-text="m.total ? parseFloat(m.total).toFixed(2)+' ₺' : '-'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- MODAL: Silme Onayı --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div x-show="showDeleteModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showDeleteModal=false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center"><i class="fas fa-exclamation-triangle text-red-500"></i></div>
                <div><h3 class="text-lg font-semibold text-gray-900">Ürün Sil</h3><p class="text-sm text-gray-500">Bu işlem geri alınamaz.</p></div>
            </div>
            <p class="text-gray-600 text-sm mb-6"><span class="font-medium text-gray-900" x-text="deleteName"></span> ürününü silmek istediğinize emin misiniz?</p>
            <div class="flex gap-3">
                <button @click="showDeleteModal=false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 border border-gray-200 rounded-xl">Vazgeç</button>
                <button @click="deleteProduct()" :disabled="deleting" class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 rounded-xl disabled:opacity-50 flex items-center justify-center gap-2">
                    <i x-show="deleting" class="fas fa-spinner fa-spin"></i> Evet, Sil
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- MODAL: Çoklu Fiyat --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div x-show="showPricesModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showPricesModal=false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                <div><h3 class="text-lg font-bold text-gray-900"><i class="fas fa-tags text-amber-500 mr-2"></i>Çoklu Fiyat</h3><p class="text-sm text-gray-500" x-text="pricesProductName"></p></div>
                <button @click="showPricesModal=false" class="text-gray-400 hover:text-gray-600 p-1"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 p-5">
                <div class="space-y-2 mb-4">
                    <template x-for="(p, idx) in pricesList" :key="p.id">
                        <div class="flex items-center gap-3 bg-gray-50 rounded-xl px-4 py-2.5 border border-gray-100">
                            <template x-if="editingPriceId !== p.id">
                                <div class="flex items-center gap-3 w-full">
                                    <div class="flex-1"><span class="text-sm font-medium text-gray-900" x-text="p.label"></span></div>
                                    <span class="text-sm font-bold text-blue-600" x-text="parseFloat(p.price).toFixed(2)+' ₺'"></span>
                                    <button @click="editingPriceId=p.id; editPriceLabel=p.label; editPriceValue=p.price" class="p-1.5 text-gray-400 hover:text-brand-600 rounded-lg"><i class="fas fa-pen text-xs"></i></button>
                                    <button @click="deletePrice(p.id, idx)" class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg"><i class="fas fa-trash text-xs"></i></button>
                                </div>
                            </template>
                            <template x-if="editingPriceId === p.id">
                                <div class="flex items-center gap-2 w-full">
                                    <input type="text" x-model="editPriceLabel" class="flex-1 text-sm px-2 py-1.5 border border-gray-200 rounded-lg focus:border-brand-500">
                                    <input type="number" x-model="editPriceValue" step="0.01" min="0" class="w-24 text-sm px-2 py-1.5 border border-gray-200 rounded-lg text-right">
                                    <button @click="updatePrice(p.id, idx)" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg"><i class="fas fa-check text-xs"></i></button>
                                    <button @click="editingPriceId=null" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg"><i class="fas fa-times text-xs"></i></button>
                                </div>
                            </template>
                        </div>
                    </template>
                    <div x-show="pricesList.length===0 && !pricesLoading" class="text-center py-6 text-gray-400 text-sm"><i class="fas fa-tags text-2xl mb-2 block"></i><p>Henüz alternatif fiyat eklenmemiş</p></div>
                    <div x-show="pricesLoading" class="flex items-center justify-center py-6"><i class="fas fa-spinner fa-spin text-xl text-brand-500"></i></div>
                </div>
                <div class="border-t border-gray-200 pt-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Yeni Fiyat Ekle</h4>
                    <div class="flex items-end gap-3">
                        <div class="flex-1">
                            <label class="block text-xs text-gray-500 mb-1">Fiyat Adı</label>
                            <input type="text" x-model="newPriceLabel" placeholder="Kredi Kartı, Nakit..." class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl focus:border-brand-500 placeholder-gray-400">
                        </div>
                        <div class="w-28">
                            <label class="block text-xs text-gray-500 mb-1">Fiyat (₺)</label>
                            <input type="number" x-model="newPriceValue" step="0.01" min="0" placeholder="0.00" class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl text-right">
                        </div>
                        <button @click="addPrice()" :disabled="!newPriceLabel.trim() || !newPriceValue" class="px-4 py-2 bg-gradient-to-r from-brand-500 to-purple-600 text-white text-sm font-medium rounded-xl hover:shadow-lg disabled:opacity-50 whitespace-nowrap">
                            <i class="fas fa-plus mr-1"></i>Ekle
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-1.5 mt-3">
                        <template x-for="preset in ['Kredi Kartı', 'Nakit', 'Toptan', 'Perakende', 'Online', 'Personel']" :key="preset">
                            <button @click="newPriceLabel = preset" class="px-2.5 py-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg" x-text="preset"></button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- MODAL: Ürün Varyantları --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div x-show="showProductVariantModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showProductVariantModal=false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                <div><h3 class="text-lg font-bold text-gray-900"><i class="fas fa-layer-group text-indigo-500 mr-2"></i>Ürün Varyantları</h3><p class="text-sm text-gray-500" x-text="variantProductName"></p></div>
                <button @click="showProductVariantModal=false" class="text-gray-400 hover:text-gray-600 p-1"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 p-5">
                <div x-show="variantLoading" class="text-center py-6"><i class="fas fa-spinner fa-spin text-xl text-brand-500"></i></div>
                <div x-show="!variantLoading" class="space-y-4">
                    <template x-for="vt in allVariantTypes" :key="vt.id">
                        <div class="bg-gray-50 rounded-xl border border-gray-100 p-3">
                            <h4 class="text-sm font-semibold text-gray-800 mb-2" x-text="vt.name"></h4>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="val in vt.values" :key="val.id">
                                    <button @click="toggleVariantValue(val.id)" type="button"
                                            :class="selectedVariantValueIds.includes(val.id) ? 'bg-indigo-500 text-white border-indigo-500' : 'bg-white text-gray-700 border-gray-200 hover:border-indigo-300'"
                                            class="px-3 py-1.5 text-xs font-medium border rounded-lg transition-colors" x-text="val.value"></button>
                                </template>
                                <span x-show="vt.values.length === 0" class="text-xs text-gray-400">Değer yok</span>
                            </div>
                        </div>
                    </template>
                    <div x-show="allVariantTypes.length === 0" class="text-center py-6 text-gray-400 text-sm">
                        <p>Önce varyant tipleri oluşturun (üst menüden "Varyantlar" butonuna basın).</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-3">
                <button @click="showProductVariantModal=false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl">Vazgeç</button>
                <button @click="saveProductVariants()" :disabled="variantSaving" class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl disabled:opacity-50 flex items-center justify-center gap-2">
                    <i x-show="variantSaving" class="fas fa-spinner fa-spin"></i> Kaydet
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- MODAL: Varyant Tipleri Yönetimi --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div x-show="showVariantTypeModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showVariantTypeModal=false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                <h3 class="text-lg font-bold text-gray-900"><i class="fas fa-layer-group text-purple-500 mr-2"></i>Varyant Tipleri</h3>
                <button @click="showVariantTypeModal=false" class="text-gray-400 hover:text-gray-600 p-1"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 p-5 space-y-4">
                <div x-show="vtLoading" class="text-center py-6"><i class="fas fa-spinner fa-spin text-xl text-brand-500"></i></div>
                <template x-for="vt in vtList" :key="vt.id">
                    <div class="bg-gray-50 rounded-xl border border-gray-100 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-gray-900" x-text="vt.name"></h4>
                            <button @click="deleteVariantType(vt.id)" class="text-xs text-red-500 hover:text-red-700"><i class="fas fa-trash mr-1"></i>Sil</button>
                        </div>
                        <div class="flex flex-wrap gap-1.5 mb-3">
                            <template x-for="val in vt.values" :key="val.id">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs bg-white border border-gray-200 rounded-lg text-gray-700">
                                    <span x-text="val.value"></span>
                                    <button @click="deleteVariantValue(val.id, vt)" class="text-gray-400 hover:text-red-500"><i class="fas fa-times text-[10px]"></i></button>
                                </span>
                            </template>
                        </div>
                        <div class="flex gap-2">
                            <input type="text" x-model="vt._newValue" placeholder="Yeni değer..." class="flex-1 text-sm px-3 py-1.5 border border-gray-200 rounded-lg placeholder-gray-400" @keydown.enter.prevent="addVariantValue(vt)">
                            <button @click="addVariantValue(vt)" class="px-3 py-1.5 text-xs font-medium bg-indigo-500 text-white rounded-lg hover:bg-indigo-600">Ekle</button>
                        </div>
                    </div>
                </template>
                <div class="border-t border-gray-200 pt-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Yeni Varyant Tipi</h4>
                    <div class="flex gap-2">
                        <input type="text" x-model="newVtName" placeholder="örn. Renk, Beden, Boyut..." class="flex-1 text-sm px-3 py-2 border border-gray-200 rounded-xl placeholder-gray-400" @keydown.enter.prevent="addVariantType()">
                        <button @click="addVariantType()" :disabled="!newVtName.trim()" class="px-4 py-2 text-sm font-medium bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl disabled:opacity-50">Oluştur</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- MODAL: Alt Ürün Tanımları (Koli/Paket/Adet) --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div x-show="showSubDefModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showSubDefModal=false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                <div><h3 class="text-lg font-bold text-gray-900"><i class="fas fa-boxes text-teal-500 mr-2"></i>Alt Ürün Tanımları</h3><p class="text-sm text-gray-500" x-text="subDefProductName"></p></div>
                <button @click="showSubDefModal=false" class="text-gray-400 hover:text-gray-600 p-1"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 p-5">
                <p class="text-xs text-gray-500 mb-4">Koli → Paket → Adet gibi çarpan ilişkileri tanımlayın. Örn: 1 Koli = 12 Adet</p>
                <div x-show="subDefLoading" class="text-center py-4"><i class="fas fa-spinner fa-spin text-brand-500"></i></div>
                <div class="space-y-2 mb-4">
                    <template x-for="(def, idx) in subDefList" :key="def.id">
                        <div class="flex items-center gap-3 bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-900" x-text="def.sub_product?.name || 'Ürün #'+def.sub_product_id"></span>
                                <span class="text-xs text-gray-500 ml-2" x-text="'× ' + def.multiplier"></span>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded-full" :class="def.apply_to_branches ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-200 text-gray-500'" x-text="def.apply_to_branches ? 'Şubelere uygula' : 'Genel'"></span>
                            <button @click="deleteSubDef(def.id, idx)" class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg"><i class="fas fa-trash text-xs"></i></button>
                        </div>
                    </template>
                    <div x-show="subDefList.length===0 && !subDefLoading" class="text-center py-6 text-gray-400 text-sm"><i class="fas fa-boxes text-2xl mb-2 block"></i><p>Henüz alt ürün tanımı yok</p></div>
                </div>

                <div class="border-t border-gray-200 pt-4 space-y-3">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Yeni Alt Ürün Tanımı</h4>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Alt Ürün (Aranacak)</label>
                        <div class="relative">
                            <input type="text" x-model="subDefSearch" @input.debounce.300ms="searchSubDefProducts()" placeholder="Ürün adı veya barkod..."
                                   class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl placeholder-gray-400">
                            <div x-show="subDefSearchResults.length > 0" class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl z-10 max-h-40 overflow-y-auto">
                                <template x-for="sp in subDefSearchResults" :key="sp.id">
                                    <button @click="selectSubDefProduct(sp)" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex items-center justify-between">
                                        <span x-text="sp.name"></span>
                                        <span class="text-xs text-gray-400" x-text="sp.barcode || sp.stock_code || ''"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <p x-show="selectedSubDefProduct" class="text-xs text-emerald-600 mt-1"><i class="fas fa-check mr-1"></i><span x-text="selectedSubDefProduct?.name"></span></p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Çarpan</label>
                            <input type="number" x-model="newSubDefMultiplier" step="0.01" min="0.01" placeholder="12" class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                                <input type="checkbox" x-model="newSubDefApplyBranches" class="rounded text-brand-500 border-gray-300 w-3.5 h-3.5"> Şubelere Uygula
                            </label>
                        </div>
                    </div>
                    <button @click="addSubDef()" :disabled="!selectedSubDefProduct || !newSubDefMultiplier" class="w-full px-4 py-2 text-sm font-medium bg-gradient-to-r from-teal-500 to-emerald-600 text-white rounded-xl disabled:opacity-50">
                        <i class="fas fa-plus mr-1"></i>Alt Ürün Ekle
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- MODAL: Barkod Etiket Üretimi --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div x-show="showLabelModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showLabelModal=false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                <h3 class="text-lg font-bold text-gray-900"><i class="fas fa-barcode text-purple-500 mr-2"></i>Barkod Etiketi Üret</h3>
                <button @click="showLabelModal=false" class="text-gray-400 hover:text-gray-600 p-1"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-5 space-y-4">
                <div class="flex items-center gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Her üründen kaç adet</label>
                        <input type="number" x-model="labelQty" min="1" max="100" class="w-24 text-sm px-3 py-2 border border-gray-200 rounded-xl">
                    </div>
                    <button @click="generateLabels()" :disabled="labelLoading" class="px-4 py-2 text-sm font-medium bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl disabled:opacity-50">
                        <i x-show="labelLoading" class="fas fa-spinner fa-spin mr-1"></i> Üret
                    </button>
                    <button x-show="labelData.length > 0" @click="printLabels()" class="px-4 py-2 text-sm font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl border border-gray-200">
                        <i class="fas fa-print mr-1"></i> Yazdır
                    </button>
                </div>

                <div x-show="labelData.length > 0" class="grid grid-cols-1 sm:grid-cols-3 gap-3 overflow-y-auto max-h-96" id="labelContainer">
                    <template x-for="(lbl, li) in labelData" :key="li">
                        <div class="border border-gray-300 rounded-lg p-3 text-center bg-white">
                            <p class="text-xs font-medium text-gray-900 mb-1 truncate" x-text="lbl.name"></p>
                            <div class="font-mono text-lg font-bold tracking-wider text-gray-800 mb-1" x-text="lbl.barcode || '-'"></div>
                            <p class="text-sm font-bold text-brand-600" x-text="lbl.price"></p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- MODAL: Ürün Özet Dökümü --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div x-show="showSummaryModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showSummaryModal=false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                <h3 class="text-lg font-bold text-gray-900"><i class="fas fa-chart-pie text-emerald-500 mr-2"></i>Ürün Özet Dökümü</h3>
                <div class="flex items-center gap-2">
                    <button @click="printSummary()" class="text-xs text-gray-500 hover:text-gray-700 px-3 py-1.5 bg-gray-100 rounded-lg"><i class="fas fa-print mr-1"></i>Yazdır</button>
                    <button @click="showSummaryModal=false" class="text-gray-400 hover:text-gray-600 p-1"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="overflow-y-auto flex-1 p-5">
                <div x-show="summaryLoading" class="text-center py-12"><i class="fas fa-spinner fa-spin text-2xl text-brand-500"></i></div>
                <div x-show="!summaryLoading && summaryData">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-blue-50 rounded-xl p-4 text-center border border-blue-100">
                            <p class="text-2xl font-bold text-blue-700" x-text="summaryData?.total_products || 0"></p>
                            <p class="text-xs text-blue-500">Toplam Ürün</p>
                        </div>
                        <div class="bg-emerald-50 rounded-xl p-4 text-center border border-emerald-100">
                            <p class="text-lg font-bold text-emerald-700" x-text="formatMoney(summaryData?.total_stock_value)"></p>
                            <p class="text-xs text-emerald-500">Stok Maliyeti</p>
                        </div>
                        <div class="bg-purple-50 rounded-xl p-4 text-center border border-purple-100">
                            <p class="text-lg font-bold text-purple-700" x-text="formatMoney(summaryData?.total_sale_value)"></p>
                            <p class="text-xs text-purple-500">Stok Satış Değeri</p>
                        </div>
                        <div class="bg-red-50 rounded-xl p-4 text-center border border-red-100">
                            <p class="text-2xl font-bold text-red-700" x-text="(summaryData?.low_stock_count || 0) + ' / ' + (summaryData?.zero_stock_count || 0)"></p>
                            <p class="text-xs text-red-500">Kritik / Sıfır Stok</p>
                        </div>
                    </div>
                    <table class="w-full text-xs" id="summaryTable">
                        <thead class="bg-gray-50 text-gray-500 uppercase">
                            <tr>
                                <th class="px-2 py-2 text-left">Barkod</th><th class="px-2 py-2 text-left">Stok Kodu</th>
                                <th class="px-2 py-2 text-left">Ürün Adı</th><th class="px-2 py-2 text-left">Kategori</th>
                                <th class="px-2 py-2 text-right">Alış</th><th class="px-2 py-2 text-right">Satış</th>
                                <th class="px-2 py-2 text-right">Stok</th><th class="px-2 py-2 text-right">Stok Değeri</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <template x-for="p in summaryData?.products || []" :key="p.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-2 py-1.5 font-mono text-gray-400" x-text="p.barcode || '-'"></td>
                                    <td class="px-2 py-1.5 font-mono text-gray-400" x-text="p.stock_code || '-'"></td>
                                    <td class="px-2 py-1.5 font-medium text-gray-900" x-text="p.name"></td>
                                    <td class="px-2 py-1.5 text-gray-500" x-text="p.category || '-'"></td>
                                    <td class="px-2 py-1.5 text-right font-mono" x-text="parseFloat(p.purchase_price).toFixed(2)"></td>
                                    <td class="px-2 py-1.5 text-right font-mono" x-text="parseFloat(p.sale_price).toFixed(2)"></td>
                                    <td class="px-2 py-1.5 text-right font-mono" x-text="p.stock_quantity"></td>
                                    <td class="px-2 py-1.5 text-right font-mono font-medium" x-text="parseFloat(p.stock_value).toFixed(2)+' ₺'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- MODAL: Excel İçe Aktarma --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div x-show="showImportModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showImportModal=false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4"><i class="fas fa-file-import text-blue-500 mr-2"></i>CSV İçe Aktar</h3>
            <p class="text-xs text-gray-500 mb-4">CSV dosyanız noktalı virgül (;) ile ayrılmış olmalı. Sütunlar: Barkod; Stok Kodu; Ürün Adı; Kategori; Birim; Alış Fiyatı; Satış Fiyatı; KDV%; Stok; Kritik Stok</p>
            <div class="mb-4">
                <input type="file" x-ref="importFile" accept=".csv,.txt" class="w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>
            <div x-show="importResult" class="mb-4 p-3 rounded-xl text-sm" :class="importResult?.errors?.length ? 'bg-amber-50 border border-amber-200' : 'bg-emerald-50 border border-emerald-200'">
                <p class="font-medium" x-text="importResult?.message"></p>
                <template x-if="importResult?.errors?.length">
                    <ul class="mt-2 text-xs text-red-600 space-y-0.5">
                        <template x-for="err in importResult.errors.slice(0,5)" :key="err">
                            <li x-text="err"></li>
                        </template>
                    </ul>
                </template>
            </div>
            <div class="flex gap-3">
                <button @click="showImportModal=false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl">İptal</button>
                <button @click="importCsv()" :disabled="importLoading" class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-blue-500 hover:bg-blue-600 rounded-xl disabled:opacity-50 flex items-center justify-center gap-2">
                    <i x-show="importLoading" class="fas fa-spinner fa-spin"></i> İçe Aktar
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- MODAL: Filtre Şablonu Kaydet --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div x-show="showSaveTemplateModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showSaveTemplateModal=false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-2"><i class="fas fa-save text-blue-500 mr-2"></i>Filtre Şablonu Kaydet</h3>
            <p class="text-xs text-gray-500 mb-4">Mevcut filtre ayarlarınızı bir şablon olarak kaydedin. Daha sonra tek tıkla yükleyebilirsiniz.</p>
            <input type="text" x-model="templateName" @keydown.enter="saveTemplate()" autofocus placeholder="Şablon adı girin..."
                   class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 mb-4 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400">
            <div class="flex gap-3">
                <button @click="showSaveTemplateModal=false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl">İptal</button>
                <button @click="saveTemplate()" :disabled="!templateName.trim()" class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-brand-500 hover:bg-brand-600 rounded-xl disabled:opacity-50">Kaydet</button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- MODAL: Toplu Kategori Atama --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div x-show="showBulkCategoryModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showBulkCategoryModal=false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4"><i class="fas fa-folder text-blue-500 mr-2"></i>Toplu Kategori Ata</h3>
            <p class="text-sm text-gray-500 mb-4"><span class="font-medium" x-text="selectedIds.length"></span> ürüne kategori atanacak</p>
            <select x-model="bulkCategoryId" class="w-full bg-gray-50 border border-gray-200 text-sm rounded-xl px-4 py-2.5 mb-4">
                <option value="">Kategori seçin</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @foreach($cat->children->sortBy('name') as $sub)
                        <option value="{{ $sub->id }}">&nbsp;&nbsp;└ {{ $sub->name }}</option>
                    @endforeach
                @endforeach
            </select>
            <div class="flex gap-3">
                <button @click="showBulkCategoryModal=false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl">İptal</button>
                <button @click="bulkAssignCategory()" :disabled="!bulkCategoryId" class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-blue-500 hover:bg-blue-600 rounded-xl disabled:opacity-50">Uygula</button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- MODAL: Toplu Fiyat Güncelleme --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div x-show="showBulkPriceModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showBulkPriceModal=false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4"><i class="fas fa-dollar-sign text-amber-500 mr-2"></i>Toplu Fiyat Güncelle</h3>
            <p class="text-sm text-gray-500 mb-4"><span class="font-medium" x-text="selectedIds.length"></span> ürünün fiyatı güncellenecek</p>
            <div class="space-y-3 mb-4">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Fiyat Alanı</label>
                    <select x-model="bulkPriceField" class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl">
                        <option value="sale_price">Satış Fiyatı</option>
                        <option value="purchase_price">Alış Fiyatı</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Güncelleme Tipi</label>
                    <select x-model="bulkPriceType" class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl">
                        <option value="percent">Yüzde (%)</option>
                        <option value="fixed">Sabit Fiyat (₺)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1" x-text="bulkPriceType === 'percent' ? 'Yüzde (+ artış, - azalış)' : 'Yeni Fiyat (₺)'"></label>
                    <input type="number" x-model="bulkPriceValue" step="0.01" class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl" placeholder="10">
                </div>
            </div>
            <div class="flex gap-3">
                <button @click="showBulkPriceModal=false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl">İptal</button>
                <button @click="bulkPriceUpdate()" :disabled="!bulkPriceValue" class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-amber-500 hover:bg-amber-600 rounded-xl disabled:opacity-50">Uygula</button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function categoryPicker() {
    return {
        catOpen: false,
        catSearch: '',
        catTree: [],
        expandedIds: new Set(),
        selectedCatName: '',
        addingCat: false,
        addingParent: null,
        newCatName: '',
        savingCat: false,

        async initCategoryTree() {
            try {
                const res = await fetch('{{ route("pos.categories.tree") }}');
                this.catTree = await res.json();
                // Eğer form'da seçili kategori varsa ismini bul
                if (this.form?.category_id) {
                    this.selectedCatName = this.findName(this.catTree, this.form.category_id);
                    this.expandToId(this.catTree, this.form.category_id);
                }
            } catch(e) { console.error('Kategori ağacı yüklenemedi', e); }

            this.$watch('form.category_id', (val) => {
                if (val) {
                    this.selectedCatName = this.findName(this.catTree, val);
                } else {
                    this.selectedCatName = '';
                }
            });
        },

        findName(nodes, id) {
            for (const n of nodes) {
                if (n.id == id) return n.name;
                if (n.children?.length) {
                    const found = this.findName(n.children, id);
                    if (found) return found;
                }
            }
            return '';
        },

        expandToId(nodes, id, path = []) {
            for (const n of nodes) {
                if (n.id == id) {
                    path.forEach(p => this.expandedIds.add(p));
                    return true;
                }
                if (n.children?.length) {
                    if (this.expandToId(n.children, id, [...path, n.id])) return true;
                }
            }
            return false;
        },

        filteredTree() {
            if (!this.catSearch.trim()) return this.catTree;
            return this.filterNodes(this.catTree, this.catSearch.toLowerCase());
        },

        filterNodes(nodes, q) {
            const result = [];
            for (const n of nodes) {
                const match = n.name.toLowerCase().includes(q);
                const childMatch = n.children ? this.filterNodes(n.children, q) : [];
                if (match || childMatch.length) {
                    result.push({ ...n, children: match ? n.children : childMatch });
                    if (childMatch.length) this.expandedIds.add(n.id);
                }
            }
            return result;
        },

        toggleExpand(id) {
            if (this.expandedIds.has(id)) this.expandedIds.delete(id);
            else this.expandedIds.add(id);
        },

        isExpanded(id) { return this.expandedIds.has(id); },

        selectCat(cat) {
            this.form.category_id = String(cat.id);
            this.selectedCatName = cat.name;
            this.catOpen = false;
        },

        clearCat() {
            this.form.category_id = '';
            this.selectedCatName = '';
        },

        startAddSub(parent) {
            this.addingCat = true;
            this.addingParent = parent;
            this.newCatName = '';
            this.$nextTick(() => {
                if (this.$refs.newCatInput) this.$refs.newCatInput.focus();
            });
        },

        async saveCat() {
            if (!this.newCatName.trim() || this.savingCat) return;
            this.savingCat = true;
            try {
                const res = await posAjax('{{ route("pos.categories.store") }}', {
                    name: this.newCatName.trim(),
                    parent_id: this.addingParent?.id || null,
                });
                if (res.success) {
                    const newNode = { id: res.category.id, name: res.category.name, parent_id: res.category.parent_id, children: [] };
                    if (this.addingParent) {
                        const parent = this.findNode(this.catTree, this.addingParent.id);
                        if (parent) {
                            if (!parent.children) parent.children = [];
                            parent.children.push(newNode);
                            this.expandedIds.add(parent.id);
                        }
                    } else {
                        this.catTree.push(newNode);
                    }
                    // Otomatik seç
                    this.selectCat(newNode);
                    this.addingCat = false;
                    this.newCatName = '';
                    this.addingParent = null;
                    showToast('Kategori oluşturuldu!', 'success');
                }
            } catch(e) { showToast(e.message || 'Hata!', 'error'); }
            finally { this.savingCat = false; }
        },

        findNode(nodes, id) {
            for (const n of nodes) {
                if (n.id == id) return n;
                if (n.children?.length) {
                    const found = this.findNode(n.children, id);
                    if (found) return found;
                }
            }
            return null;
        },
    };
}

function productManager() {
    return {
        // Temel State
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
        firmFilter: new URLSearchParams(window.location.search).get('firm_id') || '',
        variantFilter: new URLSearchParams(window.location.search).get('variant_type_id') || '',
        sortBy: new URLSearchParams(window.location.search).get('sort_by') || 'name',
        selectedIds: [],
        selectedTemplateId: '',
        showSaveTemplateModal: false,
        templateName: '',

        // Filtreler
        filters: {
            low_stock: new URLSearchParams(window.location.search).get('low_stock') === '1',
            is_service: new URLSearchParams(window.location.search).get('is_service') === '1',
            has_variant: new URLSearchParams(window.location.search).get('has_variant') === '1',
            show_on_pos_only: new URLSearchParams(window.location.search).get('show_on_pos_only') === '1',
            stock_status: new URLSearchParams(window.location.search).get('stock_status') || '',
        },

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

        // Şube
        showBranchSection: true,
        branchData: [],
        branchDataLoading: false,

        // Görsel
        imagePreview: null,
        imageFile: null,

        // Varyant - Ürün
        showProductVariantModal: false,
        variantProductId: null,
        variantProductName: '',
        allVariantTypes: @json($variantTypes ?? []),
        selectedVariantValueIds: [],
        variantLoading: false,
        variantSaving: false,

        // Varyant Tipleri Yönetimi
        showVariantTypeModal: false,
        vtList: [],
        vtLoading: false,
        newVtName: '',

        // Alt Ürünler
        showSubDefModal: false,
        subDefProductId: null,
        subDefProductName: '',
        subDefList: [],
        subDefLoading: false,
        subDefSearch: '',
        subDefSearchResults: [],
        selectedSubDefProduct: null,
        newSubDefMultiplier: '',
        newSubDefApplyBranches: false,

        // Barkod Etiket
        showLabelModal: false,
        labelQty: 1,
        labelData: [],
        labelLoading: false,

        // Ürün Özet
        showSummaryModal: false,
        summaryData: null,
        summaryLoading: false,

        // Excel İçe Aktarma
        showImportModal: false,
        importLoading: false,
        importResult: null,

        // Toplu Kategori
        showBulkCategoryModal: false,
        bulkCategoryId: '',

        // Toplu Fiyat
        showBulkPriceModal: false,
        bulkPriceField: 'sale_price',
        bulkPriceType: 'percent',
        bulkPriceValue: '',

        form: {
            name: '', barcode: '', stock_code: '', category_id: '', firm_id: '',
            purchase_price: '', sale_price: '', vat_rate: '10',
            stock_quantity: '', critical_stock: '', unit: 'Adet',
            country_of_origin: '', description: '',
            is_active: true, show_on_pos: true, is_service: false,
        },

        // ── Helpers ──────────────────────────────
        formatMoney(val) {
            return parseFloat(val || 0).toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ₺';
        },

        resetForm() {
            this.form = {
                name: '', barcode: '', stock_code: '', category_id: '', firm_id: '',
                purchase_price: '', sale_price: '', vat_rate: '10',
                stock_quantity: '', critical_stock: '', unit: 'Adet',
                country_of_origin: '', description: '',
                is_active: true, show_on_pos: true, is_service: false,
            };
            this.editingId = null;
            this.profitRate = null;
            this.profitAmount = null;
            this.imagePreview = null;
            this.imageFile = null;
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
                stock_code: product.stock_code || '',
                category_id: product.category_id ? String(product.category_id) : '',
                firm_id: product.firm_id ? String(product.firm_id) : '',
                purchase_price: product.purchase_price ?? '',
                sale_price: product.sale_price ?? '',
                vat_rate: product.vat_rate != null ? String(product.vat_rate) : '10',
                stock_quantity: product.stock_quantity ?? '',
                critical_stock: product.critical_stock ?? '',
                unit: product.unit || 'Adet',
                country_of_origin: product.country_of_origin || '',
                description: product.description || '',
                is_active: product.is_active ? true : false,
                show_on_pos: product.show_on_pos !== undefined ? (product.show_on_pos ? true : false) : true,
                is_service: product.is_service ? true : false,
            };
            this.imagePreview = product.image_url ? '/storage/' + product.image_url : null;
            this.imageFile = null;
            this.calcProfit();
            this.showPanel = true;
            this.loadBranches(product.id);
        },

        closePanel() {
            this.showPanel = false;
            setTimeout(() => this.resetForm(), 300);
        },

        handleImageSelect(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) { showToast('Görsel 2MB\'den büyük olamaz', 'error'); return; }
            this.imageFile = file;
            this.imagePreview = URL.createObjectURL(file);
        },

        calcProfit() {
            const buy = parseFloat(this.form.purchase_price);
            const sell = parseFloat(this.form.sale_price);
            if (buy > 0 && sell > 0) {
                this.profitRate = (((sell - buy) / buy) * 100).toFixed(2);
                this.profitAmount = (sell - buy).toFixed(2);
            } else { this.profitRate = null; this.profitAmount = null; }
        },

        // ── Filtre & Sıralama ───────────────────
        applyFilters() {
            const params = new URLSearchParams();
            if (this.searchQuery) params.set('search', this.searchQuery);
            if (this.categoryFilter) params.set('category_id', this.categoryFilter);
            if (this.firmFilter) params.set('firm_id', this.firmFilter);
            if (this.variantFilter) params.set('variant_type_id', this.variantFilter);
            if (this.sortBy !== 'name') params.set('sort_by', this.sortBy);
            if (this.filters.low_stock) params.set('low_stock', '1');
            if (this.filters.is_service) params.set('is_service', '1');
            if (this.filters.has_variant) params.set('has_variant', '1');
            if (this.filters.show_on_pos_only) params.set('show_on_pos_only', '1');
            if (this.filters.stock_status) params.set('stock_status', this.filters.stock_status);
            window.location.href = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        },

        hasActiveFilters() {
            return this.searchQuery || this.firmFilter || this.categoryFilter || this.variantFilter ||
                   this.filters.low_stock || this.filters.is_service || this.filters.has_variant ||
                   this.filters.show_on_pos_only || this.filters.stock_status;
        },

        clearAllFilters() {
            this.searchQuery = '';
            this.categoryFilter = '';
            this.firmFilter = '';
            this.variantFilter = '';
            this.sortBy = 'name';
            this.filters = { low_stock: false, is_service: false, has_variant: false, show_on_pos_only: false, stock_status: '' };
            this.selectedTemplateId = '';
            this.applyFilters();
        },

        // ── Şablon Yönetimi ─────────────────────
        getCurrentFilters() {
            return {
                search: this.searchQuery,
                category_id: this.categoryFilter,
                firm_id: this.firmFilter,
                variant_type_id: this.variantFilter,
                sort_by: this.sortBy,
                low_stock: this.filters.low_stock,
                is_service: this.filters.is_service,
                has_variant: this.filters.has_variant,
                show_on_pos_only: this.filters.show_on_pos_only,
                stock_status: this.filters.stock_status,
            };
        },

        async saveTemplate() {
            if (!this.templateName.trim()) { showToast('Şablon adı girin', 'warning'); return; }
            try {
                const res = await posAjax('{{ route("pos.products.filter-templates.store") }}', {
                    name: this.templateName.trim(),
                    filters: this.getCurrentFilters(),
                });
                if (res.success) {
                    showToast('Şablon kaydedildi!', 'success');
                    this.showSaveTemplateModal = false;
                    this.templateName = '';
                    window.location.reload();
                }
            } catch(e) { showToast(e.message || 'Hata', 'error'); }
        },

        loadTemplate() {
            if (!this.selectedTemplateId) return;
            const select = document.querySelector('select[x-model=selectedTemplateId]');
            const option = select?.querySelector(`option[value="${this.selectedTemplateId}"]`);
            if (!option) return;
            try {
                const f = JSON.parse(option.dataset.filters);
                this.searchQuery = f.search || '';
                this.categoryFilter = f.category_id || '';
                this.firmFilter = f.firm_id || '';
                this.variantFilter = f.variant_type_id || '';
                this.sortBy = f.sort_by || 'name';
                this.filters.low_stock = !!f.low_stock;
                this.filters.is_service = !!f.is_service;
                this.filters.has_variant = !!f.has_variant;
                this.filters.show_on_pos_only = !!f.show_on_pos_only;
                this.filters.stock_status = f.stock_status || '';
                this.applyFilters();
            } catch(e) { console.error('Şablon parse hatası', e); }
        },

        async deleteTemplate() {
            if (!this.selectedTemplateId || !confirm('Bu şablonu silmek istediğinize emin misiniz?')) return;
            try {
                const res = await posAjax('/products/filter-templates/' + this.selectedTemplateId, {}, 'DELETE');
                if (res.success) {
                    showToast('Şablon silindi', 'success');
                    this.selectedTemplateId = '';
                    window.location.reload();
                }
            } catch(e) { showToast(e.message || 'Hata', 'error'); }
        },

        toggleSelectAll(event) {
            if (event.target.checked) {
                this.selectedIds = [...document.querySelectorAll('tbody input[type=checkbox]')].map(cb => cb.value);
            } else {
                this.selectedIds = [];
            }
        },

        // ── CRUD ─────────────────────────────────
        async submitForm() {
            if (!this.form.name || !this.form.sale_price) { showToast('Ürün adı ve satış fiyatı zorunludur.', 'error'); return; }
            this.saving = true;
            const url = this.editingId ? '/products/' + this.editingId : '/products';
            const method = this.editingId ? 'PUT' : 'POST';
            try {
                const response = await posAjax(url, {
                    method: method,
                    body: JSON.stringify({ ...this.form, is_active: this.form.is_active ? 1 : 0, show_on_pos: this.form.show_on_pos ? 1 : 0, is_service: this.form.is_service ? 1 : 0 }),
                });

                // Görsel yükle
                if (this.imageFile && response.product?.id) {
                    const fd = new FormData();
                    fd.append('image', this.imageFile);
                    await fetch('/products/' + response.product.id + '/image', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
                        body: fd,
                    });
                }

                // Şube kaydet
                if (this.editingId && this.branchData.length > 0) await this.saveBranches();

                showToast(response.message || (this.editingId ? 'Ürün güncellendi.' : 'Ürün oluşturuldu.'), 'success');
                this.closePanel();
                window.location.reload();
            } catch (error) {
                showToast(error.message || 'Bir hata oluştu.', 'error');
            } finally { this.saving = false; }
        },

        confirmDelete(id, name) { this.deleteId = id; this.deleteName = name; this.showDeleteModal = true; },

        async deleteProduct() {
            this.deleting = true;
            try {
                await posAjax('/products/' + this.deleteId, { method: 'DELETE' });
                showToast('Ürün silindi.', 'success');
                this.showDeleteModal = false;
                window.location.reload();
            } catch (error) { showToast(error.message || 'Silme başarısız.', 'error'); }
            finally { this.deleting = false; }
        },

        // ── Şube ─────────────────────────────────
        async loadBranches(productId) {
            this.branchDataLoading = true; this.branchData = [];
            try { const res = await posAjax('/products/' + productId + '/branches', {}, 'GET'); this.branchData = res.branches || []; }
            catch { this.branchData = []; } finally { this.branchDataLoading = false; }
        },

        async saveBranches() {
            if (!this.editingId || this.branchData.length === 0) return;
            try { await posAjax('/products/' + this.editingId + '/branches', { branches: this.branchData.map(b => ({ branch_id: b.branch_id, enabled: !!b.enabled, sale_price: Number(b.sale_price) || 0, stock_quantity: Number(b.stock_quantity) || 0 })) }, 'POST'); }
            catch { showToast('Şube bilgileri kaydedilemedi', 'error'); }
        },

        // ── Geçmiş ───────────────────────────────
        async openHistory(productId, productName) {
            this.historyProductName = productName; this.historyList = []; this.historyLoading = true; this.showHistoryModal = true;
            try { const res = await posAjax('/products/' + productId + '/history', {}, 'GET'); this.historyList = res.movements || []; }
            catch { showToast('Geçmiş yüklenemedi', 'error'); this.showHistoryModal = false; }
            finally { this.historyLoading = false; }
        },

        // ── Çoklu Fiyat ──────────────────────────
        async openPrices(productId, productName) {
            this.pricesProductId = productId; this.pricesProductName = productName;
            this.pricesList = []; this.pricesLoading = true; this.showPricesModal = true;
            this.newPriceLabel = ''; this.newPriceValue = ''; this.editingPriceId = null;
            try { const res = await posAjax('/products/' + productId + '/prices', {}, 'GET'); this.pricesList = res.prices || []; }
            catch { showToast('Fiyatlar yüklenemedi', 'error'); this.showPricesModal = false; }
            finally { this.pricesLoading = false; }
        },

        async addPrice() {
            if (!this.newPriceLabel.trim() || !this.newPriceValue) return;
            try {
                const res = await posAjax('/products/' + this.pricesProductId + '/prices', { method: 'POST', body: JSON.stringify({ label: this.newPriceLabel, price: this.newPriceValue }) });
                if (res.success) { this.pricesList.push(res.price); this.newPriceLabel = ''; this.newPriceValue = ''; showToast('Fiyat eklendi', 'success'); }
            } catch(e) { showToast(e.message || 'Fiyat eklenemedi', 'error'); }
        },

        async updatePrice(priceId, idx) {
            try {
                const res = await posAjax('/products/' + this.pricesProductId + '/prices/' + priceId, { method: 'PUT', body: JSON.stringify({ label: this.editPriceLabel, price: this.editPriceValue }) });
                if (res.success) { this.pricesList[idx] = res.price; this.editingPriceId = null; showToast('Fiyat güncellendi', 'success'); }
            } catch(e) { showToast(e.message || 'Güncellenemedi', 'error'); }
        },

        async deletePrice(priceId, idx) {
            if (!confirm('Bu fiyatı silmek istediğinize emin misiniz?')) return;
            try {
                const res = await posAjax('/products/' + this.pricesProductId + '/prices/' + priceId, { method: 'DELETE' });
                if (res.success) { this.pricesList.splice(idx, 1); showToast('Fiyat silindi', 'success'); }
            } catch(e) { showToast(e.message || 'Silinemedi', 'error'); }
        },

        // ── Ürün Varyantları ─────────────────────
        async openProductVariants(productId, productName) {
            this.variantProductId = productId; this.variantProductName = productName;
            this.selectedVariantValueIds = []; this.variantLoading = true; this.showProductVariantModal = true;
            try {
                const res = await posAjax('/products/' + productId + '/variants', {}, 'GET');
                this.selectedVariantValueIds = (res.variants || []).map(v => v.id);
            } catch { /* boş bırak */ }
            finally { this.variantLoading = false; }
        },

        toggleVariantValue(valId) {
            const idx = this.selectedVariantValueIds.indexOf(valId);
            if (idx >= 0) this.selectedVariantValueIds.splice(idx, 1);
            else this.selectedVariantValueIds.push(valId);
        },

        async saveProductVariants() {
            this.variantSaving = true;
            try {
                await posAjax('/products/' + this.variantProductId + '/variants', { method: 'POST', body: JSON.stringify({ variant_value_ids: this.selectedVariantValueIds }) });
                showToast('Varyantlar kaydedildi', 'success');
                this.showProductVariantModal = false;
            } catch(e) { showToast(e.message || 'Hata', 'error'); }
            finally { this.variantSaving = false; }
        },

        // ── Varyant Tipleri Yönetimi ─────────────
        async init() {
            // Watch for variant type modal open
            this.$watch('showVariantTypeModal', async (val) => {
                if (val) { await this.loadVariantTypes(); }
            });
        },

        async loadVariantTypes() {
            this.vtLoading = true;
            try { const res = await posAjax('/product-variants', {}, 'GET'); this.vtList = (res.types || []).map(t => ({...t, _newValue: ''})); }
            catch { this.vtList = []; } finally { this.vtLoading = false; }
        },

        async addVariantType() {
            if (!this.newVtName.trim()) return;
            try {
                const res = await posAjax('/product-variants', { method: 'POST', body: JSON.stringify({ name: this.newVtName }) });
                if (res.success) { this.vtList.push({...res.type, _newValue: ''}); this.newVtName = ''; this.allVariantTypes = [...this.vtList]; showToast('Varyant tipi oluşturuldu', 'success'); }
            } catch(e) { showToast(e.message || 'Hata', 'error'); }
        },

        async deleteVariantType(id) {
            if (!confirm('Bu varyant tipini ve tüm değerlerini silmek istediğinize emin misiniz?')) return;
            try {
                await posAjax('/product-variants/' + id, { method: 'DELETE' });
                this.vtList = this.vtList.filter(t => t.id !== id);
                this.allVariantTypes = [...this.vtList];
                showToast('Varyant tipi silindi', 'success');
            } catch(e) { showToast(e.message || 'Hata', 'error'); }
        },

        async addVariantValue(vt) {
            if (!vt._newValue?.trim()) return;
            try {
                const res = await posAjax('/product-variants/' + vt.id + '/values', { method: 'POST', body: JSON.stringify({ value: vt._newValue }) });
                if (res.success) { vt.values.push(res.value); vt._newValue = ''; this.allVariantTypes = [...this.vtList]; showToast('Değer eklendi', 'success'); }
            } catch(e) { showToast(e.message || 'Hata', 'error'); }
        },

        async deleteVariantValue(valId, vt) {
            try {
                await posAjax('/product-variant-values/' + valId, { method: 'DELETE' });
                vt.values = vt.values.filter(v => v.id !== valId);
                this.allVariantTypes = [...this.vtList];
                showToast('Değer silindi', 'success');
            } catch(e) { showToast(e.message || 'Hata', 'error'); }
        },

        // ── Alt Ürün Tanımları ───────────────────
        async openSubDefs(productId, productName) {
            this.subDefProductId = productId; this.subDefProductName = productName;
            this.subDefList = []; this.subDefLoading = true; this.showSubDefModal = true;
            this.subDefSearch = ''; this.subDefSearchResults = [];
            this.selectedSubDefProduct = null; this.newSubDefMultiplier = ''; this.newSubDefApplyBranches = false;
            try {
                const res = await posAjax('/products/' + productId + '/sub-definitions', {}, 'GET');
                this.subDefList = res.definitions || [];
            } catch { /* boş */ }
            finally { this.subDefLoading = false; }
        },

        async searchSubDefProducts() {
            if (this.subDefSearch.length < 2) { this.subDefSearchResults = []; return; }
            try {
                const res = await posAjax('/products?search=' + encodeURIComponent(this.subDefSearch), {}, 'GET');
                // index() sayfadan dönen HTML yerine, AJAX'sa JSON:
                // search result'ı doğrudan products endpoint'inden al
                const doc = new DOMParser().parseFromString(await (await fetch('/products?search=' + encodeURIComponent(this.subDefSearch))).text(), 'text/html');
                // Alternatif: basit ayrıştırma
                this.subDefSearchResults = [];
            } catch { this.subDefSearchResults = []; }
            // Daha basit yöntem: mevcut sayfadaki ürünlerden filtrele
            this.subDefSearchResults = @json($products->items()).filter(p =>
                p.id !== this.subDefProductId &&
                (p.name.toLowerCase().includes(this.subDefSearch.toLowerCase()) || (p.barcode || '').includes(this.subDefSearch))
            ).slice(0, 8);
        },

        selectSubDefProduct(product) {
            this.selectedSubDefProduct = product;
            this.subDefSearch = product.name;
            this.subDefSearchResults = [];
        },

        async addSubDef() {
            if (!this.selectedSubDefProduct || !this.newSubDefMultiplier) return;
            try {
                const res = await posAjax('/products/' + this.subDefProductId + '/sub-definitions', {
                    method: 'POST',
                    body: JSON.stringify({ sub_product_id: this.selectedSubDefProduct.id, multiplier: this.newSubDefMultiplier, apply_to_branches: this.newSubDefApplyBranches ? 1 : 0 }),
                });
                if (res.success) {
                    this.subDefList.push(res.definition);
                    this.selectedSubDefProduct = null; this.subDefSearch = ''; this.newSubDefMultiplier = '';
                    showToast('Alt ürün tanımı eklendi', 'success');
                }
            } catch(e) { showToast(e.message || 'Hata', 'error'); }
        },

        async deleteSubDef(defId, idx) {
            if (!confirm('Bu alt ürün tanımını silmek istediğinize emin misiniz?')) return;
            try {
                await posAjax('/products/' + this.subDefProductId + '/sub-definitions/' + defId, { method: 'DELETE' });
                this.subDefList.splice(idx, 1);
                showToast('Alt ürün tanımı silindi', 'success');
            } catch(e) { showToast(e.message || 'Hata', 'error'); }
        },

        // ── Toplu İşlemler ───────────────────────
        async bulkDeleteProducts() {
            if (!confirm(this.selectedIds.length + ' ürünü silmek istediğinize emin misiniz?')) return;
            try {
                const res = await posAjax('/products/bulk-delete', { method: 'POST', body: JSON.stringify({ ids: this.selectedIds.map(Number) }) });
                showToast(res.message, 'success');
                window.location.reload();
            } catch(e) { showToast(e.message || 'Hata', 'error'); }
        },

        async bulkAssignCategory() {
            if (!this.bulkCategoryId) return;
            try {
                const res = await posAjax('/products/bulk-assign-category', { method: 'POST', body: JSON.stringify({ ids: this.selectedIds.map(Number), category_id: Number(this.bulkCategoryId) }) });
                showToast(res.message, 'success');
                this.showBulkCategoryModal = false;
                window.location.reload();
            } catch(e) { showToast(e.message || 'Hata', 'error'); }
        },

        async bulkPriceUpdate() {
            if (!this.bulkPriceValue) return;
            try {
                const res = await posAjax('/products/bulk-price-update', {
                    method: 'POST',
                    body: JSON.stringify({ ids: this.selectedIds.map(Number), type: this.bulkPriceType, value: Number(this.bulkPriceValue), field: this.bulkPriceField }),
                });
                showToast(res.message, 'success');
                this.showBulkPriceModal = false;
                window.location.reload();
            } catch(e) { showToast(e.message || 'Hata', 'error'); }
        },

        // ── Barkod Etiket ────────────────────────
        openLabelModal() { this.showLabelModal = true; this.labelData = []; },

        async generateLabels() {
            this.labelLoading = true;
            try {
                const res = await posAjax('/products-labels', { method: 'POST', body: JSON.stringify({ ids: this.selectedIds.map(Number), quantity: Number(this.labelQty) }) });
                this.labelData = res.labels || [];
            } catch(e) { showToast(e.message || 'Hata', 'error'); }
            finally { this.labelLoading = false; }
        },

        printLabels() {
            const w = window.open('', '_blank');
            let html = '<html><head><title>Barkod Etiketleri</title>' +
                '<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\/script>' +
                '<style>body{font-family:sans-serif;margin:0;padding:10px}' +
                '.label{display:inline-block;width:200px;border:1px solid #ccc;padding:8px;margin:4px;text-align:center;page-break-inside:avoid}' +
                '.label .name{font-size:11px;font-weight:600;margin-bottom:4px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}' +
                '.label svg{display:block;margin:4px auto}' +
                '.label .barcode-text{font-size:12px;font-family:monospace;letter-spacing:1px;margin:2px 0}' +
                '.label .price{font-size:14px;font-weight:700;color:#333}@media print{body{margin:0}}</style></head><body>';
            this.labelData.forEach((l, i) => {
                const barcodeVal = l.barcode || '';
                if (barcodeVal) {
                    html += '<div class="label"><div class="name">' + l.name + '</div>' +
                        '<svg id="bc' + i + '"></svg>' +
                        '<div class="price">' + l.price + '</div></div>';
                } else {
                    html += '<div class="label"><div class="name">' + l.name + '</div>' +
                        '<div class="barcode-text">Barkod yok</div>' +
                        '<div class="price">' + l.price + '</div></div>';
                }
            });
            html += '<script>' +
                'document.addEventListener("DOMContentLoaded", function(){' +
                'var labels = ' + JSON.stringify(this.labelData.map(l => l.barcode || '')) + ';' +
                'labels.forEach(function(code, i){' +
                'if(code && document.getElementById("bc"+i)){' +
                'try{JsBarcode("#bc"+i, code, {width:1.5,height:40,fontSize:12,margin:2,displayValue:true});}catch(e){' +
                'document.getElementById("bc"+i).outerHTML="<div style=\\"font-family:monospace;font-size:14px;letter-spacing:2px;margin:4px 0\\">"+code+"</div>";}' +
                '}});' +
                'setTimeout(function(){window.print();},300);' +
                '});<\/script></body></html>';
            w.document.write(html);
            w.document.close();
        },

        // ── Ürün Özet Dökümü ─────────────────────
        async openSummaryModal() {
            this.showSummaryModal = true; this.summaryLoading = true; this.summaryData = null;
            try {
                const res = await posAjax('/products-summary' + (this.categoryFilter ? '?category_id=' + this.categoryFilter : ''), {}, 'GET');
                this.summaryData = res.summary;
            } catch(e) { showToast(e.message || 'Hata', 'error'); }
            finally { this.summaryLoading = false; }
        },

        printSummary() {
            const w = window.open('', '_blank');
            let html = '<html><head><title>Ürün Özet Dökümü</title><style>body{font-family:sans-serif;margin:20px;font-size:12px}' +
                'table{width:100%;border-collapse:collapse;margin-top:20px}th,td{border:1px solid #ddd;padding:4px 6px;text-align:left}' +
                'th{background:#f5f5f5;font-weight:600}.stat{display:inline-block;padding:10px 20px;margin:5px;border:1px solid #ddd;border-radius:8px;text-align:center}' +
                '.stat .val{font-size:18px;font-weight:700}.stat .lbl{font-size:10px;color:#666}@media print{body{margin:5mm}}</style></head><body>';
            html += '<h2>Ürün Özet Dökümü — ' + new Date().toLocaleDateString('tr-TR') + '</h2>';
            const s = this.summaryData;
            html += '<div><div class="stat"><div class="val">' + s.total_products + '</div><div class="lbl">Toplam Ürün</div></div>';
            html += '<div class="stat"><div class="val">' + this.formatMoney(s.total_stock_value) + '</div><div class="lbl">Stok Maliyeti</div></div>';
            html += '<div class="stat"><div class="val">' + this.formatMoney(s.total_sale_value) + '</div><div class="lbl">Satış Değeri</div></div>';
            html += '<div class="stat"><div class="val">' + s.low_stock_count + ' / ' + s.zero_stock_count + '</div><div class="lbl">Kritik / Sıfır</div></div></div>';
            html += '<table><thead><tr><th>Barkod</th><th>Stok Kodu</th><th>Ürün</th><th>Kategori</th><th>Alış</th><th>Satış</th><th>Stok</th><th>Stok Değeri</th></tr></thead><tbody>';
            (s.products || []).forEach(p => {
                html += '<tr><td>' + (p.barcode || '-') + '</td><td>' + (p.stock_code || '-') + '</td><td>' + p.name + '</td><td>' + (p.category || '-') + '</td><td style="text-align:right">' + parseFloat(p.purchase_price).toFixed(2) + '</td><td style="text-align:right">' + parseFloat(p.sale_price).toFixed(2) + '</td><td style="text-align:right">' + p.stock_quantity + '</td><td style="text-align:right">' + parseFloat(p.stock_value).toFixed(2) + ' ₺</td></tr>';
            });
            html += '</tbody></table></body></html>';
            w.document.write(html);
            w.document.close();
            setTimeout(() => w.print(), 200);
        },

        // ── Excel İçe Aktarma ────────────────────
        async importCsv() {
            const fileInput = this.$refs.importFile;
            if (!fileInput.files[0]) { showToast('Dosya seçin', 'error'); return; }
            this.importLoading = true; this.importResult = null;
            try {
                const fd = new FormData();
                fd.append('file', fileInput.files[0]);
                const res = await fetch('/products-import', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
                    body: fd,
                });
                this.importResult = await res.json();
                if (this.importResult.success) showToast(this.importResult.message, 'success');
            } catch(e) { showToast('İçe aktarma hatası', 'error'); }
            finally { this.importLoading = false; }
        },
    };
}
</script>
@endpush

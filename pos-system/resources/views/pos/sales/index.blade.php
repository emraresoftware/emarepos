@extends('pos.layouts.app')
@section('title', 'Hızlı Satış')

@section('content')
<div x-data="posScreen()" x-init="init()" class="flex-1 flex overflow-hidden">

    {{-- ─── Hızı Ürün Ekleme Modalı ─── --}}
    <div x-show="showProductModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
         @keydown.escape.window="showProductModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6" @click.stop>
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-semibold text-gray-900"><i class="fas fa-box mr-2 text-brand-500"></i>Hızı Ürün Ekle</h3>
                <button @click="showProductModal = false" class="text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Ürün Adı *</label>
                    <input type="text" x-model="productForm.name" @keydown.enter="saveQuickProduct()"
                           placeholder="Ürün adını girin..."
                           class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Satış Fiyatı *</label>
                        <input type="number" x-model="productForm.sale_price" min="0" step="0.01" placeholder="0.00"
                               class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Birim</label>
                        <select x-model="productForm.unit" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-brand-500">
                            <option value="Adet">Adet</option>
                            <option value="Kg">Kg</option>
                            <option value="Lt">Lt</option>
                            <option value="Porsiyon">Porsiyon</option>
                            <option value="Paket">Paket</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Kategori</label>
                    <select x-model="productForm.category_id" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-brand-500">
                        <option value="">Kategorisiz</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                        <template x-for="cat in dynamicCategories" :key="cat.id">
                            <option :value="cat.id" x-text="cat.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Barkod <span class="text-gray-400">(opsiyonel)</span></label>
                    <input type="text" x-model="productForm.barcode" placeholder="Barkod..."
                           class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                </div>
                <div class="flex gap-3 pt-2">
                    <button @click="showProductModal = false"
                            class="flex-1 px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">İptal</button>
                    <button @click="saveQuickProduct()" :disabled="!productForm.name.trim() || !productForm.sale_price || productSaving"
                            class="flex-1 px-4 py-2 text-sm text-white bg-gradient-to-r from-brand-500 to-purple-600 rounded-xl hover:opacity-90 disabled:opacity-50 font-medium flex items-center justify-center gap-2">
                        <i class="fas fa-spinner fa-spin" x-show="productSaving"></i>
                        <i class="fas fa-plus" x-show="!productSaving"></i>
                        <span x-text="productSaving ? 'Kaydediliyor...' : 'Kaydet & Ekle'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Hızı Kategori Ekleme Modalı ─── --}}
    <div x-show="showCatModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
         @keydown.escape.window="showCatModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6" @click.stop>
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-semibold text-gray-900">Yeni Kategori Ekle</h3>
                <button @click="showCatModal = false" class="text-gray-400 hover:text-red-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Kategori Adı *</label>
                    <input type="text" x-model="newCatName" @keydown.enter="saveCategory()"
                           placeholder="Örn: Ana Yemek, Atıştırmalık, İçecek..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                </div>
                <div class="flex gap-2 flex-wrap">
                    <template x-for="preset in catPresets" :key="preset">
                        <button @click="newCatName = preset"
                                class="px-3 py-1.5 bg-brand-50 hover:bg-brand-100 text-brand-700 text-xs rounded-lg font-medium transition-colors border border-brand-200"
                                x-text="preset"></button>
                    </template>
                </div>
                <div class="flex gap-3 pt-2">
                    <button @click="showCatModal = false"
                            class="flex-1 px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                        İptal
                    </button>
                    <button @click="saveCategory()" :disabled="!newCatName.trim()"
                            class="flex-1 px-4 py-2 text-sm text-white bg-gradient-to-r from-brand-500 to-purple-600 rounded-xl hover:opacity-90 transition-opacity disabled:opacity-50 font-medium">
                        <i class="fas fa-plus mr-1"></i> Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    {{-- SOL PANEL: Ürünler --}}
    <div class="flex-1 flex flex-col overflow-hidden border-r border-gray-200">
        {{-- Üst Bar: Arama + Kategori --}}
        <div class="p-3 bg-white border-b border-gray-200 space-y-2">
            <div class="flex items-center gap-2">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    <input type="text" x-model="searchQuery" @input.debounce.300ms="searchProducts()"
                           @keydown.enter="addByBarcode()"
                           placeholder="Barkod okutun veya ürün arayın..."
                           class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 text-sm transition-all"
                           x-ref="searchInput">
                </div>
                <button @click="showAllProducts()" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm transition-colors">
                    <i class="fas fa-th"></i>
                </button>
            </div>
            
            {{-- Kategoriler --}}
            <div class="flex gap-1.5 overflow-x-auto pb-1 items-center">
                <button @click="filterCategory(null)" 
                        class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-all"
                        :class="selectedCategory === null ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-white shadow-sm' : 'bg-gray-100 text-gray-500 hover:text-gray-700 hover:bg-gray-200'">
                    Tümü
                </button>
                @foreach($categories as $cat)
                <button @click="filterCategory({{ $cat->id }})" 
                        class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-all"
                        :class="selectedCategory === {{ $cat->id }} ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-white shadow-sm' : 'bg-gray-100 text-gray-500 hover:text-gray-700 hover:bg-gray-200'">
                    {{ $cat->name }}
                </button>
                @endforeach
                {{-- Kategorileri yeniden render etmek için dinamik kategoriler --}}
                <template x-for="cat in dynamicCategories" :key="cat.id">
                    <button @click="filterCategory(cat.id)"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-all"
                            :class="selectedCategory === cat.id ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-white shadow-sm' : 'bg-gray-100 text-gray-500 hover:text-gray-700 hover:bg-gray-200'"
                            x-text="cat.name"></button>
                </template>
                {{-- Yeni Kategori Ekle --}}
                <button @click="showCatModal = true"
                        class="px-2.5 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-all bg-green-50 text-green-600 hover:bg-green-100 border border-green-200 shrink-0"
                        title="Yeni kategori ekle">
                    <i class="fas fa-tag"></i>
                </button>
                {{-- Hızı Ürün Ekle --}}
                <button @click="openProductModal()"
                        class="px-2.5 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-all bg-brand-50 text-brand-600 hover:bg-brand-100 border border-brand-200 shrink-0"
                        title="Hızı ürün ekle">
                    <i class="fas fa-box mr-1"></i>+
                </button>
            </div>
        </div>

        {{-- Ürün Grid --}}
        <div class="flex-1 overflow-y-auto p-3 bg-gray-50">
            <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2">
                <template x-for="product in filteredProducts" :key="product.id">
                    <button @click="addToCart(product)"
                            class="bg-white border border-gray-100 rounded-xl p-3 text-left hover:border-brand-300 hover:shadow-md hover:shadow-brand-100/50 transition-all group">
                        <div class="text-sm font-medium text-gray-800 group-hover:text-brand-600 truncate" x-text="product.name"></div>
                        <div class="text-xs text-gray-400 mt-1" x-text="product.category || ''"></div>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-sm font-bold text-brand-600" x-text="formatCurrency(product.sale_price)"></span>
                            <span class="text-xs text-gray-400" x-show="!product.is_service" x-text="product.stock_quantity + ' ' + (product.unit || 'Adet')"></span>
                        </div>
                        <div x-show="product.barcode" class="text-[10px] text-gray-400 mt-1 truncate" x-text="product.barcode"></div>
                    </button>
                </template>
            </div>
            <div x-show="filteredProducts.length === 0 && !loading" class="flex flex-col items-center justify-center py-20 text-gray-400">
                <i class="fas fa-search text-4xl mb-3"></i>
                <p>Ürün bulunamadı</p>
            </div>
            <div x-show="loading" class="flex items-center justify-center py-20">
                <i class="fas fa-spinner fa-spin text-2xl text-brand-500"></i>
            </div>
        </div>
    </div>

    {{-- SAĞ PANEL: Sepet --}}
    <div class="w-96 flex flex-col bg-white">
        {{-- Müşteri Seçimi --}}
        <div class="p-3 border-b border-gray-200">
            <div class="relative">
                <div x-show="!selectedCustomer">
                    <input type="text" x-model="customerSearch"
                           @input.debounce.300ms="searchCustomers(customerSearch)"
                           @focus="showCustomerDropdown = true; searchCustomers(customerSearch)"
                           @keydown.escape="showCustomerDropdown = false"
                           placeholder="Müşteri seçin (opsiyonel)..."
                           class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 text-xs transition-all">
                </div>
                <div x-show="selectedCustomer" class="flex items-center gap-2 px-3 py-2 bg-brand-50 border border-brand-200 rounded-lg">
                    <i class="fas fa-user text-brand-500 text-xs"></i>
                    <span class="text-sm text-gray-800 flex-1" x-text="selectedCustomer?.name"></span>
                    <span class="text-xs" :class="(selectedCustomer?.balance ?? 0) < 0 ? 'text-red-500' : 'text-emerald-500'" x-text="formatCurrency(selectedCustomer?.balance ?? 0) + ' bakiye'"></span>
                    <button @click="selectedCustomer = null; customerSearch = ''; customerResults = []" class="text-gray-400 hover:text-red-500">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
                {{-- Müşteri Dropdown --}}
                <div x-show="showCustomerDropdown && customerResults.length > 0"
                     class="absolute z-20 top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-48 overflow-y-auto">
                    <template x-for="c in customerResults" :key="c.id">
                        <button @click="selectedCustomer = c; showCustomerDropdown = false; customerSearch = ''; customerResults = []"
                                class="w-full text-left px-3 py-2 hover:bg-gray-50 text-sm flex items-center justify-between transition-colors border-b border-gray-50 last:border-0">
                            <div>
                                <div class="text-gray-800 font-medium" x-text="c.name"></div>
                                <div class="text-xs text-gray-400" x-text="c.phone || c.email || ''"></div>
                            </div>
                            <span class="text-xs font-medium" :class="c.balance < 0 ? 'text-red-500' : 'text-emerald-500'" x-text="formatCurrency(c.balance)"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- Sepet Listesi --}}
        <div class="flex-1 overflow-y-auto">
            <template x-if="cart.length === 0">
                <div class="flex flex-col items-center justify-center h-full text-gray-400 p-8">
                    <i class="fas fa-shopping-cart text-4xl mb-3"></i>
                    <p class="text-sm">Sepet boş</p>
                    <p class="text-xs mt-1">Ürünlere tıklayarak ekleyin</p>
                </div>
            </template>
            
            <div class="divide-y divide-gray-100">
                <template x-for="(item, index) in cart" :key="index">
                    <div class="p-3 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-800 truncate" x-text="item.product_name"></div>
                                <div class="text-xs text-gray-400" x-text="formatCurrency(item.unit_price) + ' x ' + item.quantity"></div>
                            </div>
                            <div class="text-sm font-bold text-gray-900" x-text="formatCurrency(item.total)"></div>
                        </div>
                        
                        <div class="flex items-center gap-2 mt-2">
                            {{-- Quantity Controls --}}
                            <div class="flex items-center bg-gray-100 rounded-lg">
                                <button @click="updateQty(index, -1)" class="px-2 py-1 text-gray-400 hover:text-gray-700">
                                    <i class="fas fa-minus text-xs"></i>
                                </button>
                                <input type="number" x-model.number="item.quantity" @change="recalcItem(index)"
                                       class="w-12 text-center bg-transparent text-gray-800 text-sm border-0 focus:outline-none"
                                       min="0.01" step="1">
                                <button @click="updateQty(index, 1)" class="px-2 py-1 text-gray-400 hover:text-gray-700">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                            </div>
                            
                            {{-- Item Discount --}}
                            <button @click="item.showDiscount = !item.showDiscount" 
                                    class="px-2 py-1 text-xs rounded"
                                    :class="item.discount > 0 ? 'bg-amber-100 text-amber-600' : 'text-gray-400 hover:text-gray-600'">
                                <i class="fas fa-percent"></i>
                            </button>
                            
                            {{-- Remove --}}
                            <button @click="removeFromCart(index)" class="px-2 py-1 text-gray-400 hover:text-red-500">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                        
                        {{-- Discount Input --}}
                        <div x-show="item.showDiscount" x-transition class="mt-2 flex items-center gap-2">
                            <span class="text-xs text-gray-500">İndirim:</span>
                            <input type="number" x-model.number="item.discount" @input="recalcItem(index)"
                                   class="w-20 px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800 text-xs focus:outline-none focus:border-brand-500" 
                                   min="0" step="0.01" placeholder="₺0">
                            <span class="text-xs text-gray-500">₺</span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Alt Toplam --}}
        <div class="border-t border-gray-200 p-3 space-y-2 bg-gray-50/80">
            {{-- Genel İndirim --}}
            <div class="flex items-center justify-between">
                <button @click="showGeneralDiscount = !showGeneralDiscount" class="text-xs text-gray-500 hover:text-gray-700">
                    <i class="fas fa-percent mr-1"></i> Genel İndirim
                </button>
                <div x-show="showGeneralDiscount" class="flex items-center gap-1">
                    <input type="number" x-model.number="generalDiscount" @input="recalcTotals()"
                           class="w-20 px-2 py-1 bg-white border border-gray-200 rounded text-gray-800 text-xs focus:outline-none focus:border-brand-500" 
                           min="0" step="0.01">
                    <span class="text-xs text-gray-500">₺</span>
                </div>
            </div>

            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">Ara Toplam</span>
                <span class="text-gray-800" x-text="formatCurrency(totals.subtotal)"></span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">KDV</span>
                <span class="text-gray-800" x-text="formatCurrency(totals.vat_total)"></span>
            </div>
            <div x-show="totals.discount_total > 0" class="flex items-center justify-between text-sm">
                <span class="text-amber-600">İndirim</span>
                <span class="text-amber-600" x-text="'-' + formatCurrency(totals.discount_total)"></span>
            </div>
            
            <div class="flex items-center justify-between text-lg font-bold pt-2 border-t border-gray-200">
                <span class="text-gray-900">TOPLAM</span>
                <span class="text-brand-600" x-text="formatCurrency(totals.grand_total)"></span>
            </div>
        </div>

        {{-- Ödeme Butonları --}}
        <div class="p-3 border-t border-gray-200 space-y-2">
            <div class="grid grid-cols-3 gap-2">
                <button @click="processPayment('cash')" :disabled="cart.length === 0"
                        class="py-3 bg-gradient-to-r from-emerald-500 to-teal-500 hover:shadow-lg hover:shadow-emerald-200 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl text-sm font-semibold text-white transition-all">
                    <i class="fas fa-money-bill-wave block text-lg mb-1"></i>
                    Nakit
                </button>
                <button @click="processPayment('card')" :disabled="cart.length === 0"
                        class="py-3 bg-gradient-to-r from-purple-500 to-violet-500 hover:shadow-lg hover:shadow-purple-200 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl text-sm font-semibold text-white transition-all">
                    <i class="fas fa-credit-card block text-lg mb-1"></i>
                    Kart
                </button>
                <button @click="showMixedPayment = true; mixedRemaining = totals.grand_total" :disabled="cart.length === 0"
                        class="py-3 bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl text-sm font-semibold text-white transition-all">
                    <i class="fas fa-layer-group block text-lg mb-1"></i>
                    Parçalı Ödeme
                </button>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <button @click="processPayment('credit')" :disabled="cart.length === 0 || !selectedCustomer"
                        class="py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:shadow-lg hover:shadow-amber-200 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl text-sm font-semibold text-white transition-all">
                    <i class="fas fa-file-invoice-dollar mr-1"></i> Veresiye
                </button>
                <button @click="clearCart()" :disabled="cart.length === 0"
                        class="py-2.5 bg-red-50 hover:bg-red-100 text-red-500 border border-red-200 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl text-sm font-semibold transition-all">
                    <i class="fas fa-trash mr-1"></i> Temizle
                </button>
            </div>
        </div>
    </div>

    {{-- Parçalı (Karışık) Ödeme Modal --}}
    <div x-show="showMixedPayment" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl border border-gray-200 p-6 w-[440px] shadow-2xl" @click.away="showMixedPayment = false">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-gray-900"><i class="fas fa-layer-group mr-2 text-brand-500"></i>Parçalı Ödeme</h3>
                <button @click="showMixedPayment = false" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times"></i></button>
            </div>

            {{-- Toplam & Kalan --}}
            <div class="grid grid-cols-2 gap-3 mb-5">
                <div class="bg-gray-50 rounded-xl p-3 text-center">
                    <div class="text-xs text-gray-500 mb-1">Toplam Tutar</div>
                    <div class="text-xl font-bold text-gray-900" x-text="formatCurrency(totals.grand_total)"></div>
                </div>
                <div class="rounded-xl p-3 text-center border-2"
                     :class="mixedRemaining < -0.01 ? 'border-red-400 bg-red-50' : mixedRemaining < 0.01 ? 'border-emerald-400 bg-emerald-50' : 'border-amber-300 bg-amber-50'">
                    <div class="text-xs mb-1"
                         :class="mixedRemaining < -0.01 ? 'text-red-500' : mixedRemaining < 0.01 ? 'text-emerald-600' : 'text-amber-600'"
                         x-text="mixedRemaining < -0.01 ? 'Fazla Girilen' : mixedRemaining < 0.01 ? '✓ Tamamlandı' : 'Kalan Tutar'"></div>
                    <div class="text-xl font-bold"
                         :class="mixedRemaining < -0.01 ? 'text-red-600' : mixedRemaining < 0.01 ? 'text-emerald-600' : 'text-amber-600'"
                         x-text="formatCurrency(Math.abs(mixedRemaining))"></div>
                </div>
            </div>

            {{-- Tutar Girişleri --}}
            <div class="space-y-3 mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center shrink-0">
                        <i class="fas fa-money-bill-wave text-emerald-600 text-sm"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-700 w-16">Nakit</span>
                    <input type="number" x-model.number="mixedCash" @input="recalcMixedRemaining()" min="0" step="0.01" placeholder="0,00"
                           class="flex-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400/20 focus:border-emerald-400">
                    <span class="text-sm text-gray-400 w-4">₺</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                        <i class="fas fa-credit-card text-blue-600 text-sm"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-700 w-16">Kart</span>
                    <input type="number" x-model.number="mixedCard" @input="recalcMixedRemaining()" min="0" step="0.01" placeholder="0,00"
                           class="flex-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400/20 focus:border-blue-400">
                    <span class="text-sm text-gray-400 w-4">₺</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center shrink-0">
                        <i class="fas fa-building-columns text-purple-600 text-sm"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-700 w-16">Havale</span>
                    <input type="number" x-model.number="mixedTransfer" @input="recalcMixedRemaining()" min="0" step="0.01" placeholder="0,00"
                           class="flex-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400/20 focus:border-purple-400">
                    <span class="text-sm text-gray-400 w-4">₺</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center shrink-0">
                        <i class="fas fa-user-clock text-amber-600 text-sm"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-700 w-16">Veresiye</span>
                    <input type="number" x-model.number="mixedCredit" @input="recalcMixedRemaining()" min="0" step="0.01" placeholder="0,00"
                           class="flex-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400/20 focus:border-amber-400">
                    <span class="text-sm text-gray-400 w-4">₺</span>
                </div>
            </div>

            {{-- Veresiye için müşteri --}}
            <div x-show="mixedCredit > 0" x-transition class="mb-4">
                <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider mb-2"><i class="fas fa-user mr-1"></i>Veresiye Müşterisi <span class="text-red-500">*</span></p>
                <div class="relative">
                    <input type="text" x-model="customerSearch"
                           @input.debounce.300ms="searchCustomers(customerSearch)"
                           @focus="searchCustomers(customerSearch)"
                           placeholder="Müşteri adı veya telefon..." 
                           class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400/20 focus:border-amber-400">
                    <div x-show="showCustomerDropdown" class="absolute z-10 left-0 right-0 mt-1 bg-white rounded-xl border border-gray-200 shadow-lg max-h-32 overflow-y-auto">
                        <template x-for="c in customerResults" :key="c.id">
                            <button @click="selectedCustomer = c; customerSearch = c.name; showCustomerDropdown = false"
                                    class="w-full text-left px-3 py-2 hover:bg-gray-50 text-sm border-b border-gray-50 last:border-0">
                                <span class="font-medium text-gray-900" x-text="c.name"></span>
                                <span class="text-xs text-gray-400 ml-2" x-text="c.phone || ''"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <div x-show="selectedCustomer" class="mt-2 flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">
                    <i class="fas fa-user-check text-amber-500 text-xs"></i>
                    <span class="text-sm text-amber-700 font-medium" x-text="selectedCustomer?.name"></span>
                    <button @click="selectedCustomer = null; customerSearch = ''" class="ml-auto text-amber-400 hover:text-red-500">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>

            {{-- Hızlı Doldur --}}
            <div class="flex gap-2 mb-4">
                <button @click="mixedCash = totals.grand_total; mixedCard = 0; mixedTransfer = 0; mixedCredit = 0; recalcMixedRemaining()"
                        class="flex-1 py-1.5 text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg border border-emerald-200 transition-colors">
                    <i class="fas fa-money-bill-wave mr-1"></i>Tümü Nakit
                </button>
                <button @click="mixedCard = totals.grand_total; mixedCash = 0; mixedTransfer = 0; mixedCredit = 0; recalcMixedRemaining()"
                        class="flex-1 py-1.5 text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg border border-blue-200 transition-colors">
                    <i class="fas fa-credit-card mr-1"></i>Tümü Kart
                </button>
                <button @click="mixedCredit = totals.grand_total; mixedCash = 0; mixedCard = 0; mixedTransfer = 0; recalcMixedRemaining()"
                        class="flex-1 py-1.5 text-xs bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg border border-amber-200 transition-colors">
                    <i class="fas fa-user-clock mr-1"></i>Tümü Veresiye
                </button>
            </div>

            <div class="flex gap-2">
                <button @click="showMixedPayment = false" class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-medium transition-colors">İptal</button>
                <button @click="processMixedPayment()"
                        :disabled="Math.abs(mixedRemaining) > 0.01 || (mixedCredit > 0 && !selectedCustomer)"
                        class="flex-1 py-2.5 bg-gradient-to-r from-brand-500 to-purple-600 text-white hover:shadow-lg hover:shadow-brand-200 rounded-xl text-sm font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-check-circle mr-1"></i>Ödemeyi Tamamla
                </button>
            </div>
            <p x-show="mixedCredit > 0 && !selectedCustomer" class="text-xs text-red-500 text-center mt-2">Veresiye için müşteri seçimi zorunludur.</p>
        </div>
    </div>

    {{-- Satış Tamamlandı Modal --}}
    <div x-show="showReceipt" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl border border-gray-200 p-6 w-96 shadow-2xl" @click.away="closeReceipt()">
            <div class="text-center mb-4">
                <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-check-circle text-emerald-500 text-3xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Satış Tamamlandı!</h3>
                <p class="text-sm text-gray-500 mt-1" x-text="lastSale?.receipt_no"></p>
            </div>
            <div class="bg-gray-50 rounded-xl p-4 space-y-2 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Toplam</span>
                    <span class="text-gray-900 font-bold" x-text="formatCurrency(lastSale?.grand_total || 0)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Ödeme</span>
                    <span class="text-gray-800 capitalize" x-text="lastSale?.payment_method"></span>
                </div>
            </div>
            <div class="flex gap-2">
                <button @click="printReceipt()" class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-medium transition-colors">
                    <i class="fas fa-print mr-1"></i> Yazdır
                </button>
                <button @click="closeReceipt()" class="flex-1 py-2.5 bg-gradient-to-r from-brand-500 to-purple-600 text-white hover:shadow-lg hover:shadow-brand-200 rounded-xl text-sm font-semibold transition-all">
                    <i class="fas fa-plus mr-1"></i> Yeni Satış
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function posScreen() {
    return {
        // State
        products: [],
        filteredProducts: [],
        cart: [],
        selectedCategory: null,
        searchQuery: '',
        selectedCustomer: null,
        customerResults: [],
        generalDiscount: 0,
        showGeneralDiscount: false,
        showMixedPayment: false,
        showReceipt: false,
        mixedCash: 0,
        mixedCard: 0,
        mixedCredit: 0,
        mixedTransfer: 0,
        mixedRemaining: 0,
        lastSale: null,
        loading: false,
        totals: { subtotal: 0, vat_total: 0, discount_total: 0, grand_total: 0 },
        // Kategori modal
        showCatModal: false,
        newCatName: '',
        dynamicCategories: [],
        catPresets: ['Ana Yemek', 'Çorba', 'Atıştırmalık', 'Tatlı', 'İçecek', 'Alkollü İçecek', 'Kahvaltı', 'Salata', 'Pizza', 'Burger', 'Sandviç', 'Sebze Yemeği'],
        // Müşteri arama
        customerSearch: '',
        showCustomerDropdown: false,
        // Hızlı ürün ekleme modal
        showProductModal: false,
        productSaving: false,
        productForm: { name: '', sale_price: '', category_id: '', barcode: '', unit: 'Adet' },

        init() {
            this.showAllProducts();
            this.$refs.searchInput?.focus();
            // Barkod okuyucu için keyboard shortcut
            window.addEventListener('keydown', (e) => {
                if (e.key === 'F2') { e.preventDefault(); this.$refs.searchInput?.focus(); }
                if (e.key === 'F5') { e.preventDefault(); this.processPayment('cash'); }
                if (e.key === 'F6') { e.preventDefault(); this.processPayment('card'); }
                if (e.key === 'Escape') { this.showMixedPayment = false; this.showReceipt = false; }
            });
        },

        async showAllProducts() {
            this.loading = true;
            try {
                const data = await posAjax('{{ route("pos.products.search") }}', {}, 'GET');
                this.products = data;
                this.applyFilter();
            } catch(e) { console.error(e); }
            this.loading = false;
        },

        async searchProducts() {
            if (!this.searchQuery.trim()) { this.applyFilter(); return; }
            this.loading = true;
            try {
                const params = new URLSearchParams({ q: this.searchQuery });
                if (this.selectedCategory) params.append('category_id', this.selectedCategory);
                const data = await posAjax('{{ route("pos.products.search") }}?' + params, {}, 'GET');
                this.products = data;
                this.filteredProducts = data;
            } catch(e) { console.error(e); }
            this.loading = false;
        },

        filterCategory(catId) {
            this.selectedCategory = catId;
            if (this.searchQuery) {
                this.searchProducts();
            } else {
                this.applyFilter();
            }
        },

        applyFilter() {
            if (this.selectedCategory) {
                this.filteredProducts = this.products.filter(p => p.category_id === this.selectedCategory);
            } else {
                this.filteredProducts = [...this.products];
            }
        },

        addByBarcode() {
            const barcode = this.searchQuery.trim();
            const product = this.products.find(p => p.barcode === barcode);
            if (product) {
                this.addToCart(product);
                this.searchQuery = '';
                this.showAllProducts();
            }
        },

        addToCart(product) {
            const existing = this.cart.find(i => i.product_id === product.id);
            if (existing) {
                existing.quantity++;
                this.recalcItem(this.cart.indexOf(existing));
            } else {
                this.cart.push({
                    product_id: product.id,
                    product_name: product.name,
                    barcode: product.barcode,
                    unit_price: product.sale_price,
                    quantity: 1,
                    discount: 0,
                    vat_rate: product.vat_rate || 20,
                    vat_amount: 0,
                    additional_tax_amount: 0,
                    total: product.sale_price,
                    showDiscount: false,
                });
            }
            this.recalcTotals();
        },

        updateQty(index, delta) {
            this.cart[index].quantity = Math.max(0.01, this.cart[index].quantity + delta);
            this.recalcItem(index);
        },

        recalcItem(index) {
            const item = this.cart[index];
            const lineTotal = (item.quantity * item.unit_price) - item.discount;
            item.vat_amount = Math.round(lineTotal * item.vat_rate / (100 + item.vat_rate) * 100) / 100;
            item.total = Math.round(lineTotal * 100) / 100;
            this.recalcTotals();
        },

        recalcTotals() {
            let subtotal = 0, vatTotal = 0, discountTotal = this.generalDiscount;
            this.cart.forEach(item => {
                subtotal += (item.total - item.vat_amount);
                vatTotal += item.vat_amount;
                discountTotal += item.discount;
            });
            this.totals = {
                subtotal: Math.round(subtotal * 100) / 100,
                vat_total: Math.round(vatTotal * 100) / 100,
                discount_total: Math.round(discountTotal * 100) / 100,
                grand_total: Math.round((subtotal + vatTotal - this.generalDiscount) * 100) / 100,
            };
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
            this.recalcTotals();
        },

        clearCart() {
            if (this.cart.length && !confirm('Sepeti temizlemek istediğinize emin misiniz?')) return;
            this.cart = [];
            this.selectedCustomer = null;
            this.generalDiscount = 0;
            this.recalcTotals();
        },

        async saveCategory() {
            const name = this.newCatName.trim();
            if (!name) return;
            try {
                const data = await posAjax('{{ route("pos.categories.store") }}', { name, is_active: true });
                if (data.success) {
                    this.dynamicCategories.push(data.category);
                    showToast('Kategori eklendi: ' + name);
                    this.newCatName = '';
                    this.showCatModal = false;
                }
            } catch(e) {
                showToast(e.message || 'Kategori eklenemedi.', 'error');
            }
        },

        openProductModal() {
            this.productForm = { name: '', sale_price: '', category_id: '', barcode: '', unit: 'Adet' };
            this.showProductModal = true;
            this.$nextTick(() => this.$el.querySelector('[x-model="productForm.name"]')?.focus());
        },

        async saveQuickProduct() {
            if (!this.productForm.name.trim() || !this.productForm.sale_price) return;
            this.productSaving = true;
            try {
                const payload = {
                    name: this.productForm.name,
                    sale_price: this.productForm.sale_price,
                    category_id: this.productForm.category_id || null,
                    barcode: this.productForm.barcode || null,
                    unit: this.productForm.unit || 'Adet',
                    vat_rate: 10,
                    purchase_price: 0,
                    stock_quantity: 0,
                };
                const data = await posAjax('{{ route("pos.products.store") }}', payload, 'POST');
                if (data.success) {
                    const p = data.product;
                    const newProd = {
                        id: p.id, name: p.name, sale_price: parseFloat(p.sale_price),
                        barcode: p.barcode, unit: p.unit, stock_quantity: p.stock_quantity,
                        category_id: p.category_id, category: '', vat_rate: p.vat_rate,
                        is_service: false,
                    };
                    this.products.unshift(newProd);
                    this.filteredProducts = [...this.products];
                    showToast('Ürün eklendi ve sepete eklendi!', 'success');
                    this.addToCart(newProd);
                    this.showProductModal = false;
                } else {
                    showToast(data.message || 'Ürün eklenemedi.', 'error');
                }
            } catch(e) {
                showToast(e.message || 'Ürün eklenemedi.', 'error');
            } finally {
                this.productSaving = false;
            }
        },

        async searchCustomers(query) {
            if (!query || query.length === 0) {
                try {
                    const data = await posAjax('{{ route("pos.customers.search") }}', {}, 'GET');
                    this.customerResults = data;
                    this.showCustomerDropdown = true;
                } catch(e) { console.error(e); }
                return;
            }
            try {
                const data = await posAjax('{{ route("pos.customers.search") }}?q=' + encodeURIComponent(query), {}, 'GET');
                this.customerResults = data;
                this.showCustomerDropdown = data.length > 0;
            } catch(e) { console.error(e); }
        },

        recalcMixedRemaining() {
            const entered = (this.mixedCash || 0) + (this.mixedCard || 0) + (this.mixedCredit || 0) + (this.mixedTransfer || 0);
            this.mixedRemaining = Math.round((this.totals.grand_total - entered) * 100) / 100;
        },

        async processPayment(method) {
            if (this.cart.length === 0) return;
            if (method === 'credit' && !this.selectedCustomer) {
                showToast('Veresiye satış için müşteri seçiniz.', 'error');
                return;
            }

            const payload = {
                items: this.cart.map(i => ({
                    product_id: i.product_id,
                    product_name: i.product_name,
                    barcode: i.barcode,
                    quantity: i.quantity,
                    unit_price: i.unit_price,
                    discount: i.discount,
                    vat_rate: i.vat_rate,
                    vat_amount: i.vat_amount,
                    additional_tax_amount: i.additional_tax_amount || 0,
                    total: i.total,
                })),
                payment_method: method,
                customer_id: this.selectedCustomer?.id,
                discount: this.generalDiscount,
                cash_amount: method === 'cash' ? this.totals.grand_total : 0,
                card_amount: method === 'card' ? this.totals.grand_total : 0,
            };

            try {
                const data = await posAjax('{{ route("pos.sales.store") }}', {
                    method: 'POST',
                    body: JSON.stringify(payload),
                });
                if (data.success) {
                    this.lastSale = data.sale;
                    this.showReceipt = true;
                    showToast('Satış başarıyla kaydedildi!');
                }
            } catch(e) {
                showToast(e.message || 'Satış kaydedilemedi.', 'error');
            }
        },

        async processMixedPayment() {
            const totalEntered = (this.mixedCash || 0) + (this.mixedCard || 0) + (this.mixedCredit || 0) + (this.mixedTransfer || 0);
            if (Math.abs(totalEntered - this.totals.grand_total) > 0.01) {
                showToast('Girilen tutarlar toplamı, satış tutarına (₺' + this.totals.grand_total.toFixed(2) + ') eşit olmalı.', 'error');
                return;
            }
            if (this.mixedCredit > 0 && !this.selectedCustomer) {
                showToast('Veresiye tutarı için müşteri seçiniz.', 'error');
                return;
            }

            const payload = {
                items: this.cart.map(i => ({
                    product_id: i.product_id,
                    product_name: i.product_name,
                    barcode: i.barcode,
                    quantity: i.quantity,
                    unit_price: i.unit_price,
                    discount: i.discount,
                    vat_rate: i.vat_rate,
                    vat_amount: i.vat_amount,
                    additional_tax_amount: i.additional_tax_amount || 0,
                    total: i.total,
                })),
                payment_method: 'mixed',
                customer_id: this.selectedCustomer?.id,
                discount: this.generalDiscount,
                cash_amount: this.mixedCash || 0,
                card_amount: this.mixedCard || 0,
                credit_amount: this.mixedCredit || 0,
                transfer_amount: this.mixedTransfer || 0,
            };

            try {
                const data = await posAjax('{{ route("pos.sales.store") }}', {
                    method: 'POST',
                    body: JSON.stringify(payload),
                });
                if (data.success) {
                    this.lastSale = data.sale;
                    this.showMixedPayment = false;
                    this.showReceipt = true;
                    this.mixedCash = 0; this.mixedCard = 0; this.mixedCredit = 0; this.mixedTransfer = 0; this.mixedRemaining = 0;
                    showToast('Satış başarıyla kaydedildi!');
                }
            } catch(e) {
                showToast(e.message || 'Satış kaydedilemedi.', 'error');
            }
        },

        closeReceipt() {
            this.showReceipt = false;
            this.cart = [];
            this.selectedCustomer = null;
            this.generalDiscount = 0;
            this.recalcTotals();
            this.$refs.searchInput?.focus();
        },

        printReceipt() {
            // TODO: ESC/POS printer integration
            window.print();
        },
    };
}
</script>
@endpush

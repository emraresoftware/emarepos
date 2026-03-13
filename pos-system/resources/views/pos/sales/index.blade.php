@extends('pos.layouts.app')
@section('title', 'Hızlı Satış')

@section('content')
<div x-data="posScreen()" x-init="init()" class="flex-1 flex flex-col overflow-hidden relative">

    {{-- Mobil Tab Bar (sadece mobilde görünür) --}}
    <div class="lg:hidden flex shrink-0 bg-white border-b border-gray-200 z-20">
        <button @click="mobileTab = 'cart'" 
                class="flex-1 py-3 text-center text-sm font-semibold transition-colors relative"
                :class="mobileTab === 'cart' ? 'text-brand-600 bg-brand-50' : 'text-gray-500'">
            <i class="fas fa-shopping-cart mr-1"></i> Sepet
            <span x-show="cart.length > 0" class="absolute top-1 right-1/4 bg-red-500 text-white text-[10px] w-5 h-5 rounded-full flex items-center justify-center font-bold" x-text="cart.length"></span>
        </button>
        <button @click="mobileTab = 'products'" 
                class="flex-1 py-3 text-center text-sm font-semibold transition-colors"
                :class="mobileTab === 'products' ? 'text-brand-600 bg-brand-50' : 'text-gray-500'">
            <i class="fas fa-th-large mr-1"></i> Ürünler
        </button>
    </div>

    {{-- ─── Hızı Ürün Ekleme Modalı ─── --}}
    <div x-show="showProductModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
         @keydown.escape.window="showProductModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6 max-h-[90vh] overflow-y-auto" @click.stop>
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
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
    
    {{-- ─── Hızlı Müşteri Ekle Modalı ─── --}}
    <div x-show="showQuickCustomerModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
         @keydown.escape.window="showQuickCustomerModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6" @click.stop>
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-semibold text-gray-900"><i class="fas fa-user-plus mr-2 text-brand-500"></i>Yeni Müşteri Ekle</h3>
                <button @click="showQuickCustomerModal = false" class="text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Ad Soyad *</label>
                    <input type="text" x-model="quickCustomerForm.name" @keydown.enter="saveQuickCustomer()"
                           placeholder="Müşteri adı..."
                           class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Telefon <span class="text-gray-400">(opsiyonel)</span></label>
                    <input type="tel" x-model="quickCustomerForm.phone" @keydown.enter="saveQuickCustomer()"
                           placeholder="0532..."
                           class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                </div>
                <div class="flex gap-3 pt-2">
                    <button @click="showQuickCustomerModal = false"
                            class="flex-1 px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">İptal</button>
                    <button @click="saveQuickCustomer()" :disabled="!quickCustomerForm.name.trim() || quickCustomerSaving"
                            class="flex-1 px-4 py-2 text-sm text-white bg-gradient-to-r from-brand-500 to-purple-600 rounded-xl hover:opacity-90 disabled:opacity-50 font-medium flex items-center justify-center gap-2">
                        <i class="fas fa-spinner fa-spin" x-show="quickCustomerSaving"></i>
                        <i class="fas fa-check" x-show="!quickCustomerSaving"></i>
                        <span x-text="quickCustomerSaving ? 'Kaydediliyor...' : 'Kaydet & Seç'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Paneller Satırı --}}
    <div class="flex-1 flex lg:flex-row-reverse overflow-hidden min-h-0">

    {{-- SAĞ PANEL: Sepet --}}
        <div class="w-full lg:flex-none flex flex-col bg-white border-l border-gray-200 flex-1 min-h-0 overflow-hidden relative"
            style="width:360px"
            :style="panelStyle()"
            :class="{ 'hidden lg:flex': mobileTab !== 'cart' }">

           <div x-show="panelResizeEnabled" x-cloak
               class="hidden lg:block absolute left-0 top-0 h-full w-1.5 cursor-col-resize bg-transparent hover:bg-brand-200/40"
               @mousedown.prevent="startPanelResize($event)"></div>
               

        {{-- Koyu Header: Barkod + Toplam + KDV --}}
        <div class="bg-gray-100 shrink-0">
            {{-- Barkod Input --}}
            <div class="px-3 pt-2 pb-1">
                <div class="relative">
                          <input type="text" x-model="barcodeQuery"
                              @input.debounce.200ms="searchBarcode()"
                              @keydown.enter.prevent="handleBarcodeEnter()"
                              @keydown.arrow-down.prevent="moveBarcodeSelection(1)"
                              @keydown.arrow-up.prevent="moveBarcodeSelection(-1)"
                           placeholder="Barkod Okutunuz"
                           class="w-full pl-3 pr-10 py-2.5 bg-white border-2 border-blue-400 rounded text-gray-900 text-sm font-medium placeholder-gray-400 focus:outline-none focus:border-blue-500"
                           x-ref="searchInput">
                    <i class="fas fa-barcode absolute right-3 top-3 text-gray-400 text-lg"></i>
                    <div x-show="showBarcodeDropdown" x-transition @click.away="showBarcodeDropdown = false"
                         class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl z-40 max-h-56 overflow-y-auto">
                        <template x-for="(p, idx) in barcodeResults" :key="p.id">
                            <button @click="selectBarcodeResult(p)" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex items-center justify-between"
                                    :class="barcodeSelectedIndex === idx ? 'bg-brand-50 text-brand-700' : 'text-gray-700'">
                                <span class="truncate" x-text="p.name"></span>
                                <span class="text-xs text-gray-400 ml-2" x-text="p.barcode || ''"></span>
                            </button>
                        </template>
                        <div x-show="barcodeSearching" class="px-3 py-2 text-xs text-gray-400">Araniyor...</div>
                        <div x-show="!barcodeSearching && barcodeResults.length === 0" class="px-3 py-2.5 space-y-1.5">
                            <div class="text-xs text-gray-400 text-center">Ürün bulunamadı</div>
                            <button @click="openQuickProductWithBarcode()"
                                    class="w-full py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-medium rounded-lg border border-emerald-200 flex items-center justify-center gap-1.5 transition-colors">
                                <i class="fas fa-plus-circle text-[11px]"></i>
                                "<span x-text="barcodeQuery"></span>" — Yeni ürün olarak ekle
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Toplam --}}
            <div class="bg-slate-900 px-4 py-2 text-center">
                <span class="text-2xl sm:text-3xl font-bold text-white" x-text="formatCurrency(totals.grand_total)"></span>
            </div>
            {{-- KDV Satırı --}}
            <div class="grid grid-cols-4 divide-x divide-gray-200 text-center py-1 bg-gray-100">
                <div class="px-1">
                    <div class="text-[10px] text-gray-400">%0 KDV</div>
                    <div class="text-xs text-gray-700 font-medium" x-text="formatCurrency(vatByRate(0))"></div>
                </div>
                <div class="px-1">
                    <div class="text-[10px] text-gray-400">%1 KDV</div>
                    <div class="text-xs text-gray-700 font-medium" x-text="formatCurrency(vatByRate(1))"></div>
                </div>
                <div class="px-1">
                    <div class="text-[10px] text-gray-400">%10 KDV</div>
                    <div class="text-xs text-gray-700 font-medium" x-text="formatCurrency(vatByRate(10))"></div>
                </div>
                <div class="px-1">
                    <div class="text-[10px] text-gray-400">%20 KDV</div>
                    <div class="text-xs text-gray-700 font-medium" x-text="formatCurrency(vatByRate(20))"></div>
                </div>
            </div>
            {{-- Kolon Başlıkları --}}
            <div class="grid grid-cols-[1fr_auto_auto_auto] gap-2 px-3 py-1.5 bg-gray-200 text-[11px] text-gray-600 font-medium">
                <span>Ürün Adı</span>
                <span class="w-16 text-right">Fiyat</span>
                <span class="w-10 text-center">Birim</span>
                <span class="w-16 text-right">Tutar</span>
            </div>
        </div>

        {{-- Sepet Listesi --}}
        <div class="flex-1 overflow-y-auto bg-white">
            <template x-if="cart.length === 0">
                <div class="flex flex-col items-center justify-center h-full text-gray-300 py-12">
                    <i class="fas fa-shopping-cart text-5xl mb-3"></i>
                    <p class="text-sm text-gray-400">Sepet boş</p>
                </div>
            </template>
            <div class="divide-y divide-gray-100">
                <template x-for="(item, index) in cart" :key="index">
                    <div class="hover:bg-blue-50/50 transition-colors">
                        {{-- Ana Satır --}}
                            <div class="grid grid-cols-[1fr_auto_auto_auto] gap-2 px-3 py-2 items-center cursor-pointer"
                                @click="(item.price_options && item.price_options.length > 1) ? openCartPriceModal(index) : (item.showDiscount = !item.showDiscount)">
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate" x-text="item.product_name"></div>
                                <div class="text-[11px] text-gray-500 flex items-center gap-1.5 mt-0.5">
                                    <div class="flex items-center bg-gray-100 rounded">
                                        <button @click.stop="updateQty(index, -1)" class="px-1.5 py-0.5 text-gray-500 hover:text-red-500 text-xs">−</button>
                                        <span class="px-1 text-gray-700 font-medium text-xs" x-text="item.quantity"></span>
                                        <button @click.stop="updateQty(index, 1)" class="px-1.5 py-0.5 text-gray-500 hover:text-emerald-600 text-xs">+</button>
                                    </div>
                                    <template x-if="item.price_options && item.price_options.length > 1">
                                        <select x-model="item.price_label" @change="updatePriceType(index, item.price_label)"
                                                class="bg-white border border-gray-200 rounded px-1.5 py-0.5 text-[10px] text-gray-600">
                                            <template x-for="opt in item.price_options" :key="opt.label + '-' + opt.price">
                                                <option :value="opt.label" x-text="opt.label"></option>
                                            </template>
                                        </select>
                                    </template>
                                    <template x-if="item.price_label === 'Diğer'">
                                        <input type="number" x-model.number="item.custom_price" @input="updatePriceType(index, 'Diğer')"
                                               class="w-16 bg-white border border-gray-200 rounded px-1.5 py-0.5 text-[10px] text-gray-600"
                                               min="0" step="0.01" placeholder="0.00">
                                    </template>
                                    <span x-text="'× ' + formatCurrency(item.unit_price)" class="text-gray-400"></span>
                                    <button @click.stop="removeFromCart(index)" class="ml-auto text-gray-300 hover:text-red-500 transition-colors">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="w-16 text-right text-xs text-gray-500" x-text="formatCurrency(item.unit_price)"></div>
                            <div class="w-10 text-center text-xs text-gray-500" x-text="item.quantity"></div>
                            <div class="w-16 text-right text-sm font-bold text-gray-900" x-text="formatCurrency(item.total)"></div>
                        </div>
                        {{-- İskonto Satırı --}}
                        <div x-show="item.showDiscount" x-transition class="px-3 pb-2 flex items-center gap-2 bg-amber-50">
                            <span class="text-xs text-amber-700 font-medium">İsk:</span>
                            <input type="number" x-model.number="item.discount" @input="recalcItem(index)"
                                   class="w-20 px-2 py-1 bg-white border border-amber-300 rounded text-gray-800 text-xs focus:outline-none focus:border-amber-500"
                                   min="0" step="0.01" :placeholder="item.discountType === '%' ? '%0' : '₺0'"
                                   @click.stop>
                            <button @click.stop="item.discountType = item.discountType === '%' ? 'TL' : '%'; recalcItem(index)"
                                    class="px-2 py-1 text-xs rounded font-bold transition-colors"
                                    :class="item.discountType === '%' ? 'bg-amber-200 text-amber-700' : 'bg-gray-200 text-gray-600'"
                                    x-text="item.discountType === '%' ? '%' : '₺'"></button>
                            <span class="text-xs text-amber-600 ml-auto" x-show="item.discountAmount > 0" x-text="'-' + formatCurrency(item.discountAmount)"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Genel İndirim + Toplam Bölümü --}}
        <div class="border-t border-gray-200 px-3 py-1.5 sm:px-4 sm:py-3 bg-white space-y-1 sm:space-y-1.5 shrink-0">
            {{-- Genel İndirim --}}
            <div class="flex items-center justify-between">
                <button @click="showGeneralDiscount = !showGeneralDiscount" class="text-xs text-gray-500 hover:text-gray-700 flex items-center gap-1.5">
                    <i class="fas fa-cut text-[11px]"></i> Genel İndirim
                </button>
                <div x-show="showGeneralDiscount" class="flex items-center gap-1">
                    <input type="number" x-model.number="generalDiscount" @input="recalcTotals()"
                           class="w-20 px-2 py-0.5 bg-white border border-gray-200 rounded text-gray-800 text-xs focus:outline-none focus:border-brand-500"
                           min="0" step="0.01">
                    <button @click="generalDiscountType = generalDiscountType === '%' ? 'TL' : '%'; recalcTotals()"
                            class="px-2 py-0.5 text-xs rounded font-bold transition-colors"
                            :class="generalDiscountType === '%' ? 'bg-amber-100 text-amber-700' : 'bg-gray-200 text-gray-600'"
                            x-text="generalDiscountType === '%' ? '%' : '₺'"></button>
                </div>
                <div x-show="!showGeneralDiscount && totals.discount_total > 0" class="text-xs text-amber-600 font-medium" x-text="'-' + formatCurrency(totals.discount_total)"></div>
            </div>
            {{-- Ara Toplam --}}
            <div class="flex justify-between text-xs sm:text-sm text-gray-600">
                <span>Ara Toplam</span>
                <span class="font-medium" x-text="formatCurrency(totals.subtotal)"></span>
            </div>
            {{-- KDV --}}
            <div class="flex justify-between text-xs sm:text-sm text-gray-600">
                <span>KDV</span>
                <span class="font-medium" x-text="formatCurrency(totals.vat_total)"></span>
            </div>
            {{-- Ayırıcı --}}
            <div class="border-t border-gray-200 pt-1 sm:pt-2">
                <div class="flex justify-between items-center">
                    <span class="text-base sm:text-lg font-bold text-gray-900">TOPLAM</span>
                    <span class="text-base sm:text-lg font-bold text-red-600" x-text="formatCurrency(totals.grand_total)"></span>
                </div>
            </div>
            {{-- Ödenen / Para Üstü --}}
            <div class="flex items-center gap-2 pt-1" x-show="cart.length > 0">
                <span class="text-xs text-gray-500 whitespace-nowrap">Ödenen:</span>
                <input type="number" x-model.number="paidAmount"
                       class="flex-1 min-w-0 px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-800 text-sm focus:outline-none focus:border-emerald-500"
                       min="0" step="0.01" placeholder="Nakit tutar...">
                <span class="text-sm font-semibold text-emerald-600 whitespace-nowrap"
                      x-show="(paidAmount || 0) >= totals.grand_total && paidAmount > 0"
                      x-text="'Üstü: ' + formatCurrency((paidAmount||0) - totals.grand_total)"></span>
            </div>
        </div>

        {{-- Müşteri Seçimi --}}
        <div class="border-t border-gray-200 shrink-0" @click.away="showCustomerDropdown = false">
            {{-- Seçilmemiş → Buton --}}
            <button x-show="!selectedCustomer && !showCustomerDropdown"
                    @click="showCustomerDropdown = true; $nextTick(() => $refs.customerInput?.focus()); searchCustomers('')"
                    class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium flex items-center justify-center gap-2 transition-colors">
                <i class="fas fa-user-circle text-base"></i> Müşteri Seçiniz
            </button>
            {{-- Arama Alanı --}}
            <div x-show="!selectedCustomer && showCustomerDropdown" class="p-2">
                <div class="flex items-center gap-1.5">
                    <input x-ref="customerInput" type="text" x-model="customerSearch"
                           @input.debounce.300ms="searchCustomers(customerSearch)"
                           @keydown.escape="showCustomerDropdown = false; customerSearch = ''"
                           placeholder="Müşteri ara (ad / telefon)..."
                           class="flex-1 px-3 py-1.5 border border-gray-200 rounded-lg text-sm text-gray-800 focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30">
                    <button @click="quickCustomerForm.name = customerSearch; showQuickCustomerModal = true; showCustomerDropdown = false"
                            title="Yeni müşteri ekle"
                            class="p-1.5 text-green-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors shrink-0">
                        <i class="fas fa-user-plus text-sm"></i>
                    </button>
                    <button @click="showCustomerDropdown = false; customerSearch = ''" class="p-1.5 text-gray-400 hover:text-red-500 transition-colors shrink-0">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
                {{-- Sonuçlar --}}
                <div x-show="customerResults.length > 0" class="mt-1 max-h-36 overflow-y-auto border border-gray-100 rounded-lg divide-y divide-gray-50 bg-white shadow-sm">
                    <template x-for="c in customerResults" :key="c.id">
                        <button @click="selectedCustomer = c; showCustomerDropdown = false; customerSearch = ''; customerResults = []"
                                class="w-full text-left px-3 py-2 hover:bg-blue-50 text-sm flex items-center justify-between transition-colors">
                            <div>
                                <div class="text-gray-900 font-medium text-sm" x-text="c.name"></div>
                                <div class="text-xs text-gray-400" x-text="c.phone || c.email || ''"></div>
                            </div>
                            <span class="text-xs font-medium shrink-0 ml-2" :class="c.balance < 0 ? 'text-red-500' : 'text-emerald-500'" x-text="formatCurrency(c.balance)"></span>
                        </button>
                    </template>
                </div>
                {{-- Sonuç yok --}}
                <div x-show="customerSearch.length > 1 && customerResults.length === 0" class="mt-1">
                    <p class="text-xs text-gray-400 text-center py-1">Kayıt bulunamadı</p>
                    <button @click="quickCustomerForm.name = customerSearch; showQuickCustomerModal = true; showCustomerDropdown = false"
                            class="w-full py-2 bg-brand-50 hover:bg-brand-100 text-brand-600 text-xs font-medium rounded-lg border border-brand-200 flex items-center justify-center gap-1.5 transition-colors">
                        <i class="fas fa-user-plus"></i>
                        "<span x-text="customerSearch"></span>" adlı müşteri oluştur
                    </button>
                </div>
            </div>
            {{-- Seçildi --}}
            <div x-show="selectedCustomer" class="flex items-center gap-2 px-3 py-2.5 bg-blue-600">
                <i class="fas fa-user-check text-white text-sm shrink-0"></i>
                <span class="flex-1 text-sm text-white font-medium truncate" x-text="selectedCustomer?.name"></span>
                <span class="text-xs text-blue-200 whitespace-nowrap" :class="(selectedCustomer?.balance ?? 0) < 0 ? 'text-red-300' : 'text-blue-200'" x-text="formatCurrency(selectedCustomer?.balance ?? 0)"></span>
                <button @click="selectedCustomer = null; customerSearch = ''" class="text-blue-200 hover:text-white transition-colors shrink-0">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
        </div>

        {{-- Alt Bar: Son Fişler + İskonto + Yazdır + İade + Ödeme Al --}}
        <div class="grid grid-cols-5 shrink-0 safe-bottom">
            <button @click="loadRecentSales()"
                    class="py-2.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium flex items-center justify-center gap-1 border-r border-gray-500 transition-colors">
                <i class="fas fa-receipt text-[11px]"></i><span class="hidden sm:inline">Son</span> Fişler
            </button>
            <button @click="showDiscountModal = true"
                    class="py-2.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium flex items-center justify-center gap-1 border-r border-gray-500 transition-colors">
                <i class="fas fa-percent text-[11px]"></i>İskonto
            </button>
            <button @click="printReceipt()"
                    class="py-2.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium flex items-center justify-center gap-1 border-r border-gray-500 transition-colors">
                <i class="fas fa-print text-[11px]"></i>Yazdır
            </button>
            <button @click="startRefund()"
                    class="py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium flex items-center justify-center gap-1 border-r border-gray-500 transition-colors">
                <i class="fas fa-undo text-[11px]"></i>İade
            </button>
            <button @click="openOdemeAl()"
                    class="py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-medium flex items-center justify-center gap-1 transition-colors">
                <i class="fas fa-hand-holding-usd text-[11px]"></i>Ödeme Al
            </button>
        </div>
    </div>

    {{-- İSKONTO UYGULA MODALI --}}
    <div x-show="showDiscountModal" x-transition x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto" @click.away="showDiscountModal = false">
            <div class="flex items-center justify-between px-5 py-3 bg-blue-600 rounded-t-2xl">
                <h3 class="text-base font-bold text-white"><i class="fas fa-percent mr-2"></i>İskonto Uygula</h3>
                <button @click="showDiscountModal = false" class="text-white/70 hover:text-white"><i class="fas fa-times-circle text-lg"></i></button>
            </div>
            <div class="p-4">
                {{-- Hızlı Yüzdeler --}}
                <div class="grid grid-cols-4 sm:grid-cols-6 gap-2 mb-4">
                    <template x-for="rate in [0,5,10,15,20,25,30,35,40,45,50,55,60,65,70,75,80,85,90,95,100]" :key="rate">
                        <button @click="applyDiscountRate(rate); showDiscountModal = false"
                                class="py-4 border-2 rounded-xl text-center hover:border-blue-500 hover:bg-blue-50 transition-all"
                                :class="generalDiscount == rate && generalDiscountType === '%' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                            <div class="text-lg font-bold text-gray-900" x-text="'%' + rate"></div>
                            <div class="text-[10px] text-gray-500 uppercase font-semibold tracking-wide">İSKONTO</div>
                        </button>
                    </template>
                </div>
                {{-- Elle Giriş --}}
                <div class="border-t border-gray-200 pt-3">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-gray-700 whitespace-nowrap">Elle İskonto:</span>
                        <input type="number" x-model.number="manualDiscountInput" min="0" step="0.01" placeholder="Oran veya tutar..."
                               class="flex-1 px-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                        <button @click="generalDiscountType = generalDiscountType === '%' ? 'TL' : '%'"
                                class="px-3 py-2 text-sm rounded-xl font-bold transition-colors"
                                :class="generalDiscountType === '%' ? 'bg-blue-100 text-blue-700 border border-blue-300' : 'bg-gray-100 text-gray-600 border border-gray-300'"
                                x-text="generalDiscountType === '%' ? '%' : '₺'"></button>
                        <button @click="generalDiscount = manualDiscountInput || 0; recalcTotals(); showDiscountModal = false"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-colors">
                            Uygula
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SON FİŞLER MODALI --}}
    <div x-show="showRecentSales" x-transition x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[85vh] flex flex-col" @click.away="showRecentSales = false">
            <div class="flex items-center justify-between px-5 py-3 bg-gray-700 rounded-t-2xl shrink-0">
                <h3 class="text-base font-bold text-white"><i class="fas fa-receipt mr-2"></i>Son Fişler</h3>
                <button @click="showRecentSales = false" class="text-white/70 hover:text-white"><i class="fas fa-times-circle text-lg"></i></button>
            </div>
            {{-- Fiş Listesi --}}
            <div class="flex-1 overflow-y-auto divide-y divide-gray-100" x-show="!selectedSaleDetail">
                <template x-for="sale in recentSalesList" :key="sale.id">
                    <button @click="openSaleDetail(sale.id)" class="w-full text-left px-5 py-3 hover:bg-gray-50 transition-colors flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0"
                             :class="sale.status === 'refunded' ? 'bg-red-100' : 'bg-emerald-100'">
                            <i class="fas text-sm" :class="sale.status === 'refunded' ? 'fa-undo text-red-500' : 'fa-check text-emerald-500'"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-900" x-text="sale.receipt_no"></div>
                            <div class="text-xs text-gray-500 flex gap-3">
                                <span x-text="new Date(sale.sold_at).toLocaleString('tr-TR', {day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'})"></span>
                                <span class="capitalize" x-text="sale.payment_method"></span>
                                <span x-show="sale.customer" x-text="sale.customer?.name" class="text-blue-500"></span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-sm font-bold" :class="sale.status === 'refunded' ? 'text-red-500 line-through' : 'text-gray-900'" x-text="formatCurrency(sale.grand_total)"></div>
                            <div x-show="sale.status === 'refunded'" class="text-[10px] text-red-400 font-medium">İADE</div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-300 text-xs shrink-0"></i>
                    </button>
                </template>
                <div x-show="recentSalesList.length === 0" class="p-10 text-center text-gray-400">
                    <i class="fas fa-receipt text-3xl mb-2"></i>
                    <p>Fiş bulunamadı</p>
                </div>
            </div>
            {{-- Fiş Detayı --}}
            <div x-show="selectedSaleDetail" class="flex-1 overflow-y-auto">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-3 bg-gray-50 shrink-0">
                    <button @click="selectedSaleDetail = null" class="text-gray-500 hover:text-gray-700"><i class="fas fa-arrow-left"></i></button>
                    <div>
                        <div class="text-sm font-bold text-gray-900" x-text="selectedSaleDetail?.receipt_no"></div>
                        <div class="text-xs text-gray-500" x-text="selectedSaleDetail?.sold_at ? new Date(selectedSaleDetail.sold_at).toLocaleString('tr-TR') : ''"></div>
                    </div>
                    <div class="ml-auto">
                        <span class="text-xs px-2 py-1 rounded-full font-medium"
                              :class="selectedSaleDetail?.status === 'refunded' ? 'bg-red-100 text-red-600' : 'bg-emerald-100 text-emerald-600'"
                              x-text="selectedSaleDetail?.status === 'refunded' ? 'İade Edildi' : 'Tamamlandı'"></span>
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                    <template x-for="item in (selectedSaleDetail?.items || [])" :key="item.id">
                        <div class="px-5 py-2.5 flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-900" x-text="item.product_name"></div>
                                <div class="text-xs text-gray-500" x-text="item.quantity + ' x ' + formatCurrency(item.unit_price)"></div>
                            </div>
                            <div class="text-sm font-bold text-gray-900" x-text="formatCurrency(item.total)"></div>
                        </div>
                    </template>
                </div>
                <div class="px-5 py-3 bg-gray-50 space-y-1 border-t">
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Toplam</span><span class="font-bold text-gray-900" x-text="formatCurrency(selectedSaleDetail?.grand_total || 0)"></span></div>
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Ödeme</span><span class="capitalize text-gray-700" x-text="selectedSaleDetail?.payment_method"></span></div>
                    <div class="flex justify-between text-sm" x-show="selectedSaleDetail?.customer"><span class="text-gray-500">Müşteri</span><span class="text-blue-600" x-text="selectedSaleDetail?.customer?.name"></span></div>
                </div>
                {{-- İade Butonu --}}
                <div class="px-5 py-3 border-t" x-show="selectedSaleDetail?.status !== 'refunded'">
                    <button @click="refundSale(selectedSaleDetail?.id)"
                            class="w-full py-2.5 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-xl transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-undo"></i> Bu Fişi İade Al
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ÖDEME AL MODALI --}}
    <div x-show="showOdemeAlModal" x-transition x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-3">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-auto max-h-[92vh] overflow-y-auto" @click.away="showOdemeAlModal = false">
            <div class="flex items-center justify-between px-5 py-3 bg-emerald-700 rounded-t-2xl sticky top-0 z-10">
                <h3 class="text-base font-bold text-white"><i class="fas fa-hand-holding-usd mr-2"></i>Ödeme Al / Hesap İşlemi</h3>
                <button @click="showOdemeAlModal = false" class="text-white/70 hover:text-white"><i class="fas fa-times-circle text-lg"></i></button>
            </div>
            <div class="p-5 space-y-4">

                {{-- ADIM 1: Müşteri Seç --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-2"><span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-600 text-white text-xs mr-1.5">1</span>Müşteri</label>
                    <div x-show="!odemeCustomer" class="space-y-2">
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <input type="text" x-model="odemeSearch"
                                       @input.debounce.300ms="searchOdemeCustomers(odemeSearch)"
                                       placeholder="Ad, telefon veya e-posta ile ara..."
                                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                                <i class="fas fa-search absolute right-3 top-3 text-gray-400 text-xs"></i>
                            </div>
                            <button @click="odemeNewCustomerMode = !odemeNewCustomerMode"
                                    class="px-3 py-2 bg-blue-50 border border-blue-200 text-blue-600 rounded-xl text-xs font-medium hover:bg-blue-100 transition-colors whitespace-nowrap">
                                <i class="fas fa-user-plus mr-1"></i>Yeni
                            </button>
                        </div>
                        {{-- Sonuçlar --}}
                        <div x-show="odemeCustomerResults.length > 0" class="border border-gray-100 rounded-xl divide-y divide-gray-50 max-h-40 overflow-y-auto shadow-sm">
                            <template x-for="c in odemeCustomerResults" :key="c.id">
                                <button @click="selectOdemeCustomer(c)"
                                        class="w-full text-left px-3 py-2.5 hover:bg-emerald-50 transition-colors flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900" x-text="c.name"></div>
                                        <div class="text-xs text-gray-400" x-text="c.phone || c.email || ''"></div>
                                    </div>
                                    <div class="text-right shrink-0 ml-3">
                                        <div class="text-xs font-bold" :class="(c.balance ?? 0) < 0 ? 'text-red-500' : 'text-emerald-600'" x-text="formatCurrency(c.balance ?? 0)"></div>
                                        <div class="text-[10px] text-gray-400" x-text="(c.balance ?? 0) < 0 ? 'Borçlu' : (c.balance ?? 0) > 0 ? 'Alacaklı' : 'Bakiye 0'"></div>
                                    </div>
                                </button>
                            </template>
                        </div>
                        {{-- Sonuç yok → oluştur --}}
                        <div x-show="odemeSearch.length > 1 && odemeCustomerResults.length === 0 && !odemeCustomerLoading" class="mt-1">
                            <button @click="odemeNewCustomerMode = true; odemeNewName = odemeSearch"
                                    class="w-full py-2 bg-emerald-50 text-emerald-700 rounded-xl text-xs font-medium border border-emerald-200 hover:bg-emerald-100 transition-colors">
                                <i class="fas fa-user-plus mr-1"></i> "<span x-text="odemeSearch"></span>" — Yeni müşteri oluştur
                            </button>
                        </div>
                        {{-- Yeni Müşteri Formu --}}
                        <div x-show="odemeNewCustomerMode" x-transition class="bg-blue-50 border border-blue-200 rounded-xl p-3 space-y-2">
                            <p class="text-xs font-semibold text-blue-700"><i class="fas fa-user-plus mr-1"></i>Yeni Müşteri Oluştur</p>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" x-model="odemeNewName" placeholder="Ad Soyad *"
                                       class="col-span-2 px-3 py-2 border border-blue-200 rounded-lg text-sm bg-white focus:outline-none focus:border-blue-500">
                                <input type="text" x-model="odemeNewPhone" placeholder="Telefon"
                                       class="px-3 py-2 border border-blue-200 rounded-lg text-sm bg-white focus:outline-none focus:border-blue-500">
                                <select x-model="odemeNewType"
                                        class="px-3 py-2 border border-blue-200 rounded-lg text-sm bg-white focus:outline-none focus:border-blue-500">
                                    <option value="individual">Bireysel</option>
                                    <option value="corporate">Kurumsal</option>
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <button @click="odemeNewCustomerMode = false" class="flex-1 py-1.5 text-xs text-gray-600 bg-white border border-gray-200 rounded-lg">İptal</button>
                                <button @click="createAndSelectOdemeCustomer()" :disabled="!odemeNewName.trim()"
                                        class="flex-1 py-1.5 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-medium disabled:opacity-50">
                                    <i class="fas fa-check mr-1"></i>Oluştur ve Seç
                                </button>
                            </div>
                        </div>
                    </div>
                    {{-- Seçildi --}}
                    <div x-show="odemeCustomer" class="flex items-center gap-3 p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                        <div class="flex-1">
                            <div class="text-sm font-bold text-gray-900" x-text="odemeCustomer?.name"></div>
                            <div class="text-xs text-gray-500" x-text="odemeCustomer?.phone || ''"></div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs font-bold" :class="(odemeCustomer?.balance ?? 0) < 0 ? 'text-red-500' : 'text-emerald-600'" x-text="formatCurrency(odemeCustomer?.balance ?? 0)"></div>
                            <div class="text-[10px] text-gray-400">Mevcut Bakiye</div>
                        </div>
                        <button @click="odemeCustomer = null; odemeSearch = ''" class="text-gray-400 hover:text-red-500 transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                {{-- ADIM 2: İşlem Türü --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-2"><span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-600 text-white text-xs mr-1.5">2</span>İşlem Türü</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button @click="odemeType = 'payment'"
                                class="flex flex-col items-center py-3 rounded-xl border-2 transition-all"
                                :class="odemeType === 'payment' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 text-gray-600 hover:border-gray-300'">
                            <i class="fas fa-coins text-xl mb-1"></i>
                            <span class="text-xs font-semibold">Tahsilat</span>
                            <span class="text-[10px] text-gray-400">Müşteriden para al</span>
                        </button>
                        <button @click="odemeType = 'debt'"
                                class="flex flex-col items-center py-3 rounded-xl border-2 transition-all"
                                :class="odemeType === 'debt' ? 'border-red-500 bg-red-50 text-red-700' : 'border-gray-200 text-gray-600 hover:border-gray-300'">
                            <i class="fas fa-file-invoice-dollar text-xl mb-1"></i>
                            <span class="text-xs font-semibold">Borç Ekle</span>
                            <span class="text-[10px] text-gray-400">Veresiye / alacak</span>
                        </button>
                    </div>
                </div>

                {{-- ADIM 3: Ödeme Yöntemi (Tahsilatta) --}}
                <div x-show="odemeType === 'payment'" x-transition>
                    <label class="block text-sm font-semibold text-gray-800 mb-2"><span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-600 text-white text-xs mr-1.5">3</span>Ödeme Yöntemi</label>
                    <div class="grid grid-cols-4 gap-2">
                        <button @click="odemePaymentMethod = 'cash'"
                                class="py-2 rounded-xl border-2 text-xs font-semibold transition-all"
                                :class="odemePaymentMethod === 'cash' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 text-gray-600 hover:border-gray-300'">
                            <i class="fas fa-money-bill-wave block mb-0.5"></i>Nakit
                        </button>
                        <button @click="odemePaymentMethod = 'card'"
                                class="py-2 rounded-xl border-2 text-xs font-semibold transition-all"
                                :class="odemePaymentMethod === 'card' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-600 hover:border-gray-300'">
                            <i class="fas fa-credit-card block mb-0.5"></i>Kart
                        </button>
                        <button @click="odemePaymentMethod = 'transfer'"
                                class="py-2 rounded-xl border-2 text-xs font-semibold transition-all"
                                :class="odemePaymentMethod === 'transfer' ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-gray-200 text-gray-600 hover:border-gray-300'">
                            <i class="fas fa-exchange-alt block mb-0.5"></i>Havale
                        </button>
                        <button @click="odemePaymentMethod = 'other'"
                                class="py-2 rounded-xl border-2 text-xs font-semibold transition-all"
                                :class="odemePaymentMethod === 'other' ? 'border-gray-700 bg-gray-100 text-gray-800' : 'border-gray-200 text-gray-600 hover:border-gray-300'">
                            <i class="fas fa-ellipsis-h block mb-0.5"></i>Diğer
                        </button>
                    </div>
                </div>

                {{-- ADIM 3b: Borç Türü --}}
                <div x-show="odemeType === 'debt'" x-transition>
                    <label class="block text-sm font-semibold text-gray-800 mb-2"><span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-xs mr-1.5">3</span>Borç Türü</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button @click="odemeDebtType = 'veresiye'"
                                class="py-2.5 text-xs rounded-xl border-2 font-semibold transition-all"
                                :class="odemeDebtType === 'veresiye' ? 'border-red-500 bg-red-50 text-red-700' : 'border-gray-200 text-gray-600 hover:border-gray-300'">
                            <i class="fas fa-shopping-cart block mb-1"></i>Veresiye Satış
                        </button>
                        <button @click="odemeDebtType = 'avans'"
                                class="py-2.5 text-xs rounded-xl border-2 font-semibold transition-all"
                                :class="odemeDebtType === 'avans' ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-gray-200 text-gray-600 hover:border-gray-300'">
                            <i class="fas fa-hand-holding-heart block mb-1"></i>Avans / Ön Ödeme
                        </button>
                    </div>
                </div>

                {{-- ADIM 4: Tutar + Açıklama --}}
                <div class="border-t border-gray-100 pt-4 space-y-3">
                    <label class="block text-sm font-semibold text-gray-800"><span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-600 text-white text-xs mr-1.5">4</span>Tutar ve Açıklama</label>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tutar (₺) *</label>
                        <input type="number" step="0.01" min="0.01" x-model="odemeAmount"
                               placeholder="0.00"
                               class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg font-bold text-gray-900 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-right">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Açıklama</label>
                        <input type="text" x-model="odemeDescription"
                               placeholder="İşlem açıklaması (opsiyonel)"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                    </div>
                </div>

                {{-- Sonuç Önizleme --}}
                <div x-show="odemeCustomer && odemeAmount > 0" class="bg-gray-50 border border-gray-100 rounded-xl p-3 space-y-1.5">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Mevcut Bakiye</span>
                        <span class="font-medium" :class="(odemeCustomer?.balance ?? 0) < 0 ? 'text-red-500' : 'text-emerald-600'" x-text="formatCurrency(odemeCustomer?.balance ?? 0)"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500" x-text="odemeType === 'payment' ? 'Tahsilat' : 'Borç'"></span>
                        <span :class="odemeType === 'payment' ? 'text-emerald-600' : 'text-red-500'" x-text="(odemeType === 'payment' ? '+' : '-') + formatCurrency(odemeAmount || 0)"></span>
                    </div>
                    <div class="flex justify-between text-sm font-bold border-t border-gray-200 pt-1.5">
                        <span class="text-gray-700">Yeni Bakiye</span>
                        <span x-text="formatCurrency((odemeCustomer?.balance ?? 0) + (odemeType === 'payment' ? parseFloat(odemeAmount||0) : -parseFloat(odemeAmount||0)))"
                              :class="((odemeCustomer?.balance ?? 0) + (odemeType === 'payment' ? parseFloat(odemeAmount||0) : -parseFloat(odemeAmount||0))) < 0 ? 'text-red-600' : 'text-emerald-700'"></span>
                    </div>
                </div>

                {{-- Butonlar --}}
                <div class="flex gap-3 pt-1">
                    <button @click="showOdemeAlModal = false" class="flex-1 py-2.5 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl font-medium transition-colors">İptal</button>
                    <button @click="submitOdemeAl()"
                            :disabled="!odemeCustomer || !odemeAmount || odemeAmount <= 0 || odemedSaving"
                            class="flex-1 py-2.5 text-sm text-white rounded-xl font-semibold transition-all disabled:opacity-50 flex items-center justify-center gap-2"
                            :class="odemeType === 'payment' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700'">
                        <i :class="odemedSaving ? 'fas fa-spinner fa-spin' : (odemeType === 'payment' ? 'fas fa-check-circle' : 'fas fa-file-invoice')"></i>
                        <span x-text="odemedSaving ? 'Kaydediliyor...' : (odemeType === 'payment' ? 'Tahsilatı Kaydet' : 'Borcu Kaydet')"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>


    {{-- İADE MODALI --}}
    <div x-show="showRefundModal" x-transition x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4" @click.away="showRefundModal = false">
            <div class="flex items-center justify-between px-5 py-3 bg-red-600 rounded-t-2xl">
                <h3 class="text-base font-bold text-white"><i class="fas fa-undo mr-2"></i>İade Al</h3>
                <button @click="showRefundModal = false" class="text-white/70 hover:text-white"><i class="fas fa-times-circle text-lg"></i></button>
            </div>
            <div class="p-5">
                <p class="text-sm text-gray-600 mb-3">Fiş numarası girerek veya son fişlerden seçerek iade yapabilirsiniz.</p>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Fiş Numarası</label>
                        <input type="text" x-model="refundReceiptNo" placeholder="POS-2026-000001"
                               class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">İade Sebebi</label>
                        <input type="text" x-model="refundReason" placeholder="Müşteri iade istedi..."
                               class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20">
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button @click="showRefundModal = false" class="flex-1 px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl">İptal</button>
                        <button @click="searchAndRefund()" :disabled="!refundReceiptNo.trim() || refundProcessing"
                                class="flex-1 px-4 py-2 text-sm text-white bg-red-600 hover:bg-red-700 rounded-xl font-medium disabled:opacity-50 flex items-center justify-center gap-2">
                            <i class="fas fa-spinner fa-spin" x-show="refundProcessing"></i>
                            <i class="fas fa-undo" x-show="!refundProcessing"></i>
                            <span x-text="refundProcessing ? 'İşleniyor...' : 'İade Yap'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Fiyat Seçim Modalı (Barkod okutunca çoklu fiyat varsa) --}}
    <div x-show="showPriceSelectModal" x-transition x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4" @click.away="showPriceSelectModal = false; pendingProduct = null; pendingCartIndex = null">
            <div class="flex items-center justify-between px-5 py-3 bg-brand-600 rounded-t-2xl">
                <h3 class="text-base font-bold text-white"><i class="fas fa-tags mr-2"></i>Fiyat Seçin</h3>
                <button @click="showPriceSelectModal = false; pendingProduct = null; pendingCartIndex = null" class="text-white/70 hover:text-white"><i class="fas fa-times-circle text-lg"></i></button>
            </div>
            <div class="p-4">
                <div class="text-sm font-medium text-gray-900 mb-3" x-text="pendingProduct?.name"></div>
                <div class="space-y-2">
                    {{-- Ana Fiyat --}}
                    <button @click="selectPrice(pendingProduct?.sale_price, 'Standart'); showPriceSelectModal = false"
                            class="w-full py-3 px-4 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 flex items-center justify-between transition-colors">
                        <span class="text-sm font-medium text-gray-700">Standart Fiyat</span>
                        <span class="text-sm font-bold text-blue-600" x-text="formatCurrency(pendingProduct?.sale_price || 0)"></span>
                    </button>
                    {{-- Alternatif Fiyatlar --}}
                    <template x-for="ap in (pendingProduct?.alternative_prices || [])" :key="ap.id">
                        <button @click="selectPrice(ap.price, ap.label); showPriceSelectModal = false"
                                class="w-full py-3 px-4 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 flex items-center justify-between transition-colors">
                            <span class="text-sm font-medium text-gray-700" x-text="ap.label"></span>
                            <span class="text-sm font-bold text-blue-600" x-text="formatCurrency(ap.price)"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- SOL PANEL: Kategoriler + Ürünler --}}
    <div class="flex-1 flex overflow-hidden"
         :class="{ 'hidden lg:flex': mobileTab !== 'products' }">
        {{-- Dikey Kategori Sidebar (tablet+desktop) / Horizontal scroll (mobil) --}}
        <div class="hidden sm:flex w-32 lg:w-44 flex-col bg-white border-r border-gray-200 overflow-y-auto shrink-0">
            <button @click="filterCategory(null); searchQuery = ''"
                    class="px-3 py-3 text-sm font-semibold text-center transition-colors border-b border-gray-100 uppercase tracking-wide"
                    :class="selectedCategory === null ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-blue-50'">>
                <i class="fas fa-star text-xs mr-1"></i>FAVORİLER
            </button>
            @foreach($categories as $cat)
            <button @click="filterCategory({{ $cat->id }})"
                    class="px-3 py-3 text-xs font-medium text-center transition-colors border-b border-gray-100 uppercase tracking-wide"
                    :class="selectedCategory === {{ $cat->id }} ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-blue-50'">
                {{ $cat->name }}
            </button>
            @endforeach
            <template x-for="cat in dynamicCategories" :key="cat.id">
                <button @click="filterCategory(cat.id)"
                        class="px-3 py-3 text-xs font-medium text-center transition-colors border-b border-gray-100 uppercase tracking-wide"
                        :class="selectedCategory === cat.id ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-blue-50'"
                        x-text="cat.name"></button>
            </template>
            {{-- Kategori Ekle --}}
            <button @click="showCatModal = true"
                    class="px-3 py-2.5 text-xs text-green-600 hover:bg-green-50 border-b border-gray-100 flex items-center justify-center gap-1 transition-colors">
                <i class="fas fa-plus"></i> Kategori Ekle
            </button>
        </div>

        {{-- Arama + Ürün Grid --}}
        <div class="flex-1 flex flex-col overflow-hidden bg-gray-50">
            {{-- Mobil yatay kategori strip --}}
            <div class="sm:hidden flex items-center gap-1.5 px-2 py-2 bg-white border-b border-gray-200 overflow-x-auto hide-scrollbar shrink-0">
                <button @click="filterCategory(null); searchQuery = ''"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap transition-colors shrink-0"
                        :class="selectedCategory === null ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'">
                    <i class="fas fa-star text-[10px] mr-0.5"></i>Tümü
                </button>
                @foreach($categories as $cat)
                <button @click="filterCategory({{ $cat->id }})"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap transition-colors shrink-0"
                        :class="selectedCategory === {{ $cat->id }} ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'">
                    {{ $cat->name }}
                </button>
                @endforeach
                <template x-for="cat in dynamicCategories" :key="cat.id">
                    <button @click="filterCategory(cat.id)"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap transition-colors shrink-0"
                            :class="selectedCategory === cat.id ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                            x-text="cat.name"></button>
                </template>
            </div>
            {{-- Arama Bar --}}
            <div class="bg-white border-b border-gray-200 px-3 py-2 flex items-center gap-2 shrink-0">
                <div class="flex-1 relative">
                    <input type="text" x-model="searchQuery" @input.debounce.300ms="searchProducts()"
                           placeholder="Arama yapınız.."
                           class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded text-gray-800 text-sm placeholder-gray-400 focus:outline-none focus:border-blue-400 pr-8">
                    <button @click="searchProducts()" class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-search text-sm"></i>
                    </button>
                </div>
                <button @click="checkPrice()" class="px-2.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-600 border border-amber-200 rounded text-xs font-medium transition-colors whitespace-nowrap" title="Fiyat Gör [F3]">
                    <i class="fas fa-tag mr-1"></i>Fiyat
                </button>
                <button @click="openProductModal()"
                        class="px-2.5 py-2 bg-brand-50 hover:bg-brand-100 text-brand-600 border border-brand-200 rounded text-xs font-medium transition-colors whitespace-nowrap"
                        title="Hızlı ürün ekle">
                    <i class="fas fa-box mr-1"></i>+
                </button>
            </div>

            {{-- Ürün Grid --}}
            <div class="flex-1 overflow-y-auto p-2 sm:p-3">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-1.5 sm:gap-2">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <button @click="handleProductClick(product)"
                                class="bg-white border border-gray-100 rounded-xl p-2 sm:p-3 text-left hover:border-blue-300 hover:shadow-md hover:shadow-blue-100/50 transition-all group active:scale-95">
                            <div class="text-xs sm:text-sm font-medium text-gray-800 group-hover:text-blue-600 truncate" x-text="product.name"></div>
                            <div class="text-[10px] sm:text-xs text-gray-400 mt-0.5 sm:mt-1 truncate" x-text="product.category || ''"></div>
                            <div class="flex items-center justify-between mt-1 sm:mt-2">
                                <span class="text-xs sm:text-sm font-bold text-blue-600" x-text="formatCurrency(product.sale_price)"></span>
                                <span class="text-[10px] sm:text-xs text-gray-400" x-show="!product.is_service" x-text="product.stock_quantity + ' ' + (product.unit || 'Adet')"></span>
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
                    <i class="fas fa-spinner fa-spin text-2xl text-blue-500"></i>
                </div>
            </div>
        </div>
    </div>
    </div>{{-- /paneller satırı --}}

    {{-- Ödeme Butonları - Tam genişlik alt bar --}}
    <div class="grid grid-cols-3 lg:grid-cols-6 shrink-0 gap-3 p-3 bg-gray-100 border-t border-gray-200 safe-bottom">
        <button @click="processPayment('cash')" :disabled="cart.length === 0"
                class="flex flex-col items-center justify-center gap-2 py-5 rounded-2xl text-white font-bold text-sm shadow-md disabled:opacity-40 disabled:cursor-not-allowed transition-all hover:brightness-110 hover:shadow-lg active:scale-95"
                style="background: linear-gradient(135deg, #43b692, #39a583);">
            <i class="fas fa-money-bill-wave text-2xl"></i>
            <span>Nakit</span>
        </button>
        <button @click="processPayment('card')" :disabled="cart.length === 0"
                class="flex flex-col items-center justify-center gap-2 py-5 rounded-2xl text-white font-bold text-sm shadow-md disabled:opacity-40 disabled:cursor-not-allowed transition-all hover:brightness-110 hover:shadow-lg active:scale-95"
                style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
            <i class="fas fa-credit-card text-2xl"></i>
            <span>Kart</span>
        </button>
        <button @click="showMixedPayment = true; mixedRemaining = totals.grand_total" :disabled="cart.length === 0"
                class="flex flex-col items-center justify-center gap-2 py-5 rounded-2xl text-white font-bold text-sm shadow-md disabled:opacity-40 disabled:cursor-not-allowed transition-all hover:brightness-110 hover:shadow-lg active:scale-95"
                style="background: linear-gradient(135deg, #a855f7, #7c3aed);">
            <i class="fas fa-layer-group text-2xl"></i>
            <span>Parçalı</span>
        </button>
        <button @click="processPayment('credit')" :disabled="cart.length === 0 || !selectedCustomer"
                class="flex flex-col items-center justify-center gap-2 py-5 rounded-2xl text-white font-bold text-sm shadow-md disabled:opacity-40 disabled:cursor-not-allowed transition-all hover:brightness-110 hover:shadow-lg active:scale-95"
                style="background: linear-gradient(135deg, #f4a84b, #e8913a);">
            <i class="fas fa-file-invoice-dollar text-2xl"></i>
            <span>Veresiye</span>
        </button>
        {{-- Diğer + Dropdown --}}
        <div class="relative" @click.away="showOtherPayments = false">
            <button @click="showOtherPayments = !showOtherPayments" :disabled="cart.length === 0"
                    class="w-full h-full flex flex-col items-center justify-center gap-2 py-5 rounded-2xl text-white font-bold text-sm shadow-md disabled:opacity-40 disabled:cursor-not-allowed transition-all hover:brightness-110 hover:shadow-lg active:scale-95"
                    style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                <i class="fas fa-ellipsis-h text-2xl"></i>
                <span>Diğer</span>
            </button>
            <div x-show="showOtherPayments" x-transition
                 class="absolute bottom-full left-0 mb-2 bg-white border border-gray-200 rounded-xl shadow-2xl z-30 p-2 space-y-1 min-w-[180px]">
                <div class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider px-2 mb-1">Diğer Ödeme Türleri</div>
                <template x-for="pt in customPaymentTypes" :key="pt.id">
                    <button @click="processPayment('other_' + pt.code); showOtherPayments = false"
                            class="w-full py-2 px-3 bg-gray-50 hover:bg-blue-50 text-gray-700 hover:text-blue-700 text-sm font-medium rounded-lg flex items-center gap-2 transition-colors">
                        <i class="fas fa-circle text-[6px] text-blue-400"></i>
                        <span x-text="pt.name"></span>
                    </button>
                </template>
                <div x-show="customPaymentTypes.length === 0" class="text-center py-3 text-xs text-gray-400">
                    <p>Ödeme türü bulunamadı</p>
                    <a href="{{ route('pos.settings') }}" class="text-brand-500 hover:underline mt-1 inline-block">Ayarlar'dan ekleyin</a>
                </div>
            </div>
        </div>
        <button @click="clearCart()" :disabled="cart.length === 0"
                class="flex flex-col items-center justify-center gap-2 py-5 rounded-2xl text-white font-bold text-sm shadow-md disabled:opacity-40 disabled:cursor-not-allowed transition-all hover:brightness-110 hover:shadow-lg active:scale-95"
                style="background: linear-gradient(135deg, #f87171, #ef4444);">
            <i class="fas fa-trash text-2xl"></i>
            <span>Temizle</span>
        </button>
    </div>

    {{-- Parçalı (Karışık) Ödeme Modal --}}
    <div x-show="showMixedPayment" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl border border-gray-200 p-4 sm:p-6 w-full max-w-md mx-4 shadow-2xl max-h-[90vh] overflow-y-auto" @click.away="showMixedPayment = false">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-gray-900"><i class="fas fa-layer-group mr-2 text-brand-500"></i>Parçalı Ödeme</h3>
                <button @click="showMixedPayment = false" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times"></i></button>
            </div>

            {{-- Toplam & Kalan --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">
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
        <div class="bg-white rounded-2xl border border-gray-200 p-6 w-full max-w-sm mx-4 shadow-2xl" @click.away="closeReceipt()">
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
                <div x-show="(paidAmount || 0) > 0 && lastSale?.payment_method === 'cash'" class="flex justify-between text-sm">
                    <span class="text-gray-500">Ödendi</span>
                    <span class="text-gray-800" x-text="formatCurrency(paidAmount || 0)"></span>
                </div>
                <div x-show="(paidAmount || 0) > (lastSale?.grand_total || 0) && lastSale?.payment_method === 'cash'" class="flex justify-between text-sm font-semibold">
                    <span class="text-emerald-600">Para Üstü</span>
                    <span class="text-emerald-600" x-text="formatCurrency((paidAmount || 0) - (lastSale?.grand_total || 0))"></span>
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
        mobileTab: 'products',
        products: [],
        filteredProducts: [],
        cart: [],
        selectedCategory: null,
        searchQuery: '',
        barcodeQuery: '',
        barcodeResults: [],
        showBarcodeDropdown: false,
        barcodeSelectedIndex: -1,
        barcodeSearching: false,
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
        generalDiscountType: 'TL',
        paidAmount: '',
        showPaymentMenu: false,
        showOtherPayments: false,
        customPaymentTypes: @json($paymentTypes ?? []),
        showQuickCustomerModal: false,
        quickCustomerForm: { name: '', phone: '' },
        quickCustomerSaving: false,
        // İskonto modal
        showDiscountModal: false,
        manualDiscountInput: 0,
        // Son Fişler
        showRecentSales: false,
        recentSalesList: [],
        selectedSaleDetail: null,
        // İade
        showRefundModal: false,
        refundReceiptNo: '',
        refundReason: '',
        refundProcessing: false,
        // Ödeme Al
        showOdemeAlModal: false,
        odemeCustomer: null,
        odemeSearch: '',
        odemeCustomerResults: [],
        odemeCustomerLoading: false,
        odemeType: 'payment',  // 'payment' | 'debt'
        odemePaymentMethod: 'cash',
        odemeDebtType: 'veresiye',
        odemeAmount: '',
        odemeDescription: '',
        odemedSaving: false,
        odemeNewCustomerMode: false,
        odemeNewName: '',
        odemeNewPhone: '',
        odemeNewType: 'individual',
        // Çoklu fiyat seçim
        showPriceSelectModal: false,
        pendingProduct: null,
        pendingCartIndex: null,
        pendingPriceCallback: null,
        // Fiş ayarları
        receiptSettings: @json($receiptSettings),
        isDesktop: window.innerWidth >= 1024,
        panelWidth: 360,
        panelMinWidth: 300,
        panelMaxWidth: 600,
        panelResizing: false,
        panelResizeEnabled: {{ auth()->user()->is_super_admin ? 'true' : 'false' }},
        panelResizeStorageKey: 'pos_cart_width',
        cartStorageKey: 'pos_cart_state',

        init() {
            this.showAllProducts();
            this.initPanelResize();
            this.loadCart();
            this.$refs.searchInput?.focus();
            // Barkod okuyucu için keyboard shortcut
            window.addEventListener('keydown', (e) => {
                if (e.key === 'F2') { e.preventDefault(); this.$refs.searchInput?.focus(); }
                if (e.key === 'F3') { e.preventDefault(); this.checkPrice(); }
                if (e.key === 'F5') { e.preventDefault(); this.processPayment('cash'); }
                if (e.key === 'F6') { e.preventDefault(); this.processPayment('card'); }
                if (e.key === 'Escape') { this.showMixedPayment = false; this.showReceipt = false; this.showDiscountModal = false; this.showRecentSales = false; this.showRefundModal = false; this.showPriceSelectModal = false; this.showOdemeAlModal = false; }
            });
            window.addEventListener('resize', () => {
                this.isDesktop = window.innerWidth >= 1024;
            });
        },

        initPanelResize() {
            if (this.panelResizeEnabled) {
                const saved = localStorage.getItem(this.panelResizeStorageKey);
                if (saved) {
                    this.panelWidth = this.clampPanelWidth(parseInt(saved, 10));
                }
            }

            this.panelResizeMove = (e) => {
                if (!this.panelResizing) return;
                this.panelWidth = this.clampPanelWidth(window.innerWidth - e.clientX);
            };
            this.panelResizeEnd = () => {
                if (!this.panelResizing) return;
                this.panelResizing = false;
                document.body.classList.remove('select-none');
                if (this.panelResizeEnabled) {
                    localStorage.setItem(this.panelResizeStorageKey, String(this.panelWidth));
                }
            };

            window.addEventListener('mousemove', this.panelResizeMove);
            window.addEventListener('mouseup', this.panelResizeEnd);
        },

        clampPanelWidth(value) {
            return Math.max(this.panelMinWidth, Math.min(this.panelMaxWidth, value));
        },

        panelStyle() {
            return this.isDesktop ? `width: ${this.panelWidth}px;` : '';
        },

        startPanelResize() {
            if (!this.panelResizeEnabled) return;
            this.panelResizing = true;
            document.body.classList.add('select-none');
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

        async addByBarcode() {
            const query = this.barcodeQuery.trim();
            if (!query) return;

            let product = this.products.find(p =>
                (p.barcode && p.barcode === query) ||
                (p.name && p.name.toLowerCase() === query.toLowerCase())
            );

            if (!product) {
                try {
                    const data = await posAjax('{{ route("pos.products.search") }}?q=' + encodeURIComponent(query), {}, 'GET');
                    if (Array.isArray(data) && data.length === 1) {
                        product = data[0];
                    } else if (Array.isArray(data) && data.length > 1) {
                        showToast('Birden fazla ürün bulundu, soldan seçiniz.', 'warning');
                        return;
                    }
                } catch (e) {
                    showToast('Ürün bulunamadı.', 'error');
                    return;
                }
            }

            if (!product) {
                showToast('Ürün bulunamadı.', 'error');
                return;
            }

            // Çoklu fiyat varsa modal aç
            if (product.alternative_prices && product.alternative_prices.length > 0) {
                this.pendingProduct = product;
                this.showPriceSelectModal = true;
                this.barcodeQuery = '';
                return;
            }

            this.addToCart(product);
            this.barcodeQuery = '';
            this.showBarcodeDropdown = false;
            this.barcodeResults = [];
            this.barcodeSelectedIndex = -1;
        },

        buildPriceOptions(product) {
            const options = [{ label: 'Standart', price: product.sale_price }];
            if (product.alternative_prices && product.alternative_prices.length > 0) {
                product.alternative_prices.forEach(p => {
                    options.push({ label: p.label, price: p.price });
                });
            }
            options.push({ label: 'Diğer', price: null });
            return options;
        },

        async searchBarcode() {
            const query = this.barcodeQuery.trim();
            if (!query) {
                this.barcodeResults = [];
                this.showBarcodeDropdown = false;
                this.barcodeSelectedIndex = -1;
                return;
            }

            this.barcodeSearching = true;
            try {
                const data = await posAjax('{{ route("pos.products.search") }}?q=' + encodeURIComponent(query), {}, 'GET');
                this.barcodeResults = Array.isArray(data) ? data : [];
                this.showBarcodeDropdown = true;
                this.barcodeSelectedIndex = this.barcodeResults.length > 0 ? 0 : -1;
            } catch (e) {
                this.barcodeResults = [];
                this.showBarcodeDropdown = true;
                this.barcodeSelectedIndex = -1;
            } finally {
                this.barcodeSearching = false;
            }
        },

        handleBarcodeEnter() {
            if (this.showBarcodeDropdown && this.barcodeSelectedIndex >= 0 && this.barcodeResults[this.barcodeSelectedIndex]) {
                this.selectBarcodeResult(this.barcodeResults[this.barcodeSelectedIndex]);
                return;
            }
            this.addByBarcode();
        },

        moveBarcodeSelection(delta) {
            if (!this.showBarcodeDropdown || this.barcodeResults.length === 0) return;
            const next = this.barcodeSelectedIndex + delta;
            this.barcodeSelectedIndex = Math.max(0, Math.min(this.barcodeResults.length - 1, next));
        },

        selectBarcodeResult(product) {
            if (!product) return;
            // Çoklu fiyat varsa modal aç
            if (product.alternative_prices && product.alternative_prices.length > 0) {
                this.pendingProduct = product;
                this.showPriceSelectModal = true;
            } else {
                this.addToCart(product);
            }
            this.barcodeQuery = '';
            this.showBarcodeDropdown = false;
            this.barcodeResults = [];
            this.barcodeSelectedIndex = -1;
        },

        addToCart(product) {
            const priceOptions = this.buildPriceOptions(product);
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
                    price_label: 'Standart',
                    price_options: priceOptions,
                    custom_price: null,
                    quantity: 1,
                    discount: 0,
                    discountType: 'TL',
                    discountAmount: 0,
                    vat_rate: product.vat_rate || 20,
                    vat_amount: 0,
                    additional_tax_amount: 0,
                    total: product.sale_price,
                    showDiscount: false,
                });
                this.recalcItem(this.cart.length - 1);
            }
            this.recalcTotals();
            // Mobilde sepete geçiş
            if (window.innerWidth < 1024) this.mobileTab = 'cart';
        },

        handleProductClick(product) {
            if (product.alternative_prices && product.alternative_prices.length > 0) {
                this.pendingProduct = product;
                this.pendingCartIndex = null;
                this.showPriceSelectModal = true;
                return;
            }
            this.addToCart(product);
        },

        openCartPriceModal(index) {
            const item = this.cart[index];
            if (!item || !item.price_options || item.price_options.length <= 1) {
                item.showDiscount = !item.showDiscount;
                return;
            }
            this.pendingCartIndex = index;
            this.pendingProduct = {
                name: item.product_name,
                sale_price: item.price_options[0]?.price || item.unit_price,
                alternative_prices: item.price_options.slice(1).map(p => ({ label: p.label, price: p.price })),
            };
            this.showPriceSelectModal = true;
        },

        updateQty(index, delta) {
            this.cart[index].quantity = Math.max(0.01, this.cart[index].quantity + delta);
            this.recalcItem(index);
        },

        recalcItem(index) {
            const item = this.cart[index];
            const grossTotal = item.quantity * item.unit_price;
            const discountAmt = item.discountType === '%'
                ? Math.round(grossTotal * (item.discount || 0) / 100 * 100) / 100
                : (item.discount || 0);
            item.discountAmount = discountAmt;
            const lineTotal = grossTotal - discountAmt;
            item.vat_amount = Math.round(lineTotal * item.vat_rate / (100 + item.vat_rate) * 100) / 100;
            item.total = Math.round(lineTotal * 100) / 100;
            this.recalcTotals();
        },

        updatePriceType(index, label) {
            const item = this.cart[index];
            if (!item || !item.price_options) return;
            const selected = item.price_options.find(p => p.label === label);
            if (!selected) return;
            if (label === 'Diğer') {
                item.unit_price = parseFloat(item.custom_price || 0);
            } else {
                item.unit_price = parseFloat(selected.price || 0);
            }
            item.price_label = selected.label;
            this.recalcItem(index);
        },

        recalcTotals() {
            let subtotal = 0, vatTotal = 0, discountTotal = 0;
            this.cart.forEach(item => {
                subtotal += (item.total - item.vat_amount);
                vatTotal += item.vat_amount;
                discountTotal += (item.discountAmount ?? item.discount ?? 0);
            });
            const genDiscAmt = this.generalDiscountType === '%'
                ? Math.round((subtotal + vatTotal) * (this.generalDiscount || 0) / 100 * 100) / 100
                : (this.generalDiscount || 0);
            discountTotal += genDiscAmt;
            this.totals = {
                subtotal: Math.round(subtotal * 100) / 100,
                vat_total: Math.round(vatTotal * 100) / 100,
                discount_total: Math.round(discountTotal * 100) / 100,
                grand_total: Math.round((subtotal + vatTotal - genDiscAmt) * 100) / 100,
            };
            this.saveCart();
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
            this.paidAmount = '';
            this.recalcTotals();
        },

        saveCart() {
            const payload = {
                cart: this.cart,
                selectedCustomer: this.selectedCustomer,
                generalDiscount: this.generalDiscount,
                generalDiscountType: this.generalDiscountType,
            };
            try {
                localStorage.setItem(this.cartStorageKey, JSON.stringify(payload));
            } catch (e) { /* ignore */ }
        },

        loadCart() {
            try {
                const raw = localStorage.getItem(this.cartStorageKey);
                if (!raw) return;
                const data = JSON.parse(raw);
                if (Array.isArray(data.cart)) {
                    this.cart = data.cart;
                }
                if (data.selectedCustomer) {
                    this.selectedCustomer = data.selectedCustomer;
                }
                if (data.generalDiscount !== undefined) {
                    this.generalDiscount = data.generalDiscount;
                }
                if (data.generalDiscountType) {
                    this.generalDiscountType = data.generalDiscountType;
                }
                this.recalcTotals();
            } catch (e) { /* ignore */ }
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

        openQuickProductWithBarcode() {
            this.productForm = {
                name: '',
                sale_price: '',
                category_id: '',
                barcode: this.barcodeQuery.trim(),
                unit: 'Adet',
            };
            this.showProductModal = true;
            this.showBarcodeDropdown = false;
        },

        async saveQuickProduct() {
            if (!this.productForm.name.trim() || !this.productForm.sale_price) return;
            this.productSaving = true;
            try {
                const payload = {
                    name: this.productForm.name,
                    sale_price: this.productForm.sale_price,
                    category_id: this.productForm.category_id || null,
                    barcode: this.productForm.barcode || ('POS' + Date.now().toString().slice(-8)),
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

            // "other_xxx" formatında gelen özel ödeme türleri
            const isOther = method.startsWith('other_');
            const actualMethod = isOther ? method : method;

            // Genel iskonto TL cinsinden hesapla
            const genDiscTL = this.generalDiscountType === '%'
                ? Math.round((this.totals.subtotal + this.totals.vat_total) * (this.generalDiscount || 0) / 100 * 100) / 100
                : (this.generalDiscount || 0);

            const payload = {
                items: this.cart.map(i => ({
                    product_id: i.product_id,
                    product_name: i.product_name,
                    barcode: i.barcode,
                    quantity: i.quantity,
                    unit_price: i.unit_price,
                    discount: i.discountAmount ?? i.discount,
                    vat_rate: i.vat_rate,
                    vat_amount: i.vat_amount,
                    additional_tax_amount: i.additional_tax_amount || 0,
                    total: i.total,
                })),
                payment_method: actualMethod,
                customer_id: this.selectedCustomer?.id,
                discount: genDiscTL,
                cash_amount: method === 'cash' ? this.totals.grand_total : 0,
                card_amount: method === 'card' ? this.totals.grand_total : 0,
                credit_amount: method === 'credit' ? this.totals.grand_total : 0,
                transfer_amount: (method === 'transfer' || isOther) ? this.totals.grand_total : 0,
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
                    // Otomatik yazdırma
                    if (this.receiptSettings.auto_print_receipt) {
                        this.$nextTick(() => this.printReceipt());
                    }
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

            // Genel iskonto TL cinsinden hesapla
            const genDiscTL = this.generalDiscountType === '%'
                ? Math.round((this.totals.subtotal + this.totals.vat_total) * (this.generalDiscount || 0) / 100 * 100) / 100
                : (this.generalDiscount || 0);

            const payload = {
                items: this.cart.map(i => ({
                    product_id: i.product_id,
                    product_name: i.product_name,
                    barcode: i.barcode,
                    quantity: i.quantity,
                    unit_price: i.unit_price,
                    discount: i.discountAmount ?? i.discount,
                    vat_rate: i.vat_rate,
                    vat_amount: i.vat_amount,
                    additional_tax_amount: i.additional_tax_amount || 0,
                    total: i.total,
                })),
                payment_method: 'mixed',
                customer_id: this.selectedCustomer?.id,
                discount: genDiscTL,
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
                    // Otomatik yazdırma
                    if (this.receiptSettings.auto_print_receipt) {
                        this.$nextTick(() => this.printReceipt());
                    }
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
            this.paidAmount = '';
            this.recalcTotals();
            this.mobileTab = 'products';
            this.$refs.searchInput?.focus();
        },

        checkPrice() {
            const barcode = this.barcodeQuery.trim();
            if (!barcode) { showToast('Barkod alanına ürün barkodunu girin', 'warning'); return; }
            const product = this.products.find(p => p.barcode === barcode);
            if (product) {
                showToast(`${product.name} → ${formatCurrency(product.sale_price)} (Stok: ${product.stock_quantity})`, 'success');
            } else {
                showToast('Bu barkod ile ürün bulunamadı', 'error');
            }
        },

        vatByRate(rate) {
            return Math.round(this.cart.reduce((sum, item) =>
                item.vat_rate === rate ? sum + (item.vat_amount || 0) : sum, 0) * 100) / 100;
        },

        async saveQuickCustomer() {
            if (!this.quickCustomerForm.name.trim()) return;
            this.quickCustomerSaving = true;
            try {
                const data = await posAjax('{{ route("pos.customers.store") }}', this.quickCustomerForm);
                if (data.success) {
                    this.selectedCustomer = data.customer;
                    this.showQuickCustomerModal = false;
                    this.quickCustomerForm = { name: '', phone: '' };
                    this.customerSearch = '';
                    this.customerResults = [];
                    this.showCustomerDropdown = false;
                    showToast('Müşteri eklendi!', 'success');
                }
            } catch(e) { showToast(e.message || 'Müşteri eklenemedi.', 'error'); }
            finally { this.quickCustomerSaving = false; }
        },

        printReceipt() {
            // Sepette ürün varsa veya son satış varsa yazdır
            const items = this.cart.length > 0 ? this.cart : (this.lastSale?.items || []);
            if (items.length === 0) {
                showToast('Yazdırılacak ürün yok', 'warning');
                return;
            }

            const isLastSale = this.cart.length === 0 && this.lastSale;
            const receiptNo = isLastSale ? this.lastSale.receipt_no : 'ÖNİZLEME';
            const grandTotal = isLastSale ? this.lastSale.grand_total : this.totals.grand_total;
            const paymentMethod = isLastSale ? this.lastSale.payment_method : '-';
            const now = new Date().toLocaleString('tr-TR');

            let rows = '';
            items.forEach(item => {
                const name = item.product_name || item.name || '';
                const qty = item.quantity || item.qty || 1;
                const price = item.unit_price || item.sale_price || item.price || 0;
                const total = item.total || (qty * price);
                rows += `<tr><td style="text-align:left">${name}</td><td style="text-align:center">${qty}</td><td style="text-align:right">${formatCurrency(price)}</td><td style="text-align:right">${formatCurrency(total)}</td></tr>`;
            });

            const printWindow = window.open('', '_blank', 'width=320,height=600');
            if (!printWindow) {
                showToast('Popup engelleyici aktif! Lütfen bu site için popup izni verin.', 'error');
                return;
            }
            const htmlContent = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>Fiş</title>
            <style>
                body{font-family:'Courier New',monospace;font-size:12px;margin:0;padding:8px;width:280px}
                .center{text-align:center}
                .bold{font-weight:bold}
                .line{border-top:1px dashed #000;margin:6px 0}
                table{width:100%;border-collapse:collapse}
                td{padding:2px 0;font-size:11px}
                .total-row td{font-weight:bold;font-size:13px;padding-top:4px}
                @media print { @page { margin: 2mm; size: 80mm auto; } }
            </style></head><body>
                ${this.receiptSettings.receipt_header ? '<div class="center" style="font-size:10px;white-space:pre-line;margin-bottom:4px">' + this.receiptSettings.receipt_header.replace(/</g,'&lt;') + '</div>' : ''}
                <div class="center bold" style="font-size:14px">{{ config('app.name', 'EMARE POS') }}</div>
                <div class="center" style="font-size:10px">${now}</div>
                <div class="center" style="font-size:10px">Fiş: ${receiptNo}</div>
                <div class="line"></div>
                <table>
                    <tr style="font-weight:bold;border-bottom:1px solid #000"><td>Ürün</td><td style="text-align:center">Ad.</td><td style="text-align:right">Fiyat</td><td style="text-align:right">Tutar</td></tr>
                    ${rows}
                </table>
                <div class="line"></div>
                <table>
                    <tr class="total-row"><td>TOPLAM</td><td colspan="3" style="text-align:right">${formatCurrency(grandTotal)}</td></tr>
                    <tr><td>Ödeme</td><td colspan="3" style="text-align:right;text-transform:capitalize">${paymentMethod}</td></tr>
                </table>
                <div class="line"></div>
                <div class="center" style="font-size:10px;margin-top:8px">${this.receiptSettings.receipt_footer || 'Teşekkür ederiz!'}</div>
            </body></html>`;
            printWindow.document.write(htmlContent);
            printWindow.document.close();
            printWindow.onafterprint = () => printWindow.close();
            setTimeout(() => {
                printWindow.focus();
                printWindow.print();
            }, 300);
        },

        // ---- İskonto Oranı Uygula ----
        applyDiscountRate(rate) {
            this.generalDiscount = rate;
            this.generalDiscountType = '%';
            this.recalcTotals();
            showToast('%' + rate + ' iskonto uygulandı', 'success');
        },

        // ---- Son Fişler ----
        async loadRecentSales() {
            this.showRecentSales = true;
            this.selectedSaleDetail = null;
            try {
                const data = await posAjax('{{ route("pos.sales.recent") }}', {}, 'GET');
                this.recentSalesList = data || [];
            } catch(e) {
                console.error(e);
                showToast('Son fişler yüklenemedi', 'error');
            }
        },

        async openSaleDetail(saleId) {
            try {
                const data = await posAjax('/pos/sale/' + saleId, {}, 'GET');
                this.selectedSaleDetail = data;
            } catch(e) {
                showToast('Fiş detayı yüklenemedi', 'error');
            }
        },

        // ---- İade ----
        startRefund() {
            this.showRefundModal = true;
            this.refundReceiptNo = '';
            this.refundReason = '';
            this.refundProcessing = false;
        },

        async searchAndRefund() {
            if (!this.refundReceiptNo.trim()) return;
            this.refundProcessing = true;
            try {
                const data = await posAjax('{{ route("pos.sales.refund.search") }}', {
                    receipt_no: this.refundReceiptNo.trim(),
                    reason: this.refundReason.trim(),
                });
                if (data.success) {
                    showToast('İade başarıyla gerçekleştirildi! Fiş: ' + data.sale?.receipt_no, 'success');
                    this.showRefundModal = false;
                    this.loadRecentSales();
                } else {
                    showToast(data.message || 'İade işlemi başarısız', 'error');
                }
            } catch(e) {
                showToast(e.message || 'İade işlemi başarısız', 'error');
            } finally {
                this.refundProcessing = false;
            }
        },

        async refundSale(saleId) {
            if (!saleId) return;
            if (!confirm('Bu fişi iade almak istediğinize emin misiniz?')) return;
            try {
                const data = await posAjax('/pos/sale/' + saleId + '/refund', { reason: 'Son fişlerden iade' });
                if (data.success) {
                    showToast('İade başarıyla gerçekleştirildi!', 'success');
                    this.selectedSaleDetail = { ...this.selectedSaleDetail, status: 'refunded' };
                    this.loadRecentSales();
                } else {
                    showToast(data.message || 'İade işlemi başarısız', 'error');
                }
            } catch(e) {
                showToast(e.message || 'İade işlemi başarısız', 'error');
            }
        },

        // ---- Ödeme Al (Hesap İşlemi) ----
        openOdemeAl() {
            this.showOdemeAlModal = true;
            this.odemeSearch = '';
            this.odemeCustomerResults = [];
            this.odemeAmount = '';
            this.odemeDescription = '';
            this.odemeType = 'payment';
            this.odemePaymentMethod = 'cash';
            this.odemeDebtType = 'veresiye';
            this.odemeNewCustomerMode = false;
            this.odemeNewName = '';
            this.odemeNewPhone = '';
            // selectedCustomer oturumda seçiliyse öntanımlı al
            if (this.selectedCustomer) {
                this.odemeCustomer = this.selectedCustomer;
            } else {
                this.odemeCustomer = null;
            }
        },

        async searchOdemeCustomers(q) {
            if (!q || q.length < 2) { this.odemeCustomerResults = []; return; }
            this.odemeCustomerLoading = true;
            try {
                const res = await posAjax('{{ route("pos.customers.search") }}?q=' + encodeURIComponent(q), {}, 'GET');
                this.odemeCustomerResults = res || [];
            } catch(e) {
                this.odemeCustomerResults = [];
            } finally { this.odemeCustomerLoading = false; }
        },

        selectOdemeCustomer(c) {
            this.odemeCustomer = c;
            this.odemeSearch = c.name;
            this.odemeCustomerResults = [];
        },

        async createAndSelectOdemeCustomer() {
            if (!this.odemeNewName.trim()) return;
            try {
                const res = await posAjax('{{ route("pos.customers.store") }}', {
                    name: this.odemeNewName.trim(),
                    phone: this.odemeNewPhone.trim() || null,
                    type: this.odemeNewType,
                });
                if (res.success) {
                    this.odemeCustomer = res.customer;
                    this.odemeNewCustomerMode = false;
                    showToast('Müşteri oluşturuldu: ' + res.customer.name, 'success');
                }
            } catch(e) { showToast(e.message || 'Müşteri oluşturulamadı', 'error'); }
        },

        async submitOdemeAl() {
            if (!this.odemeCustomer || !this.odemeAmount || parseFloat(this.odemeAmount) <= 0) return;
            this.odemedSaving = true;
            try {
                const url = this.odemeType === 'payment'
                    ? '/customers/' + this.odemeCustomer.id + '/payment'
                    : '/customers/' + this.odemeCustomer.id + '/debt';
                const description = this.odemeDescription.trim() ||
                    (this.odemeType === 'payment'
                        ? ('Tahsilat — ' + this.odemePaymentMethod)
                        : ('Borç — ' + this.odemeDebtType));
                const res = await posAjax(url, {
                    amount: parseFloat(this.odemeAmount),
                    description: description,
                    payment_method: this.odemeType === 'payment' ? this.odemePaymentMethod : null,
                });
                if (res.success) {
                    showToast(
                        this.odemeType === 'payment'
                            ? formatCurrency(this.odemeAmount) + ' tahsilat kaydedildi'
                            : formatCurrency(this.odemeAmount) + ' borç kaydedildi',
                        'success'
                    );
                    // Bakiyeyi güncelle
                    if (res.customer) {
                        this.odemeCustomer = { ...this.odemeCustomer, balance: res.customer.balance };
                        if (this.selectedCustomer?.id === this.odemeCustomer.id) {
                            this.selectedCustomer = { ...this.selectedCustomer, balance: res.customer.balance };
                        }
                    }
                    this.odemeAmount = '';
                    this.odemeDescription = '';
                } else {
                    showToast(res.message || 'İşlem başarısız', 'error');
                }
            } catch(e) { showToast(e.message || 'İşlem sırasında hata oluştu', 'error'); }
            finally { this.odemedSaving = false; }
        },

        // ---- Çoklu Fiyat Seçimi (barkod okutunca) ----
        selectPrice(price, label = 'Standart') {
            if (this.pendingCartIndex !== null) {
                const item = this.cart[this.pendingCartIndex];
                if (item) {
                    item.unit_price = parseFloat(price || 0);
                    item.price_label = label;
                    this.recalcItem(this.pendingCartIndex);
                }
                this.pendingCartIndex = null;
                this.pendingProduct = null;
                return;
            }
            if (this.pendingProduct) {
                const product = this.pendingProduct;
                const existing = this.cart.find(i => i.product_id === product.id && i.unit_price === price);
                const priceOptions = this.buildPriceOptions(product);
                if (existing) {
                    existing.quantity++;
                    this.recalcItem(this.cart.indexOf(existing));
                } else {
                    const vatRate = parseFloat(product.vat_rate) || 0;
                    const vatAmount = Math.round(price * vatRate / (100 + vatRate) * 100) / 100;
                    this.cart.unshift({
                        product_id: product.id,
                        product_name: product.name,
                        barcode: product.barcode,
                        unit_price: price,
                        price_label: label,
                        price_options: priceOptions,
                        custom_price: null,
                        quantity: 1,
                        discount: 0,
                        discountType: 'TL',
                        showDiscount: false,
                        discountAmount: 0,
                        vat_rate: vatRate,
                        vat_amount: vatAmount,
                        additional_tax_amount: 0,
                        total: price,
                    });
                }
                this.recalcTotals();
                this.barcodeQuery = '';
                this.pendingProduct = null;
            }
        },
    };
}
</script>
@endpush

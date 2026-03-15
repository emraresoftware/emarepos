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

    {{-- ─── Muhtelif Tutar Modalı ─── --}}
    <div x-show="showMuhtelifModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
         @keydown.escape.window="showMuhtelifModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6 max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-semibold text-gray-900"><i class="fas fa-pen-ruler mr-2 text-sky-500"></i>Muhtelif Tutar Ekle</h3>
                <button @click="showMuhtelifModal = false" class="text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Açıklama *</label>
                    <input type="text" x-model="muhtelifForm.aciklama" @keydown.enter="saveMuhtelifItem()"
                           placeholder="Örn: Paketleme hizmeti, özel sipariş, ekstra servis..."
                           class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tutar *</label>
                    <input type="number" x-model="muhtelifForm.tutar" min="0.01" step="0.01" placeholder="0.00"
                           class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">KDV Oranı</label>
                    <select x-model.number="muhtelifForm.vat_rate"
                            class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-sky-500">
                        <option :value="0">%0</option>
                        <option :value="1">%1</option>
                        <option :value="10">%10</option>
                        <option :value="20">%20</option>
                    </select>
                </div>
                <div class="rounded-xl bg-sky-50 border border-sky-200 px-3 py-3 text-xs text-sky-700">
                    Muhtelif satır sepete tek kalem olarak eklenir. İsterseniz sonrasında satır bazlı iskonto uygulayabilirsiniz.
                </div>
                <div class="flex gap-3 pt-2">
                    <button @click="showMuhtelifModal = false"
                            class="flex-1 px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">İptal</button>
                    <button @click="saveMuhtelifItem()" :disabled="!muhtelifForm.aciklama.trim() || !muhtelifForm.tutar || parseFloat(muhtelifForm.tutar) <= 0"
                            class="flex-1 px-4 py-2 text-sm text-white bg-gradient-to-r from-sky-500 to-cyan-600 rounded-xl hover:opacity-90 disabled:opacity-50 font-medium">
                        <i class="fas fa-plus mr-1"></i> Sepete Ekle
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
    
    {{-- ─── Müşteri Seçim Sayfası (Tam Ekran Modal) ─── --}}
    <div x-show="showCustomerModal" x-cloak
         class="fixed inset-0 z-50 flex flex-col bg-gray-50"
         @keydown.escape.window="showCustomerModal = false"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

        {{-- Üst Bar --}}
        <div class="shrink-0 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <button @click="showCustomerModal = false" class="w-9 h-9 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
                    <i class="fas fa-arrow-left text-gray-600"></i>
                </button>
                <h2 class="text-lg font-bold text-gray-900"><i class="fas fa-users mr-2 text-blue-500"></i>Müşteri Seçimi</h2>
            </div>
            <div class="flex items-center gap-2">
                <button @click="customerModalTab = 'list'" :class="customerModalTab === 'list' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                    <i class="fas fa-list mr-1"></i> Müşteri Listesi
                </button>
                <button @click="customerModalTab = 'new'; $nextTick(() => $refs.newCustName?.focus())" :class="customerModalTab === 'new' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                    <i class="fas fa-user-plus mr-1"></i> Yeni Müşteri
                </button>
            </div>
        </div>

        {{-- TAB: Müşteri Listesi --}}
        <div x-show="customerModalTab === 'list'" class="flex-1 flex flex-col overflow-hidden">
            {{-- Arama --}}
            <div class="shrink-0 px-4 py-3 bg-white border-b border-gray-100">
                <div class="relative max-w-xl mx-auto">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" x-model="customerSearch" x-ref="customerModalSearch"
                           @input.debounce.300ms="searchCustomers(customerSearch)"
                           placeholder="Müşteri adı veya telefon numarası ile arayın..."
                           class="w-full pl-11 pr-10 py-3 border border-gray-200 rounded-2xl text-sm text-gray-800 focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/20 shadow-sm">
                    <button x-show="customerSearch.length > 0" @click="customerSearch = ''; searchCustomers('')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
            </div>
            {{-- Liste --}}
            <div class="flex-1 overflow-y-auto px-4 py-3">
                <div class="max-w-xl mx-auto space-y-2">
                    <template x-for="c in customerResults" :key="c.id">
                        <button @click="selectCustomer(c, { closeModal: true, clearSearch: true })"
                                class="w-full text-left bg-white border border-gray-200 rounded-2xl px-4 py-3 hover:border-blue-400 hover:shadow-md transition-all flex items-center gap-4 group">
                            <div class="w-11 h-11 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-sm" x-text="c.name?.charAt(0)?.toUpperCase()"></div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold text-gray-900 group-hover:text-blue-600 truncate" x-text="c.name"></div>
                                <div class="text-xs text-gray-400 mt-0.5" x-text="c.phone || c.email || 'Telefon bilgisi yok'"></div>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="text-sm font-bold" :class="(c.balance ?? 0) < 0 ? 'text-red-500' : 'text-emerald-500'" x-text="formatCurrency(c.balance ?? 0)"></div>
                                <div class="text-[10px] text-gray-400" x-text="(c.balance ?? 0) < 0 ? 'Borçlu' : 'Alacaklı'"></div>
                            </div>
                            <i class="fas fa-chevron-right text-gray-300 group-hover:text-blue-400 transition-colors shrink-0"></i>
                        </button>
                    </template>
                    <div x-show="customerResults.length === 0 && customerSearch.length > 1" class="text-center py-12">
                        <i class="fas fa-user-slash text-4xl text-gray-300 mb-3"></i>
                        <p class="text-sm text-gray-400">"<span x-text="customerSearch"></span>" ile eşleşen müşteri bulunamadı</p>
                        <button @click="customerModalTab = 'new'; quickCustomerForm.name = customerSearch; $nextTick(() => $refs.newCustName?.focus())"
                                class="mt-3 px-5 py-2.5 bg-green-600 text-white text-sm font-medium rounded-xl hover:bg-green-700 transition-colors">
                            <i class="fas fa-user-plus mr-1"></i> Yeni Müşteri Oluştur
                        </button>
                    </div>
                    <div x-show="customerResults.length === 0 && customerSearch.length <= 1" class="text-center py-12">
                        <i class="fas fa-search text-4xl text-gray-300 mb-3"></i>
                        <p class="text-sm text-gray-400">Müşteri aramak için yazmaya başlayın</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB: Yeni Müşteri --}}
        <div x-show="customerModalTab === 'new'" class="flex-1 overflow-y-auto px-4 py-6">
            <div class="max-w-lg mx-auto">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-5"><i class="fas fa-user-plus mr-2 text-green-500"></i>Yeni Müşteri Kaydı</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wider">Ad Soyad <span class="text-red-500">*</span></label>
                            <input type="text" x-model="quickCustomerForm.name" x-ref="newCustName" @keydown.enter="saveQuickCustomer()"
                                   placeholder="Müşteri adı soyadı..."
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wider">Telefon</label>
                            <input type="tel" x-model="quickCustomerForm.phone" @keydown.enter="saveQuickCustomer()"
                                   placeholder="0532 xxx xx xx"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wider">E-posta</label>
                            <input type="email" x-model="quickCustomerForm.email" @keydown.enter="saveQuickCustomer()"
                                   placeholder="ornek@mail.com"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wider">Adres</label>
                            <textarea x-model="quickCustomerForm.address" rows="2"
                                      placeholder="Adres bilgisi (opsiyonel)"
                                      class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wider">Not</label>
                            <input type="text" x-model="quickCustomerForm.notes"
                                   placeholder="Müşteri hakkında not..."
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20">
                        </div>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button @click="customerModalTab = 'list'; quickCustomerForm = { name: '', phone: '', email: '', address: '', notes: '' }"
                                class="flex-1 px-4 py-3 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors font-medium">
                            <i class="fas fa-arrow-left mr-1"></i> Listeye Dön
                        </button>
                        <button @click="saveQuickCustomer()" :disabled="!quickCustomerForm.name.trim() || quickCustomerSaving"
                                class="flex-1 px-4 py-3 text-sm text-white bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl hover:opacity-90 disabled:opacity-50 font-bold flex items-center justify-center gap-2 shadow-sm">
                            <i class="fas fa-spinner fa-spin" x-show="quickCustomerSaving"></i>
                            <i class="fas fa-check" x-show="!quickCustomerSaving"></i>
                            <span x-text="quickCustomerSaving ? 'Kaydediliyor...' : 'Kaydet & Seç'"></span>
                        </button>
                    </div>
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

        {{-- Genel İndirim + Detaylı Özet Bölümü --}}
        <div class="border-t border-gray-200 px-3 py-2 sm:px-4 sm:py-3 bg-white space-y-2 shrink-0">
            <div x-show="criticalSummaryAlert()" class="rounded-2xl border px-3 py-3 shadow-sm"
                 :class="criticalSummaryAlert()?.tone === 'danger' ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200'">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0"
                         :class="criticalSummaryAlert()?.tone === 'danger' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'">
                        <i :class="criticalSummaryAlert()?.icon"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-bold text-gray-900" x-text="criticalSummaryAlert()?.title"></div>
                        <div class="text-xs text-gray-600 mt-1" x-text="criticalSummaryAlert()?.detail"></div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-gradient-to-br from-slate-50 via-white to-slate-100 p-3 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400">Özet</p>
                        <h4 class="text-sm font-bold text-gray-900">Satış Dökümü</h4>
                    </div>
                    <button @click="showGeneralDiscount = !showGeneralDiscount" class="inline-flex items-center gap-1.5 rounded-xl bg-white px-2.5 py-1.5 text-[11px] font-semibold text-gray-500 shadow-sm ring-1 ring-gray-200 transition hover:text-gray-700">
                        <i class="fas fa-cut text-[11px]"></i>
                        <span>Genel İndirim</span>
                    </button>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2">
                    <div class="rounded-xl bg-white/90 px-3 py-2 ring-1 ring-gray-200">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Kalem</div>
                        <div class="mt-1 text-base font-bold text-gray-900" x-text="cart.length"></div>
                    </div>
                    <div class="rounded-xl bg-white/90 px-3 py-2 ring-1 ring-gray-200">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Toplam Adet</div>
                        <div class="mt-1 text-base font-bold text-gray-900" x-text="cartQuantityTotal()"></div>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold"
                          :class="cart.length ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-200' : 'bg-gray-100 text-gray-500 ring-1 ring-gray-200'">
                        <i class="fas fa-shopping-basket text-[10px]"></i>
                        <span x-text="cart.length ? 'Aktif sepet' : 'Sepet boş'"></span>
                    </span>
                    <span x-show="totals.discount_total > 0" class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700 ring-1 ring-amber-200">
                        <i class="fas fa-badge-percent text-[10px]"></i>
                        <span x-text="'İndirim: ' + formatCurrency(totals.discount_total)"></span>
                    </span>
                    <span x-show="selectedCustomer" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200 max-w-full">
                        <i class="fas fa-user-check text-[10px]"></i>
                        <span class="truncate max-w-[150px]" x-text="selectedCustomer?.name"></span>
                    </span>
                </div>

                <div x-show="showGeneralDiscount" class="mt-3 flex items-center gap-1.5 rounded-xl border border-amber-200 bg-amber-50 px-2 py-2">
                    <input type="number" x-model.number="generalDiscount" @input="recalcTotals()"
                           class="flex-1 min-w-0 px-2.5 py-1.5 bg-white border border-amber-200 rounded-lg text-gray-800 text-xs focus:outline-none focus:border-amber-400"
                           min="0" step="0.01" placeholder="İndirim girin">
                    <button @click="generalDiscountType = generalDiscountType === '%' ? 'TL' : '%'; recalcTotals()"
                            class="px-2.5 py-1.5 text-xs rounded-lg font-bold transition-colors"
                            :class="generalDiscountType === '%' ? 'bg-amber-200 text-amber-700' : 'bg-white text-gray-600 border border-gray-200'"
                            x-text="generalDiscountType === '%' ? '%' : '₺'"></button>
                </div>

                <div class="mt-3 space-y-1.5">
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span>Brüt Sepet</span>
                        <span class="font-semibold text-gray-700" x-text="formatCurrency(grossCartTotal())"></span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span>Ürün İskontosu</span>
                        <span class="font-semibold text-amber-600" x-text="'-' + formatCurrency(itemDiscountTotal())"></span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span>Genel İskonto</span>
                        <span class="font-semibold text-amber-600" x-text="'-' + formatCurrency(generalDiscountAmount())"></span>
                    </div>
                    <div class="flex items-center justify-between text-xs sm:text-sm text-gray-600">
                        <span>Ara Toplam</span>
                        <span class="font-semibold text-gray-800" x-text="formatCurrency(totals.subtotal)"></span>
                    </div>
                    <div class="flex items-center justify-between text-xs sm:text-sm text-gray-600">
                        <span>KDV</span>
                        <span class="font-semibold text-gray-800" x-text="formatCurrency(totals.vat_total)"></span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-1" x-show="totals.vat_total > 0">
                        <div class="rounded-xl bg-white/80 px-2.5 py-2 ring-1 ring-gray-200 text-center">
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">%1 KDV</div>
                            <div class="mt-1 text-xs font-bold text-gray-800" x-text="formatCurrency(vatByRate(1))"></div>
                        </div>
                        <div class="rounded-xl bg-white/80 px-2.5 py-2 ring-1 ring-gray-200 text-center">
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">%10 KDV</div>
                            <div class="mt-1 text-xs font-bold text-gray-800" x-text="formatCurrency(vatByRate(10))"></div>
                        </div>
                        <div class="rounded-xl bg-white/80 px-2.5 py-2 ring-1 ring-gray-200 text-center">
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">%20 KDV</div>
                            <div class="mt-1 text-xs font-bold text-gray-800" x-text="formatCurrency(vatByRate(20))"></div>
                        </div>
                        <div class="rounded-xl bg-white/80 px-2.5 py-2 ring-1 ring-gray-200 text-center">
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">%0 KDV</div>
                            <div class="mt-1 text-xs font-bold text-gray-800" x-text="formatCurrency(vatByRate(0))"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-xs text-gray-500" x-show="selectedCustomer">
                        <span>Müşteri Bakiyesi</span>
                        <span class="font-semibold" :class="(selectedCustomer?.balance ?? 0) < 0 ? 'text-red-500' : 'text-emerald-600'" x-text="formatCurrency(selectedCustomer?.balance ?? 0)"></span>
                    </div>
                </div>

                <div class="mt-3 border-t border-dashed border-gray-200 pt-3">
                    <div class="flex justify-between items-center">
                        <span class="text-base sm:text-lg font-bold text-gray-900">TOPLAM</span>
                        <span class="text-base sm:text-lg font-bold text-red-600" x-text="formatCurrency(totals.grand_total)"></span>
                    </div>
                    <div class="mt-1 flex items-center justify-between text-[11px] text-gray-400" x-show="totals.discount_total > 0">
                        <span>Toplam indirim</span>
                        <span class="font-semibold text-amber-600" x-text="'-' + formatCurrency(totals.discount_total)"></span>
                    </div>
                </div>

                <div class="mt-3 rounded-2xl border border-violet-200 bg-violet-50/80 p-3 space-y-2" x-show="cart.length > 0">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-wider text-violet-500">Kârlılık Özeti</div>
                            <div class="text-xs text-violet-700 mt-0.5">Tahmini maliyet ve brüt kâr görünümü</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold" :class="profitAmount() >= 0 ? 'text-violet-700' : 'text-red-600'" x-text="formatCurrency(profitAmount())"></div>
                            <div class="text-[11px] text-violet-500" x-text="profitMarginLabel()"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="rounded-xl bg-white/90 px-3 py-2 ring-1 ring-violet-100">
                            <div class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold">Tahmini Maliyet</div>
                            <div class="mt-1 text-sm font-bold text-gray-900" x-text="formatCurrency(costAmount())"></div>
                        </div>
                        <div class="rounded-xl bg-white/90 px-3 py-2 ring-1 ring-violet-100">
                            <div class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold">Kâr / Zarar</div>
                            <div class="mt-1 text-sm font-bold" :class="profitAmount() >= 0 ? 'text-emerald-600' : 'text-red-600'" x-text="formatCurrency(profitAmount())"></div>
                        </div>
                    </div>
                    <div class="h-2 rounded-full bg-white/80 overflow-hidden ring-1 ring-violet-100">
                        <div class="h-full rounded-full transition-all"
                             :class="profitAmount() >= 0 ? 'bg-gradient-to-r from-violet-400 to-fuchsia-600' : 'bg-gradient-to-r from-red-400 to-red-600'"
                             :style="'width:' + Math.min(100, Math.max(8, Math.abs(profitMarginPercent()))) + '%'">
                        </div>
                    </div>
                </div>

                <div class="mt-3 rounded-2xl bg-white/90 ring-1 ring-gray-200 p-3 space-y-2" x-show="cart.length > 0">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Mini Sepet Özeti</span>
                        <span class="text-[11px] text-gray-500" x-text="'Ort. Kalem: ' + formatCurrency(averageLineAmount())"></span>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-3 py-2 border border-slate-200" x-show="topCartItem()">
                        <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">En Yüksek Kalem</div>
                        <div class="mt-1 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm font-bold text-slate-900 truncate" x-text="topCartItem()?.product_name || '-' "></div>
                                <div class="text-[11px] text-slate-500" x-text="(topCartItem()?.quantity || 0) + ' adet' "></div>
                            </div>
                            <div class="text-sm font-bold text-slate-900" x-text="formatCurrency(topCartItem()?.total || 0)"></div>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <template x-for="(item, itemIndex) in topCartItems(3)" :key="item.product_id + '-' + itemIndex">
                            <div class="flex items-center justify-between gap-2 text-xs rounded-xl px-2.5 py-2 bg-gray-50 border border-gray-100">
                                <div class="min-w-0 flex-1">
                                    <div class="font-semibold text-gray-800 truncate" x-text="item.product_name"></div>
                                    <div class="text-[11px] text-gray-400" x-text="item.quantity + ' × ' + formatCurrency(item.unit_price)"></div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="font-bold text-gray-900" x-text="formatCurrency(item.total)"></div>
                                    <div class="text-[10px] text-gray-400" x-text="cartShareLabel(item.total)"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-3 space-y-2" x-show="cart.length > 0">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Tahsilat Özeti</span>
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[11px] font-semibold"
                          :class="remainingAmount() > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'"
                          x-text="remainingAmount() > 0 ? 'Eksik ödeme var' : 'Ödeme tamam'"></span>
                </div>
                <div class="space-y-1">
                    <div class="h-2 rounded-full bg-white/80 overflow-hidden ring-1 ring-emerald-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600 transition-all"
                             :style="'width:' + Math.min(100, Math.max(0, ((parseFloat(paidAmount) || 0) / Math.max(totals.grand_total || 1, 1)) * 100)) + '%'">
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-emerald-700/80">
                        <span x-text="'Tahsilat: ' + formatCurrency(parseFloat(paidAmount) || 0)"></span>
                        <span x-text="totals.grand_total > 0 ? Math.min(100, Math.max(0, Math.round(((parseFloat(paidAmount) || 0) / totals.grand_total) * 100))) + '%' : '0%'"></span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 whitespace-nowrap">Ödenen:</span>
                    <input type="number" x-model.number="paidAmount" x-ref="summaryPaidInput"
                           class="flex-1 min-w-0 px-2.5 py-2 bg-white border border-emerald-200 rounded-xl text-gray-800 text-sm focus:outline-none focus:border-emerald-500"
                           min="0" step="0.01" placeholder="Nakit tutar...">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="rounded-xl bg-white px-3 py-2 border border-emerald-100">
                        <div class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold">Kalan</div>
                        <div class="mt-1 text-sm font-bold" :class="remainingAmount() > 0 ? 'text-amber-600' : 'text-emerald-600'" x-text="formatCurrency(remainingAmount())"></div>
                    </div>
                    <div class="rounded-xl bg-white px-3 py-2 border border-emerald-100">
                        <div class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold">Para Üstü</div>
                        <div class="mt-1 text-sm font-bold text-emerald-600" x-text="formatCurrency(changeAmount())"></div>
                    </div>
                </div>

                <div class="rounded-2xl bg-white/90 border border-emerald-100 p-3 space-y-2.5">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">İşlem Önerileri</span>
                        <span class="text-[11px] font-semibold text-emerald-700" x-text="'Önerilen: ' + suggestedPaymentMethodLabel()"></span>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(suggestion, suggestionIndex) in summarySuggestions()" :key="suggestion.label + '-' + suggestionIndex">
                            <div class="rounded-xl px-3 py-2.5 border flex items-start gap-2"
                                 :class="suggestion.tone === 'warning' ? 'bg-amber-50 border-amber-200' : suggestion.tone === 'danger' ? 'bg-red-50 border-red-200' : 'bg-blue-50 border-blue-200'">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center shrink-0"
                                     :class="suggestion.tone === 'warning' ? 'bg-amber-100 text-amber-700' : suggestion.tone === 'danger' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'">
                                    <i :class="suggestion.icon"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-gray-900" x-text="suggestion.label"></div>
                                    <div class="text-[11px] text-gray-600 mt-0.5" x-text="suggestion.detail"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="rounded-2xl p-3 space-y-2.5 border"
                     x-show="cart.length > 0"
                     :class="creditRiskTone() === 'danger' ? 'bg-red-50 border-red-200' : creditRiskTone() === 'warning' ? 'bg-amber-50 border-amber-200' : 'bg-sky-50 border-sky-200'">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Veresiye Risk Analizi</span>
                            <div class="text-xs font-bold text-gray-900 mt-0.5" x-text="selectedCustomer ? selectedCustomer.name : 'Müşteri seçilmedi'"></div>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[11px] font-semibold"
                              :class="creditRiskTone() === 'danger' ? 'bg-red-100 text-red-700' : creditRiskTone() === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-700'"
                              x-text="creditRiskLabel()"></span>
                    </div>

                    <div x-show="selectedCustomer" class="grid grid-cols-2 gap-2">
                        <div class="rounded-xl bg-white/85 px-3 py-2 ring-1 ring-black/5">
                            <div class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold">Mevcut Bakiye</div>
                            <div class="mt-1 text-sm font-bold" :class="(selectedCustomer?.balance ?? 0) < 0 ? 'text-red-600' : 'text-emerald-600'" x-text="formatCurrency(selectedCustomer?.balance ?? 0)"></div>
                        </div>
                        <div class="rounded-xl bg-white/85 px-3 py-2 ring-1 ring-black/5">
                            <div class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold">Kredi Limiti</div>
                            <div class="mt-1 text-sm font-bold text-gray-900" x-text="customerCreditLimitLabel()"></div>
                        </div>
                        <div class="rounded-xl bg-white/85 px-3 py-2 ring-1 ring-black/5">
                            <div class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold">Veresiye Sonrası</div>
                            <div class="mt-1 text-sm font-bold" :class="projectedCustomerBalanceAfterCredit() < 0 ? 'text-red-600' : 'text-emerald-600'" x-text="formatCurrency(projectedCustomerBalanceAfterCredit())"></div>
                        </div>
                        <div class="rounded-xl bg-white/85 px-3 py-2 ring-1 ring-black/5">
                            <div class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold">Limit Kullanımı</div>
                            <div class="mt-1 text-sm font-bold" :class="creditUsagePercent() >= 100 ? 'text-red-600' : creditUsagePercent() >= 80 ? 'text-amber-600' : 'text-sky-700'" x-text="creditUsageLabel()"></div>
                        </div>
                    </div>

                    <div class="space-y-2" x-show="selectedCustomer">
                        <div class="h-2 rounded-full bg-white/80 overflow-hidden ring-1 ring-black/5">
                            <div class="h-full rounded-full transition-all"
                                 :class="creditRiskTone() === 'danger' ? 'bg-gradient-to-r from-red-400 to-red-600' : creditRiskTone() === 'warning' ? 'bg-gradient-to-r from-amber-400 to-amber-600' : 'bg-gradient-to-r from-sky-400 to-sky-600'"
                                 :style="'width:' + Math.min(100, Math.max(0, creditUsagePercent())) + '%'">
                            </div>
                        </div>
                        <template x-for="(warning, warningIndex) in creditRiskWarnings()" :key="warning + '-' + warningIndex">
                            <div class="rounded-xl bg-white/85 px-3 py-2 text-[11px] text-gray-700 ring-1 ring-black/5" x-text="warning"></div>
                        </template>
                    </div>

                    <div x-show="!selectedCustomer" class="rounded-xl bg-white/85 px-3 py-2 text-[11px] text-gray-700 ring-1 ring-black/5">
                        Veresiye satış ihtimali varsa önce müşteri seçin. Seçilmeden limit, bakiye ve risk hesaplaması yapılamaz.
                    </div>

                    <div class="rounded-2xl bg-white/85 px-3 py-3 ring-1 ring-black/5 space-y-2" x-show="riskActionButtons().length > 0">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Önerilen Aksiyonlar</div>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <template x-for="(action, actionIndex) in riskActionButtons()" :key="action.label + '-' + actionIndex">
                                <button @click="runRiskAction(action.action)"
                                        class="rounded-xl px-3 py-2.5 text-left border transition-all"
                                        :class="action.tone === 'danger' ? 'bg-red-50 border-red-200 hover:bg-red-100' : action.tone === 'warning' ? 'bg-amber-50 border-amber-200 hover:bg-amber-100' : 'bg-blue-50 border-blue-200 hover:bg-blue-100'">
                                    <div class="flex items-start gap-2">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"
                                             :class="action.tone === 'danger' ? 'bg-red-100 text-red-700' : action.tone === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700'">
                                            <i :class="action.icon"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-gray-900" x-text="action.label"></div>
                                            <div class="text-[11px] text-gray-600 mt-0.5" x-text="action.detail"></div>
                                        </div>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Müşteri Seçimi --}}
        <div class="border-t border-gray-200 shrink-0">
            {{-- Seçilmemiş → Buton --}}
            <button x-show="!selectedCustomer"
                    @click="showCustomerModal = true; customerModalTab = 'list'; $nextTick(() => { searchCustomers(''); $refs.customerModalSearch?.focus(); })"
                    class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium flex items-center justify-center gap-2 transition-colors">
                <i class="fas fa-user-circle text-base"></i> Müşteri Seçiniz
            </button>
            {{-- Seçildi --}}
            <div x-show="selectedCustomer" class="flex items-center gap-2 px-3 py-2.5 bg-blue-600">
                <i class="fas fa-user-check text-white text-sm shrink-0"></i>
                <span class="flex-1 text-sm text-white font-medium truncate cursor-pointer" x-text="selectedCustomer?.name" @click="showCustomerModal = true; customerModalTab = 'list'"></span>
                <span class="text-xs text-blue-200 whitespace-nowrap" :class="(selectedCustomer?.balance ?? 0) < 0 ? 'text-red-300' : 'text-blue-200'" x-text="formatCurrency(selectedCustomer?.balance ?? 0)"></span>
                <button @click="selectedCustomer = null; customerSearch = ''" class="text-blue-200 hover:text-white transition-colors shrink-0">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
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
                <h3 class="text-base font-bold text-white"><i class="fas fa-hand-holding-usd mr-2"></i>Hızlı Ödeme / Hesap İşlemi</h3>
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
                            <div class="text-[10px] text-gray-400" x-text="odemeCustomerBalanceStatus()"></div>
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
                    <div x-show="odemeType === 'payment' && odemeTahsilatBorctanDusulen() > 0" class="flex justify-between text-sm">
                        <span class="text-gray-500">Borçtan Düşecek</span>
                        <span class="text-emerald-600 font-medium" x-text="formatCurrency(odemeTahsilatBorctanDusulen())"></span>
                    </div>
                    <div x-show="odemeType === 'payment' && odemeTahsilatBakiyeyeEklenen() > 0" class="flex justify-between text-sm">
                        <span class="text-gray-500">Bakiyeye Eklenecek</span>
                        <span class="text-sky-600 font-medium" x-text="formatCurrency(odemeTahsilatBakiyeyeEklenen())"></span>
                    </div>
                    <div x-show="odemeType === 'payment'" class="rounded-lg px-2.5 py-2 text-xs"
                         :class="odemeTahsilatBakiyeyeEklenen() > 0 ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'"
                         x-text="odemeTahsilatBakiyeyeEklenen() > 0 ? 'Ödeme önce borcu kapatacak, kalan tutar müşterinin alacak bakiyesine eklenecek.' : 'Ödeme doğrudan mevcut borçtan düşülecek.'"></div>
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
                        <span x-text="odemedSaving ? 'Kaydediliyor...' : (odemeType === 'payment' ? 'Hızlı Ödemeyi Kaydet' : 'Borcu Kaydet')"></span>
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

    {{-- Alt Aksiyon Alanı --}}
    <div class="shrink-0 border-t border-gray-200 bg-white px-3 pt-3 pb-4 safe-bottom shadow-[0_-6px_20px_rgba(15,23,42,0.06)]">
        <div class="grid grid-cols-3 lg:grid-cols-6 gap-3">
            <button @click="cart.length ? processPayment('cash') : showToast('Önce sepete ürün ekleyin.', 'warning')"
                    class="flex flex-col items-center justify-center gap-2 pt-4 pb-3 min-h-[96px] rounded-full text-white font-bold text-sm shadow-sm transition-all hover:-translate-y-0.5 hover:brightness-110 hover:shadow-lg active:translate-y-0"
                    style="background: linear-gradient(135deg, #43b692, #39a583);">
                <span class="w-9 h-9 rounded-full bg-white/18 inline-flex items-center justify-center backdrop-blur-sm shadow-inner shadow-white/10 shrink-0">
                    <i class="fas fa-money-bill-wave text-lg leading-none"></i>
                </span>
                <span class="leading-none">Nakit</span>
            </button>
            <button @click="cart.length ? processPayment('card') : showToast('Önce sepete ürün ekleyin.', 'warning')"
                    class="flex flex-col items-center justify-center gap-2 pt-4 pb-3 min-h-[96px] rounded-full text-white font-bold text-sm shadow-sm transition-all hover:-translate-y-0.5 hover:brightness-110 hover:shadow-lg active:translate-y-0"
                    style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                <span class="w-9 h-9 rounded-full bg-white/18 inline-flex items-center justify-center backdrop-blur-sm shadow-inner shadow-white/10 shrink-0">
                    <i class="fas fa-credit-card text-lg leading-none"></i>
                </span>
                <span class="leading-none">Kart</span>
            </button>
                <button @click="cart.length ? openMixedPayment() : showToast('Önce sepete ürün ekleyin.', 'warning')"
                    class="flex flex-col items-center justify-center gap-2 pt-4 pb-3 min-h-[96px] rounded-full text-white font-bold text-sm shadow-sm transition-all hover:-translate-y-0.5 hover:brightness-110 hover:shadow-lg active:translate-y-0"
                    style="background: linear-gradient(135deg, #a855f7, #7c3aed);">
                <span class="w-9 h-9 rounded-full bg-white/18 inline-flex items-center justify-center backdrop-blur-sm shadow-inner shadow-white/10 shrink-0">
                    <i class="fas fa-layer-group text-lg leading-none"></i>
                </span>
                <span class="leading-none">Parçalı</span>
            </button>
                <button @click="handleCreditPayment()"
                    class="flex flex-col items-center justify-center gap-2 pt-4 pb-3 min-h-[96px] rounded-full text-white font-bold text-sm shadow-sm transition-all hover:-translate-y-0.5 hover:brightness-110 hover:shadow-lg active:translate-y-0"
                    :class="!cart.length || creditSaleBlocked(this.totals.grand_total) ? 'opacity-55' : ''"
                    style="background: linear-gradient(135deg, #f4a84b, #e8913a);">
                <span class="w-9 h-9 rounded-full bg-white/18 inline-flex items-center justify-center backdrop-blur-sm shadow-inner shadow-white/10 shrink-0">
                    <i class="fas fa-file-invoice-dollar text-lg leading-none"></i>
                </span>
                <span class="leading-none">Veresiye</span>
            </button>
            <div class="relative" @click.away="showOtherPayments = false">
                <button @click="cart.length ? showOtherPayments = !showOtherPayments : showToast('Önce sepete ürün ekleyin.', 'warning')"
                        class="w-full h-full flex flex-col items-center justify-center gap-2 pt-4 pb-3 min-h-[96px] rounded-full text-white font-bold text-sm shadow-sm transition-all hover:-translate-y-0.5 hover:brightness-110 hover:shadow-lg active:translate-y-0"
                        :class="!cart.length ? 'opacity-55' : ''"
                        style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                    <span class="w-9 h-9 rounded-full bg-white/18 inline-flex items-center justify-center backdrop-blur-sm shadow-inner shadow-white/10 shrink-0">
                        <i class="fas fa-ellipsis-h text-lg leading-none"></i>
                    </span>
                    <span class="leading-none">Diğer</span>
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
            <button @click="cart.length ? clearCart() : showToast('Sepet zaten boş.', 'warning')"
                    class="flex flex-col items-center justify-center gap-2 pt-4 pb-3 min-h-[96px] rounded-full text-white font-bold text-sm shadow-sm transition-all hover:-translate-y-0.5 hover:brightness-110 hover:shadow-lg active:translate-y-0"
                    :class="!cart.length ? 'opacity-55' : ''"
                    style="background: linear-gradient(135deg, #f87171, #ef4444);">
                <span class="w-9 h-9 rounded-full bg-white/18 inline-flex items-center justify-center backdrop-blur-sm shadow-inner shadow-white/10 shrink-0">
                    <i class="fas fa-trash text-lg leading-none"></i>
                </span>
                <span class="leading-none">Temizle</span>
            </button>
        </div>

        <div class="grid grid-cols-3 lg:grid-cols-6 gap-3 mt-3">
            <button @click="loadRecentSales()"
                    class="flex flex-col items-center justify-center gap-2 pt-4 pb-3 min-h-[92px] rounded-full text-white font-bold text-sm shadow-sm transition-all hover:-translate-y-0.5 hover:brightness-110 hover:shadow-lg active:translate-y-0"
                    style="background: linear-gradient(135deg, #64748b, #475569);">
                <span class="w-9 h-9 rounded-full bg-white/16 inline-flex items-center justify-center backdrop-blur-sm shadow-inner shadow-white/10 shrink-0">
                    <i class="fas fa-receipt text-lg leading-none"></i>
                </span>
                <span class="leading-none">Son Fişler</span>
            </button>
            <button @click="showDiscountModal = true"
                    class="flex flex-col items-center justify-center gap-2 pt-4 pb-3 min-h-[92px] rounded-full text-white font-bold text-sm shadow-sm transition-all hover:-translate-y-0.5 hover:brightness-110 hover:shadow-lg active:translate-y-0"
                    style="background: linear-gradient(135deg, #0ea5e9, #0284c7);">
                <span class="w-9 h-9 rounded-full bg-white/16 inline-flex items-center justify-center backdrop-blur-sm shadow-inner shadow-white/10 shrink-0">
                    <i class="fas fa-percent text-lg leading-none"></i>
                </span>
                <span class="leading-none">İskonto</span>
            </button>
            <button @click="printReceipt()"
                    class="flex flex-col items-center justify-center gap-2 pt-4 pb-3 min-h-[92px] rounded-full text-white font-bold text-sm shadow-sm transition-all hover:-translate-y-0.5 hover:brightness-110 hover:shadow-lg active:translate-y-0"
                    style="background: linear-gradient(135deg, #64748b, #475569);">
                <span class="w-9 h-9 rounded-full bg-white/16 inline-flex items-center justify-center backdrop-blur-sm shadow-inner shadow-white/10 shrink-0">
                    <i class="fas fa-print text-lg leading-none"></i>
                </span>
                <span class="leading-none">Yazdır</span>
            </button>
            <button @click="startRefund()"
                    class="flex flex-col items-center justify-center gap-2 pt-4 pb-3 min-h-[92px] rounded-full text-white font-bold text-sm shadow-sm transition-all hover:-translate-y-0.5 hover:brightness-110 hover:shadow-lg active:translate-y-0"
                    style="background: linear-gradient(135deg, #f97316, #ea580c);">
                <span class="w-9 h-9 rounded-full bg-white/16 inline-flex items-center justify-center backdrop-blur-sm shadow-inner shadow-white/10 shrink-0">
                    <i class="fas fa-undo text-lg leading-none"></i>
                </span>
                <span class="leading-none">İade</span>
            </button>
            <button @click="openOdemeAl()"
                    class="flex flex-col items-center justify-center gap-2 pt-4 pb-3 min-h-[92px] rounded-full text-white font-bold text-sm shadow-sm transition-all hover:-translate-y-0.5 hover:brightness-110 hover:shadow-lg active:translate-y-0"
                    style="background: linear-gradient(135deg, #10b981, #059669);">
                <span class="w-9 h-9 rounded-full bg-white/16 inline-flex items-center justify-center backdrop-blur-sm shadow-inner shadow-white/10 shrink-0">
                    <i class="fas fa-hand-holding-usd text-lg leading-none"></i>
                </span>
                <span class="leading-none">Ödeme Al</span>
            </button>
            <button @click="openMuhtelifModal()"
                    class="flex flex-col items-center justify-center gap-2 pt-4 pb-3 min-h-[92px] rounded-full text-white font-bold text-sm shadow-sm transition-all hover:-translate-y-0.5 hover:brightness-110 hover:shadow-lg active:translate-y-0"
                    style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                <span class="w-9 h-9 rounded-full bg-white/16 inline-flex items-center justify-center backdrop-blur-sm shadow-inner shadow-white/10 shrink-0">
                    <i class="fas fa-pen-ruler text-lg leading-none"></i>
                </span>
                <span class="leading-none">Muhtelif</span>
            </button>
        </div>
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
                                <button @click="selectCustomer(c, { setSearch: true, closeDropdown: true })"
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

                <div x-show="selectedCustomer" class="mt-3 rounded-2xl border px-3 py-3"
                     :class="creditSaleBlocked(mixedCredit) ? 'border-red-200 bg-red-50/80' : 'border-amber-200 bg-amber-50/70'">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Veresiye Limit Özeti</div>
                            <div class="text-sm font-bold text-gray-900 mt-1" x-text="selectedCustomer?.name"></div>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold"
                              :class="creditSaleBlocked(mixedCredit) ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'"
                              x-text="creditSaleBlocked(mixedCredit) ? 'Bloklu' : (mixedCredit > 0 ? 'Kontrol Edildi' : 'Hazır')"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 mt-3">
                        <div class="rounded-xl bg-white/80 px-3 py-2 ring-1 ring-black/5">
                            <div class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold">Mevcut Borç</div>
                            <div class="mt-1 text-sm font-bold text-red-600" x-text="formatCurrency(-customerDebtAmount())"></div>
                        </div>
                        <div class="rounded-xl bg-white/80 px-3 py-2 ring-1 ring-black/5">
                            <div class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold">Kullanılabilir Limit</div>
                            <div class="mt-1 text-sm font-bold text-gray-900" x-text="availableCreditLimitLabel()"></div>
                        </div>
                        <div class="rounded-xl bg-white/80 px-3 py-2 ring-1 ring-black/5">
                            <div class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold">Veresiye Sonrası Borç</div>
                            <div class="mt-1 text-sm font-bold"
                                 :class="creditSaleBlocked(mixedCredit) ? 'text-red-600' : 'text-amber-700'"
                                 x-text="formatCurrency(-projectedCustomerDebtAfterCreditForAmount(mixedCredit))"></div>
                        </div>
                        <div class="rounded-xl bg-white/80 px-3 py-2 ring-1 ring-black/5">
                            <div class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold">Limit Kullanımı</div>
                            <div class="mt-1 text-sm font-bold text-gray-900" x-text="creditUsageLabelForAmount(mixedCredit)"></div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="h-2 rounded-full bg-white/80 overflow-hidden ring-1 ring-black/5">
                            <div class="h-full rounded-full transition-all"
                                 :class="creditSaleBlocked(mixedCredit) ? 'bg-red-500' : 'bg-amber-400'"
                                 :style="`width: ${Math.min(100, Math.max(0, creditUsagePercentForAmount(mixedCredit)))}%`"></div>
                        </div>
                        <div class="mt-2 text-[11px]"
                             :class="creditSaleBlocked(mixedCredit) ? 'text-red-600' : 'text-gray-500'"
                             x-text="creditSaleBlocked(mixedCredit) ? creditSaleBlockMessage(mixedCredit) : 'Veresiye payı mevcut limite göre uygun görünüyor.'"></div>
                    </div>
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
                        :disabled="Math.abs(mixedRemaining) > 0.01 || creditSaleBlocked(mixedCredit)"
                        class="flex-1 py-2.5 bg-gradient-to-r from-brand-500 to-purple-600 text-white hover:shadow-lg hover:shadow-brand-200 rounded-xl text-sm font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-check-circle mr-1"></i>Ödemeyi Tamamla
                </button>
            </div>
            <p x-show="creditSaleBlocked(mixedCredit)" class="text-xs text-red-500 text-center mt-2" x-text="creditSaleBlockMessage(mixedCredit)"></p>
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
        showMuhtelifModal: false,
        muhtelifForm: { aciklama: '', tutar: '', vat_rate: 20 },
        generalDiscountType: 'TL',
        paidAmount: '',
        showPaymentMenu: false,
        showOtherPayments: false,
        customPaymentTypes: @json($paymentTypes ?? []),
        showCustomerModal: false,
        customerModalTab: 'list',
        quickCustomerForm: { name: '', phone: '', email: '', address: '', notes: '' },
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
                    purchase_price: parseFloat(product.purchase_price || 0),
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
            item.unit_price = parseFloat(selected.price || 0);
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

        cartQuantityTotal() {
            return this.cart.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0), 0);
        },

        grossCartTotal() {
            return Math.round(this.cart.reduce((sum, item) => {
                return sum + ((parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0));
            }, 0) * 100) / 100;
        },

        itemDiscountTotal() {
            return Math.round(this.cart.reduce((sum, item) => {
                return sum + (parseFloat(item.discountAmount) || 0);
            }, 0) * 100) / 100;
        },

        generalDiscountAmount() {
            const baseTotal = (this.totals.subtotal || 0) + (this.totals.vat_total || 0);
            if (!this.generalDiscount) return 0;

            const amount = this.generalDiscountType === '%'
                ? (baseTotal * (this.generalDiscount || 0) / 100)
                : (this.generalDiscount || 0);

            return Math.round(amount * 100) / 100;
        },

        remainingAmount() {
            return Math.max(0, Math.round(((this.totals.grand_total || 0) - (parseFloat(this.paidAmount) || 0)) * 100) / 100);
        },

        changeAmount() {
            return Math.max(0, Math.round(((parseFloat(this.paidAmount) || 0) - (this.totals.grand_total || 0)) * 100) / 100);
        },

        averageLineAmount() {
            if (!this.cart.length) return 0;
            return Math.round(((this.totals.grand_total || 0) / this.cart.length) * 100) / 100;
        },

        topCartItems(limit = 3) {
            return [...this.cart]
                .sort((left, right) => (parseFloat(right.total) || 0) - (parseFloat(left.total) || 0))
                .slice(0, limit);
        },

        topCartItem() {
            return this.topCartItems(1)[0] || null;
        },

        cartShareLabel(amount) {
            if (!(this.totals.grand_total > 0)) return '%0 pay';
            const ratio = Math.round(((parseFloat(amount) || 0) / this.totals.grand_total) * 100);
            return '%' + ratio + ' pay';
        },

        suggestedPaymentMethodLabel() {
            if (!this.cart.length) return 'İşlem yok';
            if (this.changeAmount() > 0) return 'Nakit / Para Üstü';
            if (this.remainingAmount() === 0 && (parseFloat(this.paidAmount) || 0) > 0) return 'Nakit Tahsilat';
            if (this.selectedCustomer && (this.selectedCustomer.balance ?? 0) < 0) return 'Tahsilat Öncelikli';
            if (this.totals.grand_total >= 1000) return 'Kart veya Havale';
            if (this.cartQuantityTotal() >= 8) return 'Parçalı Ödeme';
            return 'Nakit';
        },

        summarySuggestions() {
            const suggestions = [];
            const paidAmount = parseFloat(this.paidAmount) || 0;
            const customerBalance = this.selectedCustomer?.balance ?? 0;

            if (this.remainingAmount() > 0 && paidAmount > 0) {
                suggestions.push({
                    tone: 'warning',
                    icon: 'fas fa-money-check-dollar text-xs',
                    label: 'Eksik tahsilat',
                    detail: 'Kasada ' + formatCurrency(this.remainingAmount()) + ' daha tahsil edilmesi gerekiyor.',
                });
            }

            if (this.changeAmount() > 0) {
                suggestions.push({
                    tone: 'info',
                    icon: 'fas fa-arrow-rotate-left text-xs',
                    label: 'Para üstü hazırla',
                    detail: 'Müşteriye ' + formatCurrency(this.changeAmount()) + ' para üstü verilecek.',
                });
            }

            if (this.selectedCustomer && customerBalance < 0) {
                suggestions.push({
                    tone: 'danger',
                    icon: 'fas fa-triangle-exclamation text-xs',
                    label: 'Müşteri borçlu',
                    detail: 'Seçili müşterinin mevcut bakiyesi ' + formatCurrency(customerBalance) + '. Yeni veresiye öncesi kontrol et.',
                });
            } else if (!this.selectedCustomer && this.totals.grand_total >= 500) {
                suggestions.push({
                    tone: 'info',
                    icon: 'fas fa-user-plus text-xs',
                    label: 'Müşteri ilişkilendir',
                    detail: 'Bu tutardaki satışları müşteriye bağlamak takip ve rapor için faydalı olur.',
                });
            }

            if (this.cartQuantityTotal() >= 8 || this.totals.grand_total >= 1500) {
                suggestions.push({
                    tone: 'warning',
                    icon: 'fas fa-layer-group text-xs',
                    label: 'Parçalı ödeme uygun',
                    detail: 'Yüksek tutar veya yoğun sepet nedeniyle parçalı ödeme seçeneği daha kontrollü olabilir.',
                });
            }

            if (this.totals.discount_total > 0) {
                suggestions.push({
                    tone: 'info',
                    icon: 'fas fa-badge-percent text-xs',
                    label: 'İskonto uygulandı',
                    detail: 'Toplam iskonto ' + formatCurrency(this.totals.discount_total) + '. Satış öncesi indirim kontrolünü doğrula.',
                });
            }

            if (!suggestions.length) {
                suggestions.push({
                    tone: 'info',
                    icon: 'fas fa-circle-check text-xs',
                    label: 'İşlem hazır',
                    detail: 'Sepet ve tahsilat görünümü normal. Uygun ödeme türü ile satış tamamlanabilir.',
                });
            }

            return suggestions.slice(0, 3);
        },

        customerDebtAmount() {
            const balance = parseFloat(this.selectedCustomer?.balance) || 0;
            return balance < 0 ? Math.abs(balance) : 0;
        },

        customerCreditLimit() {
            return Math.max(0, parseFloat(this.selectedCustomer?.credit_limit) || 0);
        },

        customerCreditLimitLabel() {
            return this.customerCreditLimit() > 0 ? formatCurrency(this.customerCreditLimit()) : 'Limitsiz';
        },

        availableCreditLimit() {
            const limit = this.customerCreditLimit();
            if (limit <= 0) return null;
            return Math.max(0, Math.round((limit - this.customerDebtAmount()) * 100) / 100);
        },

        availableCreditLimitLabel() {
            const available = this.availableCreditLimit();
            return available === null ? 'Limitsiz' : formatCurrency(available);
        },

        projectedCustomerBalanceAfterCreditForAmount(creditAmount = null) {
            const currentBalance = parseFloat(this.selectedCustomer?.balance) || 0;
            const amount = Math.max(0, parseFloat(creditAmount ?? this.totals.grand_total) || 0);
            return Math.round((currentBalance - amount) * 100) / 100;
        },

        projectedCustomerBalanceAfterCredit() {
            return this.projectedCustomerBalanceAfterCreditForAmount(this.totals.grand_total || 0);
        },

        projectedCustomerDebtAfterCreditForAmount(creditAmount = null) {
            const projectedBalance = this.projectedCustomerBalanceAfterCreditForAmount(creditAmount);
            return projectedBalance < 0 ? Math.abs(projectedBalance) : 0;
        },

        projectedCustomerDebtAfterCredit() {
            return this.projectedCustomerDebtAfterCreditForAmount(this.totals.grand_total || 0);
        },

        creditUsagePercentForAmount(creditAmount = null) {
            const limit = this.customerCreditLimit();
            if (!this.selectedCustomer) return 0;
            if (limit <= 0) return this.projectedCustomerDebtAfterCreditForAmount(creditAmount) > 0 ? 100 : 0;
            return Math.round((this.projectedCustomerDebtAfterCreditForAmount(creditAmount) / limit) * 100);
        },

        creditUsagePercent() {
            return this.creditUsagePercentForAmount(this.totals.grand_total || 0);
        },

        creditUsageLabelForAmount(creditAmount = null) {
            const limit = this.customerCreditLimit();
            if (!this.selectedCustomer) return 'Müşteri yok';
            if (limit <= 0) return this.projectedCustomerDebtAfterCreditForAmount(creditAmount) > 0 ? 'Limitsiz / borç oluşur' : 'Limitsiz';
            return '%' + this.creditUsagePercentForAmount(creditAmount) + ' kullanım';
        },

        creditUsageLabel() {
            const limit = this.customerCreditLimit();
            if (!this.selectedCustomer) return 'Müşteri yok';
            if (limit <= 0) return this.projectedCustomerDebtAfterCredit() > 0 ? 'Limitsiz / borç oluşur' : 'Limitsiz';
            return '%' + this.creditUsagePercent() + ' kullanım';
        },

        creditRiskTone() {
            if (!this.selectedCustomer) return this.totals.grand_total >= 500 ? 'warning' : 'info';
            const usage = this.creditUsagePercent();
            const currentDebt = this.customerDebtAmount();
            if ((this.customerCreditLimit() > 0 && usage >= 100) || currentDebt >= Math.max(this.customerCreditLimit(), 1)) return 'danger';
            if (usage >= 80 || currentDebt > 0) return 'warning';
            return 'info';
        },

        creditRiskLabel() {
            const tone = this.creditRiskTone();
            if (tone === 'danger') return 'Yüksek Risk';
            if (tone === 'warning') return 'Dikkat';
            return 'Kontrollü';
        },

        creditRiskWarnings() {
            const warnings = [];

            if (!this.selectedCustomer) {
                warnings.push('Müşteri seçilmeden veresiye projeksiyonu yapılamaz.');
                if ((this.totals.grand_total || 0) >= 500) {
                    warnings.push('Yüksek tutarlı satışlarda müşteri seçmeden ilerlemek takip zafiyeti yaratır.');
                }
                return warnings;
            }

            const limit = this.customerCreditLimit();
            const currentDebt = this.customerDebtAmount();
            const projectedDebt = this.projectedCustomerDebtAfterCredit();

            if (currentDebt > 0) {
                warnings.push('Müşterinin mevcut borcu ' + formatCurrency(-currentDebt) + '.');
            }

            if (limit > 0) {
                const available = Math.max(0, limit - currentDebt);
                warnings.push('Kullanılabilir limit ' + formatCurrency(available) + '.');
                if (projectedDebt > limit) {
                    warnings.push('Bu satış sonrası limit ' + formatCurrency(projectedDebt - limit) + ' aşılmış olacak.');
                } else if (projectedDebt > limit * 0.8) {
                    warnings.push('Bu satış sonrası limitin kritik eşiğine yaklaşılacak.');
                }
            } else if (projectedDebt > 0) {
                warnings.push('Müşteride kredi limiti tanımlı değil; borç kontrolü manuel takip gerektirir.');
            }

            if (!warnings.length) {
                warnings.push('Bu satış için veresiye riski düşük görünüyor.');
            }

            return warnings.slice(0, 3);
        },

        creditSaleBlocked(creditAmount = null) {
            const amount = Math.max(0, parseFloat(creditAmount) || 0);
            if (amount <= 0) return false;
            if (!this.selectedCustomer) return true;

            const limit = this.customerCreditLimit();
            if (limit <= 0) return false;

            return this.projectedCustomerDebtAfterCreditForAmount(amount) > limit;
        },

        creditSaleBlockMessage(creditAmount = null) {
            const amount = Math.max(0, parseFloat(creditAmount) || 0);
            if (amount <= 0) return '';
            if (!this.selectedCustomer) return 'Veresiye için müşteri seçimi zorunludur.';

            const limit = this.customerCreditLimit();
            if (limit <= 0) return '';

            const projectedDebt = this.projectedCustomerDebtAfterCreditForAmount(amount);
            if (projectedDebt <= limit) return '';

            return 'Kredi limiti aşılır. Limit ' + formatCurrency(limit) + ', satış sonrası borç ' + formatCurrency(projectedDebt) + '.';
        },

        selectCustomer(customer, options = {}) {
            this.selectedCustomer = customer;

            if (options.closeModal) {
                this.showCustomerModal = false;
            }

            if (options.clearSearch) {
                this.customerSearch = '';
                this.customerResults = [];
            }

            if (options.setSearch) {
                this.customerSearch = customer?.name || '';
            }

            if (options.closeDropdown) {
                this.showCustomerDropdown = false;
            }

            this.saveCart();
            showToast((customer?.name || 'Müşteri') + ' seçildi', 'success');
        },

        openMixedPayment() {
            this.showMixedPayment = true;
            this.mixedRemaining = this.totals.grand_total;
            this.recalcMixedRemaining();
        },

        handleCreditPayment() {
            if (!this.cart.length) {
                showToast('Önce sepete ürün ekleyin.', 'warning');
                return;
            }

            const blockedMessage = this.creditSaleBlockMessage(this.totals.grand_total);
            if (blockedMessage) {
                showToast(blockedMessage, 'error');
                return;
            }

            this.processPayment('credit');
        },

        riskActionButtons() {
            const actions = [];

            if (!this.selectedCustomer && (this.totals.grand_total || 0) >= 500) {
                actions.push({
                    tone: 'warning',
                    icon: 'fas fa-user-plus text-xs',
                    label: 'Müşteri seç',
                    detail: 'Yüksek tutarlı işlem için müşteriyi satışa bağla.',
                    action: 'select-customer',
                });
            }

            if (this.remainingAmount() > 0) {
                actions.push({
                    tone: 'info',
                    icon: 'fas fa-money-bill-wave text-xs',
                    label: 'Tahsilatı tamamla',
                    detail: 'Ödenen alanına geçip kalan ' + formatCurrency(this.remainingAmount()) + ' tutarı gir.',
                    action: 'focus-paid',
                });
            }

            if (this.creditRiskTone() !== 'info' && this.cart.length > 0) {
                actions.push({
                    tone: this.creditRiskTone(),
                    icon: 'fas fa-layer-group text-xs',
                    label: 'Parçalı ödeme aç',
                    detail: 'Riskli tutarı nakit/kart/veresiye karışımıyla daha kontrollü böl.',
                    action: 'open-mixed',
                });
            }

            if (this.selectedCustomer && this.customerDebtAmount() > 0) {
                actions.push({
                    tone: 'danger',
                    icon: 'fas fa-hand-holding-dollar text-xs',
                    label: 'Ödeme al ekranı',
                    detail: 'Önce mevcut borç için tahsilat girişi yapmayı değerlendir.',
                    action: 'open-odeme-al',
                });
            }

            return actions.slice(0, 4);
        },

        runRiskAction(action) {
            if (action === 'select-customer') {
                this.showCustomerModal = true;
                this.customerModalTab = 'list';
                this.$nextTick(() => {
                    this.searchCustomers('');
                    this.$refs.customerModalSearch?.focus();
                });
                return;
            }

            if (action === 'focus-paid') {
                this.$nextTick(() => this.$refs.summaryPaidInput?.focus());
                return;
            }

            if (action === 'open-mixed') {
                this.openMixedPayment();
                return;
            }

            if (action === 'open-odeme-al') {
                this.openOdemeAl();
            }
        },

        costAmount() {
            return Math.round(this.cart.reduce((sum, item) => {
                return sum + ((parseFloat(item.purchase_price) || 0) * (parseFloat(item.quantity) || 0));
            }, 0) * 100) / 100;
        },

        profitAmount() {
            return Math.round(((this.totals.grand_total || 0) - this.costAmount()) * 100) / 100;
        },

        profitMarginPercent() {
            if (!(this.totals.grand_total > 0)) return 0;
            return Math.round((this.profitAmount() / this.totals.grand_total) * 100);
        },

        profitMarginLabel() {
            return '%' + this.profitMarginPercent() + ' marj';
        },

        criticalSummaryAlert() {
            if (!this.cart.length) return null;

            if (this.creditRiskTone() === 'danger') {
                return {
                    tone: 'danger',
                    icon: 'fas fa-shield-halved text-sm',
                    title: 'Veresiye riski kritik seviyede',
                    detail: 'Bu satış mevcut müşteri borç/limit görünümünü tehlikeli seviyeye taşıyor. Tahsilat veya parçalı ödeme öncelikli düşünülmeli.',
                };
            }

            if (!this.selectedCustomer && (this.totals.grand_total || 0) >= 1000) {
                return {
                    tone: 'warning',
                    icon: 'fas fa-user-lock text-sm',
                    title: 'Yüksek tutarlı satış müşteri olmadan ilerliyor',
                    detail: 'Takip ve tahsilat güvenliği için satış tamamlanmadan önce müşteri ilişkilendirmesi önerilir.',
                };
            }

            if (this.remainingAmount() > 0 && (parseFloat(this.paidAmount) || 0) > 0) {
                return {
                    tone: 'warning',
                    icon: 'fas fa-cash-register text-sm',
                    title: 'Tahsilat eksik',
                    detail: 'Satış tamamlanmadan önce kalan ' + formatCurrency(this.remainingAmount()) + ' tutarın kapatılması gerekiyor.',
                };
            }

            if (this.profitAmount() < 0) {
                return {
                    tone: 'danger',
                    icon: 'fas fa-chart-line-down text-sm',
                    title: 'Satış zarar gösteriyor',
                    detail: 'Tahmini maliyet, satış toplamını aşıyor. Fiyat veya iskonto kontrol edilmeli.',
                };
            }

            return null;
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

        normalizeCartItem(item) {
            if (!item || typeof item !== 'object') return item;

            const normalized = { ...item };
            const priceOptions = Array.isArray(normalized.price_options)
                ? normalized.price_options.filter(option => option && option.label !== 'Diğer')
                : [];

            normalized.price_options = priceOptions;
            normalized.custom_price = null;
            normalized.purchase_price = parseFloat(normalized.purchase_price || 0);

            const activeOption = priceOptions.find(option => option.label === normalized.price_label);
            if (normalized.price_label === 'Diğer' || !activeOption) {
                const fallbackOption = priceOptions[0];
                if (fallbackOption) {
                    normalized.price_label = fallbackOption.label;
                    normalized.unit_price = parseFloat(fallbackOption.price || 0);
                } else {
                    normalized.price_label = 'Standart';
                }
            }

            if ((!normalized.unit_price || Number(normalized.unit_price) <= 0) && priceOptions[0]) {
                normalized.unit_price = parseFloat(priceOptions[0].price || 0);
            }

            return normalized;
        },

        loadCart() {
            try {
                const raw = localStorage.getItem(this.cartStorageKey);
                if (!raw) return;
                const data = JSON.parse(raw);
                if (Array.isArray(data.cart)) {
                    this.cart = data.cart.map(item => this.normalizeCartItem(item));
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
                this.saveCart();
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

        openMuhtelifModal() {
            this.muhtelifForm = { aciklama: '', tutar: '', vat_rate: 20 };
            this.showMuhtelifModal = true;
            this.$nextTick(() => this.$el.querySelector('[x-model="muhtelifForm.aciklama"]')?.focus());
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
                        purchase_price: parseFloat(p.purchase_price || 0),
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

        saveMuhtelifItem() {
            const aciklama = this.muhtelifForm.aciklama.trim();
            const tutar = parseFloat(this.muhtelifForm.tutar);
            const vatRate = parseFloat(this.muhtelifForm.vat_rate) || 0;

            if (!aciklama || !(tutar > 0)) {
                return;
            }

            this.cart.unshift({
                product_id: null,
                product_name: 'Muhtelif - ' + aciklama,
                barcode: null,
                unit_price: tutar,
                purchase_price: 0,
                price_label: 'Standart',
                price_options: [{ label: 'Standart', price: tutar }],
                custom_price: null,
                quantity: 1,
                discount: 0,
                discountType: 'TL',
                discountAmount: 0,
                vat_rate: vatRate,
                vat_amount: 0,
                additional_tax_amount: 0,
                total: tutar,
                showDiscount: false,
            });

            this.recalcItem(0);
            this.showMuhtelifModal = false;
            this.muhtelifForm = { aciklama: '', tutar: '', vat_rate: 20 };
            showToast('Muhtelif tutar sepete eklendi.', 'success');
            if (window.innerWidth < 1024) this.mobileTab = 'cart';
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
            if (method === 'credit') {
                const blockedMessage = this.creditSaleBlockMessage(this.totals.grand_total);
                if (blockedMessage) {
                    showToast(blockedMessage, 'error');
                    return;
                }
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
            if (this.mixedCredit > 0) {
                const blockedMessage = this.creditSaleBlockMessage(this.mixedCredit);
                if (blockedMessage) {
                    showToast(blockedMessage, 'error');
                    return;
                }
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
                    this.showCustomerModal = false;
                    this.quickCustomerForm = { name: '', phone: '', email: '', address: '', notes: '' };
                    this.customerSearch = '';
                    this.customerResults = [];
                    this.customerModalTab = 'list';
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

        odemeCustomerDebtAmount() {
            const balance = parseFloat(this.odemeCustomer?.balance) || 0;
            return balance < 0 ? Math.abs(balance) : 0;
        },

        odemeCustomerBalanceStatus() {
            const balance = parseFloat(this.odemeCustomer?.balance) || 0;
            if (balance < 0) return 'Borçlu';
            if (balance > 0) return 'Alacaklı';
            return 'Bakiye 0';
        },

        odemeTahsilatBorctanDusulen() {
            if (this.odemeType !== 'payment') return 0;
            const amount = Math.max(0, parseFloat(this.odemeAmount) || 0);
            return Math.min(amount, this.odemeCustomerDebtAmount());
        },

        odemeTahsilatBakiyeyeEklenen() {
            if (this.odemeType !== 'payment') return 0;
            const amount = Math.max(0, parseFloat(this.odemeAmount) || 0);
            return Math.max(0, Math.round((amount - this.odemeTahsilatBorctanDusulen()) * 100) / 100);
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
                const isTahsilat = this.odemeType === 'payment';
                const islemTutari = parseFloat(this.odemeAmount);
                const oncekiBakiye = parseFloat(this.odemeCustomer?.balance) || 0;
                const url = this.odemeType === 'payment'
                    ? '/customers/' + this.odemeCustomer.id + '/payment'
                    : '/customers/' + this.odemeCustomer.id + '/debt';
                const description = this.odemeDescription.trim() ||
                    (this.odemeType === 'payment'
                        ? ('Tahsilat — ' + this.odemePaymentMethod)
                        : ('Borç — ' + this.odemeDebtType));
                const res = await posAjax(url, {
                    amount: islemTutari,
                    description: description,
                    payment_method: this.odemeType === 'payment' ? this.odemePaymentMethod : null,
                });
                if (res.success) {
                    if (isTahsilat) {
                        const dusulenBorc = Math.min(islemTutari, Math.max(0, oncekiBakiye < 0 ? Math.abs(oncekiBakiye) : 0));
                        const eklenenBakiye = Math.max(0, Math.round((islemTutari - dusulenBorc) * 100) / 100);

                        showToast(
                            eklenenBakiye > 0
                                ? formatCurrency(dusulenBorc) + ' borçtan düştü, ' + formatCurrency(eklenenBakiye) + ' bakiyeye eklendi'
                                : formatCurrency(islemTutari) + ' tahsilat kaydedildi',
                            'success'
                        );
                    } else {
                        showToast(formatCurrency(islemTutari) + ' borç kaydedildi', 'success');
                    }
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
                        purchase_price: parseFloat(product.purchase_price || 0),
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

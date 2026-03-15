@extends('pos.layouts.app')

@section('title', 'Şubeler')

@section('content')
<div class="p-6 overflow-y-auto h-full" x-data="branchManager()" x-cloak>

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Şubeler</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $branches->count() }} şube kayıtlı</p>
        </div>
        <button @click="openCreate()"
                class="bg-gradient-to-r from-brand-500 to-purple-600 text-white font-medium rounded-lg text-sm px-5 py-2.5 transition-colors flex items-center gap-2">
            <i class="fas fa-plus"></i> Yeni Şube
        </button>
    </div>

    {{-- Branch Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse($branches as $branch)
            <div class="bg-white rounded-xl border border-gray-100 p-5 hover:border-brand-500/30 hover:shadow-sm transition-all group cursor-pointer"
                 @click="openDetail({{ json_encode([
                     'id'                => $branch->id,
                     'name'              => $branch->name,
                     'code'              => $branch->code,
                     'address'           => $branch->address,
                     'phone'             => $branch->phone,
                     'city'              => $branch->city,
                     'district'          => $branch->district,
                     'is_active'         => $branch->is_active,
                     'is_center'         => (bool)($branch->settings['is_center'] ?? false),
                     'price_edit_locked' => (bool)($branch->settings['price_edit_locked'] ?? false),
                 ]) }})">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg {{ $branch->is_active ? 'bg-emerald-50' : 'bg-gray-100' }} flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-store {{ $branch->is_active ? 'text-emerald-500' : 'text-gray-400' }}"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $branch->name }}</h3>
                            @if($branch->code)
                                <span class="text-xs text-gray-400 font-mono">{{ $branch->code }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        @if(!$branch->is_active)
                            <span class="text-[10px] bg-red-50 text-red-500 px-2 py-0.5 rounded-full">Pasif</span>
                        @endif
                        @if((bool)($branch->settings['is_center'] ?? false))
                            <span class="text-[10px] bg-brand-50 text-brand-600 px-2 py-0.5 rounded-full">Merkez</span>
                        @endif
                    </div>
                </div>
                @if($branch->address || $branch->city)
                    <p class="text-xs text-gray-500 mb-2 truncate">
                        <i class="fas fa-map-marker-alt mr-1 text-gray-400"></i>
                        {{ trim(($branch->address ?? '') . ($branch->city ? ' · ' . $branch->city : '')) }}
                    </p>
                @endif
                @if($branch->phone)
                    <p class="text-xs text-gray-500 mb-3"><i class="fas fa-phone mr-1 text-gray-400"></i>{{ $branch->phone }}</p>
                @endif
                <div class="grid grid-cols-3 gap-2 pt-3 border-t border-gray-100">
                    <div class="text-center">
                        <p class="text-base font-bold text-gray-800">{{ $branch->users_count }}</p>
                        <p class="text-[10px] text-gray-400">Kullanıcı</p>
                    </div>
                    <div class="text-center">
                        <p class="text-base font-bold text-gray-800">{{ $branch->restaurant_tables_count }}</p>
                        <p class="text-[10px] text-gray-400">Masa</p>
                    </div>
                    <div class="text-center">
                        <p class="text-base font-bold text-gray-800">{{ $branch->cash_registers_count }}</p>
                        <p class="text-[10px] text-gray-400">Kasa</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-16 text-gray-400">
                <i class="fas fa-store text-5xl mb-3 block"></i>
                <p class="text-sm">Henüz şube eklenmemiş.</p>
            </div>
        @endforelse
    </div>

    {{-- ═══════════════════════════════════
         OLUŞTURMA MODALI
    ═══════════════════════════════════ --}}
    <div x-show="showCreateModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showCreateModal = false"></div>
        <div class="relative bg-white rounded-xl border border-gray-100 shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" x-transition>
            <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900">Yeni Şube</h2>
                <button @click="showCreateModal = false" class="p-2 text-gray-400 hover:text-gray-700 rounded-lg"><i class="fas fa-times"></i></button>
            </div>
            <form @submit.prevent="submitCreate()" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Şube Adı <span class="text-red-500">*</span></label>
                        <input type="text" x-model="createForm.name" required class="w-full border border-gray-200 text-gray-900 text-sm rounded-lg px-3 py-2.5">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Şube Kodu</label>
                        <input type="text" x-model="createForm.code" placeholder="SBE-01" class="w-full border border-gray-200 text-gray-900 text-sm rounded-lg px-3 py-2.5">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Adres</label>
                    <textarea x-model="createForm.address" rows="2" class="w-full border border-gray-200 text-gray-900 text-sm rounded-lg px-3 py-2.5 resize-none"></textarea>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Telefon</label>
                        <input type="tel" x-model="createForm.phone" class="w-full border border-gray-200 text-gray-900 text-sm rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">İl</label>
                        <input type="text" x-model="createForm.city" class="w-full border border-gray-200 text-gray-900 text-sm rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">İlçe</label>
                        <input type="text" x-model="createForm.district" class="w-full border border-gray-200 text-gray-900 text-sm rounded-lg px-3 py-2">
                    </div>
                </div>
                <div class="flex gap-6">
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" x-model="createForm.is_center" class="rounded text-brand-500 border-gray-300 w-4 h-4"> Merkez şube
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" x-model="createForm.price_edit_locked" class="rounded text-brand-500 border-gray-300 w-4 h-4"> Fiyat kilidi
                    </label>
                </div>
                <div class="flex gap-3 pt-2 border-t border-gray-100">
                    <button type="button" @click="showCreateModal = false" class="flex-1 px-4 py-2.5 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg">İptal</button>
                    <button type="submit" :disabled="createSaving" class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-brand-500 to-purple-600 rounded-lg disabled:opacity-50">
                        <span x-text="createSaving ? 'Kaydediliyor...' : 'Oluştur'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════════════════════════
         ŞUBE DETAY MODALI (Tablar)
    ═══════════════════════════════════ --}}
    <div x-show="showDetailModal" x-transition.opacity class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-2 sm:p-4 overflow-hidden" style="display:none;">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showDetailModal = false"></div>
        <div class="relative bg-white w-full max-w-5xl rounded-2xl border border-gray-100 shadow-2xl flex flex-col overflow-hidden m-auto min-h-0"
             style="height: calc(100vh - 1rem); max-height: calc(100vh - 1rem);"
             x-transition @click.stop>

            {{-- Modal Başlık --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-lg bg-brand-500/10 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-store text-brand-500"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-base font-bold text-gray-900 truncate" x-text="detail.name"></h2>
                        <p class="text-xs text-gray-400" x-text="detail.code ? '#' + detail.code : 'Şube'"></p>
                    </div>
                </div>
                <button @click="showDetailModal = false" class="p-2 text-gray-400 hover:text-gray-700 rounded-lg flex-shrink-0">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Tab Bar --}}
            <div class="flex items-center gap-0.5 px-4 pt-2 flex-shrink-0 border-b border-gray-100 overflow-x-auto" style="scrollbar-width:none">
                <template x-for="tab in ['rapor','urunler','personel','moduller','cihazlar','duzenle']" :key="tab">
                    <button @click="switchDetailTab(tab)"
                            :class="detailTab===tab ? 'text-brand-600 border-b-2 border-brand-500' : 'text-gray-500 hover:text-gray-800 border-b-2 border-transparent'"
                            class="px-4 py-2.5 text-sm font-medium transition-colors whitespace-nowrap -mb-px">
                        <span x-text="{'rapor':'Rapor','urunler':'Ürünler','personel':'Personel','moduller':'Modüller','cihazlar':'Cihazlar','duzenle':'Düzenle'}[tab]"></span>
                    </button>
                </template>
            </div>

            {{-- Tab İçerikleri --}}
            <div class="flex-1 overflow-y-auto min-h-0" style="overscroll-behavior: contain; -webkit-overflow-scrolling: touch;">

                {{-- ── RAPOR ── --}}
                <div x-show="detailTab==='rapor'" class="p-5 space-y-5">
                    {{-- Tarih filtresi --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                            <template x-for="p in [{k:'today',l:'Bugün'},{k:'7',l:'7 Gün'},{k:'30',l:'30 Gün'},{k:'month',l:'Bu Ay'}]" :key="p.k">
                                <button @click="setReportPeriod(p.k)"
                                        :class="reportPeriod===p.k ? 'bg-white shadow text-gray-900' : 'text-gray-500'"
                                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-all"
                                        x-text="p.l"></button>
                            </template>
                        </div>
                        <div class="flex items-center gap-2 ml-auto flex-wrap">
                            <input type="date" x-model="reportFrom" class="border border-gray-200 rounded-lg text-xs px-3 py-2 bg-white text-gray-700">
                            <span class="text-gray-400">—</span>
                            <input type="date" x-model="reportTo" class="border border-gray-200 rounded-lg text-xs px-3 py-2 bg-white text-gray-700">
                            <button @click="loadReport()" :disabled="reportLoading"
                                    class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white text-xs font-medium rounded-lg disabled:opacity-50">
                                <i class="fas fa-search mr-1"></i>Getir
                            </button>
                        </div>
                    </div>

                    <div x-show="reportLoading" class="text-center py-12 text-gray-400">
                        <i class="fas fa-spinner fa-spin text-2xl"></i>
                        <p class="text-sm mt-2">Yükleniyor...</p>
                    </div>

                    <div x-show="!reportLoading && reportData" class="space-y-4">
                        {{-- KPI --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4">
                                <p class="text-[11px] text-emerald-600 font-medium">Toplam Ciro</p>
                                <p class="text-xl font-bold text-emerald-700 mt-1" x-text="fmt(reportData.kpi.totalRevenue)"></p>
                                <p class="text-[11px] text-emerald-500 mt-0.5" x-text="(reportData.kpi.totalCount||0) + ' satış'"></p>
                            </div>
                            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                                <p class="text-[11px] text-blue-600 font-medium">Ort. Fiş</p>
                                <p class="text-xl font-bold text-blue-700 mt-1" x-text="fmt(reportData.kpi.avgTicket)"></p>
                            </div>
                            <div class="bg-amber-50 border border-amber-100 rounded-xl p-4">
                                <p class="text-[11px] text-amber-600 font-medium">İndirim</p>
                                <p class="text-xl font-bold text-amber-700 mt-1" x-text="fmt(reportData.kpi.totalDiscount)"></p>
                            </div>
                            <div class="bg-purple-50 border border-purple-100 rounded-xl p-4">
                                <p class="text-[11px] text-purple-600 font-medium">Veresiye</p>
                                <p class="text-xl font-bold text-purple-700 mt-1" x-text="fmt(reportData.kpi.creditTotal)"></p>
                            </div>
                        </div>

                        {{-- Ödeme Yöntemleri --}}
                        <div class="bg-white border border-gray-100 rounded-xl p-4">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Ödeme Yöntemleri</h3>
                            <div class="space-y-2.5">
                                <template x-for="pm in reportData.payments" :key="pm.method">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs text-gray-500 w-16 text-right" x-text="pm.method"></span>
                                        <div class="flex-1 bg-gray-100 rounded-full h-2">
                                            <div class="h-2 rounded-full bg-gradient-to-r from-brand-500 to-purple-500 transition-all duration-700"
                                                 :style="'width:' + (reportData.kpi.totalRevenue>0 ? Math.round((pm.total/reportData.kpi.totalRevenue)*100) : 0) + '%'"></div>
                                        </div>
                                        <span class="text-xs font-semibold text-gray-700 w-24 text-right" x-text="fmt(pm.total)"></span>
                                        <span class="text-[10px] text-gray-400 w-8"
                                              x-text="reportData.kpi.totalRevenue>0 ? Math.round((pm.total/reportData.kpi.totalRevenue)*100)+'%' : '0%'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Günlük Çubuk Grafik --}}
                        <div x-show="reportData.daily && reportData.daily.length > 0" class="bg-white border border-gray-100 rounded-xl p-4">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Günlük Satış</h3>
                            <div class="flex items-end gap-0.5 h-28 overflow-x-auto pb-2">
                                <template x-for="d in reportData.daily" :key="d.day">
                                    <div class="flex flex-col items-center gap-1 flex-shrink-0" style="min-width:26px">
                                        <span class="text-[9px] text-gray-400" x-text="fmtShort(d.revenue)"></span>
                                        <div class="w-4 bg-brand-500/80 rounded-t transition-all"
                                             :style="'height:' + (maxDaily>0 ? Math.max(3,Math.round((d.revenue/maxDaily)*72)) : 3) + 'px'"
                                             :title="d.day + ': ' + fmt(d.revenue)"></div>
                                        <span class="text-[9px] text-gray-400" x-text="d.day.slice(5)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Top Ürünler & Müşteriler --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-white border border-gray-100 rounded-xl p-4">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                                    <i class="fas fa-box-open text-brand-400 mr-1.5"></i>En Çok Satan Ürünler
                                </h3>
                                <p x-show="!reportData.top_products.length" class="text-xs text-gray-400 py-4 text-center">Veri yok</p>
                                <div class="space-y-2">
                                    <template x-for="(p,i) in reportData.top_products" :key="p.product_id">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold text-gray-400 w-5 text-right" x-text="i+1+'.'"></span>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs text-gray-800 font-medium truncate" x-text="p.name"></p>
                                                <p class="text-[10px] text-gray-400" x-text="p.total_qty + ' adet'"></p>
                                            </div>
                                            <span class="text-xs font-bold text-gray-700 shrink-0" x-text="fmt(p.total_revenue)"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <div class="bg-white border border-gray-100 rounded-xl p-4">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                                    <i class="fas fa-users text-emerald-400 mr-1.5"></i>En Çok Alışveriş Yapan
                                </h3>
                                <p x-show="!reportData.top_customers.length" class="text-xs text-gray-400 py-4 text-center">Veri yok</p>
                                <div class="space-y-2">
                                    <template x-for="(c,i) in reportData.top_customers" :key="c.customer_id">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold text-gray-400 w-5 text-right" x-text="i+1+'.'"></span>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs text-gray-800 font-medium truncate" x-text="c.name"></p>
                                                <p class="text-[10px] text-gray-400" x-text="c.sale_count + ' satış'"></p>
                                            </div>
                                            <span class="text-xs font-bold text-gray-700 shrink-0" x-text="fmt(c.total_revenue)"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── ÜRÜNLER ── --}}
                <div x-show="detailTab==='urunler'" class="p-5">
                    <div x-show="reportLoading" class="text-center py-10 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i></div>
                    <div x-show="!reportLoading">
                        <div class="flex items-center justify-between mb-3 gap-3">
                            <p class="text-sm text-gray-500" x-text="filteredBranchProducts.length + ' / ' + branchProducts.length + ' ürün'"></p>
                            <input type="text" x-model="productSearch" placeholder="Ara..."
                                   class="border border-gray-200 rounded-lg text-xs px-3 py-2 w-44 bg-white">
                        </div>
                        <div class="overflow-x-auto rounded-xl border border-gray-100">
                            <table class="w-full text-xs">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2.5 text-left text-gray-500 font-medium">Ürün</th>
                                        <th class="px-3 py-2.5 text-left text-gray-500 font-medium">Kategori</th>
                                        <th class="px-3 py-2.5 text-left text-gray-500 font-medium">Barkod</th>
                                        <th class="px-3 py-2.5 text-right text-gray-500 font-medium">Stok</th>
                                        <th class="px-3 py-2.5 text-right text-gray-500 font-medium">Satış Fiyatı</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="p in filteredBranchProducts" :key="p.id">
                                        <tr class="border-t border-gray-50 hover:bg-gray-50">
                                            <td class="px-3 py-2.5 font-medium text-gray-800" x-text="p.name"></td>
                                            <td class="px-3 py-2.5 text-gray-500" x-text="p.category || '—'"></td>
                                            <td class="px-3 py-2.5 font-mono text-gray-400" x-text="p.barcode || '—'"></td>
                                            <td class="px-3 py-2.5 text-right font-semibold"
                                                :class="p.stock<=0 ? 'text-red-500' : p.stock<5 ? 'text-amber-500' : 'text-gray-700'"
                                                x-text="p.stock"></td>
                                            <td class="px-3 py-2.5 text-right font-semibold text-gray-800" x-text="fmt(p.sale_price)"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="filteredBranchProducts.length===0">
                                        <td colspan="5" class="px-3 py-8 text-center text-gray-400">Ürün bulunamadı.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- ── PERSONEL ── --}}
                <div x-show="detailTab==='personel'" class="p-5">
                    <div x-show="reportLoading" class="text-center py-10 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i></div>
                    <div x-show="!reportLoading">
                        <p class="text-sm text-gray-500 mb-3" x-text="branchStaff.length + ' personel'"></p>
                        <div x-show="branchStaff.length===0" class="text-center py-10 text-gray-400">
                            <i class="fas fa-user-slash text-3xl mb-2 block"></i>
                            <p class="text-sm">Bu şubede personel yok</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <template x-for="s in branchStaff" :key="s.id">
                                <div class="flex items-center gap-3 bg-white border border-gray-100 rounded-xl p-4">
                                    <div class="w-9 h-9 rounded-full bg-brand-100 flex items-center justify-center shrink-0">
                                        <span class="text-sm font-bold text-brand-600" x-text="(s.name||'?').charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-800 truncate" x-text="s.name"></p>
                                        <p class="text-xs text-gray-400" x-text="s.role || '—'"></p>
                                    </div>
                                    <a x-show="s.phone" :href="'tel:'+s.phone" class="text-xs text-brand-500 hover:underline shrink-0" x-text="s.phone"></a>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- ── MODÜLLER ── --}}
                <div x-show="detailTab==='moduller'" class="p-5">
                    <div x-show="modulesLoading" class="text-center py-10 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i></div>
                    <div x-show="!modulesLoading" class="space-y-3">
                        <template x-for="m in modulesList" :key="m.id">
                            <div class="flex items-start justify-between gap-4 border border-gray-100 rounded-xl p-4">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-semibold text-gray-800" x-text="m.name"></span>
                                        <span class="text-[10px] px-2 py-0.5 rounded-full"
                                              :class="m.tenant_active ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-400'"
                                              x-text="m.tenant_active ? 'Plan aktif' : 'Plan dışı'"></span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1" x-text="m.description||''"></p>
                                </div>
                                <label class="flex items-center gap-2 text-xs text-gray-700 shrink-0 cursor-pointer">
                                    <input type="checkbox" x-model="m.branch_active" :disabled="!m.tenant_active" class="rounded text-brand-500 border-gray-300 w-4 h-4">
                                    Aktif
                                </label>
                            </div>
                        </template>
                        <p x-show="!modulesLoading && modulesList.length===0" class="text-center text-sm text-gray-400 py-8">Modül bulunamadı.</p>
                    </div>
                    <div class="flex justify-end mt-4">
                        <button @click="saveModules()" :disabled="modulesSaving"
                                class="px-5 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-brand-500 to-purple-600 rounded-lg disabled:opacity-50">
                            <span x-text="modulesSaving ? 'Kaydediliyor...' : 'Modülleri Kaydet'"></span>
                        </button>
                    </div>
                </div>

                {{-- ── CİHAZLAR ── --}}
                <div x-show="detailTab==='cihazlar'" class="p-5 space-y-4">
                    <div x-show="deviceLoading" class="text-center py-10 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i></div>
                    <div x-show="!deviceLoading" class="space-y-4">
                        <div class="rounded-2xl border border-brand-100 bg-gradient-to-r from-brand-50/80 to-indigo-50/80 p-4 space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">Hızlı Satış Terminalleri</h3>
                                    <p class="text-xs text-gray-500 mt-1">Her fiziksel kasa veya hızlı satış ekranı için ayrı terminal tanımlayın.</p>
                                </div>
                                <button @click="openTerminalForm()"
                                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-brand-500 to-purple-600 rounded-lg shadow-sm hover:shadow-md transition-all">
                                    <i class="fas fa-plus text-xs"></i>
                                    <span>Terminal Ekle</span>
                                </button>
                            </div>

                            <div x-show="deviceOptions.terminals.length === 0" class="rounded-xl border border-dashed border-brand-200 bg-white/80 px-4 py-8 text-center text-sm text-gray-500">
                                Bu şubeye henüz terminal eklenmemiş.
                            </div>

                            <div x-show="deviceOptions.terminals.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                                <template x-for="terminal in deviceOptions.terminals" :key="terminal.id">
                                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm space-y-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <h4 class="text-sm font-semibold text-gray-900 truncate" x-text="terminal.name"></h4>
                                                    <span class="text-[10px] px-2 py-0.5 rounded-full"
                                                          :class="terminal.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'"
                                                          x-text="terminal.is_active ? 'Aktif' : 'Pasif'"></span>
                                                </div>
                                                <p class="text-xs text-gray-400 mt-1">
                                                    <span x-show="terminal.code">#<span x-text="terminal.code"></span></span>
                                                    <span x-show="!terminal.code">ID: <span x-text="terminal.id"></span></span>
                                                </p>
                                                <p class="text-xs text-gray-500 mt-2" x-show="terminal.responsible_user || terminal.responsible_user_id">
                                                    Sorumlu: <span class="font-medium text-gray-700" x-text="terminal.responsible_user?.name || userName(terminal.responsible_user_id)"></span>
                                                </p>
                                                <p x-show="terminal.description" class="text-xs text-gray-500 mt-2 line-clamp-2" x-text="terminal.description"></p>
                                            </div>
                                            <div class="flex items-center gap-1 shrink-0">
                                                <button @click="openTerminalForm(terminal)" class="w-9 h-9 rounded-lg border border-gray-200 text-gray-500 hover:text-brand-600 hover:border-brand-200 hover:bg-brand-50 transition-colors">
                                                    <i class="fas fa-pen text-xs"></i>
                                                </button>
                                                <button @click="deleteTerminal(terminal.id)" class="w-9 h-9 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition-colors">
                                                    <i class="fas fa-trash text-xs"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                                            <div class="rounded-xl bg-gray-50 px-3 py-2 border border-gray-100">
                                                <div class="text-gray-400 uppercase tracking-wide text-[10px]">Fiş</div>
                                                <div class="text-gray-700 font-medium mt-1 truncate" x-text="printerName(terminal.receipt_printer_id)"></div>
                                            </div>
                                            <div class="rounded-xl bg-gray-50 px-3 py-2 border border-gray-100">
                                                <div class="text-gray-400 uppercase tracking-wide text-[10px]">Mutfak</div>
                                                <div class="text-gray-700 font-medium mt-1 truncate" x-text="printerName(terminal.kitchen_printer_id)"></div>
                                            </div>
                                            <div class="rounded-xl bg-gray-50 px-3 py-2 border border-gray-100">
                                                <div class="text-gray-400 uppercase tracking-wide text-[10px]">Çekmece</div>
                                                <div class="text-gray-700 font-medium mt-1 truncate" x-text="cashDrawerName(terminal.cash_drawer_id)"></div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Fiş Yazıcısı</label>
                            <select x-model="deviceSettings.receipt_printer_id" class="w-full border border-gray-200 text-gray-800 text-sm rounded-lg px-4 py-2.5 bg-white">
                                <option value="">Otomatik (varsayılan)</option>
                                <template x-for="pr in deviceOptions.printers" :key="pr.id">
                                    <option :value="String(pr.id)" x-text="pr.name + (pr.is_default?' (varsayılan)':'')"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Mutfak Yazıcısı</label>
                            <select x-model="deviceSettings.kitchen_printer_id" class="w-full border border-gray-200 text-gray-800 text-sm rounded-lg px-4 py-2.5 bg-white">
                                <option value="">Otomatik</option>
                                <template x-for="pr in deviceOptions.printers" :key="pr.id">
                                    <option :value="String(pr.id)" x-text="pr.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Para Çekmecesi</label>
                            <select x-model="deviceSettings.cash_drawer_device_id" class="w-full border border-gray-200 text-gray-800 text-sm rounded-lg px-4 py-2.5 bg-white">
                                <option value="">Otomatik</option>
                                <template x-for="d in deviceOptions.cash_drawers" :key="d.id">
                                    <option :value="String(d.id)" x-text="d.name"></option>
                                </template>
                            </select>
                        </div>
                        <p x-show="deviceOptions.printers.length===0 && deviceOptions.cash_drawers.length===0"
                           class="text-sm text-gray-400 text-center py-4">Bu şubeye ait cihaz yok.</p>
                    </div>
                    <div class="flex justify-end border-t border-gray-100 pt-4">
                        <button @click="saveDeviceSettings()" :disabled="deviceSaving"
                                class="px-5 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-brand-500 to-purple-600 rounded-lg disabled:opacity-50">
                            <span x-text="deviceSaving ? 'Kaydediliyor...' : 'Cihazları Kaydet'"></span>
                        </button>
                    </div>

                    <div x-show="terminalFormOpen" x-transition.opacity class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none;">
                        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="terminalFormOpen = false"></div>
                        <div class="relative w-full max-w-2xl rounded-2xl border border-gray-100 bg-white shadow-2xl overflow-hidden" @click.stop>
                            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900" x-text="terminalForm.id ? 'Terminal Düzenle' : 'Yeni Terminal'"></h3>
                                    <p class="text-xs text-gray-500 mt-1">Kasa ekranı ve hızlı satış ayrımı için terminal tanımlayın.</p>
                                </div>
                                <button @click="terminalFormOpen = false" class="p-2 text-gray-400 hover:text-gray-700 rounded-lg">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <div class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Terminal Adı</label>
                                    <input type="text" x-model="terminalForm.name" class="w-full border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 bg-white" placeholder="Örn: Ön Kasa 1">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Terminal Kodu</label>
                                    <input type="text" x-model="terminalForm.code" class="w-full border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 bg-white" placeholder="Örn: ONK-01">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Sorumlu Personel</label>
                                    <select x-model="terminalForm.responsible_user_id" class="w-full border border-gray-200 text-gray-800 text-sm rounded-lg px-4 py-2.5 bg-white">
                                        <option value="">Atanmadı</option>
                                        <template x-for="user in deviceOptions.users" :key="'terminal-user-' + user.id">
                                            <option :value="String(user.id)" x-text="user.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Fiş Yazıcısı</label>
                                        <select x-model="terminalForm.receipt_printer_id" class="w-full border border-gray-200 text-gray-800 text-sm rounded-lg px-4 py-2.5 bg-white">
                                            <option value="">Otomatik</option>
                                            <template x-for="pr in deviceOptions.printers" :key="'terminal-receipt-' + pr.id">
                                                <option :value="String(pr.id)" x-text="pr.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Mutfak Yazıcısı</label>
                                        <select x-model="terminalForm.kitchen_printer_id" class="w-full border border-gray-200 text-gray-800 text-sm rounded-lg px-4 py-2.5 bg-white">
                                            <option value="">Otomatik</option>
                                            <template x-for="pr in deviceOptions.printers" :key="'terminal-kitchen-' + pr.id">
                                                <option :value="String(pr.id)" x-text="pr.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Para Çekmecesi</label>
                                        <select x-model="terminalForm.cash_drawer_id" class="w-full border border-gray-200 text-gray-800 text-sm rounded-lg px-4 py-2.5 bg-white">
                                            <option value="">Otomatik</option>
                                            <template x-for="drawer in deviceOptions.cash_drawers" :key="'terminal-drawer-' + drawer.id">
                                                <option :value="String(drawer.id)" x-text="drawer.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="flex items-end">
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer h-[42px]">
                                            <input type="checkbox" x-model="terminalForm.is_active" class="rounded text-brand-500 border-gray-300 w-4 h-4">
                                            Terminal aktif
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Açıklama</label>
                                    <textarea x-model="terminalForm.description" rows="3" class="w-full border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 bg-white resize-none" placeholder="Örn: Ön banko sağ kasa, akşam vardiyası kullanıyor"></textarea>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50/80">
                                <button @click="terminalFormOpen = false" class="px-4 py-2.5 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg">İptal</button>
                                <button @click="saveTerminal()" :disabled="terminalSaving" class="px-5 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-brand-500 to-purple-600 rounded-lg disabled:opacity-50">
                                    <span x-text="terminalSaving ? 'Kaydediliyor...' : (terminalForm.id ? 'Güncelle' : 'Terminali Kaydet')"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── DÜZENLE ── --}}
                <div x-show="detailTab==='duzenle'" class="p-5">
                    <form @submit.prevent="submitEdit()" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Şube Adı <span class="text-red-500">*</span></label>
                                <input type="text" x-model="editForm.name" required class="w-full border border-gray-200 text-gray-900 text-sm rounded-lg px-3 py-2.5">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Şube Kodu</label>
                                <input type="text" x-model="editForm.code" class="w-full border border-gray-200 text-gray-900 text-sm rounded-lg px-3 py-2.5">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Adres</label>
                            <textarea x-model="editForm.address" rows="2" class="w-full border border-gray-200 text-gray-900 text-sm rounded-lg px-3 py-2.5 resize-none"></textarea>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Telefon</label>
                                <input type="tel" x-model="editForm.phone" class="w-full border border-gray-200 text-gray-900 text-sm rounded-lg px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">İl</label>
                                <input type="text" x-model="editForm.city" class="w-full border border-gray-200 text-gray-900 text-sm rounded-lg px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">İlçe</label>
                                <input type="text" x-model="editForm.district" class="w-full border border-gray-200 text-gray-900 text-sm rounded-lg px-3 py-2">
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-6">
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" x-model="editForm.is_active" class="rounded text-brand-500 border-gray-300 w-4 h-4"> Aktif
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" x-model="editForm.is_center" class="rounded text-brand-500 border-gray-300 w-4 h-4"> Merkez şube
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" x-model="editForm.price_edit_locked" class="rounded text-brand-500 border-gray-300 w-4 h-4"> Fiyat kilidi
                            </label>
                        </div>
                        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                            <button type="button" @click="confirmDeleteBranch()"
                                    class="px-4 py-2 text-sm text-red-600 hover:bg-red-50 border border-red-200 rounded-lg transition-colors">
                                <i class="fas fa-trash mr-1.5"></i>Şubeyi Sil
                            </button>
                            <button type="submit" :disabled="editSaving"
                                    class="px-5 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-brand-500 to-purple-600 rounded-lg disabled:opacity-50">
                                <span x-text="editSaving ? 'Kaydediliyor...' : 'Kaydet'"></span>
                            </button>
                        </div>
                    </form>
                </div>

            </div>{{-- /tab content --}}
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function branchManager() {
    return {
        emptyReportData() {
            return {
                kpi: {
                    totalRevenue: 0,
                    totalCount: 0,
                    avgTicket: 0,
                    totalDiscount: 0,
                    creditTotal: 0,
                },
                payments: [],
                daily: [],
                top_products: [],
                top_customers: [],
                products: [],
                staff: [],
            };
        },

        showCreateModal: false, createSaving: false,
        createForm: { name:'', code:'', address:'', phone:'', city:'', district:'', is_center:false, price_edit_locked:false },

        showDetailModal: false,
        detail: {},
        detailTab: 'rapor',

        reportLoading: false, reportData: null, reportPeriod: '30', reportFrom: '', reportTo: '',

        branchProducts: [], productSearch: '',
        branchStaff: [],

        modulesLoading: false, modulesSaving: false, modulesList: [],
        deviceLoading: false, deviceSaving: false,
        deviceOptions: { printers:[], cash_drawers:[], terminals:[], users:[] },
        deviceSettings: { receipt_printer_id:'', kitchen_printer_id:'', cash_drawer_device_id:'' },
        
        terminalFormOpen: false,
        terminalSaving: false,
        terminalForm: { id: '', name: '', code: '', responsible_user_id: '', receipt_printer_id: '', kitchen_printer_id: '', cash_drawer_id: '', description: '', is_active: true },

        editSaving: false, editForm: {},

        get maxDaily() {
            if (!this.reportData?.daily?.length) return 0;
            return Math.max(...this.reportData.daily.map(d => d.revenue));
        },
        get filteredBranchProducts() {
            if (!this.productSearch) return this.branchProducts;
            const q = this.productSearch.toLowerCase();
            return this.branchProducts.filter(p =>
                (p.name||'').toLowerCase().includes(q) ||
                (p.barcode||'').toLowerCase().includes(q) ||
                (p.category||'').toLowerCase().includes(q)
            );
        },

        fmt(v) {
            return parseFloat(v||0).toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2}) + ' \u20ba';
        },
        fmtShort(v) {
            const n = parseFloat(v||0);
            return n>=1000 ? (n/1000).toFixed(1)+'K' : String(Math.round(n));
        },
        printerName(id) {
            if (!id) return 'Otomatik';
            return this.deviceOptions.printers.find(pr => Number(pr.id) === Number(id))?.name || 'Tanımsız';
        },
        cashDrawerName(id) {
            if (!id) return 'Otomatik';
            return this.deviceOptions.cash_drawers.find(drawer => Number(drawer.id) === Number(id))?.name || 'Tanımsız';
        },
        userName(id) {
            if (!id) return 'Atanmadı';
            return this.deviceOptions.users.find(user => Number(user.id) === Number(id))?.name || 'Bilinmiyor';
        },

        openCreate() {
            this.createForm = { name:'', code:'', address:'', phone:'', city:'', district:'', is_center:false, price_edit_locked:false };
            this.showCreateModal = true;
        },

        init() {
            this.reportData = this.emptyReportData();
        },

        async submitCreate() {
            this.createSaving = true;
            try {
                await posAjax('/branches', { method:'POST', body:JSON.stringify(this.createForm) });
                showToast('Şube oluşturuldu', 'success');
                this.showCreateModal = false;
                window.location.reload();
            } catch(e) { showToast(e.message||'Hata', 'error'); }
            finally { this.createSaving = false; }
        },

        openDetail(b) {
            this.detail = b;
            this.detailTab = 'rapor';
            this.reportData = this.emptyReportData(); this.branchProducts = []; this.branchStaff = []; this.modulesList = [];
            this.deviceOptions = { printers:[], cash_drawers:[], terminals:[], users:[] };
            this.deviceSettings = { receipt_printer_id:'', kitchen_printer_id:'', cash_drawer_device_id:'' };
            this.editForm = { name:b.name||'', code:b.code||'', address:b.address||'', phone:b.phone||'',
                              city:b.city||'', district:b.district||'', is_active:!!b.is_active,
                              is_center:!!b.is_center, price_edit_locked:!!b.price_edit_locked };
            this.showDetailModal = true; window.scrollTo({top: 0, behavior: "smooth"});
            this.setReportPeriod('30');
        },

        async switchDetailTab(tab) {
            this.detailTab = tab;
            if ((tab==='urunler'||tab==='personel') && !this.reportData) await this.loadReport();
            if (tab==='moduller' && !this.modulesList.length) await this.loadModules();
            if (tab==='cihazlar' && !this.deviceOptions.printers.length && !this.deviceOptions.cash_drawers.length && !this.deviceOptions.terminals.length) await this.loadDeviceOptions();
        },

        setReportPeriod(period) {
            this.reportPeriod = period;
            const t = new Date();
            const pad = n => String(n).padStart(2,'0');
            const f = d => d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());
            if (period==='today') { this.reportFrom=this.reportTo=f(t); }
            else if (period==='7') { const d=new Date(t); d.setDate(d.getDate()-6); this.reportFrom=f(d); this.reportTo=f(t); }
            else if (period==='30') { const d=new Date(t); d.setDate(d.getDate()-29); this.reportFrom=f(d); this.reportTo=f(t); }
            else if (period==='month') { this.reportFrom=f(new Date(t.getFullYear(),t.getMonth(),1)); this.reportTo=f(t); }
            this.loadReport();
        },
        async loadReport() {
            if (!this.detail.id) return;
            this.reportLoading = true;
            try {
                const r = await posAjax('/branches/'+this.detail.id+'/report?from='+this.reportFrom+'&to='+this.reportTo, {}, 'GET');
                this.reportData = {
                    ...this.emptyReportData(),
                    ...(r || {}),
                    kpi: {
                        ...this.emptyReportData().kpi,
                        ...((r && r.kpi) || {}),
                    },
                    payments: Array.isArray(r?.payments) ? r.payments : [],
                    daily: Array.isArray(r?.daily) ? r.daily : [],
                    top_products: Array.isArray(r?.top_products) ? r.top_products : [],
                    top_customers: Array.isArray(r?.top_customers) ? r.top_customers : [],
                    products: Array.isArray(r?.products) ? r.products : [],
                    staff: Array.isArray(r?.staff) ? r.staff : [],
                };
                this.branchProducts = r.products || [];
                this.branchStaff = r.staff || [];
            } catch(e) { showToast(e.message||'Rapor yüklenemedi','error'); this.reportData = this.emptyReportData(); }
            finally { this.reportLoading = false; }
        },

        async loadModules() {
            if (!this.detail.id) return;
            this.modulesLoading = true;
            try {
                const r = await posAjax('/branches/'+this.detail.id+'/modules', {}, 'GET');
                this.modulesList = r.modules||[];
            } catch(e) { showToast(e.message||'Hata','error'); }
            finally { this.modulesLoading=false; }
        },
        async saveModules() {
            if (!this.detail.id) return;
            this.modulesSaving = true;
            try {
                await posAjax('/branches/'+this.detail.id+'/modules', {method:'POST', body:JSON.stringify({modules:this.modulesList.map(m=>({module_id:m.id,is_active:!!m.branch_active}))})});
                showToast('Modüller güncellendi','success');
            } catch(e) { showToast(e.message||'Hata','error'); }
            finally { this.modulesSaving=false; }
        },

        async loadDeviceOptions() {
            if (!this.detail.id) return;
            this.deviceLoading = true;
            try {
                const r = await posAjax('/branches/'+this.detail.id+'/devices', {}, 'GET');
                this.deviceOptions.printers = r.printers||[];
                this.deviceOptions.cash_drawers = r.cash_drawers||[];
                this.deviceOptions.terminals = r.terminals||[];
                this.deviceOptions.users = r.users||[];
                this.deviceSettings.receipt_printer_id = r.settings?.receipt_printer_id ? String(r.settings.receipt_printer_id) : '';
                this.deviceSettings.kitchen_printer_id = r.settings?.kitchen_printer_id ? String(r.settings.kitchen_printer_id) : '';
                this.deviceSettings.cash_drawer_device_id = r.settings?.cash_drawer_device_id ? String(r.settings.cash_drawer_device_id) : '';
            } catch(e) { showToast(e.message||'Hata','error'); }
            finally { this.deviceLoading=false; }
        },
        async saveDeviceSettings() {
            if (!this.detail.id) return;
            this.deviceSaving=true;
            try {
                await posAjax('/branches/'+this.detail.id+'/device-settings', {method:'POST', body:JSON.stringify(this.deviceSettings)});
                showToast('Cihazlar kaydedildi','success');
            } catch(e) { showToast(e.message||'Hata','error'); }
            finally { this.deviceSaving=false; }
        },

        openTerminalForm(term = null) {
            if (term) {
                this.terminalForm = { id: term.id, name: term.name, code: term.code || '', responsible_user_id: String(term.responsible_user_id || ''), receipt_printer_id: String(term.receipt_printer_id || ''), kitchen_printer_id: String(term.kitchen_printer_id || ''), cash_drawer_id: String(term.cash_drawer_id || ''), description: term.description || '', is_active: !!term.is_active };
            } else {
                this.terminalForm = { id: '', name: '', code: '', responsible_user_id: '', receipt_printer_id: '', kitchen_printer_id: '', cash_drawer_id: '', description: '', is_active: true };
            }
            this.terminalFormOpen = true;
        },
        async saveTerminal() {
            if (!this.terminalForm.name) return showToast('Terminal adı zorunlu', 'warning');
            this.terminalSaving = true;
            try {
                const r = await posAjax('/branches/'+this.detail.id+'/terminals', {method:'POST', body:JSON.stringify(this.terminalForm)});
                showToast('Terminal kaydedildi', 'success');
                this.terminalFormOpen = false;
                await this.loadDeviceOptions();
            } catch(e) { showToast(e.message||'Hata', 'error'); }
            finally { this.terminalSaving = false; }
        },
        async deleteTerminal(termId) {
            if (!confirm('Bu terminali silmek istediğinize emin misiniz?')) return;
            try {
                await posAjax('/branches/'+this.detail.id+'/terminals/'+termId, {method:'DELETE'});
                showToast('Terminal silindi.', 'success');
                await this.loadDeviceOptions();
            } catch(e) { showToast(e.message||'Hata', 'error'); }
        },

        async submitEdit() {
            if (!this.detail.id) return;
            this.editSaving=true;
            try {
                await posAjax('/branches/'+this.detail.id, {method:'PUT', body:JSON.stringify(this.editForm)});
                showToast('Şube güncellendi','success');
                this.detail.name = this.editForm.name;
                setTimeout(()=>window.location.reload(),700);
            } catch(e) { showToast(e.message||'Hata','error'); }
            finally { this.editSaving=false; }
        },
        async confirmDeleteBranch() {
            if (!confirm('"'+this.detail.name+'" şubesini silmek istediğinize emin misiniz?')) return;
            try {
                const r = await posAjax('/branches/'+this.detail.id, {method:'DELETE'});
                showToast(r.message||'Silindi','success');
                this.showDetailModal=false;
                setTimeout(()=>window.location.reload(),700);
            } catch(e) { showToast(e.message||'Silinemedi','error'); }
        },
    };
}
</script>
@endpush

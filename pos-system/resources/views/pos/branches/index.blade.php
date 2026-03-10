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
                class="bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 text-white font-medium rounded-lg text-sm px-5 py-2.5 transition-colors flex items-center gap-2 justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Şube
        </button>
    </div>

    {{-- Branch Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse($branches as $branch)
            <div class="bg-white rounded-xl border border-gray-100 p-6 hover:border-blue-500/30 transition-colors group">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-lg {{ $branch->is_active ? 'bg-emerald-500/10' : 'bg-gray-100/50' }} flex items-center justify-center">
                            <svg class="w-6 h-6 {{ $branch->is_active ? 'text-emerald-400' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-gray-900 font-semibold text-lg">{{ $branch->name }}</h3>
                            @if($branch->code)
                                <span class="text-xs text-gray-500 font-mono">{{ $branch->code }}</span>
                            @endif
                        </div>
                    </div>
                        <div class="flex items-center gap-1">
                            <button @click="openModules({{ json_encode(['id' => $branch->id, 'name' => $branch->name]) }})"
                                class="p-2 text-gray-500 hover:text-blue-500 hover:bg-blue-500/10 rounded-lg transition-colors sm:opacity-0 sm:group-hover:opacity-100" title="Moduller">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                                </svg>
                            </button>
                            <button @click="openDeviceSettings({{ json_encode(['id' => $branch->id, 'name' => $branch->name]) }})"
                                class="p-2 text-gray-500 hover:text-purple-500 hover:bg-purple-500/10 rounded-lg transition-colors sm:opacity-0 sm:group-hover:opacity-100" title="Cihazlar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17h6m-6 0a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2m-6 0h6"/>
                                </svg>
                            </button>
                            <button @click="openStats({{ json_encode(['id' => $branch->id, 'name' => $branch->name]) }})"
                                class="p-2 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors sm:opacity-0 sm:group-hover:opacity-100" title="Rapor">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19h16M6 16V8m6 8V5m6 11v-6"/>
                                </svg>
                            </button>
                            <button @click="openEdit({{ json_encode(['id' => $branch->id, 'name' => $branch->name, 'code' => $branch->code, 'address' => $branch->address, 'phone' => $branch->phone, 'city' => $branch->city, 'district' => $branch->district, 'is_active' => $branch->is_active, 'is_center' => (bool)($branch->settings['is_center'] ?? false), 'price_edit_locked' => (bool)($branch->settings['price_edit_locked'] ?? false)]) }})"
                                class="p-2 text-gray-500 hover:text-yellow-400 hover:bg-yellow-500/10 rounded-lg transition-colors sm:opacity-0 sm:group-hover:opacity-100" title="Düzenle">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                        </div>
                </div>

                @if($branch->address || $branch->city)
                    <p class="text-sm text-gray-500 mb-3">
                        <i class="fas fa-map-marker-alt w-4 text-gray-500"></i>
                        {{ $branch->address ?? '' }} {{ $branch->city ? '- ' . $branch->city : '' }}
                    </p>
                @endif
                @if($branch->phone)
                    <p class="text-sm text-gray-500 mb-4">
                        <i class="fas fa-phone w-4 text-gray-500"></i>
                        {{ $branch->phone }}
                    </p>
                @endif

                <div class="grid grid-cols-3 gap-3 pt-4 border-t border-gray-100">
                    <div class="text-center">
                        <p class="text-lg font-bold text-gray-900">{{ $branch->users_count }}</p>
                        <p class="text-xs text-gray-500">Kullanıcı</p>
                    </div>
                    <div class="text-center">
                        <p class="text-lg font-bold text-gray-900">{{ $branch->restaurant_tables_count }}</p>
                        <p class="text-xs text-gray-500">Masa</p>
                    </div>
                    <div class="text-center">
                        <p class="text-lg font-bold text-gray-900">{{ $branch->cash_registers_count }}</p>
                        <p class="text-xs text-gray-500">Kasa</p>
                    </div>
                </div>

                @if(!$branch->is_active)
                    <div class="mt-3 text-center">
                        <span class="text-xs bg-red-500/10 text-red-500 px-3 py-1 rounded-full border border-red-500/20">Pasif</span>
                    </div>
                @endif
            </div>
        @empty
            <div class="col-span-full text-center py-16">
                <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                </svg>
                <p class="text-gray-500 text-sm">Henüz şube eklenmemiş.</p>
            </div>
        @endforelse
    </div>

    {{-- Modal --}}
    <div x-show="showModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="relative bg-white rounded-xl border border-gray-100 shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" x-transition>
            <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900" x-text="editingId ? 'Şube Düzenle' : 'Yeni Şube'"></h2>
                <button @click="showModal = false" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form @submit.prevent="submitForm()" class="p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Şube Adı <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.name" required class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Şube Kodu</label>
                        <input type="text" x-model="form.code" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5" placeholder="Ör: SBE-01">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Adres</label>
                    <textarea x-model="form.address" rows="2" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 resize-none"></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Telefon</label>
                        <input type="tel" x-model="form.phone" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">İl</label>
                        <input type="text" x-model="form.city" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">İlçe</label>
                        <input type="text" x-model="form.district" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" x-model="form.is_center" class="rounded text-brand-500 border-gray-300 w-4 h-4">
                        Merkez şube
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" x-model="form.price_edit_locked" class="rounded text-brand-500 border-gray-300 w-4 h-4">
                        Fiyat düzenleme kilidi
                    </label>
                </div>
                <div class="flex gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="showModal = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 rounded-lg">İptal</button>
                    <button type="submit" :disabled="saving" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-900 bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 rounded-lg disabled:opacity-50">
                        <span x-text="editingId ? 'Güncelle' : 'Kaydet'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Moduller Modal --}}
    <div x-show="showModulesModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showModulesModal = false"></div>
        <div class="relative bg-white rounded-xl border border-gray-100 shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" x-transition>
            <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Moduller</h2>
                    <p class="text-xs text-gray-500" x-text="moduleBranchName"></p>
                </div>
                <button @click="showModulesModal = false" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 space-y-3">
                <div x-show="modulesLoading" class="text-center py-6"><i class="fas fa-spinner fa-spin text-brand-500"></i></div>
                <template x-for="m in modulesList" :key="m.id">
                    <div class="flex items-start justify-between gap-4 border border-gray-100 rounded-xl p-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-semibold text-gray-900" x-text="m.name"></h3>
                                <span class="text-[10px] px-2 py-0.5 rounded-full" :class="m.tenant_active ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-400'" x-text="m.tenant_active ? 'Plan aktif' : 'Plan disi'"></span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1" x-text="m.description || '-' "></p>
                            <p class="text-[10px] text-gray-400 mt-1" x-text="'Scope: ' + m.scope"></p>
                        </div>
                        <label class="flex items-center gap-2 text-xs text-gray-700">
                            <input type="checkbox" x-model="m.branch_active" :disabled="!m.tenant_active" class="rounded text-brand-500 border-gray-300 w-4 h-4">
                            Aktif
                        </label>
                    </div>
                </template>
                <p x-show="!modulesLoading && modulesList.length === 0" class="text-center text-sm text-gray-400 py-6">Modul bulunamadi.</p>
            </div>
            <div class="border-t border-gray-100 px-6 py-4 flex gap-3">
                <button type="button" @click="showModulesModal = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 rounded-lg">Kapat</button>
                <button type="button" @click="saveModules()" :disabled="modulesSaving" class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 rounded-lg disabled:opacity-50">
                    <span x-text="modulesSaving ? 'Kaydediliyor...' : 'Kaydet'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Cihaz Ayarlari Modal --}}
    <div x-show="showDeviceModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showDeviceModal = false"></div>
        <div class="relative bg-white rounded-xl border border-gray-100 shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto" x-transition>
            <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Cihaz Ayarlari</h2>
                    <p class="text-xs text-gray-500" x-text="deviceBranchName"></p>
                </div>
                <button @click="showDeviceModal = false" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div x-show="deviceLoading" class="text-center py-6"><i class="fas fa-spinner fa-spin text-brand-500"></i></div>
                <div x-show="!deviceLoading" class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Fis Yazicisi</label>
                        <select x-model="deviceSettings.receipt_printer_id" class="w-full bg-white border border-gray-200 text-gray-800 text-sm rounded-lg px-4 py-2.5">
                            <option value="">Otomatik (varsayilan)</option>
                            <template x-for="p in deviceOptions.printers" :key="p.id">
                                <option :value="String(p.id)" x-text="p.name + (p.is_default ? ' (varsayilan)' : '')"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Mutfak Yazicisi</label>
                        <select x-model="deviceSettings.kitchen_printer_id" class="w-full bg-white border border-gray-200 text-gray-800 text-sm rounded-lg px-4 py-2.5">
                            <option value="">Otomatik (mutfak etiketi)</option>
                            <template x-for="p in deviceOptions.printers" :key="p.id">
                                <option :value="String(p.id)" x-text="p.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Para Cekmecesi</label>
                        <select x-model="deviceSettings.cash_drawer_device_id" class="w-full bg-white border border-gray-200 text-gray-800 text-sm rounded-lg px-4 py-2.5">
                            <option value="">Otomatik (cekmece/yazici)</option>
                            <template x-for="d in deviceOptions.cash_drawers" :key="d.id">
                                <option :value="String(d.id)" x-text="d.name"></option>
                            </template>
                        </select>
                    </div>
                    <p x-show="deviceOptions.printers.length === 0 && deviceOptions.cash_drawers.length === 0" class="text-sm text-gray-400 text-center py-4">Bu subeye ait cihaz bulunamadi.</p>
                </div>
            </div>
            <div class="border-t border-gray-100 px-6 py-4 flex gap-3">
                <button type="button" @click="showDeviceModal = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 rounded-lg">Kapat</button>
                <button type="button" @click="saveDeviceSettings()" :disabled="deviceSaving" class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 rounded-lg disabled:opacity-50">
                    <span x-text="deviceSaving ? 'Kaydediliyor...' : 'Kaydet'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Rapor Modal --}}
    <div x-show="showStatsModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showStatsModal = false"></div>
        <div class="relative bg-white rounded-xl border border-gray-100 shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto" x-transition>
            <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Sube Raporu</h2>
                    <p class="text-xs text-gray-500" x-text="statsBranchName"></p>
                </div>
                <button @click="showStatsModal = false" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6">
                <div x-show="statsLoading" class="text-center py-8"><i class="fas fa-spinner fa-spin text-brand-500"></i></div>
                <div x-show="!statsLoading && statsData" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4">
                        <p class="text-xs text-emerald-600">Bugun Ciro</p>
                        <p class="text-lg font-bold text-emerald-700" x-text="formatMoney(statsData.today_revenue)"></p>
                        <p class="text-xs text-emerald-500" x-text="statsData.today_sales + ' satis'"></p>
                    </div>
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                        <p class="text-xs text-blue-600">Toplam Ciro</p>
                        <p class="text-lg font-bold text-blue-700" x-text="formatMoney(statsData.total_revenue)"></p>
                        <p class="text-xs text-blue-500" x-text="statsData.total_sales + ' satis'"></p>
                    </div>
                    <div class="bg-purple-50 border border-purple-100 rounded-xl p-4">
                        <p class="text-xs text-purple-600">Ortalama Fis</p>
                        <p class="text-lg font-bold text-purple-700" x-text="formatMoney(statsData.avg_ticket)"></p>
                    </div>
                    <div class="bg-amber-50 border border-amber-100 rounded-xl p-4">
                        <p class="text-xs text-amber-600">Son 7 Gun</p>
                        <p class="text-lg font-bold text-amber-700" x-text="formatMoney(statsData.last7_revenue)"></p>
                    </div>
                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                        <p class="text-xs text-gray-600">Son 30 Gun</p>
                        <p class="text-lg font-bold text-gray-700" x-text="formatMoney(statsData.last30_revenue)"></p>
                    </div>
                    <div class="bg-slate-50 border border-slate-100 rounded-xl p-4">
                        <p class="text-xs text-slate-600">Operasyon</p>
                        <p class="text-xs text-slate-600" x-text="'Kullanici: ' + statsData.users"></p>
                        <p class="text-xs text-slate-600" x-text="'Masa: ' + statsData.tables"></p>
                        <p class="text-xs text-slate-600" x-text="'Kasa: ' + statsData.cash_registers"></p>
                        <p class="text-xs text-slate-600" x-text="'Siparis: ' + statsData.orders"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function branchManager() {
    return {
        showModal: false, showModulesModal: false, showDeviceModal: false, showStatsModal: false, editingId: null, saving: false,
        modulesLoading: false, modulesSaving: false, modulesList: [], moduleBranchId: null, moduleBranchName: '',
        deviceLoading: false, deviceSaving: false, deviceOptions: { printers: [], cash_drawers: [] },
        deviceSettings: { receipt_printer_id: '', kitchen_printer_id: '', cash_drawer_device_id: '' },
        deviceBranchId: null, deviceBranchName: '',
        statsLoading: false, statsData: {
            today_revenue: 0,
            today_sales: 0,
            total_revenue: 0,
            total_sales: 0,
            avg_ticket: 0,
            last7_revenue: 0,
            last30_revenue: 0,
            users: 0,
            tables: 0,
            cash_registers: 0,
            orders: 0,
        }, statsBranchName: '', statsBranchId: null,
        form: { name: '', code: '', address: '', phone: '', city: '', district: '', is_center: false, price_edit_locked: false },
        defaultStats() {
            return {
                today_revenue: 0,
                today_sales: 0,
                total_revenue: 0,
                total_sales: 0,
                avg_ticket: 0,
                last7_revenue: 0,
                last30_revenue: 0,
                users: 0,
                tables: 0,
                cash_registers: 0,
                orders: 0,
            };
        },
        formatMoney(val) {
            return parseFloat(val || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺';
        },
        openCreate() { this.editingId = null; this.form = { name: '', code: '', address: '', phone: '', city: '', district: '', is_center: false, price_edit_locked: false }; this.showModal = true; },
        openEdit(b) { this.editingId = b.id; this.form = { name: b.name||'', code: b.code||'', address: b.address||'', phone: b.phone||'', city: b.city||'', district: b.district||'', is_center: !!b.is_center, price_edit_locked: !!b.price_edit_locked }; this.showModal = true; },
        async openModules(b) {
            this.moduleBranchId = b.id;
            this.moduleBranchName = b.name || '';
            this.modulesList = [];
            this.showModulesModal = true;
            await this.loadModules();
        },
        async loadModules() {
            if (!this.moduleBranchId) return;
            this.modulesLoading = true;
            try {
                const res = await posAjax(`/branches/${this.moduleBranchId}/modules`, {}, 'GET');
                this.modulesList = res.modules || [];
            } catch (e) {
                showToast(e.message || 'Moduller yuklenemedi', 'error');
                this.modulesList = [];
            } finally { this.modulesLoading = false; }
        },
        async saveModules() {
            if (!this.moduleBranchId) return;
            this.modulesSaving = true;
            try {
                await posAjax(`/branches/${this.moduleBranchId}/modules`, {
                    method: 'POST',
                    body: JSON.stringify({
                        modules: this.modulesList.map(m => ({ module_id: m.id, is_active: !!m.branch_active }))
                    })
                });
                showToast('Moduller guncellendi', 'success');
                this.showModulesModal = false;
            } catch (e) {
                showToast(e.message || 'Moduller guncellenemedi', 'error');
            } finally { this.modulesSaving = false; }
        },
        async openDeviceSettings(b) {
            this.deviceBranchId = b.id;
            this.deviceBranchName = b.name || '';
            this.deviceOptions = { printers: [], cash_drawers: [] };
            this.deviceSettings = { receipt_printer_id: '', kitchen_printer_id: '', cash_drawer_device_id: '' };
            this.showDeviceModal = true;
            await this.loadDeviceOptions();
        },
        async loadDeviceOptions() {
            if (!this.deviceBranchId) return;
            this.deviceLoading = true;
            try {
                const res = await posAjax(`/branches/${this.deviceBranchId}/devices`, {}, 'GET');
                this.deviceOptions.printers = res.printers || [];
                this.deviceOptions.cash_drawers = res.cash_drawers || [];
                this.deviceSettings.receipt_printer_id = res.settings?.receipt_printer_id ? String(res.settings.receipt_printer_id) : '';
                this.deviceSettings.kitchen_printer_id = res.settings?.kitchen_printer_id ? String(res.settings.kitchen_printer_id) : '';
                this.deviceSettings.cash_drawer_device_id = res.settings?.cash_drawer_device_id ? String(res.settings.cash_drawer_device_id) : '';
            } catch (e) {
                showToast(e.message || 'Cihazlar yuklenemedi', 'error');
            } finally { this.deviceLoading = false; }
        },
        async saveDeviceSettings() {
            if (!this.deviceBranchId) return;
            this.deviceSaving = true;
            try {
                await posAjax(`/branches/${this.deviceBranchId}/device-settings`, {
                    method: 'POST',
                    body: JSON.stringify(this.deviceSettings),
                });
                showToast('Cihaz ayarlari guncellendi', 'success');
                this.showDeviceModal = false;
            } catch (e) {
                showToast(e.message || 'Cihaz ayarlari guncellenemedi', 'error');
            } finally { this.deviceSaving = false; }
        },
        async openStats(b) {
            this.statsBranchId = b.id;
            this.statsBranchName = b.name || '';
            this.statsData = this.defaultStats();
            this.showStatsModal = true;
            await this.loadStats();
        },
        async loadStats() {
            if (!this.statsBranchId) return;
            this.statsLoading = true;
            try {
                const res = await posAjax(`/branches/${this.statsBranchId}/stats`, {}, 'GET');
                this.statsData = res.stats || this.defaultStats();
            } catch (e) {
                showToast(e.message || 'Rapor yuklenemedi', 'error');
                this.statsData = this.defaultStats();
            } finally { this.statsLoading = false; }
        },
        async submitForm() {
            this.saving = true;
            const url = this.editingId ? `/branches/${this.editingId}` : '/branches';
            try {
                await posAjax(url, { method: this.editingId ? 'PUT' : 'POST', body: JSON.stringify(this.form) });
                showToast(this.editingId ? 'Şube güncellendi' : 'Şube oluşturuldu', 'success');
                this.showModal = false; window.location.reload();
            } catch (e) { showToast(e.message || 'Hata', 'error'); }
            finally { this.saving = false; }
        }
    };
}
</script>
@endpush

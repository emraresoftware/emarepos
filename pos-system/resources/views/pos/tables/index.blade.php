@extends('pos.layouts.app')
@section('title', 'Masa Haritası')

@section('content')
<div x-data="masaHaritasi()" x-init="init()" class="flex-1 flex flex-col overflow-hidden">

    {{-- ─── ÜST BAR ─────────────────────────────────────────── --}}
    <div class="shrink-0 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between gap-3 flex-wrap">

        {{-- Sol: Başlık + Bölge Filtreleri --}}
        <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-lg font-bold text-gray-900 shrink-0">Masa Haritası</h1>
            <div class="flex items-center gap-1.5 flex-wrap">
                <button @click="filterRegion(null)"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                    :class="selectedRegion === null ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                    Tümü
                </button>
                <template x-for="region in regions" :key="region.id">
                    <button @click="filterRegion(region.id)"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all flex items-center gap-1"
                        :class="selectedRegion === region.id ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                        <i :class="'fas ' + (region.icon || 'fa-location-dot') + ' text-[10px]'"></i>
                        <span x-text="region.name"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Sağ: Durum göstergeleri + Tasarım modu --}}
        <div class="flex items-center gap-3">
            <template x-if="!designMode">
                <div class="flex items-center gap-3 text-xs text-gray-500">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-emerald-400 rounded-full inline-block"></span> Boş</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-red-400 rounded-full inline-block"></span> Dolu</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-amber-400 rounded-full inline-block"></span> Reserve</span>
                </div>
            </template>
            <template x-if="designMode">
                <div class="flex items-center gap-2">
                    <button @click="openRegionModal()"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-purple-100 transition-colors"
                        style="background:#f3e8ff;color:#7e22ce;">
                        <i class="fas fa-plus text-[10px]"></i> Mekan Ekle
                    </button>
                    <button @click="openTableModal(null)"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-indigo-100 transition-colors"
                        style="background:#eef2ff;color:#6366f1;">
                        <i class="fas fa-plus text-[10px]"></i> Masa Ekle
                    </button>
                    <button @click="saveLayout()"
                        :disabled="!layoutDirty"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                        :class="layoutDirty ? 'bg-emerald-500 text-white hover:bg-emerald-600' : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                        <i class="fas fa-floppy-disk text-[10px]"></i> Kaydet
                    </button>
                </div>
            </template>
            <button @click="toggleDesignMode()"
                class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all border"
                :class="designMode ? 'bg-amber-400 border-amber-500 text-white shadow-md' : 'bg-white border-gray-200 text-gray-600 hover:border-indigo-300'">
                <i :class="designMode ? 'fas fa-check' : 'fas fa-pencil-ruler'" class="text-xs"></i>
                <span x-text="designMode ? 'Tasarımı Bitir' : 'Tasarım Modu'"></span>
            </button>
        </div>
    </div>

    {{-- ─── KANVAS ALANI ────────────────────────────────────── --}}
    <div class="flex-1 overflow-y-auto p-4 bg-gray-50/70">

        {{-- Boş mekan mesajı --}}
        <template x-if="visibleRegions.length === 0 && regions.length === 0">
            <div class="flex flex-col items-center justify-center h-64 text-gray-400">
                <i class="fas fa-map-location-dot text-5xl mb-4 opacity-30"></i>
                <p class="text-lg font-medium">Henüz mekan eklenmemiş</p>
                <p class="text-sm mt-1">Tasarım Modu'nu açarak mekan ve masa ekleyebilirsiniz.</p>
                <button @click="designMode = true; openRegionModal()"
                    class="mt-4 px-5 py-2 rounded-xl text-sm font-medium text-white transition-all"
                    style="background:linear-gradient(to right,#6366f1,#9333ea);">
                    <i class="fas fa-plus mr-2"></i>İlk Mekanı Ekle
                </button>
            </div>
        </template>

        {{-- Bölgesiz masalar --}}
        <template x-if="unassignedTables.length > 0">
            <div class="mb-4">
                <div class="flex items-center gap-2 mb-2 px-1">
                    <i class="fas fa-table-cells text-gray-400 text-sm"></i>
                    <span class="text-sm font-medium text-gray-500">Mekan Atanmamış</span>
                </div>
                <div class="relative rounded-2xl border-2 border-dashed border-gray-200 bg-white/80 p-3 min-h-24"
                     @dragover.prevent="onDragOver($event, null)"
                     @drop.prevent="onDrop($event, null)">
                    <div class="flex flex-wrap gap-3 p-1">
                        <template x-for="table in unassignedTables" :key="table.id">
                            <div class="relative cursor-pointer select-none transition-all hover:scale-105"
                                 :draggable="designMode ? 'true' : 'false'"
                                 @dragstart="startDrag($event, table)"
                                 @click="designMode ? openTableModal(table) : goToTable(table)">
                                <div :class="tableClass(table, 'sm')">
                                    <span class="font-bold text-base" x-text="table.table_no"></span>
                                    <span class="text-[10px] text-center truncate max-w-full leading-tight mt-0.5 px-1" x-text="table.name"></span>
                                    <div class="flex items-center gap-0.5 text-[10px] opacity-50 mt-0.5">
                                        <i class="fas fa-users text-[8px]"></i>
                                        <span x-text="table.capacity"></span>
                                    </div>
                                </div>
                                <div x-show="table.status === 'occupied'" class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                                <template x-if="designMode">
                                    <button @click.stop="deleteTable(table.id)"
                                        class="absolute -top-1.5 -left-1.5 w-4 h-4 bg-red-500 text-white rounded-full text-[10px] flex items-center justify-center hover:bg-red-600 z-10">
                                        <i class="fas fa-xmark"></i>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        {{-- Mekanlar --}}
        <template x-for="region in visibleRegions" :key="region.id">
            <div class="mb-5 rounded-3xl overflow-hidden border-2 transition-all"
                 :class="selectedRegion === region.id ? 'border-indigo-400' : 'border-transparent'"
                 :style="'background-color:' + (region.bg_color || '#f0f9ff')">

                {{-- Mekan Başlığı --}}
                <div class="flex items-center gap-2 px-4 pt-3 pb-2">
                    <i :class="'fas ' + (region.icon || 'fa-location-dot') + ' text-sm opacity-60'"></i>
                    <span class="font-semibold text-gray-700 text-sm" x-text="region.name"></span>
                    <span class="ml-1 text-xs text-gray-400" x-text="'(' + tablesInRegion(region.id).length + ' masa)'"></span>
                    <template x-if="designMode">
                        <div class="flex items-center gap-1.5 ml-auto">
                            <button @click="openTableModal(null, region.id)"
                                class="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white/70 text-gray-600 text-xs hover:bg-white transition-colors">
                                <i class="fas fa-plus text-[10px]"></i> Masa
                            </button>
                            <button @click="openRegionModal(region)"
                                class="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white/70 text-gray-600 text-xs hover:bg-white transition-colors">
                                <i class="fas fa-pen text-[10px]"></i> Düzenle
                            </button>
                            <button @click="deleteRegion(region.id)"
                                class="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-50 text-red-500 text-xs hover:bg-red-100 transition-colors">
                                <i class="fas fa-trash text-[10px]"></i>
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Serbest Kanvas --}}
                <div class="relative mx-3 mb-3 rounded-2xl bg-white/40 border border-white/60"
                     style="min-height:300px;"
                     :id="'canvas-' + region.id"
                     @dragover.prevent="onDragOver($event, region.id)"
                     @drop.prevent="onDrop($event, region.id)">

                    {{-- Tasarım modu ızgara --}}
                    <template x-if="designMode">
                        <div class="absolute inset-0 rounded-2xl pointer-events-none opacity-20"
                             style="background-image:radial-gradient(circle,#94a3b8 1px,transparent 1px);background-size:24px 24px;"></div>
                    </template>

                    {{-- Boş mekan rehber --}}
                    <template x-if="designMode && tablesInRegion(region.id).length === 0">
                        <div class="absolute inset-0 flex items-center justify-center text-gray-300 text-sm pointer-events-none">
                            <div class="text-center">
                                <i class="fas fa-arrows-up-down-left-right text-3xl mb-2 block opacity-40"></i>
                                <span>Masaları sürükleyin veya "+ Masa" ekleyin</span>
                            </div>
                        </div>
                    </template>

                    {{-- Masalar (absolute konumlu) --}}
                    <template x-for="table in tablesInRegion(region.id)" :key="table.id">
                        <div class="absolute select-none"
                             :style="absoluteStyle(table)"
                             :draggable="designMode ? 'true' : 'false'"
                             @dragstart="startDrag($event, table)"
                             @click="designMode ? openTableModal(table) : goToTable(table)">
                            <div :class="tableClass(table, 'md')" class="cursor-pointer hover:shadow-lg transition-all w-full h-full">
                                <span class="font-bold text-xl leading-none" x-text="table.table_no"></span>
                                <span class="text-[11px] font-medium leading-tight mt-0.5 text-center truncate w-full px-1" x-text="table.name"></span>
                                <div class="flex items-center gap-0.5 text-[10px] mt-0.5 opacity-60">
                                    <i class="fas fa-users text-[9px]"></i>
                                    <span x-text="table.capacity"></span>
                                </div>
                                <template x-if="table.status === 'occupied' && table.active_session">
                                    <div class="text-xs font-bold text-gray-800 mt-1 leading-none" x-text="tableTotal(table) + ' ₺'"></div>
                                </template>
                            </div>
                            <div x-show="table.status === 'occupied'"
                                 class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-red-500 rounded-full animate-pulse shadow-sm"></div>
                            <div x-show="table.status === 'reserved'"
                                 class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-amber-400 rounded-full shadow-sm"></div>
                            <template x-if="designMode">
                                <button @click.stop="deleteTable(table.id)"
                                    class="absolute -top-2 -left-2 w-5 h-5 bg-red-500 text-white rounded-full text-[10px] flex items-center justify-center hover:bg-red-600 shadow-sm z-10">
                                    <i class="fas fa-xmark"></i>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        {{-- Tasarım modunda yeni mekan butonu --}}
        <template x-if="designMode">
            <button @click="openRegionModal()"
                class="w-full py-6 rounded-3xl border-2 border-dashed border-gray-200 text-gray-400 text-sm hover:border-indigo-300 hover:text-indigo-500 transition-all flex items-center justify-center gap-2 mt-2"
                style="min-height:80px;">
                <i class="fas fa-plus text-lg"></i>
                <span>Yeni Mekan Ekle</span>
            </button>
        </template>
    </div>

    {{-- ─── MEKAN MODAL ─────────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="showRegionModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             @keydown.escape.window="showRegionModal = false">
            <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="showRegionModal = false"></div>
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 z-10">
                <h3 class="text-lg font-bold text-gray-900 mb-5" x-text="editingRegion ? 'Mekanı Düzenle' : 'Yeni Mekan Ekle'"></h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Mekan Adı <span class="text-red-400">*</span></label>
                        <input x-model="regionForm.name" type="text" placeholder="örn. İç Mekan, Bahçe, Teras, VIP…"
                               class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none"
                               @keydown.enter="saveRegion()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Arka Plan Rengi</label>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="color in bgColors" :key="color.value">
                                <button @click="regionForm.bg_color = color.value"
                                    class="w-9 h-9 rounded-xl border-2 transition-all hover:scale-110 relative"
                                    :class="regionForm.bg_color === color.value ? 'border-indigo-500 scale-110 shadow-md' : 'border-gray-200'"
                                    :style="'background-color:' + color.value"
                                    :title="color.label">
                                    <template x-if="regionForm.bg_color === color.value">
                                        <i class="fas fa-check text-[10px] text-indigo-600 absolute inset-0 flex items-center justify-center"
                                           style="display:flex;align-items:center;justify-content:center;"></i>
                                    </template>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">İkon</label>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="icon in regionIcons" :key="icon.value">
                                <button @click="regionForm.icon = icon.value"
                                    class="w-10 h-10 rounded-xl border-2 flex items-center justify-center text-gray-600 transition-all hover:scale-110"
                                    :class="regionForm.icon === icon.value ? 'border-indigo-500 bg-indigo-50 text-indigo-600' : 'border-gray-200 hover:border-gray-300'"
                                    :title="icon.label">
                                    <i :class="'fas ' + icon.value"></i>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Açıklama</label>
                        <input x-model="regionForm.description" type="text" placeholder="Opsiyonel not…"
                               class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button @click="showRegionModal = false"
                        class="flex-1 py-2.5 rounded-xl bg-gray-100 text-gray-600 text-sm font-medium hover:bg-gray-200 transition-colors">
                        İptal
                    </button>
                    <button @click="saveRegion()"
                        :disabled="!regionForm.name.trim()"
                        class="flex-1 py-2.5 rounded-xl text-white text-sm font-medium hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        style="background:linear-gradient(to right,#6366f1,#9333ea);">
                        <span x-text="editingRegion ? 'Güncelle' : 'Ekle'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ─── MASA MODAL ──────────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="showTableModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             @keydown.escape.window="showTableModal = false">
            <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="showTableModal = false"></div>
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 z-10">
                <h3 class="text-lg font-bold text-gray-900 mb-5" x-text="editingTable ? 'Masayı Düzenle' : 'Yeni Masa Ekle'"></h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Masa No <span class="text-red-400">*</span></label>
                            <input x-model="tableForm.table_no" type="text" placeholder="1, A2, B-3…"
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Masa Adı <span class="text-red-400">*</span></label>
                            <input x-model="tableForm.name" type="text" placeholder="VIP, Bahçe 1…"
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kapasite (kişi)</label>
                        <div class="flex items-center gap-4">
                            <button @click="tableForm.capacity = Math.max(1, tableForm.capacity - 1)"
                                class="w-10 h-10 rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 flex items-center justify-center text-lg font-bold">−</button>
                            <span class="text-2xl font-bold text-gray-900 w-10 text-center" x-text="tableForm.capacity"></span>
                            <button @click="tableForm.capacity = Math.min(30, tableForm.capacity + 1)"
                                class="w-10 h-10 rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 flex items-center justify-center text-lg font-bold">+</button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Masa Şekli</label>
                        <div class="flex gap-3">
                            <button @click="tableForm.shape = 'square'"
                                class="flex-1 flex flex-col items-center gap-2 py-3 rounded-xl border-2 transition-all"
                                :class="tableForm.shape === 'square' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                                <div class="w-10 h-10 rounded-xl border-2"
                                     :class="tableForm.shape === 'square' ? 'border-indigo-500' : 'border-gray-300'"></div>
                                <span class="text-xs font-medium" :class="tableForm.shape === 'square' ? 'text-indigo-600' : 'text-gray-500'">Kare</span>
                            </button>
                            <button @click="tableForm.shape = 'circle'"
                                class="flex-1 flex flex-col items-center gap-2 py-3 rounded-xl border-2 transition-all"
                                :class="tableForm.shape === 'circle' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                                <div class="w-10 h-10 rounded-full border-2"
                                     :class="tableForm.shape === 'circle' ? 'border-indigo-500' : 'border-gray-300'"></div>
                                <span class="text-xs font-medium" :class="tableForm.shape === 'circle' ? 'text-indigo-600' : 'text-gray-500'">Yuvarlak</span>
                            </button>
                            <button @click="tableForm.shape = 'rectangle'"
                                class="flex-1 flex flex-col items-center gap-2 py-3 rounded-xl border-2 transition-all"
                                :class="tableForm.shape === 'rectangle' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                                <div class="w-14 h-8 rounded-xl border-2"
                                     :class="tableForm.shape === 'rectangle' ? 'border-indigo-500' : 'border-gray-300'"></div>
                                <span class="text-xs font-medium" :class="tableForm.shape === 'rectangle' ? 'text-indigo-600' : 'text-gray-500'">Dikdörtgen</span>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Mekan</label>
                        <select x-model="tableForm.table_region_id"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-300 outline-none bg-white">
                            <option :value="null">— Mekansız —</option>
                            <template x-for="region in regions" :key="region.id">
                                <option :value="region.id" x-text="region.name"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button @click="showTableModal = false"
                        class="flex-1 py-2.5 rounded-xl bg-gray-100 text-gray-600 text-sm font-medium hover:bg-gray-200 transition-colors">
                        İptal
                    </button>
                    <button @click="saveTable()"
                        :disabled="!tableForm.table_no || !tableForm.name"
                        class="flex-1 py-2.5 rounded-xl text-white text-sm font-medium hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        style="background:linear-gradient(to right,#6366f1,#9333ea);">
                        <span x-text="editingTable ? 'Güncelle' : 'Ekle'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>

<style>
[x-cloak] { display: none !important; }
</style>

@endsection

@push('scripts')
@php
$tablesJson = $tables->map(fn($t) => [
    'id'              => $t->id,
    'table_no'        => $t->table_no,
    'name'            => $t->name,
    'capacity'        => $t->capacity,
    'status'          => $t->status,
    'shape'           => $t->shape ?? 'square',
    'color'           => $t->color,
    'table_region_id' => $t->table_region_id,
    'pos_x'           => $t->pos_x ?? 5.0,
    'pos_y'           => $t->pos_y ?? 5.0,
    'active_session'  => $t->activeSession ? [
        'id'    => $t->activeSession->id,
        'total' => round($t->activeSession->orders->sum(fn($o) => $o->items->sum('total')), 2),
    ] : null,
]);
@endphp
<script>
function masaHaritasi() {
    return {
        designMode: false,
        selectedRegion: null,
        layoutDirty: false,

        regions: @json($regions),
        tables: @json($tablesJson),

        showRegionModal: false,
        editingRegion: null,
        regionForm: { name: '', bg_color: '#f0f9ff', icon: 'fa-location-dot', description: '' },

        showTableModal: false,
        editingTable: null,
        tableForm: { table_no: '', name: '', capacity: 4, shape: 'square', table_region_id: null },

        draggingTable: null,

        bgColors: [
            { value: '#f0f9ff', label: 'Buz Mavisi' },
            { value: '#f0fdf4', label: 'Nane' },
            { value: '#fefce8', label: 'Krem' },
            { value: '#fff7ed', label: 'Şeftali' },
            { value: '#fdf4ff', label: 'Leylak' },
            { value: '#fef2f2', label: 'Gül' },
            { value: '#f5f3ff', label: 'Lavanta' },
            { value: '#ecfdf5', label: 'Yeşim' },
            { value: '#e0f2fe', label: 'Gökyüzü' },
            { value: '#fff1f2', label: 'Pembe' },
            { value: '#f1f5f9', label: 'Gri' },
            { value: '#fafafa', label: 'Beyaz' },
        ],
        regionIcons: [
            { value: 'fa-location-dot', label: 'Konum' },
            { value: 'fa-house', label: 'İç Mekan' },
            { value: 'fa-tree', label: 'Bahçe' },
            { value: 'fa-umbrella-beach', label: 'Veranda' },
            { value: 'fa-star', label: 'VIP' },
            { value: 'fa-utensils', label: 'Yemek' },
            { value: 'fa-mug-hot', label: 'Kafe' },
            { value: 'fa-champagne-glasses', label: 'Bar' },
            { value: 'fa-layer-group', label: 'Bölüm' },
            { value: 'fa-building', label: 'Kat' },
            { value: 'fa-wifi', label: 'Wi-Fi' },
            { value: 'fa-snowflake', label: 'Kapalı' },
        ],

        init() {},

        toggleDesignMode() {
            if (this.designMode && this.layoutDirty) {
                if (!confirm('Kaydedilmemiş konum değişiklikleri var. Çıkmak istiyor musunuz?')) return;
                this.layoutDirty = false;
            }
            this.designMode = !this.designMode;
        },

        filterRegion(id) { this.selectedRegion = id; },

        get visibleRegions() {
            if (this.selectedRegion !== null) return this.regions.filter(r => r.id === this.selectedRegion);
            return this.regions;
        },

        tablesInRegion(regionId) {
            return this.tables.filter(t => t.table_region_id == regionId);
        },

        get unassignedTables() {
            if (this.selectedRegion !== null) return [];
            return this.tables.filter(t => !t.table_region_id);
        },

        tableTotal(table) {
            if (!table.active_session) return '0.00';
            return parseFloat(table.active_session.total || 0).toFixed(2);
        },

        // ─── CSS Sınıfları ───────────────────────────────────

        tableClass(table, size = 'md') {
            const shape = table.shape || 'square';
            const isCircle = shape === 'circle';
            const isRect   = shape === 'rectangle';

            let sizeStyle = '';
            if (size === 'sm')       sizeStyle = isRect ? 'width:80px;height:52px;' : 'width:56px;height:56px;';
            else if (size === 'md')  sizeStyle = isRect ? 'width:112px;height:68px;' : 'width:80px;height:80px;';

            let colorClass = '';
            if (table.status === 'occupied')   colorClass = 'bg-red-50 border-red-300 text-red-600 shadow-md shadow-red-100/50';
            else if (table.status === 'reserved') colorClass = 'bg-amber-50 border-amber-300 text-amber-600';
            else                               colorClass = 'bg-white border-gray-200 text-emerald-600 hover:border-emerald-300 hover:shadow-md';

            const roundClass = isCircle ? 'rounded-full' : 'rounded-2xl';
            return `flex flex-col items-center justify-center border-2 ${colorClass} ${roundClass}`;
        },

        absoluteStyle(table) {
            const shape = table.shape || 'square';
            const w = shape === 'rectangle' ? 112 : 80;
            const h = shape === 'rectangle' ?  68 : 80;
            const left = Math.min(Math.max(parseFloat(table.pos_x) || 5, 0), 90);
            const top  = Math.min(Math.max(parseFloat(table.pos_y) || 5, 0), 85);
            return `left:${left}%;top:${top}%;width:${w}px;height:${h}px;`;
        },

        // ─── Drag & Drop ─────────────────────────────────────

        startDrag(e, table) {
            if (!this.designMode) { e.preventDefault(); return; }
            this.draggingTable = table;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', String(table.id));
        },

        onDragOver(e, regionId) {
            if (!this.designMode || !this.draggingTable) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        },

        onDrop(e, regionId) {
            if (!this.designMode || !this.draggingTable) return;
            e.preventDefault();

            const canvas = e.currentTarget;
            const rect = canvas.getBoundingClientRect();
            const shape = this.draggingTable.shape || 'square';
            const w = shape === 'rectangle' ? 112 : 80;
            const h = shape === 'rectangle' ?  68 : 80;

            let posX = ((e.clientX - rect.left - w / 2) / rect.width)  * 100;
            let posY = ((e.clientY - rect.top  - h / 2) / rect.height) * 100;
            posX = Math.max(0, Math.min(posX, 90));
            posY = Math.max(0, Math.min(posY, 85));

            const idx = this.tables.findIndex(t => t.id === this.draggingTable.id);
            if (idx !== -1) {
                this.tables[idx] = { ...this.tables[idx], pos_x: posX, pos_y: posY, table_region_id: regionId };
            }
            this.draggingTable = null;
            this.layoutDirty = true;
        },

        // ─── Layout Kaydet ───────────────────────────────────

        async saveLayout() {
            const positions = this.tables.map(t => ({
                id: t.id, pos_x: t.pos_x, pos_y: t.pos_y, region_id: t.table_region_id,
            }));
            try {
                const data = await posAjax('{{ route("pos.tables.layout") }}', { positions });
                if (data.success) { this.layoutDirty = false; showToast('Layout kaydedildi ✓', 'success'); }
                else showToast(data.message || 'Hata', 'error');
            } catch { showToast('Sunucu hatası', 'error'); }
        },

        goToTable(table) {
            window.location.href = `/tables/${table.id}/detail`;
        },

        // ─── Mekan Modal ─────────────────────────────────────

        openRegionModal(region = null) {
            this.editingRegion = region;
            this.regionForm = region
                ? { name: region.name, bg_color: region.bg_color || '#f0f9ff', icon: region.icon || 'fa-location-dot', description: region.description || '' }
                : { name: '', bg_color: '#f0f9ff', icon: 'fa-location-dot', description: '' };
            this.showRegionModal = true;
        },

        async saveRegion() {
            if (!this.regionForm.name.trim()) return;
            try {
                const url    = this.editingRegion ? `/regions/${this.editingRegion.id}` : '/regions';
                const method = this.editingRegion ? 'PUT' : 'POST';
                const data = await posAjax(url, this.regionForm, method);
                if (data.success) {
                    if (this.editingRegion) {
                        const i = this.regions.findIndex(r => r.id === this.editingRegion.id);
                        if (i !== -1) this.regions[i] = data.region;
                    } else {
                        this.regions.push(data.region);
                    }
                    this.showRegionModal = false;
                    showToast(this.editingRegion ? 'Mekan güncellendi' : 'Mekan eklendi', 'success');
                } else showToast(data.message || 'Hata', 'error');
            } catch { showToast('Sunucu hatası', 'error'); }
        },

        async deleteRegion(id) {
            const region = this.regions.find(r => r.id === id);
            const count = this.tablesInRegion(id).length;
            const msg = count > 0
                ? `"${region?.name}" mekanında ${count} masa var. Masalar mekansız kalacak. Devam?`
                : `"${region?.name}" mekanını silmek istiyor musunuz?`;
            if (!confirm(msg)) return;
            try {
                const data = await posAjax(`/regions/${id}`, {}, 'DELETE');
                if (data.success) {
                    this.regions = this.regions.filter(r => r.id !== id);
                    this.tables.forEach(t => { if (t.table_region_id === id) t.table_region_id = null; });
                    if (this.selectedRegion === id) this.selectedRegion = null;
                    showToast('Mekan silindi', 'success');
                } else showToast(data.message || 'Hata', 'error');
            } catch { showToast('Sunucu hatası', 'error'); }
        },

        // ─── Masa Modal ──────────────────────────────────────

        openTableModal(table = null, defaultRegionId = null) {
            this.editingTable = table;
            this.tableForm = table
                ? { table_no: table.table_no, name: table.name, capacity: table.capacity, shape: table.shape || 'square', table_region_id: table.table_region_id }
                : { table_no: '', name: '', capacity: 4, shape: 'square', table_region_id: defaultRegionId };
            this.showTableModal = true;
        },

        async saveTable() {
            if (!this.tableForm.table_no || !this.tableForm.name) return;
            try {
                const url    = this.editingTable ? `/tables/${this.editingTable.id}/update` : '/tables/store';
                const method = this.editingTable ? 'PUT' : 'POST';
                const data = await posAjax(url, this.tableForm, method);
                if (data.success) {
                    if (this.editingTable) {
                        const i = this.tables.findIndex(t => t.id === this.editingTable.id);
                        if (i !== -1) this.tables[i] = { ...this.tables[i], ...data.table };
                    } else {
                        this.tables.push({
                            id: data.table.id, table_no: data.table.table_no, name: data.table.name,
                            capacity: data.table.capacity, status: 'empty',
                            shape: data.table.shape || 'square', color: data.table.color,
                            table_region_id: data.table.table_region_id,
                            pos_x: data.table.pos_x || 5, pos_y: data.table.pos_y || 5,
                            active_session: null,
                        });
                    }
                    this.showTableModal = false;
                    showToast(this.editingTable ? 'Masa güncellendi' : 'Masa eklendi', 'success');
                } else showToast(data.message || 'Hata', 'error');
            } catch { showToast('Sunucu hatası', 'error'); }
        },

        async deleteTable(id) {
            const table = this.tables.find(t => t.id === id);
            if (!confirm(`"${table?.name}" masasını silmek istiyor musunuz?`)) return;
            try {
                const data = await posAjax(`/tables/${id}/destroy`, {}, 'DELETE');
                if (data.success) { this.tables = this.tables.filter(t => t.id !== id); showToast('Masa silindi', 'success'); }
                else showToast(data.message || 'Hata', 'error');
            } catch { showToast('Sunucu hatası', 'error'); }
        },
    }
}
</script>
@endpush

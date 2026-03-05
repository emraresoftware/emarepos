@extends('pos.layouts.app')
@section('title', 'Masa Yönetimi')

@section('content')
<div x-data="tableManager()" x-init="init()" class="flex-1 flex flex-col overflow-hidden">
    
    {{-- Üst Bar --}}
    <div class="p-4 bg-gray-50 border-b border-gray-700 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <h1 class="text-xl font-bold text-gray-900">Masa Haritası</h1>
            {{-- Bölge Filtreleri --}}
            <div class="flex gap-1.5">
                <button @click="filterRegion(null)" 
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        :class="selectedRegion === null ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-gray-900' : 'bg-slate-700 text-gray-500 hover:text-gray-800'">
                    Tümü
                </button>
                @foreach($regions as $region)
                <button @click="filterRegion({{ $region->id }})" 
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        :class="selectedRegion === {{ $region->id }} ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-gray-900' : 'bg-slate-700 text-gray-500 hover:text-gray-800'">
                    {{ $region->name }}
                </button>
                @endforeach
            </div>
        </div>
        <div class="flex items-center gap-3 text-sm">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-green-500 rounded-full inline-block"></span> Boş</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-red-500 rounded-full inline-block"></span> Dolu</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-amber-500 rounded-full inline-block"></span> Reserve</span>
        </div>
    </div>

    {{-- Masa Grid --}}
    <div class="flex-1 overflow-y-auto p-6">
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8 gap-4">
            @foreach($tables as $table)
            <a href="{{ route('pos.tables.detail', $table->id) }}"
               class="relative group cursor-pointer">
                <div class="aspect-square rounded-2xl border-2 flex flex-col items-center justify-center p-3 transition-all
                    {{ $table->status === 'occupied' ? 'bg-red-900/30 border-red-600 hover:bg-red-900/50' : 
                       ($table->status === 'reserved' ? 'bg-amber-900/30 border-amber-600 hover:bg-amber-900/50' : 
                       'bg-green-900/20 border-green-600/50 hover:bg-green-900/40') }}"
                    data-region="{{ $table->table_region_id }}">
                    
                    {{-- Masa Numarası --}}
                    <div class="text-2xl font-bold {{ $table->status === 'occupied' ? 'text-red-500' : ($table->status === 'reserved' ? 'text-amber-600' : 'text-emerald-500') }}">
                        {{ $table->table_no }}
                    </div>
                    
                    {{-- Masa Adı --}}
                    <div class="text-xs text-gray-500 mt-1 truncate w-full text-center">{{ $table->name }}</div>
                    
                    {{-- Kapasite --}}
                    <div class="flex items-center gap-1 mt-1 text-xs text-gray-500">
                        <i class="fas fa-users"></i>
                        <span>{{ $table->capacity }}</span>
                    </div>

                    @if($table->status === 'occupied' && $table->activeSession)
                    {{-- Toplam Tutar --}}
                    <div class="mt-1 text-sm font-bold text-gray-900">
                        {{ number_format($table->activeSession->orders->sum(function($o) { return $o->items->sum('total'); }), 2) }} ₺
                    </div>
                    {{-- Süre --}}
                    <div class="text-[10px] text-gray-500 mt-0.5">
                        {{ $table->activeSession->opened_at->diffForHumans(null, true) }}
                    </div>
                    @endif
                </div>
                
                @if($table->status === 'occupied')
                <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full animate-pulse"></div>
                @endif
            </a>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function tableManager() {
    return {
        selectedRegion: null,
        
        init() {
            // Auto-refresh every 30 seconds
            setInterval(() => location.reload(), 30000);
        },
        
        filterRegion(regionId) {
            this.selectedRegion = regionId;
            document.querySelectorAll('[data-region]').forEach(el => {
                const parent = el.closest('a');
                if (!regionId || el.dataset.region == regionId) {
                    parent.style.display = '';
                } else {
                    parent.style.display = 'none';
                }
            });
        }
    };
}
</script>
@endpush

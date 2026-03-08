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
                    <button @click="openEdit({{ json_encode(['id' => $branch->id, 'name' => $branch->name, 'code' => $branch->code, 'address' => $branch->address, 'phone' => $branch->phone, 'city' => $branch->city, 'district' => $branch->district, 'is_active' => $branch->is_active]) }})"
                            class="p-2 text-gray-500 hover:text-yellow-400 hover:bg-yellow-500/10 rounded-lg transition-colors opacity-0 group-hover:opacity-100" title="Düzenle">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
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
        <div class="relative bg-white rounded-xl border border-gray-100 shadow-2xl w-full max-w-lg" x-transition>
            <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900" x-text="editingId ? 'Şube Düzenle' : 'Yeni Şube'"></h2>
                <button @click="showModal = false" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form @submit.prevent="submitForm()" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
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
                <div class="grid grid-cols-3 gap-4">
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
                <div class="flex gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="showModal = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 rounded-lg">İptal</button>
                    <button type="submit" :disabled="saving" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-900 bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 rounded-lg disabled:opacity-50">
                        <span x-text="editingId ? 'Güncelle' : 'Kaydet'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function branchManager() {
    return {
        showModal: false, editingId: null, saving: false,
        form: { name: '', code: '', address: '', phone: '', city: '', district: '' },
        openCreate() { this.editingId = null; this.form = { name: '', code: '', address: '', phone: '', city: '', district: '' }; this.showModal = true; },
        openEdit(b) { this.editingId = b.id; this.form = { name: b.name||'', code: b.code||'', address: b.address||'', phone: b.phone||'', city: b.city||'', district: b.district||'' }; this.showModal = true; },
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

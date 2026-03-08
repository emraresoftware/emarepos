@extends('pos.layouts.app')

@section('title', 'Ayarlar')

@section('content')
<div x-data="{ activeTab: 'branch' }" class="p-3 sm:p-6 overflow-y-auto h-full">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Ayarlar</h1>

    {{-- Tabs --}}
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-100 pb-3 overflow-x-auto hide-scrollbar">
        <button @click="activeTab = 'branch'" :class="activeTab === 'branch' ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-white' : 'bg-white text-gray-500 hover:text-gray-800'" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            <i class="fas fa-store mr-1"></i> Şube Bilgileri
        </button>
        <button @click="activeTab = 'general'" :class="activeTab === 'general' ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-white' : 'bg-white text-gray-500 hover:text-gray-800'" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            <i class="fas fa-cog mr-1"></i> Genel Ayarlar
        </button>
        <button @click="activeTab = 'payment'" :class="activeTab === 'payment' ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-white' : 'bg-white text-gray-500 hover:text-gray-800'" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            <i class="fas fa-credit-card mr-1"></i> Ödeme Tipleri
        </button>
        <button @click="activeTab = 'tax'" :class="activeTab === 'tax' ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-white' : 'bg-white text-gray-500 hover:text-gray-800'" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            <i class="fas fa-percent mr-1"></i> Vergi Oranları
        </button>
    </div>

    {{-- Branch Settings Tab --}}
    <div x-show="activeTab === 'branch'" x-transition>
        <form method="POST" action="{{ url('/settings/branch') }}" class="bg-white rounded-xl border border-gray-100 p-6 space-y-5">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Şube Adı</label>
                    <input type="text" name="name" value="{{ $branch->name ?? '' }}" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Telefon</label>
                    <input type="text" name="phone" value="{{ $branch->phone ?? '' }}" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-500 mb-1">Adres</label>
                    <textarea name="address" rows="2" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">{{ $branch->address ?? '' }}</textarea>
                </div>
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Şehir</label>
                    <input type="text" name="city" value="{{ $branch->city ?? '' }}" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm text-gray-500 mb-1">İlçe</label>
                    <input type="text" name="district" value="{{ $branch->district ?? '' }}" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">
                </div>
            </div>
            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                    <i class="fas fa-save mr-1"></i> Kaydet
                </button>
            </div>
        </form>
    </div>

    {{-- General Settings Tab --}}
    <div x-show="activeTab === 'general'" x-transition>
        <form method="POST" action="{{ url('/settings/general') }}" class="bg-white rounded-xl border border-gray-100 p-6 space-y-5">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Fiş Üst Yazı</label>
                    <textarea name="receipt_header" rows="3" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">{{ $tenant->meta['receipt_header'] ?? '' }}</textarea>
                </div>
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Fiş Alt Yazı</label>
                    <textarea name="receipt_footer" rows="3" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">{{ $tenant->meta['receipt_footer'] ?? '' }}</textarea>
                </div>
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Para Birimi Sembolü</label>
                    <input type="text" name="currency_symbol" value="{{ $tenant->meta['currency_symbol'] ?? '₺' }}" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5 space-y-4">
                <h3 class="text-sm font-medium text-gray-700">Seçenekler</h3>
                <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="tax_included" value="1" {{ ($tenant->meta['tax_included'] ?? false) ? 'checked' : '' }}
                        class="w-4 h-4 rounded bg-white border-gray-200 text-blue-600 focus:ring-brand-500/20">
                    Fiyatlara KDV dahil
                </label>
                <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="auto_print_receipt" value="1" {{ ($tenant->meta['auto_print_receipt'] ?? false) ? 'checked' : '' }}
                        class="w-4 h-4 rounded bg-white border-gray-200 text-blue-600 focus:ring-brand-500/20">
                    Satış sonrası otomatik fiş yazdır
                </label>
                <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="kitchen_print" value="1" {{ ($tenant->meta['kitchen_print'] ?? false) ? 'checked' : '' }}
                        class="w-4 h-4 rounded bg-white border-gray-200 text-blue-600 focus:ring-brand-500/20">
                    Mutfak yazıcısı aktif
                </label>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                    <i class="fas fa-save mr-1"></i> Kaydet
                </button>
            </div>
        </form>
    </div>

    {{-- Payment Types Tab --}}
    <div x-show="activeTab === 'payment'" x-transition x-data="paymentTypeManager()">
        {{-- Yeni Ödeme Türü Ekle --}}
        <div class="bg-white rounded-xl border border-gray-100 p-4 mb-4">
            <h4 class="text-sm font-semibold text-gray-800 mb-3"><i class="fas fa-plus-circle text-brand-500 mr-1"></i> Yeni Ödeme Türü Ekle</h4>
            <div class="flex items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Ödeme Türü Adı *</label>
                    <input type="text" x-model="newType.name" @keydown.enter="addType()"
                           placeholder="Örn: Yemek Kartı, EFT POS, Online Ödeme..."
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-800 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                </div>
                <div class="w-40">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Kod (opsiyonel)</label>
                    <input type="text" x-model="newType.code" @keydown.enter="addType()"
                           placeholder="Örn: yemek_karti"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-800 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                </div>
                <button @click="addType()" :disabled="!newType.name.trim() || saving"
                        class="px-5 py-2 bg-gradient-to-r from-brand-500 to-purple-600 text-white text-sm font-medium rounded-lg hover:opacity-90 disabled:opacity-50 transition-all whitespace-nowrap">
                    <i class="fas fa-plus mr-1"></i> Ekle
                </button>
            </div>
            {{-- Hızlı Ekleme Önerileri --}}
            <div class="flex gap-2 mt-3 flex-wrap">
                <template x-for="preset in presets" :key="preset">
                    <button @click="newType.name = preset" class="px-3 py-1 bg-gray-100 hover:bg-brand-50 text-gray-600 hover:text-brand-600 text-xs rounded-lg border border-gray-200 hover:border-brand-200 transition-colors" x-text="preset"></button>
                </template>
            </div>
        </div>

        {{-- Mevcut Ödeme Türleri --}}
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-700">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3.5">Ad</th>
                            <th class="px-4 py-3.5">Kod</th>
                            <th class="px-4 py-3.5 text-center">Sıra</th>
                            <th class="px-4 py-3.5 text-center">Durum</th>
                            <th class="px-4 py-3.5 text-center">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="pt in types" :key="pt.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <template x-if="editingId !== pt.id">
                                        <span class="text-gray-900 font-medium" x-text="pt.name"></span>
                                    </template>
                                    <template x-if="editingId === pt.id">
                                        <input type="text" x-model="editForm.name" class="w-full px-2 py-1 border border-brand-300 rounded text-sm text-gray-800 focus:outline-none focus:ring-1 focus:ring-brand-400">
                                    </template>
                                </td>
                                <td class="px-4 py-3">
                                    <template x-if="editingId !== pt.id">
                                        <span class="font-mono text-gray-500" x-text="pt.code || '-'"></span>
                                    </template>
                                    <template x-if="editingId === pt.id">
                                        <input type="text" x-model="editForm.code" class="w-full px-2 py-1 border border-brand-300 rounded text-sm text-gray-800 focus:outline-none focus:ring-1 focus:ring-brand-400">
                                    </template>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-xs text-gray-500" x-text="pt.sort_order"></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button @click="toggleActive(pt)"
                                            class="text-xs px-2.5 py-1 rounded-full border transition-colors"
                                            :class="pt.is_active ? 'bg-green-500/10 text-emerald-600 border-green-500/30 hover:bg-red-50 hover:text-red-500 hover:border-red-300' : 'bg-red-500/10 text-red-500 border-red-500/30 hover:bg-green-50 hover:text-emerald-600 hover:border-green-300'"
                                            x-text="pt.is_active ? 'Aktif' : 'Pasif'"></button>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <template x-if="editingId !== pt.id">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="startEdit(pt)" class="text-brand-500 hover:text-brand-700 text-xs"><i class="fas fa-edit"></i></button>
                                            <button @click="deleteType(pt)" class="text-red-400 hover:text-red-600 text-xs"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </template>
                                    <template x-if="editingId === pt.id">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="saveEdit(pt)" class="text-emerald-500 hover:text-emerald-700 text-xs font-medium"><i class="fas fa-check mr-0.5"></i>Kaydet</button>
                                            <button @click="editingId = null" class="text-gray-400 hover:text-gray-600 text-xs"><i class="fas fa-times"></i></button>
                                        </div>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="types.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">Ödeme türü bulunamadı. Yukarıdan ekleyin.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function paymentTypeManager() {
        return {
            types: @json($paymentTypes),
            newType: { name: '', code: '' },
            saving: false,
            editingId: null,
            editForm: { name: '', code: '' },
            presets: ['Havale', 'Yemek Kartı', 'EFT POS', 'Seyyar POS', 'Online Ödeme', 'Çek', 'Senet', 'Mobil Ödeme'],

            async addType() {
                if (!this.newType.name.trim()) return;
                this.saving = true;
                try {
                    const data = await posAjax('{{ route("pos.payment-types.store") }}', this.newType);
                    if (data.success) {
                        this.types.push(data.paymentType);
                        this.newType = { name: '', code: '' };
                        showToast('Ödeme türü eklendi!', 'success');
                    }
                } catch(e) { showToast(e.message || 'Eklenemedi', 'error'); }
                this.saving = false;
            },

            startEdit(pt) {
                this.editingId = pt.id;
                this.editForm = { name: pt.name, code: pt.code || '' };
            },

            async saveEdit(pt) {
                try {
                    const data = await posAjax('/payment-types/' + pt.id, this.editForm, 'PUT');
                    if (data.success) {
                        pt.name = data.paymentType.name;
                        pt.code = data.paymentType.code;
                        this.editingId = null;
                        showToast('Güncellendi!', 'success');
                    }
                } catch(e) { showToast(e.message || 'Güncellenemedi', 'error'); }
            },

            async toggleActive(pt) {
                try {
                    const data = await posAjax('/payment-types/' + pt.id, { ...pt, is_active: !pt.is_active }, 'PUT');
                    if (data.success) {
                        pt.is_active = data.paymentType.is_active;
                        showToast(pt.is_active ? 'Aktifleştirildi' : 'Pasifleştirildi', 'success');
                    }
                } catch(e) { showToast(e.message || 'Güncellenemedi', 'error'); }
            },

            async deleteType(pt) {
                if (!confirm(pt.name + ' ödeme türünü silmek istediğinize emin misiniz?')) return;
                try {
                    const data = await posAjax('/payment-types/' + pt.id, {}, 'DELETE');
                    if (data.success) {
                        this.types = this.types.filter(t => t.id !== pt.id);
                        showToast('Silindi!', 'success');
                    }
                } catch(e) { showToast(e.message || 'Silinemedi', 'error'); }
            },
        };
    }
    </script>

    {{-- Tax Rates Tab --}}
    <div x-show="activeTab === 'tax'" x-transition>
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-700">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3.5">Vergi Adı</th>
                            <th class="px-4 py-3.5 text-right">Oran (%)</th>
                            <th class="px-4 py-3.5 text-center">Durum</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($taxRates as $tax)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-gray-900">{{ $tax->name }}</td>
                                <td class="px-4 py-3 text-right font-mono text-gray-900">%{{ $tax->rate }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($tax->is_active ?? true)
                                        <span class="text-xs bg-green-500/10 text-emerald-600 px-2.5 py-1 rounded-full border border-green-500/30">Aktif</span>
                                    @else
                                        <span class="text-xs bg-red-500/10 text-red-500 px-2.5 py-1 rounded-full border border-red-500/30">Pasif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-gray-500">Vergi oranı bulunamadı</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

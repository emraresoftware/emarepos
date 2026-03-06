@extends('pos.layouts.app')
@section('title', 'Personel Yönetimi')

@section('content')
<div class="flex-1 overflow-y-auto p-5 space-y-5" x-data="staffManager()" x-init="init()" x-cloak>

    {{-- ── Başlık ── --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Personel</h1>
            <p class="text-sm text-gray-500 mt-0.5">Çalışan listesi ve performans takibi</p>
        </div>
        <button @click="openModal()"
                class="px-4 py-2 rounded-xl text-sm font-semibold text-white
                       bg-gradient-to-r from-brand-500 to-purple-600
                       shadow-lg shadow-brand-500/20 hover:shadow-brand-500/40
                       hover:scale-105 transition-all duration-200">
            <i class="fas fa-user-plus mr-1.5"></i> Personel Ekle
        </button>
    </div>

    {{-- ── İstatistikler ── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-500 text-xs font-medium">Toplam Personel</span>
                <div class="w-9 h-9 rounded-xl bg-brand-50 flex items-center justify-center">
                    <i class="fas fa-users text-brand-500 text-sm"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-500 text-xs font-medium">Aktif</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <i class="fas fa-circle-check text-emerald-500 text-sm"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-emerald-600">{{ $stats['active'] }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-500 text-xs font-medium">Toplam Satış</span>
                <div class="w-9 h-9 rounded-xl bg-purple-50 flex items-center justify-center">
                    <i class="fas fa-coins text-purple-500 text-sm"></i>
                </div>
            </div>
            <div class="text-lg font-bold text-purple-600">{{ number_format($stats['total_sales'], 2, ',', '.') }} ₺</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-500 text-xs font-medium">En Çok Satan</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center">
                    <i class="fas fa-trophy text-amber-500 text-sm"></i>
                </div>
            </div>
            <div class="text-sm font-bold text-amber-600 truncate">{{ $stats['top_seller'] }}</div>
        </div>
    </div>

    {{-- ── Arama ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-lg shadow-gray-100/50 p-4">
        <form method="GET" action="{{ route('pos.staff') }}" class="flex items-center gap-2">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="İsim, telefon veya görev ara..."
                       class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm transition-all">
            </div>
            <select name="active" class="px-3 py-2.5 rounded-xl border border-gray-200 focus:border-brand-500 outline-none text-sm text-gray-600">
                <option value="">Tümü</option>
                <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Pasif</option>
            </select>
            <button type="submit"
                    class="px-4 py-2.5 bg-brand-500 hover:bg-brand-600 text-white rounded-xl text-sm font-medium transition-colors">
                <i class="fas fa-filter"></i>
            </button>
        </form>
    </div>

    {{-- ── Tablo ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-lg shadow-gray-100/50 overflow-hidden">
        @if($staff->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Personel</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Görev</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">İletişim</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Satış</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">İşlem</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Durum</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($staff as $member)
                    <tr class="hover:bg-gray-50 transition-colors group">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-500/10 to-purple-500/10
                                            flex items-center justify-center text-brand-600 font-bold text-sm">
                                    {{ mb_substr($member->name, 0, 1) }}
                                </div>
                                <span class="font-semibold text-gray-800">{{ $member->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-gray-500">
                            @if($member->role)
                                <span class="px-2.5 py-1 bg-brand-50 text-brand-600 rounded-full text-xs font-medium">{{ $member->role }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-gray-500 text-xs space-y-0.5">
                            @if($member->phone)
                                <div><i class="fas fa-phone w-3 mr-1"></i>{{ $member->phone }}</div>
                            @endif
                            @if($member->email)
                                <div><i class="fas fa-envelope w-3 mr-1"></i>{{ $member->email }}</div>
                            @endif
                            @if(!$member->phone && !$member->email)
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right font-bold text-gray-800">
                            {{ number_format($member->total_sales, 2, ',', '.') }} ₺
                        </td>
                        <td class="px-5 py-3.5 text-right text-gray-500">
                            {{ number_format($member->total_transactions) }}
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            @if($member->is_active)
                                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-medium">Aktif</span>
                            @else
                                <span class="px-2.5 py-1 bg-gray-100 text-gray-500 rounded-full text-xs font-medium">Pasif</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="editMember({{ json_encode($member) }})"
                                        class="p-1.5 text-gray-400 hover:text-brand-500 hover:bg-brand-50 rounded-lg transition-colors">
                                    <i class="fas fa-pen text-xs"></i>
                                </button>
                                <button @click="deleteMember({{ $member->id }})"
                                        class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-100">{{ $staff->withQueryString()->links() }}</div>
        @else
        <div class="flex flex-col items-center justify-center py-20 text-gray-400">
            <i class="fas fa-id-badge text-5xl mb-4 text-gray-200"></i>
            <p class="font-medium text-gray-500">Henüz personel eklenmemiş</p>
            <p class="text-sm mt-1">Çalışanlarınızı ekleyerek satış takibini başlatın</p>
            <button @click="openModal()"
                    class="mt-4 px-5 py-2.5 bg-gradient-to-r from-brand-500 to-purple-600 text-white rounded-xl text-sm font-semibold shadow-lg shadow-brand-500/20 hover:scale-105 transition-all">
                <i class="fas fa-user-plus mr-1.5"></i> Personel Ekle
            </button>
        </div>
        @endif
    </div>

    {{-- ── MODAL ── --}}
    <div x-show="showModal" x-transition.opacity class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.outside="showModal = false"
             class="bg-white rounded-3xl shadow-2xl w-full max-w-md"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="p-6">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500/10 to-purple-500/10
                                    flex items-center justify-center">
                            <i class="fas fa-id-badge text-brand-500"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900" x-text="editId ? 'Personel Düzenle' : 'Yeni Personel'"></h3>
                    </div>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 p-1"><i class="fas fa-times"></i></button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Ad Soyad <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.name" placeholder="örn. Ahmet Yılmaz"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Görev / Pozisyon</label>
                        <input type="text" x-model="form.role" placeholder="örn. Garson, Kasiyer, Şef..."
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm transition-all">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Telefon</label>
                            <input type="tel" x-model="form.phone" placeholder="05XX XXX XX XX"
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">E-posta</label>
                            <input type="email" x-model="form.email" placeholder="ornek@email.com"
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm transition-all">
                        </div>
                    </div>
                    <div x-show="editId">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Durum</label>
                        <div class="flex gap-2">
                            <button @click="form.is_active = true"
                                    :class="form.is_active ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 text-gray-500'"
                                    class="flex-1 py-2 border-2 rounded-xl text-sm font-medium transition-all">
                                <i class="fas fa-circle-check mr-1"></i> Aktif
                            </button>
                            <button @click="form.is_active = false"
                                    :class="!form.is_active ? 'border-gray-500 bg-gray-100 text-gray-700' : 'border-gray-200 text-gray-400'"
                                    class="flex-1 py-2 border-2 rounded-xl text-sm font-medium transition-all">
                                <i class="fas fa-circle-xmark mr-1"></i> Pasif
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 mt-5">
                    <button @click="showModal = false"
                            class="flex-1 px-4 py-2.5 rounded-xl border-2 border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-all">
                        İptal
                    </button>
                    <button @click="submitForm()" :disabled="saving"
                            class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold text-white
                                   bg-gradient-to-r from-brand-500 to-purple-600
                                   shadow-lg shadow-brand-500/20 hover:scale-[1.02] transition-all">
                        <span x-show="!saving">Kaydet</span>
                        <span x-show="saving"><i class="fas fa-spinner fa-spin mr-1"></i> Kaydediliyor...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function staffManager() {
    return {
        showModal: false,
        saving: false,
        editId: null,
        form: { name: '', role: '', phone: '', email: '', is_active: true },

        init() {},

        openModal() {
            this.editId = null;
            this.form = { name: '', role: '', phone: '', email: '', is_active: true };
            this.showModal = true;
        },

        editMember(member) {
            this.editId = member.id;
            this.form = {
                name: member.name,
                role: member.role || '',
                phone: member.phone || '',
                email: member.email || '',
                is_active: member.is_active,
            };
            this.showModal = true;
        },

        async submitForm() {
            if (!this.form.name) {
                showToast('Ad Soyad zorunlu', 'error');
                return;
            }
            this.saving = true;
            try {
                if (this.editId) {
                    await posAjax(`/staff/${this.editId}`, { method: 'PUT', body: JSON.stringify(this.form) });
                    showToast('Personel güncellendi', 'success');
                } else {
                    await posAjax('{{ route("pos.staff.store") }}', { method: 'POST', body: JSON.stringify(this.form) });
                    showToast('Personel eklendi', 'success');
                }
                this.showModal = false;
                setTimeout(() => window.location.reload(), 600);
            } catch {
                showToast('Bir hata oluştu', 'error');
            } finally {
                this.saving = false;
            }
        },

        async deleteMember(id) {
            if (!confirm('Bu personeli silmek istediğinize emin misiniz?')) return;
            try {
                await posAjax(`/staff/${id}`, { method: 'DELETE' });
                showToast('Personel silindi', 'success');
                setTimeout(() => window.location.reload(), 500);
            } catch {
                showToast('Silme işlemi başarısız', 'error');
            }
        },
    };
}
</script>
@endpush
@endsection

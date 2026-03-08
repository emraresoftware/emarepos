@extends('pos.layouts.app')

@section('title', 'Kullanıcılar')

@section('content')
<div class="p-3 sm:p-6 overflow-y-auto h-full" x-data="userManager()" x-cloak>
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Kullanıcılar</h1>
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative w-full sm:w-auto">
                <input type="text" x-model="searchQuery" @input.debounce.400ms="applySearch()"
                       placeholder="Kullanıcı ara..."
                       class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg pl-9 pr-4 py-2.5 w-full sm:w-64 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <button @click="openCreate()"
                    class="bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 text-white font-medium rounded-lg text-sm px-5 py-2.5 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Yeni Kullanıcı
            </button>
        </div>
    </div>

    {{-- Users Table --}}
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3.5">Ad Soyad</th>
                        <th class="px-4 py-3.5">E-posta</th>
                        <th class="px-4 py-3.5">Rol</th>
                        <th class="px-4 py-3.5">Şube</th>
                        <th class="px-4 py-3.5">Kayıt Tarihi</th>
                        <th class="px-4 py-3.5 text-center">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-brand-500/10 flex items-center justify-center text-sm font-semibold text-brand-500">
                                        {{ mb_substr($user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $user->name }}</p>
                                        @if($user->is_super_admin)
                                            <span class="text-xs text-yellow-400">Süper Admin</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs bg-purple-500/10 text-purple-600 px-2 py-1 rounded-full">{{ $user->role->name ?? 'Tanımsız' }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $user->branch->name ?? 'Tümü' }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $user->created_at->format('d.m.Y') }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="openEdit({{ json_encode(['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role_id' => $user->role_id, 'branch_id' => $user->branch_id]) }})"
                                            class="p-2 text-gray-500 hover:text-yellow-400 hover:bg-yellow-500/10 rounded-lg transition-colors" title="Düzenle">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    @if($user->id !== auth()->id())
                                        <button @click="deleteUser({{ $user->id }})"
                                                class="p-2 text-gray-500 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Sil">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <p class="text-gray-500 text-sm">Kullanıcı bulunamadı</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $users->links() }}</div>
        @endif
    </div>

    {{-- User Form Modal --}}
    <div x-show="showModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="relative bg-white rounded-xl border border-gray-100 shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" x-transition>
            <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900" x-text="editingId ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı'"></h2>
                <button @click="showModal = false" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form @submit.prevent="submitForm()" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Ad Soyad <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.name" required class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">E-posta <span class="text-red-500">*</span></label>
                    <input type="email" x-model="form.email" required class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Şifre <span x-show="!editingId" class="text-red-500">*</span></label>
                    <input type="password" x-model="form.password" :required="!editingId" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5" placeholder="Boş bırakılırsa değişmez">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Rol</label>
                        <select x-model="form.role_id" class="w-full bg-white border border-gray-200 text-gray-700 text-sm rounded-lg px-4 py-2.5">
                            <option value="">Seçiniz</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Şube</label>
                        <select x-model="form.branch_id" class="w-full bg-white border border-gray-200 text-gray-700 text-sm rounded-lg px-4 py-2.5">
                            <option value="">Tüm Şubeler</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
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
function userManager() {
    return {
        showModal: false, editingId: null, saving: false,
        searchQuery: new URLSearchParams(window.location.search).get('search') || '',
        form: { name: '', email: '', password: '', role_id: '', branch_id: '' },
        openCreate() { this.editingId = null; this.form = { name: '', email: '', password: '', role_id: '', branch_id: '' }; this.showModal = true; },
        openEdit(u) { this.editingId = u.id; this.form = { name: u.name, email: u.email, password: '', role_id: u.role_id || '', branch_id: u.branch_id || '' }; this.showModal = true; },
        applySearch() {
            const params = new URLSearchParams();
            if (this.searchQuery) params.set('search', this.searchQuery);
            window.location.href = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        },
        async submitForm() {
            this.saving = true;
            const url = this.editingId ? `/users/${this.editingId}` : '/users';
            try {
                await posAjax(url, { method: this.editingId ? 'PUT' : 'POST', body: JSON.stringify(this.form) });
                showToast(this.editingId ? 'Kullanıcı güncellendi' : 'Kullanıcı oluşturuldu', 'success');
                this.showModal = false; window.location.reload();
            } catch (e) { showToast(e.message || 'Hata', 'error'); }
            finally { this.saving = false; }
        },
        async deleteUser(id) {
            if (!confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')) return;
            try { await posAjax(`/users/${id}`, { method: 'DELETE' }); showToast('Kullanıcı silindi', 'success'); window.location.reload(); }
            catch (e) { showToast(e.message || 'Silinemedi', 'error'); }
        }
    };
}
</script>
@endpush

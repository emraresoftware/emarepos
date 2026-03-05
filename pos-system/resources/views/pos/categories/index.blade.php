@extends('pos.layouts.app')

@section('title', 'Kategoriler')

@section('content')
<div class="p-6 overflow-y-auto h-full" x-data="categoryManager()" x-cloak>
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Kategoriler</h1>
            <p class="text-sm text-gray-500 mt-1">Toplam {{ $categories->count() }} kategori</p>
        </div>
        <button @click="openCreate()"
                class="bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 text-gray-900 font-medium rounded-lg text-sm px-5 py-2.5 transition-colors flex items-center gap-2 justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Kategori
        </button>
    </div>

    {{-- Category Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($categories as $category)
            <div class="bg-white rounded-xl border border-gray-100 p-5 hover:border-blue-500/30 transition-colors group">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg {{ $category->is_active ? 'bg-brand-500/10' : 'bg-gray-100/50' }} flex items-center justify-center">
                        <svg class="w-5 h-5 {{ $category->is_active ? 'text-brand-500' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    </div>
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button @click="openEdit({{ json_encode(['id' => $category->id, 'name' => $category->name, 'parent_id' => $category->parent_id, 'sort_order' => $category->sort_order, 'is_active' => $category->is_active]) }})"
                                class="p-1.5 text-gray-500 hover:text-yellow-400 hover:bg-yellow-500/10 rounded-lg transition-colors" title="Düzenle">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button @click="deleteCategory({{ $category->id }})"
                                class="p-1.5 text-gray-500 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Sil">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <h3 class="text-gray-900 font-semibold text-base">{{ $category->name }}</h3>
                <div class="flex items-center justify-between mt-3">
                    <span class="text-xs text-gray-500">{{ $category->products_count }} ürün</span>
                    <div class="flex items-center gap-2">
                        @if($category->parent_id)
                            <span class="text-xs bg-purple-500/10 text-purple-600 px-2 py-0.5 rounded-full">Alt kategori</span>
                        @endif
                        @if(!$category->is_active)
                            <span class="text-xs bg-red-500/10 text-red-500 px-2 py-0.5 rounded-full">Pasif</span>
                        @endif
                        <span class="text-xs text-gray-500">Sıra: {{ $category->sort_order }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <svg class="w-12 h-12 text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <p class="text-gray-500 text-sm">Henüz kategori eklenmemiş</p>
                <button @click="openCreate()" class="text-brand-500 hover:text-brand-600 text-sm font-medium mt-2">+ İlk kategoriyi ekle</button>
            </div>
        @endforelse
    </div>

    {{-- Category Form Modal --}}
    <div x-show="showModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="relative bg-white rounded-xl border border-gray-100 shadow-2xl w-full max-w-md" x-transition>
            <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900" x-text="editingId ? 'Kategori Düzenle' : 'Yeni Kategori'"></h2>
                <button @click="showModal = false" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form @submit.prevent="submitForm()" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Kategori Adı <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.name" required class="w-full bg-white border border-gray-700 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400" placeholder="Kategori adı">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Üst Kategori</label>
                    <select x-model="form.parent_id" class="w-full bg-white border border-gray-700 text-gray-700 text-sm rounded-lg px-4 py-2.5 focus:ring-brand-500/20 focus:border-brand-500">
                        <option value="">Ana Kategori</option>
                        @foreach($categories->whereNull('parent_id') as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Sıralama</label>
                        <input type="number" x-model="form.sort_order" class="w-full bg-white border border-gray-700 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-brand-500/20 focus:border-brand-500" placeholder="0">
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="form.is_active" class="w-4 h-4 rounded border-gray-600 bg-white text-blue-500 focus:ring-brand-500/20">
                            <span class="text-sm text-gray-700">Aktif</span>
                        </label>
                    </div>
                </div>
                <div class="flex gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="showModal = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-700 rounded-lg transition-colors">İptal</button>
                    <button type="submit" :disabled="saving" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-900 bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 rounded-lg transition-colors disabled:opacity-50">
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
function categoryManager() {
    return {
        showModal: false, editingId: null, saving: false,
        form: { name: '', parent_id: '', sort_order: 0, is_active: true },
        openCreate() { this.editingId = null; this.form = { name: '', parent_id: '', sort_order: 0, is_active: true }; this.showModal = true; },
        openEdit(cat) { this.editingId = cat.id; this.form = { name: cat.name, parent_id: cat.parent_id || '', sort_order: cat.sort_order, is_active: cat.is_active }; this.showModal = true; },
        async submitForm() {
            this.saving = true;
            const url = this.editingId ? `/categories/${this.editingId}` : '/categories';
            const method = this.editingId ? 'PUT' : 'POST';
            try {
                await posAjax(url, { method, body: JSON.stringify(this.form) });
                showToast(this.editingId ? 'Kategori güncellendi' : 'Kategori oluşturuldu', 'success');
                this.showModal = false; window.location.reload();
            } catch (e) { showToast(e.message || 'Hata oluştu', 'error'); }
            finally { this.saving = false; }
        },
        async deleteCategory(id) {
            if (!confirm('Bu kategoriyi silmek istediğinize emin misiniz?')) return;
            try {
                await posAjax(`/categories/${id}`, { method: 'DELETE' });
                showToast('Kategori silindi', 'success'); window.location.reload();
            } catch (e) { showToast(e.message || 'Kategori silinemedi', 'error'); }
        }
    };
}
</script>
@endpush

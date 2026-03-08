@extends('pos.layouts.app')

@section('title', 'Kategoriler')

@section('content')
<div class="p-3 sm:p-6 overflow-y-auto h-full" x-data="categoryManager()" x-cloak>
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Kategoriler</h1>
            <p class="text-sm text-gray-500 mt-1">Toplam {{ $categories->count() }} kategori</p>
        </div>
        <button @click="openCreate()"
                class="bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 text-white font-medium rounded-lg text-sm px-5 py-2.5 transition-colors flex items-center gap-2 justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Kategori
        </button>
    </div>

    {{-- Category Tree --}}
    <div class="space-y-3">
        @forelse($tree as $group)
            {{-- Üst Grup (Seviye 1) --}}
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="flex items-center justify-between p-4 bg-gray-50/80">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-brand-500/10 flex items-center justify-center">
                            <i class="fas fa-folder text-brand-500"></i>
                        </div>
                        <div>
                            <h3 class="text-gray-900 font-semibold text-base">{{ $group->name }}</h3>
                            <span class="text-xs text-gray-500">{{ $group->products_count }} ürün · {{ $group->children->count() }} alt kategori</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        @if(!$group->is_active)<span class="text-xs bg-red-500/10 text-red-500 px-2 py-0.5 rounded-full mr-2">Pasif</span>@endif
                        <button @click="openEdit({{ json_encode(['id'=>$group->id,'name'=>$group->name,'parent_id'=>$group->parent_id,'sort_order'=>$group->sort_order,'is_active'=>$group->is_active]) }})"
                                class="p-1.5 text-gray-500 hover:text-yellow-400 hover:bg-yellow-500/10 rounded-lg" title="Düzenle"><i class="fas fa-pen text-xs"></i></button>
                        <button @click="deleteCategory({{ $group->id }})" class="p-1.5 text-gray-500 hover:text-red-500 hover:bg-red-50 rounded-lg" title="Sil"><i class="fas fa-trash text-xs"></i></button>
                    </div>
                </div>
                @if($group->children->count() > 0)
                    <div class="divide-y divide-gray-50">
                        @foreach($group->children->sortBy('sort_order') as $sub)
                            {{-- Alt Kategori (Seviye 2 - ör: Marka) --}}
                            <div class="pl-8">
                                <div class="flex items-center justify-between px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <i class="fas fa-caret-right text-gray-400"></i>
                                        <div class="w-8 h-8 rounded-lg bg-purple-500/10 flex items-center justify-center">
                                            <i class="fas fa-tag text-purple-500 text-xs"></i>
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium text-gray-800">{{ $sub->name }}</span>
                                            <span class="text-xs text-gray-400 ml-2">{{ $sub->products_count }} ürün</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        @if(!$sub->is_active)<span class="text-xs bg-red-500/10 text-red-500 px-2 py-0.5 rounded-full mr-2">Pasif</span>@endif
                                        <button @click="openEdit({{ json_encode(['id'=>$sub->id,'name'=>$sub->name,'parent_id'=>$sub->parent_id,'sort_order'=>$sub->sort_order,'is_active'=>$sub->is_active]) }})"
                                                class="p-1.5 text-gray-500 hover:text-yellow-400 hover:bg-yellow-500/10 rounded-lg"><i class="fas fa-pen text-xs"></i></button>
                                        <button @click="deleteCategory({{ $sub->id }})" class="p-1.5 text-gray-500 hover:text-red-500 hover:bg-red-50 rounded-lg"><i class="fas fa-trash text-xs"></i></button>
                                    </div>
                                </div>
                                @if($sub->children && $sub->children->count() > 0)
                                    <div class="pl-8 pb-2">
                                        @foreach($sub->children->sortBy('sort_order') as $sub2)
                                            {{-- Seviye 3 --}}
                                            <div class="flex items-center justify-between px-4 py-2">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-minus text-gray-300 text-[8px]"></i>
                                                    <span class="text-sm text-gray-600">{{ $sub2->name }}</span>
                                                    <span class="text-xs text-gray-400">{{ $sub2->products_count }} ürün</span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <button @click="openEdit({{ json_encode(['id'=>$sub2->id,'name'=>$sub2->name,'parent_id'=>$sub2->parent_id,'sort_order'=>$sub2->sort_order,'is_active'=>$sub2->is_active]) }})"
                                                            class="p-1 text-gray-400 hover:text-yellow-400 rounded"><i class="fas fa-pen text-[10px]"></i></button>
                                                    <button @click="deleteCategory({{ $sub2->id }})" class="p-1 text-gray-400 hover:text-red-500 rounded"><i class="fas fa-trash text-[10px]"></i></button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center py-12">
                <i class="fas fa-tags text-3xl text-gray-300 mb-3"></i>
                <p class="text-gray-500 text-sm">Henüz kategori eklenmemiş</p>
                <button @click="openCreate()" class="text-brand-500 hover:text-brand-600 text-sm font-medium mt-2">+ İlk kategoriyi ekle</button>
            </div>
        @endforelse
    </div>

    {{-- Category Form Modal --}}
    <div x-show="showModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="relative bg-white rounded-xl border border-gray-100 shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto" x-transition>
            <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900" x-text="editingId ? 'Kategori Düzenle' : 'Yeni Kategori'"></h2>
                <button @click="showModal = false" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form @submit.prevent="submitForm()" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Kategori Adı <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.name" required class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400" placeholder="Kategori adı">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Üst Kategori</label>
                    <select x-model="form.parent_id" class="w-full bg-white border border-gray-200 text-gray-700 text-sm rounded-lg px-4 py-2.5 focus:ring-brand-500/20 focus:border-brand-500">
                        <option value="">Ana Kategori (Grup)</option>
                        @foreach($categories->whereNull('parent_id') as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @foreach($categories->where('parent_id', $cat->id) as $sub)
                                <option value="{{ $sub->id }}">&nbsp;&nbsp;└ {{ $sub->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Sıralama</label>
                        <input type="number" x-model="form.sort_order" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-brand-500/20 focus:border-brand-500" placeholder="0">
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="form.is_active" class="w-4 h-4 rounded border-gray-200 bg-white text-blue-500 focus:ring-brand-500/20">
                            <span class="text-sm text-gray-700">Aktif</span>
                        </label>
                    </div>
                </div>
                <div class="flex gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="showModal = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 rounded-lg transition-colors">İptal</button>
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

@extends('pos.layouts.app')

@section('title', 'Cariler')

@section('content')
<div class="p-6 overflow-y-auto h-full" x-data="firmManager()" x-cloak>
    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Toplam Cari</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total_firms'] }}</p>
                </div>
                <div class="w-11 h-11 rounded-lg bg-brand-500/10 flex items-center justify-center">
                    <svg class="w-6 h-6 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Toplam Borç</p>
                    <p class="text-2xl font-bold text-red-500 mt-1">{{ formatCurrency(abs($stats['total_debt'])) }}</p>
                </div>
                <div class="w-11 h-11 rounded-lg bg-red-500/10 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Toplam Alacak</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ formatCurrency($stats['total_credit']) }}</p>
                </div>
                <div class="w-11 h-11 rounded-lg bg-green-500/10 flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Cariler (Tedarikçiler)</h1>
        <div class="flex items-center gap-3">
            <div class="relative">
                <input type="text" x-model="searchQuery" @input.debounce.400ms="applySearch()"
                       placeholder="Cari ara..."
                       class="bg-white border border-gray-700 text-gray-700 text-sm rounded-lg pl-9 pr-4 py-2.5 w-64 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <button @click="openCreate()"
                    class="bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 text-gray-900 font-medium rounded-lg text-sm px-5 py-2.5 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Yeni Cari
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3.5">Firma Adı</th>
                        <th class="px-4 py-3.5">Telefon</th>
                        <th class="px-4 py-3.5">E-posta</th>
                        <th class="px-4 py-3.5">Vergi No</th>
                        <th class="px-4 py-3.5 text-right">Bakiye</th>
                        <th class="px-4 py-3.5 text-center">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($firms as $firm)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-purple-500/10 flex items-center justify-center text-sm font-semibold text-purple-600">
                                        {{ mb_substr($firm->name, 0, 1) }}
                                    </div>
                                    <p class="font-medium text-gray-900">{{ $firm->name }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $firm->phone ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $firm->email ?? '-' }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $firm->tax_number ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                @php $bal = $firm->balance ?? 0; @endphp
                                <span class="font-mono font-medium {{ $bal < 0 ? 'text-red-500' : ($bal > 0 ? 'text-emerald-600' : 'text-gray-500') }}">
                                    {{ formatCurrency($bal) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="openDetail({{ $firm->id }})"
                                            class="p-2 text-gray-500 hover:text-brand-600 hover:bg-brand-500/10 rounded-lg transition-colors" title="Detay / Hareketler">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    </button>
                                    <button @click="openEdit({{ json_encode(['id'=>$firm->id,'name'=>$firm->name,'tax_number'=>$firm->tax_number,'tax_office'=>$firm->tax_office,'phone'=>$firm->phone,'email'=>$firm->email,'address'=>$firm->address,'city'=>$firm->city,'notes'=>$firm->notes]) }})"
                                            class="p-2 text-gray-500 hover:text-yellow-400 hover:bg-yellow-500/10 rounded-lg transition-colors" title="Düzenle">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button @click="openPayment({{ $firm->id }}, '{{ addslashes($firm->name) }}', {{ $bal }})"
                                            class="p-2 text-gray-500 hover:text-emerald-600 hover:bg-green-500/10 rounded-lg transition-colors" title="Ödeme">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <p class="text-gray-500 text-sm">Henüz cari kaydı eklenmemiş</p>
                                <button @click="openCreate()" class="text-brand-500 hover:text-brand-600 text-sm font-medium mt-2">+ İlk cariyi ekle</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($firms->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $firms->links() }}</div>
        @endif
    </div>

    {{-- Form Modal --}}
    <div x-show="showFormModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showFormModal = false"></div>
        <div class="relative bg-white rounded-xl border border-gray-100 shadow-2xl w-full max-w-lg" x-transition>
            <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900" x-text="editingId ? 'Cari Düzenle' : 'Yeni Cari'"></h2>
                <button @click="showFormModal = false" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form @submit.prevent="submitForm()" class="p-6 space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Firma Adı <span class="text-red-500">*</span></label><input type="text" x-model="form.name" required class="w-full bg-white border border-gray-700 text-gray-900 text-sm rounded-lg px-4 py-2.5"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Vergi No</label><input type="text" x-model="form.tax_number" class="w-full bg-white border border-gray-700 text-gray-900 text-sm rounded-lg px-4 py-2.5"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Vergi Dairesi</label><input type="text" x-model="form.tax_office" class="w-full bg-white border border-gray-700 text-gray-900 text-sm rounded-lg px-4 py-2.5"></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Telefon</label><input type="tel" x-model="form.phone" class="w-full bg-white border border-gray-700 text-gray-900 text-sm rounded-lg px-4 py-2.5"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">E-posta</label><input type="email" x-model="form.email" class="w-full bg-white border border-gray-700 text-gray-900 text-sm rounded-lg px-4 py-2.5"></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Adres</label><textarea x-model="form.address" rows="2" class="w-full bg-white border border-gray-700 text-gray-900 text-sm rounded-lg px-4 py-2.5 resize-none"></textarea></div>
                <div class="flex gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="showFormModal = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-700 rounded-lg">İptal</button>
                    <button type="submit" :disabled="saving" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-900 bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 rounded-lg disabled:opacity-50"><span x-text="editingId ? 'Güncelle' : 'Kaydet'"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Payment Modal --}}
    <div x-show="showPaymentModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showPaymentModal = false"></div>
        <div class="relative bg-white rounded-xl border border-gray-100 shadow-2xl w-full max-w-md" x-transition>
            <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Ödeme Yap</h2>
                <button @click="showPaymentModal = false" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form @submit.prevent="submitPayment()" class="p-6 space-y-4">
                <div class="bg-white rounded-lg border border-gray-100 p-4">
                    <p class="text-sm text-gray-500">Cari: <span class="text-gray-900 font-medium" x-text="payFirmName"></span></p>
                    <p class="text-sm text-gray-500 mt-1">Bakiye: <span class="font-mono font-medium" :class="payFirmBalance < 0 ? 'text-red-500' : 'text-emerald-600'" x-text="formatCurrency(payFirmBalance)"></span></p>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Tutar <span class="text-red-500">*</span></label><input type="number" x-model="payForm.amount" step="0.01" min="0.01" required class="w-full bg-white border border-gray-700 text-gray-900 text-sm rounded-lg px-4 py-2.5 font-mono text-lg"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Açıklama</label><input type="text" x-model="payForm.description" class="w-full bg-white border border-gray-700 text-gray-900 text-sm rounded-lg px-4 py-2.5" placeholder="Opsiyonel"></div>
                <div class="flex gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="showPaymentModal = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-700 rounded-lg">İptal</button>
                    <button type="submit" :disabled="paying" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-900 bg-gradient-to-r from-emerald-500 to-teal-500 hover:shadow-lg hover:shadow-emerald-200 rounded-lg disabled:opacity-50">Öde</button>
                </div>
            </form>
        </div>
    </div>
    {{-- Detail / Transactions Modal --}}
    <div x-show="showDetailModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showDetailModal = false"></div>
        <div class="relative bg-white rounded-xl border border-gray-100 shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col" x-transition>
            <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between shrink-0">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-handshake text-brand-500"></i>
                    <span x-text="detailData?.firm?.name || 'Cari Detayı'"></span>
                </h2>
                <button @click="showDetailModal = false" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-6">
                {{-- Loading --}}
                <div x-show="detailLoading" class="flex items-center justify-center py-16">
                    <i class="fas fa-spinner fa-spin text-2xl text-brand-500"></i>
                </div>
                {{-- Content --}}
                <template x-if="detailData && !detailLoading">
                    <div>
                        {{-- Özet --}}
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="bg-gray-50 rounded-xl p-4 text-center">
                                <p class="text-xs text-gray-500 mb-1">Bakiye</p>
                                <p class="text-xl font-bold" :class="(detailData.firm.balance||0) < 0 ? 'text-red-500' : 'text-emerald-600'" x-text="formatCur(detailData.firm.balance)"></p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 text-center">
                                <p class="text-xs text-gray-500 mb-1">Telefon</p>
                                <p class="text-sm font-medium text-gray-800" x-text="detailData.firm.phone || '-'"></p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 text-center">
                                <p class="text-xs text-gray-500 mb-1">Vergi No</p>
                                <p class="text-sm font-mono text-gray-800" x-text="detailData.firm.tax_number || '-'"></p>
                            </div>
                        </div>

                        {{-- Hareketler Başlığı --}}
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold text-gray-900 text-sm"><i class="fas fa-list-ul mr-2 text-brand-500"></i>Hesap Hareketleri</h3>
                            <button @click="openPayment(detailData.firm.id, detailData.firm.name, detailData.firm.balance); showDetailModal = false"
                                    class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-medium rounded-lg transition-colors">
                                <i class="fas fa-plus mr-1"></i>Ödeme Ekle
                            </button>
                        </div>

                        {{-- Hareketler Listesi --}}
                        <div x-show="detailData.transactions.length === 0" class="text-center py-8 text-gray-400 text-sm">
                            Henüz hareket kaydı yok
                        </div>
                        <div class="space-y-2" x-show="detailData.transactions.length > 0">
                            <template x-for="t in detailData.transactions" :key="t.id">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-100">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold"
                                             :class="t.amount > 0 ? 'bg-emerald-500' : 'bg-red-500'">
                                            <i :class="t.amount > 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-800" x-text="t.description || t.type"></p>
                                            <p class="text-xs text-gray-400" x-text="t.reference ? ('Ref: ' + t.reference) : ''"></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold" :class="t.amount > 0 ? 'text-emerald-600' : 'text-red-500'" x-text="(t.amount > 0 ? '+' : '') + formatCur(t.amount)"></p>
                                        <p class="text-xs text-gray-400" x-text="t.transaction_date ? new Date(t.transaction_date).toLocaleDateString('tr-TR') : ''"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function firmManager() {
    return {
        showFormModal: false, showPaymentModal: false, showDetailModal: false,
        editingId: null, saving: false, paying: false, detailLoading: false,
        detailData: null,
        searchQuery: new URLSearchParams(window.location.search).get('search') || '',
        form: { name: '', tax_number: '', tax_office: '', phone: '', email: '', address: '', city: '', notes: '' },
        payForm: { amount: '', description: '' }, payFirmId: null, payFirmName: '', payFirmBalance: 0,
        openCreate() { this.editingId = null; this.form = { name: '', tax_number: '', tax_office: '', phone: '', email: '', address: '', city: '', notes: '' }; this.showFormModal = true; },
        openEdit(f) { this.editingId = f.id; this.form = { name: f.name||'', tax_number: f.tax_number||'', tax_office: f.tax_office||'', phone: f.phone||'', email: f.email||'', address: f.address||'', city: f.city||'', notes: f.notes||'' }; this.showFormModal = true; },
        openPayment(id, name, balance) { this.payFirmId = id; this.payFirmName = name; this.payFirmBalance = balance; this.payForm = { amount: '', description: '' }; this.showPaymentModal = true; },
        async openDetail(id) {
            this.detailData = null;
            this.detailLoading = true;
            this.showDetailModal = true;
            try {
                const data = await posAjax(`/firms/${id}`, {}, 'GET');
                this.detailData = data;
            } catch(e) { showToast('Detay yüklenemedi', 'error'); this.showDetailModal = false; }
            finally { this.detailLoading = false; }
        },
        applySearch() {
            const params = new URLSearchParams();
            if (this.searchQuery) params.set('search', this.searchQuery);
            window.location.href = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        },
        async submitForm() {
            this.saving = true;
            const url = this.editingId ? `/firms/${this.editingId}` : '/firms';
            const method = this.editingId ? 'PUT' : 'POST';
            try { await posAjax(url, this.form, method); showToast(this.editingId ? 'Cari güncellendi' : 'Cari oluşturuldu', 'success'); this.showFormModal = false; window.location.reload(); }
            catch (e) { showToast(e.message || 'Hata', 'error'); } finally { this.saving = false; }
        },
        async submitPayment() {
            this.paying = true;
            try { await posAjax(`/firms/${this.payFirmId}/payment`, this.payForm, 'POST'); showToast('Ödeme kaydedildi', 'success'); this.showPaymentModal = false; window.location.reload(); }
            catch (e) { showToast(e.message || 'Hata', 'error'); } finally { this.paying = false; }
        },
        formatCur(v) { return new Intl.NumberFormat('tr-TR', {style:'currency', currency:'TRY'}).format(v||0); },
    };
}
</script>
@endpush

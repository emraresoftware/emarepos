@extends('pos.layouts.app')

@section('title', 'Cariler')

@section('content')
<div class="p-6 overflow-y-auto h-full" x-data="firmManager()" x-cloak x-init="init()">
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
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative w-full sm:w-auto">
                <input type="text" x-model="searchQuery" @input.debounce.400ms="applySearch()"
                       placeholder="Cari ara..."
                       class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg pl-9 pr-4 py-2.5 w-full sm:w-64 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <select x-model="groupFilter" @change="applySearch()"
                    class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg px-3 py-2.5 focus:ring-brand-500/20 focus:border-brand-500">
                <option value="">Tüm Gruplar</option>
                @foreach($groups as $g)
                    <option value="{{ $g->id }}">{{ $g->name }} ({{ $g->firms_count }})</option>
                @endforeach
            </select>
            <button @click="showGroupPanel = !showGroupPanel"
                    class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 font-medium rounded-lg text-sm px-4 py-2.5 transition-colors flex items-center gap-2">
                <i class="fas fa-layer-group"></i> Gruplar
            </button>
            <button @click="openCreate()"
                    class="bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 text-white font-medium rounded-lg text-sm px-5 py-2.5 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Yeni Cari
            </button>
        </div>
    </div>

    {{-- Grup Yönetim Paneli --}}
    <div x-show="showGroupPanel" x-transition class="bg-white rounded-xl border border-gray-100 p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900"><i class="fas fa-layer-group text-brand-500 mr-2"></i>Cari Grupları</h3>
            <button @click="showGroupPanel = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($groups as $g)
                <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2 border border-gray-100">
                    <span class="text-sm text-gray-700">{{ $g->name }}</span>
                    <span class="text-xs text-gray-400">({{ $g->firms_count }})</span>
                    <button @click="editGroup({{ $g->id }}, '{{ addslashes($g->name) }}')" class="text-gray-400 hover:text-yellow-500"><i class="fas fa-pen text-[10px]"></i></button>
                    <button @click="deleteGroup({{ $g->id }})" class="text-gray-400 hover:text-red-500"><i class="fas fa-trash text-[10px]"></i></button>
                </div>
            @endforeach
            <div x-show="!showGroupForm" class="flex items-center">
                <button @click="showGroupForm = true; newGroupName = ''" class="text-sm text-brand-500 hover:text-brand-600"><i class="fas fa-plus mr-1"></i>Yeni Grup</button>
            </div>
        </div>
        <div x-show="showGroupForm" class="flex items-center gap-2">
            <input type="text" x-model="newGroupName" placeholder="Grup adı" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg px-3 py-2 flex-1 focus:border-brand-500">
            <button @click="saveGroup()" :disabled="!newGroupName.trim()" class="px-4 py-2 bg-brand-500 text-white text-sm rounded-lg hover:bg-brand-600 disabled:opacity-50">
                <span x-text="editingGroupId ? 'Güncelle' : 'Ekle'"></span>
            </button>
            <button @click="showGroupForm = false; editingGroupId = null" class="px-3 py-2 text-gray-500 hover:text-gray-700 text-sm">Vazgeç</button>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3.5">Firma Adı</th>
                        <th class="px-4 py-3.5">Grup</th>
                        <th class="px-4 py-3.5">Telefon</th>
                        <th class="px-4 py-3.5">E-posta</th>
                        <th class="px-4 py-3.5">Vergi No</th>
                        <th class="px-4 py-3.5 text-right">Bakiye</th>
                        <th class="px-4 py-3.5 text-center">İşlemler</th>
                    </tr>                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($firms as $firm)
                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer" @click.stop="openDetail({{ $firm->id }})">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-purple-500/10 flex items-center justify-center text-sm font-semibold text-purple-600">
                                        {{ mb_substr($firm->name, 0, 1) }}
                                    </div>
                                    <p class="font-medium text-gray-900">{{ $firm->name }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if($firm->group)
                                    <span class="text-xs bg-purple-500/10 text-purple-600 px-2 py-0.5 rounded-full">{{ $firm->group->name }}</span>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                @if($firm->phones->count())
                                    <div class="flex flex-col gap-0.5">
                                        @foreach($firm->phones->take(2) as $ph)
                                            <div class="flex items-center gap-1.5">
                                                @if($ph->is_primary)<span class="text-yellow-500 text-[9px]">★</span>@endif
                                                <span class="text-[10px] font-semibold rounded px-1 py-0.5 {{ $ph->type === 'mobile' ? 'bg-blue-100 text-blue-700' : ($ph->type === 'landline' ? 'bg-gray-100 text-gray-600' : 'bg-purple-100 text-purple-700') }}">{{ $ph->type === 'mobile' ? 'H' : ($ph->type === 'landline' ? 'S' : 'D') }}</span>
                                                <span class="text-xs">{{ $ph->phone }}</span>
                                            </div>
                                        @endforeach
                                        @if($firm->phones->count() > 2)<span class="text-[10px] text-gray-400">+{{ $firm->phones->count() - 2 }} daha</span>@endif
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
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
                                        <button @click.stop="openDetail({{ $firm->id }})"
                                            class="p-2 text-gray-500 hover:text-brand-600 hover:bg-brand-500/10 rounded-lg transition-colors" title="Detay / Hareketler">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    </button>
                                        <button @click.stop="openEdit({{ json_encode(['id'=>$firm->id,'name'=>$firm->name,'firm_group_id'=>$firm->firm_group_id,'tax_number'=>$firm->tax_number,'tax_office'=>$firm->tax_office,'email'=>$firm->email,'address'=>$firm->address,'city'=>$firm->city,'notes'=>$firm->notes,'phones'=>$firm->phones->map(fn($p)=>['phone'=>$p->phone,'type'=>$p->type,'is_primary'=>(bool)$p->is_primary])->values()->toArray()]) }})"
                                            class="p-2 text-gray-500 hover:text-yellow-400 hover:bg-yellow-500/10 rounded-lg transition-colors" title="Düzenle">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                        <button @click.stop="openPayment({{ $firm->id }}, '{{ addslashes($firm->name) }}', {{ $bal }})"
                                            class="p-2 text-gray-500 hover:text-emerald-600 hover:bg-green-500/10 rounded-lg transition-colors" title="Ödeme Yap">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    </button>
                                        <button @click.stop="openDebt({{ $firm->id }}, '{{ addslashes($firm->name) }}', {{ $bal }})"
                                            class="p-2 text-gray-500 hover:text-red-500 hover:bg-red-500/10 rounded-lg transition-colors" title="Borç Ekle">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
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
        <div class="relative bg-white rounded-xl border border-gray-100 shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" x-transition>
            <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900" x-text="editingId ? 'Cari Düzenle' : 'Yeni Cari'"></h2>
                <button @click="showFormModal = false" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form @submit.prevent="submitForm()" class="p-6 space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Firma Adı <span class="text-red-500">*</span></label><input type="text" x-model="form.name" required class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Cari Grubu</label>
                    <select x-model="form.firm_group_id" class="w-full bg-white border border-gray-200 text-gray-700 text-sm rounded-lg px-4 py-2.5">
                        <option value="">Grup Seç (Opsiyonel)</option>
                        @foreach($groups as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Vergi No</label><input type="text" x-model="form.tax_number" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Vergi Dairesi</label><input type="text" x-model="form.tax_office" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5"></div>
                </div>
                {{-- Çoklu Telefon --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-sm font-medium text-gray-700">Telefon Numaraları</label>
                        <button type="button" @click="addPhone()" class="text-xs text-brand-600 hover:text-brand-700 font-semibold flex items-center gap-1">
                            <i class="fas fa-plus"></i> Telefon Ekle
                        </button>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(p, idx) in form.phones" :key="idx">
                            <div class="flex items-center gap-2">
                                <select x-model="p.type" class="bg-gray-50 border border-gray-200 text-gray-700 text-xs rounded-lg px-2 py-2.5 w-28 flex-shrink-0">
                                    <option value="mobile">Hareketli</option>
                                    <option value="landline">Sabit</option>
                                    <option value="other">Diğer</option>
                                </select>
                                <input type="tel" x-model="p.phone" placeholder="Telefon numarası"
                                       class="flex-1 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg px-3 py-2.5 placeholder-gray-400">
                                <button type="button" @click="setPrimaryPhone(idx)"
                                        :title="p.is_primary ? 'Ana numara' : 'Ana numara yap'"
                                        :class="p.is_primary ? 'text-yellow-500 bg-yellow-50' : 'text-gray-300 hover:text-yellow-400'"
                                        class="p-2 rounded-lg border border-gray-200 flex-shrink-0">
                                    <i class="fas fa-star text-xs"></i>
                                </button>
                                <button type="button" @click="removePhone(idx)" x-show="form.phones.length > 1"
                                        class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg border border-gray-200 flex-shrink-0">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">E-posta</label><input type="email" x-model="form.email" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5"></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Adres</label><textarea x-model="form.address" rows="2" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 resize-none"></textarea></div>
                <div class="flex gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="showFormModal = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 rounded-lg">İptal</button>
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
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Tutar <span class="text-red-500">*</span></label><input type="number" x-model="payForm.amount" step="0.01" min="0.01" required class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 font-mono text-lg"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Açıklama</label><input type="text" x-model="payForm.description" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5" placeholder="Opsiyonel"></div>
                <div class="flex gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="showPaymentModal = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 rounded-lg">İptal</button>
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
                        {{-- Özet Kartlar --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                            <div class="bg-gray-50 rounded-xl p-3 text-center">
                                <p class="text-xs text-gray-500 mb-1">Bakiye</p>
                                <p class="text-lg font-bold" :class="(detailData.firm.balance||0) < 0 ? 'text-red-500' : 'text-emerald-600'" x-text="formatCur(detailData.firm.balance)"></p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-3 text-center">
                                <p class="text-xs text-gray-500 mb-1">Toplam Alış</p>
                                <p class="text-lg font-bold text-gray-800" x-text="formatCur(detailData.total_purchase)"></p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-3 text-center">
                                <p class="text-xs text-gray-500 mb-1">Toplam Ödeme</p>
                                <p class="text-lg font-bold text-emerald-600" x-text="formatCur(detailData.total_payment)"></p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-3 text-center">
                                <p class="text-xs text-gray-500 mb-1">Fatura Sayısı</p>
                                <p class="text-lg font-bold text-gray-800" x-text="(detailData.purchase_invoices||[]).length"></p>
                            </div>
                        </div>

                        {{-- Telefon & İletişim Bilgileri --}}
                        <div class="bg-gray-50 rounded-xl border border-gray-100 p-3 mb-4">
                            <div class="flex flex-wrap gap-3 items-center">
                                <template x-for="(ph, i) in (detailData.phones||[])" :key="i">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] font-bold rounded px-1.5 py-0.5"
                                              :class="ph.type === 'mobile' ? 'bg-blue-100 text-blue-700' : (ph.type === 'landline' ? 'bg-gray-200 text-gray-600' : 'bg-purple-100 text-purple-700')"
                                              x-text="ph.type === 'mobile' ? 'H' : (ph.type === 'landline' ? 'S' : 'D')"></span>
                                        <span class="text-sm text-gray-800" x-text="ph.phone"></span>
                                        <span x-show="ph.is_primary" class="text-yellow-500 text-xs">★</span>
                                    </div>
                                </template>
                                <template x-if="!(detailData.phones||[]).length">
                                    <span class="text-xs text-gray-400">Telefon yok</span>
                                </template>
                                <span class="text-gray-300">|</span>
                                <span class="text-xs text-gray-500" x-text="detailData.firm.email || ''"></span>
                                <span x-show="detailData.firm.tax_number" class="text-xs text-gray-500">VN: <span x-text="detailData.firm.tax_number" class="font-mono"></span></span>
                            </div>
                        </div>

                        {{-- Sekmeler --}}
                        <div class="flex flex-wrap gap-2 mb-4">
                            <button @click="detailTab = 'timeline'" class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors"
                                    :class="detailTab === 'timeline' ? 'bg-brand-500 text-white border-brand-500' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'">
                                <i class="fas fa-stream mr-1"></i>Tüm Hareketler
                            </button>
                            <button @click="detailTab = 'invoices'" class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors"
                                    :class="detailTab === 'invoices' ? 'bg-brand-500 text-white border-brand-500' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'">
                                <i class="fas fa-file-invoice mr-1"></i>Alış Faturaları
                                <span class="ml-1 bg-gray-200 text-gray-600 rounded-full px-1.5 py-0.5 text-[10px]" x-text="(detailData.purchase_invoices||[]).length"></span>
                            </button>
                            <button @click="detailTab = 'payments'" class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors"
                                    :class="detailTab === 'payments' ? 'bg-brand-500 text-white border-brand-500' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'">
                                <i class="fas fa-coins mr-1"></i>Ödemeler
                                <span class="ml-1 bg-gray-200 text-gray-600 rounded-full px-1.5 py-0.5 text-[10px]" x-text="(detailData.payments||[]).length"></span>
                            </button>
                            <button @click="detailTab = 'notes'" class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors"
                                    :class="detailTab === 'notes' ? 'bg-brand-500 text-white border-brand-500' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'">
                                Notlar
                            </button>
                        </div>

                        {{-- Quick Actions --}}
                        <div class="flex flex-wrap gap-2 mb-4">
                            <button @click="openPayment(detailData.firm.id, detailData.firm.name, detailData.firm.balance); showDetailModal=false"
                                    class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-medium rounded-lg flex items-center gap-1.5">
                                <i class="fas fa-money-bill-wave"></i> Ödeme Yap
                            </button>
                            <button @click="openDebt(detailData.firm.id, detailData.firm.name, detailData.firm.balance); showDetailModal=false"
                                    class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded-lg flex items-center gap-1.5">
                                <i class="fas fa-plus-circle"></i> Borç Ekle
                            </button>
                        </div>

                        <div class="bg-white rounded-2xl border border-gray-200 p-4 mb-4 shadow-sm">
                            <div class="flex flex-col gap-3">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Rapor Gönder</p>
                                        <p class="text-xs text-gray-500" x-text="selectedInvoiceIds.length ? selectedInvoiceIds.length + ' fatura seçili' : 'Tek fatura, birkaç seçili fatura veya tüm geçmiş gönderilebilir.'"></p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" @click="toggleAllFirmInvoices(true)" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">Tümünü Seç</button>
                                        <button type="button" @click="toggleAllFirmInvoices(false)" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">Seçimi Temizle</button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                                    <div class="rounded-xl border border-orange-100 bg-orange-50/50 p-3">
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-orange-700 mb-2">Seçili Faturalar</p>
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" @click="sendFirmReportWhatsApp('selected')" class="px-3 py-2 text-xs font-bold rounded-xl bg-emerald-500 text-white hover:bg-emerald-600 transition-colors"><i class="fab fa-whatsapp mr-1.5"></i>WhatsApp</button>
                                            <button type="button" @click="sendFirmReportEmail('selected')" class="px-3 py-2 text-xs font-bold rounded-xl bg-sky-500 text-white hover:bg-sky-600 transition-colors"><i class="fas fa-envelope mr-1.5"></i>E-posta</button>
                                            <button type="button" @click="printFirmReport('selected')" class="px-3 py-2 text-xs font-bold rounded-xl bg-gray-900 text-white hover:bg-black transition-colors"><i class="fas fa-print mr-1.5"></i>Yazdır</button>
                                        </div>
                                    </div>
                                    <div class="rounded-xl border border-purple-100 bg-purple-50/50 p-3">
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-purple-700 mb-2">Tüm Geçmiş</p>
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" @click="sendFirmReportWhatsApp('all')" class="px-3 py-2 text-xs font-bold rounded-xl bg-emerald-500 text-white hover:bg-emerald-600 transition-colors"><i class="fab fa-whatsapp mr-1.5"></i>WhatsApp</button>
                                            <button type="button" @click="sendFirmReportEmail('all')" class="px-3 py-2 text-xs font-bold rounded-xl bg-sky-500 text-white hover:bg-sky-600 transition-colors"><i class="fas fa-envelope mr-1.5"></i>E-posta</button>
                                            <button type="button" @click="printFirmReport('all')" class="px-3 py-2 text-xs font-bold rounded-xl bg-gray-900 text-white hover:bg-black transition-colors"><i class="fas fa-print mr-1.5"></i>Yazdır</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tüm Hareketler (Timeline) --}}
                        <div x-show="detailTab === 'timeline'">
                            <div x-show="detailTimeline.length === 0" class="text-center py-8 text-gray-400 text-sm">Hareket kaydı yok</div>
                            <div class="space-y-2">
                                <template x-for="item in detailTimeline" :key="item._key">
                                    <div>
                                        {{-- Fatura Satırı --}}
                                        <template x-if="item._type === 'invoice'">
                                            <div>
                                            <div @click="toggleInvoiceDetails(item)"
                                                 class="w-full flex items-center justify-between p-3 bg-white border border-gray-100 rounded-xl hover:border-orange-200 hover:shadow-sm hover:shadow-orange-500/10 transition-all text-left group cursor-pointer">
                                                <div class="flex items-center gap-3">
                                                    <label class="flex items-center" @click.stop>
                                                        <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-orange-500 focus:ring-orange-500" :checked="selectedInvoiceIds.includes(item.id)" @change="toggleFirmInvoiceSelection(item.id)">
                                                    </label>
                                                    <div class="w-9 h-9 rounded-full bg-orange-50 flex items-center justify-center group-hover:scale-110 group-hover:bg-orange-100 transition-transform shadow-sm">
                                                        <i class="fas fa-file-invoice text-orange-500 text-sm"></i>
                                                    </div>
                                                    <div>
                                                        <p class="text-[13px] font-bold text-gray-800" x-text="item.invoice_no || ('Fatura #' + item.id)"></p>
                                                        <p class="text-[11px] text-gray-400" x-text="item.invoice_date ? new Date(item.invoice_date).toLocaleDateString('tr-TR', {day:'numeric',month:'short',year:'numeric'}) : ''"></p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <div class="text-right">
                                                        <p class="text-[15px] font-black text-gray-900" x-text="formatCur(item.grand_total)"></p>
                                                        <p class="text-[10px] font-bold uppercase tracking-wider"
                                                           :class="item.payment_status === 'paid' ? 'text-green-500' : 'text-orange-500'"
                                                           x-text="item.payment_status === 'paid' ? 'Ödendi' : 'Bekliyor'"></p>
                                                    </div>
                                                    <i class="fas text-gray-300 text-xs group-hover:text-orange-400 group-hover:translate-x-0.5 transition-all"
                                                       :class="expandedInvoiceKey === item._key ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                                </div>
                                            </div>
                                            <div x-show="expandedInvoiceKey === item._key" x-transition class="mt-2 rounded-2xl border border-orange-100 bg-orange-50/50 p-4" :id="'firm-invoice-print-' + item._key">
                                                <div class="flex items-start justify-between gap-3 mb-4">
                                                    <div>
                                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1" x-text="detailData?.firm?.name"></p>
                                                        <div class="text-2xl font-black text-gray-900 tracking-tight" x-text="formatCur(item.grand_total)"></div>
                                                        <div class="mt-2 flex items-center gap-2 flex-wrap">
                                                            <span class="text-[11px] px-2 py-0.5 rounded-lg font-bold"
                                                                  :class="item.payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'"
                                                                  x-text="item.payment_status === 'paid' ? 'Ödendi' : 'Ödenmedi'"></span>
                                                            <span class="text-xs text-gray-400" x-text="item.invoice_date ? new Date(item.invoice_date).toLocaleString('tr-TR',{day:'numeric',month:'long',year:'numeric'}) : (item.created_at ? new Date(item.created_at).toLocaleString('tr-TR',{day:'numeric',month:'long',year:'numeric'}) : '')"></span>
                                                        </div>
                                                    </div>
                                                    <div class="flex flex-wrap justify-end gap-2 shrink-0">
                                                        <button type="button" @click="sendFirmReportWhatsApp('single', item)" class="px-3 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors"><i class="fab fa-whatsapp"></i> WhatsApp</button>
                                                        <button type="button" @click="sendFirmReportEmail('single', item)" class="px-3 py-2 bg-sky-500 hover:bg-sky-600 text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors"><i class="fas fa-envelope"></i> E-posta</button>
                                                        <button type="button" @click="printFirmReport('single', item)" class="px-3 py-2 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors"><i class="fas fa-print"></i> Yazdır</button>
                                                    </div>
                                                </div>

                                                <div x-show="item._loading" class="py-10 text-center text-gray-400 text-sm">
                                                    <i class="fas fa-spinner fa-spin text-lg mb-2"></i>
                                                    <p>Fatura detayı yükleniyor...</p>
                                                </div>

                                                <div x-show="!item._loading" class="space-y-3">
                                                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                                                        <div class="divide-y divide-gray-50">
                                                            <template x-for="line in (item.items||[])" :key="line.id">
                                                                <div class="p-4 flex items-center justify-between hover:bg-gray-50/50 transition-colors">
                                                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                                                        <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600 flex-shrink-0" x-text="line.quantity||1"></div>
                                                                        <div class="min-w-0">
                                                                            <div class="text-[13px] font-bold text-gray-800 truncate" x-text="line.product_name || line.description || 'Kalem'"></div>
                                                                            <div class="text-[11px] text-gray-400 mt-0.5" x-text="'Birim: ' + formatCur(line.unit_price)"></div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="text-right ml-3 flex-shrink-0">
                                                                        <div class="text-[14px] font-black text-gray-900" x-text="formatCur(line.total_price || (line.unit_price * (line.quantity||1)))"></div>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                            <div x-show="!(item.items||[]).length" class="p-8 text-center text-gray-400 text-sm">
                                                                <i class="fas fa-inbox text-2xl mb-2"></i>
                                                                <p>Kalem detayı bulunamadı</p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 space-y-3">
                                                        <div class="flex justify-between items-center text-sm">
                                                            <span class="text-gray-500">Ara Toplam</span>
                                                            <span class="font-bold text-gray-900" x-text="formatCur(item.subtotal || item.grand_total)"></span>
                                                        </div>
                                                        <template x-if="item.tax_total > 0">
                                                            <div class="flex justify-between items-center text-sm">
                                                                <span class="text-gray-500">KDV</span>
                                                                <span class="font-bold text-gray-900" x-text="formatCur(item.tax_total)"></span>
                                                            </div>
                                                        </template>
                                                        <template x-if="item.discount_amount > 0">
                                                            <div class="flex justify-between items-center text-sm">
                                                                <span class="text-gray-500">İndirim</span>
                                                                <span class="text-red-500 font-bold" x-text="'-' + formatCur(item.discount_amount)"></span>
                                                            </div>
                                                        </template>
                                                        <div class="pt-3 border-t border-gray-100 flex justify-between items-center">
                                                            <span class="text-base font-bold text-gray-800">Genel Toplam</span>
                                                            <span class="text-xl font-black text-gray-900" x-text="formatCur(item.grand_total)"></span>
                                                        </div>
                                                    </div>

                                                    <template x-if="item.paid_amount > 0">
                                                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 space-y-2">
                                                            <div class="flex justify-between items-center text-sm">
                                                                <span class="text-gray-500">Ödenen</span>
                                                                <span class="text-emerald-600 font-bold" x-text="formatCur(item.paid_amount)"></span>
                                                            </div>
                                                            <div class="flex justify-between items-center text-sm" x-show="item.grand_total - item.paid_amount > 0">
                                                                <span class="text-gray-500">Kalan</span>
                                                                <span class="text-orange-500 font-bold" x-text="formatCur(item.grand_total - item.paid_amount)"></span>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                            </div>
                                        </template>
                                        {{-- İşlem Satırı --}}
                                        <template x-if="item._type === 'tx'">
                                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-100">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold"
                                                         :class="item.amount > 0 ? 'bg-emerald-500' : 'bg-red-500'">
                                                        <i :class="item.amount > 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-800" x-text="item.description || item.type"></p>
                                                        <p class="text-xs text-gray-400" x-text="item.transaction_date ? new Date(item.transaction_date).toLocaleDateString('tr-TR') : ''"></p>
                                                    </div>
                                                </div>
                                                <p class="text-sm font-bold" :class="item.amount > 0 ? 'text-emerald-600' : 'text-red-500'"
                                                   x-text="(item.amount > 0 ? '+' : '') + formatCur(item.amount)"></p>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Alış Faturaları --}}
                        <div x-show="detailTab === 'invoices'">
                            <div x-show="(detailData.purchase_invoices||[]).length === 0" class="text-center py-8 text-gray-400 text-sm">Alış faturası bulunamadı</div>
                            <div class="space-y-2">
                                <template x-for="inv in (detailData.purchase_invoices||[])" :key="inv.id">
                                    <div>
                                    <div @click="toggleInvoiceDetails(inv)"
                                         class="w-full flex items-center justify-between p-3 bg-white border border-gray-100 rounded-xl hover:border-orange-200 hover:shadow-sm hover:shadow-orange-500/10 transition-all text-left group cursor-pointer">
                                        <div class="flex items-center gap-3">
                                            <label class="flex items-center" @click.stop>
                                                <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-orange-500 focus:ring-orange-500" :checked="selectedInvoiceIds.includes(inv.id)" @change="toggleFirmInvoiceSelection(inv.id)">
                                            </label>
                                            <div class="w-9 h-9 rounded-full bg-orange-50 flex items-center justify-center group-hover:scale-110 group-hover:bg-orange-100 transition-transform shadow-sm">
                                                <i class="fas fa-file-invoice text-orange-500 text-sm"></i>
                                            </div>
                                            <div>
                                                <p class="text-[13px] font-bold text-gray-800" x-text="inv.invoice_no || ('Fatura #' + inv.id)"></p>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <span class="text-[11px] text-gray-400" x-text="inv.invoice_date ? new Date(inv.invoice_date).toLocaleDateString('tr-TR',{day:'numeric',month:'short',year:'numeric'}) : ''"></span>
                                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md"
                                                          :class="inv.payment_status === 'paid' ? 'bg-green-100 text-green-600' : 'bg-orange-100 text-orange-600'"
                                                          x-text="inv.payment_status === 'paid' ? 'Ödendi' : 'Bekliyor'"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <p class="text-[15px] font-black text-gray-900" x-text="formatCur(inv.grand_total)"></p>
                                            <i class="fas text-gray-300 text-xs group-hover:text-orange-400 group-hover:translate-x-0.5 transition-all"
                                               :class="expandedInvoiceKey === ('inv_' + inv.id) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                        </div>
                                    </div>
                                    <div x-show="expandedInvoiceKey === ('inv_' + inv.id)" x-transition class="mt-2 rounded-2xl border border-orange-100 bg-orange-50/50 p-4" :id="'firm-invoice-print-list-' + inv.id">
                                        <div class="flex items-start justify-between gap-3 mb-4">
                                            <div>
                                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1" x-text="detailData?.firm?.name"></p>
                                                <div class="text-2xl font-black text-gray-900 tracking-tight" x-text="formatCur(inv.grand_total)"></div>
                                                <div class="mt-2 flex items-center gap-2 flex-wrap">
                                                    <span class="text-[11px] px-2 py-0.5 rounded-lg font-bold"
                                                          :class="inv.payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'"
                                                          x-text="inv.payment_status === 'paid' ? 'Ödendi' : 'Ödenmedi'"></span>
                                                    <span class="text-xs text-gray-400" x-text="inv.invoice_date ? new Date(inv.invoice_date).toLocaleString('tr-TR',{day:'numeric',month:'long',year:'numeric'}) : (inv.created_at ? new Date(inv.created_at).toLocaleString('tr-TR',{day:'numeric',month:'long',year:'numeric'}) : '')"></span>
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap justify-end gap-2 shrink-0">
                                                <button type="button" @click="sendFirmReportWhatsApp('single', inv)" class="px-3 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors"><i class="fab fa-whatsapp"></i> WhatsApp</button>
                                                <button type="button" @click="sendFirmReportEmail('single', inv)" class="px-3 py-2 bg-sky-500 hover:bg-sky-600 text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors"><i class="fas fa-envelope"></i> E-posta</button>
                                                <button type="button" @click="printFirmReport('single', inv)" class="px-3 py-2 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors"><i class="fas fa-print"></i> Yazdır</button>
                                            </div>
                                        </div>

                                        <div x-show="inv._loading" class="py-10 text-center text-gray-400 text-sm">
                                            <i class="fas fa-spinner fa-spin text-lg mb-2"></i>
                                            <p>Fatura detayı yükleniyor...</p>
                                        </div>

                                        <div x-show="!inv._loading" class="space-y-3">
                                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                                                <div class="divide-y divide-gray-50">
                                                    <template x-for="line in (inv.items||[])" :key="line.id">
                                                        <div class="p-4 flex items-center justify-between hover:bg-gray-50/50 transition-colors">
                                                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                                                <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600 flex-shrink-0" x-text="line.quantity||1"></div>
                                                                <div class="min-w-0">
                                                                    <div class="text-[13px] font-bold text-gray-800 truncate" x-text="line.product_name || line.description || 'Kalem'"></div>
                                                                    <div class="text-[11px] text-gray-400 mt-0.5" x-text="'Birim: ' + formatCur(line.unit_price)"></div>
                                                                </div>
                                                            </div>
                                                            <div class="text-right ml-3 flex-shrink-0">
                                                                <div class="text-[14px] font-black text-gray-900" x-text="formatCur(line.total_price || (line.unit_price * (line.quantity||1)))"></div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                    <div x-show="!(inv.items||[]).length" class="p-8 text-center text-gray-400 text-sm">
                                                        <i class="fas fa-inbox text-2xl mb-2"></i>
                                                        <p>Kalem detayı bulunamadı</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 space-y-3">
                                                <div class="flex justify-between items-center text-sm">
                                                    <span class="text-gray-500">Ara Toplam</span>
                                                    <span class="font-bold text-gray-900" x-text="formatCur(inv.subtotal || inv.grand_total)"></span>
                                                </div>
                                                <template x-if="inv.tax_total > 0">
                                                    <div class="flex justify-between items-center text-sm">
                                                        <span class="text-gray-500">KDV</span>
                                                        <span class="font-bold text-gray-900" x-text="formatCur(inv.tax_total)"></span>
                                                    </div>
                                                </template>
                                                <template x-if="inv.discount_amount > 0">
                                                    <div class="flex justify-between items-center text-sm">
                                                        <span class="text-gray-500">İndirim</span>
                                                        <span class="text-red-500 font-bold" x-text="'-' + formatCur(inv.discount_amount)"></span>
                                                    </div>
                                                </template>
                                                <div class="pt-3 border-t border-gray-100 flex justify-between items-center">
                                                    <span class="text-base font-bold text-gray-800">Genel Toplam</span>
                                                    <span class="text-xl font-black text-gray-900" x-text="formatCur(inv.grand_total)"></span>
                                                </div>
                                            </div>

                                            <template x-if="inv.paid_amount > 0">
                                                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 space-y-2">
                                                    <div class="flex justify-between items-center text-sm">
                                                        <span class="text-gray-500">Ödenen</span>
                                                        <span class="text-emerald-600 font-bold" x-text="formatCur(inv.paid_amount)"></span>
                                                    </div>
                                                    <div class="flex justify-between items-center text-sm" x-show="inv.grand_total - inv.paid_amount > 0">
                                                        <span class="text-gray-500">Kalan</span>
                                                        <span class="text-orange-500 font-bold" x-text="formatCur(inv.grand_total - inv.paid_amount)"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Ödemeler --}}
                        <div x-show="detailTab === 'payments'">
                            <div x-show="(detailData.payments||[]).length === 0" class="text-center py-8 text-gray-400 text-sm">Ödeme kaydı yok</div>
                            <div class="space-y-2">
                                <template x-for="t in (detailData.payments||[])" :key="t.id">
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-100">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center text-white text-xs">
                                                <i class="fas fa-arrow-up"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-800" x-text="t.description || t.type"></p>
                                                <p class="text-xs text-gray-400" x-text="t.transaction_date ? new Date(t.transaction_date).toLocaleDateString('tr-TR') : ''"></p>
                                            </div>
                                        </div>
                                        <p class="text-sm font-bold text-emerald-600" x-text="'+' + formatCur(t.amount)"></p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Notlar --}}
                        <div x-show="detailTab === 'notes'">
                            <div class="bg-gray-50 rounded-xl border border-gray-100 p-4 text-sm text-gray-700 whitespace-pre-line min-h-[80px]" x-text="detailData.firm.notes || 'Not bulunmuyor.'"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Borç Ekle Modal --}}
    <div x-show="showDebtModal" x-transition class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showDebtModal = false"></div>
        <div class="relative bg-white rounded-xl border border-gray-100 shadow-2xl w-full max-w-md" x-transition>
            <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900"><i class="fas fa-plus-circle text-red-500 mr-2"></i>Borç Ekle</h2>
                <button @click="showDebtModal = false" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form @submit.prevent="submitDebt()" class="p-6 space-y-4">
                <div class="bg-red-50 rounded-lg border border-red-100 p-4">
                    <p class="text-sm text-gray-600">Cari: <span class="font-semibold text-gray-900" x-text="debtFirmName"></span></p>
                    <p class="text-sm text-gray-600 mt-1">Mevcut Bakiye: <span class="font-mono font-semibold" :class="debtFirmBalance < 0 ? 'text-red-500' : 'text-emerald-600'" x-text="formatCur(debtFirmBalance)"></span></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Tutar <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" x-model="debtForm.amount" x-ref="debtAmountInput" step="0.01" min="0.01" required
                               class="w-full bg-white border border-gray-200 text-gray-900 text-lg rounded-lg pl-4 pr-8 py-2.5 font-mono focus:border-red-400"
                               placeholder="0.00">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 font-semibold">₺</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Açıklama</label>
                    <input type="text" x-model="debtForm.description" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5" placeholder="Borç açıklaması (opsiyonel)">
                </div>
                <div class="flex gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="showDebtModal = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 rounded-lg">İptal</button>
                    <button type="submit" :disabled="addingDebt" class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 rounded-lg disabled:opacity-50 flex items-center justify-center gap-2">
                        <svg x-show="addingDebt" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="addingDebt ? 'Kaydediliyor...' : 'Borç Ekle'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function firmManager() {
    return {
        showFormModal: false, showPaymentModal: false, showDetailModal: false,
        showDebtModal: false,
        showGroupPanel: false, showGroupForm: false,
        expandedInvoiceKey: null,
        selectedInvoiceIds: [],
        loadingInvoiceKey: null,
        editingId: null, saving: false, paying: false, addingDebt: false, detailLoading: false,
        detailData: null,
        detailTab: 'timeline',
        searchQuery: new URLSearchParams(window.location.search).get('search') || '',
        groupFilter: new URLSearchParams(window.location.search).get('group_id') || '',
        newGroupName: '', editingGroupId: null,
        form: { name: '', firm_group_id: '', tax_number: '', tax_office: '', phones: [{phone: '', type: 'mobile', is_primary: true}], email: '', address: '', city: '', notes: '' },
        payForm: { amount: '', description: '' }, payFirmId: null, payFirmName: '', payFirmBalance: 0,
        debtForm: { amount: '', description: '' }, debtFirmId: null, debtFirmName: '', debtFirmBalance: 0,

        init() {},

        // ─── Telefon yönetimi ────────────────────────────────
        addPhone() { this.form.phones.push({phone: '', type: 'mobile', is_primary: false}); },
        removePhone(idx) {
            const wasPrimary = this.form.phones[idx].is_primary;
            this.form.phones.splice(idx, 1);
            if (wasPrimary && this.form.phones.length > 0) this.form.phones[0].is_primary = true;
        },
        setPrimaryPhone(idx) { this.form.phones.forEach((p, i) => p.is_primary = (i === idx)); },

        openCreate() {
            this.editingId = null;
            this.form = { name: '', firm_group_id: '', tax_number: '', tax_office: '', phones: [{phone: '', type: 'mobile', is_primary: true}], email: '', address: '', city: '', notes: '' };
            this.showFormModal = true;
        },
        openEdit(f) {
            this.editingId = f.id;
            let phones = [];
            if (f.phones && f.phones.length > 0) {
                phones = f.phones.map(p => ({phone: p.phone, type: p.type || 'mobile', is_primary: !!p.is_primary}));
            } else {
                phones = [{phone: '', type: 'mobile', is_primary: true}];
            }
            this.form = {
                name: f.name||'', firm_group_id: f.firm_group_id ? String(f.firm_group_id) : '',
                tax_number: f.tax_number||'', tax_office: f.tax_office||'',
                phones: phones,
                email: f.email||'', address: f.address||'', city: f.city||'', notes: f.notes||''
            };
            this.showFormModal = true;
        },
        openPayment(id, name, balance) { this.payFirmId = id; this.payFirmName = name; this.payFirmBalance = balance; this.payForm = { amount: '', description: '' }; this.showPaymentModal = true; },
        openDebt(id, name, balance) {
            this.debtFirmId = id; this.debtFirmName = name; this.debtFirmBalance = balance;
            this.debtForm = { amount: '', description: '' };
            this.showDebtModal = true;
            this.$nextTick(() => this.$refs.debtAmountInput?.focus());
        },

        async openDetail(id) {
            this.detailData = null;
            this.detailTab = 'timeline';
            this.expandedInvoiceKey = null;
            this.selectedInvoiceIds = [];
            this.detailLoading = true;
            this.showDetailModal = true;
            try {
                const data = await posAjax(`/firms/${id}`, {}, 'GET');
                // Faturalara _open durumu ve _key ekle
                (data.purchase_invoices || []).forEach(inv => { inv._open = false; inv._loading = false; inv._type = 'invoice'; inv._key = 'inv_'+inv.id; });
                (data.transactions || []).forEach(t => { t._type = 'tx'; t._key = 'tx_'+t.id; });
                this.detailData = data;
            } catch(e) { showToast('Detay yüklenemedi', 'error'); this.showDetailModal = false; }
            finally { this.detailLoading = false; }
        },

        async toggleInvoiceDetails(inv) {
            const key = inv._key || ('inv_' + inv.id);
            if (this.expandedInvoiceKey === key) {
                this.expandedInvoiceKey = null;
                return;
            }

            this.expandedInvoiceKey = key;
            if (inv.items && inv.items.length) return;

            inv._loading = true;
            this.loadingInvoiceKey = key;
            try {
                const url = '{{ route("pos.purchase-invoices.show", ":id") }}'.replace(':id', inv.id);
                const data = await posAjax(url, {}, 'GET');
                Object.assign(inv, data, { _key: key, _type: 'invoice', _loading: false });
            } catch (e) {
                showToast(e.message || 'Fatura detayı yüklenemedi.', 'error');
                this.expandedInvoiceKey = null;
                inv._loading = false;
            } finally {
                this.loadingInvoiceKey = null;
            }
        },

        toggleFirmInvoiceSelection(id) {
            if (this.selectedInvoiceIds.includes(id)) {
                this.selectedInvoiceIds = this.selectedInvoiceIds.filter(selectedId => selectedId !== id);
                return;
            }
            this.selectedInvoiceIds = [...this.selectedInvoiceIds, id];
        },

        toggleAllFirmInvoices(selectAll) {
            if (!selectAll) {
                this.selectedInvoiceIds = [];
                return;
            }
            this.selectedInvoiceIds = (this.detailData?.purchase_invoices || []).map(invoice => invoice.id);
        },

        normalizeWhatsappPhone(phone) {
            const digits = String(phone || '').replace(/\D/g, '');
            if (!digits) return '';
            if (digits.startsWith('90')) return digits;
            if (digits.startsWith('0') && digits.length === 11) return '90' + digits.slice(1);
            if (digits.length === 10) return '90' + digits;
            return digits;
        },

        buildWhatsappText(text, maxLength = 1500) {
            const content = String(text || '').trim();
            if (!content) return '';
            if (content.length <= maxLength) return content;
            return content.slice(0, maxLength - 28).trimEnd() + '\n\n...rapor kısaltıldı';
        },

        openWhatsappReport(phone, text) {
            const message = this.buildWhatsappText(text);
            const url = `https://api.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(message)}`;
            const popup = window.open(url, '_blank', 'noopener');
            if (!popup) {
                window.location.href = url;
            }
        },

        escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        },

        formatReportDate(value) {
            if (!value) return '-';
            return new Date(value).toLocaleString('tr-TR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },

        getFirmReportPhone() {
            const phones = this.detailData?.phones || [];
            const rawPhone = phones.find(phone => phone.is_primary)?.phone || phones[0]?.phone || this.detailData?.firm?.phone || '';
            return this.normalizeWhatsappPhone(rawPhone);
        },

        getFirmReportEmail() {
            return this.detailData?.firm?.email || '';
        },

        resolveFirmReportInvoices(scope, invoice = null) {
            const invoices = this.detailData?.purchase_invoices || [];
            if (scope === 'single' && invoice) return [invoice];
            if (scope === 'all') return invoices;
            return invoices.filter(currentInvoice => this.selectedInvoiceIds.includes(currentInvoice.id));
        },

        buildFirmReport(scope, invoice = null) {
            if (!this.detailData?.firm) {
                showToast('Rapor verisi bulunamadı.', 'error');
                return null;
            }

            const firm = this.detailData.firm;
            const invoices = this.resolveFirmReportInvoices(scope, invoice);
            if (!invoices.length) {
                showToast(scope === 'selected' ? 'Önce en az bir fatura seçin.' : 'Raporlanacak fatura bulunamadı.', 'warning');
                return null;
            }

            const includeAllHistory = scope === 'all';
            const payments = includeAllHistory ? (this.detailData.payments || this.detailData.transactions || []) : [];
            const total = invoices.reduce((sum, currentInvoice) => sum + parseFloat(currentInvoice.grand_total || 0), 0);
            const invoicesPreview = invoices.slice(0, 12);
            const paymentsPreview = payments.slice(0, 8);
            const reportTitle = scope === 'single'
                ? `Fatura Raporu - ${firm.name}`
                : (scope === 'all' ? `Tüm Cari Geçmişi - ${firm.name}` : `Seçili Faturalar Raporu - ${firm.name}`);

            const textLines = [
                reportTitle,
                `Firma: ${firm.name}`,
                `Bakiye: ${this.formatCur(firm.balance || 0)}`,
                `Fatura Sayısı: ${invoices.length}`,
                `Toplam Tutar: ${this.formatCur(total)}`,
                '',
                'Faturalar:',
                ...invoicesPreview.map(currentInvoice => `- ${(currentInvoice.invoice_no || ('Fatura #' + currentInvoice.id))} | ${this.formatReportDate(currentInvoice.invoice_date || currentInvoice.created_at)} | ${this.formatCur(currentInvoice.grand_total)} | ${currentInvoice.payment_status === 'paid' ? 'Ödendi' : 'Bekliyor'}`),
            ];

            if (invoices.length > invoicesPreview.length) {
                textLines.push(`... ve ${invoices.length - invoicesPreview.length} fatura daha`);
            }

            if (includeAllHistory && payments.length) {
                textLines.push('', 'Son Ödeme / Hareketler:');
                paymentsPreview.forEach(payment => {
                    const amount = payment.amount ?? payment.paid_amount ?? 0;
                    textLines.push(`- ${payment.description || payment.type || 'Hareket'} | ${this.formatReportDate(payment.transaction_date || payment.payment_date || payment.created_at)} | ${(amount >= 0 ? '+' : '-') + this.formatCur(Math.abs(amount || 0))}`);
                });
                if (payments.length > paymentsPreview.length) {
                    textLines.push(`... ve ${payments.length - paymentsPreview.length} hareket daha`);
                }
            }

            const invoicesHtml = invoices.map(currentInvoice => `
                <tr>
                    <td>${this.escapeHtml(currentInvoice.invoice_no || ('Fatura #' + currentInvoice.id))}</td>
                    <td>${this.escapeHtml(this.formatReportDate(currentInvoice.invoice_date || currentInvoice.created_at))}</td>
                    <td>${this.escapeHtml(currentInvoice.payment_status === 'paid' ? 'Ödendi' : 'Bekliyor')}</td>
                    <td style="text-align:right;">${this.escapeHtml(this.formatCur(currentInvoice.grand_total))}</td>
                </tr>
            `).join('');

            const paymentsHtml = includeAllHistory ? payments.map(payment => {
                const amount = payment.amount ?? payment.paid_amount ?? 0;
                return `
                    <tr>
                        <td>${this.escapeHtml(payment.description || payment.type || 'Hareket')}</td>
                        <td>${this.escapeHtml(this.formatReportDate(payment.transaction_date || payment.payment_date || payment.created_at))}</td>
                        <td style="text-align:right;">${this.escapeHtml((amount >= 0 ? '+' : '-') + this.formatCur(Math.abs(amount || 0)))}</td>
                    </tr>
                `;
            }).join('') : '';

            const html = `<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><title>${this.escapeHtml(reportTitle)}</title><style>
                body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;padding:24px;color:#111827;background:#fff}
                h1{font-size:24px;margin-bottom:8px} h2{font-size:16px;margin:24px 0 12px} p{margin:4px 0;color:#4b5563}
                .cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin:20px 0}
                .card{border:1px solid #e5e7eb;border-radius:14px;padding:14px;background:#f9fafb}
                .card .label{font-size:12px;color:#6b7280;margin-bottom:6px}.card .value{font-size:20px;font-weight:800;color:#111827}
                table{width:100%;border-collapse:collapse;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden} th,td{padding:10px 12px;border-bottom:1px solid #e5e7eb;font-size:13px;text-align:left}
                th{background:#f3f4f6;color:#374151;font-weight:700} tr:last-child td{border-bottom:none}
                @media print{body{padding:8mm}}
            </style></head><body>
                <h1>${this.escapeHtml(reportTitle)}</h1>
                <p>Firma: ${this.escapeHtml(firm.name)}</p>
                <p>Bakiye: ${this.escapeHtml(this.formatCur(firm.balance || 0))}</p>
                <div class="cards">
                    <div class="card"><div class="label">Fatura Sayısı</div><div class="value">${invoices.length}</div></div>
                    <div class="card"><div class="label">Toplam Tutar</div><div class="value">${this.escapeHtml(this.formatCur(total))}</div></div>
                    <div class="card"><div class="label">Rapor Tipi</div><div class="value" style="font-size:16px;">${this.escapeHtml(scope === 'single' ? 'Tek Fatura' : (scope === 'all' ? 'Tüm Geçmiş' : 'Seçili Faturalar'))}</div></div>
                </div>
                <h2>Faturalar</h2>
                <table><thead><tr><th>Fatura No</th><th>Tarih</th><th>Durum</th><th style="text-align:right;">Tutar</th></tr></thead><tbody>${invoicesHtml}</tbody></table>
                ${includeAllHistory ? `<h2>Ödeme / Hareketler</h2><table><thead><tr><th>Açıklama</th><th>Tarih</th><th style="text-align:right;">Tutar</th></tr></thead><tbody>${paymentsHtml || '<tr><td colspan="3">Hareket yok</td></tr>'}</tbody></table>` : ''}
            </body></html>`;

            return { title: reportTitle, subject: reportTitle, text: textLines.join('\n'), html };
        },

        sendFirmReportWhatsApp(scope, invoice = null) {
            const report = this.buildFirmReport(scope, invoice);
            if (!report) return;
            const phone = this.getFirmReportPhone();
            if (!phone) {
                showToast('WhatsApp için firma telefon numarası bulunamadı.', 'warning');
                return;
            }
            this.openWhatsappReport(phone, report.text);
        },

        sendFirmReportEmail(scope, invoice = null) {
            const report = this.buildFirmReport(scope, invoice);
            if (!report) return;
            const email = this.getFirmReportEmail();
            if (!email) {
                showToast('E-posta göndermek için firma e-posta adresi bulunamadı.', 'warning');
                return;
            }
            window.location.href = `mailto:${encodeURIComponent(email)}?subject=${encodeURIComponent(report.subject)}&body=${encodeURIComponent(report.text)}`;
        },

        printFirmReport(scope, invoice = null) {
            const report = this.buildFirmReport(scope, invoice);
            if (!report) return;
            const reportWindow = window.open('', '', 'width=960,height=760');
            reportWindow.document.write(report.html);
            reportWindow.document.close();
            setTimeout(() => { reportWindow.focus(); reportWindow.print(); }, 500);
        },

        printInvoice(invoice, printAreaId) {
            const printArea = document.getElementById(printAreaId);
            if (!printArea) return;
            const printWindow = window.open('', '', 'width=850,height=700');
            printWindow.document.write(`<!DOCTYPE html><html lang="tr"><head>
                <meta charset="UTF-8"><title>Alım Faturası</title>
                <style>
                    * { box-sizing: border-box; margin: 0; padding: 0; }
                    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 20px; color: #111; background: #fff; }
                    .space-y-5 > * + * { margin-top: 20px; }
                    h4 { font-size: 14px; font-weight: 700; margin-bottom: 10px; color: #374151; }
                    .card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; background: #fff; margin-bottom: 16px; }
                    .row { display: flex; justify-content: space-between; align-items: center; padding: 10px 8px; border-bottom: 1px solid #f3f4f6; }
                    .row:last-child { border-bottom: none; }
                    .total-row { border-top: 2px solid #e5e7eb; padding-top: 12px; margin-top: 8px; display: flex; justify-content: space-between; }
                    .text-xs { font-size: 12px; color: #6b7280; }
                    .font-black { font-weight: 900; }
                    .text-3xl { font-size: 28px; }
                    .text-right { text-align: right; }
                    footer { text-align: center; margin-top: 30px; font-size: 11px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 16px; }
                    @media print { body { padding: 5mm; } }
                </style>
            </head><body>${printArea.innerHTML}</body></html>`);
            printWindow.document.close();
            setTimeout(() => { printWindow.focus(); printWindow.print(); }, 600);
        },

        get detailTimeline() {
            if (!this.detailData) return [];
            const invoices = (this.detailData.purchase_invoices || []).map(i => ({...i, _type:'invoice', _key:'inv_'+i.id}));
            const txs = (this.detailData.transactions || []).map(t => ({...t, _type:'tx', _key:'tx_'+t.id}));
            const all = [...invoices, ...txs];
            all.sort((a, b) => {
                const dA = new Date(a.invoice_date || a.transaction_date || a.created_at);
                const dB = new Date(b.invoice_date || b.transaction_date || b.created_at);
                return dB - dA;
            });
            return all;
        },

        applySearch() {
            const params = new URLSearchParams();
            if (this.searchQuery) params.set('search', this.searchQuery);
            if (this.groupFilter) params.set('group_id', this.groupFilter);
            window.location.href = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        },
        async submitForm() {
            this.saving = true;
            const url = this.editingId ? `/firms/${this.editingId}` : '/firms';
            const method = this.editingId ? 'PUT' : 'POST';
            try {
                await posAjax(url, this.form, method);
                showToast(this.editingId ? 'Cari güncellendi' : 'Cari oluşturuldu', 'success');
                this.showFormModal = false; window.location.reload();
            } catch (e) { showToast(e.message || 'Hata', 'error'); } finally { this.saving = false; }
        },
        async submitPayment() {
            this.paying = true;
            try {
                await posAjax(`/firms/${this.payFirmId}/payment`, this.payForm, 'POST');
                showToast('Ödeme kaydedildi', 'success'); this.showPaymentModal = false; window.location.reload();
            } catch (e) { showToast(e.message || 'Hata', 'error'); } finally { this.paying = false; }
        },
        async submitDebt() {
            if (!this.debtForm.amount || parseFloat(this.debtForm.amount) <= 0) { showToast('Geçerli bir tutar girin', 'error'); return; }
            this.addingDebt = true;
            try {
                await posAjax(`/firms/${this.debtFirmId}/debt`, this.debtForm, 'POST');
                showToast('Borç başarıyla eklendi', 'success'); this.showDebtModal = false; window.location.reload();
            } catch (e) { showToast(e.message || 'Borç eklenemedi', 'error'); } finally { this.addingDebt = false; }
        },
        // Grup CRUD
        editGroup(id, name) { this.editingGroupId = id; this.newGroupName = name; this.showGroupForm = true; },
        async saveGroup() {
            if (!this.newGroupName.trim()) return;
            try {
                if (this.editingGroupId) {
                    await posAjax(`/firm-groups/${this.editingGroupId}`, { name: this.newGroupName }, 'PUT');
                    showToast('Grup güncellendi', 'success');
                } else {
                    await posAjax('/firm-groups', { name: this.newGroupName }, 'POST');
                    showToast('Grup oluşturuldu', 'success');
                }
                window.location.reload();
            } catch(e) { showToast(e.message || 'Hata', 'error'); }
        },
        async deleteGroup(id) {
            if (!confirm('Bu grubu silmek istediğinize emin misiniz?')) return;
            try { await posAjax(`/firm-groups/${id}`, {}, 'DELETE'); showToast('Grup silindi', 'success'); window.location.reload(); }
            catch(e) { showToast(e.message || 'Silinemedi', 'error'); }
        },
        formatCur(v) { return new Intl.NumberFormat('tr-TR', {style:'currency', currency:'TRY'}).format(v||0); },
    };
}
</script>
@endpush

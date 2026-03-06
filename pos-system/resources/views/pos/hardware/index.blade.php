@extends('pos.layouts.app')
@section('title', 'Donanım Yönetimi')

@section('content')
<div class="p-6 space-y-6" x-data="hardwareManager()">

    {{-- Başlık --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">🔧 Donanım Yönetimi</h1>
            <p class="text-sm text-gray-500 mt-1">Yazıcı, barkod okuyucu, tartı, para çekmecesi ve diğer cihazlar</p>
        </div>
        <button @click="openAdd()"
                class="flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-brand-500 to-purple-600 text-white text-sm font-semibold rounded-xl hover:opacity-90 transition shadow-sm">
            <i class="fa-solid fa-plus"></i> Cihaz Ekle
        </button>
    </div>

    {{-- İstatistik Kartlar --}}
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Toplam Cihaz</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
            <p class="text-2xl font-bold text-emerald-600">{{ $stats['active'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Aktif</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
            <p class="text-2xl font-bold text-blue-600">{{ $stats['connected'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Bağlı</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
            <p class="text-2xl font-bold text-brand-600">{{ $stats['printer'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Yazıcı</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
            <p class="text-2xl font-bold text-emerald-600">{{ $stats['scanner'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Barkod Okuyucu</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
            <p class="text-2xl font-bold text-amber-600">{{ $stats['scale'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Tartı</p>
        </div>
    </div>

    {{-- Tab --}}
    <div class="flex gap-1 bg-gray-100 p-1 rounded-xl w-fit">
        <button @click="tab='devices'"
                :class="tab==='devices' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                class="px-5 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-microchip mr-1.5"></i> Cihazlarım
        </button>
        <button @click="tab='catalog'"
                :class="tab==='catalog' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                class="px-5 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-book-open mr-1.5"></i> Sürücü Kataloğu
        </button>
    </div>

    {{-- SEKME: Cihazlarım --}}
    <div x-show="tab==='devices'" x-cloak>
        @if($devices->isEmpty())
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm py-20 text-center">
            <div class="text-6xl mb-4">🔌</div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Henüz cihaz eklenmemiş</h3>
            <p class="text-sm text-gray-400 mb-6">Yazıcı, barkod okuyucu veya tartınızı ekleyerek başlayın.</p>
            <button @click="openAdd()" class="px-6 py-2.5 bg-gradient-to-r from-brand-500 to-purple-600 text-white text-sm font-semibold rounded-xl hover:opacity-90 transition">
                <i class="fa-solid fa-plus mr-1"></i> İlk Cihazı Ekle
            </button>
        </div>
        @else
        {{-- Tip bazlı gruplandırma --}}
        @php
        $typeLabels = [
            'printer'          => ['icon'=>'fa-print',         'label'=>'Fiş Yazıcılar',      'color'=>'brand'],
            'barcode_scanner'  => ['icon'=>'fa-barcode',       'label'=>'Barkod Okuyucular',   'color'=>'emerald'],
            'scale'            => ['icon'=>'fa-weight-scale',  'label'=>'Tartı / Baskül',      'color'=>'amber'],
            'cash_drawer'      => ['icon'=>'fa-cash-register', 'label'=>'Para Çekmeceleri',    'color'=>'purple'],
            'customer_display' => ['icon'=>'fa-display',       'label'=>'Müşteri Ekranları',   'color'=>'blue'],
            'other'            => ['icon'=>'fa-microchip',     'label'=>'Diğer Cihazlar',      'color'=>'gray'],
        ];
        $grouped = $devices->groupBy('type');
        @endphp

        <div class="space-y-6">
            @foreach($grouped as $type => $typeDevices)
            @php $meta = $typeLabels[$type] ?? $typeLabels['other']; @endphp
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3 bg-gray-50/70 border-b border-gray-100 flex items-center gap-2">
                    <i class="fa-solid {{ $meta['icon'] }} text-{{ $meta['color'] }}-500 text-sm"></i>
                    <span class="text-sm font-semibold text-gray-700">{{ $meta['label'] }}</span>
                    <span class="ml-auto text-xs text-gray-400">{{ $typeDevices->count() }} cihaz</span>
                </div>
                <div class="divide-y divide-gray-50">
                    @foreach($typeDevices as $device)
                    <div class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50/50 transition">
                        {{-- Durum göstergesi --}}
                        <div class="relative flex-shrink-0">
                            <div class="w-11 h-11 rounded-xl bg-{{ $meta['color'] }}-50 flex items-center justify-center">
                                <i class="fa-solid {{ $meta['icon'] }} text-{{ $meta['color'] }}-500"></i>
                            </div>
                            @if($device->status === 'connected')
                            <span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-emerald-500 rounded-full border-2 border-white"></span>
                            @elseif($device->is_active)
                            <span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-gray-300 rounded-full border-2 border-white"></span>
                            @else
                            <span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-red-400 rounded-full border-2 border-white"></span>
                            @endif
                        </div>

                        {{-- Bilgiler --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-gray-900 text-sm truncate">{{ $device->name }}</p>
                                @if($device->is_default)
                                <span class="px-2 py-0.5 bg-brand-50 text-brand-600 text-[10px] font-bold rounded-full">VARSAYİLAN</span>
                                @endif
                                @if(!$device->is_active)
                                <span class="px-2 py-0.5 bg-red-50 text-red-500 text-[10px] font-bold rounded-full">PASİF</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ $device->manufacturer }} {{ $device->model }}
                                @if($device->manufacturer || $device->model) · @endif
                                <span class="uppercase">{{ str_replace('_', ' ', $device->connection) }}</span>
                                @if($device->ip_address) · {{ $device->ip_address }}:{{ $device->port }} @endif
                                @if($device->serial_port) · {{ $device->serial_port }} @endif
                                @if($device->protocol) · {{ $device->protocol }} @endif
                            </p>
                            @if($device->last_seen_at)
                            <p class="text-[11px] text-gray-300 mt-0.5">Son görülme: {{ $device->last_seen_at->diffForHumans() }}</p>
                            @endif
                        </div>

                        {{-- Durum Badge --}}
                        @php
                        $statusMap = [
                            'connected'    => ['bg-emerald-50 text-emerald-700', 'Bağlı'],
                            'disconnected' => ['bg-gray-100 text-gray-500',      'Bağlı Değil'],
                            'error'        => ['bg-red-50 text-red-600',          'Hata'],
                        ];
                        [$sc, $sl] = $statusMap[$device->status] ?? ['bg-gray-100 text-gray-500', $device->status];
                        @endphp
                        <span class="px-2.5 py-1 rounded-lg text-xs font-semibold {{ $sc }} hidden sm:inline-flex">{{ $sl }}</span>

                        {{-- Aksiyon butonları --}}
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <button
                                @click="testDevice({{ $device->id }}, $event)"
                                class="px-3 py-1.5 bg-blue-50 text-blue-600 text-xs font-semibold rounded-lg hover:bg-blue-100 transition"
                                title="Bağlantı Testi">
                                <i class="fa-solid fa-plug-circle-check"></i>
                                <span class="hidden sm:inline ml-1">Test</span>
                            </button>
                            <button
                                @click="openEdit({{ $device->id }})"
                                class="p-2 text-gray-300 hover:text-brand-500 hover:bg-brand-50 transition rounded-lg"
                                title="Düzenle">
                                <i class="fa-solid fa-pen text-xs"></i>
                            </button>
                            <button
                                @click="deleteDevice({{ $device->id }})"
                                class="p-2 text-gray-300 hover:text-red-400 hover:bg-red-50 transition rounded-lg"
                                title="Sil">
                                <i class="fa-solid fa-trash text-xs"></i>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- SEKME: Sürücü Kataloğu --}}
    <div x-show="tab==='catalog'" x-cloak>
        <div class="space-y-6">
            @php
            $catalogTypes = [
                'printer'          => ['icon'=>'fa-print',         'label'=>'Fiş Yazıcılar'],
                'barcode_scanner'  => ['icon'=>'fa-barcode',       'label'=>'Barkod Okuyucular'],
                'scale'            => ['icon'=>'fa-weight-scale',  'label'=>'Tartı / Baskül'],
                'cash_drawer'      => ['icon'=>'fa-cash-register', 'label'=>'Para Çekmeceleri'],
                'customer_display' => ['icon'=>'fa-display',       'label'=>'Müşteri Ekranları'],
            ];
            @endphp

            @foreach($catalogTypes as $type => $typeMeta)
            @if(isset($drivers[$type]))
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3 bg-gray-50/70 border-b border-gray-100 flex items-center gap-2">
                    <i class="fa-solid {{ $typeMeta['icon'] }} text-brand-500 text-sm"></i>
                    <span class="text-sm font-semibold text-gray-700">{{ $typeMeta['label'] }}</span>
                    <span class="ml-auto text-xs text-gray-400">{{ $drivers[$type]->count() }} model</span>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-0 divide-y divide-gray-50 lg:divide-y-0">
                    @foreach($drivers[$type] as $i => $driver)
                    <div class="p-5 {{ $i%2===1 ? 'lg:border-l' : '' }} border-gray-50 hover:bg-gray-50/30 transition">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <span class="text-sm font-bold text-gray-800">{{ $driver->manufacturer }}</span>
                                    <span class="text-sm font-semibold text-gray-500">{{ $driver->model }}</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5 mb-2">
                                    {{-- Bağlantı tipleri --}}
                                    @foreach($driver->connections ?? [] as $conn)
                                    @php
                                    $connColors = ['usb'=>'bg-blue-50 text-blue-600','serial'=>'bg-amber-50 text-amber-700','ethernet'=>'bg-emerald-50 text-emerald-700','wifi'=>'bg-sky-50 text-sky-600','bluetooth'=>'bg-purple-50 text-purple-600','rj11_via_printer'=>'bg-gray-50 text-gray-500'];
                                    $connLabels = ['usb'=>'USB','serial'=>'RS-232','ethernet'=>'LAN','wifi'=>'Wi-Fi','bluetooth'=>'BT','rj11_via_printer'=>'RJ11'];
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold {{ $connColors[$conn] ?? 'bg-gray-50 text-gray-500' }}">
                                        {{ $connLabels[$conn] ?? $conn }}
                                    </span>
                                    @endforeach
                                    {{-- Protokol --}}
                                    @if($driver->protocol)
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-brand-50 text-brand-600">{{ $driver->protocol }}</span>
                                    @endif
                                </div>
                                {{-- Özellikler --}}
                                @if($driver->features)
                                <div class="flex flex-wrap gap-1 mb-2">
                                    @foreach($driver->features as $feat)
                                    <span class="text-[10px] text-gray-400 bg-gray-50 px-1.5 py-0.5 rounded">{{ $feat }}</span>
                                    @endforeach
                                </div>
                                @endif
                                {{-- Notlar --}}
                                @if($driver->notes)
                                <p class="text-xs text-gray-400">{{ $driver->notes }}</p>
                                @endif
                                {{-- Teknik Özellikler --}}
                                @if($driver->specs)
                                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-0.5">
                                    @foreach($driver->specs as $key => $val)
                                    <span class="text-[11px] text-gray-400">
                                        <span class="text-gray-600 font-medium">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                        {{ is_array($val) ? implode(', ', $val) : $val }}
                                    </span>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            {{-- Hızlı Ekle --}}
                            <button
                                @click="quickAdd({{ $driver->id }}, '{{ $driver->device_type }}', '{{ addslashes($driver->manufacturer) }}', '{{ addslashes($driver->model) }}', '{{ $driver->protocol }}', {{ json_encode($driver->connections ?? []) }})"
                                class="flex-shrink-0 px-3 py-1.5 bg-brand-50 text-brand-600 text-xs font-semibold rounded-lg hover:bg-brand-100 transition whitespace-nowrap">
                                <i class="fa-solid fa-plus mr-1"></i>Ekle
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>

    {{-- Cihaz Ekle / Düzenle Modal --}}
    <div x-show="showModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display:none;">
        <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="closeModal()"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-xl p-6 space-y-5 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900" x-text="editId ? '✏️ Cihaz Düzenle' : '➕ Yeni Cihaz Ekle'"></h2>
                <button @click="closeModal()" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>

            <div class="grid grid-cols-2 gap-4">
                {{-- Cihaz Adı --}}
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Cihaz Adı *</label>
                    <input x-model="form.name" type="text" placeholder="örn. Kasa Yazıcısı"
                           class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
                </div>

                {{-- Cihaz Tipi --}}
                <div x-show="!editId">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Cihaz Tipi *</label>
                    <select x-model="form.type" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                        <option value="">Seçin...</option>
                        <option value="printer">Fiş Yazıcı</option>
                        <option value="barcode_scanner">Barkod Okuyucu</option>
                        <option value="scale">Tartı / Baskül</option>
                        <option value="cash_drawer">Para Çekmecesi</option>
                        <option value="customer_display">Müşteri Ekranı</option>
                        <option value="other">Diğer</option>
                    </select>
                </div>

                {{-- Bağlantı Tipi --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Bağlantı *</label>
                    <select x-model="form.connection" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                        <option value="">Seçin...</option>
                        <option value="usb">USB</option>
                        <option value="serial">Seri Port (RS-232)</option>
                        <option value="ethernet">Ethernet (LAN)</option>
                        <option value="wifi">Wi-Fi</option>
                        <option value="bluetooth">Bluetooth</option>
                        <option value="rj11_via_printer">RJ11 (Yazıcı Üzerinden)</option>
                    </select>
                </div>

                {{-- Üretici --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Üretici</label>
                    <input x-model="form.manufacturer" type="text" placeholder="Epson, CAS, Zebra..."
                           class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                </div>

                {{-- Model --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Model</label>
                    <input x-model="form.model" type="text" placeholder="TM-T88VI, SW-1..."
                           class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                </div>

                {{-- Protokol --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Protokol</label>
                    <input x-model="form.protocol" type="text" placeholder="ESC/POS, CAS Protocol..."
                           class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                </div>

                {{-- Ethernet alanlar --}}
                <template x-if="form.connection === 'ethernet' || form.connection === 'wifi'">
                    <div class="col-span-2 grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">IP Adresi</label>
                            <input x-model="form.ip_address" type="text" placeholder="192.168.1.100"
                                   class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Port</label>
                            <input x-model="form.port" type="number" placeholder="9100"
                                   class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                        </div>
                    </div>
                </template>

                {{-- Serial/RS-232 alanlar --}}
                <template x-if="form.connection === 'serial'">
                    <div class="col-span-2 grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Seri Port</label>
                            <input x-model="form.serial_port" type="text" placeholder="/dev/ttyUSB0 veya COM3"
                                   class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Baud Rate</label>
                            <select x-model="form.baud_rate" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                                <option value="1200">1200</option>
                                <option value="2400">2400</option>
                                <option value="4800">4800</option>
                                <option value="9600" selected>9600 (varsayılan)</option>
                                <option value="19200">19200</option>
                                <option value="38400">38400</option>
                                <option value="57600">57600</option>
                                <option value="115200">115200</option>
                            </select>
                        </div>
                    </div>
                </template>

                {{-- USB alanlar --}}
                <template x-if="form.connection === 'usb'">
                    <div class="col-span-2 grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Vendor ID (isteğe bağlı)</label>
                            <input x-model="form.vendor_id" type="text" placeholder="04b8"
                                   class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Product ID (isteğe bağlı)</label>
                            <input x-model="form.product_id_usb" type="text" placeholder="0202"
                                   class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                        </div>
                    </div>
                </template>

                {{-- Checkboxlar --}}
                <div class="col-span-2 flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="form.is_default" class="rounded text-brand-500">
                        <span class="text-sm text-gray-600">Bu tipin varsayılan cihazı olarak işaretle</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="form.is_active" class="rounded text-brand-500">
                        <span class="text-sm text-gray-600">Aktif</span>
                    </label>
                </div>
            </div>

            {{-- Butonlar --}}
            <div class="flex gap-3 justify-end pt-2">
                <button @click="closeModal()" class="px-5 py-2.5 text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">İptal</button>
                <button @click="saveDevice()"
                        :disabled="!form.name || !form.connection || (!editId && !form.type) || saving"
                        class="px-6 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-brand-500 to-purple-600 rounded-xl hover:opacity-90 transition disabled:opacity-50">
                    <span x-show="!saving" x-text="editId ? 'Güncelle' : 'Ekle'"></span>
                    <span x-show="saving">Kaydediliyor...</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
// Tüm cihaz verilerini PHP'den aktar (düzenleme için)
const DEVICES_DATA = @json($devices->keyBy('id'));

function hardwareManager() {
    return {
        tab: 'devices',
        showModal: false,
        editId: null,
        saving: false,
        form: {
            name: '', type: '', connection: '', protocol: '',
            manufacturer: '', model: '',
            vendor_id: '', product_id_usb: '',
            ip_address: '', port: '',
            serial_port: '', baud_rate: 9600,
            is_default: false, is_active: true,
        },

        resetForm() {
            this.form = { name:'', type:'', connection:'', protocol:'',
                manufacturer:'', model:'', vendor_id:'', product_id_usb:'',
                ip_address:'', port:'', serial_port:'', baud_rate: 9600,
                is_default: false, is_active: true };
            this.editId = null;
        },

        openAdd() {
            this.resetForm();
            this.showModal = true;
        },

        openEdit(id) {
            const d = DEVICES_DATA[id];
            if (!d) return;
            this.editId = id;
            this.form = {
                name: d.name, type: d.type, connection: d.connection,
                protocol: d.protocol || '', manufacturer: d.manufacturer || '',
                model: d.model || '', vendor_id: d.vendor_id || '',
                product_id_usb: d.product_id_usb || '',
                ip_address: d.ip_address || '', port: d.port || '',
                serial_port: d.serial_port || '', baud_rate: d.baud_rate || 9600,
                is_default: d.is_default, is_active: d.is_active,
            };
            this.showModal = true;
        },

        quickAdd(driverId, type, manufacturer, model, protocol, connections) {
            this.resetForm();
            this.form.type = type;
            this.form.manufacturer = manufacturer;
            this.form.model = model;
            this.form.protocol = protocol || '';
            this.form.name = manufacturer + ' ' + model;
            if (connections.includes('usb')) this.form.connection = 'usb';
            else if (connections.includes('ethernet')) this.form.connection = 'ethernet';
            else if (connections.includes('serial')) this.form.connection = 'serial';
            else if (connections.length > 0) this.form.connection = connections[0];
            this.tab = 'devices';
            this.showModal = true;
        },

        closeModal() { this.showModal = false; this.resetForm(); },

        async saveDevice() {
            if (!this.form.name || !this.form.connection) return;
            if (!this.editId && !this.form.type) return;
            this.saving = true;
            try {
                const url = this.editId ? `/hardware/${this.editId}` : '/hardware';
                const method = this.editId ? 'PUT' : 'POST';
                const res = await posAjax(url, { method, body: JSON.stringify(this.form) });
                if (res.success) {
                    showToast(res.message || 'Kaydedildi', 'success');
                    this.closeModal();
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    showToast(res.message || 'Hata oluştu', 'error');
                }
            } catch (e) { showToast('Hata oluştu', 'error'); }
            finally { this.saving = false; }
        },

        async testDevice(id, e) {
            const btn = e.currentTarget;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            try {
                const res = await posAjax(`/hardware/${id}/test`, { method: 'POST', body: '{}' });
                if (res.success) {
                    showToast(res.message || 'Bağlantı başarılı ✅', 'success');
                } else {
                    showToast(res.message || 'Bağlantı başarısız', 'error');
                }
                setTimeout(() => window.location.reload(), 1000);
            } catch (e) { showToast('Test başarısız', 'error'); }
            finally { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-plug-circle-check"></i><span class="hidden sm:inline ml-1">Test</span>'; }
        },

        async deleteDevice(id) {
            if (!confirm('Bu cihazı silmek istediğinize emin misiniz?')) return;
            try {
                const res = await posAjax(`/hardware/${id}`, { method: 'DELETE' });
                if (res.success) {
                    showToast('Cihaz silindi', 'success');
                    setTimeout(() => window.location.reload(), 600);
                }
            } catch(e) { showToast('Hata oluştu', 'error'); }
        },
    };
}
</script>
@endpush

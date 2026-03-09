<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\HardwareDevice;
use App\Models\HardwareDriver;
use App\Services\PrinterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HardwareController extends Controller
{
    private function cihazErisimiVarMi(HardwareDevice $device): bool
    {
        return $device->tenant_id === (int) session('tenant_id')
            && $device->branch_id === (int) session('branch_id');
    }

    private function ozelIpMi(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    private function gecerliSeriPortMu(?string $port): bool
    {
        if (! $port) {
            return false;
        }

        return str_starts_with($port, '/dev/tty') || str_starts_with($port, '/dev/cu.');
    }

    // Desteklenen cihaz tipleri
    const DEVICE_TYPES = [
        'printer'          => ['icon' => 'fa-print',          'label' => 'Fiş Yazıcı',       'color' => 'brand'],
        'barcode_scanner'  => ['icon' => 'fa-barcode',        'label' => 'Barkod Okuyucu',    'color' => 'emerald'],
        'scale'            => ['icon' => 'fa-weight-scale',   'label' => 'Tartı / Baskül',    'color' => 'amber'],
        'cash_drawer'      => ['icon' => 'fa-cash-register',  'label' => 'Para Çekmecesi',    'color' => 'purple'],
        'customer_display' => ['icon' => 'fa-display',        'label' => 'Müşteri Ekranı',    'color' => 'blue'],
        'other'            => ['icon' => 'fa-microchip',      'label' => 'Diğer',             'color' => 'gray'],
    ];

    const CONNECTION_TYPES = [
        'usb'              => 'USB',
        'serial'           => 'Seri Port (RS-232)',
        'ethernet'         => 'Ethernet (LAN)',
        'wifi'             => 'Wi-Fi',
        'bluetooth'        => 'Bluetooth',
        'rj11_via_printer' => 'RJ11 (Yazıcı Üzerinden)',
    ];

    /**
     * GET /hardware
     */
    public function index(Request $request)
    {
        $tenantId = session('tenant_id');
        $branchId = session('branch_id');

        // Kayıtlı cihazlar
        $devices = HardwareDevice::where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->when($request->query('type'), fn($q, $t) => $q->where('type', $t))
            ->orderBy('type')
            ->orderByDesc('is_default')
            ->get();

        // Sürücü kataloğu
        $drivers = HardwareDriver::orderBy('device_type')
            ->orderBy('manufacturer')
            ->get()
            ->groupBy('device_type');

        $stats = [
            'total'      => $devices->count(),
            'active'     => $devices->where('is_active', true)->count(),
            'connected'  => $devices->where('status', 'connected')->count(),
            'printer'    => $devices->where('type', 'printer')->count(),
            'scanner'    => $devices->where('type', 'barcode_scanner')->count(),
            'scale'      => $devices->where('type', 'scale')->count(),
        ];

        return view('pos.hardware.index', compact('devices', 'drivers', 'stats'));
    }

    /**
     * POST /hardware
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'type'         => 'required|in:printer,barcode_scanner,scale,cash_drawer,customer_display,other',
            'connection'   => 'required|in:usb,serial,ethernet,wifi,bluetooth,rj11_via_printer',
            'protocol'     => 'nullable|string|max:50',
            'manufacturer' => 'nullable|string|max:100',
            'model'        => 'nullable|string|max:100',
            'vendor_id'    => 'nullable|string|max:20',
            'product_id_usb' => 'nullable|string|max:20',
            'ip_address'   => 'nullable|ip',
            'port'         => 'nullable|integer|min:1|max:65535',
            'serial_port'  => 'nullable|string|max:30',
            'baud_rate'    => 'nullable|integer',
            'is_default'   => 'boolean',
            'is_active'    => 'boolean',
        ]);

        $tenantId = session('tenant_id');

        // Aynı tipte is_default varsa öncekini kaldır
        if (!empty($validated['is_default'])) {
            HardwareDevice::where('tenant_id', $tenantId)
                ->where('branch_id', session('branch_id'))
                ->where('type', $validated['type'])
                ->update(['is_default' => false]);
        }

        $device = HardwareDevice::create(array_merge($validated, [
            'tenant_id'  => $tenantId,
            'branch_id'  => session('branch_id'),
            'status'     => 'disconnected',
        ]));

        return response()->json([
            'success' => true,
            'device'  => $device,
            'message' => 'Cihaz eklendi.',
        ]);
    }

    /**
     * PUT /hardware/{device}
     */
    public function update(Request $request, HardwareDevice $device): JsonResponse
    {
        if (! $this->cihazErisimiVarMi($device)) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'connection'   => 'required|in:usb,serial,ethernet,wifi,bluetooth,rj11_via_printer',
            'protocol'     => 'nullable|string|max:50',
            'manufacturer' => 'nullable|string|max:100',
            'model'        => 'nullable|string|max:100',
            'vendor_id'    => 'nullable|string|max:20',
            'product_id_usb' => 'nullable|string|max:20',
            'ip_address'   => 'nullable|ip',
            'port'         => 'nullable|integer|min:1|max:65535',
            'serial_port'  => 'nullable|string|max:30',
            'baud_rate'    => 'nullable|integer',
            'is_default'   => 'boolean',
            'is_active'    => 'boolean',
        ]);

        if (!empty($validated['is_default'])) {
            HardwareDevice::where('tenant_id', session('tenant_id'))
                ->where('branch_id', session('branch_id'))
                ->where('type', $device->type)
                ->where('id', '!=', $device->id)
                ->update(['is_default' => false]);
        }

        $device->update($validated);

        return response()->json(['success' => true, 'message' => 'Cihaz güncellendi.']);
    }

    /**
     * DELETE /hardware/{device}
     */
    public function destroy(HardwareDevice $device): JsonResponse
    {
        if (! $this->cihazErisimiVarMi($device)) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $device->delete();
        return response()->json(['success' => true]);
    }

    /**
     * POST /hardware/{device}/test
     * Cihaz bağlantı testi (basit ping / port check)
     */
    public function test(HardwareDevice $device): JsonResponse
    {
        if (! $this->cihazErisimiVarMi($device)) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $result = false;
        $message = '';

        try {
            if ($device->connection === 'ethernet' && $device->ip_address) {
                if (! $this->ozelIpMi($device->ip_address)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Sadece yerel ağ IP adresleri test edilebilir.',
                        'status' => 'disconnected',
                    ], 422);
                }

                $port = $device->port ?? 9100;
                $fp = @fsockopen($device->ip_address, $port, $errno, $errstr, 3);
                if ($fp) {
                    $result = true;
                    $message = "Bağlantı başarılı: {$device->ip_address}:{$port}";
                    fclose($fp);
                } else {
                    $message = "Bağlanamadı: {$errstr} ({$errno})";
                }
            } elseif ($device->connection === 'usb') {
                $message = 'USB cihazı takılı mı? USB cihazlar gerçek zamanlı test için tarayıcı üzerinden kontrol edilemez.';
                $result = true; // optimistik
            } elseif ($device->connection === 'serial') {
                $port = $device->serial_port ?? '/dev/ttyUSB0';
                if (! $this->gecerliSeriPortMu($port)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Geçersiz seri port yolu.',
                        'status' => 'disconnected',
                    ], 422);
                }
                $result = file_exists($port);
                $message = $result ? "Seri port mevcut: {$port}" : "Seri port bulunamadı: {$port}";
            } else {
                $message = 'Bu bağlantı tipi için otomatik test desteklenmiyor.';
                $result = null;
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        $device->update([
            'status'       => $result ? 'connected' : 'disconnected',
            'last_seen_at' => $result ? now() : $device->last_seen_at,
        ]);

        return response()->json([
            'success' => (bool) $result,
            'message' => $message,
            'status'  => $result ? 'connected' : 'disconnected',
        ]);
    }

    /**
     * GET /hardware/drivers  — Katalog JSON
     */
    public function drivers(Request $request): JsonResponse
    {
        $drivers = HardwareDriver::when(
            $request->query('type'),
            fn($q, $t) => $q->where('device_type', $t)
        )
        ->orderBy('manufacturer')
        ->get();

        return response()->json($drivers);
    }

    /**
     * POST /hardware/{device}/print — Belirli cihaza fiş yazdır
     */
    public function print(Request $request, HardwareDevice $device): JsonResponse
    {
        if (! $this->cihazErisimiVarMi($device)) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $data = $request->validate([
            'receipt_no'      => 'nullable|string',
            'date'            => 'nullable|string',
            'grand_total'     => 'required|numeric',
            'payment_method'  => 'nullable|string',
            'items'           => 'required|array',
            'items.*.product_name' => 'required|string',
            'items.*.quantity'     => 'required|numeric',
            'items.*.total'        => 'required|numeric',
            'receipt_header'  => 'nullable|string',
            'receipt_footer'  => 'nullable|string',
            'open_drawer'     => 'nullable|boolean',
        ]);

        $result = PrinterService::printReceipt($data, $device);
        return response()->json($result);
    }

    /**
     * POST /hardware/print-receipt — Varsayılan yazıcıya fiş yazdır
     */
    public function printReceipt(Request $request): JsonResponse
    {
        $data = $request->validate([
            'receipt_no'      => 'nullable|string',
            'date'            => 'nullable|string',
            'grand_total'     => 'required|numeric',
            'payment_method'  => 'nullable|string',
            'items'           => 'required|array',
            'items.*.product_name' => 'required|string',
            'items.*.quantity'     => 'required|numeric',
            'items.*.total'        => 'required|numeric',
            'receipt_header'  => 'nullable|string',
            'receipt_footer'  => 'nullable|string',
            'open_drawer'     => 'nullable|boolean',
        ]);

        $result = PrinterService::printReceipt($data);
        return response()->json($result);
    }

    /**
     * POST /hardware/print-kitchen — Mutfak fişi yazdır
     */
    public function printKitchen(Request $request): JsonResponse
    {
        $data = $request->validate([
            'receipt_no'   => 'nullable|string',
            'table_name'   => 'nullable|string',
            'note'         => 'nullable|string',
            'items'        => 'required|array',
            'items.*.product_name' => 'required|string',
            'items.*.quantity'     => 'required|numeric',
            'items.*.note'         => 'nullable|string',
        ]);

        $result = PrinterService::printKitchenTicket($data);
        return response()->json($result);
    }

    /**
     * POST /hardware/open-drawer — Para çekmecesini aç
     */
    public function openDrawer(): JsonResponse
    {
        $result = PrinterService::openCashDrawer();
        return response()->json($result);
    }
}

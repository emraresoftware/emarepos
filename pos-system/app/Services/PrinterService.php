<?php

namespace App\Services;

use App\Models\HardwareDevice;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\EscposImage;

class PrinterService
{
    /**
     * Varsayılan yazıcı cihazını bul
     */
    public static function getDefaultPrinter(?int $branchId = null): ?HardwareDevice
    {
        $branchId = $branchId ?: session('branch_id');

        return HardwareDevice::where('type', 'printer')
            ->where('is_active', true)
            ->where('branch_id', $branchId)
            ->orderByDesc('is_default')
            ->first();
    }

    /**
     * Mutfak yazıcısını bul
     */
    public static function getKitchenPrinter(?int $branchId = null): ?HardwareDevice
    {
        $branchId = $branchId ?: session('branch_id');

        return HardwareDevice::where('type', 'printer')
            ->where('is_active', true)
            ->where('branch_id', $branchId)
            ->where('name', 'like', '%mutfak%')
            ->first();
    }

    /**
     * Yazıcıya bağlan (ESC/POS)
     */
    public static function connect(HardwareDevice $device): ?Printer
    {
        try {
            $connector = match ($device->connection) {
                'ethernet', 'wifi' => new NetworkPrintConnector(
                    $device->ip_address,
                    (int) ($device->port ?: 9100)
                ),
                'usb' => new FilePrintConnector("/dev/usb/lp0"),
                'serial' => new FilePrintConnector(
                    $device->serial_port ?: '/dev/ttyS0'
                ),
                default => null,
            };

            if (!$connector) {
                return null;
            }

            return new Printer($connector);
        } catch (\Exception $e) {
            \Log::error('Yazıcı bağlantı hatası: ' . $e->getMessage(), [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'connection' => $device->connection,
            ]);
            return null;
        }
    }

    /**
     * Satış fişi yazdır (termal)
     */
    public static function printReceipt(array $data, ?HardwareDevice $device = null): array
    {
        $device = $device ?: static::getDefaultPrinter();

        if (!$device) {
            return ['success' => false, 'message' => 'Yazıcı bulunamadı. Donanım ayarlarından yazıcı ekleyin.'];
        }

        $printer = static::connect($device);
        if (!$printer) {
            return ['success' => false, 'message' => 'Yazıcıya bağlanılamadı: ' . $device->name];
        }

        try {
            // Başlık
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->setTextSize(2, 2);

            $header = $data['header'] ?? config('app.name', 'EMARE POS');
            $printer->text($header . "\n");

            $printer->setTextSize(1, 1);
            $printer->setEmphasis(false);

            // Alt başlık (receipt_header ayarı)
            if (!empty($data['receipt_header'])) {
                $printer->text($data['receipt_header'] . "\n");
            }

            $printer->text(($data['date'] ?? now()->format('d.m.Y H:i')) . "\n");
            $printer->text("Fiş: " . ($data['receipt_no'] ?? '-') . "\n");

            // Çizgi
            $printer->text(str_repeat('-', 32) . "\n");

            // Ürün satırları
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            foreach ($data['items'] ?? [] as $item) {
                $name = mb_substr($item['product_name'] ?? $item['name'] ?? '', 0, 18);
                $qty = $item['quantity'] ?? $item['qty'] ?? 1;
                $total = number_format($item['total'] ?? 0, 2, '.', '');

                $line = sprintf("%-18s %2sx %8s", $name, $qty, $total);
                $printer->text($line . "\n");
            }

            // Çizgi
            $printer->text(str_repeat('-', 32) . "\n");

            // Toplam
            $printer->setEmphasis(true);
            $printer->setJustification(Printer::JUSTIFY_RIGHT);
            $printer->text("TOPLAM: " . number_format($data['grand_total'] ?? 0, 2, '.', '') . " TL\n");
            $printer->setEmphasis(false);

            // Ödeme yöntemi
            $paymentLabels = [
                'cash' => 'Nakit',
                'card' => 'Kart',
                'credit' => 'Veresiye',
                'mixed' => 'Karışık',
                'transfer' => 'Havale/EFT',
            ];
            $printer->text("Ödeme: " . ($paymentLabels[$data['payment_method'] ?? ''] ?? $data['payment_method'] ?? '-') . "\n");

            // Çizgi
            $printer->text(str_repeat('-', 32) . "\n");

            // Alt yazı
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $footer = $data['receipt_footer'] ?? 'Teşekkür ederiz!';
            $printer->text($footer . "\n");

            // Kağıt kes
            $printer->feed(3);
            $printer->cut();

            // Para çekmecesi açma
            if ($data['open_drawer'] ?? false) {
                $printer->pulse();
            }

            $printer->close();

            // Cihaz last_seen_at güncelle
            $device->update(['last_seen_at' => now(), 'status' => 'connected']);

            return ['success' => true, 'message' => 'Fiş yazıcıdan çıktı: ' . $device->name];
        } catch (\Exception $e) {
            try { $printer->close(); } catch (\Exception $ex) {}

            \Log::error('Fiş yazdırma hatası: ' . $e->getMessage(), [
                'device_id' => $device->id,
            ]);

            $device->update(['status' => 'error']);

            return ['success' => false, 'message' => 'Yazdırma hatası: ' . $e->getMessage()];
        }
    }

    /**
     * Mutfak fişi yazdır
     */
    public static function printKitchenTicket(array $data, ?HardwareDevice $device = null): array
    {
        $device = $device ?: static::getKitchenPrinter();

        if (!$device) {
            // Mutfak yazıcısı yoksa varsayılan yazıcıyı dene
            $device = static::getDefaultPrinter();
        }

        if (!$device) {
            return ['success' => false, 'message' => 'Mutfak yazıcısı bulunamadı.'];
        }

        $printer = static::connect($device);
        if (!$printer) {
            return ['success' => false, 'message' => 'Mutfak yazıcısına bağlanılamadı.'];
        }

        try {
            // Büyük yazı — dikkat çekici
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->setTextSize(2, 2);
            $printer->text("** SİPARİŞ **\n");

            $printer->setTextSize(1, 1);
            $printer->text(($data['date'] ?? now()->format('H:i')) . "\n");

            if (!empty($data['table_name'])) {
                $printer->setTextSize(2, 1);
                $printer->text("Masa: " . $data['table_name'] . "\n");
            }

            if (!empty($data['receipt_no'])) {
                $printer->text("Fiş: " . $data['receipt_no'] . "\n");
            }

            $printer->setTextSize(1, 1);
            $printer->setEmphasis(false);
            $printer->text(str_repeat('=', 32) . "\n");

            // Ürünler — büyük font
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->setTextSize(2, 1);

            foreach ($data['items'] ?? [] as $item) {
                $name = $item['product_name'] ?? $item['name'] ?? '';
                $qty = $item['quantity'] ?? $item['qty'] ?? 1;
                $printer->text($qty . "x " . $name . "\n");

                // Ürün notu varsa
                if (!empty($item['note'])) {
                    $printer->setTextSize(1, 1);
                    $printer->text("  > " . $item['note'] . "\n");
                    $printer->setTextSize(2, 1);
                }
            }

            $printer->setTextSize(1, 1);
            $printer->text(str_repeat('=', 32) . "\n");

            if (!empty($data['note'])) {
                $printer->setEmphasis(true);
                $printer->text("NOT: " . $data['note'] . "\n");
                $printer->setEmphasis(false);
            }

            $printer->feed(3);
            $printer->cut();
            $printer->close();

            $device->update(['last_seen_at' => now(), 'status' => 'connected']);

            return ['success' => true, 'message' => 'Mutfak fişi yazdırıldı.'];
        } catch (\Exception $e) {
            try { $printer->close(); } catch (\Exception $ex) {}
            \Log::error('Mutfak fişi hatası: ' . $e->getMessage());

            return ['success' => false, 'message' => 'Mutfak fişi hatası: ' . $e->getMessage()];
        }
    }

    /**
     * X Raporu yazdır (ara rapor)
     */
    public static function printXReport(array $data, ?HardwareDevice $device = null): array
    {
        $device = $device ?: static::getDefaultPrinter();
        if (!$device) {
            return ['success' => false, 'message' => 'Yazıcı bulunamadı.'];
        }

        $printer = static::connect($device);
        if (!$printer) {
            return ['success' => false, 'message' => 'Yazıcıya bağlanılamadı.'];
        }

        try {
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->setTextSize(2, 2);
            $printer->text("X RAPORU\n");
            $printer->setTextSize(1, 1);
            $printer->text("(Ara Rapor)\n");
            $printer->text(now()->format('d.m.Y H:i') . "\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat('-', 32) . "\n");

            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $rows = [
                ['Açılış Saati', $data['opened_at'] ?? '-'],
                ['Açan', $data['opened_by'] ?? '-'],
                ['Açılış Bakiye', number_format($data['opening_amount'] ?? 0, 2) . ' TL'],
                ['Nakit Satış', number_format($data['cash_total'] ?? 0, 2) . ' TL'],
                ['Kart Satış', number_format($data['card_total'] ?? 0, 2) . ' TL'],
                ['Veresiye', number_format($data['credit_total'] ?? 0, 2) . ' TL'],
                ['Satış Adedi', (string) ($data['sale_count'] ?? 0)],
            ];

            foreach ($rows as [$label, $value]) {
                $printer->text(sprintf("%-18s %13s", $label, $value) . "\n");
            }

            $printer->text(str_repeat('-', 32) . "\n");
            $printer->setEmphasis(true);
            $totalSales = ($data['cash_total'] ?? 0) + ($data['card_total'] ?? 0) + ($data['credit_total'] ?? 0);
            $printer->text(sprintf("%-18s %13s", "Toplam Satış", number_format($totalSales, 2) . ' TL') . "\n");
            $expectedCash = ($data['opening_amount'] ?? 0) + ($data['cash_total'] ?? 0);
            $printer->text(sprintf("%-18s %13s", "Beklenen Nakit", number_format($expectedCash, 2) . ' TL') . "\n");
            $printer->setEmphasis(false);

            $printer->text(str_repeat('-', 32) . "\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("* Kasa kapatılmamıştır *\n");

            $printer->feed(3);
            $printer->cut();
            $printer->close();

            return ['success' => true, 'message' => 'X Raporu yazdırıldı.'];
        } catch (\Exception $e) {
            try { $printer->close(); } catch (\Exception $ex) {}
            return ['success' => false, 'message' => 'X Raporu hatası: ' . $e->getMessage()];
        }
    }

    /**
     * Para çekmecesini aç
     */
    public static function openCashDrawer(?HardwareDevice $device = null): array
    {
        // Çekmece tipi cihaz varsa onu, yoksa yazıcı üzerinden RJ11 ile aç
        $branchId = session('branch_id');

        $device = $device ?: HardwareDevice::where('type', 'cash_drawer')
            ->where('is_active', true)
            ->where('branch_id', $branchId)
            ->first();

        if (!$device) {
            // Yazıcı üzerinden aç (RJ11 bağlı çekmece)
            $device = static::getDefaultPrinter();
        }

        if (!$device) {
            return ['success' => false, 'message' => 'Çekmece/yazıcı bulunamadı.'];
        }

        $printer = static::connect($device);
        if (!$printer) {
            return ['success' => false, 'message' => 'Cihaza bağlanılamadı.'];
        }

        try {
            $printer->pulse(); // ESC p 0 komutu — çekmece aç
            $printer->close();
            return ['success' => true, 'message' => 'Para çekmecesi açıldı.'];
        } catch (\Exception $e) {
            try { $printer->close(); } catch (\Exception $ex) {}
            return ['success' => false, 'message' => 'Çekmece açılamadı: ' . $e->getMessage()];
        }
    }
}

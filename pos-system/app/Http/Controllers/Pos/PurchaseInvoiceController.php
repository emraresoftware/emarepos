<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Product;
use App\Models\Firm;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class PurchaseInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $branchId = session('branch_id');

        $invoices = PurchaseInvoice::with(['firm', 'items'])
            ->where('branch_id', $branchId)
            ->orderByDesc('invoice_date')
            ->paginate(20)
            ->withQueryString();

        $firms = Firm::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'barcode', 'purchase_price', 'sale_price']);

        return view('pos.purchase-invoice.index', compact('invoices', 'firms', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'firm_id' => ['required', 'integer', Rule::exists('firms', 'id')->where('tenant_id', session('tenant_id'))],
            'invoice_no' => 'nullable|string|max:100',
            'invoice_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('tenant_id', session('tenant_id'))],
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount' => 'nullable|numeric|min:0',
        ]);

        $branchId = session('branch_id');

        $subtotal = 0;
        $vatTotal = 0;
        $discountTotal = 0;

        foreach ($request->items as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $discount = $item['discount'] ?? 0;
            $lineTotal -= $discount;
            $vatRate = $item['vat_rate'] ?? 0;
            $vatAmount = $lineTotal * ($vatRate / 100);
            $subtotal += $lineTotal;
            $vatTotal += $vatAmount;
            $discountTotal += $discount;
        }

        $grandTotal = $subtotal + $vatTotal;

        $invoice = PurchaseInvoice::create([
            'tenant_id' => session('tenant_id'),
            'branch_id' => $branchId,
            'firm_id' => $request->firm_id,
            'invoice_no' => $request->invoice_no,
            'invoice_date' => $request->invoice_date,
            'subtotal' => $subtotal,
            'vat_total' => $vatTotal,
            'discount_total' => $discountTotal,
            'grand_total' => $grandTotal,
            'status' => 'received',
            'payment_status' => 'unpaid',
            'notes' => $request->notes,
            'user_id' => auth()->id(),
        ]);

        // Ürünleri önceden toplu yükle (N+1 önleme)
        $productIds = collect($request->items)->pluck('product_id')->unique();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($request->items as $item) {
            $product = $products[$item['product_id']] ?? null;
            if (!$product) continue;
            $qty = $item['quantity'];
            $unitPrice = $item['unit_price'];
            $discount = $item['discount'] ?? 0;
            $vatRate = $item['vat_rate'] ?? 0;
            $lineTotal = ($qty * $unitPrice) - $discount;
            $vatAmount = $lineTotal * ($vatRate / 100);

            $invoice->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'discount' => $discount,
                'total' => $lineTotal + $vatAmount,
            ]);

            // Stok güncelle
            $product->increment('stock_quantity', $qty);

            // Alış fiyatı güncelle
            if ($unitPrice > 0) {
                $product->update(['purchase_price' => $unitPrice]);
            }

            // Stok hareketi kaydet
            StockMovement::create([
                'tenant_id' => session('tenant_id'),
                'branch_id' => $branchId,
                'type' => 'purchase',
                'barcode' => $product->barcode,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'transaction_code' => 'PI-' . $invoice->id,
                'note' => 'Alış faturası: ' . ($request->invoice_no ?? $invoice->id),
                'quantity' => $qty,
                'remaining' => $product->fresh()->stock_quantity,
                'unit_price' => $unitPrice,
                'total' => $qty * $unitPrice,
                'movement_date' => Carbon::parse($request->invoice_date),
            ]);
        }

        // Cari bakiyeyi güncelle (Tedarikçi borç artan → decrement)
        $firm = Firm::find($request->firm_id);
        if ($firm) {
            $firm->decrement('balance', $grandTotal);
        }

        return response()->json(['success' => true, 'invoice' => $invoice->load('items', 'firm')]);
    }

    public function show(PurchaseInvoice $invoice)
    {
        if ($invoice->branch_id !== (int) session('branch_id')) {
            return response()->json(['error' => 'Yetkiniz yok.'], 403);
        }
        return response()->json($invoice->load('items', 'firm'));
    }

    public function update(Request $request, PurchaseInvoice $invoice)
    {
        if ($invoice->branch_id !== (int) session('branch_id')) {
            return response()->json(['error' => 'Yetkiniz yok.'], 403);
        }
        $request->validate([
            'invoice_no' => 'nullable|string|max:100',
            'invoice_date' => 'required|date',
            'notes' => 'nullable|string',
            'payment_status' => 'nullable|in:unpaid,partial,paid',
            'status' => 'nullable|in:draft,received,approved,returned,cancelled',
        ]);

        $oldStatus = $invoice->status;
        $newStatus = $request->input('status', $oldStatus);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($invoice, $request, $oldStatus, $newStatus) {
            // Status 'received' → 'cancelled'/'returned' geçişinde stok/bakiye geri al
            if ($oldStatus === 'received' && in_array($newStatus, ['cancelled', 'returned'])) {
                foreach ($invoice->items as $item) {
                    $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
                    if ($product) {
                        $product->decrement('stock_quantity', $item->quantity);
                    }
                }
                $firm = Firm::where('id', $invoice->firm_id)->lockForUpdate()->first();
                if ($firm) {
                    $firm->increment('balance', $invoice->grand_total);
                }
            }

            $invoice->update($request->only('invoice_no', 'invoice_date', 'notes', 'payment_status', 'status'));

            return response()->json(['success' => true, 'invoice' => $invoice->fresh()->load('items', 'firm')]);
        });
    }

    public function destroy(PurchaseInvoice $invoice)
    {
        if ($invoice->branch_id !== (int) session('branch_id')) {
            return response()->json(['error' => 'Yetkiniz yok.'], 403);
        }
        return \Illuminate\Support\Facades\DB::transaction(function () use ($invoice) {
            if ($invoice->status === 'received') {
                // Stokları geri al
                foreach ($invoice->items as $item) {
                    $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
                    if ($product) {
                        $product->decrement('stock_quantity', $item->quantity);
                    }
                }
                // Cari bakiyeyi geri al
                $firm = Firm::where('id', $invoice->firm_id)->lockForUpdate()->first();
                if ($firm) {
                    $firm->increment('balance', $invoice->grand_total);
                }
            }

            $invoice->delete();
            return response()->json(['success' => true, 'message' => 'Fatura silindi.']);
        });
    }
}

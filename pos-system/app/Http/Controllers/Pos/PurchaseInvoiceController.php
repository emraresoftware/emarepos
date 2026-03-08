<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Product;
use App\Models\Firm;
use App\Models\StockMovement;
use Illuminate\Http\Request;
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

        $firms = Firm::where('is_active', true)->orderBy('name')->get(['id', 'name', 'type']);
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'barcode', 'purchase_price', 'sale_price']);

        return view('pos.purchase-invoice.index', compact('invoices', 'firms', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'firm_id' => 'required|integer|exists:firms,id',
            'invoice_no' => 'nullable|string|max:100',
            'invoice_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
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

        // Cari bakiyeyi güncelle (Tedarikçi borç artan)
        $firm = Firm::find($request->firm_id);
        if ($firm) {
            $firm->increment('balance', $grandTotal);
        }

        return response()->json(['success' => true, 'invoice' => $invoice->load('items', 'firm')]);
    }

    public function show(PurchaseInvoice $invoice)
    {
        return response()->json($invoice->load('items', 'firm'));
    }

    public function update(Request $request, PurchaseInvoice $invoice)
    {
        $request->validate([
            'invoice_no' => 'nullable|string|max:100',
            'invoice_date' => 'required|date',
            'notes' => 'nullable|string',
            'payment_status' => 'nullable|in:unpaid,partial,paid',
            'status' => 'nullable|in:received,returned,cancelled',
        ]);

        $invoice->update($request->only('invoice_no', 'invoice_date', 'notes', 'payment_status', 'status'));

        return response()->json(['success' => true, 'invoice' => $invoice->fresh()->load('items', 'firm')]);
    }

    public function destroy(PurchaseInvoice $invoice)
    {
        if ($invoice->status === 'received') {
            // Stokları geri al
            foreach ($invoice->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->decrement('stock_quantity', $item->quantity);
                }
            }
            // Cari bakiyeyi geri al
            $firm = Firm::find($invoice->firm_id);
            if ($firm) {
                $firm->decrement('balance', $invoice->grand_total);
            }
        }

        $invoice->delete();
        return response()->json(['success' => true, 'message' => 'Fatura silindi.']);
    }
}

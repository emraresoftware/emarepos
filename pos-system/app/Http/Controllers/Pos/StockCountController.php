<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class StockCountController extends Controller
{
    public function index(Request $request)
    {
        $branchId = session('branch_id');

        $counts = StockCount::where('branch_id', $branchId)
            ->with('items')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $products = Product::where('is_active', true)
            ->with(['branches' => fn ($q) => $q->where('branch_id', $branchId)])
            ->orderBy('name')
            ->get(['id', 'name', 'barcode', 'stock_quantity']);

        $products->each(function ($product) use ($branchId) {
            $product->stock_quantity = $product->stockForBranch($branchId);
        });

        return view('pos.stock-count.index', compact('counts', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('tenant_id', session('tenant_id'))],
            'items.*.counted_quantity' => 'required|numeric|min:0',
            'items.*.note' => 'nullable|string',
        ]);

        $branchId = session('branch_id');

        return DB::transaction(function () use ($request, $branchId) {
            $prefix = 'SC-' . date('Ymd') . '-';
            $lastCount = StockCount::where('code', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByDesc('code')
                ->first();
            $nextNum = $lastCount ? ((int) substr($lastCount->code, -3)) + 1 : 1;
            $code = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

            $count = StockCount::create([
                'tenant_id' => session('tenant_id'),
                'branch_id' => $branchId,
                'code' => $code,
                'title' => $request->title ?: 'Sayım ' . date('d.m.Y'),
                'status' => 'draft',
                'user_id' => auth()->id(),
            ]);

        // Ürünleri tek sorguda yükle (N+1 önleme)
        $productIds = collect($request->items)->pluck('product_id')->filter()->unique()->toArray();
        $productMap = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($request->items as $item) {
            $product = $productMap[$item['product_id']] ?? null;
            $systemQty = $product?->stockForBranch($branchId) ?? 0;
            $countedQty = $item['counted_quantity'];

            $count->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'barcode' => $product->barcode,
                'system_quantity' => $systemQty,
                'counted_quantity' => $countedQty,
                'difference' => $countedQty - $systemQty,
                'note' => $item['note'] ?? null,
            ]);
        }

        return response()->json(['success' => true, 'count' => $count->load('items')]);
        }); // end DB::transaction
    }

    public function show(StockCount $stockCount)
    {
        if ($stockCount->branch_id !== (int) session('branch_id')) {
            return response()->json(['success' => false, 'message' => 'Bu sayıma erişim yetkiniz yok.'], 403);
        }

        return response()->json([
            'success' => true,
            'count' => $stockCount->load('items'),
        ]);
    }

    /**
     * Sayımı onayla ve stokları güncelle
     */
    public function apply(StockCount $stockCount)
    {
        if ($stockCount->branch_id !== (int) session('branch_id')) {
            return response()->json(['success' => false, 'message' => 'Bu sayıma erişim yetkiniz yok.'], 403);
        }

        return DB::transaction(function () use ($stockCount) {
            // Status kontrolü transaction içinde lockForUpdate ile (yarış koşulunu önler)
            $stockCount = StockCount::where('id', $stockCount->id)->lockForUpdate()->firstOrFail();

            if ($stockCount->status !== 'draft') {
                return response()->json(['success' => false, 'message' => 'Bu sayım zaten uygulanmış.'], 422);
            }
            foreach ($stockCount->items as $item) {
                $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
                if (!$product) continue;

                $diff = $item->difference;
                if ($diff == 0) continue;

                // Stoku güncelle
                $remaining = $product->setStockForBranch((int) session('branch_id'), (float) $item->counted_quantity);

                // Stok hareketi kaydet
                StockMovement::create([
                    'tenant_id' => $stockCount->tenant_id,
                    'branch_id' => session('branch_id'),
                    'type' => 'adjustment',
                    'barcode' => $product->barcode,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'transaction_code' => $stockCount->code,
                    'note' => 'Stok sayımı düzeltme: ' . ($item->note ?: $stockCount->title),
                    'quantity' => $diff,
                    'remaining' => $remaining,
                    'unit_price' => $product->purchase_price ?? 0,
                    'total' => abs($diff) * ($product->purchase_price ?? 0),
                    'movement_date' => Carbon::now(),
                ]);
            }

            $stockCount->update([
                'status' => 'applied',
                'applied_at' => Carbon::now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Sayım uygulandı, stoklar güncellendi.']);
        });
    }

    public function destroy(StockCount $stockCount)
    {
        if ($stockCount->branch_id !== (int) session('branch_id')) {
            return response()->json(['success' => false, 'message' => 'Bu sayıma erişim yetkiniz yok.'], 403);
        }

        if ($stockCount->status === 'applied') {
            return response()->json(['success' => false, 'message' => 'Uygulanmış sayım silinemez.'], 422);
        }

        $stockCount->items()->delete();
        $stockCount->delete();

        return response()->json(['success' => true]);
    }
}

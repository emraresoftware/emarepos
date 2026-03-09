<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Product;
use App\Models\Branch;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $branchId = session('branch_id');

        $transfers = StockTransfer::with(['fromBranch', 'toBranch', 'items'])
            ->where(function ($q) use ($branchId) {
                $q->where('from_branch_id', $branchId)
                  ->orWhere('to_branch_id', $branchId);
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'barcode', 'stock_quantity']);

        return view('pos.stock-transfer.index', compact('transfers', 'branches', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'to_branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('tenant_id', session('tenant_id'))],
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('tenant_id', session('tenant_id'))],
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $fromBranchId = session('branch_id');
        if ($request->to_branch_id == $fromBranchId) {
            return response()->json(['success' => false, 'message' => 'Aynı şubeye transfer yapılamaz.'], 422);
        }

        return DB::transaction(function () use ($request, $fromBranchId) {
            $prefix = 'ST-' . date('Ymd') . '-';
            $lastTransfer = StockTransfer::where('code', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByDesc('code')
                ->first();
            $nextNum = $lastTransfer ? ((int) substr($lastTransfer->code, -3)) + 1 : 1;
            $code = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        $transfer = StockTransfer::create([
            'tenant_id' => session('tenant_id'),
            'code' => $code,
            'from_branch_id' => $fromBranchId,
            'to_branch_id' => $request->to_branch_id,
            'status' => 'pending',
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            $transfer->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $item['quantity'],
                'note' => $item['note'] ?? null,
            ]);
        }

        return response()->json(['success' => true, 'transfer' => $transfer->load('items', 'fromBranch', 'toBranch')]);
        }); // end DB::transaction
    }

    /**
     * Transferi onayla — gönderen stoktan düş, alan stoğa ekle
     */
    public function show(StockTransfer $transfer)
    {
        return response()->json($transfer->load('items', 'fromBranch', 'toBranch'));
    }

    /**
     * Transferi onayla — gönderen stoktan düş, alan stoğa ekle
     */
    public function approve(StockTransfer $transfer)
    {
        if ($transfer->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Bu transfer zaten işlenmiş.'], 422);
        }

        return DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $item) {
            $product = Product::find($item->product_id);
            if (!$product) continue;

            // Gönderen şubenin pivot stoğunu düş (ana stok değişmez, sadece şubeler arası hareket)
            $senderPivot = $product->branches()->where('branch_id', $transfer->from_branch_id)->first();
            if ($senderPivot) {
                $product->branches()->updateExistingPivot($transfer->from_branch_id, [
                    'stock_quantity' => max(0, $senderPivot->pivot->stock_quantity - $item->quantity),
                ]);
            }

            StockMovement::create([
                'tenant_id' => $transfer->tenant_id,
                'branch_id' => $transfer->from_branch_id,
                'type' => 'transfer',
                'barcode' => $product->barcode,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'transaction_code' => $transfer->code,
                'note' => 'Şube transferi (çıkış): → ' . $transfer->toBranch->name,
                'quantity' => -$item->quantity,
                'remaining' => $senderPivot ? max(0, $senderPivot->pivot->stock_quantity - $item->quantity) : 0,
                'unit_price' => $product->purchase_price ?? 0,
                'total' => $item->quantity * ($product->purchase_price ?? 0),
                'movement_date' => Carbon::now(),
            ]);

            // Alan şubeye ekle (branch_product pivot eğer varsa update, yoksa create)
            $pivotData = $product->branches()->where('branch_id', $transfer->to_branch_id)->first();
            if ($pivotData) {
                $product->branches()->updateExistingPivot($transfer->to_branch_id, [
                    'stock_quantity' => $pivotData->pivot->stock_quantity + $item->quantity,
                ]);
            } else {
                $product->branches()->attach($transfer->to_branch_id, [
                    'stock_quantity' => $item->quantity,
                    'sale_price' => $product->sale_price,
                ]);
            }

            StockMovement::create([
                'tenant_id' => $transfer->tenant_id,
                'branch_id' => $transfer->to_branch_id,
                'type' => 'transfer',
                'barcode' => $product->barcode,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'transaction_code' => $transfer->code,
                'note' => 'Şube transferi (giriş): ← ' . $transfer->fromBranch->name,
                'quantity' => $item->quantity,
                'remaining' => ($pivotData ? $pivotData->pivot->stock_quantity + $item->quantity : $item->quantity),
                'unit_price' => $product->purchase_price ?? 0,
                'total' => $item->quantity * ($product->purchase_price ?? 0),
                'movement_date' => Carbon::now(),
            ]);
        }

            $transfer->update([
                'status' => 'completed',
                'approved_by' => auth()->id(),
                'approved_at' => Carbon::now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Transfer onaylandı, stoklar güncellendi.']);
        }); // end DB::transaction
    }

    public function reject(StockTransfer $transfer)
    {
        if ($transfer->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Bu transfer zaten işlenmiş.'], 422);
        }

        $transfer->update(['status' => 'rejected']);
        return response()->json(['success' => true, 'message' => 'Transfer reddedildi.']);
    }
}

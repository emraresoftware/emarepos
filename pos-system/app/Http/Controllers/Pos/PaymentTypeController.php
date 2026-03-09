<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PaymentType;
use Illuminate\Http\Request;

class PaymentTypeController extends Controller
{
    public function index()
    {
        $paymentTypes = PaymentType::orderBy('sort_order')->get();
        return response()->json($paymentTypes);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $data['tenant_id'] = session('tenant_id');
        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        if (empty($data['code'])) {
            $data['code'] = str()->slug($data['name'], '_');
        }

        $paymentType = PaymentType::create($data);

        return response()->json(['success' => true, 'paymentType' => $paymentType]);
    }

    public function update(Request $request, PaymentType $paymentType)
    {
        if ($paymentType->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $paymentType->update($data);

        return response()->json(['success' => true, 'paymentType' => $paymentType->fresh()]);
    }

    public function destroy(PaymentType $paymentType)
    {
        if ($paymentType->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $paymentType->delete();
        return response()->json(['success' => true]);
    }
}

<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Branch;
use App\Models\PaymentType;
use App\Models\TaxRate;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $tenant = Tenant::find(session('tenant_id'));
        $branch = Branch::find(session('branch_id'));
        $paymentTypes = PaymentType::orderBy('sort_order')->get();
        $taxRates = TaxRate::orderBy('rate')->get();

        $isCenter = (bool) ($branch?->settings['is_center'] ?? false);
        $canManagePaymentTypes = $isCenter && (auth()->user()->is_super_admin || auth()->user()->hasPermission('payment_types.manage'));

        return view('pos.settings.index', compact('tenant', 'branch', 'paymentTypes', 'taxRates', 'isCenter', 'canManagePaymentTypes'));
    }

    public function updateBranch(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'timezone' => 'nullable|string|max:100',
        ]);

        $branch = Branch::findOrFail(session('branch_id'));
        $settings = $branch->settings ?? [];
        if (!empty($data['timezone'])) {
            $settings['timezone'] = $data['timezone'];
        }

        unset($data['timezone']);
        $data['settings'] = $settings;
        $branch->update($data);

        return redirect()->route('pos.settings')->with('success', 'Şube bilgileri güncellendi.');
    }

    public function updateGeneral(Request $request)
    {
        $tenant = Tenant::findOrFail(session('tenant_id'));
        $meta = $tenant->meta ?? [];

        $meta['receipt_header'] = $request->input('receipt_header', '');
        $meta['receipt_footer'] = $request->input('receipt_footer', '');
        $meta['currency_symbol'] = $request->input('currency_symbol', '₺');
        $meta['tax_included'] = $request->boolean('tax_included');
        $meta['auto_print_receipt'] = $request->boolean('auto_print_receipt');
        $meta['kitchen_print'] = $request->boolean('kitchen_print');

        $tenant->update(['meta' => $meta]);

        return redirect()->route('pos.settings')->with('success', 'Genel ayarlar güncellendi.');
    }
}

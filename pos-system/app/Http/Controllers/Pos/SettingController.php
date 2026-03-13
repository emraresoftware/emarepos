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

        $request->validate([
            'service_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'receipt_business_title' => 'nullable|string|max:120',
            'receipt_paper_width' => 'nullable|in:58,80',
            'receipt_font_size' => 'nullable|integer|min:9|max:14',
        ]);

        $meta['receipt_header'] = $request->input('receipt_header', '');
        $meta['receipt_footer'] = $request->input('receipt_footer', '');
        $meta['receipt_business_title'] = $request->input('receipt_business_title', config('app.name', 'EMARE POS'));
        $meta['receipt_paper_width'] = (string) $request->input('receipt_paper_width', '80');
        $meta['receipt_font_size'] = (int) $request->input('receipt_font_size', 12);
        $meta['currency_symbol'] = $request->input('currency_symbol', '₺');
        $meta['tax_included'] = $request->boolean('tax_included');
        $meta['auto_print_receipt'] = $request->boolean('auto_print_receipt');
        $meta['kitchen_print'] = $request->boolean('kitchen_print');
        $meta['service_fee_percentage'] = round((float) $request->input('service_fee_percentage', 0), 2);
        $meta['receipt_show_datetime'] = $request->boolean('receipt_show_datetime');
        $meta['receipt_show_receipt_no'] = $request->boolean('receipt_show_receipt_no');
        $meta['receipt_show_customer_name'] = $request->boolean('receipt_show_customer_name');
        $meta['receipt_show_customer_balance'] = $request->boolean('receipt_show_customer_balance');
        $meta['receipt_show_staff_name'] = $request->boolean('receipt_show_staff_name');
        $meta['receipt_show_payment_breakdown'] = $request->boolean('receipt_show_payment_breakdown');
        $meta['receipt_show_tax_breakdown'] = $request->boolean('receipt_show_tax_breakdown');
        $meta['receipt_show_service_fee'] = $request->boolean('receipt_show_service_fee');
        $meta['receipt_show_notes'] = $request->boolean('receipt_show_notes');

        $tenant->update(['meta' => $meta]);

        return redirect()->route('pos.settings')->with('success', 'Genel ayarlar güncellendi.');
    }
}

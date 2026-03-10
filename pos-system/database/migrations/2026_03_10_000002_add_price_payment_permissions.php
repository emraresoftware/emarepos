<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            [
                'code' => 'products.price_edit',
                'name' => 'Fiyat Düzenleme',
                'module_code' => 'core_pos',
                'group' => 'Ürün',
            ],
            [
                'code' => 'payment_types.manage',
                'name' => 'Ödeme Türleri Yönetimi',
                'module_code' => 'core_pos',
                'group' => 'Yönetim',
            ],
        ];

        foreach ($permissions as $perm) {
            $exists = DB::table('permissions')->where('code', $perm['code'])->exists();
            if (!$exists) {
                $permId = DB::table('permissions')->insertGetId([
                    ...$perm,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $adminRole = DB::table('roles')->where('code', 'admin')->first();
                if ($adminRole) {
                    $already = DB::table('role_permissions')
                        ->where('role_id', $adminRole->id)
                        ->where('permission_id', $permId)
                        ->exists();
                    if (!$already) {
                        DB::table('role_permissions')->insert([
                            'role_id' => $adminRole->id,
                            'permission_id' => $permId,
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        $codes = ['products.price_edit', 'payment_types.manage'];
        $permIds = DB::table('permissions')->whereIn('code', $codes)->pluck('id');
        if ($permIds->isNotEmpty()) {
            DB::table('role_permissions')->whereIn('permission_id', $permIds)->delete();
            DB::table('permissions')->whereIn('code', $codes)->delete();
        }
    }
};

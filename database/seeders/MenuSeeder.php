<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            ['name' => 'Orders', 'route' => 'orders', 'url' => 'admin/order'],
            ['name' => 'Point Management', 'route' => 'points', 'url' => 'admin/point'],
            ['name' => 'Redeem Points', 'route' => 'redeem-points', 'url' => 'admin/point-customer'],
            ['name' => 'Gift Management', 'route' => 'gifts', 'url' => 'admin/gift'],
            ['name' => 'Member Level', 'route' => 'member-levels', 'url' => 'admin/member_level'],
            ['name' => 'Approval', 'route' => 'approvals', 'url' => 'admin/approval'],
        ];

        foreach ($menus as $menu) {
            $exists = Menu::where('route', $menu['route'])->exists();

            if ($exists) {
                $this->command->warn("Menu dengan route '{$menu['route']}' sudah ada, lewati.");
                continue;
            }

            Menu::create($menu);
            $this->command->info("Menu '{$menu['name']}' berhasil dibuat (route: {$menu['route']})");
        }
    }
}

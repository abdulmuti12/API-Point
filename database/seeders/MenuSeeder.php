<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            ['name' => 'Orders', 'route' => 'orders', 'url' => 'dashboard/order'],
            ['name' => 'Point Management', 'route' => 'points', 'url' => 'dashboard/point'],
            ['name' => 'Redeem Points', 'route' => 'redeem-points', 'url' => 'dashboard/point-customer'],
            ['name' => 'Gift Management', 'route' => 'gifts', 'url' => 'dashboard/gift'],
            ['name' => 'Member Level', 'route' => 'member-levels', 'url' => 'dashboard/member_level'],
            ['name' => 'Approval', 'route' => 'approvals', 'url' => 'dashboard/approval'],
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

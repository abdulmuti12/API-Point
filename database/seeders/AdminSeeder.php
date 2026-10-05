<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Menu;



class AdminSeeder extends Seeder
{

   public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $editorRole = Role::firstOrCreate(['name' => 'Editor']);

        $menusToEnsure = [
            ['name' => 'Dashboard', 'route' => 'Dashboard', 'url' => 'dashboard'],
            ['name' => 'Admin', 'route' => 'admins', 'url' => 'dashboard/admin'],
            ['name' => 'Settings', 'route' => 'accounts', 'url' => 'dashboard/account'],
            ['name' => 'Role Management', 'route' => 'roles', 'url' => 'dashboard/role'],
            ['name' => 'Category', 'route' => 'categories', 'url' => 'dashboard/category'],
            ['name' => 'Brand', 'route' => 'brands', 'url' => 'dashboard/brand'],
            ['name' => 'Customers', 'route' => 'customer', 'url' => 'dashboard/customers'],
            ['name' => 'Contact Us', 'route' => 'contact-us', 'url' => 'dashboard/contact-us'],
            ['name' => 'Logo Management', 'route' => 'logoes', 'url' => 'dashboard/logo'],
        ];

        foreach ($menusToEnsure as $menuData) {
            Menu::firstOrCreate(['route' => $menuData['route']], $menuData);
        }

        $dashboard = Menu::where('route', 'Dashboard')->first();
        $adminManagement = Menu::where('route', 'admins')->first();
        $settings = Menu::where('route', 'accounts')->first();
        $roleManagement = Menu::where('route', 'roles')->first();
        $categoryManagement = Menu::where('route', 'categories')->first();
        $brandManagement = Menu::where('route', 'brands')->first();
        $customerManagement = Menu::where('route', 'customer')->first();
        $contactUs = Menu::where('route', 'contact-us')->first();
        $logoManagement = Menu::where('route', 'logoes')->first();

        $adminRole->menus()->syncWithoutDetaching([
            $dashboard->id,
            $adminManagement->id,
            $settings->id,
            $roleManagement->id,
            $categoryManagement->id,
            $brandManagement->id,
            $customerManagement->id,
            $contactUs->id,
            $logoManagement->id,
        ]);

        $editorRole->menus()->syncWithoutDetaching([$dashboard->id]);

        $admin = Admin::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin Satu',
                'status' => 'Active',
                'password' => bcrypt('password123'),
            ]
        );

        $customer = Customer::firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'customer satu',
                'full_name' => 'customer satu',
                'status' => 'active',
                'phone_number' => '08123456789',
                'password' => bcrypt('password'),
            ]
        );

        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
    }
}

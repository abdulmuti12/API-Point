<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class DashboardController extends BaseController
{

    private $route = 'dashboard';
    Public $title="Dashboard";

    public function data()
    {
        // $access = Auth::guard(name: 'api')->user(); // atau 'admins' sesuai guard kamu
        // if (!$access || !$access->hasAccessToMenu($this->route)) {
        //     return $this->sendError('Akses ditolak', 'Tidak memiliki akses ke menu ini', 403);
        // }

        $totalCategories = Category::count();
        $totalBrands = Brand::count();
        $customer=Customer::count();
        $totalAdmins = Admin::count(); // Assuming you have a guard for admins
        $totalRole = Role::count(); // Assuming you have a guard for admins

        $data = [
            'total_categories' => $totalCategories,
            'total_brands' => $totalBrands,
            'total_customers' => $customer,
            'total_admins'=>$totalAdmins,
            'total_role'=>$totalRole,

        ];

        return $this->sendResponse($data, 'Dashboard data retrieved successfully');
    }
}

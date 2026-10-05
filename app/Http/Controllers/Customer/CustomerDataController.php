<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Mappers\Customer\CustomerFEDetailMapper;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Kodepos;
use App\Models\MasterCity;
use App\Models\MasterDistrict;
use App\Models\MasterPostalCode;
use App\Models\MasterProvince;
use App\Models\MasterSubdistrict;
use App\Models\RegDistrict;
use App\Models\RegProvince;
use App\Models\RegRegencie;
use App\Models\RegVillage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class CustomerDataController extends BaseController
{
    private $model;
    private $route = 'customer';
    public $title = "Customer Data";

    public function __construct(Customer $model)
    {
        $this->model = $model;
    }

    private function checkAdminAccess()
    {
        $admin = Auth::guard('api')->user();
        if (!$admin || !$admin->hasAccessToMenu($this->route)) {
            return $this->sendError('Forbidden', 'Anda tidak memiliki akses ke menu customer');
        }
        return true;
    }

    public function show($id)
    {
        $this->checkAdminAccess();

        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError('Not Found', 'Customer tidak ditemukan');
        }

        $detailMapper = new CustomerFEDetailMapper($data);
        $result = $detailMapper->data();

        return $this->sendResponse($result, 'Success Load Data');
    }

    public function getProvince()
    {
        $provinces=MasterProvince::all();

        return $this->sendResponse($provinces, 'Success Load Data');
    }
    public function getCity(Request $request)
    {
        $provinces=MasterCity::where('prov_id', request()->get('prov_id'))->get();

        return $this->sendResponse($provinces, 'Success Load Data');
    }
    public function getDistricts(Request $request)
    {
        // dd(request()->get('city_id'));
        $districts=MasterDistrict::where('city_id', request()->get('city_id'))->get();

        return $this->sendResponse($districts, 'Success Load Data');
    }
    public function getSubDistrict(Request $request)
    {
        $villages=MasterSubdistrict::where('dis_id', request()->get('dis_id'))->get();

        return $this->sendResponse($villages, 'Success Load Data');
    }
    
    public function getPostalCode(Request $request)
    {
        $postalcode=MasterPostalCode::where('prov_id', request()->get('prov_id'))
                                      ->where('city_id', request()->get('city_id'))
                                      ->where('dis_id', request()->get('dis_id'))
                                      ->where('subdis_id', request()->get('subdis_id'))->first();

        return $this->sendResponse($postalcode, 'Success Load Data');
    }
    public function createAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address_line' => 'required|string',
            'province_id' => 'required|exists:master_province,prov_id',
            'province_name' => 'required|string|max:255',
            'city_id' => 'required|exists:master_city,city_id',
            'city_name' => 'required|string|max:255',
            'district_id' => 'required|exists:master_district,dis_id',
            'district_name' => 'required|string|max:255',
            'subdistrict_id' => 'required|exists:master_subdistrict,subdis_id',
            'subdistrict_name' => 'required|string|max:255',
            'postal_code' => 'required|string|max:10',
            'is_default' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors());
        }

        $customer = Auth::guard('customer')->user();

        if ($request->is_default) {
            Address::where('user_id', $customer->id)->update(['is_default' => false]);
        }

        $address = Address::create([
            'user_id' => $customer->id,
            'recipient_name' => strip_tags($request->recipient_name),
            'phone' => preg_replace('/[^0-9+\-\s]/', '', $request->phone),
            'province_id' => $request->province_id,
            'province_name' => strip_tags($request->province_name),
            'city_id' => $request->city_id,
            'city_name' => strip_tags($request->city_name),
            'district_id' => $request->district_id,
            'district_name' => strip_tags($request->district_name),
            'subdistrict_id' => $request->subdistrict_id,
            'subdistrict_name' => strip_tags($request->subdistrict_name),
            'postal_code' => preg_replace('/[^0-9]/', '', $request->postal_code),
            'address_line' => strip_tags($request->address_line),
            'is_default' => $request->is_default ?? false,
        ]);

        return $this->sendResponse($address, 'Address created successfully');
    }

    public function getAddresses()
    {
        $customer = Auth::guard('customer')->user();

        $addresses = Address::where('user_id', $customer->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->sendResponse($addresses, 'Success Load Addresses');
    }

    public function deleteAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:addresses,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors());
        }

        $customer = Auth::guard('customer')->user();

        $address = Address::where('id', $request->id)
            ->where('user_id', $customer->id)
            ->first();

        if (!$address) {
            return $this->sendError('Not Found', 'Address not found or access denied');
        }

        $address->delete();

        return $this->sendResponse(null, 'Address deleted');
    }
}

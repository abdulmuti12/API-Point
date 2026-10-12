<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Mappers\Customer\CustomerFEDetailMapper;
use App\Models\Customer;
use App\Models\MasterCity;
use App\Models\MasterDistrict;
use App\Models\MasterPostalCode;
use App\Models\MasterProvince;
use App\Models\MasterSubdistrict;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Customer\CustomerDataService;


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
        $provinces = MasterProvince::all();

        return $this->sendResponse($provinces, 'Success Load Data');
    }

    public function getCity(Request $request)
    {
        $provinces = MasterCity::where('prov_id', request()->get('prov_id'))->get();

        return $this->sendResponse($provinces, 'Success Load Data');
    }

    public function getDistricts(Request $request)
    {
        $districts = MasterDistrict::where('city_id', request()->get('city_id'))->get();

        return $this->sendResponse($districts, 'Success Load Data');
    }

    public function getSubDistrict(Request $request)
    {
        $villages = MasterSubdistrict::where('dis_id', request()->get('dis_id'))->get();

        return $this->sendResponse($villages, 'Success Load Data');
    }

    public function getPostalCode(Request $request)
    {
        $postalcode = MasterPostalCode::where('prov_id', request()->get('prov_id'))
            ->where('city_id', request()->get('city_id'))
            ->where('dis_id', request()->get('dis_id'))
            ->where('subdis_id', request()->get('subdis_id'))->first();

        return $this->sendResponse($postalcode, 'Success Load Data');
    }

    public function createAddress(Request $request, CustomerDataService $service)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu');
        }

        $result = $service->createAddress($customer, $request->all());
        if (isset($result['error'])) {
            return $this->sendError($result['error'], $result['errors'] ?? $result['message']);
        }

        return $this->sendResponse($result['address'], 'Address created successfully');
    }

    public function getAddresses(CustomerDataService $service)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu');
        }

        return $this->sendResponse($service->getAddresses($customer), 'Success Load Addresses');
    }

    public function deleteAddress(Request $request, CustomerDataService $service)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu');
        }

        $result = $service->deleteAddress($customer, $request->all());
        if (isset($result['error'])) {
            return $this->sendError($result['error'], $result['errors'] ?? $result['message']);
        }

        return $this->sendResponse(null, 'Address deleted');
    }

    public function getAddressTexts(CustomerDataService $service)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu');
        }

        return $this->sendResponse($service->getAddressTexts($customer), 'Success Load Address Texts');
    }

    public function createAddressText(Request $request, CustomerDataService $service)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu');
        }

        $result = $service->createAddressText($customer, $request->all());
        if (isset($result['error'])) {
            return $this->sendError($result['error'], $result['errors']);
        }

        return $this->sendResponse($result['addressText'], 'Address text created successfully');
    }

    public function toggleAddressTextStatus(Request $request, CustomerDataService $service)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu');
        }

        $result = $service->toggleAddressTextStatus($customer, $request->all());
        if (isset($result['error'])) {
            return $this->sendError($result['error'], $result['errors'] ?? $result['message']);
        }

        return $this->sendResponse($result['addressText'], 'Address status updated');
    }

    public function updateAddressText(Request $request, CustomerDataService $service)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu');
        }

        $result = $service->updateAddressText($customer, $request->all());
        if (isset($result['error'])) {
            return $this->sendError($result['error'], $result['errors'] ?? $result['message']);
        }

        return $this->sendResponse($result['addressText'], 'Address updated successfully');
    }

    public function deleteAddressText(Request $request, CustomerDataService $service)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu');
        }

        $result = $service->deleteAddressText($customer, $request->all());
        if (isset($result['error'])) {
            return $this->sendError($result['error'], $result['errors'] ?? $result['message']);
        }

        return $this->sendResponse(null, 'Address deleted');
    }
}

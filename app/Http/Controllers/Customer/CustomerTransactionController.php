<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Services\Customer\TransactionService;


class CustomerTransactionController extends BaseController
{
    private $model;
    private $route = 'transactions';
    Public $title="Transaction";

    public function __construct(Transaction $model)
    {
        $this->model = $model;
    }

    public function index(Request $request, TransactionService $service)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu', 401);
        }

        return $this->sendResponse($service->indexForCustomer($customer), 'Data transaksi berhasil diambil');
    }

    public function store(Request $request, TransactionService $service)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu', 401);
        }

        $result = $service->storeForCustomer($customer, $request->all());
        if (isset($result['error'])) {
            return $this->sendError($result['error'], $result['errors']);
        }

        return $this->sendResponse($result['transaction'], 'Transaksi berhasil ditambahkan');
    }

    public function show(Request $request, $id, TransactionService $service)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu', 401);
        }

        $result = $service->showForCustomer($customer, (int) $id);
        if (!$result) {
            return $this->sendError('Not Found', 'Transaksi tidak ditemukan atau bukan milik Anda', 404);
        }

        return $this->sendResponse($result, 'Detail transaksi berhasil diambil');
    }

    public function update(Request $request, $id, TransactionService $service)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu', 401);
        }

        $result = $service->updateForCustomer($customer, (int) $id, $request->all());
        if (isset($result['error'])) {
            return $this->sendError($result['error'], $result['errors'] ?? $result['message']);
        }

        return $this->sendResponse($result['transaction'], 'Transaksi berhasil diperbarui');
    }

    public function destroy(Request $request, $id, TransactionService $service)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu', 401);
        }

        $result = $service->destroyForCustomer($customer, (int) $id);
        if (isset($result['error'])) {
            return $this->sendError($result['error'], $result['message']);
        }

        return $this->sendResponse(null, 'Transaksi berhasil dihapus');
    }
}

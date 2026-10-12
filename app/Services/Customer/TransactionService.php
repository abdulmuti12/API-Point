<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\Transaction;

class TransactionService
{
    /**
     * Ambil semua transaksi customer yang sedang login.
     * Return: collection Transaction milik customer.
     */
    public function indexForCustomer(Customer $customer)
    {
        return Transaction::where('order_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Detail transaksi berdasarkan ID.
     * Return: Transaction bila ada, null bila tidak.
     */
    public function showForCustomer(Customer $customer, int $id)
    {
        return Transaction::where('order_id', $customer->id)
            ->where('id', $id)
            ->first();
    }

    /**
     * Buat transaksi baru untuk customer.
     * Return:
     *  - ['transaction' => Transaction] bila sukses
     *  - ['error' => ..., 'errors' => ...] bila validasi gagal
     */
    public function storeForCustomer(Customer $customer, array $request): array
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request, [
            'order_id'      => 'required|integer|min:1',
            'total_payment' => 'required|integer|min:0',
            'status'        => 'required|in:pending,success,failed,cancelled',
            'note'          => 'nullable|string',
            'address_id'    => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return ['error' => 'Validation Error', 'errors' => $validator->errors()];
        }

        $transaction = Transaction::create([
            'transaction_code' => 'TRX-' . date('Ymd') . '-' . str_pad((string) Transaction::count() + 1, 4, '0', STR_PAD_LEFT),
            'order_id'         => $request['order_id'],
            'total_payment'    => (int) $request['total_payment'],
            'status'           => $request['status'],
            'note'             => $request['note'] ?? null,
            'address_id'       => (int) $request['address_id'],
        ]);

        return ['transaction' => $transaction];
    }

    /**
     * Update status/total transaksi milik customer.
     * Return:
     *  - ['transaction' => Transaction] bila sukses
     *  - ['error' => 'Not Found', 'message' => ...] bila transaksi bukan milik customer
     */
    public function updateForCustomer(Customer $customer, int $id, array $request): array
    {
        $transaction = Transaction::where('order_id', $customer->id)
            ->where('id', $id)
            ->first();

        if (!$transaction) {
            return ['error' => 'Not Found', 'message' => 'Transaction not found or access denied'];
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request, [
            'total_payment' => 'sometimes|integer|min:0',
            'status'        => 'sometimes|in:pending,success,failed,cancelled',
            'note'         => 'sometimes|nullable|string',
            'address_id'    => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return ['error' => 'Validation Error', 'errors' => $validator->errors()];
        }

        $transaction->update($validator->validated());

        return ['transaction' => $transaction->fresh()];
    }

    /**
     * Hapus transaksi milik customer.
     * Return:
     *  - ['deleted' => true] bila sukses
     *  - ['error' => 'Not Found', 'message' => ...] bila transaksi bukan milik customer
     */
    public function destroyForCustomer(Customer $customer, int $id): array
    {
        $transaction = Transaction::where('order_id', $customer->id)
            ->where('id', $id)
            ->first();

        if (!$transaction) {
            return ['error' => 'Not Found', 'message' => 'Transaction not found or access denied'];
        }

        $transaction->delete();

        return ['deleted' => true];
    }
}

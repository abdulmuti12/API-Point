<?php

namespace App\Services\Customer;

use App\Models\Address;
use App\Models\AddressText;
use App\Models\Customer;
use Illuminate\Support\Facades\Validator;

class CustomerDataService
{
    public function getAddresses(Customer $customer)
    {
        return Address::where('user_id', $customer->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function createAddress(Customer $customer, array $request)
    {
        $validator = Validator::make($request, [
            'recipient_name'   => 'required|string|max:255',
            'phone'            => 'required|string|max:20',
            'address_line'     => 'required|string',
            'province_id'      => 'required|exists:master_province,prov_id',
            'province_name'    => 'required|string|max:255',
            'city_id'          => 'required|exists:master_city,city_id',
            'city_name'        => 'required|string|max:255',
            'district_id'      => 'required|exists:master_district,dis_id',
            'district_name'    => 'required|string|max:255',
            'subdistrict_id'   => 'required|exists:master_subdistrict,subdis_id',
            'subdistrict_name' => 'required|string|max:255',
            'postal_code'      => 'required|string|max:10',
            'is_default'       => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return ['error' => 'Validation Error', 'errors' => $validator->errors()];
        }

        if (!empty($request['is_default'])) {
            Address::where('user_id', $customer->id)->update(['is_default' => false]);
        }

        $address = Address::create([
            'user_id'          => $customer->id,
            'recipient_name'   => strip_tags($request['recipient_name']),
            'phone'            => preg_replace('/[^0-9+\-\s]/', '', $request['phone']),
            'province_id'      => $request['province_id'],
            'province_name'    => strip_tags($request['province_name']),
            'city_id'          => $request['city_id'],
            'city_name'        => strip_tags($request['city_name']),
            'district_id'      => $request['district_id'],
            'district_name'    => strip_tags($request['district_name']),
            'subdistrict_id'   => $request['subdistrict_id'],
            'subdistrict_name' => strip_tags($request['subdistrict_name']),
            'postal_code'      => preg_replace('/[^0-9]/', '', $request['postal_code']),
            'address_line'     => strip_tags($request['address_line']),
            'is_default'       => $request['is_default'] ?? false,
        ]);

        return ['address' => $address];
    }

    public function deleteAddress(Customer $customer, array $request)
    {
        $validator = Validator::make($request, [
            'id' => 'required|exists:addresses,id',
        ]);

        if ($validator->fails()) {
            return ['error' => 'Validation Error', 'errors' => $validator->errors()];
        }

        $address = Address::where('id', $request['id'])
            ->where('user_id', $customer->id)
            ->first();

        if (!$address) {
            return ['error' => 'Not Found', 'message' => 'Address not found or access denied'];
        }

        $address->delete();

        return ['deleted' => true];
    }

    public function getAddressTexts(Customer $customer)
    {
        return AddressText::where('customer_id', $customer->id)
            ->orderBy('status', 'desc')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'address', 'note', 'status']);
    }

    public function createAddressText(Customer $customer, array $request)
    {
        $validator = Validator::make($request, [
            'address' => 'required|string',
            'note'    => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return ['error' => 'Validation Error', 'errors' => $validator->errors()];
        }

        $hasActive = AddressText::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->exists();

        $addressText = AddressText::create([
            'customer_id' => $customer->id,
            'address'     => strip_tags($request['address']),
            'note'        => $request['note'] ? strip_tags($request['note']) : null,
            'status'      => $hasActive ? 'inactive' : 'active',
        ]);

        return ['addressText' => $addressText];
    }

    public function toggleAddressTextStatus(Customer $customer, array $request)
    {
        $validator = Validator::make($request, [
            'id' => 'required|exists:address_text,id',
        ]);

        if ($validator->fails()) {
            return ['error' => 'Validation Error', 'errors' => $validator->errors()];
        }

        $addressText = AddressText::where('id', $request['id'])
            ->where('customer_id', $customer->id)
            ->first();

        if (!$addressText) {
            return ['error' => 'Not Found', 'message' => 'Address not found or access denied'];
        }

        if ($addressText->status === 'active') {
            $addressText->update(['status' => 'inactive']);
        } else {
            AddressText::where('customer_id', $customer->id)
                ->where('status', 'active')
                ->update(['status' => 'inactive']);
            $addressText->update(['status' => 'active']);
        }

        return ['addressText' => $addressText];
    }

    public function updateAddressText(Customer $customer, array $request)
    {
        $validator = Validator::make($request, [
            'id'      => 'required|exists:address_text,id',
            'address' => 'required|string',
            'note'    => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return ['error' => 'Validation Error', 'errors' => $validator->errors()];
        }

        $addressText = AddressText::where('id', $request['id'])
            ->where('customer_id', $customer->id)
            ->first();

        if (!$addressText) {
            return ['error' => 'Not Found', 'message' => 'Address not found or access denied'];
        }

        $addressText->update([
            'address' => strip_tags($request['address']),
            'note'    => !empty($request['note']) ? strip_tags($request['note']) : null,
        ]);

        return ['addressText' => $addressText];
    }

    public function deleteAddressText(Customer $customer, array $request)
    {
        $validator = Validator::make($request, [
            'id' => 'required|exists:address_text,id',
        ]);

        if ($validator->fails()) {
            return ['error' => 'Validation Error', 'errors' => $validator->errors()];
        }

        $addressText = AddressText::where('id', $request['id'])
            ->where('customer_id', $customer->id)
            ->first();

        if (!$addressText) {
            return ['error' => 'Not Found', 'message' => 'Address not found or access denied'];
        }

        $addressText->delete();

        return ['deleted' => true];
    }
}

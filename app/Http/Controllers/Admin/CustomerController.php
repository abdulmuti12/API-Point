<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\AddressText;
use App\Http\Resources\Admin\CustomerResource;
use App\Mappers\Admin\CustomerDetailMapper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\CustomerWelcomeMail;

class CustomerController extends BaseController
{
    private $model;
    private $route = 'customer';
    Public $title="Customer";

    public function __construct(Customer $model)
    {
        $this->model = $model;
    }
 public function index(Request $request)
    {
        $access = Auth::guard('api')->user();
        if (!$access || !$access->hasAccessToMenu($this->route)) {
                    return $this->sendError('Akses ditolak', 'Tidak memiliki akses ke menu ini');
                }

        $dataRequest=$request->all() ?? null;
        $page = $request->query('page') ?? 1;
        $raw = (bool) $request->query('raw') ?? false;
        $data = $this->model;

        if($dataRequest){

            if (isset($dataRequest['search'])) {
                $searchTerm = $dataRequest['search'];
                $data = $data->where(function ($query) use ($searchTerm) {
                    $query->where('name', 'like', '%' . $searchTerm . '%')
                          ->orWhere('full_name', 'like', '%' . $searchTerm . '%')
                          ->orWhere('email', 'like', '%' . $searchTerm . '%')
                          ->orWhere('phone_number', 'like', '%' . $searchTerm . '%');
                });
            }

            if (isset($dataRequest['name'])) {
                $data = $data->where(function ($query) use ($dataRequest) {
                    $query->where('name', 'like', '%' . $dataRequest['name'] . '%');
                });
            }

             if (isset($dataRequest['full_name'])) {
                $data = $data->where(function ($query) use ($dataRequest) {
                    $query->where('full_name', 'like', '%' . $dataRequest['full_name'] . '%');
                });
            }

            if (isset($dataRequest['email'])) {
                $data = $data->where(function ($query) use ($dataRequest) {
                    $query->where('email', 'like', '%' . $dataRequest['email'] . '%');
                });
            }

            if (isset($dataRequest['phone_number'])) {
                $data = $data->where(function ($query) use ($dataRequest) {
                    $query->where('phone_number', 'like', '%' . $dataRequest['phone_number'] . '%');
                });
            }
        }
        $collection = $data->with('addressTexts')->orderBy('id', 'desc')->paginate(10, ['*'], 'page', $page);
        $rawData = CustomerResource::collection($collection);

        if ($raw) {
            return $this->sendResponse($rawData, 'Data berhasil diambil');
        }

        $responses = $rawData->response()->getData(true);
        $links = $responses['links'];
        $meta = $responses['meta'];
        unset($responses['links'], $responses['meta']);

        $datas = array_merge($responses, $meta, [
            'first_page_url' => $links['first'],
            'last_page_url'  => $links['last'],
            'prev_page_url'  => $links['prev'],
            'next_page_url'  => $links['next'],
        ]);

        return $this->sendResponse($datas, 'Data berhasil diambil');
    }
    public function show($id)
    {
        $access = Auth::guard('api')->user();
        if (!$access || !$access->hasAccessToMenu($this->route)) {
            return $this->sendError('Akses ditolak', 'Tidak memiliki akses ke menu ini');
        }

        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }
        $detailMapper = new CustomerDetailMapper($data);
        $result = $detailMapper->data();

        return $this->sendResponse($result, 'Success Load Data');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:50',
            'full_name'    => 'required|string|max:50',
            'email'        => 'required|email|max:50',
            'phone_number' => 'required|string|max:15',
            'status'       => 'nullable|string|max:20',
            'verify'       => 'nullable|integer|min:0|max:1',
            'address'      => 'nullable|string',
            'note'         => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors()->first(), 422);
        }

        if (Customer::where('email', $request->email)->exists()) {
            return $this->sendError('Email sudah digunakan', $request->email, 409);
        }
        if (Customer::where('phone_number', $request->phone_number)->exists()) {
            return $this->sendError('Nomor telepon sudah digunakan', $request->phone_number, 409);
        }

        $generatedPassword = Str::random(10);

        try {
            DB::beginTransaction();

            $customer = Customer::create([
                'name'         => $request->name,
                'full_name'    => $request->full_name,
                'email'        => $request->email,
                'phone_number' => $request->phone_number,
                'password'     => Hash::make($generatedPassword),
                'status'       => $request->status ?? 'active',
                'verify'       => $request->verify ?? 0,
            ]);

            $addressText = null;
            if (!empty($request->address)) {
                $addressText = AddressText::create([
                    'customer_id' => $customer->id,
                    'address'     => $request->address,
                    'note'        => $request->note,
                ]);
            }

            DB::commit();

            // Kirim email selamat datang
            try {
                Mail::to($customer->email)->send(
                    new CustomerWelcomeMail(
                        $customer->full_name,
                        $customer->email,
                        $generatedPassword
                    )
                );
            } catch (\Throwable $e) {
                // Jika gagal kirim email, tidak cancel customer creation
                \Log::error('Failed to send welcome email to customer: '.$customer->email, [
                    'error' => 'Terjadi kesalahan pada server',
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Gagal membuat customer', 'Terjadi kesalahan pada server', 500);
        }

        $customer->load('addressTexts');

        return $this->sendResponse([
            'customer'         => new CustomerResource($customer),
            'generated_password' => $generatedPassword,
            'address_text'     => $addressText,
        ], 'Customer berhasil ditambahkan', 201);
    }
}


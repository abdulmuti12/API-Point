<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Gift;
use App\Http\Resources\Admin\GiftResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Services\FileUploadService;

class GiftController extends BaseController
{
    private $model;
    private $route = 'gifts';
    public $title = "Gift";

    public function __construct(Gift $model)
    {
        $this->model = $model;
    }

    public function index(Request $request)
    {
        $dataRequest = $request->all() ?? null;
        $page = $request->query('page') ?? 1;
        $raw = (bool) $request->query('raw') ?? false;
        $data = $this->model;

        if ($dataRequest) {
            if (isset($dataRequest['name'])) {
                $data = $data->where(function ($query) use ($dataRequest) {
                    $query->where('name', 'like', '%' . $dataRequest['name'] . '%');
                });
            }

            if (isset($dataRequest['status'])) {
                $data = $data->where('status', $dataRequest['status']);
            }
        }

        $collection = $data->orderBy('id', 'desc')->paginate(10, ['*'], 'page', $page);
        $rawData = GiftResource::collection($collection);
        if ($raw) {
            $datas = $rawData;
        } else {
            $responses = $rawData->response()->getData(true);
            $links = $responses['links'];
            unset($responses['links']);
            $meta = $responses['meta'];
            unset($responses['meta']);
            $datas = [
                'data' => $responses['data'],
                'meta' => $meta,
                'links' => $links,
            ];
        }

        return $this->sendResponse($datas, 'Data berhasil diambil');
    }

    public function store(Request $request)
    {
        $checkProcess = $this->model::where('name', $request->name)->first();

        if ($checkProcess) {
            return $this->sendError('Gagal', 'Gift sudah ada');
        }

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:50',
            'total_point' => 'required|integer',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:15360',
            'description' => 'nullable|string',
            'status'      => 'nullable|string|max:60',
        ], [
            'image.max' => 'Ukuran file gambar tidak boleh lebih dari 15MB.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors());
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $uploadService = new FileUploadService();
            try {
                $imagePath = $uploadService->validateAndStore($request, 'image', 'gifts');
            } catch (\InvalidArgumentException $e) {
                return $this->sendError('Upload failed', 'Terjadi kesalahan pada server');
            }
        }

        $data = $this->model::create([
            'name'        => $request->name,
            'total_point' => $request->total_point,
            'image'       => $imagePath,
            'description' => $request->description,
            'status'      => $request->status ?? 'active',
        ]);

        return $this->sendResponse($data, 'Tambah Gift berhasil');
    }

    public function show($id)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError($this->title . ' not found', 'Data not found');
        }

        return $this->sendResponse(new GiftResource($data), 'Success Load Data');
    }

    public function edit($id)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }

        return $this->sendResponse(new GiftResource($data), 'Data berhasil diambil');
    }

    public function update(Request $request, $id)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }

        $checkProcess = $this->model::where('name', $request->name)->where('id', '!=', $id)->first();

        if ($checkProcess) {
            return $this->sendError('Gagal', $this->title . ' sudah ada');
        }

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:50',
            'total_point' => 'required|integer',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:15360',
            'description' => 'nullable|string',
            'status'      => 'nullable|string|max:60',
        ], [
            'image.max' => 'Ukuran file gambar tidak boleh lebih dari 15MB.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors());
        }

        if ($request->hasFile('image')) {
            if (!empty($data->image) && Storage::disk('public')->exists($data->image)) {
                Storage::disk('public')->delete($data->image);
            }
            $uploadService = new FileUploadService();
            try {
                $imagePath = $uploadService->validateAndStore($request, 'image', 'gifts');
            } catch (\InvalidArgumentException $e) {
                return $this->sendError('Upload failed', 'Terjadi kesalahan pada server');
            }
            if (!$imagePath) {
                return $this->sendError('Gagal upload gambar', 'Terjadi kesalahan saat upload file');
            }
        } else {
            $imagePath = $data->image;
        }

        $data->update([
            'name'        => $request->name,
            'total_point' => $request->total_point,
            'image'       => $imagePath,
            'description' => $request->description,
            'status'      => $request->status ?? 'active',
        ]);

        return $this->sendResponse($data, 'Update Gift berhasil');
    }

    public function destroy($id)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }

        if (!empty($data->image) && Storage::disk('public')->exists($data->image)) {
            Storage::disk('public')->delete($data->image);
        }

        $data->delete();

        return $this->sendResponse(null, 'Hapus Gift berhasil');
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MemberLevel;
use App\Http\Resources\Admin\MemberLevelResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Services\FileUploadService;

class MemberLevelController extends BaseController
{
    private $model;
    private $route = 'member_levels';
    public $title = "Member Level";

    public function __construct(MemberLevel $model)
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
        $rawData = MemberLevelResource::collection($collection);
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
            return $this->sendError('Gagal', 'Member Level sudah ada');
        }

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:100',
            'min_point'   => 'nullable|integer',
            'max_point'   => 'nullable|integer',
            'file'        => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'description' => 'nullable|string',
            'status'      => 'nullable|string|max:60',
        ], [
            'file.max' => 'Ukuran file gambar tidak boleh lebih dari 5MB.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors());
        }

        $filePath = null;
        if ($request->hasFile('file')) {
            $uploadService = new FileUploadService();
            try {
                $filePath = $uploadService->validateAndStore($request, 'file', 'member_levels');
            } catch (\InvalidArgumentException $e) {
                return $this->sendError('Upload failed', 'Terjadi kesalahan pada server');
            }
        }

        $data = $this->model::create([
            'name'        => $request->name,
            'min_point'   => $request->min_point,
            'max_point'   => $request->max_point,
            'file'        => $filePath,
            'description' => $request->description,
            'status'      => $request->status ?? 'active',
        ]);

        return $this->sendResponse($data, 'Tambah Member Level berhasil');
    }

    public function show($id)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError($this->title . ' not found', 'Data not found');
        }

        return $this->sendResponse(new MemberLevelResource($data), 'Success Load Data');
    }

    public function edit($id)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }

        return $this->sendResponse(new MemberLevelResource($data), 'Data berhasil diambil');
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
            'name'        => 'required|string|max:100',
            'min_point'   => 'nullable|integer',
            'max_point'   => 'nullable|integer',
            'file'        => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'description' => 'nullable|string',
            'status'      => 'nullable|string|max:60',
        ], [
            'file.max' => 'Ukuran file gambar tidak boleh lebih dari 5MB.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors());
        }

        if ($request->hasFile('file')) {
            if (!empty($data->file) && Storage::disk('public')->exists($data->file)) {
                Storage::disk('public')->delete($data->file);
            }
            $uploadService = new FileUploadService();
            try {
                $filePath = $uploadService->validateAndStore($request, 'file', 'member_levels');
            } catch (\InvalidArgumentException $e) {
                return $this->sendError('Upload failed', 'Terjadi kesalahan pada server');
            }
            if (!$filePath) {
                return $this->sendError('Gagal upload file', 'Terjadi kesalahan saat upload file');
            }
        } else {
            $filePath = $data->file;
        }

        $data->update([
            'name'        => $request->name,
            'min_point'   => $request->min_point,
            'max_point'   => $request->max_point,
            'file'        => $filePath,
            'description' => $request->description,
            'status'      => $request->status ?? 'active',
        ]);

        return $this->sendResponse($data, 'Update Member Level berhasil');
    }

    public function destroy($id)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }

        if (!empty($data->file) && Storage::disk('public')->exists($data->file)) {
            Storage::disk('public')->delete($data->file);
        }

        $data->delete();

        return $this->sendResponse(null, 'Hapus Member Level berhasil');
    }
}

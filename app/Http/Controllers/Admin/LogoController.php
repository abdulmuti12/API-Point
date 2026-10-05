<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Logo;
use App\Http\Resources\Admin\LogoResource;
use App\Mappers\Admin\LogoDetailMapper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Services\FileUploadService;


class LogoController extends BaseController
{
    private $model;
    private $route = 'logos';
    Public $title = "Logo";

    public function __construct(Logo $model)
    {
        $this->model = $model;
    }

    public function index(Request $request)
    {
        $dataRequest = $request->all() ?? null;
        $page        = $request->query('page') ?? 1;
        $raw         = (bool) $request->query('raw') ?? false;
        $data        = $this->model;

        if ($dataRequest) {
            if (isset($dataRequest['description'])) {
                $data = $data->where(function ($query) use ($dataRequest) {
                    $query->where('description', 'like', '%' . $dataRequest['description'] . '%');
                });
            }
        }

        $collection = $data->orderBy('id', 'desc')->paginate(10, ['*'], 'page', $page);
        $rawData    = LogoResource::collection($collection);

        if ($raw) {
            $datas = $rawData;
        } else {
            $responses = $rawData->response()->getData(true);
            $links     = $responses['links'];
            $dataLinks = [];
            unset($responses['links']);
            $meta = $responses['meta'];
            unset($responses['meta']);
            $datas = [
                'data'  => $responses,
                'meta'  => $meta,
                'links' => $links,
            ];
        }

        return $this->sendResponse($datas, 'Data berhasil diambil');
    }

   public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'description' => 'nullable|string',
        'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:15360',
        'is_active'   => 'nullable|in:0,1,true,false',
    ], [
        'image.max' => 'Ukuran file gambar tidak boleh lebih dari 15MB.',
    ]);

    if ($validator->fails()) {
        return $this->sendError('Gagal', $validator->errors());
    }

    // 🔥 Nonaktifkan semua logo lama
    $this->model::where('is_active', true)->update(['is_active' => false]);

    $data = new Logo();
    $data->description = $request->description;
    $data->is_active   = true;

    if ($request->hasFile('image')) {
        $uploadService = new FileUploadService();
        try {
            $data->image = $uploadService->validateAndStore($request, 'image', 'logos');
        } catch (\InvalidArgumentException $e) {
            return $this->sendError('Upload failed', 'Terjadi kesalahan pada server');
        }
    }

    $data->save();

    return $this->sendResponse(new LogoResource($data), 'Data berhasil ditambahkan');
}
    public function show($id)
    {
        $data = $this->model::find($id);
        if (!$data) {
            return $this->sendError($this->title . ' not found', 'Data not found');
        }
        $detailMapper = new LogoDetailMapper($data);
        $result       = $detailMapper->data();

        return $this->sendResponse($data, 'Success Load Data');
    }

    public function edit($id)
    {
        $data = $this->model::find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }

        return $this->sendResponse(new LogoResource($data), 'Data berhasil diambil');
    }

    public function update(Request $request, $id)
    {
        $data = $this->model::find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:15360',
        ], [
            'image.max' => 'Ukuran file gambar tidak boleh lebih dari 15MB.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Gagal', $validator->errors());
        }

        $this->model::where('id', '!=', $id)->update(['is_active' => false]);

        $data->is_active = true;

        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($data->image) {
                Storage::disk('public')->delete($data->image);
            }
            $uploadService = new FileUploadService();
            try {
                $data->image = $uploadService->validateAndStore($request, 'image', 'logos');
            } catch (\InvalidArgumentException $e) {
                return $this->sendError('Upload failed', 'Terjadi kesalahan pada server');
            }
        }

        $data->save();

        return $this->sendResponse(new LogoResource($data), 'Data berhasil diperbarui');
    }

    /**
     * Toggle aktif/nonaktif logo.
     * Hanya 1 logo yang boleh aktif. Jika logo yang dituju sudah aktif, ia akan dinonaktifkan.
     * Jika diaktifkan, semua logo lain otomatis dinonaktifkan.
     */
   public function setActive($id)
   {
    $data = $this->model::find($id);

    // ✅ Tambah ini
    if (!$data) {
        return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
    }

    if ($data->is_active) {
        $data->is_active = false;
        $data->save();
        return $this->sendResponse(new LogoResource($data), 'Logo berhasil dinonaktifkan');
    } else {
        $this->model::where('id', '!=', $id)->update(['is_active' => false]);
        $data->is_active = true;
        $data->save();
        return $this->sendResponse(new LogoResource($data), 'Logo berhasil diaktifkan');
    }
   }
    public function activeLogo()
    {
    $data = Logo::getActiveLogo(); // ✅ lebih ringkas
    if (!$data) {
        return $this->sendError('Tidak ada logo aktif', 'Data not found', 404);
    }
    return $this->sendResponse(new LogoResource($data), 'Logo aktif berhasil diambil');
    }
    public function destroy($id)
    {
        $data = $this->model::find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }

        if ($data->image) {
            Storage::disk('public')->delete($data->image);
        }

        $data->delete();

        return $this->sendResponse(null, 'Hapus ' . $this->title . ' berhasil');
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Brand;
use App\Http\Resources\Admin\BrandResource;
use App\Mappers\Admin\BrandDetailMapper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Services\FileUploadService;


class BrandController extends BaseController
{
    private $model;
    private $route = 'brands';
    Public $title="Brand";

    public function __construct(Brand $model)
    {
        $this->model = $model;
    }

    public function index(Request $request)
    {
        // $access = Auth::guard(name: 'api')->user(); // atau 'admins' sesuai guard kamu
        // if (!$access || !$access->hasAccessToMenu($this->route)) {
        //     return $this->sendError('Akses ditolak', 'Tidak memiliki akses ke menu ini', 403);
        // }

        $dataRequest=$request->all() ?? null;
        $page = $request->query('page') ?? 1;
        $raw = (bool) $request->query('raw') ?? false;
        $data = $this->model;

        if($dataRequest){

            if (isset($dataRequest['name'])) {
                $data = $data->where(function ($query) use ($dataRequest) {
                    $query->where('name', 'like', '%' . $dataRequest['name'] . '%');
                });
            }
        }
        $collection = $data->orderBy('id', 'desc')->paginate(10, ['*'], 'page', $page);
        $rawData = BrandResource::collection($collection);
        if ($raw) {
            $datas = $rawData;
        } else {
            $responses = $rawData->response()->getData(true);
            $links = $responses['links'];
            $dataLinks = [];
            unset($responses['links']);
            $meta = $responses['meta'];
            unset($responses['meta']);
            $datas = [
                'data' => $responses,
                'meta' => $meta,
                'links' => $links,
            ];
        }

        return $this->sendResponse($datas, 'Data berhasil diambil');
    }
    public function store(Request $request)
    {
        $checkProcess=$this->model::where('name', $request->name)->first();
       
        // dd($checkProcess);
        if ($checkProcess) {
            return $this->sendError('Gagal', 'Brand sudah ada');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:15360',
            'note' => 'nullable|string',
            // 'country_of_origin' => 'nullable|string',
            'link' => 'nullable|string',
        ],[
            'image.max' => 'Ukuran file gambar tidak boleh lebih dari 15MB.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Gagal', $validator->errors());
        }

        $data = new Brand();
        $data->name = $request->name;
        $data->description = $request->description;
        $data->note = $request->note;
        $uploadService = new FileUploadService();
        if ($request->hasFile('image')) {
            try {
                $imagePath = $uploadService->validateAndStore($request, 'image', 'brands');
            } catch (\InvalidArgumentException $e) {
                return $this->sendError('Upload failed', 'Terjadi kesalahan pada server');
            }
        } else {
            $imagePath = null;
        }
        $data->image = $imagePath;
        $data->link = $request->link;

        $data->save();

        return $this->sendResponse($data, 'Data berhasil ditambahkan');
    }

    public function show($id)
    {

        // $access = Auth::guard(name: 'api')->user();
        // if (!$access || !$access->hasAccessToMenu($this->route)) {
        //     return $this->sendError('Akses ditolak', 'Tidak memiliki akses ke menu ini', 403);
        // }

        $data = $this->model::find($id);
        if (!$data) {
            return $this->sendError($this->title . 'not found', 'Data not found');
        }
        $detailMapper = new BrandDetailMapper($data);
        $result = $detailMapper->data();

        return $this->sendResponse($result, 'Success Load Data');


        return $this->sendResponse($data, 'Success Load Data');
    }
    public function edit($id)
    {
        $data = $this->model::find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }

        return $this->sendResponse(new BrandResource($data), 'Data berhasil diambil');
    }
    public function update(Request $request, $id)
    {
        $data = $this->model::find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }

        $checkProcess=$this->model::where('name', $request->name)->where('id', '!=', $id)->first();
       
        if ($checkProcess) {
            return $this->sendError('Gagal', $this->title.' sudah ada');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:15360',
            'note' => 'nullable|string',
            'link' => 'nullable|string',
        ],[
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
                $imagePath = $uploadService->validateAndStore($request, 'image', 'brands');
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
            'name' => $request->name,
            'description' => $request->description,
            'note' => $request->note,
            'image' => $imagePath,
            'link' => $request->link,
        ]);

        return $this->sendResponse($data, 'Update Kategori berhasil');
    }
    public function destroy($id)
    {
        $data = $this->model::find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }

        Storage::disk('public')->delete($data->image);
        $data->delete();

        return $this->sendResponse(null, 'Hapus '.$this->title.' berhasil');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Promotion;
use App\Http\Resources\Admin\PromotionResource;
use App\Mappers\Admin\PromotionDetailMapper;
use App\Models\Brand;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Services\FileUploadService;

class PromotionController extends BaseController
{
    private $model;
    private $route = 'promotions';
    Public $title="Promotion";

    public function __construct(Promotion $model)
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
        $data = $this->model->with('brand');

        if($dataRequest){

            if (isset($dataRequest['name'])) {
                $data = $data->where(function ($query) use ($dataRequest) {
                    $query->where('name', 'like', '%' . $dataRequest['name'] . '%');
                });
            }

            if (isset($dataRequest['email'])) {
                $data = $data->where(function ($query) use ($dataRequest) {
                    $query->where('email', 'like', '%' . $dataRequest['email'] . '%');
                });
            }
        }
        $collection = $data->orderBy('id', 'desc')->paginate(10, ['*'], 'page', $page);
        $rawData = PromotionResource::collection($collection);
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

    public function show($id)
    {
        // $access = Auth::guard(name: 'api')->user(); // atau 'admins' sesuai guard kamu
        // if (!$access || !$access->hasAccessToMenu($this->route)) {
        //     return $this->sendError('Akses ditolak', 'Tidak memiliki akses ke menu ini');
        // }

        $data = $this->model->with('brand')->find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }
        $detailMapper = new PromotionDetailMapper($data);
        $result = $detailMapper->data();

        return $this->sendResponse($result, 'Success Load Data');
    }

    public function edit($id)
    {
        // $access = Auth::guard(name: 'api')->user(); 
        // if (!$access || !$access->hasAccessToMenu($this->route)) {
        //     return $this->sendError('Akses ditolak', 'Tidak memiliki akses ke menu ini');
        // }

        $data = $this->model->find($id);

        if (!$data) {
            return $this->sendError('Data not found', 'Data not found');
        }

        $data['file']=$data->file ? asset('storage/' . $data->file) : null;
        $data['file2']=$data->file2 ? asset('storage/' . $data->file2) : null;
        $data['file3']=$data->file3 ? asset('storage/' . $data->file3) : null;
        $data['file4']=$data->file4 ? asset('storage/' . $data->file4) : null;
        $data['file5']=$data->file5 ? asset('storage/' . $data->file5) : null;

        return $this->sendResponse($data, 'Success Load Data');
    }

    public function store(Request $request)
    {
       $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'note' => 'nullable|string',
            'type' => 'nullable',
            'brand_id' => 'nullable',
            'file' => 'nullable|image|mimes:jpeg,png,jpg|max:15360',
            'file2' => 'nullable|image|mimes:jpeg,png,jpg|max:15360',
            'file3' => 'nullable|image|mimes:jpeg,png,jpg|max:15360',
            'file4' => 'nullable|image|mimes:jpeg,png,jpg|max:15360',
            'file5' => 'nullable|image|mimes:jpeg,png,jpg|max:15360',
        ], [
            'file.max' => 'Ukuran file gambar 1 tidak boleh lebih dari 15MB.',
            'file2.max' => 'Ukuran file gambar 2 tidak boleh lebih dari 15MB.',
            'file3.max' => 'Ukuran file gambar 3 tidak boleh lebih dari 15MB.',
            'file4.max' => 'Ukuran file gambar 4 tidak boleh lebih dari 15MB.',
            'file5.max' => 'Ukuran file gambar 5 tidak boleh lebih dari 15MB.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors());
        }

        $data = $request->only([
            'name',
            'description',
            'note',
            'type',
            'brand_id',
            'status'
        ]);

        // Handle image uploads
        $uploadService = new FileUploadService();
        $fileFields = ['file', 'file2', 'file3', 'file4', 'file5'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                try {
                    $data[$field] = $uploadService->validateAndStore($request, $field, 'promotions');
                } catch (\InvalidArgumentException $e) {
                    return $this->sendError('Upload failed', 'Terjadi kesalahan pada server');
                }
            } else {
                $data[$field] = null;
            }
        }

        // If status is being set to active, make all other promotions inactive
        if (isset($data['status']) && $data['status'] === 'active') {
            Promotion::where('id', '!=', null)->update(['status' => 'inactive']);
        }

        $promotion = Promotion::create($data);

        return $this->sendResponse($promotion, 'Add PromotionSuccess');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function update(Request $request, $id)
    {
        // $access = Auth::guard(name: 'api')->user(); // atau 'admins' sesuai guard kamu
        // if (!$access || !$access->hasAccessToMenu($this->route)) {
        //     return $this->sendError('Akses ditolak', 'Tidak memiliki akses ke menu ini', 403);
        // }

        $data = Promotion::find($id);
        // echo $data;
        if (!$data) {

            return $this->sendError('Promotion not found', 'Product not found');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'note' => 'nullable|string',
            'type' => 'nullable|string',
            'brand_id' => 'nullable|exists:brands,id',
            'file' => 'nullable|image|mimes:jpeg,png,jpg|max:15360',
            'file2' => 'nullable|image|mimes:jpeg,png,jpg|max:15360',
            'file3' => 'nullable|image|mimes:jpeg,png,jpg|max:15360',
            'file4' => 'nullable|image|mimes:jpeg,png,jpg|max:15360',
            'file5' => 'nullable|image|mimes:jpeg,png,jpg|max:15360',
        ], [
            'file.max' => 'Ukuran file gambar 1 tidak boleh lebih dari 15MB.',
            'file2.max' => 'Ukuran file gambar 2 tidak boleh lebih dari 15MB.',
            'file3.max' => 'Ukuran file gambar 3 tidak boleh lebih dari 15MB.',
            'file4.max' => 'Ukuran file gambar 4 tidak boleh lebih dari 15MB.',
            'file5.max' => 'Ukuran file gambar 5 tidak boleh lebih dari 15MB.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors());
        }

        $uploadService = new FileUploadService();
        $fileFields = ['file', 'file2', 'file3', 'file4', 'file5'];

        foreach ($fileFields as $field) {
            if (!$request->hasFile($field)) {
                continue;
            }

            // Delete the old file if it exists
            if ($data->$field) {
                Storage::disk('public')->delete($data->$field);
            }

            try {
                $data->$field = $uploadService->validateAndStore($request, $field, 'promotions');
            } catch (\InvalidArgumentException $e) {
                return $this->sendError('Upload failed', 'Terjadi kesalahan pada server');
            }
        }

        // Update only the provided fields
        $data->update($request->except($fileFields));
        // Handle image1 upload

        // If status is being set to active, make all other promotions inactive
        if ($request->has('status') && $request->input('status') === 'active') {
            Promotion::where('id', '!=', $id)->update(['status' => 'inactive']);
        }

        return $this->sendResponse($data, 'Promotion updated successfully');
    }

    public function destroy($id)
    {
        // $access = Auth::guard(name: 'api')->user(); // atau 'admins' sesuai guard kamu
        // if (!$access || !$access->hasAccessToMenu($this->route)) {
        //     return $this->sendError('Akses ditolak', 'Tidak memiliki akses ke menu ini', 403);
        // }
    
        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }
    
        $imageFields = ['file', 'file2', 'file3', 'file4', 'file5'];
        foreach ($imageFields as $field) {
            if (!empty($data->$field) && Storage::disk('public')->exists($data->$field)) {
                Storage::disk('public')->delete($data->$field);
            }
        }
    
        $data->delete();
    
        return $this->sendResponse(null, 'Data berhasil dihapus');
    }

    public function getPromotion()
    {
         $brand = Brand::select('id', 'name')->get();
        if (!$brand) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan');
        }

        return $this->sendResponse($brand, 'Data berhasil diambil');
    }
}

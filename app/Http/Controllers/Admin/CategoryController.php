<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Resources\Admin\CategoryResource;
use App\Mappers\Admin\CategoryMapper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CategoryController extends BaseController
{
    private $model;
    private $route = 'categories';
    Public $title="Categories";
    public function __construct(Category $model)
    {
        $this->model = $model;
    }
    public function index(Request $request)
    {
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

            if (isset($dataRequest['email'])) {
                $data = $data->where(function ($query) use ($dataRequest) {
                    $query->where('email', 'like', '%' . $dataRequest['email'] . '%');
                });
            }
        }

        $collection = $data->orderBy('id', 'desc')->paginate(10, ['*'], 'page', $page);
        $rawData = CategoryResource::collection($collection);
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
       
        if ($checkProcess) {
            return $this->sendError('Gagal', 'Kategori sudah ada');
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'nullable|string',
            'note' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:88408',
        ],[
            'image.max' => 'Ukuran file gambar tidak boleh lebih dari 17MB.',
        ]);


        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors());
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            
            // Cek apakah file valid
            if (!$file->isValid()) {
                return $this->sendError('Upload gagal', 'File tidak valid: ' . $file->getErrorMessage());
            }
            
            // Coba upload dengan penanganan error
            try {
                $imagePath = $file->store('categories', 'public');
                if (!$imagePath) {
                    return $this->sendError('Upload gagal', 'Gagal menyimpan file ke storage');
                }
            } catch (\Exception $e) {
                return $this->sendError('Upload gagal', 'Error: ' . 'Terjadi kesalahan pada server');
            }
        } else {
            $imagePath = null;
        }

        $data = $this->model::create([
            'name' => $request->name,
            'description' => $request->description,
            'note' => $request->note,
            'image' => $imagePath,
        ]);

        return $this->sendResponse($data, 'Tambah Kategori berhasil');
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
        if (!$data) {
            return $this->sendError('Data not found', 'Data not found');
        }

        return $this->sendResponse($data, 'Success Load Data');
    }
    public function edit($id)
    {
        $data = $this->model::find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }

        return $this->sendResponse(new CategoryResource($data), 'Data berhasil diambil');
    }
    public function update(Request $request, $id)
    {
        $data = $this->model::find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }

        $checkProcess=$this->model::where('name', $request->name)->where('id', '!=', $id)->first();
       
        if ($checkProcess) {
            return $this->sendError('Gagal', 'Kategori sudah ada');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'nullable|string',
            'note' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:15360',
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
            $imagePath = $request->file('image')->store('categories', 'public');
        } else {
            $imagePath = $data->image;
        }

        $data->update([
            'name' => $request->name,
            'description' => $request->description,
            'note' => $request->note,
            'image' => $imagePath,
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

        return $this->sendResponse(null, 'Hapus Kategori berhasil');
    }
}

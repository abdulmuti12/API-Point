<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banner;
use App\Mappers\Admin\BannerMapper;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Admin\BannerResource;
use App\Mappers\Admin\BannerDetailMapper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Services\FileUploadService;

class BannerController extends BaseController
{
    private $model;
    private $route = 'campaigns';
    Public $title="Campaigns";

    public function __construct(Banner $model)
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
        $rawData = BannerResource::collection($collection);
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
        //     return $this->sendError('Akses ditolak', 'Tidak memiliki akses ke menu ini', 403);
        // }

        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }
        $data = (new BannerDetailMapper($data))->data();

        return $this->sendResponse($data, 'Load Banner Success');}
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'note' => 'nullable|string',
            'type' => 'nullable|string',
            'title' => 'nullable|string|max:100',
            'title2' => 'nullable|string|max:100',
            'title3' => 'nullable|string|max:100',
            'title4' => 'nullable|string|max:100',
            'title5' => 'nullable|string|max:100',
            'file'  => 'nullable|file|mimes:jpeg,png,jpg,mp4,mov,avi,webm|max:20480',
            'file2' => 'nullable|file|mimes:jpeg,png,jpg,mp4,mov,avi,webm|max:20480',
            'file3' => 'nullable|file|mimes:jpeg,png,jpg,mp4,mov,avi,webm|max:20480',
            'file4' => 'nullable|file|mimes:jpeg,png,jpg,mp4,mov,avi,webm|max:20480',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors());
        }

        $data = $request->only(['name', 'description', 'note', 'type','status','note2','note3','note4','note5','title','title2','title3','title4','title5']);

        if($request->status == 'Active'){

        }

        $uploadService = new FileUploadService();
        foreach (['file', 'file2', 'file3', 'file4'] as $field) {
            if ($request->hasFile($field)) {
                try {
                    $data[$field] = $uploadService->validateAndStore($request, $field, 'banners');
                } catch (\InvalidArgumentException $e) {
                    return $this->sendError('Upload failed', 'Terjadi kesalahan pada server');
                }
            } else {
                $data[$field] = null;
            }
        }

        $banner = Banner::create($data);

        if ($request->status === 'Active') {
            Banner::where('id', '!=', $banner->id)->update(['status' => 'Non Active']);
        }

        return $this->sendResponse($banner, 'Add Banner Success');
    }

    public function edit($id)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }

        return $this->sendResponse($data, 'Load Banner Success');
    }
    
    public function update(Request $request, $id)
    {
        $data = Banner::find($id);
        if (!$data) {
            return $this->sendError('Banner not found', 'Banner not found');
        }
    
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'note' => 'nullable|string',
            'type' => 'nullable|string',
            'file'  => 'nullable|file|mimes:jpeg,png,jpg,mp4,mov,avi,webm|max:20480',
            'file2' => 'nullable|file|mimes:jpeg,png,jpg,mp4,mov,avi,webm|max:20480',
            'file3' => 'nullable|file|mimes:jpeg,png,jpg,mp4,mov,avi,webm|max:20480',
            'file4' => 'nullable|file|mimes:jpeg,png,jpg,mp4,mov,avi,webm|max:20480',
        ]);
    
        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors());
        }
    
        // Handle file update & delete old file if exists
        $uploadService = new FileUploadService();
        foreach (['file', 'file2', 'file3', 'file4'] as $field) {
            if ($request->hasFile($field)) {
                if ($data->$field) {
                    Storage::disk('public')->delete($data->$field);
                }
                try {
                    $data->$field = $uploadService->validateAndStore($request, $field, 'banners');
                } catch (\InvalidArgumentException $e) {
                    return $this->sendError('Upload failed', 'Terjadi kesalahan pada server');
                }
            }
        }
    
        $data->update($request->except(['file', 'file2', 'file3', 'file4']));
        $data->save();

        if ($request->status === 'Active') {
            Banner::where('id', '!=', $id)->update(['status' => 'Non Active']);
        }
    
        return $this->sendResponse($data, 'Banner updated successfully');
    }
    public function destroy($id)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }
    
        foreach (['file', 'file2', 'file3', 'file4'] as $field) {
            if (!empty($data->$field) && Storage::disk('public')->exists($data->$field)) {
                Storage::disk('public')->delete($data->$field);
            }
        }
    
        $data->delete();
    
        return $this->sendResponse(null, 'Data dan file berhasil dihapus');
    }

}

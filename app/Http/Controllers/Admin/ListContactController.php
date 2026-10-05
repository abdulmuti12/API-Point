<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactUs;
use App\Http\Resources\Admin\ContactUsResource;
use App\Mappers\Admin\ContactUsDetailMapper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ListContactController extends BaseController
{
  private $model;
    private $route = 'contact_us';
    Public $title="ContactUs";

    public function __construct(ContactUs $model)
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

            if (isset($dataRequest['email'])) {
                $data = $data->where(function ($query) use ($dataRequest) {
                    $query->where('email', 'like', '%' . $dataRequest['email'] . '%');
                });
            }
        }
        $collection = $data->orderBy('id', 'desc')->paginate(10, ['*'], 'page', $page);
        $rawData = ContactUsResource::collection($collection);
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

        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }
        $detailMapper = new ContactUsDetailMapper($data);
        $result = $detailMapper->data();

        return $this->sendResponse($result, 'Success Load Data');
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
    
        $data->delete();
    
        return $this->sendResponse(null, 'Data berhasil dihapus');
    }
}
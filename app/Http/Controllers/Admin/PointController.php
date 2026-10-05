<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Point;
use App\Http\Resources\Admin\PointResource;
use Illuminate\Support\Facades\Validator;

class PointController extends BaseController
{
    private $model;
    private $route = 'points';
    public $title = "Points";

    public function __construct(Point $model)
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
                $data = $data->where('name', 'like', '%' . $dataRequest['name'] . '%');
            }
        }

        $collection = $data->orderBy('id', 'desc')->paginate(10, ['*'], 'page', $page);
        $rawData = PointResource::collection($collection);
        if ($raw) {
            $datas = $rawData;
        } else {
            $responses = $rawData->response()->getData(true);
            $links = $responses['links'];
            $meta = $responses['meta'];
            unset($responses['links']);
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
        $checkProcess = $this->model->where('name', $request->name)->first();

        if ($checkProcess) {
            return $this->sendError('Gagal', 'Point sudah ada');
        }

        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:50',
            'status'        => 'nullable|string|max:60',
            'price_point'   => 'required|numeric',
            'range_point'   => 'required|numeric',
            'point'         => 'required|integer',
            'description'   => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors());
        }

        $data = $this->model::create([
            'name'          => $request->name,
            'status'        => $request->status ?? 'active',
            'price_point'   => $request->price_point,
            'range_point'   => $request->range_point,
            'point'         => $request->point,
            'description'   => $request->description,
        ]);

        if (strtolower(($request->status ?? 'active')) === 'active') {
            $this->model->where('id', '!=', $data->id)
                ->update(['status' => 'non-active']);
        }

        return $this->sendResponse($data, 'Tambah Point berhasil');
    }

    public function show($id)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError($this->title . ' not found', 'Data not found');
        }

        return $this->sendResponse(new PointResource($data), 'Success Load Data');
    }

    public function edit($id)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }

        return $this->sendResponse(new PointResource($data), 'Data berhasil diambil');
    }

    public function update(Request $request, $id)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }

        $checkProcess = $this->model->where('name', $request->name)->where('id', '!=', $id)->first();

        if ($checkProcess) {
            return $this->sendError('Gagal', 'Point sudah ada');
        }

        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:50',
            'status'        => 'nullable|string|max:60',
            'price_point'   => 'required|numeric',
            'range_point'   => 'required|numeric',
            'point'         => 'required|integer',
            'description'   => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors());
        }

        $data->update([
            'name'          => $request->name,
            'status'        => $request->status ?? 'active',
            'price_point'   => $request->price_point,
            'range_point'   => $request->range_point,
            'point'         => $request->point,
            'description'   => $request->description,
        ]);

        if (strtolower(($request->status ?? 'active')) === 'active') {
            $this->model->where('id', '!=', $data->id)
                ->update(['status' => 'non-active']);
        }

        return $this->sendResponse($data, 'Update Point berhasil');
    }

    public function destroy($id)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }

        $data->delete();

        return $this->sendResponse(null, 'Hapus Point berhasil');
    }
}
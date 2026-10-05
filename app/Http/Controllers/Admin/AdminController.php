<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Admin\AdminResource;
use Illuminate\Support\Facades\Validator;
use App\Mappers\Admin\AdminDetailMapper;
use App\Models\Role;

class AdminController extends BaseController
{
    private $model;
    private $route = 'admins';
    Public $title="Admin";

    public function __construct(Admin $model)
    {
        $this->model = $model;
    }
    public function index(Request $request)
    {
        $access = Auth::guard('api')->user();
        if (!$access || !$access->hasAccessToMenu($this->route)) {
            return $this->sendError('Akses ditolak', 'Tidak memiliki akses ke menu ini', 403);
        }

        $page = $request->query('page', 1);
        $raw = filter_var($request->query('raw', false), FILTER_VALIDATE_BOOLEAN);

        $data = $this->model->with(['roles:id,name']);

        if ($request->filled('name')) {
            $data->where('name', 'like', '%'.$request->query('name').'%');
        }
        if ($request->filled('email')) {
            $data->where('email', 'like', '%'.$request->query('email').'%');
        }

        $collection = $data->orderByDesc('id')->paginate(10, ['*'], 'page', $page);
        $rawData = AdminResource::collection($collection);

        if ($raw) return $this->sendResponse($rawData, 'Success Load Data');

        $responses = $rawData->response()->getData(true);
        $links = $responses['links'];
        $meta  = $responses['meta'];
        unset($responses['links'], $responses['meta']);

        $datas = array_merge($responses, $meta, [
            'first_page_url' => $links['first'],
            'last_page_url'  => $links['last'],
            'prev_page_url'  => $links['prev'],
            'next_page_url'  => $links['next'],
        ]);

        return $this->sendResponse($datas, 'Success Load Data');

    }

    public function store(Request $request)
    {
        $access = Auth::guard('api')->user(); // atau 'admins' sesuai guard kamu
        if (!$access || !$access->hasAccessToMenu($this->route)) {
            return $this->sendError('Akses ditolak', 'Tidak memiliki akses ke menu ini', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            // 'full_name' => 'required|string',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return $this->sendError('errors', $validator->errors());
        }

        $admin = $this->model::create([
            'name' => $request->name,
            'email' => $request->email,
            'status'=>$request->status,
            'password' => bcrypt($request->password),
        ]);

        $admin->roles()->attach($request->role_id);

        return $this->sendResponse($admin, 'Register berhasil');
    }

    public function edit($id)
    {
        $access = Auth::guard(name: 'api')->user(); // atau 'admins' sesuai guard kamu
        if (!$access || !$access->hasAccessToMenu($this->route)) {
            return $this->sendError('Akses ditolak', 'Tidak memiliki akses ke menu ini', 403);
        }

        $data = $this->model::find($id);

        if (!$data) {
            return $this->sendError('Data not found', 'Data not found');
        }

        $detailMapper = new AdminDetailMapper($data);
        $result = $detailMapper->data();

        return $this->sendResponse($result, 'Success Load Data');
    }
    public function update(Request $request, $id)
    {
        $access = Auth::guard(name: 'api')->user(); // atau 'admins' sesuai guard kamu
        if (!$access || !$access->hasAccessToMenu($this->route)) {
            return $this->sendError('Akses ditolak', 'Tidak memiliki akses ke menu ini', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'status' => 'required|string',
            'email' => 'required|string',
            // 'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:8',
        ]);

        $checkProcess=$this->model::where('email', $request->email)->where('id', '!=', $id)->first();
       
        if ($validator->fails()) {
            return $this->sendError('errors', $validator->errors());
        }

        if ($checkProcess) {
            return $this->sendError('Gagal', $this->title.' sudah ada');
        }
        
        $admin = $this->model::find($id);

        if (!$admin) {
            return $this->sendError('Data not found', 'Data not found');
        }

        $admin->update([
            'name' => $request->name,
            'status' => $request->status,
            'updated_at' => now(),
            'email' => $request->email,
            'password' => $request->password ? bcrypt($request->password) : $admin->password,
        ]);

        $admin->roles()->sync($request->role_id);

        return $this->sendResponse($admin, 'Update berhasil');
    }
    public function destroy($id)
    {
        $access = Auth::guard(name: 'api')->user(); // atau 'admins' sesuai guard kamu
        if (!$access || !$access->hasAccessToMenu($this->route)) {
            return $this->sendError('Akses ditolak', 'Tidak memiliki akses ke menu ini', 403);
        }

        $admin = $this->model::find($id);

        if (!$admin) {
            return $this->sendError('Data not found', 'Data not found');
        }

        $admin->delete();

        return $this->sendResponse(null, 'Delete berhasil');
    }

    public function show($id)
    {
        // $access = Auth::guard(name: 'api')->user(); // atau 'admins' sesuai guard kamu
        // if (!$access || !$access->hasAccessToMenu($this->route)) {
        //     return $this->sendError('Akses ditolak', 'Tidak memiliki akses ke menu ini', 403);
        // }

        $data = $this->model::find($id);
        if (!$data) {
            return $this->sendError($this->title . 'not found', 'Data not found');
        }

        $detailMapper = new AdminDetailMapper($data);
        $result = $detailMapper->data();

        return $this->sendResponse($result, 'Success Load Data');
    }

    public function getRole(){
        $access = Auth::guard(name: 'api')->user(); // atau 'admins' sesuai guard kamu
        if (!$access || !$access->hasAccessToMenu($this->route)) {
            return $this->sendError('Akses ditolak', 'Tidak memiliki akses ke menu ini', 403);
        }

        $roles = Role::select('id', 'name')->get();

        return $this->sendResponse($roles, 'Success Load Data');
    }

}

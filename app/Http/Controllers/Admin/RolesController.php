<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Http\Resources\Admin\RoleResource;
use App\Mappers\Admin\RolesDetailMapper;
use App\Models\Menu;
use App\Models\RoleMenu;
use Illuminate\Support\Facades\Validator;

class RolesController extends BaseController
{
    private $model;
    private $route = 'roles';
    Public $title="Roles";
    public function __construct(Role $model)
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
        $rawData = RoleResource::collection($collection);
        if ($raw) {
            $datas = $rawData;
        } else {
            $responses = $rawData->response()->getData(true);
            $links = $responses['links'];
            unset($responses['links']);
            $meta = $responses['meta'];
            unset($responses['meta']);
            $datas = [
                'data' => $responses,
                'meta' => $meta,
                'links' => $links,
            ];
        }
        return response()->json($datas);
    }
    public function show($id)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }

        $detailMapper = new RolesDetailMapper($data);
        $result = $detailMapper->data();

        return $this->sendResponse($result, 'Success Load Data');
    }

    public function store(Request $request)
    {
        $checkProcess=$this->model::where('name', $request->name)->first();
       
        if ($checkProcess) {
            return $this->sendError('Gagal', $this->title.' sudah ada');
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $data = $this->model::create([
            'name' => $request->name
        ]);

        $data->menus()->sync($request->menus);


        return $this->sendResponse($data, 'Tambah '.$this->title.' berhasil');
    }
    // public function update(Request $request, $id)
    // {
    //     $data = $this->model::find($id);
    //     if (!$data) {
    //         return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
    //     }

    //     $checkProcess=$this->model::where('name', $request->name)->where('id', '!=', $id)->first();
       
    //     if ($checkProcess) {
    //         return $this->sendError('Gagal', $this->title.' sudah ada');
    //     }

    //     $validator = Validator::make($request->all(), [
    //         'name' => 'required|string'
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->sendError('Validasi gagal', $validator->errors());
    //     }

    //     if($request->menus){
    //         $Role = RoleMenu::where('role_id', $id)->delete();
    //         foreach ($request->menus as $menu) {
    //             RoleMenu::create([
    //                 'role_id' => $id,
    //                 'menu_id' => $menu
    //             ]);
    //         }       
    //     }

    //     $data->update([
    //         'name' => $request->name
    //     ]);

    //     return $this->sendResponse($data, 'Update Kategori berhasil');
    // }
    public function destroy($id)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }

        RoleMenu::where('role_id', $id)->delete();
        
        $data->delete();

        return $this->sendResponse(null, 'Hapus '.$this->title.' berhasil');
    }
    public function getRoleById($id)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }
        return response()->json(new RoleResource($data), 200);
    }
    public function getMenu()
    {
        $data = Menu::select('id', 'name')->get();
        if (!$data) {
            return $this->sendError('Data tidak ditemukan', 'Data tidak ditemukan', 404);
        }
        
        return $this->sendResponse($data,  'Data LoadSuccess');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // 'menus' => 'required|array',
            // 'menus.*' => 'integer|exists:menus,id',
        ]);

        $role = Role::findOrFail($id);

        // Update name
        $role->update([
            'name' => $validated['name'],
        ]);

        // Update associated menus (many-to-many)
        $role->menus()->sync($request->menus);

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully.',
            'data' => $role,
        ]);

    }
    public function edit($id)
    {
        $role = Role::findOrFail($id);
        $menus= Menu::all();

        foreach ($menus as $menu) {
            $roleMenus= RoleMenu::where('role_id', $id)->where('menu_id', $menu->id)->first();
            
            if ($roleMenus) {
                $status = true; // Add a 'selected' property to indicate the menu is associated with the role
            } else {
                $status = false; // Add a 'selected' property to indicate the menu is not associated with the role
            }

            $menulist[]= array(
                'id' => $menu->id,
                'name' => $menu->name,
                'selected' => $status, // Add a 'selected' property to indicate the menu is associated with the role
            );
                    // $menu->selected = true; // Add a 'selected' property to indicate the menu is associated with the role
        }
        $data['name']= $role->name;
        $data['menus']= $menulist;

        return $this->sendResponse($data,  'Data LoadSuccess');
    }
}

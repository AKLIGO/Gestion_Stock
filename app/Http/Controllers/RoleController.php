<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\RoleRequest;
use App\Models\Role;
class RoleController extends Controller
{
    //
    public function store(RoleRequest $request)
    {

        $role = Role::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);
        return response()->json(['message' => 'Role created successfully', 'role' => $role], 201);
        
    }
}

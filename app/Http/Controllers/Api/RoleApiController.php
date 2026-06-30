<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;

class RoleApiController extends Controller
{
    public function index()
    {
        return response()->json(Role::orderBy('name')->get(['id', 'name']));
    }
}

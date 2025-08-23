<?php

namespace App\Http\Controllers\SuperManager;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\SubManager;
use App\Models\User;
// use Dotenv\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class BranchController extends Controller
{


    public function getBranchDetails($branchId)
    {
        $branch = Branch::with([
            'subManager' => function ($query) {
                $query->select('id', 'user_id', 'photo', 'phone');
            },
            'subManager.user' => function ($query) {
                $query->select('id', 'name', 'email');
            }
        ])
            ->select('id', 'address', 'latitude', 'longitude', 'sub_manager_id')
            ->findOrFail($branchId);

        return response()->json([
            'id'        => $branch->id,
            'address'   => $branch->address,
            'latitude'  => $branch->latitude,
            'longitude' => $branch->longitude,
            'manager'   => $branch->subManager ? [
                'name'  => $branch->subManager->user->name ?? null,
                'email' => $branch->subManager->user->email ?? null,
                'phone' => $branch->subManager->phone ?? null,
            ] : null,
        ]);
    }

    public function index()
    {
        $branches = Branch::all();
        return response()->json($branches);
    }

    public function getBranchesWithManagers()
    {
        $branches = Branch::with('subManager.user:id,name')
            ->select('id', 'address', 'sub_manager_id')
            ->get()
            ->map(function ($branch) {
                return [
                    'branch_id'    => $branch->id,
                    'branch_name'  => $branch->address,
                    'manager_name' => $branch->subManager && $branch->subManager->user
                        ? $branch->subManager->user->name
                        : null,
                ];
            });

        return response()->json([
            'branches' => $branches
        ]);
    }


    public function addNewBranch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude'        => 'required|numeric',
            'longitude'       => 'required|numeric',
            'address'         => 'required|string|max:500',
            'sub_manager_id'  => 'required|exists:sub_managers,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $branch = Branch::create([
            'address'         => $request->address,
            'latitude'        => $request->latitude,
            'longitude'       => $request->longitude,
            'sub_manager_id'  => $request->sub_manager_id,
        ]);

        return response()->json([
            'message' => 'Branch created successfully',
            'branch'  => $branch,
        ], 201);
    }

    public function assignManagerToBranch(Request $request)
    {
        $request->validate([
            'branch_id'  => 'required|exists:branchs,id',
            'manager_id' => 'required|exists:sub_managers,id',
        ]);

        $branch = Branch::findOrFail($request->branch_id);
        $branch->sub_manager_id = $request->manager_id;
        $branch->save();

        return response()->json([
            'message' => 'Manager assigned to branch successfully',
            'branch'  => $branch,
        ]);
    }

    public function delete($branch_id)
    {
        $branch = Branch::findOrFail($branch_id);
        if ($branch) {
            $branch->delete();
        }
        return response()->json([
            'message' => 'deleted ^_^'
        ]);
    }
}

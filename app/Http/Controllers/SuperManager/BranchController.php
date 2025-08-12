<?php

namespace App\Http\Controllers\SuperManager;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\SubManager;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::all();
        return response()->json($branches);
    }

    public function getBranchManagers()
    {
        $managers = SubManager::with('user:id,name,image,mobile')
            ->get()
            ->map(function ($manager) {
                return [
                    'name'   => $manager->user->name,
                    'image'  => $manager->user->image,
                    'mobile' => $manager->user->mobile,
                ];
            });

        return response()->json($managers);
    }

    public function getBranchManagerDetails($branchId)
    {
        $branch = Branch::with(['subManager.user:id,name,mobile,email'])
            ->select('id', 'address', 'sub_manager_id')
            ->findOrFail($branchId);

        return response()->json([
            'name'   => $branch->subManager->user->name,
            'mobile' => $branch->subManager->user->mobile,
            'email'  => $branch->subManager->user->email,
            'address' => $branch->address,
        ]);
    }

    public function delete($id)
    {
        $manager = SubManager::findOrFail($id);
        $manager->delete(); // Soft delete

        return response()->json(['message' => 'Gallery Manager  deleted successfully']);
    }
}
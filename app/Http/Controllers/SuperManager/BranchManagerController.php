<?php

namespace App\Http\Controllers\SuperManager;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\SubManager;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class BranchManagerController extends Controller
{

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email', // التحقق من عدم تكرار الإيميل في الجدولين
            'phone'    => 'nullable|string|max:20', // التحقق من رقم الموبايل
            'password' => 'required|string|min:6',
            'photo'    => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // رفع الصورة إذا موجودة
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('sub_managers', 'public');
        }

        // إنشاء SubManager مع الإيميل ورقم الموبايل
        SubManager::create([
            'user_id' => $user->id,
            'photo'   => $photoPath,
            'email'   => $validated['email'],
            'phone'   => $validated['phone'] ?? null,
        ]);

        return response('Done', 200);
    }


    public function getBranchManagers()
{
    $managers = SubManager::with('user:id,name,email')
        ->get()
        ->map(function ($manager) {
            return [
                'id'    => $manager->id,
                'name'  => $manager->user->name,
                'image' => $manager->photo, 
                'email' => $manager->user->email,
                'phone' => $manager->phone,
            ];
        });

    return response()->json([
        'managers' => $managers
    ]);
}



    public function getBranchManagerDetails($managerId)
    {
        $manager = SubManager::with('user:id,name,email')
            ->select('id', 'user_id', 'photo', 'phone')
            ->findOrFail($managerId);

        return response()->json([
            'id' => $manager->id,
            'image'  => $manager->photo,
            'name'   => $manager->user->name,
            'phone'  => $manager->phone,
            'email'  => $manager->user->email,
        ]);
    }


  public function update(Request $request, $id)
{
    $subManager = SubManager::with('user')->findOrFail($id);

    if (!$subManager->user) {
        return response()->json(['message' => 'User not found for this SubManager'], 404);
    }

    $request->validate([
        'name'  => 'nullable|string|max:255',
        'email' => 'nullable|email|unique:users,email,' . $subManager->user->id, 
        'phone' => 'nullable|string|max:20',
    ]);

    \DB::transaction(function () use ($request, $subManager) {
        // تحديث جدول users
        $subManager->user->update([
            'name'  => $request->input('name', $subManager->user->name),
            'email' => $request->input('email', $subManager->user->email),
        ]);

        // تحديث جدول sub_managers
        $subManager->update([
            'phone' => $request->input('phone', $subManager->phone),
        ]);
    });

    return response()->json([
        'message' => 'Done!',
        'manager' => $subManager->load('user')
    ]);
}




    public function delete($id)
    {
        $manager = SubManager::findOrFail($id);

        if ($manager->user) {
            $manager->user()->delete();
        }

        $manager->delete();

        return response()->json(['message' => 'Branch Manager deleted successfully']);
    }
}
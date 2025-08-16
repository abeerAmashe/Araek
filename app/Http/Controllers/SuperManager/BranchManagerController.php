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
        $managers = SubManager::with('user:id,name,email') // جلب بيانات المستخدم المرتبطة
            ->get()
            ->map(function ($manager) {
                return [
                    'id' => $manager->id,
                    'name'   => $manager->user->name,
                    'image'  => $manager->photo,  // الصورة من جدول sub_managers
                    'email'  => $manager->user->email,  // الإيميل من جدول sub_managers
                    'phone'  => $manager->phone,  // الهاتف من جدول sub_managers
                ];
            });

        return response()->json($managers);
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
        $request->validate([
            'name'      => 'string|max:255',
            'email'     => 'email|unique:users,email,' . $id,
            'phone'     => 'string|max:20',
            'address'   => 'string|max:255',
        ]);

        $manager = SubManager::findOrFail($id);

        $manager->update([
            'name'    => $request->name,
            'email'   => $request->email,
            'phone'   => $request->phone ?? $manager->phone,
            'address' => $request->address ?? $manager->address,
        ]);

        return response()->json([
            'message' => 'Done !',
            'manager' => $manager
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
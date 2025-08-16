<?php

namespace App\Http\Controllers\supermanager;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index()
    {
        $complaints = Complaint::with(['customer.user' => function ($query) {
            $query->select('id', 'name'); // نجيب فقط الاسم والـ id
        }])
            ->select('id', 'customer_id', 'message', 'status', 'created_at') // الأعمدة المطلوبة
            ->get()
            ->map(function ($complaint) {
                return [
                    'id'            => $complaint->id,                   // معرف الشكوى
                    'customer_name' => $complaint->customer->user->name ?? null,
                    'message'       => $complaint->message,
                    'status'        => $complaint->status,
                    'created_at'    => $complaint->created_at->toDateTimeString(), // أو toFormattedDateString()
                ];
            });

        return response()->json([
            'complaints' => $complaints
        ], 200);
    }


    public function show($id)
    {
        $complaint = Complaint::with('customer')->find($id);

        if (!$complaint) {
            return response()->json(['message' => 'Complaint not found'], 404);
        }

        return response()->json([
            'complaint' => $complaint
        ], 200);
    }
}
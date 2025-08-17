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
            $query->select('id', 'name'); 
        }])
            ->select('id', 'customer_id', 'message', 'status', 'created_at') 
            ->get()
            ->map(function ($complaint) {
                return [
                    'id'            => $complaint->id,                  
                    'customer_name' => $complaint->customer->user->name ?? null,
                    'message'       => $complaint->message,
                    'status'        => $complaint->status,
                    'date'    => $complaint->created_at->toDateTimeString(), 
                ];
            });

        return response()->json([
            'complaints' => $complaints
        ], 200);
    }


   
}
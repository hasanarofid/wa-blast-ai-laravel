<?php

namespace App\Http\Controllers;

use App\Models\ChatHistory;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class HistoriController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function getHistory(Request $request)
    {
        $data = ChatHistory::with('user');
        return Datatables::of($data)
                ->addColumn('nama_user', fn($data) => $data->user->name ?? '-')
                ->addColumn('pesan', fn($data) => $data->message ?? '-')
                ->addColumn('response', fn($data) => $data->response ?? '-')
                ->rawColumns(['nama_user', 'pesan', 'response'])
                ->make(true);
        
    }
}

<?php
// app/Http/Controllers/Api/V1/AktivitasController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AktivitasUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AktivitasController extends Controller
{
    // GET ALL ACTIVITIES (with filters)
    public function index(Request $request)
    {
        // Only owner can access
        if ($request->user()->role !== 'owner') {
            return response()->json([
                'message' => 'Akses ditolak. Hanya owner yang dapat melihat aktivitas user.'
            ], 403);
        }

        $query = AktivitasUser::with('user:id,name,email,role');

        // Filter by user_id
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by role (through user relation)
        if ($request->has('role')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('role', $request->role);
            });
        }

        // Filter by aksi
        if ($request->has('aksi')) {
            $aksi = $request->aksi;
            if (is_array($aksi)) {
                $query->whereIn('aksi', $aksi);
            } else {
                $query->where('aksi', $aksi);
            }
        }

        // Filter by model
        if ($request->has('model')) {
            $query->where('model', $request->model);
        }

        // Filter date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Filter by search (in detail or user name)
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('detail', 'like', "%{$search}%")
                  ->orWhere('aksi', 'like', "%{$search}%")
                  ->orWhereHas('user', function($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting
        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->per_page ?? 20;
        $aktivitas = $query->paginate($perPage);

        return response()->json([
            'message' => 'success',
            'data' => $aktivitas,
            'filters' => $request->all(),
        ]);
    }

    // GET ACTIVITY BY ID (detail)
    public function show(Request $request, $id)
    {
        if ($request->user()->role !== 'owner') {
            return response()->json([
                'message' => 'Akses ditolak. Hanya owner yang dapat melihat detail aktivitas.'
            ], 403);
        }

        $aktivitas = AktivitasUser::with('user')->find($id);

        if (!$aktivitas) {
            return response()->json([
                'message' => 'Aktivitas tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'success',
            'data' => $aktivitas
        ]);
    }

    // GET ACTIVITY STATISTICS (dashboard for owner)
    public function statistics(Request $request)
    {
        // Total activities today
        $totalToday = AktivitasUser::whereDate('created_at', today())->count();
        
        // Total activities this week
        $totalThisWeek = AktivitasUser::whereBetween('created_at', [
            now()->startOfWeek(), 
            now()->endOfWeek()
        ])->count();
        
        // Top 5 most active users
        $topUsers = AktivitasUser::select('user_id', DB::raw('count(*) as total'))
            ->with('user:id,name,email,role')
            ->groupBy('user_id')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();
        
        return response()->json([
            'message' => 'success',
            'data' => [
                'summary' => [
                    'today' => $totalToday,
                    'this_week' => $totalThisWeek,
                ],
                'top_users' => $topUsers,
            ]
        ]);
    }

    // GET ACTIVITIES BY SPECIFIC USER
    public function userActivities(Request $request, $userId)
    {
        if ($request->user()->role !== 'owner') {
            return response()->json([
                'message' => 'Akses ditolak. Hanya owner yang dapat melihat aktivitas user.'
            ], 403);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        $query = AktivitasUser::where('user_id', $userId)->with('user');

        // Filter by aksi
        if ($request->has('aksi')) {
            $query->where('aksi', $request->aksi);
        }

        // Filter date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $perPage = $request->per_page ?? 20;
        $aktivitas = $query->latest()->paginate($perPage);

        return response()->json([
            'message' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'data' => $aktivitas,
        ]);
    }

    // EXPORT ACTIVITIES (CSV/JSON)
    public function export(Request $request)
    {
        if ($request->user()->role !== 'owner') {
            return response()->json([
                'message' => 'Akses ditolak. Hanya owner yang dapat export aktivitas.'
            ], 403);
        }

        $query = AktivitasUser::with('user');

        // Apply same filters as index
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->has('aksi')) {
            $query->where('aksi', $request->aksi);
        }

        $aktivitas = $query->latest()->get();

        $format = $request->format ?? 'json';

        if ($format === 'csv') {
            $csvData = $this->generateCSV($aktivitas);
            
            return response($csvData)
                ->withHeaders([
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="aktivitas_user_' . date('Y-m-d') . '.csv"',
                ]);
        }

        return response()->json([
            'message' => 'success',
            'data' => $aktivitas,
            'exported_at' => now(),
        ]);
    }

    // GET LIST OF ALL AVAILABLE ACTIONS (for filter dropdown)
    public function actionsList(Request $request)
    {
        if ($request->user()->role !== 'owner') {
            return response()->json([
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $actions = AktivitasUser::select('aksi')
            ->distinct()
            ->orderBy('aksi')
            ->pluck('aksi');

        return response()->json([
            'message' => 'success',
            'data' => $actions,
        ]);
    }

    // GET LIST OF ALL MODELS (for filter dropdown)
    public function modelsList(Request $request)
    {
        if ($request->user()->role !== 'owner') {
            return response()->json([
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $models = AktivitasUser::select('model')
            ->whereNotNull('model')
            ->distinct()
            ->orderBy('model')
            ->pluck('model');

        return response()->json([
            'message' => 'success',
            'data' => $models,
        ]);
    }

    // DELETE OLD ACTIVITIES (cleanup, owner only)
    public function cleanup(Request $request)
    {
        if ($request->user()->role !== 'owner') {
            return response()->json([
                'message' => 'Akses ditolak. Hanya owner yang dapat membersihkan data aktivitas.'
            ], 403);
        }

        $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        $days = $request->days;
        $cutoffDate = now()->subDays($days);

        $deletedCount = AktivitasUser::where('created_at', '<', $cutoffDate)->delete();

        return response()->json([
            'message' => "Berhasil menghapus {$deletedCount} data aktivitas yang lebih dari {$days} hari",
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate,
        ]);
    }

    // HELPER: Generate CSV
    private function generateCSV($aktivitas)
    {
        $handle = fopen('php://temp', 'r+');
        
        // Headers
        fputcsv($handle, ['ID', 'User', 'Role', 'Aksi', 'Model', 'Model ID', 'Detail', 'IP Address', 'Waktu']);
        
        // Data
        foreach ($aktivitas as $item) {
            fputcsv($handle, [
                $item->id,
                $item->user->name ?? 'Unknown',
                $item->user->role ?? '-',
                $item->aksi,
                $item->model ?? '-',
                $item->model_id ?? '-',
                $item->detail ?? '-',
                $item->ip_address ?? '-',
                $item->created_at,
            ]);
        }
        
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        
        return $csv;
    }
}
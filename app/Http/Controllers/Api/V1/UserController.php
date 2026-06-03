<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get all users except current user
     */
    public function index(Request $request)
    {
        $users = User::select('id', 'name', 'email', 'role')
            ->where('id', '!=', $request->user()->id)
            ->orderBy('name')
            ->get();
        
        return response()->json([
            'message' => 'success',
            'data' => $users
        ]);
    }
    
    /**
     * Search users by name or email
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        $users = User::select('id', 'name', 'email', 'role')
            ->where('id', '!=', $request->user()->id)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->get();
        
        return response()->json([
            'message' => 'success',
            'data' => $users
        ]);
    }
}
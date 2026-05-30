<?php
// app/Http/Controllers/Api/V1/InboxController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Http\Requests\CreateConversationRequest;
use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\Message;
use App\Models\User;
use App\Models\AktivitasUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// ========== TAMBAHKAN IMPORT EVENT ==========
use App\Events\NewMessageEvent;
use App\Events\UnreadCountUpdatedEvent;

class InboxController extends Controller
{
    // GET ALL CONVERSATIONS FOR CURRENT USER
    public function conversations(Request $request)
    {
        $user = $request->user();
        
        // Simplified query
        $conversations = $user->conversations()
            ->with(['messages' => function($q) {
                $q->latest()->limit(1);
            }])
            ->latest('updated_at')
            ->paginate(20);
        
        return response()->json([
            'message' => 'success',
            'data' => $conversations
        ]);
    }
    
    // GET MESSAGES IN A CONVERSATION
    public function messages(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        
        // Check if user is member of conversation
        if (!$conversation->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke percakapan ini'
            ], 403);
        }
        
        // Get messages
        $messages = $conversation->messages()
            ->with('sender:id,name,email,role')
            ->latest()
            ->paginate($request->per_page ?? 50);
        
        // Mark as read (update last_read_at)
        $member = $conversation->members()->where('user_id', $user->id)->first();
        if ($member && $member->pivot) {
            $member->pivot->update(['last_read_at' => now()]);
        }
        
        return response()->json([
            'message' => 'success',
            'conversation' => [
                'id' => $conversation->id,
                'jenis' => $conversation->jenis,
                'judul' => $conversation->judul,
            ],
            'data' => $messages,
        ]);
    }
    
    // SEND MESSAGE

    public function send(SendMessageRequest $request)
    {
        $user = $request->user();
        
        DB::beginTransaction();
        
        try {
            $conversation = null;
            
            // If conversation_id provided, use existing
            if ($request->has('conversation_id')) {
                $conversation = Conversation::find($request->conversation_id);
                
                // Check if user is member
                if (!$conversation->members()->where('user_id', $user->id)->exists()) {
                    return response()->json([
                        'message' => 'Anda tidak memiliki akses ke percakapan ini'
                    ], 403);
                }
            } 
            // If user_id provided, create new personal conversation
            else {
                $targetUser = User::find($request->user_id);
                
                // Prevent self-chat
                if ($targetUser->id === $user->id) {
                    return response()->json([
                        'message' => 'Tidak bisa mengirim pesan ke diri sendiri'
                    ], 422);
                }
                
                // Check if conversation already exists between these two users
                $existingConversation = Conversation::where('jenis', 'personal')
                    ->whereHas('members', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })
                    ->whereHas('members', function($q) use ($targetUser) {
                        $q->where('user_id', $targetUser->id);
                    })
                    ->first();
                
                if ($existingConversation) {
                    $conversation = $existingConversation;
                } else {
                    // Create new conversation
                    $conversation = Conversation::create([
                        'jenis' => 'personal',
                        'judul' => $request->judul ?? null,
                    ]);
                    
                    // Add members
                    $conversation->members()->attach([
                        $user->id => ['last_read_at' => now()],
                        $targetUser->id => ['last_read_at' => null],
                    ]);
                }
            }
            
            // Create message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'is_system' => false,
                'body' => $request->body,
                'meta' => null,
            ]);
            
            // Update conversation updated_at
            $conversation->touch();
            
            // Update current user's last_read_at for this conversation
            $conversation->members()->updateExistingPivot($user->id, ['last_read_at' => now()]);
            
            // Log aktivitas
            AktivitasUser::log(
                $user,
                'send_message',
                'Message',
                $message->id,
                "Mengirim pesan ke conversation #{$conversation->id}"
            );
            
            DB::commit();
            
            // ========== BROADCAST EVENTS ==========
            // Broadcast event new message
            broadcast(new NewMessageEvent($message, $conversation->id));
            
            // Get all members in conversation and broadcast unread count update
            $members = $conversation->members()->get();
            foreach ($members as $member) {
                $unreadCount = $this->getUserUnreadCount($member->id);
                broadcast(new UnreadCountUpdatedEvent($member->id, $unreadCount));
            }
            
            return response()->json([
                'message' => 'Pesan berhasil dikirim',
                'data' => $message->load('sender:id,name,email,role')
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Gagal mengirim pesan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // MARK CONVERSATION AS READ
    public function markAsRead(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        
        // Check if user is member
        if (!$conversation->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke percakapan ini'
            ], 403);
        }
        
        // Update last_read_at
        $conversation->members()->updateExistingPivot($user->id, ['last_read_at' => now()]);
        
        // ========== BROADCAST UNREAD COUNT UPDATE ==========
        $unreadCount = $this->getUserUnreadCount($user->id);
        broadcast(new UnreadCountUpdatedEvent($user->id, $unreadCount));
        
        return response()->json([
            'message' => 'Percakapan ditandai sudah dibaca'
        ]);
    }
    
    // GET UNREAD COUNT (for badge/navigation)
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        
        $unreadCount = $this->getUserUnreadCount($user->id);
        
        return response()->json([
            'message' => 'success',
            'data' => [
                'total_unread' => $unreadCount,
            ]
        ]);
    }
    
    // GET SYSTEM MESSAGES ONLY (filtered)
    public function systemMessages(Request $request)
    {
        $user = $request->user();
        
        $conversation = Conversation::where('jenis', 'system')->first();
        
        if (!$conversation) {
            return response()->json([
                'message' => 'success',
                'data' => []
            ]);
        }
        
        // Check if user is member
        if (!$conversation->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses'
            ], 403);
        }
        
        $messages = $conversation->messages()
            ->where('is_system', true)
            ->latest()
            ->paginate($request->per_page ?? 50);
        
        // Mark as read
        $conversation->members()->updateExistingPivot($user->id, ['last_read_at' => now()]);
        
        // ========== BROADCAST UNREAD COUNT UPDATE ==========
        $unreadCount = $this->getUserUnreadCount($user->id);
        broadcast(new UnreadCountUpdatedEvent($user->id, $unreadCount));
        
        return response()->json([
            'message' => 'success',
            'data' => $messages
        ]);
    }
    
    // DELETE MESSAGE (soft delete/hide for user)
    // For simplicity, we'll hard delete, but only sender can delete
    public function deleteMessage(Request $request, Message $message)
    {
        $user = $request->user();
        
        // Only sender can delete their own message
        if ($message->sender_id !== $user->id && !$user->isOwner()) {
            return response()->json([
                'message' => 'Anda tidak memiliki izin menghapus pesan ini'
            ], 403);
        }
        
        // Don't delete system messages if not owner
        if ($message->is_system && !$user->isOwner()) {
            return response()->json([
                'message' => 'Pesan sistem tidak bisa dihapus'
            ], 403);
        }
        
        $message->delete();
        
        AktivitasUser::log(
            $user,
            'delete_message',
            'Message',
            $message->id,
            "Menghapus pesan"
        );
        
        // ========== BROADCAST UNREAD COUNT UPDATE ==========
        $conversation = $message->conversation;
        $members = $conversation->members()->get();
        foreach ($members as $member) {
            $unreadCount = $this->getUserUnreadCount($member->id);
            broadcast(new UnreadCountUpdatedEvent($member->id, $unreadCount));
        }
        
        return response()->json([
            'message' => 'Pesan berhasil dihapus'
        ]);
    }
    
    // SEARCH CONVERSATIONS
    public function search(Request $request)
    {
        $user = $request->user();
        $keyword = $request->get('q');
        
        if (!$keyword) {
            return response()->json([
                'message' => 'Keyword pencarian tidak boleh kosong'
            ], 422);
        }
        
        $conversations = $user->conversations()
            ->whereHas('messages', function($q) use ($keyword) {
                $q->where('body', 'like', "%{$keyword}%");
            })
            ->with([
                'messages' => function($q) use ($keyword) {
                    $q->where('body', 'like', "%{$keyword}%")->latest()->limit(3);
                },
                'members'
            ])
            ->get();
        
        $result = $conversations->map(function($conversation) use ($user, $keyword) {
            $matchedMessages = $conversation->messages;
            $otherMembers = $conversation->members->filter(function($member) use ($user) {
                return $member->id !== $user->id;
            });
            
            return [
                'id' => $conversation->id,
                'judul' => $conversation->judul ?? ($otherMembers->first()?->name ?? 'System'),
                'matched_messages' => $matchedMessages->map(function($message) {
                    return [
                        'body' => $message->body,
                        'created_at' => $message->created_at,
                    ];
                }),
            ];
        });
        
        return response()->json([
            'message' => 'success',
            'data' => $result
        ]);
    }
    
    // ========== HELPER METHOD ==========
    
    /**
     * Hitung jumlah pesan belum dibaca untuk user tertentu
     */
    private function getUserUnreadCount($userId)
    {
        $conversations = Conversation::whereHas('members', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->get();
        
        $totalUnread = 0;
        
        foreach ($conversations as $conversation) {
            $lastRead = $conversation->members()
                ->where('user_id', $userId)
                ->first()
                ?->pivot
                ->last_read_at;
            
            $unreadCount = Message::where('conversation_id', $conversation->id)
                ->when($lastRead, function($q) use ($lastRead) {
                    $q->where('created_at', '>', $lastRead);
                })
                ->count();
            
            $totalUnread += $unreadCount;
        }
        
        return $totalUnread;
    }
}
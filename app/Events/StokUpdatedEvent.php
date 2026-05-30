<?php
// app/Events/StokUpdatedEvent.php

namespace App\Events;

use App\Models\Barang;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StokUpdatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $barang;
    public $userId;
    public $jenis;
    public $jumlah;

    public function __construct(Barang $barang, $userId, $jenis, $jumlah)
    {
        $this->barang = $barang;
        $this->userId = $userId;
        $this->jenis = $jenis;
        $this->jumlah = $jumlah;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('stok-updates');
    }

    public function broadcastAs()
    {
        return 'stok-updated';
    }

    public function broadcastWith()
    {
        return [
            'barang_id' => $this->barang->id,
            'barang_nama' => $this->barang->nama,
            'stok_baru' => $this->barang->stok,
            'jenis' => $this->jenis,
            'jumlah' => $this->jumlah,
            'updated_at' => now()->toISOString(),
        ];
    }
}
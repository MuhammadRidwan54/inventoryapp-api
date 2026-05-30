<?php
// app/Events/StokMenipisEvent.php

namespace App\Events;

use App\Models\Barang;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StokMenipisEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $barang;

    public function __construct(Barang $barang)
    {
        $this->barang = $barang;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('stok-alerts');
    }

    public function broadcastAs()
    {
        return 'stok-menipis';
    }

    public function broadcastWith()
    {
        return [
            'barang_id' => $this->barang->id,
            'barang_nama' => $this->barang->nama,
            'stok_saat_ini' => $this->barang->stok,
            'stok_minimal' => $this->barang->stok_minimal,
            'alert' => "Stok {$this->barang->nama} menipis!",
        ];
    }
}
<?php
// app/Services/CloudinaryService.php

namespace App\Services;

use Cloudinary\Cloudinary;

class CloudinaryService
{
    protected $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => [
                'secure' => true
            ]
        ]);
    }

    /**
     * Upload file ke Cloudinary
     */
    public function upload($file, array $options = [])
    {
        $defaultOptions = [
            'folder' => 'inventoryapp/barang',
            'resource_type' => 'image',
        ];

        $options = array_merge($defaultOptions, $options);

        $result = $this->cloudinary->uploadApi()->upload($file->getRealPath(), $options);

        return (object) [
            'secure_url' => $result['secure_url'],
            'public_id'  => $result['public_id'],
        ];
    }

    /**
     * Delete file dari Cloudinary
     */
    public function delete(string $publicId)
    {
        return $this->cloudinary->uploadApi()->destroy($publicId);
    }
}
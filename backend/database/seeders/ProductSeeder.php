<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Wireless Mouse',        'sku' => 'ELC-001', 'description' => 'Ergonomic wireless mouse with USB receiver.',         'price' => 25.99,  'stock_quantity' => 150],
            ['name' => 'Mechanical Keyboard',   'sku' => 'ELC-002', 'description' => 'Full-size mechanical keyboard with RGB backlighting.', 'price' => 89.99,  'stock_quantity' => 60],
            ['name' => 'USB-C Hub',             'sku' => 'ELC-003', 'description' => '7-in-1 USB-C hub with HDMI and card reader.',          'price' => 45.00,  'stock_quantity' => 8],
            ['name' => 'Monitor Stand',         'sku' => 'DSK-001', 'description' => 'Adjustable monitor stand with cable management.',      'price' => 34.50,  'stock_quantity' => 0],
            ['name' => 'Laptop Stand',          'sku' => 'DSK-002', 'description' => 'Aluminium foldable laptop stand.',                     'price' => 29.99,  'stock_quantity' => 5],
            ['name' => 'Webcam HD 1080p',       'sku' => 'CAM-001', 'description' => 'Full HD webcam with built-in microphone.',             'price' => 59.99,  'stock_quantity' => 40],
            ['name' => 'Noise-Cancelling Headphones', 'sku' => 'AUD-001', 'description' => 'Over-ear headphones with ANC.',                 'price' => 149.99, 'stock_quantity' => 25],
            ['name' => 'Desk Lamp LED',         'sku' => 'LGT-001', 'description' => 'Adjustable LED desk lamp with USB charging port.',    'price' => 19.99,  'stock_quantity' => 3],
            ['name' => 'Mouse Pad XL',          'sku' => 'DSK-003', 'description' => 'Extra-large desk mouse pad 900x400mm.',               'price' => 12.99,  'stock_quantity' => 200],
            ['name' => 'Cable Organiser Kit',   'sku' => 'DSK-004', 'description' => 'Set of 10 reusable cable ties and clips.',            'price' => 7.99,   'stock_quantity' => 10],
        ];

        foreach ($products as $data) {
            Product::firstOrCreate(['sku' => $data['sku']], $data);
        }
    }
}

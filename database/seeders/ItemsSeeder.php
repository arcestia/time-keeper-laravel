<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StoreItem;

class ItemsSeeder extends Seeder
{
    public function run(): void
    {
        $foods = [
            ['key' => 'apple',        'name' => 'Apple',        'desc' => 'Fresh red apple',          'price' => 120, 'food' => 10, 'energy' => 2],
            ['key' => 'bread',        'name' => 'Bread',        'desc' => 'Loaf of bread',            'price' => 240, 'food' => 18, 'energy' => 3],
            ['key' => 'sandwich',     'name' => 'Sandwich',     'desc' => 'Ham and cheese',           'price' => 360, 'food' => 25, 'energy' => 4],
            ['key' => 'salad',        'name' => 'Salad',        'desc' => 'Green veggie salad',       'price' => 300, 'food' => 20, 'energy' => 3],
            ['key' => 'pasta',        'name' => 'Pasta',        'desc' => 'Bowl of pasta',            'price' => 480, 'food' => 30, 'energy' => 5],
            ['key' => 'rice_bowl',    'name' => 'Rice Bowl',    'desc' => 'Hearty rice bowl',         'price' => 420, 'food' => 28, 'energy' => 4],
            ['key' => 'soup',         'name' => 'Soup',         'desc' => 'Warm soup',                'price' => 240, 'food' => 16, 'energy' => 3],
            ['key' => 'steak',        'name' => 'Steak',        'desc' => 'Grilled steak',            'price' => 720, 'food' => 40, 'energy' => 6],
            ['key' => 'energy_bar',   'name' => 'Energy Bar',   'desc' => 'Quick bite for energy',    'price' => 180, 'food' => 8,  'energy' => 6],
            ['key' => 'omelette',     'name' => 'Omelette',     'desc' => 'Cheese omelette',          'price' => 360, 'food' => 22, 'energy' => 4],
        ];

        foreach ($foods as $f) {
            $amount = (int)$f['food'];
            // Base: 60 seconds per food point, with a volume discount up to 30%
            $discount = 1 - min(0.30, max(0, $amount) * 0.01); // +1% off per point capped at 30%
            $price = (int) round($amount * 60 * $discount);
            StoreItem::updateOrCreate(
                ['key' => $f['key']],
                [
                    'name' => $f['name'],
                    'type' => 'food',
                    'description' => $f['desc'],
                    'price_seconds' => max(1, $price),
                    'quantity' => 100,
                    'restore_food' => $f['food'],
                    'restore_water' => 0,
                    'restore_energy' => $f['energy'],
                    'is_active' => true,
                ]
            );
        }

        $waters = [
            ['key' => 'water_small',  'name' => 'Water (250ml)',  'desc' => 'Small bottle',        'price' => 60,  'water' => 10],
            ['key' => 'water_med',    'name' => 'Water (500ml)',  'desc' => 'Medium bottle',       'price' => 120, 'water' => 18],
            ['key' => 'water_large',  'name' => 'Water (1L)',     'desc' => 'Large bottle',        'price' => 180, 'water' => 28],
            ['key' => 'sport_small',  'name' => 'Isotonic (250ml)','desc' => 'Light electrolytes', 'price' => 120, 'water' => 16],
            ['key' => 'sport_med',    'name' => 'Isotonic (500ml)','desc' => 'Rehydrate fast',     'price' => 180, 'water' => 24],
            ['key' => 'tea',          'name' => 'Tea',            'desc' => 'Warm tea',            'price' => 120, 'water' => 14],
            ['key' => 'coffee',       'name' => 'Coffee',         'desc' => 'Black coffee',        'price' => 150, 'water' => 10],
            ['key' => 'juice',        'name' => 'Juice',          'desc' => 'Fruit juice',         'price' => 180, 'water' => 18],
            ['key' => 'smoothie',     'name' => 'Smoothie',       'desc' => 'Fruit smoothie',      'price' => 240, 'water' => 22],
            ['key' => 'sparkling',    'name' => 'Sparkling Water','desc' => 'Bubbly mineral',     'price' => 150, 'water' => 16],
        ];

        foreach ($waters as $w) {
            $amount = (int)$w['water'];
            // Base: 30 seconds per water point, with a volume discount up to 30%
            $discount = 1 - min(0.30, max(0, $amount) * 0.01);
            $price = (int) round($amount * 30 * $discount);
            StoreItem::updateOrCreate(
                ['key' => $w['key']],
                [
                    'name' => $w['name'],
                    'type' => 'water',
                    'description' => $w['desc'],
                    'price_seconds' => max(1, $price),
                    'quantity' => 150,
                    'restore_food' => 0,
                    'restore_water' => $w['water'],
                    'restore_energy' => 0,
                    'is_active' => true,
                ]
            );
        }
    }
}

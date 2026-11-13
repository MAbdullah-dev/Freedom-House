<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            'MENTAL HEALTH',
            'ETHICS',
            'SUBSTANCE USE DISORDER',
        ] as $categoryName) {
            Category::query()->firstOrCreate([
                'category_name' => $categoryName,
            ]);
        }
    }
}

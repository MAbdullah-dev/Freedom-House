<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            'Featured',
            'New',
            'Most Popular',
            'Trending',
            'Top Rated',
            'Editor\'s Choice',
            'Best Seller',
        ] as $tagName) {
            Tag::query()->firstOrCreate([
                'name' => $tagName,
            ]);
        }
    }
}

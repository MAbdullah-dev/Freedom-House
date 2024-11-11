<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            ['name' => 'Laravel'],
            ['name' => 'PHP'],
            ['name' => 'JavaScript'],
            ['name' => 'Vue.js'],
            ['name' => 'React'],
            ['name' => 'CSS'],
            ['name' => 'HTML'],
            ['name' => 'MySQL'],
            ['name' => 'SEO'],
            ['name' => 'Web Development'],
        ];
        foreach ($tags as $tag) {
            Tag::create($tag);
        }
    }
}

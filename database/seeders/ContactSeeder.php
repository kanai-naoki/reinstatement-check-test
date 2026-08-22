<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = Tag::all();

        Contact::factory(20)->create()->each(function ($contact) use ($tags) {
            $contact->tags()->attach(
                $tags->random(rand(1, 3))->pluck('id')
            );
        });
    }
}

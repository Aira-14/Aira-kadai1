<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Faker\Factory;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        // 日本語ロケールのFakerを初期化
        $faker = Factory::create('ja_JP');

        $categories = Category::all();
        $tags = Tag::all();

        // 20件のダミーデータを投入
        for ($i = 0; $i < 20; $i++) {
            $contact = Contact::create([
                'category_id' => $categories->random()->id,
                'first_name' => $faker->lastName,
                'last_name' => $faker->firstName,
                'gender' => $faker->numberBetween(1, 3),
                'email' => $faker->safeEmail,
                'tel' => $faker->numerify($faker->randomElement(['090########', '080########', '03########'])),
                'address' => $faker->prefecture.$faker->city.$faker->streetAddress,
                'building' => $faker->randomElement([$faker->secondaryAddress, null]),
                'detail' => $faker->realText(50),
            ]);

            // 各お問い合わせに対し、既存タグからランダムに1〜3件をランダムに選んで紐付け
            $randomTags = $tags->random(rand(1, 3))->pluck('id')->toArray();
            $contact->tags()->attach($randomTags);
        }
    }
}

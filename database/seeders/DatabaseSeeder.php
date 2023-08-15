<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $faker = \Faker\Factory::create();
        User::factory(100)->create()->each(function ($user) use ($faker) {

            $user->detail()->create([
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName,
                'birthday' => $faker->dateTimeBetween('1990-01-01', '2012-12-31'),
                'phone_no' => $faker->numerify('###-###-####'),
                'about_me' => $faker->text,
                'gender' => $faker->randomElement(['male', 'female']),
                'age' => $faker->numberBetween($min = 18, $max = 59),
                'country' => $faker->country(),
            ]);
            $array = [1, 2, 3, 4];
            if (($key = array_search($user->id, $array)) !== false) {
                unset($array[$key]);
            }
            $user->follow(User::find($faker->randomElement($array)));
            $user->subscribe(User::find($faker->randomElement($array)));
        });

        // for accepting all request
        DB::table('followables')->update([
            'accepted_at' => now()
        ]);

        Artisan::call('passport:install');
    }
}

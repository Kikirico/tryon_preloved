<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        foreach ([['Sage Everyday Shirt', 'Uniqlo', 'M', 149000, 'sage'], ['The Sunday Knit', 'H&M', 'L', 129000, 'cream'], ['Midnight Overshirt', 'Zara', 'L', 189000, 'navy'], ['Terracotta Essential', 'Mango', 'S', 99000, 'clay']] as [$name, $brand, $size, $price, $color]) {
            \App\Models\Product::firstOrCreate(['name'=>$name], ['brand'=>$brand, 'size'=>$size, 'price'=>$price, 'condition'=>'Koleksi demo', 'description'=>'Produk ilustrasi untuk pratinjau katalog. Ganti dengan foto, ukuran aktual, dan kondisi pakaian asli sebelum toko dibuka.', 'photo'=>"demo/$color.svg"]);
        }
    }
}

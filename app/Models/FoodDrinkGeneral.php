<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodDrinkGeneral extends Model
{
    use HasFactory;

    // Explicitly define the master table name
    protected $table = 'food_drink_general';

    // The primary key is a standard auto-incrementing id for the master catalog
    protected $primaryKey = 'id';

    // Form fields allowed for mass-assignment insertion
    protected $fillable = [
        'name',
        'category_tag',
        'image_path',
        'suggested_price'
    ];
}
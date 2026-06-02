<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchFoodDrinkInventory extends Model
{
    use HasFactory;

    protected $table = 'branch_food_drink_inventory';

    // Since this table has a composite primary key (cinema_id, food_drink_id),
    // Laravel needs to know that these are not auto-incrementing integers.
    public $incrementing = false;
    protected $keyType = 'array'; // Optional: helps with specific composite key packages

    protected $fillable = [
        'cinema_id',
        'food_drink_id',
        'price',
        'status'
    ];

    /**
     * Relationship to the Cinema
     */
    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'cinema_id', 'cinema_id');
    }

    /**
     * Relationship to the Master Food Table
     */
    public function food()
    {
        return $this->belongsTo(FoodDrinkGeneral::class, 'food_drink_id', 'id');
    }
}
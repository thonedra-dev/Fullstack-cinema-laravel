<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    protected $table      = 'genres';
    protected $primaryKey = 'genre_id';
    public    $timestamps = true;

    protected $fillable = ['genre_name'];

    /**
     * A genre belongs to many movies via movie_genres pivot.
     */
    public function movies()
    {
        return $this->belongsToMany(
            Movie::class,
            'movie_genres',
            'genre_id',
            'movie_id'
        );
    }
}
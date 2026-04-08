<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trailer extends Model
{
    protected $table      = 'trailers';
    protected $primaryKey = 'trailer_id';
    public    $timestamps = true;

    protected $fillable = [
        'movie_id',
        'youtube_url',
        'type',
    ];

    /**
     * Extract a YouTube video ID from any common YouTube URL format.
     * Supports: youtube.com/watch?v=, youtu.be/, youtube.com/embed/
     *
     * @return string|null
     */
    public function getVideoIdAttribute(): ?string
    {
        $url = $this->youtube_url;
        if (!$url) return null;

        // youtu.be/VIDEO_ID
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_\-]{11})/', $url, $m)) {
            return $m[1];
        }

        // youtube.com/watch?v=VIDEO_ID  or  youtube.com/embed/VIDEO_ID
        if (preg_match('/[?&\/](?:v=|embed\/)([a-zA-Z0-9_\-]{11})/', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Return a clean YouTube embed URL for use in an <iframe>.
     *
     * @return string|null
     */
    public function getEmbedUrlAttribute(): ?string
    {
        $id = $this->video_id;
        return $id ? 'https://www.youtube.com/embed/' . $id : null;
    }

    public function movie()
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'movie_id');
    }
}
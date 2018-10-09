<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Models;

use Illuminate\Database\Eloquent\Model;

class ScrapedDataset extends Model
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    public $casts = ['data' => 'json'];

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'url';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'url',
        'type',
        'variant',
        'data',
    ];

    public function scopeWithType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeWithVariant($query, string $variant)
    {
        return $query->where('variant', $variant);
    }
}

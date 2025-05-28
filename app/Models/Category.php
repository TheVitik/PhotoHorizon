<?php

namespace App\Models;

use Vinelab\NeoEloquent\Eloquent\Model;

class Category extends Model
{
    protected $connection = 'neo4j';

    protected $label = 'Category';

    protected $fillable = [
      'Id',
      'Name'
    ];

    public function scopeFindUuid($query, $uuid)
    {
        return $query->where('Id', $uuid)->first();
    }

    // Relationships
    public function photos()
    {
        return $this->belongsToMany(Photo::class, 'IN_CATEGORY');
    }

    public function contests()
    {
        return $this->belongsToMany(Contest::class, 'ACCEPTS');
    }
}
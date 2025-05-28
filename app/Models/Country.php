<?php

namespace App\Models;

use Vinelab\NeoEloquent\Eloquent\Model;

class Country extends Model
{
    protected $connection = 'neo4j';

    protected $label = 'Country';

    protected $fillable = [
      'Id',
      'Name'
    ];

    public function scopeFindUuid($query, $uuid)
    {
        return $query->where('Id', $uuid)->first();
    }

    // Relationships
    public function regions()
    {
        return $this->belongsToMany(Region::class, 'IN_COUNTRY');
    }
}
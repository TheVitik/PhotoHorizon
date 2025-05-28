<?php

namespace App\Models;

use Vinelab\NeoEloquent\Eloquent\Model;

class Region extends Model
{
    protected $connection = 'neo4j';

    protected $label = 'Region';

    protected $fillable = [
      'Id',
      'Name'
    ];

    public function scopeFindUuid($query, $uuid)
    {
        return $query->where('Id', $uuid)->first();
    }

    // Relationships
    public function country()
    {
        return $this->hasOne(Country::class, 'IN_COUNTRY');
    }

    public function cities()
    {
        return $this->belongsToMany(City::class, 'IN_REGION');
    }
}
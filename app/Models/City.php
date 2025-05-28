<?php

namespace App\Models;

use Vinelab\NeoEloquent\Eloquent\Model;

class City extends Model
{
    protected $connection = 'neo4j';

    protected $label = 'City';

    protected $fillable = [
      'Id',
      'Name',
      'Latitude',
      'Longitude'
    ];

    public function scopeFindUuid($query, $uuid)
    {
        return $query->where('Id', $uuid)->first();
    }

    // Relationships
    public function region()
    {
        return $this->hasOne(Region::class, 'IN_REGION');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'LIVE');
    }
}
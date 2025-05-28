<?php

namespace App\Models;

use Vinelab\NeoEloquent\Eloquent\Model;

class Contest extends Model
{
    protected $connection = 'neo4j';

    protected $label = 'Contest';

    protected $dateFormat = 'Y-m-d\TH:i:s';

    protected $fillable = [
      'Id',
      'Name',
      'PhotoPath',
      'Description',
      'StartDateTime',
      'EndDateTime'
    ];

    protected $dates = ['StartDateTime', 'EndDateTime'];

    public function scopeFindUuid($query, $uuid)
    {
        return $query->where('Id', $uuid)->first();
    }

    // Relationships
    public function organizers()
    {
        return $this->belongsToMany(User::class, 'HOLDS');
    }

    public function photos()
    {
        return $this->belongsToMany(Photo::class, 'PARTICIPATES_IN');
    }
}
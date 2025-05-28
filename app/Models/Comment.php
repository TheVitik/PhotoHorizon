<?php

namespace App\Models;

use Vinelab\NeoEloquent\Eloquent\Model;

class Comment extends Model
{
    protected $connection = 'neo4j';

    protected $label = 'Comment';

    protected $dateFormat = 'Y-m-d\TH:i:s';

    protected $fillable = [
      'Id',
      'Text'
    ];

    public function scopeFindUuid($query, $uuid)
    {
        return $query->where('Id', $uuid)->first();
    }

    // Relationships
    public function user()
    {
        return $this->hasOne(User::class, 'BELONGS');
    }

    public function photo()
    {
        return $this->hasOne(Photo::class, 'COMMENTED_ON');
    }
}
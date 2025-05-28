<?php

namespace App\Models;

use Vinelab\NeoEloquent\Eloquent\Model;

class Contact extends Model
{
    protected $connection = 'neo4j';

    protected $label = 'Contact';

    protected $fillable = [
      'Id',
      'Type',
      'Handle'
    ];

    public function scopeFindUuid($query, $uuid)
    {
        return $query->where('Id', $uuid)->first();
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'HAS_CONTACT');
    }
}
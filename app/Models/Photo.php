<?php

namespace App\Models;

use Vinelab\NeoEloquent\Eloquent\Model;

class Photo extends Model
{
    protected $connection = 'neo4j';

    protected $label = 'Photo';

    protected $dateFormat = 'Y-m-d\TH:i:s';

    protected $fillable = [
      'Id',
      'Path',
      'Description',
      'CreationDate',
      'LocationLatitude',
      'LocationLongitude',
      'LikesCount'
    ];

    protected $dates = ['CreationDate'];

    public function scopeFindUuid($query, $uuid)
    {
        return $query->where('Id', $uuid)->first();
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'UPLOADED');
    }

    public function comments()
    {
        return $this->belongsToMany(Comment::class, 'COMMENTED_ON');
    }

    public function contests()
    {
        return $this->hasMany(Contest::class, 'PARTICIPATES_IN');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'IN_CATEGORY');
    }
}

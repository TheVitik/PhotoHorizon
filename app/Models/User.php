<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Vinelab\NeoEloquent\Eloquent\Model;
use Vinelab\NeoEloquent\Eloquent\Relations\BelongsToMany;
use Vinelab\NeoEloquent\Eloquent\Relations\HasMany;
use Vinelab\NeoEloquent\Eloquent\Relations\HasOne;

class User extends Model
{
    use HasApiTokens;

    protected $connection = 'neo4j';

    protected $label = 'User';

    protected $dateFormat = 'Y-m-d\TH:i:s';

    protected $fillable = [
      'Id',
      'Name',
      'AvatarPath',
      'PasswordHash',
      'Email',
      'RegisteredAt',
      'Bio'
    ];

    protected $dates = ['RegisteredAt'];

    public function scopeFindUuid($query, $uuid)
    {
        return $query->where('Id', $uuid)->first();
    }

    // Relationships
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class, 'UPLOADED');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'BELONGS');
    }

    public function contests(): HasMany
    {
        return $this->hasMany(Contest::class, 'HOLDS');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'HAS_CONTACT');
    }

    public function city(): HasOne
    {
        return $this->hasOne(City::class, 'LIVE');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'FOLLOWS', 'FOLLOWED_BY', 'FOLLOWS');
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'FOLLOWS', 'FOLLOWS', 'FOLLOWED_BY');
    }

}
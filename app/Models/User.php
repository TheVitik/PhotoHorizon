<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
      'Bio',
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
        return $this->belongsToMany(User::class, 'FOLLOWS');
    }

    public function following(): Collection
    {
        $results = DB::connection('neo4j')->select(
          "MATCH (user:User {Id: '$this->Id'})-[:FOLLOWS]->(user2:User) RETURN user2",
        );

        return collect($results->toArray())->map(function ($row) {
            return new User($row->get('user2')->properties()->toArray());
        });
    }

    public function follow(User $user)
    {
        $result = DB::connection('neo4j')->select(
          "MATCH (user:User {Id: '$this->Id'})-[r:FOLLOWS]->(user2:User {Id: '$user->Id'}) RETURN count(r) > 0 AS exists;",
        );

        $exists = false;
        if (!empty($result) && isset($result[0]['exists'])) {
            $exists = (bool) $result[0]['exists'];
        }

        if(!$exists){
            DB::connection('neo4j')->select(
              "MATCH (user:User {Id: '$this->Id'}), (user2:User {Id: '$user->Id'}) MERGE (user)-[:FOLLOWS]->(user2)",
            );
        }
    }

    public function unfollow(User $user)
    {
        DB::connection('neo4j')->select(
          "MATCH (user:User {Id: '$this->Id'})-[r:FOLLOWS]->(user2:User {Id: '$user->Id'}) DELETE r",
        );
    }

}
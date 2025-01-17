<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model implements Authenticatable
{
    use HasFactory;
    protected $table = "users";
    protected $keyType = "int";
    protected $primaryKey = "id";
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        "name",
        "username",
        "password"
    ];
    public function contact(): HasMany
    {
        return $this->hasMany(Contact::class, "user_id", "id");

    }
    function getAuthIdentifier()
    {
        // idntifire untuk otentikasi
        return $this->username;

    }
    function getAuthIdentifierName()
    {
        return "username";

    }
    function getAuthPassword()
    {
        return $this->password;

    }
    function getRememberToken()
    {
        return $this->token;

    }
    function getRememberTokenName()
    {

        return "token";
    }
    function setRememberToken($value)
    {
        $this->token = $value;

    }



}


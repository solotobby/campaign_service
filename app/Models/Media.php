<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class Media  extends Model
{
    // use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = "media";

    protected $fillable = [
        'name', 'url'
    ];

}
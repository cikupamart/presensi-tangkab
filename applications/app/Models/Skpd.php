<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skpd extends Model
{
    protected $table = 'preson_skpd';

    protected $fillable = ['nama','singkatan','status','actor'];
}

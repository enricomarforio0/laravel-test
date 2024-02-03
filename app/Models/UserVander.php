<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserVander extends Model
{
    use HasFactory;

    public $nome;
    public $cognome;
    public $id;
}

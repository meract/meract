<?php
namespace App\Models;
use Meract\Core\Model;
class User extends Model
{
    protected static $table = 'users';
    protected $fillable = ['id', 'name', 'message'];
}

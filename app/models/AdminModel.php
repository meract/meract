<?php
namespace App\Models;

use Meract\Core\Model;

class AdminModel extends Model 
{
	protected static $table = 'admins';
	protected $primarykey = 'id';
    protected $fillable = [
		'id',
		'name',
		'age',
		'mail',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    public const ADMIN = 'Admin';

    public const TUTOR = 'Tutor';

    public const LEARNER = 'Learner';

    protected $fillable = ['role_name'];

    public static function findByName(string $roleName): ?self
    {
        return static::query()->where('role_name', $roleName)->first();
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'active',
        'firstName',
        'middleName',
        'lastName',
        'phone',
        'email',
        'gender',
        'address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get the user associated with this employee.
     */
//    public function user()
//    {
//        return $this->hasOne(User::class);
//    }

    /**
     * Get the full name of the employee.
     */
    public function getFullNameAttribute()
    {
        $name = $this->firstName;

        if ($this->middleName) {
            $name .= ' ' . $this->middleName;
        }

        $name .= ' ' . $this->lastName;

        return $name;
    }
}

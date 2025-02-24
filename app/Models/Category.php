<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'desc',
    ];

    /**
     * posts
     *
     * @return void
     */

    public function suratmasuk()
    {
        return $this->hasMany(SuratMasuk::class);
    }

    public function suratkeluar()
    {
        return $this->hasMany(SuratKeluar::class);
    }
}

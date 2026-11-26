<?php

namespace App\Models\Vistas;

use Illuminate\Database\Eloquent\Model;
use LogicException;

// Base de los modelos que leen una vista SQL: se pueden consultar con
// Eloquent, pero no escribir.
abstract class VistaSoloLectura extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['*'];

    private function soloLectura(): never
    {
        throw new LogicException(
            'El modelo ' . static::class . ' lee una vista SQL y es de solo lectura.'
        );
    }

    public function save(array $options = [])
    {
        $this->soloLectura();
    }

    public function update(array $attributes = [], array $options = [])
    {
        $this->soloLectura();
    }

    public function delete()
    {
        $this->soloLectura();
    }

    public function forceDelete()
    {
        $this->soloLectura();
    }
}

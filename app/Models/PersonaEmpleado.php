<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonaEmpleado extends Model
{
    protected $connection = 'sqlsrv_vpn';
    protected $table = 'VN_PERSONAEMPLEADO';
    protected $primaryKey = 'IdEmpleado';
    public $timestamps = false;

    public function detallesLiquidacion()
    {
        return $this->hasMany(DetalleLiquidacion::class, 'IdEmpleado', 'IdEmpleado');
    }
}

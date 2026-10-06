<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleLiquidacion extends Model
{
    protected $connection = 'sqlsrv_vpn';
    protected $table = 'VN_DETALLELIQUIDACION';
    protected $primaryKey = 'IdDetalleLiquidacion';
    public $timestamps = false;

    public function solicitud()
    {
        return $this->belongsTo(SolicitudLiquidacion::class, 'IdSolicitudLiquidacion', 'IdSolicitudLiquidacion');
    }
}

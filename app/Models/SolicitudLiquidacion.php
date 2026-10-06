<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudLiquidacion extends Model
{
    protected $connection = 'sqlsrv_vpn';
    protected $table = 'VN_SOLICITUDLIQUIDACION';
    protected $primaryKey = 'IdSolicitudLiquidacion';
    public $timestamps = false;
}

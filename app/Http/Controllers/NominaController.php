<?php

namespace App\Http\Controllers;

use App\Services\ReporteNominaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class NominaController extends Controller
{
    /**
     * Muestra la vista principal que contiene el componente Livewire.
     */
    public function index()
    {
        return view('nomina.index');
    }
    public function dashboard()
    {
        return view('nomina.dashboard');
    }
}

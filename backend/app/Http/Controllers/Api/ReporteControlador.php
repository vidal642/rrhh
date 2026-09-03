<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DynamicReportExport;
use Illuminate\Support\Facades\Log;

class ReporteControlador extends Controller
{
    /**
     * Genera y descarga un archivo Excel genérico con el formato DynamicReportExport
     */
    public function exportarExcel(Request $request)
    {
        try {
            $request->validate([
                'titulo'    => 'required|string',
                'cabeceras' => 'required|array',
                'datos'     => 'required|array'
            ]);

            $export = new DynamicReportExport(
                $request->datos,
                $request->cabeceras,
                $request->titulo,
                'CONSTRUCTORA FLORES'
            );

            $safeTitle = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($request->titulo));
            $filename = $safeTitle . '_' . date('Ymd_His') . '.xlsx';

            return Excel::download($export, $filename);
        } catch (\Exception $e) {
            Log::error('Error al exportar Excel genérico: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al generar el reporte Excel'], 500);
        }
    }
}

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ─── Controladores ────────────────────────────────────────────────────────────
use App\Http\Controllers\Api\AutenticacionControlador;
use App\Http\Controllers\Api\EmpleadoControlador;
use App\Http\Controllers\Api\DepartamentoControlador;
use App\Http\Controllers\Api\CargoControlador;
use App\Http\Controllers\Api\AsistenciaControlador;
use App\Http\Controllers\Api\PlanillaControlador;
use App\Http\Controllers\Api\AusenciaControlador;
use App\Http\Controllers\Api\AdelantoControlador;
use App\Http\Controllers\Api\UsuarioControlador;
use App\Http\Controllers\Api\PanelControlador;
use App\Http\Controllers\Api\ConfiguracionControlador;
use App\Http\Controllers\Api\ReconocimientoFacialController;


Route::post('/iniciar-sesion', [AutenticacionControlador::class, 'iniciarSesion'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/cerrar-sesion', [AutenticacionControlador::class, 'cerrarSesion']);
});

Route::middleware('auth:sanctum')->group(function () {

    // ─── Usuario autenticado ──────────────────────────────────────────────────
    Route::get('/user',   fn(Request $r) => $r->user());
    Route::get('/perfil', [AutenticacionControlador::class, 'perfil']);


    // ─── Panel / Dashboard ────────────────────────────────────────────────────
    Route::get('/dashboard', [PanelControlador::class, 'index']);

    // ─── Departamentos y Cargos (Lectura pública para empleados) ──────────────
    Route::get('/departamentos/{id}/cargos', [DepartamentoControlador::class, 'cargos']);

    // 🔹 Asistencia 🔹
    Route::get('/asistencia/resumen-hoy', [AsistenciaControlador::class, 'resumenHoy']);
    Route::get('/asistencia/reporte-historial', [AsistenciaControlador::class, 'reporteHistorial']);
    Route::apiResource('asistencia', AsistenciaControlador::class);

    // ─── Planillas ──────────────────────────
    Route::get('/planillas/resumen',              [PlanillaControlador::class, 'resumenMensual']);
    Route::get('/planillas/calcular-asistencia',  [PlanillaControlador::class, 'calcularAsistencia']);
    Route::get('/planillas',                      [PlanillaControlador::class, 'index']);

    // ─── Ausencias (Empleados pueden gestionar sus propias ausencias) ─────────
    Route::apiResource('ausencias', AusenciaControlador::class);

    // ─── Adelantos (Empleados pueden solicitar adelantos) ──────────────────────
    Route::apiResource('adelantos', AdelantoControlador::class)->except(['update', 'destroy']);

    // ─── Reconocimiento Facial (Actualización) ────────────────────────────────
    Route::post('/empleados/{id}/actualizar-rostro', [ReconocimientoFacialController::class, 'actualizarRostro']);

    // ─── Planillas (Detalle para empleados y admin) ───────────────────────────
    Route::get('/planillas/{planilla}', [PlanillaControlador::class, 'show']);

    // ─── Rutas Administrativas (Solo Administrador) ───────────────────────────
    Route::middleware('rol:Administrador')->group(function () {
        // Módulo de Asistencia: Reconocimiento Facial
        Route::get('/empleados/disponibles-para-rostro', [ReconocimientoFacialController::class, 'empleadosDisponibles']);
        Route::post('/admin/attendance/validate-employee', [ReconocimientoFacialController::class, 'validarEmpleado']);

        Route::apiResource('empleados', EmpleadoControlador::class);

        Route::apiResource('departamentos', DepartamentoControlador::class);

        Route::apiResource('cargos',    CargoControlador::class);

        Route::post('/planillas/generar-mensual',     [PlanillaControlador::class, 'generarPlanillaMensual']);
        Route::patch('/planillas/{payroll}/pagar',    [PlanillaControlador::class, 'marcarPagado']);
        Route::apiResource('planillas', PlanillaControlador::class)->except(['index', 'show']);
        
        Route::patch('/ausencias/{vacation}/estado', [AusenciaControlador::class, 'cambiarEstado']);
        
        Route::patch('/adelantos/{adelanto}/aprobar', [AdelantoControlador::class, 'aprobar']);
        Route::patch('/adelantos/{adelanto}/rechazar', [AdelantoControlador::class, 'rechazar']);
        
        Route::apiResource('usuarios', UsuarioControlador::class);
        Route::patch('/usuarios/{usuario}/estado', [UsuarioControlador::class, 'cambiarEstado']);
        
        Route::get('/configuracion', [ConfiguracionControlador::class, 'index']);
        Route::put('/configuracion', [ConfiguracionControlador::class, 'update']);
    });

    // ─── Reconocimiento Facial ────────────────────────────────────────────────
    Route::get('/reconocimiento/estado',     [ReconocimientoFacialController::class, 'verificarRegistroRostro']);
    Route::post('/reconocimiento/registrar', [ReconocimientoFacialController::class, 'registrarRostro']);
    Route::get('/reconocimiento/embeddings', [ReconocimientoFacialController::class, 'obtenerEmbeddings']);
    Route::post('/asistencia/facial',        [ReconocimientoFacialController::class, 'registrarAsistenciaFacial']);
});

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\RespuestaJson;
use App\Models\Empleado;
use App\Models\Cargo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmpleadoControlador extends Controller
{
    use RespuestaJson;

    // ─────────────────────────────────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        try {
            $consulta = Empleado::with(['departamento', 'cargo']);

            $termino = $request->input('buscar') ?? $request->input('q');
            if (!empty($termino)) {
                $consulta->where(function ($q) use ($termino) {
                    $q->where('nombre',         'like', "%{$termino}%")
                      ->orWhere('apellido',      'like', "%{$termino}%")
                      ->orWhere('ci',            'like', "%{$termino}%")
                      ->orWhere('telefono',      'like', "%{$termino}%")
                      ->orWhere('codigo_empleado','like', "%{$termino}%")
                      ->orWhereHas('departamento', fn($d) => $d->where('nombre', 'like', "%{$termino}%"));
                });
            }

            if ($request->filled('estado')) {
                $consulta->where('estado', $request->estado);
            }
            // Acepta tanto id_departamento como departamento_id para compatibilidad
            if ($request->filled('id_departamento') || $request->filled('departamento_id')) {
                $consulta->where('id_departamento', $request->input('id_departamento') ?? $request->input('departamento_id'));
            }
            if ($request->filled('id_cargo') || $request->filled('cargo_id')) {
                $consulta->where('id_cargo', $request->input('id_cargo') ?? $request->input('cargo_id'));
            }

            $perPage = $request->input('per_page', 8);
            if ($perPage === 'all' || $perPage == -1) {
                $empleados = $consulta->orderBy('nombre')->get();
                $datos = [
                    'data' => $empleados,
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => $empleados->count()
                ];
            } else {
                $empleadosPag = $consulta->orderBy('nombre')->paginate($perPage);
                $datos = [
                    'data' => $empleadosPag->items(),
                    'current_page' => $empleadosPag->currentPage(),
                    'last_page' => $empleadosPag->lastPage(),
                    'total' => $empleadosPag->total()
                ];
            }
            return $this->respuestaExito($datos, 'Empleados obtenidos correctamente');
        } catch (\Exception) {
            return $this->respuestaServidor('Error al obtener los empleados');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STORE — usa reglasValidacionEmpleado() sin $id
    // ─────────────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        try {
            $validado = $request->validate(
                $this->reglasValidacionEmpleado(),
                $this->mensajesValidacionEmpleado()
            );

            // Validación de cargo único
            if (isset($validado['id_cargo'])) {
                $cargo = Cargo::find($validado['id_cargo']);
                if ($cargo && $cargo->es_unico) {
                    $existe = Empleado::where('id_cargo', $validado['id_cargo'])->exists();
                    if ($existe) {
                        throw ValidationException::withMessages([
                            'id_cargo' => ["Ya existe un empleado con el cargo de {$cargo->nombre}."]
                        ]);
                    }
                }
            }

            $validado['estado'] = $validado['estado'] ?? 'Activo';
            $empleado = Empleado::create($validado);

            // Código único: FL + id con padding de 4 dígitos (ej: FL0023)
            $empleado->codigo_empleado = 'FL' . str_pad($empleado->id_empleado, 4, '0', STR_PAD_LEFT);
            $empleado->save();

            $empleado->load(['departamento', 'cargo']);

            return $this->respuestaCreado($empleado, 'Empleado creado correctamente');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception) {
            return $this->respuestaServidor('Error al crear el empleado');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHOW
    // ─────────────────────────────────────────────────────────────────────────

    public function show($id): JsonResponse
    {
        try {
            $empleado = Empleado::with([
                'departamento', 'cargo', 'usuario'
            ])->findOrFail($id);
            return $this->respuestaExito($empleado, 'Empleado obtenido correctamente');
        } catch (\Exception) {
            return $this->respuestaServidor('Error al obtener el empleado');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // UPDATE — usa reglasValidacionEmpleado($id) para ignorar CI propio
    // ─────────────────────────────────────────────────────────────────────────

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $empleado = Empleado::findOrFail($id);

            $validado = $request->validate(
                $this->reglasValidacionEmpleado((int) $id),
                $this->mensajesValidacionEmpleado()
            );

            // Validación de cargo único
            if (isset($validado['id_cargo'])) {
                $cargo = Cargo::find($validado['id_cargo']);
                if ($cargo && $cargo->es_unico) {
                    $existe = Empleado::where('id_cargo', $validado['id_cargo'])
                        ->where('id_empleado', '!=', $id)
                        ->exists();
                    if ($existe) {
                        throw ValidationException::withMessages([
                            'id_cargo' => ["Ya existe un empleado con el cargo de {$cargo->nombre}."]
                        ]);
                    }
                }
            }

            $empleado->update($validado);
            $empleado->load(['departamento', 'cargo']);

            return $this->respuestaExito($empleado, 'Empleado actualizado correctamente');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception) {
            return $this->respuestaServidor('Error al actualizar el empleado');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DESTROY
    // ─────────────────────────────────────────────────────────────────────────

    public function destroy($id): JsonResponse
    {
        try {
            $empleado = Empleado::findOrFail($id);
            $nombre   = "{$empleado->nombre} {$empleado->apellido}";
            $empleado->delete();
            return $this->respuestaEliminado("Empleado {$nombre} eliminado correctamente");
        } catch (\Exception) {
            return $this->respuestaServidor('Error al eliminar el empleado');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Métodos privados — Validaciones centralizadas (DRY)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Reglas de validación para store y update de empleados.
     *
     * DRY: un solo método que store() y update() comparten.
     * La diferencia entre crear y actualizar está en la regla 'unique':
     *   - store()  → unique sin excepción (no se pasa $id)
     *   - update() → unique->ignore($id) para permitir el mismo CI del empleado
     *
     * @param  int|null $id  ID del empleado a ignorar en validación unique. Null para store.
     */
    private function reglasValidacionEmpleado(?int $id = null): array
    {
        // La regla de unicidad del CI cambia según si es creación o edición
        $reglaCi = $id
            ? ['required', 'string', 'regex:/^\d{7,8}$/', Rule::unique('empleado', 'ci')->ignore($id, 'id_empleado')]
            : 'required|string|unique:empleado,ci|regex:/^\d{7,8}$/';

        $reglaFecha = $id
            ? 'required|date'
            : 'required|date|after_or_equal:today';

        return [
            'nombre'             => 'required|string|min:2|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'apellido'           => 'required|string|min:2|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'ci'                 => $reglaCi,
            'extension_ci'       => 'nullable|string|max:5',
            'fecha_nacimiento'   => 'nullable|date',
            'telefono'           => 'nullable|string|regex:/^\d{8}$/',
            'correo'             => 'nullable|email|max:150',
            'direccion'          => 'nullable|string|max:255',
            'id_departamento'    => 'required|exists:departamento,id_departamento',
            'id_cargo'           => 'required|exists:cargo,id_cargo',
            'fecha_contratacion' => $reglaFecha,
            'estado'             => ['nullable', Rule::in(['Activo', 'Inactivo', 'Vacaciones', 'Suspendido', 'Retirado'])],
            'salario_base'       => 'required|numeric|min:0',
            'foto_rostro'        => 'nullable|string',
        ];
    }

    /**
     * Mensajes de validación en español para empleados.
     * Separados de las reglas para facilitar su traducción futura.
     */
    private function mensajesValidacionEmpleado(): array
    {
        return [
            'nombre.required'             => 'El nombre es obligatorio.',
            'nombre.min'                  => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.regex'                => 'El nombre solo puede contener letras y espacios.',
            'apellido.required'           => 'El apellido es obligatorio.',
            'apellido.min'                => 'El apellido debe tener al menos 2 caracteres.',
            'apellido.regex'              => 'El apellido solo puede contener letras y espacios.',
            'ci.required'                 => 'La cédula de identidad es obligatoria.',
            'ci.unique'                   => 'El CI ya se encuentra registrado.',
            'ci.regex'                    => 'La cédula de identidad debe contener 7 u 8 dígitos numéricos.',
            'extension_ci.max'            => 'La extensión del CI no puede superar los 5 caracteres.',
            'fecha_nacimiento.date'       => 'La fecha de nacimiento no es correcta.',
            'telefono.regex'              => 'El teléfono debe contener exactamente 8 dígitos numéricos.',
            'correo.email'                => 'El correo electrónico no es válido.',
            'id_departamento.required'    => 'Seleccione un departamento válido.',
            'id_departamento.exists'      => 'Seleccione un departamento válido.',
            'id_cargo.required'           => 'Seleccione un cargo válido.',
            'id_cargo.exists'             => 'Seleccione un cargo válido.',
            'fecha_contratacion.required' => 'La fecha de contratación es obligatoria.',
            'fecha_contratacion.date'     => 'La fecha de contratación no es correcta.',
            'fecha_contratacion.after_or_equal' => 'La fecha de contratación no puede ser en el pasado.',
            'salario_base.required'       => 'El salario base es obligatorio.',
            'salario_base.numeric'        => 'El salario base debe ser un número.',
            'salario_base.min'            => 'El salario base no puede ser negativo.',
            'estado.in'                   => 'Seleccione un estado válido.',
        ];
    }
}

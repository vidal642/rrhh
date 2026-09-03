<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    use HasFactory;

    protected $table = 'configuracion';

    protected $primaryKey = 'id';

    protected $fillable = [
        'clave',
        'valor',
        'grupo',
        'tipo',
        'descripcion',
    ];

    // ─── Métodos estáticos de acceso rápido ────────────────────────────────────

    /**
     * Obtiene el valor raw (string) de una configuración por su clave.
     * Retorna $default si la clave no existe.
     */
    public static function get(string $clave, $default = null): mixed
    {
        $config = self::where('clave', $clave)->first();
        return $config ? $config->valor : $default;
    }

    /**
     * Obtiene el valor casteado según el tipo de la configuración.
     */
    public static function getCasteado(string $clave, $default = null): mixed
    {
        $config = self::where('clave', $clave)->first();
        if (! $config) return $default;
        return self::castearValor($config->valor, $config->tipo);
    }

    /**
     * Alias conveniente: obtiene un booleano.
     */
    public static function getBool(string $clave, bool $default = false): bool
    {
        $valor = self::get($clave);
        if ($valor === null) return $default;
        return in_array($valor, ['true', '1', 1, true], true);
    }

    /**
     * Alias conveniente: obtiene un entero.
     */
    public static function getInt(string $clave, int $default = 0): int
    {
        $valor = self::get($clave);
        return $valor !== null ? (int) $valor : $default;
    }

    /**
     * Alias conveniente: obtiene un decimal (float).
     */
    public static function getDecimal(string $clave, float $default = 0.0): float
    {
        $valor = self::get($clave);
        return $valor !== null ? (float) $valor : $default;
    }

    /**
     * Alias conveniente: obtiene un string.
     */
    public static function getString(string $clave, string $default = ''): string
    {
        $valor = self::get($clave);
        return $valor !== null ? (string) $valor : $default;
    }

    /**
     * Establece o actualiza una configuración.
     */
    public static function set(string $clave, $valor, string $grupo = null, string $tipo = null): self
    {
        $datos = ['valor' => is_bool($valor) ? ($valor ? 'true' : 'false') : (string) $valor];
        if ($grupo !== null) $datos['grupo'] = $grupo;
        if ($tipo !== null)  $datos['tipo']  = $tipo;

        return self::updateOrCreate(
            ['clave' => $clave],
            $datos
        );
    }

    /**
     * Obtiene todas las configuraciones de un grupo como array clave=>valor casteado.
     */
    public static function getPorGrupo(string $grupo): array
    {
        return self::where('grupo', $grupo)->get()
            ->mapWithKeys(fn($c) => [$c->clave => self::castearValor($c->valor, $c->tipo)])
            ->toArray();
    }

    /**
     * Convierte un valor string al tipo PHP correspondiente.
     */
    private static function castearValor(mixed $valor, ?string $tipo): mixed
    {
        return match ($tipo) {
            'boolean' => in_array($valor, ['true', '1', 1, true], true),
            'integer' => (int) $valor,
            'decimal' => (float) $valor,
            'time'    => (string) $valor,
            'json'    => json_decode($valor, true),
            default   => (string) $valor,
        };
    }
}

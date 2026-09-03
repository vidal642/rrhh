<?php

namespace Database\Seeders;

use App\Models\Departamento;
use App\Models\Cargo;
use App\Models\Empleado;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $depto = Departamento::create([
            'nombre' => 'Dirección General',
            'descripcion' => 'Departamento de gerencia general de la constructora'
        ]);

        $cargo = Cargo::create([
            'nombre' => 'Gerente General',
            'descripcion' => 'Encargado de la constructora',
            'salario_referencia' => 15000.00,
            'id_departamento' => $depto->id_departamento
        ]);

        $empleado = Empleado::create([
            'nombre' => 'Admin',
            'apellido' => 'Sistema',
            'ci' => '1234567',
            'fecha_nacimiento' => '1990-01-01',
            'telefono' => '70000000',
            'correo' => 'admin@constructora.com',
            'direccion' => 'Av. Principal',
            'fecha_contratacion' => '2020-01-01',
            'estado' => 'Activo',
            'salario_base' => 15000.00,
            'id_departamento' => $depto->id_departamento,
            'id_cargo' => $cargo->id_cargo
        ]);

        Usuario::create([
            'usuario' => 'admin',
            'password' => Hash::make('password123'),
            'rol' => 'Administrador',
            'id_empleado' => $empleado->id_empleado
        ]);
        
        // Empleado normal para pruebas
        $empleado2 = Empleado::create([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'ci' => '7654321',
            'fecha_nacimiento' => '1995-05-15',
            'telefono' => '71111111',
            'correo' => 'juan@constructora.com',
            'direccion' => 'Calle 2',
            'fecha_contratacion' => '2022-03-01',
            'estado' => 'Activo',
            'salario_base' => 3500.00,
            'id_departamento' => $depto->id_departamento,
            'id_cargo' => $cargo->id_cargo
        ]);

        Usuario::create([
            'usuario' => 'jperez',
            'password' => Hash::make('password123'),
            'rol' => 'Empleado',
            'id_empleado' => $empleado2->id_empleado
        ]);
    }
}

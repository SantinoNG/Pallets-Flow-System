<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Proveedores del taller.
 *
 * Los dos primeros son los originales; se agregaron ocho mas para que el
 * listado y el filtro por materia prima tengan variedad: maderas, clavos,
 * tornilleria, pinturas, telas, espumas y herrajes.
 *
 * Es idempotente: correrlo de nuevo no duplica proveedores y permite
 * actualizar un telefono o un correo sin perder la fecha de alta.
 */
class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $proveedores = [
            ['company_name' => 'Distribuidora Norte S.A.',                  'supplier_email' => 'contacto@dnorte.com',              'supplier_phone' => '3764-111222'],
            ['company_name' => 'Materiales del Sur',                        'supplier_email' => 'ventas@matsur.com',                'supplier_phone' => '3764-333444'],
            ['company_name' => 'Maderas del Litoral S.A.',                  'supplier_email' => 'ventas@maderasdellitoral.com.ar',  'supplier_phone' => '3624-512345'],
            ['company_name' => 'Aserradero San José',                       'supplier_email' => 'pedidos@aserraderosanjose.com',    'supplier_phone' => '3764-418820'],
            ['company_name' => 'Ferretería Industrial Tornillos del Norte', 'supplier_email' => 'ventas@tornillosdelnorte.com',     'supplier_phone' => '11-4455-7788'],
            ['company_name' => 'Clavos y Tachuelas SRL',                    'supplier_email' => 'contacto@clavosytachuelas.com.ar', 'supplier_phone' => '11-4322-9911'],
            ['company_name' => 'Pinturas y Barnices Delta',                 'supplier_email' => 'presupuestos@pinturasdelta.com',   'supplier_phone' => '342-455-6677'],
            ['company_name' => 'Textiles Andinos',                          'supplier_email' => 'ventas@textilesandinos.com.ar',    'supplier_phone' => '381-433-2211'],
            ['company_name' => 'Espumas y Rellenos NOA',                    'supplier_email' => 'info@espumasyrellenosnoa.com',     'supplier_phone' => '381-455-9090'],
            ['company_name' => 'Herrajes y Accesorios Centro',              'supplier_email' => 'ventas@herrajescentro.com.ar',     'supplier_phone' => '11-4777-1234'],
        ];

        foreach ($proveedores as $proveedor) {
            $existente = DB::table('suppliers')
                ->where('company_name', $proveedor['company_name'])
                ->first();

            if ($existente) {
                DB::table('suppliers')
                    ->where('id_supplier', $existente->id_supplier)
                    ->update([
                        'supplier_email' => $proveedor['supplier_email'],
                        'supplier_phone' => $proveedor['supplier_phone'],
                        'updated_at'     => now(),
                    ]);

                continue;
            }

            DB::table('suppliers')->insert($proveedor + [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

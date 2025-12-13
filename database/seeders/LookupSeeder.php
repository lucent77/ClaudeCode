<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\Oven;
use App\Models\Material;
use App\Models\Shade;
use Illuminate\Database\Seeder;

class LookupSeeder extends Seeder
{
    public function run(): void
    {
        // COCR Machines
        $cocrMachines = ['COCR-Mill-1', 'COCR-Mill-2', 'COCR-Mill-3', 'VHF-S1', 'VHF-S2'];
        foreach ($cocrMachines as $name) {
            Machine::create(['dept' => 'COCR', 'name' => $name, 'active' => true]);
        }

        // SOLIDEX Machines
        $solidexMachines = ['SOLIDEX-CNC-1', 'SOLIDEX-CNC-2', 'Roland-DWX-52'];
        foreach ($solidexMachines as $name) {
            Machine::create(['dept' => 'SOLIDEX', 'name' => $name, 'active' => true]);
        }

        // 3D Print Machines (Printers)
        $printers = ['Form 3B', 'Form 3BL', 'SprintRay Pro 55', 'Asiga Max', 'Carbon M2'];
        foreach ($printers as $name) {
            Machine::create(['dept' => 'PRINT', 'name' => $name, 'active' => true]);
        }

        // Ovens
        $ovens = ['Programat P710', 'Programat P510', 'Dekema Austromat 654', 'VITA Vacumat 6000 M'];
        foreach ($ovens as $name) {
            Oven::create(['name' => $name, 'active' => true]);
        }

        // Materials
        $materials = [
            'Zirconia - Multilayer',
            'Zirconia - Solid',
            'Zirconia - High Translucent',
            'Lithium Disilicate',
            'PMMA',
            'Wax',
            'CoCr',
            'Titanium',
        ];
        foreach ($materials as $name) {
            Material::create(['name' => $name, 'active' => true]);
        }

        // Shades
        $shades = [
            'A1', 'A2', 'A3', 'A3.5', 'A4',
            'B1', 'B2', 'B3', 'B4',
            'C1', 'C2', 'C3', 'C4',
            'D2', 'D3', 'D4',
            'BL1', 'BL2', 'BL3', 'BL4',
            'OM1', 'OM2', 'OM3',
        ];
        foreach ($shades as $name) {
            Shade::create(['name' => $name, 'active' => true]);
        }
    }
}

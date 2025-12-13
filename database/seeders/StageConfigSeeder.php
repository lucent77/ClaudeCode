<?php

namespace Database\Seeders;

use App\Models\StageConfig;
use Illuminate\Database\Seeder;

class StageConfigSeeder extends Seeder
{
    public function run(): void
    {
        // COCR Stages: TRANS(FD) → DESIGN → CAM → CNC(machine req) → OVENS(oven req) → QC
        $cocrStages = [
            ['stage' => 'TRANS', 'order_index' => 1, 'required_fields' => []],
            ['stage' => 'DESIGN', 'order_index' => 2, 'required_fields' => []],
            ['stage' => 'CAM', 'order_index' => 3, 'required_fields' => []],
            ['stage' => 'CNC', 'order_index' => 4, 'required_fields' => ['machine']],
            ['stage' => 'OVENS', 'order_index' => 5, 'required_fields' => ['oven']],
            ['stage' => 'QC', 'order_index' => 6, 'required_fields' => []],
        ];

        foreach ($cocrStages as $stage) {
            StageConfig::create([
                'dept' => 'COCR',
                'stage' => $stage['stage'],
                'order_index' => $stage['order_index'],
                'enable_subtasks' => false,
                'allowed_fields' => null,
                'required_fields' => $stage['required_fields'],
                'active' => true,
            ]);
        }

        // SOLIDEX Stages: TRANSCAN(FD) → PRECAD → CAD → PRECAM(multi-subtasks) → CNC(machine req) → QC
        $solidexStages = [
            ['stage' => 'TRANSCAN', 'order_index' => 1, 'enable_subtasks' => false, 'required_fields' => []],
            ['stage' => 'PRECAD', 'order_index' => 2, 'enable_subtasks' => false, 'required_fields' => []],
            ['stage' => 'CAD', 'order_index' => 3, 'enable_subtasks' => false, 'required_fields' => []],
            ['stage' => 'PRECAM', 'order_index' => 4, 'enable_subtasks' => true, 'required_fields' => []],
            ['stage' => 'CNC', 'order_index' => 5, 'enable_subtasks' => false, 'required_fields' => ['machine']],
            ['stage' => 'QC', 'order_index' => 6, 'enable_subtasks' => false, 'required_fields' => []],
        ];

        foreach ($solidexStages as $stage) {
            StageConfig::create([
                'dept' => 'SOLIDEX',
                'stage' => $stage['stage'],
                'order_index' => $stage['order_index'],
                'enable_subtasks' => $stage['enable_subtasks'],
                'allowed_fields' => null,
                'required_fields' => $stage['required_fields'],
                'active' => true,
            ]);
        }

        // 3D PRINT Stages: TRANS → PREP → DESIGN → NESTING/PRINT(printer req) → POST → QC
        $printStages = [
            ['stage' => 'TRANS', 'order_index' => 1, 'required_fields' => []],
            ['stage' => 'PREP', 'order_index' => 2, 'required_fields' => []],
            ['stage' => 'DESIGN', 'order_index' => 3, 'required_fields' => []],
            ['stage' => 'NESTING', 'order_index' => 4, 'required_fields' => ['printer']],
            ['stage' => 'PRINT', 'order_index' => 5, 'required_fields' => ['printer']],
            ['stage' => 'POST', 'order_index' => 6, 'required_fields' => []],
            ['stage' => 'QC', 'order_index' => 7, 'required_fields' => []],
        ];

        foreach ($printStages as $stage) {
            StageConfig::create([
                'dept' => 'PRINT',
                'stage' => $stage['stage'],
                'order_index' => $stage['order_index'],
                'enable_subtasks' => false,
                'allowed_fields' => null,
                'required_fields' => $stage['required_fields'],
                'active' => true,
            ]);
        }
    }
}

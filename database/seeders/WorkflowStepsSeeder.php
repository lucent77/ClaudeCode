<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkflowStepsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSteps = [
            ['step_name' => 'Intake', 'step_order' => 1],
            ['step_name' => 'Pre-op Scan', 'step_order' => 2],
            ['step_name' => 'Planning', 'step_order' => 3],
            ['step_name' => 'Fabrication', 'step_order' => 4],
            ['step_name' => 'QA Review', 'step_order' => 5],
            ['step_name' => 'Delivery', 'step_order' => 6],
            ['step_name' => 'Surgery', 'step_order' => 7],
            ['step_name' => 'Follow-up', 'step_order' => 8],
            ['step_name' => 'Completed', 'step_order' => 9],
        ];

        // This is a template - actual implementation would need case_id
        // Use this as reference when creating new cases

        $this->command->info('Workflow steps template created');
        $this->command->info('These steps will be automatically created for each new case');
    }
}

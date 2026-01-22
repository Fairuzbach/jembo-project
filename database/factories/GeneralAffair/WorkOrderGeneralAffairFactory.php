<?php

namespace Database\Factories\GeneralAffair;

use App\Models\GeneralAffair\WorkOrderGeneralAffair;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkOrderGeneralAffairFactory extends Factory
{
    protected $model = WorkOrderGeneralAffair::class;

    public function definition()
    {
        // Ambil random user atau user ID 1
        $user = User::inRandomOrder()->first() ?? User::factory()->create();

        return [
            'ticket_num' => 'GA-' . date('Ymd') . '-' . $this->faker->unique()->numberBetween(1000, 9999),
            'requester_id' => $user->id,
            'requester_nik' => $user->nik ?? '12345',
            'requester_name' => $user->name,
            'requester_department' => 'Engineering', // Default Eng biar gampang dites

            'plant' => 1, // Sesuaikan dengan ID Plant yang ada di DB Anda
            'department' => $this->faker->randomElement(['GA', 'IT', 'SC', 'SALES']),
            'category' => $this->faker->randomElement(['RINGAN', 'SEDANG', 'BERAT']),
            'description' => $this->faker->sentence(10),
            'parameter_permintaan' => 'PERBAIKAN',

            'status_permintaan' => 'OPEN',
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    // STATE: Tiket Baru (Menunggu SPV)
    public function waitingSpv()
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'waiting_spv',
        ]);
    }

    // STATE: Siap untuk Admin GA (Sudah diapprove SPV)
    public function pendingGa()
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
            'approved_tech_by' => 1,
            'approved_tech_at' => now(),
        ]);
    }

    // STATE: Sedang Dikerjakan (In Progress)
    public function inProgress()
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'in_progress',
            'approved_tech_by' => 1,
            'processed_by' => 1, // ID Admin GA
            'processed_by_name' => 'Admin GA Test',
        ]);
    }

    // STATE: Selesai (Completed)
    public function completed()
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
            'processed_by' => 1,
            'processed_by_name' => 'Admin GA Test',
            'target_completion_date' => now()->addDays(2),
            'actual_completion_date' => now(),
        ]);
    }
}

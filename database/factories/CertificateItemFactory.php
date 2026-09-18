<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\CertificateItem;
use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CertificateItem>
 */
class CertificateItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'certificate_id' => Certificate::factory(),
            'equipment_id' => Equipment::factory(),
        ];
    }
}

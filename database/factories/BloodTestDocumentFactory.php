<?php

namespace Database\Factories;

use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BloodTestDocument>
 */
class BloodTestDocumentFactory extends Factory
{
    protected $model = BloodTestDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'blood_test_id' => BloodTest::factory(),
            'original_filename' => 'lab-result.pdf',
            'storage_disk' => 'local',
            'storage_path' => 'blood-test-documents/'.Str::uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ];
    }
}

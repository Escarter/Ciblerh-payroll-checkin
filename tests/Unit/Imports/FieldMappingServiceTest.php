<?php

namespace Tests\Unit\Imports;

use App\Imports\Adapters\EmployeeImportAdapter;
use App\Imports\Services\FieldMappingService;
use Tests\TestCase;

class FieldMappingServiceTest extends TestCase
{
    /** @test */
    public function it_auto_maps_csv_headers_with_confidence_scores()
    {
        $adapter = new EmployeeImportAdapter();
        $service = new FieldMappingService();

        $headers = [
            'first name',
            'last name',
            'email',
            'phone',
            'random_column_unmapped',
        ];

        $suggestions = $service->autoMap($headers, $adapter);

        $this->assertCount(5, $suggestions);

        // Exact match
        $this->assertEquals('first_name', $suggestions[0]['field']);
        $this->assertEquals(100, $suggestions[0]['confidence']);
        $this->assertEquals('exact', $suggestions[0]['strategy']);

        // Exact match
        $this->assertEquals('last_name', $suggestions[1]['field']);
        $this->assertEquals(100, $suggestions[1]['confidence']);

        // Exact match
        $this->assertEquals('email', $suggestions[2]['field']);
        $this->assertEquals(100, $suggestions[2]['confidence']);

        // Alias match
        $this->assertEquals('professional_phone_number', $suggestions[3]['field']);
        $this->assertEquals(90, $suggestions[3]['confidence']);
        $this->assertEquals('alias', $suggestions[3]['strategy']);

        // Unmatched
        $this->assertNull($suggestions[4]['field']);
        $this->assertEquals(0, $suggestions[4]['confidence']);
    }

    /** @test */
    public function it_applies_mapping_to_raw_rows()
    {
        $service = new FieldMappingService();
        $rawRow = ['John', 'Doe', 'john@example.com'];
        $mapping = [
            0 => 'first_name',
            1 => 'last_name',
            2 => 'email',
        ];

        $mapped = $service->applyMapping($rawRow, $mapping);

        $this->assertEquals([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ], $mapped);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_similar_vehicles_prioritizes_same_category_and_price_band(): void
    {
        $suvCategory = Category::factory()->create(['name' => 'SUV']);
        $sedanCategory = Category::factory()->create(['name' => 'Sedan']);

        $subject = Vehicle::factory()->create([
            'category_id' => $suvCategory->id, 'car_type' => 'normal', 'propulsion' => 'essence', 'price_per_day' => 50000, 'status' => 'available',
        ]);
        $sameCategory = Vehicle::factory()->create([
            'category_id' => $suvCategory->id, 'car_type' => 'normal', 'propulsion' => 'essence', 'price_per_day' => 52000, 'status' => 'available',
        ]);
        $samePriceOnly = Vehicle::factory()->create([
            'category_id' => $sedanCategory->id, 'car_type' => 'hybrid', 'propulsion' => 'diesel', 'price_per_day' => 51000, 'status' => 'available',
        ]);
        $noOverlap = Vehicle::factory()->create([
            'category_id' => $sedanCategory->id, 'car_type' => 'electric', 'propulsion' => null, 'price_per_day' => 200000, 'status' => 'available',
        ]);

        $results = $subject->similarVehicles();

        $this->assertTrue($results->contains('id', $sameCategory->id));
        $this->assertFalse($results->contains('id', $noOverlap->id), 'A vehicle sharing nothing should score 0 and be dropped.');
        // Same category (+3) + price band (+2) outranks price-band-only (+2).
        $this->assertSame($sameCategory->id, $results->first()->id);
        $this->assertTrue($results->contains('id', $samePriceOnly->id));
    }

    public function test_similar_vehicles_excludes_itself_and_unavailable_vehicles(): void
    {
        $category = Category::factory()->create();
        $subject = Vehicle::factory()->create(['category_id' => $category->id, 'price_per_day' => 40000, 'status' => 'available']);
        $maintenance = Vehicle::factory()->create(['category_id' => $category->id, 'price_per_day' => 40000, 'status' => 'maintenance']);

        $results = $subject->similarVehicles();

        $this->assertFalse($results->contains('id', $subject->id));
        $this->assertFalse($results->contains('id', $maintenance->id));
    }
}

<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $brands = Brand::query()->pluck('id', 'slug');
        $categories = Category::query()->pluck('id', 'name');

        foreach ($this->catalog() as $index => $sample) {
            $brandId = $brands[Str::slug($sample['brand'])] ?? null;
            $categoryId = $categories[$sample['category']] ?? null;

            if (! $brandId || ! $categoryId) {
                continue;
            }

            $slug = Str::slug($sample['name']);

            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'brand_id' => $brandId,
                    'name' => $sample['name'],
                    'base_sku' => Str::upper($sample['sku']),
                    'product_type' => 'simple',
                    'status' => ProductStatus::Active,
                    'short_description' => $sample['short_description'],
                    'description' => $sample['description'],
                    'is_featured' => false,
                    'is_new_arrival' => $index < 8,
                    'is_best_seller' => false,
                    'is_project_suitable' => $index % 5 === 0,
                ]
            );

            $variant = ProductVariant::updateOrCreate(
                ['sku' => Str::upper($sample['sku'])],
                [
                    'product_id' => $product->id,
                    'variant_name' => 'Default',
                    'price' => $sample['price'],
                    'sale_price' => $sample['sale_price'],
                    'stock_quantity' => $sample['stock'],
                    'low_stock_threshold' => 5,
                    'is_default' => true,
                    'is_active' => true,
                    'sort_order' => 0,
                ]
            );

            $product->update(['default_variant_id' => $variant->id]);
            $product->categories()->sync([$categoryId]);
        }
    }

    /**
     * @return list<array{
     *     name: string,
     *     sku: string,
     *     brand: string,
     *     category: string,
     *     short_description: string,
     *     description: string,
     *     price: float|int,
     *     sale_price: float|int|null,
     *     stock: int
     * }>
     */
    private function catalog(): array
    {
        $items = [];

        $definitions = [
            'Wash Basins' => [
                'prefix' => 'WB',
                'brands' => ['Rozana', 'Royal Sanitary', 'Elite Bath', 'Crystal Bath', 'Inayat Premium'],
                'models' => ['Oval Soft', 'Square Edge', 'Round Classic', 'Vessel Luxe', 'Compact Studio'],
                'price' => [4500, 12000],
            ],
            'Counter Top Basins' => [
                'prefix' => 'CTB',
                'brands' => ['Rozana', 'Kohler', 'Elite Bath', 'Urban Sanitary'],
                'models' => ['Marble Look', 'Matte White', 'Slim Rim', 'Artisan Bowl'],
                'price' => [5200, 14500],
            ],
            'Wall Hung Basins' => [
                'prefix' => 'WHB',
                'brands' => ['Rozana', 'Grohe', 'Porta', 'Crystal Bath'],
                'models' => ['Space Saver', 'Floating Edge', 'Minimal Line', 'Corner Fit'],
                'price' => [4800, 13200],
            ],
            'Pedestal Basins' => [
                'prefix' => 'PDB',
                'brands' => ['Master', 'Sonex', 'Royal Sanitary', 'Falcon'],
                'models' => ['Heritage Column', 'Slim Pedestal', 'Classic White'],
                'price' => [3900, 9800],
            ],
            'Kitchen Sinks' => [
                'prefix' => 'KS',
                'brands' => ['Modern Kitchen', 'Jaquar', 'Master', 'Porta', 'Sonex'],
                'models' => ['Single Bowl 24', 'Double Bowl 32', 'Drain Board Pro', 'Undermount Steel', 'Granite Composite'],
                'price' => [8500, 28000],
            ],
            'Bathroom Faucets' => [
                'prefix' => 'BF',
                'brands' => ['AquaFlow', 'Smart Flow', 'Grohe', 'Jaquar', 'Falcon'],
                'models' => ['Chrome Dual', 'Matte Black Lever', 'Brushed Nickel', 'Wall Mount Twin'],
                'price' => [3200, 14500],
            ],
            'Kitchen Faucets' => [
                'prefix' => 'KF',
                'brands' => ['Modern Kitchen', 'AquaFlow', 'Jaquar', 'Smart Flow'],
                'models' => ['Pull-Out Spray', 'Gooseneck Chef', 'Deck Mount Pro', 'Filter Ready'],
                'price' => [4500, 16800],
            ],
            'Basin Mixers' => [
                'prefix' => 'BM',
                'brands' => ['Falcon', 'Grohe', 'Jaquar', 'Sonex', 'HydroLux'],
                'models' => ['Single Lever Chrome', 'Tall Spout', 'Deck Chrome Pro', 'Eco Cartridge'],
                'price' => [3800, 15500],
            ],
            'Kitchen Mixers' => [
                'prefix' => 'KM',
                'brands' => ['Jaquar', 'Modern Kitchen', 'Master', 'AquaFlow'],
                'models' => ['Pro Spray', 'Swivel Arm', 'Stainless Twin', 'Quiet Flow'],
                'price' => [5200, 17500],
            ],
            'Shower Mixers' => [
                'prefix' => 'SM',
                'brands' => ['HydroLux', 'Grohe', 'Jaquar', 'Smart Flow'],
                'models' => ['Thermostatic 3-Way', 'Manual Concealed', 'Exposed Chrome', 'Diverter Plus'],
                'price' => [7500, 26500],
            ],
            'Health Faucets' => [
                'prefix' => 'HF',
                'brands' => ['AquaFlow', 'Master', 'Porta', 'Sonex', 'EcoBath'],
                'models' => ['ABS Spray', 'SS Hose Set', 'Chrome Holder', 'Eco Jet'],
                'price' => [900, 3200],
            ],
            'Angle Valves' => [
                'prefix' => 'AV',
                'brands' => ['Master', 'Sonex', 'Porta', 'Falcon', 'AquaFlow'],
                'models' => ['Chrome 1/2', 'Brass Heavy', 'Ceramic Disc', 'Filter Valve'],
                'price' => [450, 1800],
            ],
            'Shower Sets' => [
                'prefix' => 'SS',
                'brands' => ['HydroLux', 'Grohe', 'Elite Bath', 'Jaquar', 'Inayat Premium'],
                'models' => ['Rain Combo', '3-Function Set', 'Luxury Square', 'Thermostat Bundle'],
                'price' => [12500, 42000],
            ],
            'Rain Showers' => [
                'prefix' => 'RS',
                'brands' => ['Grohe', 'HydroLux', 'Jaquar', 'Elite Bath'],
                'models' => ['200mm Round', '300mm Square', 'Ultra Slim', 'LED Ambient'],
                'price' => [6800, 24500],
            ],
            'Hand Showers' => [
                'prefix' => 'HS',
                'brands' => ['HydroLux', 'AquaFlow', 'Master', 'Smart Flow'],
                'models' => ['5-Spray', 'Anti-Limescale', 'Compact Travel', 'Massage Jet'],
                'price' => [1800, 6500],
            ],
            'Shower Panels' => [
                'prefix' => 'SP',
                'brands' => ['HydroLux', 'Elite Bath', 'Urban Sanitary', 'Inayat Premium'],
                'models' => ['Tower Jets', 'Glass Column', 'Body Spray Pro', 'LED Panel'],
                'price' => [28000, 75000],
            ],
            'One Piece' => [
                'prefix' => 'OPT',
                'brands' => ['Kohler', 'Royal Sanitary', 'Inayat Premium', 'Crystal Bath'],
                'models' => ['Arc Soft Close', 'Rimless Eco', 'Compact Urban', 'Dual Flush Luxe'],
                'price' => [22000, 48000],
            ],
            'Two Piece' => [
                'prefix' => 'TPT',
                'brands' => ['Master', 'Porta', 'Sonex', 'Royal Sanitary'],
                'models' => ['Standard S-Trap', 'P-Trap Comfort', 'Water Save Dual', 'Closed Coupled'],
                'price' => [12500, 28500],
            ],
            'Wall Hung' => [
                'prefix' => 'WHT',
                'brands' => ['Kohler', 'Grohe', 'Elite Bath', 'Urban Sanitary'],
                'models' => ['Concealed Frame', 'Rimless Hang', 'Soft Close Seat', 'Project Spec'],
                'price' => [18500, 52000],
            ],
            'Toilet Seats' => [
                'prefix' => 'TS',
                'brands' => ['Master', 'Porta', 'Sonex', 'EcoBath'],
                'models' => ['Soft Close White', 'Quick Release', 'Urea Durable', 'Family Fit'],
                'price' => [2200, 7800],
            ],
            'Mirrors' => [
                'prefix' => 'MR',
                'brands' => ['Elite Bath', 'Crystal Bath', 'Urban Sanitary', 'Inayat Premium'],
                'models' => ['LED Round 60', 'Rect Defog', 'Cabinet Mirror', 'Frameless Studio'],
                'price' => [4500, 18500],
            ],
            'Soap Dispensers' => [
                'prefix' => 'SD',
                'brands' => ['Elite Bath', 'Urban Sanitary', 'Master', 'Crystal Bath'],
                'models' => ['Wall Chrome', 'Sensor Touch', 'Counter Pump', 'Hotel Grade'],
                'price' => [1200, 5500],
            ],
            'Towel Rails' => [
                'prefix' => 'TR',
                'brands' => ['Master', 'Elite Bath', 'Sonex', 'Royal Sanitary'],
                'models' => ['SS Single Bar', 'Heated Dual', 'Ring Chrome', 'Ladder Rack'],
                'price' => [1800, 9200],
            ],
            'Corner Shelves' => [
                'prefix' => 'CS',
                'brands' => ['Urban Sanitary', 'Master', 'Porta', 'Crystal Bath'],
                'models' => ['Glass Twin', 'SS Basket', 'Floating Corner', 'Drain Shelf'],
                'price' => [900, 4200],
            ],
            'Floor Drains' => [
                'prefix' => 'FD',
                'brands' => ['Master', 'Porta', 'Sonex', 'EcoBath', 'AquaFlow'],
                'models' => ['SS Square 4', 'Tile Insert', 'Anti-Odor', 'Linear 60cm'],
                'price' => [650, 4800],
            ],
            'Flexible Pipes' => [
                'prefix' => 'FP',
                'brands' => ['Master', 'Porta', 'Sonex', 'AquaFlow', 'EcoBath'],
                'models' => ['SS 60cm Pair', 'Braided 45cm', 'Heavy Duty 90', 'Connector Kit'],
                'price' => [350, 1600],
            ],
            'Kitchen Soap Dispensers' => [
                'prefix' => 'KSD',
                'brands' => ['Modern Kitchen', 'Jaquar', 'Master'],
                'models' => ['Sink Mount', 'Chrome Pump', 'Refill Bottle'],
                'price' => [1500, 4800],
            ],
            'Kitchen Corner Shelves' => [
                'prefix' => 'KCS',
                'brands' => ['Modern Kitchen', 'Urban Sanitary', 'Master'],
                'models' => ['Wire Basket', 'Under-Sink Rack', 'Wall Organizer'],
                'price' => [1100, 3900],
            ],
            'Kitchen Flexible Pipes' => [
                'prefix' => 'KFP',
                'brands' => ['Modern Kitchen', 'AquaFlow', 'Master'],
                'models' => ['Hot Cold Pair', 'Filter Line', 'Dishwasher Link'],
                'price' => [400, 1800],
            ],
        ];

        $sequence = 1;

        // Spread ~3 products per category so all leaf categories get coverage within 80–100 items.
        foreach ($definitions as $category => $config) {
            $createdForCategory = 0;
            $targetPerCategory = 3;

            foreach ($config['brands'] as $brandIndex => $brand) {
                foreach ($config['models'] as $modelIndex => $model) {
                    if ($createdForCategory >= $targetPerCategory) {
                        break 2;
                    }

                    if (count($items) >= 95) {
                        break 3;
                    }

                    $sku = sprintf('%s-%s-%03d', $config['prefix'], Str::upper(Str::substr(Str::slug($brand), 0, 3)), $sequence);
                    $name = trim("{$category} {$brand} {$model}");
                    [$min, $max] = $config['price'];
                    $price = (int) round(($min + (($max - $min) * (($modelIndex + 1) / (count($config['models']) + 1)))) / 50) * 50;
                    $onSale = $sequence % 4 === 0;
                    $salePrice = $onSale ? (int) round($price * 0.88 / 50) * 50 : null;
                    $stock = 8 + (($sequence * 3) % 40);

                    $items[] = [
                        'name' => $name,
                        'sku' => $sku,
                        'brand' => $brand,
                        'category' => $category,
                        'short_description' => "Quality {$category} from {$brand} for modern sanitary installations.",
                        'description' => "The {$name} is a durable sanitary ware product designed for residential and commercial projects. It delivers reliable performance, clean finishing, and easy installation. Suitable for showrooms, apartments, and contractor supply.",
                        'price' => $price,
                        'sale_price' => $salePrice,
                        'stock' => $stock,
                    ];

                    $sequence++;
                    $createdForCategory++;
                }
            }
        }

        return $items;
    }
}

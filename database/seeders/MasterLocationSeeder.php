<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterLocationSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTable(
            'master_province',
            'datalocation2/province.sql',
            ['prov_id', 'prov_name'],
            2
        );

        $this->seedTable(
            'master_city',
            'datalocation2/city.sql',
            ['city_id', 'city_name', 'prov_id'],
            3
        );

        $this->seedTable(
            'master_district',
            'datalocation2/district.sql',
            ['dis_id', 'dis_name', 'city_id'],
            3
        );

        $this->seedTable(
            'master_subdistrict',
            'datalocation2/subdistrict.sql',
            ['subdis_id', 'subdis_name', 'dis_id'],
            3
        );

        $this->seedTable(
            'master_postal_code',
            'datalocation2/postal_code.sql',
            ['postal_id', 'subdis_id', 'dis_id', 'city_id', 'prov_id', 'postal_code'],
            6
        );
    }

    private function seedTable(string $table, string $file, array $columns, int $numValues): void
    {
        $path = base_path($file);
        if (!file_exists($path)) {
            $this->command->warn("File not found: $file");
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table($table)->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $lines = file($path);
        $data = [];
        $inValues = false;

        foreach ($lines as $line) {
            $line = trim($line, " \t\n\r();");
            if (empty($line) || stripos($line, 'VALUES') !== false) {
                $inValues = true;
                continue;
            }
            if (!$inValues || empty($line) || $line === ';') {
                continue;
            }

            $line = trim($line, "(),");
            $parts = array_map(function ($v) {
                return trim(trim($v), "'");
            }, explode(',', $line));

            if (count($parts) === $numValues) {
                $data[] = array_combine($columns, $parts);
            }
        }

        if (!empty($data)) {
            foreach (array_chunk($data, 500) as $chunk) {
                DB::table($table)->insert($chunk);
            }
        }

        $this->command->info("Seeded {$table}: " . count($data) . " rows");
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\IndonesiaDistrict;


class ImportDistricts extends Command
{
    protected $signature = 'import:districts {file}';

    protected $description = 'Import districts from CSV';

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found.");
            return;
        }

        $handle = fopen($file, 'r');

        $header = fgetcsv($handle);

        $count = 0;

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {

            IndonesiaDistrict::updateOrCreate(
                [
                    'code' => trim($row[0])
                ],
                [
                    'city_code' => trim($row[1]),
                    'name'      => trim($row[2]),
                    'meta'      => trim($row[2]),
                ]
            );

            $count++;
        }

        fclose($handle);

        $this->info("Imported {$count} districts.");
    }
}
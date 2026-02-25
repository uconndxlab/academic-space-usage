<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Campus;
use App\Models\Room;
use Illuminate\Database\Seeder;

class SpaceDataSeeder extends Seeder
{
    private const ALLOWED_FICM = ['110', '210', '350', '680'];

    public function run(): void
    {
        $buildingCodeMapping = $this->loadBuildingCodeMapping();

        $path = database_path('SpaceData.csv');
        if (!is_readable($path)) {
            throw new \RuntimeException('SpaceData.csv not found or not readable.');
        }

        $file = fopen($path, 'r');
        if (!$file) {
            throw new \RuntimeException('Could not open SpaceData.csv.');
        }

        $headers = fgetcsv($file);
        if (!$headers) {
            fclose($file);
            throw new \RuntimeException('Could not read SpaceData.csv headers.');
        }

        $headers = array_map(function ($h) {
            return trim(preg_replace('/^\x{FEFF}/u', '', $h));
        }, $headers);
        $headers = array_combine(array_values($headers), array_keys($headers));

        $ficmIdx = $headers['FICM'] ?? null;
        $campusIdx = $headers['Campus'] ?? null;
        $bldgIdx = $headers['Bldg'] ?? null;
        $buildingIdx = $headers['Building'] ?? null;
        $roomIdx = $headers['Room'] ?? null;
        $capacityIdx = $headers['Capacity'] ?? null;
        $ficmDescIdx = $headers['FICM Description'] ?? null;
        $deptNameIdx = $headers['Dept Name'] ?? null;

        if ($ficmIdx === null || $campusIdx === null || $bldgIdx === null || $roomIdx === null) {
            fclose($file);
            throw new \RuntimeException('SpaceData.csv missing required columns (FICM, Campus, Bldg, Room).');
        }

        $rowNum = 0;
        $imported = 0;
        $skippedFicm = 0;

        while (($row = fgetcsv($file)) !== false) {
            $rowNum++;
            if (count($row) <= max($ficmIdx, $campusIdx, $bldgIdx, $roomIdx)) {
                continue;
            }

            $ficmRaw = trim((string) ($row[$ficmIdx] ?? ''));
            $ficm = $ficmRaw !== '' ? (string) (int) $ficmRaw : '';
            if (!in_array($ficm, self::ALLOWED_FICM, true)) {
                $skippedFicm++;
                continue;
            }

            $campusName = trim((string) ($row[$campusIdx] ?? ''));
            $bldgNumber = trim((string) ($row[$bldgIdx] ?? ''));
            $buildingName = $buildingIdx !== null ? trim((string) ($row[$buildingIdx] ?? '')) : $bldgNumber;
            $roomRaw = trim((string) ($row[$roomIdx] ?? ''));
            $roomNumber = $this->stripLetters($roomRaw);
            $capacity = isset($capacityIdx, $row[$capacityIdx]) && $row[$capacityIdx] !== ''
                ? (int) $row[$capacityIdx]
                : 0;
            $ficmDescription = $ficmDescIdx !== null ? trim((string) ($row[$ficmDescIdx] ?? '')) : '';
            $deptName = $deptNameIdx !== null ? trim((string) ($row[$deptNameIdx] ?? '')) : null;

            if ($campusName === '' || $bldgNumber === '' || $roomNumber === '') {
                continue;
            }

            $mappedEntry = $buildingCodeMapping[$bldgNumber] ?? null;

            $campus = Campus::firstOrCreate(
                ['name' => $campusName],
                ['name' => $campusName]
            );

            $building = Building::firstOrCreate(
                ['building_code' => $bldgNumber],
                [
                    'description' => $buildingName ?: $bldgNumber,
                    'short_building_name' => $mappedEntry ? $mappedEntry['name'] : ($buildingName ?: $bldgNumber),
                    'campus_id' => $campus->id,
                ]
            );
            if ($building->campus_id === null) {
                $building->update(['campus_id' => $campus->id]);
            }

            Room::updateOrCreate(
                [
                    'building_id' => $building->id,
                    'room_number' => $roomNumber,
                ],
                [
                    'capacity' => $capacity,
                    'room_description' => $ficmDescription ?: $roomNumber,
                    'sa_facility_type' => $ficmDescription ?: null,
                    'dept_name' => $deptName ?: null,
                ]
            );
            $imported++;
        }

        fclose($file);
        $this->command?->info("SpaceData: processed {$rowNum} rows, imported {$imported} rooms, skipped (FICM) {$skippedFicm}.");
    }

    private function stripLetters(string $value): string
    {
        $stripped = preg_replace('/[A-Za-z]/', '', trim($value));
        return $stripped !== '' ? $stripped : trim($value);
    }

    private function loadBuildingCodeMapping(): array
    {
        $path = database_path('bulding_code_mapping.csv');
        if (!is_readable($path)) {
            return [];
        }
        $fh = fopen($path, 'r');
        if (!$fh) {
            return [];
        }
        $headers = fgetcsv($fh);
        if (!$headers) {
            fclose($fh);
            return [];
        }
        $headers = array_map('trim', $headers);
        $idxBldgNum = array_search('Building_number', $headers);
        $idxCode = array_search('Building_Code', $headers);
        $idxShort = array_search('Short_building_name', $headers);
        if ($idxBldgNum === false || $idxCode === false || $idxShort === false) {
            fclose($fh);
            return [];
        }
        $map = [];
        while ($row = fgetcsv($fh)) {
            if (count($row) <= max($idxBldgNum, $idxCode, $idxShort)) {
                continue;
            }
            $bldgNum = trim($row[$idxBldgNum] ?? '');
            $code = trim($row[$idxCode] ?? '');
            $shortName = trim($row[$idxShort] ?? '');
            if ($code === '' || $bldgNum === '') {
                continue;
            }
            $map[$bldgNum] = ['code' => $code, 'name' => $shortName];
        }
        fclose($fh);
        return $map;
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Term;
use App\Models\Building;
use App\Models\Room;
use App\Models\Course;
use App\Models\Section;
use App\Models\Campus;

class NewDataStructure extends Seeder
{
    public function run()
    {
        $file = fopen(database_path('2-2-2026-data.csv'), 'r');
        if (!$file) {
            throw new \Exception('Could not open 2-2-2026-data.csv file');
        }

        $headers = fgetcsv($file);
        if (!$headers) {
            fclose($file);
            throw new \Exception('Could not read CSV headers');
        }

        $headers = array_map('strtolower', $headers);

        $rowCount = 0;
        $processedCount = 0;
        $skippedYear = 0;
        $skippedRoomCapacity = 0;
        $skippedNoRoom = 0;
        $skippedMissingFields = 0;
        $skippedInstructionMode = 0;
        $skippedClassDays = 0;

        $alphaToNumeric = $this->loadAlphaToNumericMapping();

        $termCache = [];
        $buildingCache = [];
        $roomCache = [];
        $roomIsLabCache = [];
        $courseCache = [];
        $campusCache = [];
        $sectionBatch = [];
        $batchSize = 500;
        $now = null;

        echo "Starting to read CSV file...\n";
        echo "Headers found: " . count($headers) . " columns\n";

        while ($row = fgetcsv($file)) {
            $rowCount++;

            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            }

            $data = array_combine($headers, $row);

            if (!$data) {
                continue;
            }

            $acadYear = trim($data['acad_year'] ?? '');
            if (empty($acadYear) || (int)$acadYear < 2023) {
                $skippedYear++;
                continue;
            }

            $instructionMode = trim($data['instruction_mode'] ?? '');
            if (!in_array($instructionMode, ['Hybrid/Blended', 'In Person', 'Service Learning', 'In-Person Remote', 'Hybrid/Blended Reduced', 'Split In Person'])) {
                $skippedInstructionMode++;
                continue;
            }

            $classDays = trim($data['class_days'] ?? '');
            if (strtoupper($classDays) === 'NNNNNNN') {
                $skippedClassDays++;
                continue;
            }

            $sunday = false;
            $monday = false;
            $tuesday = false;
            $wednesday = false;
            $thursday = false;
            $friday = false;
            $saturday = false;
            $totalClassDays = 0;
            if (strlen($classDays) >= 7) {
                $sunday = strtoupper($classDays[0]) === 'Y';
                $monday = strtoupper($classDays[1]) === 'Y';
                $tuesday = strtoupper($classDays[2]) === 'Y';
                $wednesday = strtoupper($classDays[3]) === 'Y';
                $thursday = strtoupper($classDays[4]) === 'Y';
                $friday = strtoupper($classDays[5]) === 'Y';
                $saturday = strtoupper($classDays[6]) === 'Y';
                $totalClassDays = ($sunday ? 1 : 0) + ($monday ? 1 : 0) + ($tuesday ? 1 : 0)
                    + ($wednesday ? 1 : 0) + ($thursday ? 1 : 0) + ($friday ? 1 : 0) + ($saturday ? 1 : 0);
            }

            $roomCapacity = trim($data['room_capacity'] ?? '');
            $buildingCode = trim($data['building_code'] ?? '');
            $room = trim($data['room'] ?? '');

            if (empty($roomCapacity) || (int)$roomCapacity == 0) {
                $skippedRoomCapacity++;
                continue;
            }

            if (empty($buildingCode) || empty($room)) {
                $skippedNoRoom++;
                continue;
            }

            $subjectCode = trim($data['class_subject_code'] ?? '');
            $catalogNumber = trim($data['class_catalog_nbr'] ?? '');
            $termCode = trim($data['cterm_term_cd'] ?? '');

            if (empty($subjectCode) || empty($catalogNumber) || empty($termCode)) {
                $skippedMissingFields++;
                continue;
            }

            try {
                $componentCode = trim($data['class_component_code'] ?? '');
                $roomTypeCode = trim($data['room_type_code'] ?? '');
                $facilityType = ($componentCode === 'LAB')
                    ? 'LAB'
                    : $this->mapRoomTypeToFacilityType($roomTypeCode);

                // Term
                if (!isset($termCache[$termCode])) {
                    $termCache[$termCode] = Term::firstOrCreate(
                        ['term_code' => $termCode],
                        ['term_descr' => trim($data['term'] ?? 'Unknown')]
                    )->id;
                }
                $termId = $termCache[$termCode];

                $campusName = trim($data['class_campus'] ?? '');
                $campusName = $this->normalizeCourseCampusName($campusName);
                $campusId = null;
                if ($campusName !== '') {
                    if (!isset($campusCache[$campusName])) {
                        $campusCache[$campusName] = Campus::firstOrCreate(['name' => $campusName])->id;
                    }
                    $campusId = $campusCache[$campusName];
                }

                $numericCode = $alphaToNumeric[$buildingCode] ?? $buildingCode;
                if (!isset($buildingCache[$numericCode])) {
                    $building = Building::where('building_code', $numericCode)->first();
                    if (!$building) {
                        $building = Building::create([
                            'building_code' => $numericCode,
                            'short_building_name' => $buildingCode,
                            'description' => $buildingCode,
                            'type' => $facilityType,
                            'campus_id' => $campusId,
                        ]);
                    }
                    $buildingCache[$numericCode] = $building;
                }
                $building = $buildingCache[$numericCode];

                $roomNumberNorm = $this->normalizeRoomNumber($room);
                $roomCacheKey = $numericCode . '|' . $roomNumberNorm;
                if (!isset($roomCache[$roomCacheKey])) {
                    $existingRoom = Room::where('building_id', $building->id)
                        ->where('room_number', $roomNumberNorm)
                        ->first();

                    if (!$existingRoom) {
                        $existingRoom = Room::create([
                            'building_id' => $building->id,
                            'room_number' => $roomNumberNorm,
                            'capacity' => (int)$roomCapacity,
                            'room_description' => $room,
                            'sa_facility_type' => $facilityType,
                        ]);
                    }
                    $roomCache[$roomCacheKey] = $existingRoom->id;
                    $roomIsLabCache[$roomCacheKey] = $this->roomIsLab($existingRoom->sa_facility_type);
                }
                $roomId = $roomCache[$roomCacheKey];
                $isLab = $roomIsLabCache[$roomCacheKey];

                // Duration
                $classDurationWeekly = trim($data['class_duration'] ?? '');
                $durationMinutes = 0;
                if (!empty($classDurationWeekly)) {
                    if (strpos($classDurationWeekly, ':') !== false) {
                        $parts = explode(':', $classDurationWeekly);
                        $durationMinutes = (int)$parts[0] * 60 + (int)($parts[1] ?? 0);
                    } else {
                        $durationMinutes = (int)$classDurationWeekly;
                    }
                }

                $classDescr = trim($data['short_class_description'] ?? '');
                if ($classDescr === '') {
                    $classDescr = trim($data['course_description'] ?? '');
                }

                // Course
                $courseKey = $subjectCode . '|' . $catalogNumber . '|' . $termId;
                if (!isset($courseCache[$courseKey])) {
                    $courseCache[$courseKey] = Course::firstOrCreate(
                        ['subject_code' => $subjectCode, 'catalog_number' => $catalogNumber, 'term_id' => $termId],
                        [
                            'class_descr' => $classDescr,
                            'wsch_max' => 'wsch_max',
                            'term_id' => $termId,
                            'class_duration_weekly' => $classDurationWeekly ?: null,
                            'duration_minutes' => $durationMinutes,
                            'division' => trim($data['class_academic_career'] ?? ''),
                        ]
                    )->id;
                }
                $courseId = $courseCache[$courseKey];

                $startTime = trim($data['class_start_time'] ?? '') ?: '00:00:00';
                $endTime = trim($data['class_end_time'] ?? '') ?: '00:00:00';

                if ($now === null) {
                    $now = now();
                }
                $sectionBatch[] = [
                    'section_number' => trim($data['class_section'] ?? ''),
                    'course_id' => $courseId,
                    'enrol_cap' => (int)($data['enrollment_cap'] ?? 0),
                    'day10_enrol' => (int)($data['day10_enroll'] ?? 0),
                    'component_code' => $componentCode,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'days' => $classDays,
                    'sunday' => $sunday,
                    'monday' => $monday,
                    'tuesday' => $tuesday,
                    'wednesday' => $wednesday,
                    'thursday' => $thursday,
                    'friday' => $friday,
                    'saturday' => $saturday,
                    'total_class_days' => $totalClassDays,
                    'room_id' => $roomId,
                    'is_lab' => $isLab,
                    'campus_id' => $campusId,
                    'class_acad_org' => trim($data['class_acad_org'] ?? '') ?: null,
                    'enrollments_by_dept' => '[]',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($sectionBatch) >= $batchSize) {
                    DB::table('sections')->insert($sectionBatch);
                    $processedCount += count($sectionBatch);
                    $sectionBatch = [];
                    if ($processedCount % 1000 === 0) {
                        echo "Processed {$processedCount} rows...\n";
                    }
                }
            } catch (\Exception $e) {
                echo "Error processing row {$rowCount}: " . $e->getMessage() . "\n";
                echo "Subject: {$subjectCode}, Catalog: {$catalogNumber}, Term: {$termCode}\n";
                continue;
            }
        }

        if (!empty($sectionBatch)) {
            DB::table('sections')->insert($sectionBatch);
            $processedCount += count($sectionBatch);
        }
        fclose($file);
        echo "\n=== Summary ===\n";
        echo "Total rows read: {$rowCount}\n";
        echo "Rows processed: {$processedCount}\n";
        echo "Skipped - Year < 2023: {$skippedYear}\n";
        echo "Skipped - No room capacity: {$skippedRoomCapacity}\n";
        echo "Skipped - No building/room: {$skippedNoRoom}\n";
        echo "Skipped - Missing required fields: {$skippedMissingFields}\n";
        echo "Skipped - Instruction Mode: {$skippedInstructionMode}\n";
        echo "Skipped - No class days (NNNNNNN or none): {$skippedClassDays}\n";
    }

    private function mapRoomTypeToFacilityType($roomTypeCode)
    {
        $mapping = [
            'ACTV' => 'Special',
            'ARTG' => 'Special',
            'AUD' => 'Classroom',
            'CLIN' => 'LAB',
            'LSA' => 'Classroom',
            'CMLB' => 'LAB',
            'CMPL' => 'LAB',
            'CONF' => 'Classroom',
            'CRTR' => 'Special',
            'CSRA' => 'Classroom',
            'CSRM' => 'Classroom',
            'GENP' => 'Special',
            'LAB' => 'LAB',
            'LNGE' => 'Special',
            'LVST' => 'Special',
            'MCHS' => 'Special',
            'MULT' => 'Special',
            'MUSI' => 'Special',
            'NRR' => 'NO ROOM',
            'RSRC' => 'Special',
            'PHYL' => 'LAB',
            'HTEC' => 'Classroom',
        ];

        return $mapping[$roomTypeCode] ?? $roomTypeCode;
    }

    private function normalizeRoomNumber(string $roomNumber): string
    {
        $stripped = preg_replace('/[A-Za-z]/', '', trim($roomNumber));
        return $stripped !== '' ? $stripped : trim($roomNumber);
    }

    private const CAMPUS_NAME_MAP = [
        'Storrs'            => 'STORRS CAMPUS',
        'Hartford'          => 'HARTFORD REGIONAL CAMPUS',
        'Stamford'          => 'STAMFORD REGIONAL CAMPUS',
        'Waterbury'         => 'WATERBURY REGIONAL CAMPUS',
        'Avery Point'       => 'AVERY POINT REGIONAL CAMPUS',
    ];

    private function normalizeCourseCampusName(string $name): string
    {
        return self::CAMPUS_NAME_MAP[$name] ?? $name;
    }

    private function roomIsLab(?string $saFacilityType): bool
    {
        if ($saFacilityType === null || $saFacilityType === '') {
            return false;
        }
        return str_contains(strtolower($saFacilityType), 'laboratory') || $saFacilityType === 'LAB';
    }

    private function loadAlphaToNumericMapping(): array
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
        if ($idxBldgNum === false || $idxCode === false) {
            fclose($fh);
            return [];
        }
        $map = [];
        while ($row = fgetcsv($fh)) {
            if (count($row) <= max($idxBldgNum, $idxCode)) {
                continue;
            }
            $bldgNum = trim($row[$idxBldgNum] ?? '');
            $code = trim($row[$idxCode] ?? '');
            if ($code !== '' && $bldgNum !== '') {
                $map[$code] = $bldgNum;
            }
        }
        fclose($fh);
        return $map;
    }
}

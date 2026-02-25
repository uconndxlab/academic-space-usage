<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Term;
use App\Models\Building;
use App\Models\Room;
use App\Models\Course;
use App\Models\Section;
use App\Models\Campus;

/**
 * New CSV format columns:
 * CTERM_TERM_CD, Term, Acad_Year, Class_Subject_Code, Class_Catalog_NBR, Class_Section,
 * Class_Component_Code, Day10_Enroll, Enrollment_Cap, building_code, Room, Room_Type_Code,
 * Room_Capacity, Class_Days, Class_Duration, Class_Start_Time, Class_End_Time, Class_Campus,
 * Short_Class_Description, Course_Description, Class_Academic_Career, etc.
 */
class NewDataStructure extends Seeder
{
    private array $buildingCodeMapping = [];

    public function run()
    {
        $this->buildingCodeMapping = $this->loadBuildingCodeMapping();

        // Read the CSV file
        $file = fopen(database_path('2-2-2026-data.csv'), 'r');
        if (!$file) {
            throw new \Exception('Could not open 2-2-2026-data.csv file');
        }

        $headers = fgetcsv($file);
        if (!$headers) {
            fclose($file);
            throw new \Exception('Could not read CSV headers');
        }

        // Normalize headers to lowercase for consistent access
        $headers = array_map('strtolower', $headers);

        $rowCount = 0;
        $processedCount = 0;
        $skippedYear = 0;
        $skippedRoomCapacity = 0;
        $skippedNoRoom = 0;
        $skippedMissingFields = 0;
        $skippedInstructionMode = 0;
        $skippedClassDays = 0;

        $termCache = [];
        $buildingCache = [];
        $roomCache = [];
        $courseCache = [];
        $campusCache = [];
        $sectionBatch = [];
        $batchSize = 500;
        $now = null;

        echo "Starting to read CSV file...\n";
        echo "Headers found: " . count($headers) . " columns\n";
        echo "Building code mapping entries: " . count($this->buildingCodeMapping) . "\n";

        // Map CSV headers to the corresponding model attributes
        while ($row = fgetcsv($file)) {
            $rowCount++;
            
            // Skip if row is shorter than headers (handles malformed rows)
            if (count($row) < count($headers)) {
                // Pad with empty strings to match header count
                $row = array_pad($row, count($headers), '');
            }
            
            $data = array_combine($headers, $row);
            
            if (!$data) {
                continue; // Skip rows that can't be combined
            }

            // Skip data older than 2023
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

            // Parse Class_Days early so we only add rooms for rows that have at least one meeting day
            // Format [YNYNYNN] = Sun-Sat (7 days). Y = class that day, N = no class
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

            // Get room information
            $roomCapacity = trim($data['room_capacity'] ?? '');
            $buildingCode = (string)trim($data['building_code'] ?? '');
            $room = trim($data['room'] ?? '');

            // Skip if room capacity is 0, null, or empty (need at least some room info)
            if (empty($roomCapacity) || (int)$roomCapacity == 0) {
                $skippedRoomCapacity++;
                if ($processedCount == 0 && $skippedRoomCapacity <= 5) {
                    echo "Row {$rowCount}: Skipped - Room_Capacity: '{$roomCapacity}'\n";
                }
                continue;
            }

            // Skip if building or room is missing (only process rows with room assignments)
            if (empty($buildingCode) || empty($room)) {
                $skippedNoRoom++;
                if ($processedCount == 0 && $skippedNoRoom <= 5) {
                    echo "Row {$rowCount}: Skipped - Building: '{$buildingCode}', Room: '{$room}'\n";
                }
                continue;
            }

            // Skip if required course fields are missing
            $subjectCode = trim($data['class_subject_code'] ?? '');
            $catalogNumber = trim($data['class_catalog_nbr'] ?? '');
            $termCode = trim($data['cterm_term_cd'] ?? '');
            
            if (empty($subjectCode) || empty($catalogNumber) || empty($termCode)) {
                $skippedMissingFields++;
                if ($processedCount == 0 && $skippedMissingFields <= 5) {
                    echo "Row {$rowCount}: Skipped - Missing required fields (Subject: '{$subjectCode}', Catalog: '{$catalogNumber}', Term: '{$termCode}')\n";
                }
                continue;
            }
            if ($processedCount == 0) {
                echo "Row {$rowCount}: Processing - Acad_Year: {$acadYear}, Subject: {$subjectCode}, Catalog: {$catalogNumber}, Building: {$buildingCode}, Room: {$room}\n";
            }

            try {
                $componentCode = trim($data['class_component_code'] ?? '');
                $roomTypeCode = trim($data['room_type_code'] ?? '');
                $facilityType = ($componentCode === 'LAB')
                    ? 'LAB'
                    : $this->mapRoomTypeToFacilityType($roomTypeCode);

                // Term (cached)
                if (!isset($termCache[$termCode])) {
                    $termCache[$termCode] = Term::firstOrCreate(
                        ['term_code' => $termCode],
                        ['term_descr' => trim($data['term'] ?? 'Unknown')]
                    )->id;
                }
                $termId = $termCache[$termCode];

                // Building (cached) – map raw code to shorthand and short name via bulding_code_mapping.csv
                $mapped = $this->buildingCodeMapping[trim($buildingCode)] ?? null;
                $canonicalCode = $mapped ? $mapped['code'] : $buildingCode;
                $shortName = $mapped ? $mapped['name'] : $buildingCode;
                if (!isset($buildingCache[$buildingCode])) {
                    $buildingCache[$buildingCode] = Building::firstOrCreate(
                        ['building_code' => $canonicalCode],
                        [
                            'short_building_name' => $shortName,
                            'description' => $shortName,
                            'type' => $facilityType,
                        ]
                    );
                }
                $building = $buildingCache[$buildingCode];

                // Room (cached by building_id + room_number)
                $roomKey = $building->id . '|' . $room;
                if (!isset($roomCache[$roomKey])) {
                    $roomCache[$roomKey] = Room::firstOrCreate(
                        ['building_id' => $building->id, 'room_number' => $room],
                        [
                            'capacity' => (int)$roomCapacity,
                            'room_description' => $room,
                            'sa_facility_type' => $facilityType,
                        ]
                    )->id;
                }
                $roomId = $roomCache[$roomKey];

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

                // Course (cached)
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

                // Campus (cached) for section
                $campusName = trim($data['class_campus'] ?? '');
                $campusId = null;
                if ($campusName !== '') {
                    if (!isset($campusCache[$campusName])) {
                        $campusCache[$campusName] = Campus::firstOrCreate(['name' => $campusName])->id;
                    }
                    $campusId = $campusCache[$campusName];
                }

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
                    'component_code' => trim($data['class_component_code'] ?? ''),
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
                    'campus_id' => $campusId,
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

    /**
     * Load building code mapping from bulding_code_mapping.csv.
     * Keys: Building_number and Building_Code (so source data can use either).
     * Values: ['code' => Building_Code, 'name' => Short_building_name].
     */
    private function loadBuildingCodeMapping(): array
    {
        $path = database_path('bulding_code_mapping.csv');
        if (!is_readable($path)) {
            return [];
        }
        $map = [];
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
        $idxManual = array_search('Manual_Intervention', $headers);
        $idxBldgNum = array_search('Building_number', $headers);
        $idxCode = array_search('Building_Code', $headers);
        $idxShort = array_search('Short_building_name', $headers);
        if ($idxBldgNum === false || $idxCode === false || $idxShort === false) {
            fclose($fh);
            return [];
        }
        while ($row = fgetcsv($fh)) {
            if (count($row) <= max($idxBldgNum, $idxCode, $idxShort)) {
                continue;
            }
            $bldgNum = trim($row[$idxBldgNum] ?? '');
            $code = trim($row[$idxCode] ?? '');
            $shortName = trim($row[$idxShort] ?? '');
            if ($code === '') {
                continue;
            }
            $entry = ['code' => $code, 'name' => $shortName];
            if ($bldgNum !== '') {
                $map[$bldgNum] = $entry;
            }
            $map[$code] = $entry;
        }
        fclose($fh);
        return $map;
    }

    /**
     * Map Room_Type_Code to facility type
     */
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
            'PHYL' => 'LAB', // Physics Lab
            'HTEC' => 'Classroom', // High Tech Classroom
        ];

        return $mapping[$roomTypeCode] ?? $roomTypeCode;
    }
}

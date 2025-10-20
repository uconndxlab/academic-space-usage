<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Term;
use App\Models\Course;
use App\Models\Section;
use App\Models\Building;
use App\Models\Room;
use App\Models\Campus;
use App\Models\Crosslist;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComprehensiveDataImporter extends Seeder
{
    private $stats = [
        'rows_processed' => 0,
        'terms_created' => 0,
        'courses_created' => 0,
        'sections_created' => 0,
        'buildings_created' => 0,
        'rooms_created' => 0,
        'campuses_created' => 0,
        'crosslists_created' => 0,
        'errors' => [],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = database_path('comprehensive_data.csv');

        if (!file_exists($filePath)) {
            $this->command->error("CSV file not found at: {$filePath}");
            $this->command->info("Please place your CSV file at database/comprehensive_data.csv");
            return;
        }

        $this->command->info("Starting import from: {$filePath}");
        $startTime = microtime(true);

        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            $this->command->info("CSV Headers detected: " . implode(', ', array_slice($csv->getHeader(), 0, 5)) . '...');

            DB::beginTransaction();

            foreach ($csv as $index => $row) {
                try {
                    $this->processRow($row);
                    $this->stats['rows_processed']++;

                    if ($this->stats['rows_processed'] % 100 === 0) {
                        $this->command->info("Processed {$this->stats['rows_processed']} rows...");
                    }
                } catch (\Exception $e) {
                    $this->stats['errors'][] = "Row {$index}: " . $e->getMessage();
                    Log::error("Import error on row {$index}", [
                        'error' => $e->getMessage(),
                        'row' => $row
                    ]);
                }
            }

            DB::commit();

            $duration = round(microtime(true) - $startTime, 2);
            $this->displayStats($duration);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Import failed: " . $e->getMessage());
            Log::error("Import failed", ['error' => $e->getMessage()]);
        }
    }

    private function processRow(array $row): void
    {
        // Get or create Term
        $term = $this->getOrCreateTerm($row);
        
        // Get or create Campus
        $campus = $this->getOrCreateCampus($row);
        
        // Get or create Building
        $building = $this->getOrCreateBuilding($row);
        
        // Get or create Room
        $room = $this->getOrCreateRoom($row, $building, $campus);
        
        // Get or create Course
        $course = $this->getOrCreateCourse($row, $term);
        
        // Handle Crosslist if present
        $crosslist = $this->getOrCreateCrosslist($row);
        
        // Create Section
        $this->createSection($row, $course, $room, $campus, $crosslist);
    }

    private function getOrCreateTerm(array $row): Term
    {
        $termCode = $row['CTERM_TERM_CD'] ?? null;
        $termDescr = $row['Term'] ?? null;

        if (!$termCode) {
            throw new \Exception("Missing term code");
        }

        $term = Term::firstOrCreate(
            ['term_code' => $termCode],
            ['term_descr' => $termDescr ?? $termCode]
        );

        if ($term->wasRecentlyCreated) {
            $this->stats['terms_created']++;
        }

        return $term;
    }

    private function getOrCreateCampus(array $row): ?Campus
    {
        $campusName = $row['Class_Campus'] ?? null;

        if (!$campusName) {
            return null;
        }

        $campus = Campus::firstOrCreate(['name' => $campusName]);

        if ($campus->wasRecentlyCreated) {
            $this->stats['campuses_created']++;
        }

        return $campus;
    }

    private function getOrCreateBuilding(array $row): ?Building
    {
        $buildingCode = $row['Building_Code'] ?? null;

        if (!$buildingCode || strtoupper($buildingCode) === 'TBA' || empty(trim($buildingCode))) {
            return null;
        }

        $building = Building::firstOrCreate(
            ['building_code' => $buildingCode],
            ['description' => $buildingCode] // Will use code as description if not provided
        );

        if ($building->wasRecentlyCreated) {
            $this->stats['buildings_created']++;
        }

        return $building;
    }

    private function getOrCreateRoom(array $row, ?Building $building, ?Campus $campus): ?Room
    {
        if (!$building) {
            return null;
        }

        $roomNumber = $row['Room'] ?? null;
        $roomCapacity = $row['Room_Capacity'] ?? 0;
        $roomType = $row['Room_Type_Code'] ?? null;

        if (!$roomNumber || strtoupper($roomNumber) === 'TBA' || empty(trim($roomNumber))) {
            return null;
        }

        $roomDescription = $building->building_code . ' ' . $roomNumber;

        $room = Room::firstOrCreate(
            [
                'building_id' => $building->id,
                'room_number' => $roomNumber,
            ],
            [
                'room_description' => $roomDescription,
                'capacity' => (int)$roomCapacity,
                'sa_facility_type' => $roomType,
            ]
        );

        if ($room->wasRecentlyCreated) {
            $this->stats['rooms_created']++;
        }

        return $room;
    }

    private function getOrCreateCourse(array $row, Term $term): Course
    {
        $subjectCode = $row['Class_Subject_Code'] ?? null;
        $catalogNumber = $row['Class_Catalog_NBR'] ?? null;
        $classDescr = $row['Short_Class_Description'] ?? null;

        if (!$subjectCode || !$catalogNumber) {
            throw new \Exception("Missing subject code or catalog number");
        }

        // Parse duration - "Class_Duration" appears to be in minutes
        $durationMinutes = $this->parseDuration($row['Class_Duration'] ?? null);

        $course = Course::firstOrCreate(
            [
                'term_id' => $term->id,
                'subject_code' => $subjectCode,
                'catalog_number' => $catalogNumber,
            ],
            [
                'class_descr' => $classDescr,
                'course_description' => $row['Course_Description'] ?? null,
                'course_topic_description' => $row['Course_Topic_Description'] ?? null,
                'duration_minutes' => $durationMinutes,
                'class_duration_weekly' => $row['Class_Duration'] ?? null,
                'acad_org_code' => $row['Class_Acad_Org_Code'] ?? null,
                'acad_org' => $row['Class_Acad_Org'] ?? null,
                'academic_career_code' => $row['Class_Academic_Career_Code'] ?? null,
                'academic_career' => $row['Class_Academic_Career'] ?? null,
                'academic_group_code' => $row['Class_Academic_Group_Code'] ?? null,
                'academic_group' => $row['Class_Academic_Group'] ?? null,
                'grading_basis' => $row['Course_Grading_Basis'] ?? null,
                'max_credits' => $this->parseDecimal($row['Max_Credits'] ?? null),
                'min_credits' => $this->parseDecimal($row['Min_Credits'] ?? null),
                'variable_credits_flag' => $this->parseBoolean($row['Variable_Credits_Flag'] ?? null),
                'allow_multi_enroll_flag' => $this->parseBoolean($row['Allow_Multi_Enroll_Flag'] ?? null),
                'course_fee_flag' => $this->parseBoolean($row['Course_Fee_Flag'] ?? null),
                'allowable_finaid_credits' => $this->parseDecimal($row['Allowable_FinAid_Credits'] ?? null),
                'course_repeat_limit' => $this->parseInt($row['Course_Repeat_Limit'] ?? null),
                'equivalent_course_id' => $row['Equivalent_Course_ID'] ?? null,
                'equivalent_course' => $row['Equivalent_Course'] ?? null,
                'course_id' => $row['Course_ID'] ?? null,
                'course_sid' => $row['Course_SID'] ?? null,
            ]
        );

        if ($course->wasRecentlyCreated) {
            $this->stats['courses_created']++;
        }

        return $course;
    }

    private function getOrCreateCrosslist(array $row): ?Crosslist
    {
        $crosslistId = $row['crosslist_id'] ?? null;

        if (!$crosslistId || empty(trim($crosslistId))) {
            return null;
        }

        $crosslist = Crosslist::firstOrCreate(
            ['crosslist_id' => $crosslistId],
            [
                'crosslist_descr' => $row['crosslist_descr'] ?? null,
                'crosslist_combination_type' => $row['Crosslist_Combination_Type'] ?? null,
                'crosslisted_enrollment_cap' => $this->parseInt($row['Crosslisted_Enrollment_Cap'] ?? null),
                'crosslisted_enrollment_total' => $this->parseInt($row['Crosslisted_Enrollment_Total'] ?? null),
                'crosslist_dup' => $this->parseBoolean($row['crosslist_dup'] ?? null),
            ]
        );

        if ($crosslist->wasRecentlyCreated) {
            $this->stats['crosslists_created']++;
        }

        return $crosslist;
    }

    private function createSection(array $row, Course $course, ?Room $room, ?Campus $campus, ?Crosslist $crosslist): Section
    {
        $section = Section::create([
            'course_id' => $course->id,
            'section_number' => $row['Class_Section'] ?? null,
            'component_code' => $row['Class_Component_Code'] ?? null,
            'enrol_cap' => $this->parseInt($row['Enrollment_Cap'] ?? 0),
            'day10_enrol' => $this->parseInt($row['Day10_Enroll'] ?? 0),
            'room_id' => $room?->id,
            'campus_id' => $campus?->id,
            'start_time' => $this->parseTime($row['Class_Start_Time'] ?? null),
            'end_time' => $this->parseTime($row['Class_End_Time'] ?? null),
            'days' => $row['Class_Days'] ?? null,
            'instruction_mode_code' => $row['Instruction_Mode_Code'] ?? null,
            'instruction_mode' => $row['Instruction_Mode'] ?? null,
            'class_type_code' => $row['Class_Type_Code'] ?? null,
            'class_type_descr' => $row['Class_Type_Descr'] ?? null,
            'class_location' => $row['Class_Location'] ?? null,
            'meeting_pattern_descr' => $row['Meeting_Pattern_Descr'] ?? null,
            'standard_meeting_pattern' => $this->parseBoolean($row['Standard_Meeting_Pattern'] ?? 'Y'),
            'class_start_date' => $this->parseDate($row['CLASS_START_DATE'] ?? null),
            'class_end_date' => $this->parseDate($row['CLASS_END_DATE'] ?? null),
            'class_duration' => $this->parseInt($row['Class_Duration'] ?? null),
            'room_capacity_request' => $this->parseInt($row['Room_Capacity_Request'] ?? null),
            'associated_class' => $row['Associated_Class'] ?? null,
            'number_of_pis' => $this->parseInt($row['Number_of_PIs'] ?? null) ?? 0,
            'number_of_sis' => $this->parseInt($row['Number_of_SIs'] ?? null) ?? 0,
            'number_of_tas' => $this->parseInt($row['Number_of_TAs'] ?? null) ?? 0,
            'autoenroll_section_key' => $row['Autoenroll_Section_Key'] ?? null,
            'autoenroll_1' => $row['Autoenroll_1'] ?? null,
            'autoenroll_2' => $row['Autoenroll_2'] ?? null,
            'class_sid' => $row['CLASS_SID'] ?? null,
            'class_session_cd' => $row['CLASS_SESSION_CD'] ?? null,
            'course_offer_sid' => $row['Course_Offer_SID'] ?? null,
            'course_offer_number' => $this->parseInt($row['Course_Offer_Number'] ?? null),
            'class_number' => $this->parseInt($row['Class_Number'] ?? null),
            'course_topic_id' => $row['Course_Topic_ID'] ?? null,
            'class_sched_print_instr' => $this->parseBoolean($row['CLASS_SCHED_PRINT_INSTR'] ?? 'Y'),
            'class_primary_key' => $row['Class_Primary_Key'] ?? null,
            'duplicate_meeting_flag' => $this->parseBoolean($row['Duplicate_Meeting_Flag'] ?? 'N'),
            'crosslisted_course_flag' => $this->parseBoolean($row['CrossListed_Course_Flag'] ?? 'N'),
            'crosslist_id' => $crosslist?->id,
        ]);

        $this->stats['sections_created']++;

        return $section;
    }

    // Helper methods for parsing
    private function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        $value = strtoupper(trim((string)$value));
        return in_array($value, ['Y', 'YES', 'TRUE', '1', 'T']);
    }

    private function parseInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int)$value;
    }

    private function parseDecimal($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (float)$value;
    }

    private function parseTime($value): ?string
    {
        if (!$value || empty(trim($value))) {
            return null;
        }

        // Handle various time formats
        $value = trim($value);
        
        // If already in HH:MM:SS format
        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
            return strlen($value) === 5 ? $value . ':00' : $value;
        }
        
        // If in format like "1030" or "10:30 AM"
        // Add more parsing logic as needed based on actual data format
        
        return null;
    }

    private function parseDate($value): ?string
    {
        if (!$value || empty(trim($value))) {
            return null;
        }

        try {
            $date = new \DateTime($value);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseDuration($value): ?int
    {
        if (!$value || empty(trim($value))) {
            return null;
        }

        // Assuming duration is already in minutes
        // Adjust if format is different (e.g., "1:30" for 1 hour 30 mins)
        return (int)$value;
    }

    private function displayStats(float $duration): void
    {
        $this->command->info("\n" . str_repeat('=', 60));
        $this->command->info("Import Complete!");
        $this->command->info(str_repeat('=', 60));
        $this->command->info("Duration: {$duration} seconds");
        $this->command->info("Rows Processed: {$this->stats['rows_processed']}");
        $this->command->info("\nRecords Created:");
        $this->command->info("  Terms: {$this->stats['terms_created']}");
        $this->command->info("  Campuses: {$this->stats['campuses_created']}");
        $this->command->info("  Buildings: {$this->stats['buildings_created']}");
        $this->command->info("  Rooms: {$this->stats['rooms_created']}");
        $this->command->info("  Courses: {$this->stats['courses_created']}");
        $this->command->info("  Sections: {$this->stats['sections_created']}");
        $this->command->info("  Crosslists: {$this->stats['crosslists_created']}");

        if (count($this->stats['errors']) > 0) {
            $this->command->warn("\nErrors encountered: " . count($this->stats['errors']));
            $this->command->warn("First 5 errors:");
            foreach (array_slice($this->stats['errors'], 0, 5) as $error) {
                $this->command->warn("  - {$error}");
            }
            $this->command->info("\nCheck storage/logs/laravel.log for full error details");
        }
        
        $this->command->info(str_repeat('=', 60) . "\n");
    }
}

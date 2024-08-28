<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Department;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;

class Over190Seeder extends Seeder
{
    public function run()
    {
        // Clear existing data (compatible with SQLite)
        Room::query()->delete();
        Course::query()->delete();
        Enrollment::query()->delete();
        Department::query()->delete();
        DB::table('course_department')->delete();

        // Load the CSV file
        $csv = Reader::createFromPath(database_path('over-190.csv'), 'r');
        $csv->setHeaderOffset(0); // Assume the first row is the header

        $records = $csv->getRecords();

        foreach ($records as $record) {
            // Create or find the Room
            $room = Room::firstOrCreate([
                'building' => $record['building'],
                'building_code' => $record['building_code'],
                'room_number' => $record['Room'],
                'room_description' => $record['Room_Description'],
                'capacity' => $record['Capacity'],
            ]);

            // Create the Course
            $course = $room->courses()->create([
                'subject_code' => $record['Subject_Code'],
                'catalog_number' => $record['Catalog_Number'],
                'section' => $record['Section'],
                'class_descr' => $record['CLASS_DESCR'],
                'term_code' => $record['Term_Code'],
                'term' => $record['TERM'],
                'class_duration_weekly' => $record['class_duration_weekly'],
                'duration_minutes' => $record['Duration_minutes'],
                'division' => $record['Division'],
                'component_code' => $record['Component_Code'],
                'class_nbr' => $record['Class_NBR'],
            ]);

            // Create the Enrollment
            $course->enrollments()->create([
                'day10_enroll' => $record['Day10_Enroll'],
                'wsch_max' => $record['WSCH_Max'],
                'enrl_cap' => $record['Enrl_Cap'],
            ]);

            // Create or attach Departments
            foreach ($record as $key => $value) {
                if (!empty($value) && !in_array($key, ['Room_Utilization', 'Seat_Utilization', 'Include Y/N', 'Subject_Code', 'BLDG_ROOM', 'Subject_Course', 'CLASS_DESCR', 'building', 'building_code', 'Room', 'Term_Code', 'TERM', 'Catalog_Number', 'Section', 'class_duration_weekly', 'Duration_minutes', 'Division', 'Component_Code', 'Class_NBR', 'Day10_Enroll', 'WSCH_Max', 'Enrl_Cap', 'Capacity', 'Room_Description', 'Sum_Enrollment', 'Bldg_Description'])) {
                    $department = Department::firstOrCreate(['name' => $key]);
                    $course->departments()->attach($department);
                }
            }
        }
    }
}

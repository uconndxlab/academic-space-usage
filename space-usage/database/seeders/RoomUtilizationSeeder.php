<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Room;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Support\Facades\File;
use League\Csv\Reader;

class RoomUtilizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Path to your CSV file
        $filePath = database_path('over-190.csv');

        // Load the CSV file
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0); // Use the first row as headers

        // Iterate over the CSV rows
        foreach ($csv as $row) {
            // Find or create the room
            $room = Room::firstOrCreate(
                [
                    'building' => $row['building'], // building
                    'room_number' => $row['Room'], // Room
                ],
                [
                    'capacity' => $row['Capacity'], // Capacity
                    'description' => $row['Room_Description'], // Room_Description
                ]
            );

            // Create the course
            $course = Course::create([
                'subject_code' => $row['Subject_Code'], // Subject_Code
                'course_number' => $row['Catalog_Number'], // Catalog_Number
                'section' => $row['Section'], // Section
                'term_code' => $row['Term_Code'], // Term_Code
                'class_duration_weekly' => $row['class_duration_weekly'], // class_duration_weekly
                'description' => $row['CLASS_DESCR'], // CLASS_DESCR
            ]);

            // Create the enrollment data
            Enrollment::create([
                'course_id' => $course->id,
                'room_id' => $room->id,
                'projected_enrollment' => $row['Day10_Enroll'], // Day10_Enroll
                'actual_enrollment' => $row['Day10_Enroll'], // Assuming this is actual enrollment
            ]);
        }
    }
}

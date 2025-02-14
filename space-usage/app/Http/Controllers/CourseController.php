<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Section;
use App\Models\Campus;
use Illuminate\Http\Request;
use App\Models\Room;

class CourseController
{
    public function index()
    {
        $departments = Course::select('subject_code')->distinct()->pluck('subject_code')->sort();
        $facilityTypes = Room::select('sa_facility_type')->distinct()->pluck('sa_facility_type')->sort();
        $campuses = Campus::orderBy('name')->get();
    
        // Query sections with relationships
        $sections = Section::query()->with(['course', 'room', 'room.building']);
    
        if (request('campus')) {
            $campus = Campus::find(request('campus'));
            $sections->whereHas('room', function ($query) use ($campus) {
                $query->where('campus_id', $campus->id);
            });
        }
    
        if (request('sa_facility_type')) {
            $sections->whereHas('room', function ($query) {
                $query->where('sa_facility_type', request('sa_facility_type'));
            });
        }
    
        if (request('department')) {
            $sections->whereHas('course', function ($query) {
                $query->where('subject_code', request('department'));
            });
        }
    
        // Get filtered sections and group them by course
        $filteredSections = $sections->get();
        $courses = $filteredSections->groupBy('course_id')->map(function ($sections) {
            $course = $sections->first()->course;
    
            $course->total_enrollment = $sections->sum('day10_enrol');
            $course->sections_count = $sections->count();
            $course->rooms_used = $sections->unique('room_id')->count();
            $course->total_capacity = $sections->unique('room_id')->sum('room.capacity');
    
            // WSCH calculation
            $course->total_wsch = ceil(($course->total_enrollment * $course->duration_minutes) / 60);
    
            // WSCH Benchmark
            $roomCapacity = optional($sections->first()->room)->capacity ?? 1; // Avoid division by zero
            $course->wsch_benchmark = round(28 * ($roomCapacity * 0.8), -1);
    
            // Rooms Needed
            $course->rooms_needed = round($course->total_wsch / $course->wsch_benchmark, 2);

            
    
            // Delta
            $course->delta = $course->rooms_used - $course->rooms_needed;
    
            return $course;
        })->values(); // Reset array keys
    
        return view('courses.index', compact('courses', 'departments', 'campuses', 'facilityTypes'));
    }
    









    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $course = Course::find($id);
        $currentEnrollment = $course->sections->sum('day10_enrol'); // Example: current enrollment
        $componentCodes = $course->sections->pluck('component_code')->unique();
        $campuses = Campus::orderBy('name')->get(); // Default campus list

        return view('courses.show', compact('course', 'currentEnrollment', 'componentCodes', 'campuses'));
    }

    public function simulateRoomNeeds(Request $request, $id)
    {
        $course = Course::find($id);

        // Get inputs from the form
        $currentEnrollment = $request->input('current_enrollment');
        $enrollmentIncrease = $request->input('enrollment_increase', 0);
        $roomCapacity = $request->input('room_capacity');

        // Calculate the total enrollment based on the percentage increase
        $simulatedEnrollment = $currentEnrollment + ($currentEnrollment * ($enrollmentIncrease / 100));

        // Calculate the number of rooms needed
        $roomsNeeded = ceil($simulatedEnrollment / $roomCapacity);

        return view('courses.show', compact('course', 'simulatedEnrollment', 'roomsNeeded', 'currentEnrollment', 'roomCapacity'));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Course $course)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Course $course)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        //
    }
}

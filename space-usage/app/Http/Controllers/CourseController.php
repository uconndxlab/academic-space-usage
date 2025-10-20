<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Section;
use App\Models\Campus;
use App\Models\Term;
use Illuminate\Http\Request;
use App\Models\Room;
use Termwind\Components\Raw;

class CourseController
{
    public function index()
    {
        $departments = Course::select('subject_code')->distinct()->orderBy('subject_code')->pluck('subject_code');
        $facilityTypes = Room::select('sa_facility_type')->distinct()->whereNotNull('sa_facility_type')->orderBy('sa_facility_type')->pluck('sa_facility_type');
        $campuses = Campus::orderBy('name')->get();
        $terms = Term::orderBy('term_code', 'desc')->get();
        
        // Don't load courses unless filters are applied
        if (!request('term_id')) {
            return view('courses.index', [
                'courses' => collect([]),
                'departments' => $departments,
                'campuses' => $campuses,
                'facilityTypes' => $facilityTypes,
                'terms' => $terms,
                'requiresFilter' => true
            ]);
        }
    
        // Query sections with relationships
        $sections = Section::query()->with(['course', 'room', 'room.building']);
        
        // Filter by term (required)
        $sections->whereHas('course', function ($query) {
            $query->where('term_id', request('term_id'));
        });
    
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
    
            // Contact Hours (CH) - total hours per week for all sections
            $course->contact_hours = $sections->sum(function($section) {
                return ($section->course->duration_minutes ?? 0) / 60;
            });
    
            // Total WSCH calculation
            $course->total_wsch = ceil(($course->total_enrollment * ($course->duration_minutes ?? 0)) / 60);
    
            // Average per section
            $course->average_per_section = $course->sections_count > 0 
                ? round($course->total_wsch / $course->sections_count, 2) 
                : 0;
    
            // Enrollment growth 20%
            $course->enroll_growth_20 = ceil($course->total_enrollment * 1.20);
    
            // WSCH growth (20% enrollment increase)
            $course->wsch_growth = ceil(($course->enroll_growth_20 * ($course->duration_minutes ?? 0)) / 60);
    
            // Students per section
            $course->students_per_section = $course->sections_count > 0 
                ? round($course->total_enrollment / $course->sections_count, 2) 
                : 0;
    
            // WSCH Benchmark (updated to 75% utilization)
            $roomCapacity = optional($sections->first()->room)->capacity ?? 1; // Avoid division by zero
            $course->seating_capacity_75_utiliz = round($roomCapacity * 0.75);
            $course->wsch_benchmark = round(28 * $course->seating_capacity_75_utiliz, -1);
    
            // Rooms Needed (based on 75% utilization benchmark)
            $course->rooms_needed = $course->wsch_benchmark > 0 
                ? round($course->total_wsch / $course->wsch_benchmark, 2) 
                : 0;
    
            // Seating range (min-max capacity across rooms)
            $roomCapacities = $sections->pluck('room.capacity')->filter();
            if ($roomCapacities->isNotEmpty()) {
                $minCapacity = $roomCapacities->min();
                $maxCapacity = $roomCapacities->max();
                $course->seating_range = $minCapacity === $maxCapacity 
                    ? (string)$minCapacity 
                    : "{$minCapacity}-{$maxCapacity}";
            } else {
                $course->seating_range = 'N/A';
            }
    
            // Delta (rooms used vs rooms needed)
            $course->delta = $course->rooms_used - $course->rooms_needed;
    
            return $course;
        })->values(); // Reset array keys
    
        return view('courses.index', [
            'courses' => $courses,
            'departments' => $departments,
            'campuses' => $campuses,
            'facilityTypes' => $facilityTypes,
            'terms' => $terms,
            'requiresFilter' => false
        ]);
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
    public function show(Request $request, $id)
    {
        $course = Course::find($id);
        $selectedCampus = $request->input('campus_id', 1); // Default campus ID to 1 if not provided
        $selectedCampus = Campus::find($selectedCampus);
        $facilityType = $request->input('sa_facility_type', "LAB");
        $facilityTypes = Room::select('sa_facility_type')->distinct()->pluck('sa_facility_type')->sort();

    
        // Filter sections based on campus and facility type
        $sections = $course->sections()->whereHas('room', function ($query) use ($selectedCampus, $facilityType) {
            if ($selectedCampus) {
                $query->where('campus_id', $selectedCampus->id);
            }
            if ($facilityType) {
                $query->where('sa_facility_type', $facilityType);
            }
        })->get();
    
        $course->sections = $sections;
    
        $currentEnrollment = $sections->sum('day10_enrol');
        $componentCodes = $sections->pluck('component_code')->unique();
        $campuses = Campus::orderBy('name')->get();
        $selectedFacilityType = $facilityType;
    
        return view('courses.show', compact('course', 'currentEnrollment', 'componentCodes', 'campuses', 'selectedCampus', 'selectedFacilityType', 'facilityTypes'));
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

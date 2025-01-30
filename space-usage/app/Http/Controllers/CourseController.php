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
    // Get all unique departments (subject codes) from the database
    $departments = Course::select('subject_code')->distinct()->pluck('subject_code')->sort();
    $facilityTypes = Room::select('sa_facility_type')->distinct()->pluck('sa_facility_type')->sort();
    $campuses = Campus::orderBy('name')->get(); // Default campus list

    // Initialize sections as an empty query builder instance
    $sections = Section::query();

    // if a campus is selected, select only the courses that are offered on that campus
    if (request('campus')) {
        $campus = Campus::find(request('campus'));
        
        // get the sections that are offered on the selected campus
        $sections = $sections->whereHas('room', function ($query) use ($campus) {
            $query->where('campus_id', $campus->id);
        });
    }

    // if sa_facility_type is selected, further filter the sections where room's sa_facility_type matches the selected sa_facility_type
    if (request('sa_facility_type')) {
        $sections = $sections->whereHas('room', function ($query) {
            $query->where('sa_facility_type', request('sa_facility_type'));
        });
    }

    // if department is selected, further filter the sections where course's subject_code matches the selected subject_code
    if (request('department')) {
        $sections = $sections->whereHas('course', function ($query) {
            $query->where('subject_code', request('department'));
        });
    }

    // get the unique courses from the filtered sections
    $courses = Course::whereIn('id', $sections->pluck('course_id'))->paginate(50);




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

        return view('courses.show', compact('course', 'currentEnrollment'));
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

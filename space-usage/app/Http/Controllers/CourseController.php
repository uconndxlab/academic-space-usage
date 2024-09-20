<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get all unique departments (subject codes) from the database
        $departments = Course::select('subject_code')->distinct()->pluck('subject_code')->sort();
        // Build the initial query
        $query = Course::query();
    
        // If there's a department filter, apply it
        if (request()->has('department') && request('department')) {
            $query->where('subject_code', request('department'));
        }
    
        // Order courses by subject_code and catalog_number
        $courses = $query->orderBy('subject_code')
                         ->orderBy('catalog_number')
                         ->get();
    
        return view('courses.index', compact('courses', 'departments'));
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

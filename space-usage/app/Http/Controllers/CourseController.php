<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Section;
use App\Models\Campus;
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

    // Initialize an empty collection for campuses
    $campuses = collect();

    // Build the initial query
    $query = Course::query();

    // Filter by department if the parameter exists
    if (request()->has('department') && request('department')) {
        $query->where('subject_code', request('department'));

        // Get campuses only for the filtered department
        $campuses = Campus::whereHas('sections.course', function ($q) {
            $q->where('subject_code', request('department'));
        })->get()->sortBy('name');
    } else {
        // If no department filter is applied, fetch all campuses
        $campuses = Campus::all()->sortBy('name');
    }

    // Filter by campus if the parameter exists
    if (request()->has('campus') && request('campus')) {
        $query->whereHas('sections.campus', function ($q) {
            $q->where('id', request('campus'));
        });
    }

    // Handle sorting dynamically
    $sortableColumns = [
        'subject_code', 'catalog_number', 'enrollment', 'sections_count', 
        'rooms_count', 'total_capacity', 'total_wsch', 'wsch_benchmark', 'delta'
    ];

    if (request()->has('order_by') && in_array(request('order_by'), $sortableColumns)) {
        $orderBy = request('order_by');
        $direction = request('direction', 'asc');

        // Add specific column sorting logic here
        if ($orderBy === 'enrollment') {
            $query->withSum('sections', 'day10_enrol')->orderBy('sections_sum_day10_enrol', $direction);
        } elseif ($orderBy === 'sections_count') {
            $query->withCount('sections')->orderBy('sections_count', $direction);
        } elseif ($orderBy === 'rooms_count') {
            $query->withCount(['sections as unique_rooms_count' => function ($q) {
                $q->selectRaw('count(distinct room_id)');
            }])->orderBy('unique_rooms_count', $direction);
        } elseif ($orderBy === 'total_capacity') {
            // $totalCapacity is the sum of unique room capacities
            $query->withCount(['sections as total_capacity' => function ($q) {
                $q->selectRaw('sum(capacity)');
            }])->orderBy('total_capacity', $direction);
        } elseif ($orderBy === 'total_wsch') {
            // total_wsch = total_capacity * wsch
            
        } elseif ($orderBy === 'wsch_benchmark') {
            $query->withSum('sections', 'wsch_benchmark')->orderBy('sections_sum_wsch_benchmark', $direction);
        } elseif ($orderBy === 'delta') {
            $query->withSum('sections', 'delta')->orderBy('sections_sum_delta', $direction);
        } 
        
        else {
            $query->orderBy($orderBy, $direction);
        }
    } else {
        $query->orderBy('subject_code')->orderBy('catalog_number');
    }

    // Paginate the results and append query parameters
    $courses = $query->paginate(80)->appends(request()->query());

    return view('courses.index', compact('courses', 'departments', 'campuses'));
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

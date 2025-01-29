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
        if ($department = request('department')) {
            $query->where('subject_code', $department);
    
            // Optimize campus filtering by using a direct join
            $campuses = Campus::whereIn('id', function ($q) use ($department) {
                $q->select('campus_id')
                  ->from('sections')
                  ->join('courses', 'sections.course_id', '=', 'courses.id')
                  ->where('courses.subject_code', $department);
            })->orderBy('name')->get();
        } else {
            $campuses = Campus::orderBy('name')->get();
        }
    
        // Filter by campus if the parameter exists (use join instead of whereHas)
        if ($campusId = request('campus')) {
            $query->join('sections', 'sections.course_id', '=', 'courses.id')
                  ->where('sections.campus_id', $campusId)
                  ->select('courses.*'); // Avoid selecting unnecessary columns
        }
    
        // Handle sorting dynamically
        $sortableColumns = [
            'subject_code',
            'catalog_number',
            'enrollment',
            'sections_count',
            'rooms_count',
            'total_capacity',
            'total_wsch',
            'wsch_benchmark',
            'delta'
        ];
    
        if ($orderBy = request('order_by')) {
            $direction = request('direction', 'asc');
    
            if (in_array($orderBy, $sortableColumns)) {
                switch ($orderBy) {
                    case 'enrollment':
                        $query->withSum('sections', 'day10_enrol')->orderBy('sections_sum_day10_enrol', $direction);
                        break;
                    case 'sections_count':
                        $query->withCount('sections')->orderBy('sections_count', $direction);
                        break;
                    case 'rooms_count':
                        $query->withCount(['sections as unique_rooms_count' => function ($q) {
                            $q->selectRaw('count(distinct room_id)');
                        }])->orderBy('unique_rooms_count', $direction);
                        break;
                    case 'total_capacity':
                        $query->withCount(['sections as total_capacity' => function ($q) {
                            $q->selectRaw('sum(capacity)');
                        }])->orderBy('total_capacity', $direction);
                        break;
                    case 'wsch_benchmark':
                        $query->withSum('sections', 'wsch_benchmark')->orderBy('sections_sum_wsch_benchmark', $direction);
                        break;
                    case 'delta':
                        $query->withSum('sections', 'delta')->orderBy('sections_sum_delta', $direction);
                        break;
                    default:
                        $query->orderBy($orderBy, $direction);
                }
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

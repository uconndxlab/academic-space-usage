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
        $selectedTerm = request('term');
        $selectedDepartment = request('department');
        $selectedCampus = request('campus');
        $selectedFacilityType = request('sa_facility_type', 'all');
    
        $terms = Term::orderBy('term_code')->get();
        
        // Get initial filter options - filter departments by term if selected
        if ($selectedTerm) {
            $departments = Course::where('term_id', $selectedTerm)
                ->select('subject_code')
                ->distinct()
                ->pluck('subject_code')
                ->sort();
        } else {
            $departments = Course::select('subject_code')->distinct()->pluck('subject_code')->sort();
        }
        
        $campusesQuery = Section::query()
            ->when($selectedTerm, function ($query) use ($selectedTerm) {
                $query->whereHas('course', function ($q) use ($selectedTerm) {
                    $q->where('term_id', $selectedTerm);
                });
            })
            ->when($selectedDepartment, function ($query) use ($selectedDepartment) {
                $query->whereHas('course', function ($q) use ($selectedDepartment) {
                    $q->where('subject_code', $selectedDepartment);
                });
            });
        
        $availableCampusIds = $campusesQuery
            ->whereNotNull('campus_id')
            ->distinct()
            ->pluck('campus_id')
            ->filter()
            ->unique()
            ->values();
        $campuses = Campus::whereIn('id', $availableCampusIds)->orderBy('name')->get();
        
        $facilityTypesQuery = Section::query()
            ->when($selectedTerm, function ($query) use ($selectedTerm) {
                $query->whereHas('course', function ($q) use ($selectedTerm) {
                    $q->where('term_id', $selectedTerm);
                });
            })
            ->when($selectedDepartment, function ($query) use ($selectedDepartment) {
                $query->whereHas('course', function ($q) use ($selectedDepartment) {
                    $q->where('subject_code', $selectedDepartment);
                });
            })
            ->when($selectedCampus, function ($query) use ($selectedCampus) {
                $query->where('campus_id', $selectedCampus);
            });
        $facilityTypes = Room::whereIn('id', $facilityTypesQuery->distinct()->pluck('room_id'))
            ->distinct()
            ->pluck('sa_facility_type')
            ->sort();
    
        $hasAllFilters = !empty($selectedTerm) && !empty($selectedDepartment) && !empty($selectedCampus);
    
        $sectionsData = collect(); 
    
        if ($hasAllFilters) {
            $sections = Section::query()->with(['course', 'room', 'room.building']);
        
            if ($selectedTerm) {
                $sections->whereHas('course', function ($query) use ($selectedTerm) {
                    $query->where('term_id', $selectedTerm);
                });
            }
        
            $campus = Campus::find($selectedCampus);
            if ($campus) {
                $sections->where('campus_id', $campus->id);
            }
        
            if ($selectedFacilityType !== 'all') {
                $sections->whereHas('room', function ($query) use ($selectedFacilityType) {
                    $query->where('sa_facility_type', $selectedFacilityType);
                });
            }
        
            $sections->whereHas('course', function ($query) use ($selectedDepartment) {
                $query->where('subject_code', $selectedDepartment);
            });
        
            // Moved all logic to client side and we just give the data for calculations now
            $sectionsData = $sections->get()->groupBy('course_id')->map(function ($sections) {
                $course = $sections->first()->course;
                $firstRoom = $sections->first()->room;
                
                return [
                    'course_id' => $course->id,
                    'subject_code' => $course->subject_code,
                    'catalog_number' => $course->catalog_number,
                    'class_descr' => $course->class_descr,
                    'duration_minutes' => $course->duration_minutes,
                    'sections' => $sections->map(function ($section) {
                        return [
                            'section_id' => $section->id,
                            'day10_enrol' => $section->day10_enrol,
                            'room_id' => $section->room_id,
                            'room' => $section->room ? [
                                'id' => $section->room->id,
                                'capacity' => $section->room->capacity,
                                'room_number' => $section->room->room_number,
                                'sa_facility_type' => $section->room->sa_facility_type,
                                'building' => $section->room->building ? [
                                    'id' => $section->room->building->id,
                                    'building_code' => $section->room->building->building_code,
                                ] : null,
                            ] : null,
                        ];
                    })->values()->toArray(),
                    'first_room_capacity' => $firstRoom ? $firstRoom->capacity : 1,
                ];
            })->values(); 
        }
    
        return view('courses.index', compact('sectionsData', 'terms', 'departments', 'campuses', 'facilityTypes'));
    }

    /**
     * Get filtered options based on current selections
     */
    public function getFilterOptions(Request $request)
    {
        $term = $request->input('term');
        $department = $request->input('department');
        $campus = $request->input('campus');
        $facilityType = $request->input('sa_facility_type');

        // Get available departments (filter by term, campus and/or facility type if selected)
        $departmentsQuery = Section::query()
            ->with('course')
            ->when($term, function ($query) use ($term) {
                $query->whereHas('course', function ($q) use ($term) {
                    $q->where('term_id', $term);
                });
            })
            ->when($campus, function ($query) use ($campus) {
                $query->where('campus_id', $campus);
            })
            ->when($facilityType && $facilityType !== 'all', function ($query) use ($facilityType) {
                $query->whereHas('room', function ($q) use ($facilityType) {
                    $q->where('sa_facility_type', $facilityType);
                });
            });

        $availableDepartments = Course::whereIn('id', $departmentsQuery->distinct()->pluck('course_id'))
            ->distinct()
            ->pluck('subject_code')
            ->sort()
            ->values();

        // Get available campuses (filter by term, department and/or facility type if selected)
        $campusesQuery = Section::query()
            ->whereHas('room')
            ->when($term, function ($query) use ($term) {
                $query->whereHas('course', function ($q) use ($term) {
                    $q->where('term_id', $term);
                });
            })
            ->when($facilityType && $facilityType !== 'all', function ($query) use ($facilityType) {
                $query->whereHas('room', function ($q) use ($facilityType) {
                    $q->where('sa_facility_type', $facilityType);
                });
            })
            ->when($department, function ($query) use ($department) {
                $query->whereHas('course', function ($q) use ($department) {
                    $q->where('subject_code', $department);
                });
            });

        $availableCampusIds = $campusesQuery
            ->distinct()
            ->pluck('campus_id')
            ->filter()
            ->unique()
            ->values();

        $availableCampuses = Campus::whereIn('id', $availableCampusIds)
            ->orderBy('name')
            ->get()
            ->map(function ($campus) {
                return ['id' => $campus->id, 'name' => $campus->name];
            })
            ->values();

        // Get available facility types (filter by term, department and/or campus if selected)
        $facilityTypesQuery = Section::query()
            ->with('room')
            ->when($term, function ($query) use ($term) {
                $query->whereHas('course', function ($q) use ($term) {
                    $q->where('term_id', $term);
                });
            })
            ->when($department, function ($query) use ($department) {
                $query->whereHas('course', function ($q) use ($department) {
                    $q->where('subject_code', $department);
                });
            })
            ->when($campus, function ($query) use ($campus) {
                $query->where('campus_id', $campus);
            });

        $availableFacilityTypes = Room::whereIn('id', $facilityTypesQuery->distinct()->pluck('room_id'))
            ->distinct()
            ->pluck('sa_facility_type')
            ->sort()
            ->values();

        return response()->json([
            'departments' => $availableDepartments,
            'campuses' => $availableCampuses,
            'facilityTypes' => $availableFacilityTypes,
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
        $sections = $course->sections()
            ->when($selectedCampus, function ($query) use ($selectedCampus) {
                $query->where('campus_id', $selectedCampus->id);
            })
            ->when($facilityType, function ($query) use ($facilityType) {
                $query->whereHas('room', function ($q) use ($facilityType) {
                    $q->where('sa_facility_type', $facilityType);
                });
            })
            ->get();
    
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

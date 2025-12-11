<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Section;
use App\Models\Campus;
use App\Models\Term;
use Illuminate\Http\Request;
use App\Models\Room;

class CourseController
{
    public function index()
    {
        $selectedTerm = request('term');
        $selectedDepartments = request('department', []);
        if (!is_array($selectedDepartments)) {
            $selectedDepartments = $selectedDepartments === 'all' || $selectedDepartments === '' ? [] : [$selectedDepartments];
        }
        $selectedCampus = request('campus');
        $selectedFacilityType = request('sa_facility_type');
        $seatUtilization = request('seat_utilization', 75);
    
        $terms = Term::select('id', 'term_code', 'term_descr')->orderBy('term_code')->get();
        
        $departmentsQuery = Course::select('courses.subject_code')
            ->distinct();
        
        if ($selectedTerm) {
            $departmentsQuery->where('courses.term_id', $selectedTerm);
        }
        
        $departments = $departmentsQuery->pluck('subject_code')->sort();
        
        $campusesQuery = Section::select('sections.campus_id')
            ->join('courses', 'sections.course_id', '=', 'courses.id')
            ->whereNotNull('sections.campus_id')
            ->when($selectedTerm, function ($query) use ($selectedTerm) {
                $query->where('courses.term_id', $selectedTerm);
            })
            ->when(!empty($selectedDepartments), function ($query) use ($selectedDepartments) {
                $query->whereIn('courses.subject_code', $selectedDepartments);
            })
            ->distinct();
        
        $availableCampusIds = $campusesQuery->pluck('campus_id')->filter()->unique()->values();
        $campuses = Campus::select('id', 'name')
            ->whereIn('id', $availableCampusIds)
            ->orderBy('name')
            ->get();
        
        $facilityTypesQuery = Section::select('rooms.sa_facility_type')
            ->join('courses', 'sections.course_id', '=', 'courses.id')
            ->join('rooms', 'sections.room_id', '=', 'rooms.id')
            ->when($selectedTerm, function ($query) use ($selectedTerm) {
                $query->where('courses.term_id', $selectedTerm);
            })
            ->when(!empty($selectedDepartments), function ($query) use ($selectedDepartments) {
                $query->whereIn('courses.subject_code', $selectedDepartments);
            })
            ->when($selectedCampus, function ($query) use ($selectedCampus) {
                $query->where('sections.campus_id', $selectedCampus);
            })
            ->distinct();
        
        $facilityTypes = $facilityTypesQuery->pluck('sa_facility_type')->sort();
    
        $hasAllFilters = !empty($selectedTerm) && !empty($selectedDepartments) && !empty($selectedCampus) && !empty($selectedFacilityType);
    
        $sectionsData = collect();
    
        if ($hasAllFilters) {
            $sections = Section::select([
                'sections.id',
                'sections.section_number',
                'sections.course_id',
                'sections.total_class_days',
                'sections.day10_enrol',
                'sections.room_id',
                'courses.subject_code',
                'courses.catalog_number',
                'courses.class_descr',
                'courses.duration_minutes',
                'rooms.capacity',
                'rooms.room_number',
                'rooms.sa_facility_type',
                'rooms.building_id',
                'buildings.id as building_table_id',
                'buildings.building_code'
            ])
            ->join('courses', 'sections.course_id', '=', 'courses.id')
            ->leftJoin('rooms', 'sections.room_id', '=', 'rooms.id')
            ->leftJoin('buildings', 'rooms.building_id', '=', 'buildings.id')
            ->where('courses.term_id', $selectedTerm)
            ->where('sections.campus_id', $selectedCampus)
            ->whereIn('courses.subject_code', $selectedDepartments);
            
            if ($selectedFacilityType) {
                $sections->where('rooms.sa_facility_type', $selectedFacilityType);
            }
        
            $sectionsDataRaw = $sections->get();
        
            $sectionsData = $sectionsDataRaw->map(function ($section) use ($selectedFacilityType) {
                // Calculate rounded contact hours (rounded up to nearest half hour)
                $contactHours = ($section->duration_minutes ?? 0) / 60;
                $contactHours = (int) ceil($contactHours * 2) / 2;

                // Calculate the WSCH for the section
                $totalClassDays = $section->total_class_days ?? 0;
                $enrollment = $section->day10_enrol ?? 0;
                $wsch = $contactHours * $totalClassDays * $enrollment;
                
                $capacity = $section->capacity ?? 0;
                $facilityType = $section->sa_facility_type ?? $selectedFacilityType;
                
                return [
                    'section_id' => $section->id,
                    'section_number' => $section->section_number,
                    'course_id' => $section->course_id,
                    'subject_code' => $section->subject_code,
                    'catalog_number' => $section->catalog_number,
                    'class_descr' => $section->class_descr,
                    'duration_minutes' => $section->duration_minutes,
                    'contactHours' => $contactHours,
                    'total_class_days' => $totalClassDays,
                    'daysPerWeek' => $totalClassDays,
                    'enrollment' => $enrollment,
                    'capacity' => $capacity,
                    'facilityType' => $facilityType,
                    'wsch' => $wsch,
                    'room' => $section->room_id ? [
                        'id' => $section->room_id,
                        'capacity' => $capacity,
                        'room_number' => $section->room_number,
                        'sa_facility_type' => $facilityType,
                        'building' => $section->building_id ? [
                            'id' => $section->building_table_id ?? $section->building_id,
                            'building_code' => $section->building_code,
                        ] : null,
                    ] : null,
                ];
            })->values();
            
            // Calculate per-campus room counts by seat range. This is used for compare view whole campus buckets.
            $perCampusRoomData = self::getPerCampusRoomData(
                $selectedTerm,
                $selectedDepartments,
                $selectedCampus,
                $selectedFacilityType
            );
            
            // Calculate comparison table data - current ranges only 
            $rangeLabels = ['0-25', '26-49', '50-74', '75-124', '125-174', '175-224', '225-249', '250-299', '300-349', '350-399', '400+'];
            
            // Sum up per-campus room distribution by seat range (current count)
            $currentRanges = array_fill_keys($rangeLabels, 0);
            foreach ($perCampusRoomData as $campusData) {
                foreach ($rangeLabels as $range) {
                    if (isset($campusData['ranges'][$range])) {
                        $currentRanges[$range] += $campusData['ranges'][$range];
                    }
                }
            }
            
            $comparisonData = [];
            foreach ($rangeLabels as $range) {
                $comparisonData[] = [
                    'range' => $range,
                    'current' => $currentRanges[$range],
                ];
            }
        } else {
            $comparisonData = [];
            $perCampusRoomData = [];
        }
    
        return view('courses.index', compact(
            'sectionsData',
            'terms',
            'departments',
            'campuses',
            'facilityTypes',
            'selectedFacilityType',
            'seatUtilization',
            'comparisonData',
            'perCampusRoomData'
        ));
    }
    
    /**
     * Get seating range label for a given seating value
     */
    private static function getSeatingRange($seatingValue)
    {
        if ($seatingValue <= 0) return 'N/A';
        if ($seatingValue <= 25) return '0-25';
        if ($seatingValue <= 49) return '26-49';
        if ($seatingValue <= 74) return '50-74';
        if ($seatingValue <= 124) return '75-124';
        if ($seatingValue <= 174) return '125-174';
        if ($seatingValue <= 224) return '175-224';
        if ($seatingValue <= 249) return '225-249';
        if ($seatingValue <= 299) return '250-299';
        if ($seatingValue <= 349) return '300-349';
        if ($seatingValue <= 399) return '350-399';
        return '400+';
    }
    
    /**
     * Get per-campus room counts grouped by seat range
     * Returns data structure: [campus_id => [campus_name => '...', ranges => [range => count]]]
     */
    private static function getPerCampusRoomData($selectedTerm, $selectedDepartments, $selectedCampus, $selectedFacilityType)
    {
        $rangeLabels = ['0-25', '26-49', '50-74', '75-124', '125-174', '175-224', '225-249', '250-299', '300-349', '350-399', '400+'];
        
        $uniqueRooms = Section::select([
                'sections.campus_id',
                'campuses.name as campus_name',
                'rooms.capacity'
            ])
            ->join('courses', 'sections.course_id', '=', 'courses.id')
            ->join('rooms', 'sections.room_id', '=', 'rooms.id')
            ->join('campuses', 'sections.campus_id', '=', 'campuses.id')
            ->whereNotNull('sections.campus_id')
            ->whereNotNull('sections.room_id')
            ->where('rooms.capacity', '>', 0)
            ->when($selectedTerm, function ($query) use ($selectedTerm) {
                $query->where('courses.term_id', $selectedTerm);
            })
            ->when(!empty($selectedDepartments), function ($query) use ($selectedDepartments) {
                $query->whereIn('courses.subject_code', $selectedDepartments);
            })
            ->when($selectedCampus, function ($query) use ($selectedCampus) {
                $query->where('sections.campus_id', $selectedCampus);
            })
            ->when($selectedFacilityType, function ($query) use ($selectedFacilityType) {
                $query->where('rooms.sa_facility_type', $selectedFacilityType);
            })
            ->groupBy('sections.room_id', 'sections.campus_id', 'campuses.name', 'rooms.capacity')
            ->get();
        
        // Group by campus and calculate range counts
        $perCampusData = [];
        $groupedByCampus = $uniqueRooms->groupBy('campus_id');
        
        foreach ($groupedByCampus as $campusId => $rooms) {
            $campus = $rooms->first();
            $rangeCounts = array_fill_keys($rangeLabels, 0);
            
            foreach ($rooms as $room) {
                $range = self::getSeatingRange($room->capacity ?? 0);
                if ($range !== 'N/A' && isset($rangeCounts[$range])) {
                    $rangeCounts[$range]++;
                }
            }
            
            $perCampusData[$campusId] = [
                'campus_name' => $campus->campus_name,
                'ranges' => $rangeCounts,
                'total_rooms' => $rooms->count()
            ];
        }
        
        return $perCampusData;
    }

    /**
     * Get filtered options based on current selections
     */
    public function getFilterOptions(Request $request)
    {
        $term = $request->input('term');
        $departments = $request->input('department', []);
        if (!is_array($departments)) {
            $departments = $departments === 'all' || $departments === '' ? [] : [$departments];
        }
        $campus = $request->input('campus');
        $facilityType = $request->input('sa_facility_type');

        // Get available departments (filter by term, campus and/or facility type if selected)
        $departmentsQuery = Section::select('courses.subject_code')
            ->join('courses', 'sections.course_id', '=', 'courses.id')
            ->when($facilityType, function ($query) use ($facilityType) {
                $query->join('rooms', 'sections.room_id', '=', 'rooms.id')
                      ->where('rooms.sa_facility_type', $facilityType);
            })
            ->when($term, function ($query) use ($term) {
                $query->where('courses.term_id', $term);
            })
            ->when($campus, function ($query) use ($campus) {
                $query->where('sections.campus_id', $campus);
            })
            ->distinct();

        $availableDepartments = $departmentsQuery->pluck('subject_code')->sort()->values();

        // Get available campuses (filter by term, department and/or facility type if selected)
        $campusesQuery = Section::select('sections.campus_id')
            ->join('courses', 'sections.course_id', '=', 'courses.id')
            ->join('rooms', 'sections.room_id', '=', 'rooms.id')
            ->whereNotNull('sections.campus_id')
            ->when($term, function ($query) use ($term) {
                $query->where('courses.term_id', $term);
            })
            ->when($facilityType, function ($query) use ($facilityType) {
                $query->where('rooms.sa_facility_type', $facilityType);
            })
            ->when(!empty($departments), function ($query) use ($departments) {
                $query->whereIn('courses.subject_code', $departments);
            })
            ->distinct();

        $availableCampusIds = $campusesQuery->pluck('campus_id')->filter()->unique()->values();

        $availableCampuses = Campus::select('id', 'name')
            ->whereIn('id', $availableCampusIds)
            ->orderBy('name')
            ->get()
            ->map(function ($campus) {
                return ['id' => $campus->id, 'name' => $campus->name];
            })
            ->values();

        // Get available facility types (filter by term, department and/or campus if selected)
        $facilityTypesQuery = Section::select('rooms.sa_facility_type')
            ->join('courses', 'sections.course_id', '=', 'courses.id')
            ->join('rooms', 'sections.room_id', '=', 'rooms.id')
            ->when($term, function ($query) use ($term) {
                $query->where('courses.term_id', $term);
            })
            ->when(!empty($departments), function ($query) use ($departments) {
                $query->whereIn('courses.subject_code', $departments);
            })
            ->when($campus, function ($query) use ($campus) {
                $query->where('sections.campus_id', $campus);
            })
            ->distinct();

        $availableFacilityTypes = $facilityTypesQuery->pluck('sa_facility_type')->sort()->values();

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

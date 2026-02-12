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
    /**
     * Normalize department filter from request.
     */
    private static function normalizeDepartments($departmentInput): array
    {
        if (is_array($departmentInput)) {
            return $departmentInput;
        }
        return $departmentInput === 'all' || $departmentInput === '' ? [] : [$departmentInput];
    }

    public function index()
    {
        $selectedTerm = request('term');
        $selectedDepartments = self::normalizeDepartments(request('department', []));
        $selectedCampus = request('campus');
        $selectedFacilityType = request('sa_facility_type');
        $seatUtilization = request('seat_utilization', 75);

        $terms = Term::select('id', 'term_code', 'term_descr')->orderBy('term_code')->get();

        $departmentsQuery = Course::select('courses.subject_code')
            ->distinct()
            ->when($selectedTerm, fn ($q) => $q->where('courses.term_id', $selectedTerm));
        $departments = $departmentsQuery->pluck('subject_code')->sort()->values();

        $campuses = Section::select('campuses.id', 'campuses.name')
            ->join('courses', 'sections.course_id', '=', 'courses.id')
            ->join('campuses', 'sections.campus_id', '=', 'campuses.id')
            ->whereNotNull('sections.campus_id')
            ->when($selectedTerm, fn ($q) => $q->where('courses.term_id', $selectedTerm))
            ->when(!empty($selectedDepartments), fn ($q) => $q->whereIn('courses.subject_code', $selectedDepartments))
            ->distinct()
            ->orderBy('campuses.name')
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
                'sections.campus_id',
                'sections.total_class_days',
                'sections.day10_enrol',
                'sections.room_id',
                'courses.subject_code',
                'courses.catalog_number',
                'courses.class_descr',
                'courses.duration_minutes',
                'campuses.name as campus_name',
                'rooms.capacity',
                'rooms.room_number',
                'rooms.sa_facility_type',
                'rooms.building_id',
                'buildings.id as building_table_id',
                'buildings.building_code'
            ])
            ->join('courses', 'sections.course_id', '=', 'courses.id')
            ->join('campuses', 'sections.campus_id', '=', 'campuses.id')
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

            $rangeLabels = ['0-25', '26-49', '50-74', '75-124', '125-174', '175-224', '225-249', '250-299', '300-349', '350-399', '400+'];
            $perCampusRoomData = self::buildPerCampusRoomDataFromSections($sectionsDataRaw, $rangeLabels);

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

    public function byDayUsage()
    {
        $selectedTerm = request('term');
        $selectedDepartments = self::normalizeDepartments(request('department', []));
        $selectedCampus = request('campus');
        $selectedFacilityType = request('sa_facility_type');
        $seatUtilization = request('seat_utilization', 75);
    
        $terms = Term::select('id', 'term_code', 'term_descr')->orderBy('term_code')->get();
        
        $departmentsQuery = Course::select('courses.subject_code')
            ->distinct();
        
        if ($selectedTerm) {
            $departmentsQuery->where('courses.term_id', $selectedTerm);
        }
        
        $departments = $departmentsQuery->pluck('subject_code')->sort()->values();

        $campuses = Section::select('campuses.id', 'campuses.name')
            ->join('courses', 'sections.course_id', '=', 'courses.id')
            ->join('campuses', 'sections.campus_id', '=', 'campuses.id')
            ->whereNotNull('sections.campus_id')
            ->when($selectedTerm, fn ($q) => $q->where('courses.term_id', $selectedTerm))
            ->when(!empty($selectedDepartments), fn ($q) => $q->whereIn('courses.subject_code', $selectedDepartments))
            ->distinct()
            ->orderBy('campuses.name')
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

        $sectionsDataMWF = collect();
        $sectionsDataTuTh = collect();
        $dayType = request('day_type', 'mwf');
        if (!in_array($dayType, ['mwf', 'tuth'], true)) {
            $dayType = 'mwf';
        }

        if ($hasAllFilters) {
            $sections = Section::select([
                'sections.id',
                'sections.section_number',
                'sections.course_id',
                'sections.campus_id',
                'sections.monday',
                'sections.tuesday',
                'sections.wednesday',
                'sections.thursday',
                'sections.friday',
                'sections.total_class_days',
                'sections.day10_enrol',
                'sections.room_id',
                'courses.subject_code',
                'courses.catalog_number',
                'courses.class_descr',
                'courses.duration_minutes',
                'campuses.name as campus_name',
                'rooms.capacity',
                'rooms.room_number',
                'rooms.sa_facility_type',
                'rooms.building_id',
                'buildings.id as building_table_id',
                'buildings.building_code'
            ])
            ->join('courses', 'sections.course_id', '=', 'courses.id')
            ->join('campuses', 'sections.campus_id', '=', 'campuses.id')
            ->leftJoin('rooms', 'sections.room_id', '=', 'rooms.id')
            ->leftJoin('buildings', 'rooms.building_id', '=', 'buildings.id')
            ->where('courses.term_id', $selectedTerm)
            ->where('sections.campus_id', $selectedCampus)
            ->whereIn('courses.subject_code', $selectedDepartments);

            if ($selectedFacilityType) {
                $sections->where('rooms.sa_facility_type', $selectedFacilityType);
            }

            $sectionsDataRaw = $sections->get();

            $rangeLabels = ['0-25', '26-49', '50-74', '75-124', '125-174', '175-224', '225-249', '250-299', '300-349', '350-399', '400+'];
            $perCampusRoomData = self::buildPerCampusRoomDataFromSections($sectionsDataRaw, $rangeLabels);
            $currentRanges = array_fill_keys($rangeLabels, 0);
            foreach ($perCampusRoomData as $campusData) {
                foreach ($rangeLabels as $range) {
                    $currentRanges[$range] += $campusData['ranges'][$range] ?? 0;
                }
            }
            $comparisonData = [];
            foreach ($rangeLabels as $range) {
                $comparisonData[] = ['range' => $range, 'current' => $currentRanges[$range]];
            }

            $mapSectionToRow = function ($section) use ($selectedFacilityType) {
                $contactHours = ($section->duration_minutes ?? 0) / 60;
                $contactHours = (int) ceil($contactHours * 2) / 2;
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
            };

            $sectionsDataMWF = $sectionsDataRaw->filter(function ($s) {
                return $s->monday || $s->wednesday || $s->friday;
            })->map($mapSectionToRow)->values();
            $sectionsDataTuTh = $sectionsDataRaw->filter(function ($s) {
                return $s->tuesday || $s->thursday;
            })->map($mapSectionToRow)->values();
        } else {
            $comparisonData = [];
            $perCampusRoomData = [];
        }

        return view('courses.byDayUsage', compact(
            'sectionsDataMWF',
            'sectionsDataTuTh',
            'dayType',
            'terms',
            'departments',
            'campuses',
            'facilityTypes',
            'selectedTerm',
            'selectedDepartments',
            'selectedCampus',
            'selectedFacilityType',
            'seatUtilization',
            'hasAllFilters',
            'comparisonData',
            'perCampusRoomData'
        ));
    }

    /**
     * Build per-campus room counts from already-fetched sections
     */
    private static function buildPerCampusRoomDataFromSections($sectionsDataRaw, array $rangeLabels): array
    {
        $perCampusData = [];
        foreach ($sectionsDataRaw->groupBy('campus_id') as $campusId => $sections) {
            $uniqueRooms = $sections->unique(fn ($s) => ($s->room_id ?? '') . '-' . ($s->capacity ?? 0));
            $rangeCounts = array_fill_keys($rangeLabels, 0);
            foreach ($uniqueRooms as $s) {
                $range = self::getSeatingRange($s->capacity ?? 0);
                if ($range !== 'N/A' && isset($rangeCounts[$range])) {
                    $rangeCounts[$range]++;
                }
            }
            $first = $sections->first();
            $perCampusData[$campusId] = [
                'campus_name' => $first->campus_name ?? '',
                'ranges' => $rangeCounts,
                'total_rooms' => $uniqueRooms->count(),
            ];
        }
        return $perCampusData;
    }

    /**
     * Get seating range label for a given capacity value.
     */
    private static function getSeatingRange($seatingValue)
    {
        if ($seatingValue <= 0) {
            return 'N/A';
        }
        if ($seatingValue <= 25) {
            return '0-25';
        }
        if ($seatingValue <= 49) {
            return '26-49';
        }
        if ($seatingValue <= 74) {
            return '50-74';
        }
        if ($seatingValue <= 124) {
            return '75-124';
        }
        if ($seatingValue <= 174) {
            return '125-174';
        }
        if ($seatingValue <= 224) {
            return '175-224';
        }
        if ($seatingValue <= 249) {
            return '225-249';
        }
        if ($seatingValue <= 299) {
            return '250-299';
        }
        if ($seatingValue <= 349) {
            return '300-349';
        }
        if ($seatingValue <= 399) {
            return '350-399';
        }
        return '400+';
    }

    /**
     * Get filtered options based on current selections
     */
    public function getFilterOptions(Request $request)
    {
        $term = $request->input('term');
        $departments = self::normalizeDepartments($request->input('department', []));
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

        // Get available campuses 
        $availableCampuses = Section::select('campuses.id', 'campuses.name')
            ->join('courses', 'sections.course_id', '=', 'courses.id')
            ->join('rooms', 'sections.room_id', '=', 'rooms.id')
            ->join('campuses', 'sections.campus_id', '=', 'campuses.id')
            ->whereNotNull('sections.campus_id')
            ->when($term, fn ($q) => $q->where('courses.term_id', $term))
            ->when($facilityType, fn ($q) => $q->where('rooms.sa_facility_type', $facilityType))
            ->when(!empty($departments), fn ($q) => $q->whereIn('courses.subject_code', $departments))
            ->distinct()
            ->orderBy('campuses.name')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
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
        $course = Course::with('term')->find($id);
        
        $campuses = Campus::whereHas('sections', function ($query) use ($course) {
            $query->where('course_id', $course->id);
        })->orderBy('name')->get();
        
        $campusId = $request->input('campus');
        $selectedCampus = null;
        if ($campusId && $campusId !== '') {
            $selectedCampus = Campus::find($campusId);
        }
        
        // Get facility types
        $facilityTypesQuery = Section::select('rooms.sa_facility_type')
            ->join('rooms', 'sections.room_id', '=', 'rooms.id')
            ->where('sections.course_id', $course->id)
            ->whereNotNull('rooms.sa_facility_type')
            ->when($selectedCampus, function ($query) use ($selectedCampus) {
                $query->where('sections.campus_id', $selectedCampus->id);
            })
            ->distinct();
        
        $facilityTypes = $facilityTypesQuery->pluck('sa_facility_type')->sort();
        
        // Get facility type
        $facilityType = $request->input('sa_facility_type');
        
        // Get day type
        $dayType = $request->input('day_type', 'all');
        if (!in_array($dayType, ['mwf', 'tuth', 'all'], true)) {
            $dayType = 'all';
        }
        
        // Filter sections based on campus and facility type, eager load room relationship
        $sections = $course->sections()
            ->with('room')
            ->when($selectedCampus, function ($query) use ($selectedCampus) {
                $query->where('campus_id', $selectedCampus->id);
            })
            ->when($facilityType && $facilityType !== '', function ($query) use ($facilityType) {
                $query->whereHas('room', function ($q) use ($facilityType) {
                    $q->where('sa_facility_type', $facilityType);
                });
            })
            ->when($dayType === 'mwf', function ($query) {
                $query->where(function ($q) {
                    $q->where('monday', true)
                      ->orWhere('wednesday', true)
                      ->orWhere('friday', true);
                });
            })
            ->when($dayType === 'tuth', function ($query) {
                $query->where(function ($q) {
                    $q->where('tuesday', true)
                      ->orWhere('thursday', true);
                });
            })
            ->get();
        
        $course->sections = $sections;
    
        $currentEnrollment = $sections->sum('day10_enrol');
        $componentCodes = $sections->pluck('component_code')->unique();
        $selectedFacilityType = $facilityType;
    
        return view('courses.show', compact('course', 'currentEnrollment', 'componentCodes', 'campuses', 'selectedCampus', 'selectedFacilityType', 'facilityTypes', 'dayType'));
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

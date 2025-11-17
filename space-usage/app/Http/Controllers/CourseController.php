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
        $selectedDepartments = request('department', []);
        if (!is_array($selectedDepartments)) {
            $selectedDepartments = $selectedDepartments === 'all' || $selectedDepartments === '' ? [] : [$selectedDepartments];
        }
        $selectedCampus = request('campus');
        $selectedFacilityType = request('sa_facility_type');
        $seatUtilization = request('seat_utilization', 75);
    
        $terms = Term::orderBy('term_code')->get();
        
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
            ->when(!empty($selectedDepartments), function ($query) use ($selectedDepartments) {
                $query->whereHas('course', function ($q) use ($selectedDepartments) {
                    $q->whereIn('subject_code', $selectedDepartments);
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
            ->when(!empty($selectedDepartments), function ($query) use ($selectedDepartments) {
                $query->whereHas('course', function ($q) use ($selectedDepartments) {
                    $q->whereIn('subject_code', $selectedDepartments);
                });
            })
            ->when($selectedCampus, function ($query) use ($selectedCampus) {
                $query->where('campus_id', $selectedCampus);
            });
        $facilityTypes = Room::whereIn('id', $facilityTypesQuery->distinct()->pluck('room_id'))
            ->distinct()
            ->pluck('sa_facility_type')
            ->sort();
    
        $hasAllFilters = !empty($selectedTerm) && !empty($selectedDepartments) && !empty($selectedCampus) && !empty($selectedFacilityType);
    
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
        
            if ($selectedFacilityType) {
                $sections->whereHas('room', function ($query) use ($selectedFacilityType) {
                    $query->where('sa_facility_type', $selectedFacilityType);
                });
            }
            
            if (!empty($selectedDepartments)) {
                $sections->whereHas('course', function ($query) use ($selectedDepartments) {
                    $query->whereIn('subject_code', $selectedDepartments);
                });
            }
        
            $sectionsDataRaw = $sections->get();
        
            // Return individual sections with calculated metrics
            $sectionsData = $sectionsDataRaw->map(function ($section) use ($selectedFacilityType, $seatUtilization) {
                $course = $section->course;
                $room = $section->room;
                
                $enrollment = $section->day10_enrol ?? 0;
                $capacity = $room ? ($room->capacity ?? 0) : 0;
                $contactHours = $course->duration_minutes / 60;
                $daysPerWeek = $section->total_class_days ?? 0;
                $wsch = ceil($enrollment * $daysPerWeek * $contactHours);
                $facilityType = $room ? $room->sa_facility_type : $selectedFacilityType;
                
                $isLab = $facilityType && stripos($facilityType, 'LAB') !== false;
                $multiplier = $isLab ? 28 : 30;
                $wschBenchmark = round($capacity * $multiplier, 2);
                $roomsNeeded = $wschBenchmark > 0 ? round($wsch / $wschBenchmark, 2) : 0;
                
                $seatUtilDecimal = $seatUtilization / 100;
                $seating75Util = $seatUtilDecimal > 0 ? round($enrollment / $seatUtilDecimal) : 0;
                $seatingRange = self::getSeatingRange($seating75Util);
                
                return [
                    'section_id' => $section->id,
                    'section_number' => $section->section_number,
                    'course_id' => $course->id,
                    'subject_code' => $course->subject_code,
                    'catalog_number' => $course->catalog_number,
                    'class_descr' => $course->class_descr,
                    'duration_minutes' => $course->duration_minutes,
                    'day10_enrol' => $enrollment,
                    'total_class_days' => $daysPerWeek,
                    'enrollment' => $enrollment,
                    'capacity' => $capacity,
                    'contactHours' => $contactHours,
                    'daysPerWeek' => $daysPerWeek,
                    'wsch' => $wsch,
                    'wschBenchmark' => $wschBenchmark,
                    'roomsNeeded' => $roomsNeeded,
                    'seating75Util' => $seating75Util,
                    'seatingRange' => $seatingRange,
                    'facilityType' => $facilityType,
                    'isLab' => $isLab,
                    'room' => $room ? [
                        'id' => $room->id,
                        'capacity' => $room->capacity,
                        'room_number' => $room->room_number,
                        'sa_facility_type' => $room->sa_facility_type,
                        'building' => $room->building ? [
                            'id' => $room->building->id,
                            'building_code' => $room->building->building_code,
                        ] : null,
                    ] : null,
                ];
            })->values();
            
            // Calculate comparison table data
            $rangeLabels = ['0-25', '26-49', '50-74', '75-124', '125-174', '175-224', '225-249', '250-299', '300-349', '350-399', '400+'];
            $calculatedRanges = array_fill_keys($rangeLabels, 0);
            $currentRanges = array_fill_keys($rangeLabels, 0);
            
            foreach ($sectionsData as $section) {
                $calculatedRange = $section['seatingRange'];
                if ($calculatedRange !== 'N/A' && isset($calculatedRanges[$calculatedRange])) {
                    $calculatedRanges[$calculatedRange]++;
                }
                
                if ($section['capacity'] > 0) {
                    $currentRange = self::getSeatingRange($section['capacity']);
                    if ($currentRange !== 'N/A' && isset($currentRanges[$currentRange])) {
                        $currentRanges[$currentRange]++;
                    }
                }
            }
            
            $comparisonData = [];
            foreach ($rangeLabels as $range) {
                $comparisonData[] = [
                    'range' => $range,
                    'calculated' => $calculatedRanges[$range],
                    'current' => $currentRanges[$range],
                    'difference' => $calculatedRanges[$range] - $currentRanges[$range],
                ];
            }
        } else {
            $comparisonData = [];
        }
    
        return view('courses.index', compact(
            'sectionsData',
            'terms',
            'departments',
            'campuses',
            'facilityTypes',
            'selectedFacilityType',
            'seatUtilization',
            'comparisonData'
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
            ->when($facilityType, function ($query) use ($facilityType) {
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
            ->when($facilityType, function ($query) use ($facilityType) {
                $query->whereHas('room', function ($q) use ($facilityType) {
                    $q->where('sa_facility_type', $facilityType);
                });
            })
            ->when(!empty($departments), function ($query) use ($departments) {
                $query->whereHas('course', function ($q) use ($departments) {
                    $q->whereIn('subject_code', $departments);
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
            ->when(!empty($departments), function ($query) use ($departments) {
                $query->whereHas('course', function ($q) use ($departments) {
                    $q->whereIn('subject_code', $departments);
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

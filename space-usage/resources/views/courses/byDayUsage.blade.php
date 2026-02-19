@extends('layouts.app')
@section('title', 'By Day Usage')

@section('content')
@php
    $sectionsData = in_array($dayType, ['mwf', 'tuth'], true) ? ($dayType === 'tuth' ? $sectionsDataTuTh : $sectionsDataMWF) : collect();
    $rangeLabels = ['0-25', '26-49', '50-74', '75-124', '125-174', '175-224', '225-249', '250-299', '300-349', '350-399', '400+'];
    $currentRangesData = [];
    if ($hasAllFilters && !empty($perCampusRoomData)) {
        foreach ($perCampusRoomData as $campusData) {
            foreach ($rangeLabels as $range) {
                $currentRangesData[$range] = ($currentRangesData[$range] ?? 0) + ($campusData['ranges'][$range] ?? 0);
            }
        }
    }
@endphp

<div class="container">
    <h1 class="mb-4">By Day Usage</h1>

    <div class="mb-4">
        <form method="GET" action="{{ route('courses.byDayUsage') }}" id="filterForm">
            @if($hasAllFilters)
                <input type="hidden" name="day_type" value="{{ $dayType }}">
            @endif
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="termFilter" class="form-label">Filter by Term <span class="text-danger">*</span></label>
                    <select name="term" id="termFilter" class="form-select" required>
                        <option value="">-- Select Term --</option>
                        @foreach ($terms as $term)
                            <option @selected($term->id == $selectedTerm) value="{{ $term->id }}">{{ $term->term_code }} - {{ $term->term_descr }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="departmentFilter" class="form-label">Filter by Department <span class="text-danger">*</span></label>
                    <div class="dropdown" id="departmentDropdown">
                        <button class="form-select text-start" type="button" id="departmentFilterButton" data-bs-toggle="dropdown" aria-expanded="false">
                            <span id="departmentFilterText">Select Departments</span>
                        </button>
                        <ul class="dropdown-menu w-100 p-2" id="departmentDropdownMenu" style="max-height: 300px; overflow-y: auto;" onclick="event.stopPropagation();">
                            <li class="px-2 py-2 sticky-top bg-white" style="z-index: 1;">
                                <input type="text" class="form-control form-control-sm" id="departmentSearch" placeholder="Search departments..." autocomplete="off">
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li class="px-2 py-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAllDepartments">
                                    <label class="form-check-label fw-bold" for="selectAllDepartments">Select All</label>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            @foreach ($departments as $department)
                            <li class="px-2 py-1 department-item" data-department="{{ strtolower($department) }}">
                                <div class="form-check">
                                    <input class="form-check-input department-checkbox" type="checkbox" name="department[]" value="{{ $department }}" id="dept_{{ $loop->index }}" @checked(in_array($department, $selectedDepartments))>
                                    <label class="form-check-label" for="dept_{{ $loop->index }}">{{ $department }}</label>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="campusFilter" class="form-label">Filter by Campus <span class="text-danger">*</span></label>
                    <select name="campus" id="campusFilter" class="form-select" required>
                        <option value="">-- Select Campus --</option>
                        @foreach ($campuses as $campus)
                            <option @selected($campus->id == $selectedCampus) value="{{ $campus->id }}">{{ $campus->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="facilityTypeFilter" class="form-label">Filter by Facility Type <span class="text-danger">*</span></label>
                    <select name="sa_facility_type" id="facilityTypeFilter" class="form-select" required>
                        <option value="">-- Select Facility Type --</option>
                        @foreach ($facilityTypes as $facilityType)
                            <option @selected($facilityType == $selectedFacilityType) value="{{ $facilityType }}">{{ $facilityType }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <input type="submit" value="Filter" class="btn btn-primary" id="filterSubmit">
            </div>
        </form>
    </div>

    @if($hasAllFilters)
        <div id="results">
            <ul class="nav nav-tabs mb-3" id="dayTypeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $dayType === 'mwf' ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['day_type' => 'mwf']) }}" role="tab">MWF (Mon/Wed/Fri)</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $dayType === 'tuth' ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['day_type' => 'tuth']) }}" role="tab">TuTh (Tue/Thu)</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $dayType === 'compare' ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['day_type' => 'compare']) }}" role="tab">Compare</a>
                </li>
            </ul>

            @if($dayType === 'mwf')
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3 align-items-end">
                        <div class="col-md-4">
                            <label for="enrollmentIncrease" class="form-label">Enrollment Increase (%)</label>
                            <input type="number" id="enrollmentIncrease" class="form-control" value="0" min="0" max="100" step="1">
                        </div>
                        <div class="col-md-4">
                            <label for="percentageIncrease" class="form-label">Seat Utilization (%)</label>
                            <input type="number" id="percentageIncrease" class="form-control" value="{{ $seatUtilization ?? ($selectedFacilityType && stripos($selectedFacilityType, 'LAB') !== false ? 80 : 75) }}" min="0" max="100" step="1">
                        </div>
                        <div class="col-md-4">
                            <label for="blockPerDay" class="form-label"># of blocks per day (MWF)</label>
                            <input type="number" id="blockPerDay" class="form-control" value="9" min="1" step="0.1">
                        </div>
                        <div class="col-md-12 mt-2">
                            <button type="button" class="btn btn-primary" id="applyVariablesMWF">Apply</button>
                        </div>
                    </div>
                    @if ($sectionsDataMWF->isEmpty())
                        <p class="text-muted">No sections for MWF with the selected filters.</p>
                    @else
                        <div class="table-scroll-wrapper">
                            <table class="table table-striped table-hover table-sm sticky-header-table" id="mwf-table">
                                <thead class="sticky-top">
                                    <tr class="table-primary">
                                        <th scope="col" data-sort="text">Course</th>
                                        <th scope="col" data-sort="numeric">Enroll</th>
                                        <th scope="col" data-sort="numeric">Capacity</th>
                                        <th scope="col" data-sort="numeric">Duration<br>(minutes)</th>
                                        <th scope="col" data-sort="numeric">Days/<br>Week</th>
                                        <th scope="col" data-sort="numeric">Blocks per <br>Week</th>
                                        <th scope="col" data-sort="numeric">Enroll<br>Growth</th>
                                        <th scope="col" data-sort="numeric">Seat<br>%</th>
                                        <th scope="col" data-sort="numeric">Rooms<br>Needed</th>
                                        <th scope="col" data-sort="text">Seat<br>Range</th>
                                    </tr>
                                </thead>
                                <tbody id="coursesTableBodyMWF">
                                    @foreach($sectionsDataMWF as $section)
                                    @php
                                        $queryParams = array_filter(['campus' => request('campus'), 'sa_facility_type' => request('sa_facility_type'), 'day_type' => 'mwf']);
                                        $queryString = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';
                                    @endphp
                                    <tr class="course-row"
                                        data-original-enrollment="{{ $section['enrollment'] }}"
                                        data-duration-minutes="{{ $section['duration_minutes'] }}"
                                        data-capacity="{{ $section['capacity'] }}"
                                        data-total-class-days="{{ $section['daysPerWeek'] }}"
                                        data-facility-type="{{ $section['facilityType'] }}">
                                        <td><a href="{{ route('courses.show', ['id' => $section['course_id']]) }}{{ $queryString }}">{{ $section['subject_code'] }} {{ $section['catalog_number'] }} - {{ $section['section_number'] }}</a></td>
                                        <td class="forecast-enrollment">{{ \Illuminate\Support\Number::format((int)$section['enrollment']) }}</td>
                                        <td class="forecast-capacity">{{ \Illuminate\Support\Number::format((int)$section['capacity']) }}</td>
                                        <td class="forecast-duration">{{ $section['duration_minutes'] }}</td>
                                        <td class="forecast-days-per-week">{{ \Illuminate\Support\Number::format((int)$section['daysPerWeek']) }}</td>
                                        <td class="forecast-blocks-per-week">{{ $section['daysPerWeek'] * (int)ceil($section['duration_minutes'] / 50) }}</td>
                                        <td class="forecast-enroll-growth">{{ \Illuminate\Support\Number::format((int)$section['enrollment']) }}</td>
                                        <td class="forecast-seating-75"></td>
                                        <td class="forecast-rooms-needed"></td>
                                        <td class="forecast-seating-range"></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            @if($dayType === 'tuth')
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3 align-items-end">
                        <div class="col-md-4">
                            <label for="enrollmentIncrease" class="form-label">Enrollment Increase (%)</label>
                            <input type="number" id="enrollmentIncrease" class="form-control" value="0" min="0" max="100" step="1">
                        </div>
                        <div class="col-md-4">
                            <label for="percentageIncrease" class="form-label">Seat Utilization (%)</label>
                            <input type="number" id="percentageIncrease" class="form-control" value="{{ $seatUtilization ?? ($selectedFacilityType && stripos($selectedFacilityType, 'LAB') !== false ? 80 : 75) }}" min="0" max="100" step="1">
                        </div>
                        <div class="col-md-4">
                            <label for="blockPerDay" class="form-label"># of blocks per day (TuTh)</label>
                            <input type="number" id="blockPerDay" class="form-control" value="6" min="1" step="0.1">
                        </div>
                        <div class="col-md-12 mt-2">
                            <button type="button" class="btn btn-primary" id="applyVariablesTuTh">Apply</button>
                        </div>
                    </div>
                    @if ($sectionsDataTuTh->isEmpty())
                        <p class="text-muted">No sections for TuTh with the selected filters.</p>
                    @else
                        <div class="table-scroll-wrapper">
                            <table class="table table-striped table-hover table-sm sticky-header-table" id="tuth-table">
                                <thead class="sticky-top">
                                    <tr class="table-primary">
                                        <th scope="col" data-sort="text">Course</th>
                                        <th scope="col" data-sort="numeric">Enroll</th>
                                        <th scope="col" data-sort="numeric">Capacity</th>
                                        <th scope="col" data-sort="numeric">Duration<br>(minutes)</th>
                                        <th scope="col" data-sort="numeric">Days/<br>Week</th>
                                        <th scope="col" data-sort="numeric">Blocks per <br>Week</th>
                                        <th scope="col" data-sort="numeric">Enroll<br>Growth</th>
                                        <th scope="col" data-sort="numeric">Seat<br>%</th>
                                        <th scope="col" data-sort="numeric">Rooms<br>Needed</th>
                                        <th scope="col" data-sort="text">Seat<br>Range</th>
                                    </tr>
                                </thead>
                                <tbody id="coursesTableBodyTuTh">
                                    @foreach($sectionsDataTuTh as $section)
                                    @php
                                        $queryParams = array_filter(['campus' => request('campus'), 'sa_facility_type' => request('sa_facility_type'), 'day_type' => 'tuth']);
                                        $queryString = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';
                                    @endphp
                                    <tr class="course-row"
                                        data-original-enrollment="{{ $section['enrollment'] }}"
                                        data-duration-minutes="{{ $section['duration_minutes'] }}"
                                        data-capacity="{{ $section['capacity'] }}"
                                        data-total-class-days="{{ $section['daysPerWeek'] }}"
                                        data-facility-type="{{ $section['facilityType'] }}">
                                        <td><a href="{{ route('courses.show', ['id' => $section['course_id']]) }}{{ $queryString }}">{{ $section['subject_code'] }} {{ $section['catalog_number'] }} - {{ $section['section_number'] }}</a></td>
                                        <td class="forecast-enrollment">{{ \Illuminate\Support\Number::format((int)$section['enrollment']) }}</td>
                                        <td class="forecast-capacity">{{ \Illuminate\Support\Number::format((int)$section['capacity']) }}</td>
                                        <td class="forecast-duration">{{ $section['duration_minutes'] }}</td>
                                        <td class="forecast-days-per-week">{{ \Illuminate\Support\Number::format((int)$section['daysPerWeek']) }}</td>
                                        <td class="forecast-blocks-per-week">{{ $section['daysPerWeek'] * (int)ceil($section['duration_minutes'] / 75) }}</td>
                                        <td class="forecast-enroll-growth">{{ \Illuminate\Support\Number::format((int)$section['enrollment']) }}</td>
                                        <td class="forecast-seating-75"></td>
                                        <td class="forecast-rooms-needed"></td>
                                        <td class="forecast-seating-range"></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            @if($dayType === 'compare')
            <div class="card">
                <div class="card-body">
                    <p class="text-muted mb-4">
                        <strong>Calculated Count</strong> is the sum of "Rooms Needed" for all sections in each seat range. <strong>Current Count</strong> is the number of unique rooms per campus in that seat range.
                    </p>
                    <div class="row mb-4 align-items-end">
                        <div class="col-md-3">
                            <label for="compareEnrollmentIncrease" class="form-label">Enrollment Increase (%)</label>
                            <input type="number" id="compareEnrollmentIncrease" class="form-control" value="0" min="0" max="100" step="1">
                        </div>
                        <div class="col-md-3">
                            <label for="comparePercentageIncrease" class="form-label">Seat Utilization (%)</label>
                            <input type="number" id="comparePercentageIncrease" class="form-control" value="{{ $seatUtilization ?? 75 }}" min="0" max="100" step="1">
                        </div>
                        <div class="col-md-3">
                            <label for="compareBlockPerDayMWF" class="form-label"># of blocks per day (MWF)</label>
                            <input type="number" id="compareBlockPerDayMWF" class="form-control" value="9" min="1" step="0.1">
                        </div>
                        <div class="col-md-3">
                            <label for="compareBlockPerDayTuTh" class="form-label"># of blocks per day (TuTh)</label>
                            <input type="number" id="compareBlockPerDayTuTh" class="form-control" value="6" min="1" step="0.1">
                        </div>
                        <div class="col-md-12 mt-2">
                            <button type="button" class="btn btn-primary" id="applyCompareVariables">Apply</button>
                        </div>
                    </div>

                    <h3 class="mb-3">MWF (Mon/Wed/Fri)</h3>
                    <div class="table-responsive mb-4">
                        <table class="table table-striped table-hover table-sm">
                            <thead>
                                <tr class="table-primary">
                                    <th scope="col">Seat Range</th>
                                    <th scope="col" class="text-end">Calculated Count</th>
                                    <th scope="col" class="text-end">Current Count</th>
                                    <th scope="col" class="text-end">Difference</th>
                                </tr>
                            </thead>
                            <tbody id="comparisonTableBodyMWF">
                                @foreach($rangeLabels as $range)
                                <tr data-range="{{ $range }}">
                                    <td><strong>{{ $range }}</strong></td>
                                    <td class="text-end calculated-count">0</td>
                                    <td class="text-end current-count">{{ $currentRangesData[$range] ?? 0 }}</td>
                                    <td class="text-end difference">0</td>
                                </tr>
                                @endforeach
                                <tr class="table-secondary fw-bold">
                                    <td><strong>Total</strong></td>
                                    <td class="text-end total-calculated">0</td>
                                    <td class="text-end total-current">{{ array_sum($currentRangesData) }}</td>
                                    <td class="text-end total-difference">0</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h3 class="mb-3">TuTh (Tue/Thu)</h3>
                    <div class="table-responsive mb-4">
                        <table class="table table-striped table-hover table-sm">
                            <thead>
                                <tr class="table-primary">
                                    <th scope="col">Seat Range</th>
                                    <th scope="col" class="text-end">Calculated Count</th>
                                    <th scope="col" class="text-end">Current Count</th>
                                    <th scope="col" class="text-end">Difference</th>
                                </tr>
                            </thead>
                            <tbody id="comparisonTableBodyTuTh">
                                @foreach($rangeLabels as $range)
                                <tr data-range="{{ $range }}">
                                    <td><strong>{{ $range }}</strong></td>
                                    <td class="text-end calculated-count">0</td>
                                    <td class="text-end current-count">{{ $currentRangesData[$range] ?? 0 }}</td>
                                    <td class="text-end difference">0</td>
                                </tr>
                                @endforeach
                                <tr class="table-secondary fw-bold">
                                    <td><strong>Total</strong></td>
                                    <td class="text-end total-calculated">0</td>
                                    <td class="text-end total-current">{{ array_sum($currentRangesData) }}</td>
                                    <td class="text-end total-difference">0</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h3 class="mb-3">Combined (max of MWF and TuTh per range)</h3>
                    <div class="table-responsive mb-4">
                        <table class="table table-striped table-hover table-sm">
                            <thead>
                                <tr class="table-primary">
                                    <th scope="col">Seat Range</th>
                                    <th scope="col" class="text-end">Calculated Count</th>
                                    <th scope="col" class="text-end">Current Count</th>
                                    <th scope="col" class="text-end">Difference</th>
                                </tr>
                            </thead>
                            <tbody id="comparisonTableBodyCombined">
                                @foreach($rangeLabels as $range)
                                <tr data-range="{{ $range }}">
                                    <td><strong>{{ $range }}</strong></td>
                                    <td class="text-end calculated-count">0</td>
                                    <td class="text-end current-count">{{ $currentRangesData[$range] ?? 0 }}</td>
                                    <td class="text-end difference">0</td>
                                </tr>
                                @endforeach
                                <tr class="table-secondary fw-bold">
                                    <td><strong>Total</strong></td>
                                    <td class="text-end total-calculated">0</td>
                                    <td class="text-end total-current">{{ array_sum($currentRangesData) }}</td>
                                    <td class="text-end total-difference">0</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @if(!empty($perCampusRoomData))
                    <div class="mt-4">
                        <h3 class="mb-4">Per-Campus Room Distribution by Seat Range</h3>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr class="table-primary">
                                        <th scope="col">Campus</th>
                                        @foreach($rangeLabels as $range)
                                            <th scope="col" class="text-end">{{ $range }}</th>
                                        @endforeach
                                        <th scope="col" class="text-end fw-bold">Total Rooms</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($perCampusRoomData as $campusId => $campusData)
                                        <tr>
                                            <td><strong>{{ $campusData['campus_name'] }}</strong></td>
                                            @foreach($rangeLabels as $range)
                                                <td class="text-end">{{ $campusData['ranges'][$range] ?? 0 }}</td>
                                            @endforeach
                                            <td class="text-end fw-bold">{{ $campusData['total_rooms'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    @else
        <div class="alert alert-info mt-4" role="alert">
            <strong>Please select required filters above and click "Filter" to view by-day usage.</strong>
            <ul class="mt-2 mb-0">
                <li>Term (required)</li>
                <li>Department (required)</li>
                <li>Campus (required)</li>
                <li>Facility Type (required)</li>
            </ul>
        </div>
    @endif
</div>

<style>
    #departmentDropdownMenu { min-width: 100%; }
    #departmentDropdownMenu .form-check-input:checked { background-color: #0d6efd; border-color: #0d6efd; }
    #departmentDropdownMenu .form-check, #departmentDropdownMenu .form-check-label { cursor: pointer; }
    #departmentDropdownMenu .form-check-label { user-select: none; }
    .department-item { display: block; }
    .department-item.hidden { display: none; }
    .sticky-header-table thead.sticky-top { position: sticky; top: 0; z-index: 10; background-color: #002855; color: #ffffff; }
    .sticky-header-table thead.sticky-top th { background-color: #002855; color: #ffffff; }
    th[data-sort] { cursor: pointer; }
    th[data-sort].sort-asc::after { content: ' ▲'; opacity: 0.7; }
    th[data-sort].sort-desc::after { content: ' ▼'; opacity: 0.7; }
</style>

<script>
document.getElementById('filterForm').addEventListener('submit', function(e) {
    const selectedDepts = Array.from(document.querySelectorAll('.department-checkbox:checked')).map(cb => cb.value);
    if (selectedDepts.length === 0) {
        e.preventDefault();
        alert('Please select at least one department.');
        return false;
    }
});

(function() {
    const departmentFilterButton = document.querySelector('#departmentFilterButton');
    const departmentFilterText = document.querySelector('#departmentFilterText');

    function getDepartmentCheckboxes() { return document.querySelectorAll('.department-checkbox'); }

    function updateDepartmentFilterText() {
        const checkboxes = getDepartmentCheckboxes();
        const checked = Array.from(checkboxes).filter(cb => cb.checked);
        if (checked.length === 0) {
            departmentFilterText.textContent = 'Select Departments';
            departmentFilterButton.classList.add('text-muted');
        } else if (checked.length === checkboxes.length) {
            departmentFilterText.textContent = 'All Departments (' + checked.length + ')';
            departmentFilterButton.classList.remove('text-muted');
        } else {
            departmentFilterText.textContent = checked.length + ' Department' + (checked.length > 1 ? 's' : '') + ' Selected';
            departmentFilterButton.classList.remove('text-muted');
        }
    }

    function updateSelectAllState() {
        const visibleItems = Array.from(document.querySelectorAll('.department-item:not(.hidden)'));
        const visibleCheckboxes = visibleItems.map(item => item.querySelector('.department-checkbox')).filter(cb => cb);
        const checked = visibleCheckboxes.filter(cb => cb.checked);
        const currentSelectAll = document.querySelector('#selectAllDepartments');
        if (currentSelectAll) {
            currentSelectAll.checked = visibleCheckboxes.length > 0 && checked.length === visibleCheckboxes.length;
            currentSelectAll.indeterminate = checked.length > 0 && checked.length < visibleCheckboxes.length;
        }
    }

    function handleSelectAll(e) {
        const visibleItems = Array.from(document.querySelectorAll('.department-item:not(.hidden)'));
        const visibleCheckboxes = visibleItems.map(item => item.querySelector('.department-checkbox')).filter(cb => cb);
        visibleCheckboxes.forEach(cb => { cb.checked = e.target.checked; });
        updateDepartmentFilterText();
    }

    function handleDepartmentChange() {
        updateSelectAllState();
        updateDepartmentFilterText();
    }

    function filterDepartments(searchTerm) {
        const searchLower = searchTerm.toLowerCase().trim();
        document.querySelectorAll('.department-item').forEach(item => {
            const label = item.querySelector('.form-check-label');
            const departmentText = label ? label.textContent.toLowerCase() : '';
            const departmentName = item.getAttribute('data-department') || '';
            item.classList.toggle('hidden', searchLower !== '' && !departmentText.includes(searchLower) && !departmentName.includes(searchLower));
        });
        updateSelectAllState();
    }

    getDepartmentCheckboxes().forEach(checkbox => checkbox.addEventListener('change', handleDepartmentChange));
    document.querySelector('#selectAllDepartments')?.addEventListener('change', handleSelectAll);
    document.querySelector('#departmentSearch')?.addEventListener('input', function(e) { filterDepartments(e.target.value); });
    document.querySelector('#departmentSearch')?.addEventListener('keydown', function(e) { e.stopPropagation(); });
    updateDepartmentFilterText();
    updateSelectAllState();
})();
</script>

@if($hasAllFilters && in_array($dayType, ['mwf', 'tuth']) && $sectionsData->isNotEmpty())
<script>
(function() {
    const selectedFacilityType = @json($selectedFacilityType);
    const dayType = @json($dayType);
    const defaultBlocksPerDay = dayType === 'tuth' ? 6 : 9;
    const blockLengthMinutes = dayType === 'tuth' ? 75 : 50;
    const daysInWeek = dayType === 'tuth' ? 2 : 3;

    function getblockPerDay() {
        return parseFloat(document.querySelector('#blockPerDay')?.value) || defaultBlocksPerDay;
    }

    function getTotalBlocksAvailable() {
        return getblockPerDay() * daysInWeek;
    }

    function getSeatingRange(seating75Util) {
        if (seating75Util <= 0) return 'N/A';
        if (seating75Util <= 25) return '0-25';
        if (seating75Util <= 49) return '26-49';
        if (seating75Util <= 74) return '50-74';
        if (seating75Util <= 124) return '75-124';
        if (seating75Util <= 174) return '125-174';
        if (seating75Util <= 224) return '175-224';
        if (seating75Util <= 249) return '225-249';
        if (seating75Util <= 299) return '250-299';
        if (seating75Util <= 349) return '300-349';
        if (seating75Util <= 399) return '350-399';
        return '400+';
    }

    function formatNumber(value, decimals) {
        if (value === null || value === undefined || isNaN(value)) return '0';
        const num = parseFloat(value);
        if (!decimals) return Math.round(num).toLocaleString('en-US');
        return num.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    }

    function updateForecastGrowth(row, growthPercentage) {
        const originalEnrollment = parseFloat(row.getAttribute('data-original-enrollment'));
        const totalClassDays = parseFloat(row.getAttribute('data-total-class-days'));
        const durationMinutes = parseFloat(row.getAttribute('data-duration-minutes')) || 0;
        const seatUtilPercent = parseFloat(document.querySelector('#percentageIncrease')?.value || 75) || 75;
        const seatUtilDecimal = seatUtilPercent / 100;
        const growthEnrollment = Math.round(originalEnrollment * (1 + growthPercentage / 100));
        const seating75Util = seatUtilDecimal > 0 ? Math.round(growthEnrollment / seatUtilDecimal) : 0;

        const blocksNeededByClass = totalClassDays * Math.ceil(durationMinutes / blockLengthMinutes);
        const totalBlocksAvailable = getTotalBlocksAvailable();
        const roomsNeeded = totalBlocksAvailable > 0 ? blocksNeededByClass / totalBlocksAvailable : 0;
        const seatingRange = getSeatingRange(seating75Util);

        row.querySelector('.forecast-enroll-growth').textContent = formatNumber(growthEnrollment);
        row.querySelector('.forecast-seating-75').textContent = formatNumber(seating75Util);
        row.querySelector('.forecast-rooms-needed').textContent = formatNumber(roomsNeeded, 2);
        row.querySelector('.forecast-seating-range').textContent = seatingRange;
    }

    function updateAllTables() {
        const enrollmentIncrease = parseFloat(document.querySelector('#enrollmentIncrease')?.value || 0) || 0;
        document.querySelectorAll('.course-row').forEach(row => updateForecastGrowth(row, enrollmentIncrease));
    }

    document.querySelector('#applyVariablesMWF')?.addEventListener('click', updateAllTables);
    document.querySelector('#applyVariablesTuTh')?.addEventListener('click', updateAllTables);
    updateAllTables();

    const sortState = new Map();
    function sortTableByColumn(table, columnIndex, isNumeric, direction) {
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        const rowsArray = Array.from(tbody.querySelectorAll('tr.course-row'));
        if (rowsArray.length === 0) return;
        rowsArray.sort((a, b) => {
            const aCell = a.querySelector('td:nth-child(' + (columnIndex + 1) + ')');
            const bCell = b.querySelector('td:nth-child(' + (columnIndex + 1) + ')');
            if (!aCell || !bCell) return 0;
            const aText = aCell.textContent.trim();
            const bText = bCell.textContent.trim();
            if (isNumeric) {
                const aNum = parseFloat(aText.replace(/[^0-9.-]/g, '')) || 0;
                const bNum = parseFloat(bText.replace(/[^0-9.-]/g, '')) || 0;
                return direction === 'desc' ? bNum - aNum : aNum - bNum;
            }
            return direction === 'desc' ? bText.localeCompare(aText) : aText.localeCompare(bText);
        });
        rowsArray.forEach(row => tbody.appendChild(row));
    }

    document.querySelectorAll('th[data-sort]').forEach(header => {
        header.setAttribute('title', 'Click to sort');
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const columnIndex = Array.from(this.parentNode.children).indexOf(this);
            const isNumeric = this.getAttribute('data-sort') === 'numeric';
            const currentState = sortState.get(table);
            let newDirection = (currentState && currentState.columnIndex === columnIndex && currentState.direction === 'asc') ? 'desc' : 'asc';
            sortState.set(table, { columnIndex, direction: newDirection });
            sortTableByColumn(table, columnIndex, isNumeric, newDirection);
            table.querySelectorAll('th[data-sort]').forEach(th => th.classList.remove('sort-asc', 'sort-desc'));
            this.classList.add(newDirection === 'asc' ? 'sort-asc' : 'sort-desc');
        });
    });
})();
</script>
@endif

@if($hasAllFilters && $dayType === 'compare')
<script>
(function() {
    const rangeLabels = ['0-25', '26-49', '50-74', '75-124', '125-174', '175-224', '225-249', '250-299', '300-349', '350-399', '400+'];
    const sectionsDataMWF = @json($sectionsDataMWF);
    const sectionsDataTuTh = @json($sectionsDataTuTh);
    const currentRangesData = @json($currentRangesData);
    const blockLengthMWF = 50;
    const blockLengthTuTh = 75;
    const daysPerWeekMWF = 3;
    const daysPerWeekTuTh = 2;

    function getSeatingRange(seating75Util) {
        if (seating75Util <= 0) return 'N/A';
        if (seating75Util <= 25) return '0-25';
        if (seating75Util <= 49) return '26-49';
        if (seating75Util <= 74) return '50-74';
        if (seating75Util <= 124) return '75-124';
        if (seating75Util <= 174) return '125-174';
        if (seating75Util <= 224) return '175-224';
        if (seating75Util <= 249) return '225-249';
        if (seating75Util <= 299) return '250-299';
        if (seating75Util <= 349) return '300-349';
        if (seating75Util <= 399) return '350-399';
        return '400+';
    }

    function computeCalculatedRanges(sections, blockLengthMinutes, daysInWeek, blocksPerDay, enrollmentPct, seatUtilPct) {
        const calculated = {};
        rangeLabels.forEach(r => { calculated[r] = 0; });
        const totalBlocksAvailable = blocksPerDay * daysInWeek;
        const seatUtilDecimal = (seatUtilPct || 75) / 100;
        sections.forEach(sec => {
            const growthEnrollment = Math.round((sec.enrollment || 0) * (1 + (enrollmentPct || 0) / 100));
            const seating75Util = seatUtilDecimal > 0 ? Math.round(growthEnrollment / seatUtilDecimal) : 0;
            const range = getSeatingRange(seating75Util);
            if (range !== 'N/A' && calculated.hasOwnProperty(range)) {
                const blocksNeeded = (sec.daysPerWeek || 0) * Math.ceil((sec.duration_minutes || 0) / blockLengthMinutes);
                const roomsNeeded = totalBlocksAvailable > 0 ? blocksNeeded / totalBlocksAvailable : 0;
                calculated[range] += roomsNeeded;
            }
        });
        return calculated;
    }

    function fillComparisonTable(bodyId, calculatedRanges) {
        const tbody = document.getElementById(bodyId);
        if (!tbody) return;
        let totalCalculated = 0;
        let totalCurrent = 0;
        rangeLabels.forEach(range => {
            const row = tbody.querySelector('tr[data-range="' + range + '"]');
            if (!row) return;
            const calculated = calculatedRanges[range] || 0;
            const calculatedRounded = Math.ceil(calculated);
            const current = currentRangesData[range] || 0;
            const difference = current - calculatedRounded;
            totalCalculated += calculatedRounded;
            totalCurrent += current;
            row.querySelector('.calculated-count').textContent = calculatedRounded;
            const diffCell = row.querySelector('.difference');
            diffCell.textContent = (difference >= 0 ? '+' : '') + difference;
            diffCell.className = 'text-end difference' + (current < calculatedRounded ? ' text-danger' : current > calculatedRounded ? ' text-success' : '');
        });
        const totalRow = tbody.querySelector('tr.table-secondary');
        if (totalRow) {
            totalRow.querySelector('.total-calculated').textContent = totalCalculated;
            const totalDiff = totalCurrent - totalCalculated;
            const totalDiffEl = totalRow.querySelector('.total-difference');
            totalDiffEl.textContent = (totalDiff >= 0 ? '+' : '') + totalDiff;
            totalDiffEl.className = 'text-end total-difference' + (totalCurrent < totalCalculated ? ' text-danger' : totalCurrent > totalCalculated ? ' text-success' : '');
        }
    }

    function updateCompareAll() {
        const enrollmentPct = parseFloat(document.getElementById('compareEnrollmentIncrease')?.value || 0) || 0;
        const seatUtilPct = parseFloat(document.getElementById('comparePercentageIncrease')?.value || 75) || 75;
        const blocksMWF = parseFloat(document.getElementById('compareBlockPerDayMWF')?.value || 9) || 9;
        const blocksTuTh = parseFloat(document.getElementById('compareBlockPerDayTuTh')?.value || 6) || 6;

        const mwfCalculated = computeCalculatedRanges(sectionsDataMWF, blockLengthMWF, daysPerWeekMWF, blocksMWF, enrollmentPct, seatUtilPct);
        const tuthCalculated = computeCalculatedRanges(sectionsDataTuTh, blockLengthTuTh, daysPerWeekTuTh, blocksTuTh, enrollmentPct, seatUtilPct);

        fillComparisonTable('comparisonTableBodyMWF', mwfCalculated);
        fillComparisonTable('comparisonTableBodyTuTh', tuthCalculated);

        const combinedCalculated = {};
        rangeLabels.forEach(r => { combinedCalculated[r] = Math.max(mwfCalculated[r] || 0, tuthCalculated[r] || 0); });
        fillComparisonTable('comparisonTableBodyCombined', combinedCalculated);
    }

    document.getElementById('applyCompareVariables')?.addEventListener('click', updateCompareAll);
    updateCompareAll();
})();
</script>
@endif
@endsection

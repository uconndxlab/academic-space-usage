@extends('layouts.app')
@section('title', 'Course List')
@section('content')
    @php
        $selectedFacilityType = request('sa_facility_type');
        $selectedDepartments = request('department', []);
        if (!is_array($selectedDepartments)) {
            $selectedDepartments = $selectedDepartments === 'all' || $selectedDepartments === '' ? [] : [$selectedDepartments];
        }
        $hasAllFilters = request()->has('term') && !empty($selectedDepartments) && request()->has('campus') 
            && request()->has('sa_facility_type')
            && request('term') !== '' && request('campus') !== '' && request('sa_facility_type') !== '';
    @endphp

    <div class="container">
        <h1 class="mb-4">Course List</h1>

        <div class="mb-4">
            <form method="GET" action="{{ route('courses.index') }}" id="filterForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="termFilter" class="form-label">Filter by Term <span class="text-danger">*</span></label>
                        <select name="term" id="termFilter" class="form-select" required>
                            <option value="">-- Select Term --</option>
                            @foreach ($terms as $term)
                                <option @selected($term->id == request('term')) value="{{ $term->id }}">{{ $term->term_code }} - {{ $term->term_descr }}</option>
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
                                        <label class="form-check-label fw-bold" for="selectAllDepartments">
                                            Select All
                                        </label>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                @foreach ($departments as $department)
                                <li class="px-2 py-1 department-item" data-department="{{ strtolower($department) }}">
                                    <div class="form-check">
                                        <input class="form-check-input department-checkbox" type="checkbox" 
                                            name="department[]" 
                                            value="{{ $department }}" 
                                            id="dept_{{ $loop->index }}"
                                            @checked(in_array($department, $selectedDepartments))>
                                        <label class="form-check-label" for="dept_{{ $loop->index }}">
                                            {{ $department }}
                                        </label>
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
                                <option @selected($campus->id == request('campus')) value="{{ $campus->id }}">{{ $campus->name }}</option>
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
            
            <script>
                document.getElementById('filterForm').addEventListener('submit', function(e) {
                    const selectedDepts = Array.from(document.querySelectorAll('.department-checkbox:checked')).map(cb => cb.value);
                    if (selectedDepts.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one department.');
                        return false;
                    }
                });
            </script>
        </div>

        <style>
            #departmentDropdownMenu {
                min-width: 100%;
            }
            #departmentDropdownMenu .form-check-input:checked {
                background-color: #0d6efd;
                border-color: #0d6efd;
            }
            #departmentDropdownMenu .form-check {
                cursor: pointer;
            }
            #departmentDropdownMenu .form-check-label {
                cursor: pointer;
                user-select: none;
            }
            .department-item {
                display: block;
            }
            .department-item.hidden {
                display: none;
            }
            .sticky-header-table thead.sticky-top {
                position: sticky;
                top: 0;
                z-index: 10;
                background-color: #002855;
                color: #ffffff;
            }
            .sticky-header-table thead.sticky-top th {
                background-color: #002855;
                color: #ffffff;
            }
            th[data-sort] {
                cursor: pointer;
            }
            th[data-sort].sort-asc::after {
                content: ' ▲';
                opacity: 0.7;
            }
            th[data-sort].sort-desc::after {
                content: ' ▼';
                opacity: 0.7;
            }
        </style>
        <script>
            (function() {
                const filters = {
                    term: document.querySelector('#termFilter'),
                    campus: document.querySelector('#campusFilter'),
                    facilityType: document.querySelector('#facilityTypeFilter')
                };
                
                const departmentFilterButton = document.querySelector('#departmentFilterButton');
                const departmentFilterText = document.querySelector('#departmentFilterText');
                
                function getDepartmentCheckboxes() {
                    return document.querySelectorAll('.department-checkbox');
                }
                
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
                
                function attachDepartmentEventListeners() {
                    const checkboxes = getDepartmentCheckboxes();
                    const currentSelectAll = document.querySelector('#selectAllDepartments');
                    
                    if (currentSelectAll) {
                        currentSelectAll.removeEventListener('change', handleSelectAll);
                        currentSelectAll.addEventListener('change', handleSelectAll);
                    }
                    
                    checkboxes.forEach(checkbox => {
                        checkbox.removeEventListener('change', handleDepartmentChange);
                        checkbox.addEventListener('change', handleDepartmentChange);
                    });
                }
                
                function handleSelectAll(e) {
                    const checkboxes = getDepartmentCheckboxes();
                    const visibleItems = Array.from(document.querySelectorAll('.department-item:not(.hidden)'));
                    const visibleCheckboxes = visibleItems.map(item => item.querySelector('.department-checkbox')).filter(cb => cb);
                    
                    visibleCheckboxes.forEach(cb => {
                        cb.checked = e.target.checked;
                    });
                    updateDepartmentFilterText();
                    updateFilterOptions();
                }
                
                function handleDepartmentChange() {
                    updateSelectAllState();
                    updateDepartmentFilterText();
                    updateFilterOptions();
                }
                
                function filterDepartments(searchTerm) {
                    const searchLower = searchTerm.toLowerCase().trim();
                    const departmentItems = document.querySelectorAll('.department-item');
                    let visibleCount = 0;
                    
                    departmentItems.forEach(item => {
                        const departmentName = item.getAttribute('data-department');
                        const label = item.querySelector('.form-check-label');
                        const departmentText = label ? label.textContent.toLowerCase() : '';
                        
                        if (searchLower === '' || departmentText.includes(searchLower) || departmentName.includes(searchLower)) {
                            item.classList.remove('hidden');
                            visibleCount++;
                        } else {
                            item.classList.add('hidden');
                        }
                    });
                    
                    const selectAllItem = document.querySelector('#selectAllDepartments')?.closest('li');
                    if (selectAllItem) {
                        if (visibleCount === 0 && searchLower !== '') {
                            selectAllItem.style.display = 'none';
                        } else {
                            selectAllItem.style.display = 'block';
                        }
                    }
                }
                
                function setupDepartmentSearch() {
                    const searchInput = document.querySelector('#departmentSearch');
                    if (searchInput) {
                        searchInput.addEventListener('input', function(e) {
                            filterDepartments(e.target.value);
                            updateSelectAllState();
                        });
                        
                        searchInput.addEventListener('keydown', function(e) {
                            e.stopPropagation();
                        });
                    }
                }
                
                attachDepartmentEventListeners();
                setupDepartmentSearch();
                updateDepartmentFilterText();
                updateSelectAllState();
                
                const originals = {
                    campus: Array.from(filters.campus.options),
                    facilityType: Array.from(filters.facilityType.options)
                };
                
                async function updateDepartmentDropdown(params) {
                    const response = await fetch('{{ route("courses.filterOptions") }}?' + params.toString());
                    const data = await response.json();
                    
                    const dropdownMenu = document.querySelector('#departmentDropdownMenu');
                    const checkedDepartments = Array.from(getDepartmentCheckboxes())
                        .filter(cb => cb.checked)
                        .map(cb => cb.value);
                    
                    const searchValue = document.querySelector('#departmentSearch')?.value || '';
                    
                    dropdownMenu.innerHTML = `
                        <li class="px-2 py-2 sticky-top bg-white" style="z-index: 1;">
                            <input type="text" class="form-control form-control-sm" id="departmentSearch" placeholder="Search departments..." autocomplete="off" value="${searchValue}">
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li class="px-2 py-1">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAllDepartments">
                                <label class="form-check-label fw-bold" for="selectAllDepartments">
                                    Select All
                                </label>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                    `;
                    
                    if (data.departments && data.departments.length > 0) {
                        data.departments.forEach((dept, index) => {
                            const li = document.createElement('li');
                            li.className = 'px-2 py-1 department-item';
                            li.setAttribute('data-department', dept.toLowerCase());
                            const isChecked = checkedDepartments.includes(dept);
                            li.innerHTML = `
                                <div class="form-check">
                                    <input class="form-check-input department-checkbox" type="checkbox" 
                                        name="department[]" 
                                        value="${dept}" 
                                        id="dept_${index}"
                                        ${isChecked ? 'checked' : ''}>
                                    <label class="form-check-label" for="dept_${index}">
                                        ${dept}
                                    </label>
                                </div>
                            `;
                            dropdownMenu.appendChild(li);
                        });
                    }
                    
                    attachDepartmentEventListeners();
                    setupDepartmentSearch();
                    if (searchValue) {
                        filterDepartments(searchValue);
                    }
                    updateSelectAllState();
                    updateDepartmentFilterText();
                }
                
                async function updateDropdown(filterType, params, dataKey, placeholder, isObject, onReset) {
                    const filter = filters[filterType];
                    const savedValue = filter.value;
                    const response = await fetch('{{ route("courses.filterOptions") }}?' + params.toString());
                    const data = await response.json();
                    
                    filter.innerHTML = placeholder;
                    if (data[dataKey] && data[dataKey].length > 0) {
                        data[dataKey].forEach(item => {
                            const option = document.createElement('option');
                            option.value = isObject ? item.id : item;
                            option.textContent = isObject ? item.name : item;
                            filter.appendChild(option);
                        });
                    }
                    
                    const exists = isObject 
                        ? data[dataKey] && data[dataKey].some(c => String(c.id) === String(savedValue))
                        : data[dataKey] && data[dataKey].includes(savedValue);
                    
                    if (exists) {
                        filter.value = savedValue;
                    } else {
                        filter.value = '';
                        if (onReset) onReset();
                    }
                }
                
                function restoreDropdown(filterType, savedValue, defaultValue = '') {
                    const filter = filters[filterType];
                    filter.innerHTML = '';
                    originals[filterType].forEach(opt => {
                        filter.appendChild(opt.cloneNode(true));
                    });
                    if (savedValue && Array.from(filter.options).some(opt => opt.value == savedValue)) {
                        filter.value = savedValue;
                    } else {
                        filter.value = defaultValue;
                    }
                }
                
                function getSelectedDepartments() {
                    return Array.from(document.querySelectorAll('.department-checkbox:checked')).map(cb => cb.value);
                }
                
                async function updateFilterOptions() {
                    const values = {
                        term: filters.term.value,
                        departments: getSelectedDepartments(),
                        campus: filters.campus.value,
                        facilityType: filters.facilityType.value
                    };
                    
                    try {
                        if (values.term) {
                            const params = new URLSearchParams();
                            params.append('term', values.term);
                            await updateDepartmentDropdown(params);
                        }
                        
                        const selectedDepts = getSelectedDepartments();
                        if (values.term || selectedDepts.length > 0) {
                            const params = new URLSearchParams();
                            if (values.term) params.append('term', values.term);
                            selectedDepts.forEach(dept => {
                                params.append('department[]', dept);
                            });
                            await updateDropdown('campus', params, 'campuses', '<option value="">-- Select Campus --</option>', true, () => {
                                filters.facilityType.value = '';
                            });
                        } else {
                            restoreDropdown('campus', values.campus);
                        }
                        
                        const currentCampus = filters.campus.value;
                        if (values.term || selectedDepts.length > 0 || currentCampus) {
                            const params = new URLSearchParams();
                            if (values.term) params.append('term', values.term);
                            selectedDepts.forEach(dept => {
                                params.append('department[]', dept);
                            });
                            if (currentCampus) params.append('campus', currentCampus);
                            await updateDropdown('facilityType', params, 'facilityTypes', '<option value="">-- Select Facility Type --</option>', false);
                        } else {
                            restoreDropdown('facilityType', values.facilityType, '');
                        }
                    } catch (error) {
                        console.error('Error updating filter options:', error);
                    }
                }
                
                filters.term?.addEventListener('change', updateFilterOptions);
                filters.campus?.addEventListener('change', () => {
                    setTimeout(updateFilterOptions, 0);
                });
                filters.facilityType?.addEventListener('change', function() {
                    const facilityType = this.value;
                    const seatUtilInput = document.querySelector('#percentrageIncrease');
                    if (seatUtilInput && (!seatUtilInput.value || seatUtilInput.value === '')) {
                        const defaultUtil = isLab(facilityType) ? 80 : 75;
                        seatUtilInput.value = defaultUtil;
                    }
                    updateFilterOptions();
                });
                
                if (filters.term?.value || getSelectedDepartments().length > 0) {
                    updateFilterOptions();
                }
            })();
        </script>

        @if($hasAllFilters)
        <div id="results">
            <ul class="nav nav-tabs mb-3" id="viewTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="table-tab" data-bs-toggle="tab" data-bs-target="#table-view" type="button" role="tab" aria-controls="table-view" aria-selected="true">
                        Table View
                    </button>
                </li>
                @if(!empty($selectedFacilityType))
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="compare-tab" data-bs-toggle="tab" data-bs-target="#compare-view" type="button" role="tab" aria-controls="compare-view" aria-selected="false">
                        Compare View
                    </button>
                </li>
                @endif
            </ul>

            <div class="tab-content" id="viewTabsContent">
                <div class="tab-pane fade show active" id="table-view" role="tabpanel" aria-labelledby="table-tab">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="enrollmentIncrease" class="form-label">Enrollment Increase (%)</label>
                                <input type="number" id="enrollmentIncrease" class="form-control" value="0" min="0"
                                    max="100" step="1">
                            </div>
                            <div class="mb-3">
                                <label for="percentrageIncrease" class="form-label">Seat Utilization(%)</label>
                                <input type="number" id="percentrageIncrease" class="form-control" value="{{ $seatUtilization ?? ($selectedFacilityType && stripos($selectedFacilityType, 'LAB') !== false ? 80 : 75) }}" min="0"
                                    max="100" step="1">
                            </div>
                            <div class="mb-3">
                                <label for="wschMultiplier" class="form-label"># of hours in a week</label>
                                <input type="number" id="wschMultiplier" class="form-control" value="30" min="1"
                                    step="0.1">
                            </div>

                            @if ($sectionsData->isEmpty())
                                <p>No courses available.</p>
                            @else
                            <div class="table-scroll-wrapper">
                                <table class="table table-striped table-hover table-sm sticky-header-table">
                                    <thead class="sticky-top">
                                        <tr class="table-primary">
                                            <th scope="col" data-sort="text">Course</th>
                                            <th scope="col" data-sort="numeric">Enroll</th>
                                            <th scope="col" data-sort="numeric">Sec</th>
                                            <th scope="col" data-sort="numeric">Rooms</th>
                                            <th scope="col" data-sort="numeric">Capacity</th>
                                            <th scope="col" data-sort="numeric">CH</th>
                                            <th scope="col" data-sort="numeric">Days/<br>Week</th>
                                            <th scope="col" data-sort="numeric">WSCH</th>
                                            <th scope="col" data-sort="numeric">Enroll<br>Growth</th>
                                            <th scope="col" data-sort="numeric">WSCH<br>Growth</th>
                                            <th scope="col" data-sort="numeric">Seat<br>%</th>
                                            <th scope="col" data-sort="numeric">WSCH<br>Bench</th>
                                            <th scope="col" data-sort="numeric">Rooms<br>Needed</th>
                                            <th scope="col" data-sort="text">Seat<br>Range</th>
                                        </tr>
                                    </thead>
                                    <tbody id="coursesTableBody">
                                        @foreach($sectionsData as $section)
                                        <tr class="course-row" 
                                            data-original-enrollment="{{ $section['enrollment'] }}"
                                            data-duration-minutes="{{ $section['duration_minutes'] }}"
                                            data-capacity="{{ $section['capacity'] }}"
                                            data-total-class-days="{{ $section['daysPerWeek'] }}"
                                            data-contact-hours="{{ $section['contactHours'] }}"
                                            data-facility-type="{{ $section['facilityType'] }}">
                                            <td>
                                                <a href="/course/{{ $section['course_id'] }}">
                                                    {{ $section['subject_code'] }} {{ $section['catalog_number'] }} - {{ $section['section_number'] }}
                                                </a>
                                            </td>
                                            <td class="forecast-enrollment">{{ \Illuminate\Support\Number::format((int)$section['enrollment']) }}</td>
                                            <td class="forecast-sections">1</td>
                                            <td class="forecast-rooms">1</td>
                                            <td class="forecast-capacity">{{ \Illuminate\Support\Number::format((int)$section['capacity']) }}</td>
                                            <td class="forecast-contact-hours">{{ \Illuminate\Support\Number::format($section['contactHours'], 2) }}</td>
                                            <td class="forecast-days-per-week">{{ \Illuminate\Support\Number::format((int)$section['daysPerWeek']) }}</td>
                                            <td class="forecast-wsch">{{ \Illuminate\Support\Number::format($section['wsch']) }}</td>
                                            <td class="forecast-enroll-growth">{{ \Illuminate\Support\Number::format((int)$section['enrollment']) }}</td>
                                            <td class="forecast-wsch-growth">{{ \Illuminate\Support\Number::format($section['wsch']) }}</td>
                                            <td class="forecast-seating-75"></td>
                                            <td class="wsch-benchmark"></td>
                                            <td class="forecast-labs-needed"></td>
                                            <td class="forecast-seating-range"></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if(!empty($selectedFacilityType))
                <div class="tab-pane fade" id="compare-view" role="tabpanel" aria-labelledby="compare-tab">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="mb-4">Seat Range Comparison</h2>
                            <div class="mb-3">
                                <label for="enrollmentIncreaseCompare" class="form-label">Enrollment Increase (%)</label>
                                <input type="number" id="enrollmentIncreaseCompare" class="form-control" value="0" min="0"
                                    max="100" step="1">
                            </div>
                            <div class="mb-3">
                                <label for="percentrageIncreaseCompare" class="form-label">Seat Utilization(%)</label>
                                <input type="number" id="percentrageIncreaseCompare" class="form-control" value="{{ $seatUtilization ?? ($selectedFacilityType && stripos($selectedFacilityType, 'LAB') !== false ? 80 : 75) }}" min="0"
                                    max="100" step="1">
                            </div>
                            <div class="mb-3">
                                <label for="wschMultiplierCompare" class="form-label"># of hours in a week</label>
                                <input type="number" id="wschMultiplierCompare" class="form-control" value="30" min="1"
                                    step="0.1">
                            </div>
                            <p class="text-muted mb-4">
                                This comparison shows the rooms needed vs. available rooms across seat ranges.
                                <strong>Calculated Count</strong> is the sum of "Rooms Needed" for all sections in each seat range, based on enrollment divided by seat utilization.
                                <strong>Current Count</strong> is the total number of unique rooms available per campus, distributed by seat range based on room capacity.
                            </p>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-sm">
                                    <thead>
                                        <tr class="table-primary">
                                            <th scope="col">Seat Range</th>
                                            <th scope="col" class="text-end">Calculated Count</th>
                                            <th scope="col" class="text-end">Current Count</th>
                                            <th scope="col" class="text-end">Difference</th>
                                        </tr>
                                    </thead>
                                    <tbody id="comparisonTableBody">
                                        @php
                                        $rangeLabels = ['0-25', '26-49', '50-74', '75-124', '125-174', '175-224', '225-249', '250-299', '300-349', '350-399', '400+'];
                                    @endphp
                                    @if(!empty($comparisonData))
                                            @php
                                                $currentRangesData = [];
                                                if (!empty($perCampusRoomData)) {
                                                    foreach ($perCampusRoomData as $campusData) {
                                                        foreach ($rangeLabels as $range) {
                                                            if (!isset($currentRangesData[$range])) {
                                                                $currentRangesData[$range] = 0;
                                                            }
                                                            $currentRangesData[$range] += $campusData['ranges'][$range] ?? 0;
                                                        }
                                                    }
                                                }
                                            @endphp
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
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            
                            @if(!empty($perCampusRoomData))
                            <div class="mt-5">
                                <h3 class="mb-4">Per-Campus Room Distribution by Seat Range</h3>
                                <p class="text-muted mb-4">
                                    This table shows the number of unique rooms in each seat range for each campus, based on room capacity.
                                </p>
                                
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
                </div>
                @endif
            </div>
            
            <script>
                const selectedFacilityType = @json($selectedFacilityType);
                const rangeLabels = ['0-25', '26-49', '50-74', '75-124', '125-174', '175-224', '225-249', '250-299', '300-349', '350-399', '400+'];
                
                function isLab(facilityType) {
                    if (!facilityType) return false;
                    return facilityType.toUpperCase().includes('LAB');
                }
                
                function getWSCHMultiplier(facilityType) {
                    const wschMultiplierInput = document.querySelector('#wschMultiplier');
                    const wschMultiplierCompareInput = document.querySelector('#wschMultiplierCompare');
                    const multiplier = parseFloat(wschMultiplierInput?.value || wschMultiplierCompareInput?.value) || 30;
                    return multiplier;
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
                
                function formatNumber(value, decimals = 0) {
                    if (value === null || value === undefined || isNaN(value)) return '0';
                    const num = parseFloat(value);
                    if (decimals === 0) {
                        return Math.round(num).toLocaleString('en-US');
                    } else {
                        return num.toLocaleString('en-US', { 
                            minimumFractionDigits: decimals, 
                            maximumFractionDigits: decimals 
                        });
                    }
                }
                
                function parseFormattedNumber(value) {
                    if (!value) return 0;
                    // Remove commas and parse
                    return parseFloat(value.toString().replace(/,/g, '')) || 0;
                }
                
                function getSeatUtilizationValue() {
                    const seatUtilInput = document.querySelector('#percentrageIncrease');
                    const seatUtilCompareInput = document.querySelector('#percentrageIncreaseCompare');
                    const seatUtilPercent = parseFloat(seatUtilInput?.value || seatUtilCompareInput?.value) || 75;
                    return seatUtilPercent;
                }
                
                function getEnrollmentIncreaseValue() {
                    const enrollmentInput = document.querySelector('#enrollmentIncrease');
                    const enrollmentCompareInput = document.querySelector('#enrollmentIncreaseCompare');
                    const enrollmentIncrease = parseFloat(enrollmentInput?.value || enrollmentCompareInput?.value) || 0;
                    return enrollmentIncrease;
                }
                
                function roundToHalfOrFull(value) {
                    return Math.ceil(value * 2) / 2;
                }
                
                function updateForecastGrowth(row, growthPercentage) {
                    const originalEnrollment = parseFloat(row.getAttribute('data-original-enrollment'));
                    const totalClassDays = parseFloat(row.getAttribute('data-total-class-days'));
                    const contactHours = parseFloat(row.getAttribute('data-contact-hours')); // This is already rounded CH
                    const facilityType = row.getAttribute('data-facility-type') || selectedFacilityType;
                    
                    const seatUtilPercent = getSeatUtilizationValue();
                    const seatUtilDecimal = seatUtilPercent / 100;
                    
                    const growthEnrollment = Math.round(originalEnrollment * (1 + growthPercentage / 100));
                    // WSCH = enrollment * rounded CH * meetings per week, rounded up to nearest half or full integer
                    const wschGrowth = roundToHalfOrFull(growthEnrollment * contactHours * totalClassDays);
                    const seating75Util = seatUtilDecimal > 0 ? Math.round(growthEnrollment / seatUtilDecimal) : 0;
                    const multiplier = getWSCHMultiplier(facilityType);
                    // WSCH Benchmark = (rounded CH * meetings per week) / (# of hours a week - now an input)
                    const wschBenchmark = (contactHours * totalClassDays) / multiplier;
                    const roomsNeeded = contactHours > 0 ? (contactHours * totalClassDays) / multiplier : 0;
                    const seatingRange = getSeatingRange(seating75Util);
                    
                    row.querySelector('.forecast-enroll-growth').textContent = formatNumber(growthEnrollment);
                    row.querySelector('.forecast-wsch-growth').textContent = formatNumber(wschGrowth);
                    row.querySelector('.forecast-seating-75').textContent = formatNumber(seating75Util);
                    row.querySelector('.wsch-benchmark').textContent = formatNumber(wschBenchmark, 2);
                    row.querySelector('.forecast-labs-needed').textContent = formatNumber(roomsNeeded, 2);
                    row.querySelector('.forecast-seating-range').textContent = seatingRange;
                }
                
                function updateAllTables() {
                    const enrollmentIncrease = getEnrollmentIncreaseValue();
                    document.querySelectorAll('.course-row').forEach(row => {
                        updateForecastGrowth(row, enrollmentIncrease);
                    });
                    updateCompareView();
                }
                
                function updateCompareView() {
                    const calculatedRanges = {};
                    rangeLabels.forEach(range => {
                        calculatedRanges[range] = 0;
                    });
                    
                    // Sum up rooms needed by seat range from table rows
                    document.querySelectorAll('.course-row').forEach(row => {
                        const seatingRange = row.querySelector('.forecast-seating-range')?.textContent.trim();
                        const roomsNeeded = parseFormattedNumber(row.querySelector('.forecast-labs-needed')?.textContent.trim() || 0);
                        
                        if (seatingRange && seatingRange !== 'N/A' && calculatedRanges.hasOwnProperty(seatingRange)) {
                            calculatedRanges[seatingRange] += roomsNeeded;
                        }
                    });
                    
                    // Update comparison table
                    const comparisonBody = document.querySelector('#comparisonTableBody');
                    if (!comparisonBody) return;
                    
                    let totalCalculated = 0;
                    let totalCurrent = 0;
                    
                    rangeLabels.forEach(range => {
                        const row = comparisonBody.querySelector(`tr[data-range="${range}"]`);
                        if (!row) return;
                        
                        const calculated = calculatedRanges[range] || 0;
                        const calculatedRounded = Math.ceil(calculated);
                        const current = parseFloat(row.querySelector('.current-count')?.textContent.trim() || 0);
                        const difference = current - calculatedRounded;
                        
                        totalCalculated += calculatedRounded;
                        totalCurrent += current;
                        
                        row.querySelector('.calculated-count').textContent = calculatedRounded;
                        const diffCell = row.querySelector('.difference');
                        diffCell.textContent = (difference >= 0 ? '+' : '') + difference;
                        // Red if existing rooms (current) < needed (calculated) - we need more rooms (negative difference)
                        // Green if existing rooms (current) > needed (calculated) - we have excess (positive difference)
                        if (current < calculatedRounded) {
                            diffCell.className = 'text-end difference text-danger';
                        } else if (current > calculatedRounded) {
                            diffCell.className = 'text-end difference text-success';
                        } else {
                            diffCell.className = 'text-end difference';
                        }
                    });
                    
                    // Update totals row
                    const totalRow = comparisonBody.querySelector('tr.table-secondary');
                    if (totalRow) {
                        const totalDiff = totalCurrent - totalCalculated;
                        totalRow.querySelector('.total-calculated').textContent = totalCalculated;
                        totalRow.querySelector('.total-difference').textContent = (totalDiff >= 0 ? '+' : '') + totalDiff;
                        // Apply color to total difference as well
                        const totalDiffCell = totalRow.querySelector('.total-difference');
                        if (totalCurrent < totalCalculated) {
                            totalDiffCell.className = 'text-end total-difference text-danger';
                        } else if (totalCurrent > totalCalculated) {
                            totalDiffCell.className = 'text-end total-difference text-success';
                        } else {
                            totalDiffCell.className = 'text-end total-difference';
                        }
                    }
                }
                
                function syncInputs(sourceInput, targetInput) {
                    if (sourceInput && targetInput && sourceInput.value !== targetInput.value) {
                        targetInput.value = sourceInput.value;
                    }
                }
                
                function setupInputSync() {
                    const enrollmentInput = document.querySelector('#enrollmentIncrease');
                    const enrollmentCompareInput = document.querySelector('#enrollmentIncreaseCompare');
                    const seatUtilInput = document.querySelector('#percentrageIncrease');
                    const seatUtilCompareInput = document.querySelector('#percentrageIncreaseCompare');
                    const wschMultiplierInput = document.querySelector('#wschMultiplier');
                    const wschMultiplierCompareInput = document.querySelector('#wschMultiplierCompare');
                    
                    if (enrollmentInput && enrollmentCompareInput) {
                        enrollmentInput.addEventListener('input', function() {
                            syncInputs(enrollmentInput, enrollmentCompareInput);
                            updateAllTables();
                        });
                        enrollmentCompareInput.addEventListener('input', function() {
                            syncInputs(enrollmentCompareInput, enrollmentInput);
                            updateAllTables();
                        });
                    }
                    
                    if (seatUtilInput && seatUtilCompareInput) {
                        seatUtilInput.addEventListener('input', function() {
                            syncInputs(seatUtilInput, seatUtilCompareInput);
                            updateAllTables();
                        });
                        seatUtilCompareInput.addEventListener('input', function() {
                            syncInputs(seatUtilCompareInput, seatUtilInput);
                            updateAllTables();
                        });
                    }
                    
                    if (wschMultiplierInput && wschMultiplierCompareInput) {
                        wschMultiplierInput.addEventListener('input', function() {
                            syncInputs(wschMultiplierInput, wschMultiplierCompareInput);
                            updateAllTables();
                        });
                        wschMultiplierCompareInput.addEventListener('input', function() {
                            syncInputs(wschMultiplierCompareInput, wschMultiplierInput);
                            updateAllTables();
                        });
                    }
                }
                
                const forecastInput = document.querySelector('#enrollmentIncrease');
                const seatUtilInput = document.querySelector('#percentrageIncrease');
                const wschMultiplierInput = document.querySelector('#wschMultiplier');
                
                if (forecastInput) {
                    forecastInput.addEventListener('input', updateAllTables);
                }
                if (seatUtilInput) {
                    seatUtilInput.addEventListener('input', updateAllTables);
                }
                if (wschMultiplierInput) {
                    wschMultiplierInput.addEventListener('input', updateAllTables);
                }
                
                setupInputSync();
                
                // Initialize on page load
                updateAllTables();
                
                // Update compare view when the compare tab is shown
                const compareTab = document.querySelector('#compare-tab');
                if (compareTab) {
                    compareTab.addEventListener('shown.bs.tab', function() {
                        updateCompareView();
                    });
                }
                
                // Table sorting
                const sortState = new Map();
                
                function sortTableByColumn(table, columnIndex, isNumeric, direction) {
                    const tbody = table.querySelector('tbody');
                    if (!tbody) return;
                    
                    const rowsArray = Array.from(tbody.querySelectorAll('tr.course-row'));
                    if (rowsArray.length === 0) return;
                    
                    rowsArray.sort((a, b) => {
                        const aCell = a.querySelector(`td:nth-child(${columnIndex + 1})`);
                        const bCell = b.querySelector(`td:nth-child(${columnIndex + 1})`);
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
                        let newDirection = 'asc';
                        
                        if (currentState && currentState.columnIndex === columnIndex) {
                            newDirection = currentState.direction === 'asc' ? 'desc' : 'asc';
                        }
                        
                        sortState.set(table, { columnIndex, direction: newDirection });
                        sortTableByColumn(table, columnIndex, isNumeric, newDirection);
                        
                        table.querySelectorAll('th[data-sort]').forEach(th => th.classList.remove('sort-asc', 'sort-desc'));
                        this.classList.add(newDirection === 'asc' ? 'sort-asc' : 'sort-desc');
                    });
                });
            </script>

        </div>
        @else
        <div class="alert alert-info mt-4" role="alert">
            <strong>Please select required filters above and click "Filter" to view course data.</strong>
            <ul class="mt-2 mb-0">
                <li>Term (required)</li>
                <li>Department (required)</li>
                <li>Campus (required)</li>
                <li>Facility Type (required)</li>
            </ul>
        </div>
        @endif
    </div>
@endsection
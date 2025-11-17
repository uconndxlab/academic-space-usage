@extends('layouts.app')
@section('title', 'Course List')
@section('content')
    @php
        $selectedFacilityType = request('sa_facility_type', 'all');
        $selectedDepartments = request('department', []);
        if (!is_array($selectedDepartments)) {
            $selectedDepartments = $selectedDepartments === 'all' || $selectedDepartments === '' ? [] : [$selectedDepartments];
        }
        $hasAllFilters = request()->has('term') && !empty($selectedDepartments) && request()->has('campus') 
            && request('term') !== '' && request('campus') !== '';
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
                                <li class="px-2 py-1">
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
                        <label for="facilityTypeFilter" class="form-label">Filter by Facility Type</label>
                        <select name="sa_facility_type" id="facilityTypeFilter" class="form-select">
                            <option value="all" @selected($selectedFacilityType == 'all')>All</option>
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
                    const checkboxes = getDepartmentCheckboxes();
                    const checked = Array.from(checkboxes).filter(cb => cb.checked);
                    const currentSelectAll = document.querySelector('#selectAllDepartments');
                    if (currentSelectAll) {
                        currentSelectAll.checked = checkboxes.length > 0 && checked.length === checkboxes.length;
                        currentSelectAll.indeterminate = checked.length > 0 && checked.length < checkboxes.length;
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
                    checkboxes.forEach(cb => {
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
                
                attachDepartmentEventListeners();
                
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
                    
                    dropdownMenu.innerHTML = `
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
                            li.className = 'px-2 py-1';
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
                    
                    const isSpecialValue = filterType === 'facilityType' && savedValue === 'all';
                    const exists = isSpecialValue || (isObject 
                        ? data[dataKey] && data[dataKey].some(c => String(c.id) === String(savedValue))
                        : data[dataKey] && data[dataKey].includes(savedValue));
                    
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
                                filters.facilityType.value = 'all';
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
                            await updateDropdown('facilityType', params, 'facilityTypes', '<option value="all">All</option>', false);
                        } else {
                            restoreDropdown('facilityType', values.facilityType, 'all');
                        }
                    } catch (error) {
                        console.error('Error updating filter options:', error);
                    }
                }
                
                filters.term?.addEventListener('change', updateFilterOptions);
                filters.campus?.addEventListener('change', () => {
                    setTimeout(updateFilterOptions, 0);
                });
                filters.facilityType?.addEventListener('change', updateFilterOptions);
                
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
                @if($selectedFacilityType !== 'all' && !empty($selectedFacilityType))
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
                                <input type="number" id="percentrageIncrease" class="form-control" value="{{ $seatUtilization ?? 75 }}" min="0"
                                    max="100" step="1">
                            </div>

                            @if ($sectionsData->isEmpty())
                                <p>No courses available.</p>
                            @else
                            <div class="table-scroll-wrapper">
                                <table class="table table-striped table-hover table-sm sticky-header-table">
                                    <thead>
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
                                            <th scope="col" data-sort="numeric">Seat<br>@75%</th>
                                            <th scope="col" data-sort="numeric">WSCH<br>Bench</th>
                                            <th scope="col" data-sort="numeric">Rooms<br>Needed</th>
                                            <th scope="col" data-sort="text">Seat<br>Range</th>
                                        </tr>
                                    </thead>
                                    <tbody id="coursesTableBody">
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if($selectedFacilityType !== 'all' && !empty($selectedFacilityType))
                <div class="tab-pane fade" id="compare-view" role="tabpanel" aria-labelledby="compare-tab">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="mb-4">Seat Range Comparison</h2>
                            <p class="text-muted mb-4">
                                This comparison shows the distribution of sections across seat ranges.
                                <strong>Calculated Range</strong> is based on enrollment divided by seat utilization ({{ $seatUtilization ?? 75 }}%), using the "Seat @75%" value shown in the table.
                                <strong>Current Range</strong> is based on the actual room capacity.
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
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            
            <script>
                const sectionsData = @json($sectionsData);
                
                function calculateSectionMetrics(sectionData) {
                    const enrollment = sectionData.day10_enrol || 0;
                    const capacity = sectionData.room ? (sectionData.room.capacity || 0) : 0;
                    const contactHours = sectionData.duration_minutes / 60;
                    const daysPerWeek = sectionData.total_class_days || 0;
                    const wsch = Math.ceil((enrollment * daysPerWeek * contactHours));
                    const wschBenchmark = parseFloat((capacity * 30).toFixed(2));
                    const roomsNeeded = wschBenchmark > 0 
                        ? (wsch / wschBenchmark).toFixed(2)
                        : 0;
                    
                    return {
                        enrollment,
                        capacity,
                        contactHours,
                        daysPerWeek,
                        wsch,
                        wschBenchmark,
                        roomsNeeded
                    };
                }
                
                function getSeatingRange(seating75Util) {
                    if (seating75Util <= 0) return 'N/A';
                    const ranges = [
                        [25, '0-25'], [49, '26-49'], [74, '50-74'], [124, '75-124'], [174, '125-174'],
                        [224, '175-224'], [249, '225-249'], [299, '250-299'], [349, '300-349'], [399, '350-399']
                    ];
                    for (const [max, label] of ranges) {
                        if (seating75Util <= max) return label;
                    }
                    return '400+';
                }
                
                function getSeatUtilizationDecimal() {
                    const seatUtilInput = document.querySelector('#percentrageIncrease');
                    const seatUtilPercent = parseFloat(seatUtilInput?.value) || 75;
                    return seatUtilPercent / 100;
                }
                
                function updateForecastGrowth(row, growthPercentage) {
                    const originalEnrollment = parseFloat(row.getAttribute('data-original-enrollment'));
                    const durationMinutes = parseFloat(row.getAttribute('data-duration-minutes'));
                    const totalClassDays = parseFloat(row.getAttribute('data-total-class-days'));
                    const growthEnrollment = Math.round(originalEnrollment * (1 + growthPercentage / 100));
                    const contactHours = durationMinutes / 60;
                    const wschGrowth = parseFloat((growthEnrollment * totalClassDays * contactHours).toFixed(2));
                    const seatUtilDecimal = getSeatUtilizationDecimal();
                    const seating75Util = seatUtilDecimal > 0 
                        ? Math.round(growthEnrollment / seatUtilDecimal)
                        : 0;
                    const wschBenchmark = parseFloat((seating75Util * 30).toFixed(2));
                    const roomsNeeded = wschBenchmark > 0
                        ? (wschGrowth / wschBenchmark).toFixed(2)
                        : 0;
                    
                    const seatingRange = getSeatingRange(seating75Util);
                    
                    row.querySelector('.forecast-enroll-growth').textContent = growthEnrollment;
                    row.querySelector('.forecast-wsch-growth').textContent = Math.ceil(wschGrowth);
                    row.querySelector('.forecast-seating-75').textContent = seating75Util;
                    row.querySelector('.wsch-benchmark').textContent = wschBenchmark;
                    row.querySelector('.forecast-labs-needed').textContent = roomsNeeded;
                    row.querySelector('.forecast-seating-range').textContent = seatingRange;
                }
                
                function generateTableRows() {
                    const tbody = document.getElementById('coursesTableBody');
                    if (!tbody || !sectionsData) return;
                    
                    tbody.innerHTML = '';
                    
                    const seatUtilDecimal = getSeatUtilizationDecimal();
                    
                    sectionsData.forEach(sectionData => {
                        const metrics = calculateSectionMetrics(sectionData);
                        const seating75Util = seatUtilDecimal > 0 
                            ? Math.round(metrics.enrollment / seatUtilDecimal)
                            : 0;
                        const seatingRange = getSeatingRange(seating75Util);
                        
                        const row = document.createElement('tr');
                        row.className = 'course-row table-course';
                        row.setAttribute('data-original-enrollment', metrics.enrollment);
                        row.setAttribute('data-duration-minutes', sectionData.duration_minutes);
                        row.setAttribute('data-capacity', metrics.capacity);
                        row.setAttribute('data-total-class-days', sectionData.total_class_days || 0);
                        
                        row.innerHTML = `
                            <td>
                                <a href="/course/${sectionData.course_id}">
                                    ${sectionData.subject_code} ${sectionData.catalog_number} - ${sectionData.section_number}
                                </a>
                            </td>
                            <td class="forecast-enrollment">${metrics.enrollment}</td>
                            <td class="forecast-sections">1</td>
                            <td class="forecast-rooms">1</td>
                            <td class="forecast-capacity">${metrics.capacity}</td>
                            <td class="forecast-contact-hours">${metrics.contactHours.toFixed(2)}</td>
                            <td class="forecast-days-per-week">${sectionData.total_class_days || 0}</td>
                            <td class="forecast-wsch">${metrics.wsch}</td>
                            <td class="forecast-enroll-growth">${metrics.enrollment}</td>
                            <td class="forecast-wsch-growth">${metrics.wsch}</td>
                            <td class="forecast-seating-75">${seating75Util}</td>
                            <td class="wsch-benchmark">${metrics.wschBenchmark}</td>
                            <td class="forecast-labs-needed">${metrics.roomsNeeded}</td>
                            <td class="forecast-seating-range">${seatingRange}</td>
                        `;
                        
                        tbody.appendChild(row);
                    });
                }
                
                if (document.getElementById('coursesTableBody')) {
                    generateTableRows();
                }
                
                function updateComparisonTable() {
                    const comparisonTbody = document.getElementById('comparisonTableBody');
                    if (!comparisonTbody || !sectionsData) return;
                    
                    const rangeLabels = ['0-25', '26-49', '50-74', '75-124', '125-174', '175-224', '225-249', '250-299', '300-349', '350-399', '400+'];
                    const calculatedRanges = {};
                    const currentRanges = {};
                    
                    rangeLabels.forEach(range => {
                        calculatedRanges[range] = 0;
                        currentRanges[range] = 0;
                    });
                    
                    const seatUtilDecimal = getSeatUtilizationDecimal();
                    
                    sectionsData.forEach(sectionData => {
                        const metrics = calculateSectionMetrics(sectionData);
                        const seating75Util = seatUtilDecimal > 0 
                            ? Math.round(metrics.enrollment / seatUtilDecimal)
                            : 0;
                        const calculatedRange = getSeatingRange(seating75Util);
                        
                        if (calculatedRange !== 'N/A' && calculatedRanges.hasOwnProperty(calculatedRange)) {
                            calculatedRanges[calculatedRange]++;
                        }
                        
                        if (metrics.capacity > 0) {
                            const currentRange = getSeatingRange(metrics.capacity);
                            if (currentRange !== 'N/A' && currentRanges.hasOwnProperty(currentRange)) {
                                currentRanges[currentRange]++;
                            }
                        }
                    });
                    
                    comparisonTbody.innerHTML = '';
                    let totalCalculated = 0;
                    let totalCurrent = 0;
                    
                    rangeLabels.forEach(range => {
                        const calculated = calculatedRanges[range] || 0;
                        const current = currentRanges[range] || 0;
                        const difference = calculated - current;
                        totalCalculated += calculated;
                        totalCurrent += current;
                        
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td><strong>${range}</strong></td>
                            <td class="text-end">${calculated}</td>
                            <td class="text-end">${current}</td>
                            <td class="text-end ${difference > 0 ? 'text-danger' : (difference < 0 ? 'text-success' : '')}">
                                ${difference > 0 ? '+' : ''}${difference}
                            </td>
                        `;
                        comparisonTbody.appendChild(row);
                    });
                    
                    const totalRow = document.createElement('tr');
                    totalRow.className = 'table-secondary fw-bold';
                    const totalDifference = totalCalculated - totalCurrent;
                    totalRow.innerHTML = `
                        <td><strong>Total</strong></td>
                        <td class="text-end">${totalCalculated}</td>
                        <td class="text-end">${totalCurrent}</td>
                        <td class="text-end">${totalDifference > 0 ? '+' : ''}${totalDifference}</td>
                    `;
                    comparisonTbody.appendChild(totalRow);
                }
                
                if (document.getElementById('comparisonTableBody')) {
                    updateComparisonTable();
                }
                
                const seatUtilInput = document.querySelector('#percentrageIncrease');
                const forecastInput = document.querySelector('#enrollmentIncrease');
                
                function updateAllTables() {
                    const enrollmentIncrease = parseFloat(forecastInput?.value) || 0;
                    document.querySelectorAll('.course-row').forEach(row => {
                        updateForecastGrowth(row, enrollmentIncrease);
                    });
                    updateComparisonTable();
                }
                
                if (forecastInput) {
                    forecastInput.addEventListener('input', updateAllTables);
                }
                
                if (seatUtilInput) {
                    seatUtilInput.addEventListener('input', updateAllTables);
                }
                
                const sortState = new Map();

                function updateSortIndicator(header, direction) {
                    const table = header.closest('table');
                    table.querySelectorAll('th[data-sort]').forEach(th => {
                        th.classList.remove('sort-asc', 'sort-desc');
                    });

                    if (direction === 'asc') {
                        header.classList.add('sort-asc');
                        header.setAttribute('title', 'Click to sort descending');
                    } else {
                        header.classList.add('sort-desc');
                        header.setAttribute('title', 'Click to sort ascending');
                    }
                }

                function sortTableByColumn(table, columnIndex, isNumeric = false, direction = 'asc') {
                    const tbody = table.querySelector('tbody');
                    if (!tbody) return;

                    const rowsArray = Array.from(tbody.querySelectorAll('tr.table-course, tr.course-row'));
                    if (rowsArray.length === 0) return;

                    rowsArray.sort((a, b) => {
                        const aCell = a.querySelector(`td:nth-child(${columnIndex + 1})`);
                        const bCell = b.querySelector(`td:nth-child(${columnIndex + 1})`);

                        if (!aCell || !bCell) return 0;

                        const aColText = aCell.textContent.trim();
                        const bColText = bCell.textContent.trim();

                        let comparison = 0;
                        if (isNumeric) {
                            const aNum = parseFloat(aColText.replace(/[^0-9.-]/g, '')) || 0;
                            const bNum = parseFloat(bColText.replace(/[^0-9.-]/g, '')) || 0;
                            comparison = aNum - bNum;
                        } else {
                            comparison = aColText.localeCompare(bColText);
                        }

                        return direction === 'desc' ? -comparison : comparison;
                    });

                    rowsArray.forEach(row => tbody.appendChild(row));
                }

                document.querySelectorAll('th[data-sort]').forEach(header => {
                    header.style.cursor = 'pointer';
                    header.setAttribute('title', 'Click to sort');
                    
                    if (!document.querySelector('#sortStyles')) {
                        const style = document.createElement('style');
                        style.id = 'sortStyles';
                        style.textContent = `
                            th[data-sort].sort-asc::after {
                                content: ' ▲';
                                opacity: 0.7;
                            }
                            th[data-sort].sort-desc::after {
                                content: ' ▼';
                                opacity: 0.7;
                            }
                        `;
                        document.head.appendChild(style);
                    }

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
                        updateSortIndicator(this, newDirection);
                    });
                });
                
                (function() {
                    const table = document.querySelector('.sticky-header-table');
                    if (!table) return;
                    
                    const thead = table.querySelector('thead');
                    const headerRow = thead?.querySelector('tr');
                    
                    if (!headerRow) return;
                    
                    let stickyHeader = null;
                    let isSticky = false;
                    
                    function createStickyHeader() {
                        if (stickyHeader) return;
                        
                        stickyHeader = document.createElement('div');
                        stickyHeader.className = 'sticky-header-placeholder';
                        stickyHeader.style.cssText = 'position: fixed; top: 0; z-index: 9999; display: none; overflow: hidden;';
                        
                        const stickyTable = document.createElement('table');
                        stickyTable.className = 'table table-sm sticky-header-table';
                        stickyTable.style.cssText = 'margin: 0;';
                        
                        const stickyThead = document.createElement('thead');
                        stickyThead.className = 'table-primary';
                        stickyThead.innerHTML = headerRow.outerHTML;
                        
                        stickyTable.appendChild(stickyThead);
                        stickyHeader.appendChild(stickyTable);
                        
                        const originalThs = Array.from(headerRow.querySelectorAll('th'));
                        const stickyThs = Array.from(stickyThead.querySelectorAll('th'));
                        
                        stickyThs.forEach((th, index) => {
                            if (originalThs[index]) {
                                th.style.cssText = originalThs[index].style.cssText;
                                th.style.backgroundColor = '#002855';
                                th.style.color = '#ffffff';
                            }
                        });
                        
                        document.body.appendChild(stickyHeader);
                    }
                    
                    function updateStickyHeader() {
                        if (!stickyHeader) createStickyHeader();
                        
                        const rect = thead.getBoundingClientRect();
                        const shouldBeSticky = rect.top < 0;
                        
                        const tableContainer = table.closest('.table-scroll-wrapper') || table.parentElement;
                        const containerRect = tableContainer.getBoundingClientRect();
                        
                        if (shouldBeSticky && !isSticky) {
                            stickyHeader.style.display = 'block';
                            isSticky = true;
                            
                            const stickyThs = Array.from(stickyHeader.querySelectorAll('th'));
                            const originalThs = Array.from(headerRow.querySelectorAll('th'));
                            
                            stickyThs.forEach((th, index) => {
                                if (originalThs[index]) {
                                    const width = originalThs[index].offsetWidth;
                                    th.style.width = width + 'px';
                                    th.style.minWidth = width + 'px';
                                    th.style.maxWidth = width + 'px';
                                }
                            });
                            
                            const viewportWidth = window.innerWidth;
                            const left = Math.max(0, containerRect.left);
                            const containerWidth = Math.min(containerRect.width, viewportWidth - left);
                            
                            stickyHeader.style.left = left + 'px';
                            stickyHeader.style.width = containerWidth + 'px';
                            stickyHeader.style.maxWidth = viewportWidth + 'px';
                            stickyHeader.querySelector('table').style.width = table.offsetWidth + 'px';
                        } else if (!shouldBeSticky && isSticky) {
                            stickyHeader.style.display = 'none';
                            isSticky = false;
                        }
                        
                        if (isSticky) {
                            const viewportWidth = window.innerWidth;
                            const left = Math.max(0, containerRect.left);
                            const containerWidth = Math.min(containerRect.width, viewportWidth - left);
                            
                            stickyHeader.style.left = left + 'px';
                            stickyHeader.style.width = containerWidth + 'px';
                            stickyHeader.style.maxWidth = viewportWidth + 'px';
                            
                            const scrollLeft = tableContainer.scrollLeft || 0;
                            stickyHeader.querySelector('table').style.transform = `translateX(-${scrollLeft}px)`;
                        }
                    }
                    
                    function handleScroll() {
                        updateStickyHeader();
                    }
                    
                    function handleResize() {
                        if (isSticky) {
                            updateStickyHeader();
                        }
                    }
                    
                    function handleHorizontalScroll() {
                        if (isSticky && stickyHeader) {
                            const tableContainer = table.closest('.table-scroll-wrapper') || table.parentElement;
                            const scrollLeft = tableContainer.scrollLeft || 0;
                            stickyHeader.querySelector('table').style.transform = `translateX(-${scrollLeft}px)`;
                        }
                    }
                    
                    window.addEventListener('scroll', handleScroll, { passive: true });
                    window.addEventListener('resize', handleResize);
                    
                    const tableContainer = table.closest('.table-scroll-wrapper') || table.parentElement;
                    if (tableContainer) {
                        tableContainer.addEventListener('scroll', handleHorizontalScroll, { passive: true });
                    }
                    
                    updateStickyHeader();
                    
                    window.addEventListener('beforeunload', function() {
                        if (stickyHeader) {
                            stickyHeader.remove();
                        }
                    });
                })();
            </script>

        </div>
        @else
        <div class="alert alert-info mt-4" role="alert">
            <strong>Please select required filters above and click "Filter" to view course data.</strong>
            <ul class="mt-2 mb-0">
                <li>Term (required)</li>
                <li>Department (required)</li>
                <li>Campus (required)</li>
                <li>Facility Type (optional - defaults to "All")</li>
            </ul>
        </div>
        @endif
    </div>
@endsection
@extends('layouts.app')
@section('title', 'Course List')
@section('content')
    <div class="container">
        <h1 class="mb-4">Course List</h1>

        <!-- filter by term, department, campus, and facility type -->
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
                        <select name="department" id="departmentFilter" class="form-select" required>
                            <option value="">-- Select Department --</option>
                            @foreach ($departments as $department)
                                <option @selected($department == request('department')) value="{{ $department }}">{{ $department }}</option>
                            @endforeach
                        </select>
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

                    {{-- dropdown of all SA_Facility_Types --}}
                    <div class="col-md-6 mb-3">
                        <label for="facilityTypeFilter" class="form-label">Filter by Facility Type</label>
                        <select name="sa_facility_type" id="facilityTypeFilter" class="form-select">
                            <option value="all" @selected(request('sa_facility_type', 'all') == 'all')>All</option>
                            @foreach ($facilityTypes as $facilityType)
                                <option @selected($facilityType == request('sa_facility_type', 'all')) value="{{ $facilityType }}">{{ $facilityType }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-3">
                    <input type="submit" value="Filter" class="btn btn-primary">
                </div>
            </form>

        </div>

        @php
            $selectedFacilityType = request('sa_facility_type', 'all');
            $hasAllFilters = request()->has('term') && request()->has('department') && request()->has('campus') 
                && request('term') !== '' && request('department') !== '' && request('campus') !== '';
        @endphp

        @if($hasAllFilters)
        <div id="results">
            <div class="card mt-4">
                <div class="card-body">
                    <!-- Enrollment Increase Input -->
                    <div class="mb-3">
                        <label for="enrollmentIncrease" class="form-label">Enrollment Increase (%)</label>
                        <input type="number" id="enrollmentIncrease" class="form-control" value="0" min="0"
                            max="100" step="1">
                    </div>

                    @if ($courses->isEmpty())
                        <p>No courses available.</p>
                    @else
                    <!-- Forecast Table -->
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
                                    <th scope="col" data-sort="numeric">WSCH</th>
                                    <th scope="col" data-sort="numeric">Avg/<br>Sec</th>
                                    <th scope="col" data-sort="numeric">Enroll<br>Growth</th>
                                    <th scope="col" data-sort="numeric">WSCH<br>Growth</th>
                                    <th scope="col" data-sort="numeric">Stu/<br>Sec</th>
                                    <th scope="col" data-sort="numeric">Seat<br>@75%</th>
                                    <th scope="col" data-sort="numeric">WSCH<br>Bench</th>
                                    <th scope="col" data-sort="numeric">Rooms<br>Needed</th>
                                    <th scope="col" data-sort="text">Seat<br>Range</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($courses as $course)
                                    @php
                                        $avg_per_section = $course->sections_count > 0 ? $course->total_enrollment / $course->sections_count : 0;
                                        $enroll_growth = $course->total_enrollment;
                                        $wsch_growth = ceil(($enroll_growth * $course->duration_minutes) / 60);
                                        $students_per_section = $course->sections_count > 0 ? $enroll_growth / $course->sections_count : 0;
                                        $seating_75_util = $course->total_capacity * 0.75;
                                        $contact_hours = $course->duration_minutes / 60;
                                        
                                        // Seating range calculation based on spreadsheet formula
                                        // Only calculated on page load, then handled in JavaScript
                                        $seating_range = 'N/A';
                                        if ($seating_75_util > 0) {
                                            if ($seating_75_util <= 25) {
                                                $seating_range = '0-25';
                                            } elseif ($seating_75_util <= 49) {
                                                $seating_range = '26-49';
                                            } elseif ($seating_75_util <= 74) {
                                                $seating_range = '50-74';
                                            } elseif ($seating_75_util <= 124) {
                                                $seating_range = '75-124';
                                            } elseif ($seating_75_util <= 174) {
                                                $seating_range = '125-174';
                                            } elseif ($seating_75_util <= 224) {
                                                $seating_range = '175-224';
                                            } elseif ($seating_75_util <= 249) {
                                                $seating_range = '225-249';
                                            } elseif ($seating_75_util <= 299) {
                                                $seating_range = '250-299';
                                            } elseif ($seating_75_util <= 349) {
                                                $seating_range = '300-349';
                                            } elseif ($seating_75_util <= 399) {
                                                $seating_range = '350-399';
                                            } else {
                                                $seating_range = '400+';
                                            }
                                        }
                                    @endphp
                                    <tr class="course-row table-course"
                                        data-original-enrollment="{{ $course->total_enrollment }}"
                                        data-duration-minutes="{{ $course->duration_minutes }}"
                                        data-current-rooms="{{ $course->rooms_used }}"
                                        data-weekly-contact-hours="{{ $course->total_wsch }}"
                                        data-sections-count="{{ $course->sections_count }}"
                                        data-total-capacity="{{ $course->total_capacity }}"
                                        data-capacity-per-room="{{ $course->capacity_per_room }}"
                                        data-wsch-benchmark="{{ $course->wsch_benchmark }}">
                                        <td>
                                            <a href="{{ route('courses.show', $course->id) }}">
                                                {{ $course->subject_code }} {{ $course->catalog_number }}
                                            </a>
                                        </td>
                                        <td class="forecast-enrollment">{{ $course->total_enrollment }}</td>
                                        <td class="forecast-sections">{{ $course->sections_count }}</td>
                                        <td class="forecast-rooms">{{ $course->rooms_used }}</td>
                                        <td class="forecast-capacity">{{ $course->total_capacity }}</td>
                                        <td class="forecast-contact-hours">{{ number_format($contact_hours, 2) }}</td>
                                        <td class="forecast-wsch">{{ $course->total_wsch }}</td>
                                        <td class="forecast-avg-per-section">{{ $course->sections_count > 0 ? number_format($avg_per_section, 2) : '0.00' }}</td>
                                        <td class="forecast-enroll-growth">{{ number_format($enroll_growth, 0) }}</td>
                                        <td class="forecast-wsch-growth">{{ $wsch_growth }}</td>
                                        <td class="forecast-students-per-section">{{ $course->sections_count > 0 ? number_format($students_per_section, 2) : '0.00' }}</td>
                                        <td class="forecast-seating-75">{{ number_format($seating_75_util, 0) }}</td>
                                        <td class="wsch-benchmark">{{ $course->wsch_benchmark }}</td>
                                        <td class="forecast-labs-needed">{{ number_format($course->rooms_needed, 2) }}</td>
                                        <td class="forecast-seating-range">{{ $seating_range }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>

            <style>
                .table-scroll-wrapper {
                    overflow-x: auto;
                    overflow-y: visible;
                    position: relative;
                }
                .sticky-header-placeholder {
                    background-color: #002855;
                    overflow: hidden;
                    max-width: 100vw;
                }
                .sticky-header-placeholder table {
                    background-color: #002855;
                }
                .sticky-header-placeholder th {
                    background-color: #002855 !important;
                    color: #ffffff !important;
                }
            </style>

            <script>
                (function() {
                    const termFilter = document.querySelector('#termFilter');
                    const departmentFilter = document.querySelector('#departmentFilter');
                    const campusFilter = document.querySelector('#campusFilter');
                    const facilityTypeFilter = document.querySelector('#facilityTypeFilter');
                    
                    // Store original options for reset
                    const originalDepartmentOptions = Array.from(departmentFilter.options);
                    const originalCampusOptions = Array.from(campusFilter.options);
                    const originalFacilityTypeOptions = Array.from(facilityTypeFilter.options);
                    
                    async function updateFilterOptions() {
                        // Save current selections BEFORE making any changes
                        const term = termFilter.value;
                        const department = departmentFilter.value;
                        const savedCampus = campusFilter.value;
                        const savedFacilityType = facilityTypeFilter.value;
                        
                        try {
                            const params = new URLSearchParams();
                            if (term) params.append('term', term);
                            if (department) params.append('department', department);
                            if (savedCampus) params.append('campus', savedCampus);
                            
                            // Update departments if term is selected
                            if (term) {
                                const deptParams = new URLSearchParams();
                                deptParams.append('term', term);
                                
                                const deptResponse = await fetch('{{ route("courses.filterOptions") }}?' + deptParams.toString());
                                const deptData = await deptResponse.json();
                                
                                // Clear and repopulate department options
                                departmentFilter.innerHTML = '<option value="">-- Select Department --</option>';
                                if (deptData.departments && deptData.departments.length > 0) {
                                    deptData.departments.forEach(dept => {
                                        const option = document.createElement('option');
                                        option.value = dept;
                                        option.textContent = dept;
                                        departmentFilter.appendChild(option);
                                    });
                                }
                                
                                // Restore saved department if still valid
                                if (department && deptData.departments && deptData.departments.includes(department)) {
                                    departmentFilter.value = department;
                                } else {
                                    departmentFilter.value = '';
                                }
                            } else {
                                // No term - restore original department options
                                const savedDeptValue = department;
                                departmentFilter.innerHTML = '';
                                originalDepartmentOptions.forEach(opt => {
                                    const newOpt = opt.cloneNode(true);
                                    departmentFilter.appendChild(newOpt);
                                });
                                if (savedDeptValue && Array.from(departmentFilter.options).some(opt => opt.value == savedDeptValue)) {
                                    departmentFilter.value = savedDeptValue;
                                } else {
                                    departmentFilter.value = '';
                                }
                            }
                            
                            // Get current department value (may have changed above)
                            const currentDepartment = departmentFilter.value;
                            
                            // Update campuses if term or department is selected
                            if (term || currentDepartment) {
                                const campusParams = new URLSearchParams();
                                if (term) campusParams.append('term', term);
                                if (currentDepartment) campusParams.append('department', currentDepartment);
                                
                                const campusResponse = await fetch('{{ route("courses.filterOptions") }}?' + campusParams.toString());
                                const campusData = await campusResponse.json();
                                
                                // Clear and repopulate campus options
                                campusFilter.innerHTML = '<option value="">-- Select Campus --</option>';
                                if (campusData.campuses && campusData.campuses.length > 0) {
                                    campusData.campuses.forEach(campus => {
                                        const option = document.createElement('option');
                                        option.value = campus.id;
                                        option.textContent = campus.name;
                                        campusFilter.appendChild(option);
                                    });
                                }
                                
                                // Restore saved campus if still valid
                                const savedCampusStr = String(savedCampus);
                                const campusExists = campusData.campuses && campusData.campuses.some(c => String(c.id) === savedCampusStr);
                                if (savedCampus && campusExists) {
                                    campusFilter.value = savedCampusStr;
                                } else {
                                    campusFilter.value = '';
                                    facilityTypeFilter.value = 'all';
                                }
                            } else {
                                // No filters - restore original campus options
                                const savedCampusValue = savedCampus;
                                campusFilter.innerHTML = '';
                                originalCampusOptions.forEach(opt => {
                                    const newOpt = opt.cloneNode(true);
                                    campusFilter.appendChild(newOpt);
                                });
                                if (savedCampusValue && Array.from(campusFilter.options).some(opt => opt.value == savedCampusValue)) {
                                    campusFilter.value = savedCampusValue;
                                } else {
                                    campusFilter.value = '';
                                }
                            }
                            
                            // Get current campus value (may have changed above)
                            const currentCampus = campusFilter.value;
                            
                            // Update facility types if term, department, or campus is selected
                            if (term || currentDepartment || currentCampus) {
                                const facilityParams = new URLSearchParams();
                                if (term) facilityParams.append('term', term);
                                if (currentDepartment) facilityParams.append('department', currentDepartment);
                                if (currentCampus) facilityParams.append('campus', currentCampus);
                                
                                const facilityResponse = await fetch('{{ route("courses.filterOptions") }}?' + facilityParams.toString());
                                const facilityData = await facilityResponse.json();
                                
                                // Clear and repopulate facility type options
                                facilityTypeFilter.innerHTML = '<option value="all">All</option>';
                                if (facilityData.facilityTypes && facilityData.facilityTypes.length > 0) {
                                    facilityData.facilityTypes.forEach(facilityType => {
                                        const option = document.createElement('option');
                                        option.value = facilityType;
                                        option.textContent = facilityType;
                                        facilityTypeFilter.appendChild(option);
                                    });
                                }
                                
                                // Restore saved facility type if still valid
                                if (savedFacilityType && (savedFacilityType === 'all' || (facilityData.facilityTypes && facilityData.facilityTypes.includes(savedFacilityType)))) {
                                    facilityTypeFilter.value = savedFacilityType;
                                } else {
                                    facilityTypeFilter.value = 'all';
                                }
                            } else {
                                // No filters - restore original facility type options
                                const savedFacilityTypeValue = savedFacilityType;
                                facilityTypeFilter.innerHTML = '';
                                originalFacilityTypeOptions.forEach(opt => {
                                    const newOpt = opt.cloneNode(true);
                                    facilityTypeFilter.appendChild(newOpt);
                                });
                                if (savedFacilityTypeValue && Array.from(facilityTypeFilter.options).some(opt => opt.value == savedFacilityTypeValue)) {
                                    facilityTypeFilter.value = savedFacilityTypeValue;
                                } else {
                                    facilityTypeFilter.value = 'all';
                                }
                            }
                        } catch (error) {
                            console.error('Error updating filter options:', error);
                        }
                    }
                    
                    // Listen for changes
                    termFilter.addEventListener('change', function() {
                        updateFilterOptions();
                    });
                    
                    departmentFilter.addEventListener('change', function() {
                        updateFilterOptions();
                    });
                    
                    campusFilter.addEventListener('change', function() {
                        setTimeout(() => {
                            updateFilterOptions();
                        }, 0);
                    });
                    
                    // Initialize filters on page load if term or department is already selected
                    if (termFilter.value || departmentFilter.value) {
                        updateFilterOptions();
                    }
                })();
            </script>

            <script>
                // handle user input updates for enrollment increase
                function updateForecastGrowth(row, growthPercentage) {
                    const originalEnrollment = parseFloat(row.getAttribute('data-original-enrollment'));
                    const durationMinutes = parseFloat(row.getAttribute('data-duration-minutes'));
                    const sectionsCount = parseFloat(row.getAttribute('data-sections-count'));
                    const totalCapacity = parseFloat(row.getAttribute('data-total-capacity'));
                    const capacityPerRoom = parseFloat(row.getAttribute('data-capacity-per-room'));
                    const wschBenchmark = parseFloat(row.getAttribute('data-wsch-benchmark'));
                    
                    const growthEnrollment = Math.round(originalEnrollment * (1 + growthPercentage / 100));
                    
                    const wschGrowth = Math.ceil((growthEnrollment * durationMinutes) / 60);
                    const studentsPerSection = sectionsCount > 0 ? (growthEnrollment / sectionsCount).toFixed(2) : '0.00';
                    
                    const seating75Util = Math.round((growthEnrollment * 0.75));
                    
                    const roomsNeeded = capacityPerRoom > 0 
                        ? Math.ceil(seating75Util / (capacityPerRoom)) 
                        : 0;
                    
                    // Seating range calculation logic from spreadsheet formula
                    let seatingRange = 'N/A';
                    if (seating75Util > 0) {
                        if (seating75Util <= 25) {
                            seatingRange = '0-25';
                        } else if (seating75Util <= 49) {
                            seatingRange = '26-49';
                        } else if (seating75Util <= 74) {
                            seatingRange = '50-74';
                        } else if (seating75Util <= 124) {
                            seatingRange = '75-124';
                        } else if (seating75Util <= 174) {
                            seatingRange = '125-174';
                        } else if (seating75Util <= 224) {
                            seatingRange = '175-224';
                        } else if (seating75Util <= 249) {
                            seatingRange = '225-249';
                        } else if (seating75Util <= 299) {
                            seatingRange = '250-299';
                        } else if (seating75Util <= 349) {
                            seatingRange = '300-349';
                        } else if (seating75Util <= 399) {
                            seatingRange = '350-399';
                        } else {
                            seatingRange = '400+';
                        }
                    }
                    
                    row.querySelector('.forecast-enroll-growth').textContent = growthEnrollment;
                    row.querySelector('.forecast-wsch-growth').textContent = wschGrowth;
                    row.querySelector('.forecast-students-per-section').textContent = studentsPerSection;
                    row.querySelector('.forecast-seating-75').textContent = seating75Util;
                    row.querySelector('.forecast-labs-needed').textContent = roomsNeeded;
                    row.querySelector('.forecast-seating-range').textContent = seatingRange;
                }

                const forecastInput = document.querySelector('#enrollmentIncrease');
                if (forecastInput) {
                    forecastInput.addEventListener('input', function() {
                        const increase = parseFloat(this.value) || 0;

                        document.querySelectorAll('.course-row').forEach(row => {
                            updateForecastGrowth(row, increase);
                        });
                    });
                }
            </script>


            <script>
                // Track current sort state per table
                const sortState = new Map(); // Map<table, {columnIndex: number, direction: 'asc'|'desc'}>

                function updateSortIndicator(header, direction) {
                    // Remove all sort indicators from headers in the same table
                    const table = header.closest('table');
                    table.querySelectorAll('th[data-sort]').forEach(th => {
                        th.classList.remove('sort-asc', 'sort-desc');
                    });

                    // Add indicator to current header
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

                    // Get all rows - look for both class names used in different tables
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

                        // Reverse comparison if sorting descending
                        return direction === 'desc' ? -comparison : comparison;
                    });

                    rowsArray.forEach(row => tbody.appendChild(row));
                }

                // Add click event listener to the sortable table headers
                document.querySelectorAll('th[data-sort]').forEach(header => {
                    header.style.cursor = 'pointer';
                    header.setAttribute('title', 'Click to sort');
                    
                    // Add CSS for sort indicators
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

                        // Get current sort state for this table
                        const currentState = sortState.get(table);
                        let newDirection = 'asc';

                        // If clicking the same column, toggle direction
                        if (currentState && currentState.columnIndex === columnIndex) {
                            newDirection = currentState.direction === 'asc' ? 'desc' : 'asc';
                        }

                        // Update sort state
                        sortState.set(table, { columnIndex, direction: newDirection });

                        // Perform sort
                        sortTableByColumn(table, columnIndex, isNumeric, newDirection);

                        // Update visual indicator
                        updateSortIndicator(this, newDirection);
                    });
                });
            </script>

            <script>
                // Sticky header implementation
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
                    
                    // Initial check
                    updateStickyHeader();
                    
                    // Clean up on page unload
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
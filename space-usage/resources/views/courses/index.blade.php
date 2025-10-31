@extends('layouts.app')
@section('title', 'Course List')
@section('content')
    @php
        $selectedFacilityType = request('sa_facility_type', 'all');
        $hasAllFilters = request()->has('term') && request()->has('department') && request()->has('campus') 
            && request('term') !== '' && request('department') !== '' && request('campus') !== '';
    @endphp

    <div class="container">
        <h1 class="mb-4">Course List</h1>

        <!-- Filter Form -->
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
                    <input type="submit" value="Filter" class="btn btn-primary">
                </div>
            </form>
        </div>

        <!-- Dynamic filtering script -->
        <script>
            (function() {
                const filters = {
                    term: document.querySelector('#termFilter'),
                    department: document.querySelector('#departmentFilter'),
                    campus: document.querySelector('#campusFilter'),
                    facilityType: document.querySelector('#facilityTypeFilter')
                };
                
                const originals = {
                    department: Array.from(filters.department.options),
                    campus: Array.from(filters.campus.options),
                    facilityType: Array.from(filters.facilityType.options)
                };
                
                // Generic function to update dropdown options
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
                    
                    // Restore or clear value (special handling for 'all' in facilityType)
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
                
                // Restore original dropdown options
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
                
                async function updateFilterOptions() {
                    const values = {
                        term: filters.term.value,
                        department: filters.department.value,
                        campus: filters.campus.value,
                        facilityType: filters.facilityType.value
                    };
                    
                    try {
                        // Update departments
                        if (values.term) {
                            const params = new URLSearchParams();
                            params.append('term', values.term);
                            await updateDropdown('department', params, 'departments', '<option value="">-- Select Department --</option>', false);
                        } else {
                            restoreDropdown('department', values.department);
                        }
                        
                        // Update campuses
                        const currentDept = filters.department.value;
                        if (values.term || currentDept) {
                            const params = new URLSearchParams();
                            if (values.term) params.append('term', values.term);
                            if (currentDept) params.append('department', currentDept);
                            await updateDropdown('campus', params, 'campuses', '<option value="">-- Select Campus --</option>', true, () => {
                                filters.facilityType.value = 'all';
                            });
                        } else {
                            restoreDropdown('campus', values.campus);
                        }
                        
                        // Update facility types
                        const currentCampus = filters.campus.value;
                        if (values.term || currentDept || currentCampus) {
                            const params = new URLSearchParams();
                            if (values.term) params.append('term', values.term);
                            if (currentDept) params.append('department', currentDept);
                            if (currentCampus) params.append('campus', currentCampus);
                            await updateDropdown('facilityType', params, 'facilityTypes', '<option value="all">All</option>', false);
                        } else {
                            restoreDropdown('facilityType', values.facilityType, 'all');
                        }
                    } catch (error) {
                        console.error('Error updating filter options:', error);
                    }
                }
                
                // Event listeners
                Object.keys(filters).forEach(key => {
                    if (filters[key]) {
                        filters[key].addEventListener('change', () => {
                            if (key === 'campus') setTimeout(updateFilterOptions, 0);
                            else updateFilterOptions();
                        });
                    }
                });
                
                // Initialize
                if (Object.values(filters).some(f => f && f.value)) {
                    updateFilterOptions();
                }
            })();
        </script>

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

                    @if ($sectionsData->isEmpty())
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
                            <tbody id="coursesTableBody">
                                <!-- Rows will be generated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Pass raw data to JavaScript -->
            <script>
                const sectionsData = @json($sectionsData);
                // Calculate all metrics from raw section data
                function calculateCourseMetrics(courseData) {
                    const sections = courseData.sections;
                    
                    // Basic aggregations
                    const totalEnrollment = sections.reduce((sum, s) => sum + (s.day10_enrol || 0), 0);
                    const sectionsCount = sections.length;
                    
                    // Get unique rooms and sum capacities
                    const uniqueRooms = new Map();
                    sections.forEach(section => {
                        if (section.room && section.room.id) {
                            if (!uniqueRooms.has(section.room.id)) {
                                uniqueRooms.set(section.room.id, section.room.capacity || 0);
                            }
                        }
                    });
                    
                    const roomsUsed = uniqueRooms.size;
                    const totalCapacity = Array.from(uniqueRooms.values()).reduce((sum, cap) => sum + cap, 0);
                    const capacityPerRoom = roomsUsed > 0 ? totalCapacity / roomsUsed : 0;
                    
                    // Contact hours and WSCH
                    const contactHours = courseData.duration_minutes / 60;
                    const totalWsch = Math.ceil((totalEnrollment * courseData.duration_minutes) / 60);
                    
                    // Average per section
                    const avgPerSection = sectionsCount > 0 ? totalEnrollment / sectionsCount : 0;
                    
                    // WSCH benchmark calculation
                    const firstRoomCapacity = courseData.first_room_capacity || 1;
                    const wschBenchmark = Math.round(32 * (firstRoomCapacity * 0.8) / 10) * 10; // Round to nearest 10
                    
                    // Rooms needed
                    const roomsNeeded = capacityPerRoom > 0 
                        ? Math.ceil((totalCapacity * 0.75) / capacityPerRoom)
                        : 0;
                    
                    return {
                        totalEnrollment,
                        sectionsCount,
                        roomsUsed,
                        totalCapacity,
                        capacityPerRoom,
                        contactHours,
                        totalWsch,
                        avgPerSection,
                        wschBenchmark,
                        roomsNeeded
                    };
                }
                
                // Get seating range from seat count at 75% utilization
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
                
                // Handle user input updates for enrollment increase
                function updateForecastGrowth(row, growthPercentage) {
                    const originalEnrollment = parseFloat(row.getAttribute('data-original-enrollment'));
                    const durationMinutes = parseFloat(row.getAttribute('data-duration-minutes'));
                    const sectionsCount = parseFloat(row.getAttribute('data-sections-count'));
                    const totalCapacity = parseFloat(row.getAttribute('data-total-capacity'));
                    const capacityPerRoom = parseFloat(row.getAttribute('data-capacity-per-room'));
                    
                    const growthEnrollment = Math.round(originalEnrollment * (1 + growthPercentage / 100));
                    const wschGrowth = Math.ceil((growthEnrollment * durationMinutes) / 60);
                    const studentsPerSection = sectionsCount > 0 ? (growthEnrollment / sectionsCount).toFixed(2) : '0.00';
                    const seating75Util = Math.round((growthEnrollment * 0.75));
                    
                    const roomsNeeded = capacityPerRoom > 0 
                        ? Math.ceil(seating75Util / capacityPerRoom) 
                        : 0;
                    
                    const seatingRange = getSeatingRange(seating75Util);
                    
                    row.querySelector('.forecast-enroll-growth').textContent = growthEnrollment;
                    row.querySelector('.forecast-wsch-growth').textContent = wschGrowth;
                    row.querySelector('.forecast-students-per-section').textContent = studentsPerSection;
                    row.querySelector('.forecast-seating-75').textContent = seating75Util;
                    row.querySelector('.forecast-labs-needed').textContent = roomsNeeded;
                    row.querySelector('.forecast-seating-range').textContent = seatingRange;
                }
                
                // Generate table rows from raw data
                function generateTableRows() {
                    const tbody = document.getElementById('coursesTableBody');
                    if (!tbody || !sectionsData) return;
                    
                    tbody.innerHTML = '';
                    
                    sectionsData.forEach(courseData => {
                        const metrics = calculateCourseMetrics(courseData);
                        const seatingRange = getSeatingRange(Math.round(metrics.totalCapacity * 0.75));
                        
                        const row = document.createElement('tr');
                        row.className = 'course-row table-course';
                        row.setAttribute('data-original-enrollment', metrics.totalEnrollment);
                        row.setAttribute('data-duration-minutes', courseData.duration_minutes);
                        row.setAttribute('data-current-rooms', metrics.roomsUsed);
                        row.setAttribute('data-weekly-contact-hours', metrics.totalWsch);
                        row.setAttribute('data-sections-count', metrics.sectionsCount);
                        row.setAttribute('data-total-capacity', metrics.totalCapacity);
                        row.setAttribute('data-capacity-per-room', metrics.capacityPerRoom.toFixed(2));
                        row.setAttribute('data-wsch-benchmark', metrics.wschBenchmark);
                        
                        row.innerHTML = `
                            <td>
                                <a href="/course/${courseData.course_id}">
                                    ${courseData.subject_code} ${courseData.catalog_number}
                                </a>
                            </td>
                            <td class="forecast-enrollment">${metrics.totalEnrollment}</td>
                            <td class="forecast-sections">${metrics.sectionsCount}</td>
                            <td class="forecast-rooms">${metrics.roomsUsed}</td>
                            <td class="forecast-capacity">${metrics.totalCapacity}</td>
                            <td class="forecast-contact-hours">${metrics.contactHours.toFixed(2)}</td>
                            <td class="forecast-wsch">${metrics.totalWsch}</td>
                            <td class="forecast-avg-per-section">${metrics.avgPerSection.toFixed(2)}</td>
                            <td class="forecast-enroll-growth">${metrics.totalEnrollment}</td>
                            <td class="forecast-wsch-growth">${metrics.totalWsch}</td>
                            <td class="forecast-students-per-section">${metrics.avgPerSection.toFixed(2)}</td>
                            <td class="forecast-seating-75">${Math.round(metrics.totalCapacity * 0.75)}</td>
                            <td class="wsch-benchmark">${metrics.wschBenchmark}</td>
                            <td class="forecast-labs-needed">${metrics.roomsNeeded}</td>
                            <td class="forecast-seating-range">${seatingRange}</td>
                        `;
                        
                        tbody.appendChild(row);
                    });
                }
                
                // Initialize table on page load
                if (document.getElementById('coursesTableBody')) {
                    generateTableRows();
                }
                
                // Handle enrollment increase input
                const forecastInput = document.querySelector('#enrollmentIncrease');
                if (forecastInput) {
                    forecastInput.addEventListener('input', function() {
                        const increase = parseFloat(this.value) || 0;
                        document.querySelectorAll('.course-row').forEach(row => {
                            updateForecastGrowth(row, increase);
                        });
                    });
                }
                
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
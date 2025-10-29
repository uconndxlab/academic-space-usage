@extends('layouts.app')
@section('title', 'Course List')
@section('content')
    <div class="container">
        <h1 class="mb-4">Course List</h1>

        <!-- filter by department -- select box of all the unique departments -->
        <div class="mb-4">
            <form method="GET" action="{{ route('courses.index') }}" id="filterForm">
                <div class="form-group">
                    <label for="departmentFilter" class="form-label">Filter by Department <span class="text-danger">*</span></label>
                    <select name="department" id="departmentFilter" class="form-select" required>
                        <option value="">-- Select Department --</option>
                        @foreach ($departments as $department)
                            <option @selected($department == request('department')) value="{{ $department }}">{{ $department }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group mt-3">
                    <label for="campusFilter" class="form-label">Filter by Campus <span class="text-danger">*</span></label>
                    <select name="campus" id="campusFilter" class="form-select" required>
                        <option value="">-- Select Campus --</option>
                        @foreach ($campuses as $campus)
                            <option @selected($campus->id == request('campus')) value="{{ $campus->id }}">{{ $campus->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- dropdown of all SA_Facility_Types --}}
                <div class="form-group mt-3">
                    <label for="facilityTypeFilter" class="form-label">Filter by Facility Type <span class="text-danger">*</span></label>
                    <select name="sa_facility_type" id="facilityTypeFilter" class="form-select" required>
                        <option value="">-- Select Facility Type --</option>
                        @foreach ($facilityTypes as $facilityType)
                            <option @selected($facilityType == request('sa_facility_type')) value="{{ $facilityType }}">{{ $facilityType }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-3">
                    <input type="submit" value="Filter" class="btn btn-primary">
                </div>
            </form>

        </div>

        @php
            $hasAllFilters = request()->has('department') && request()->has('campus') && request()->has('sa_facility_type') 
                && request('department') !== '' && request('campus') !== '' && request('sa_facility_type') !== '';
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
                    <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                            <thead style="position: sticky; top: 0;">
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
                                        
                                        // This is for seating range caulation based on the
                                        // Spread sheet formula 
                                        // This is only done on the page load its then handled in the JS
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
                /* Make tables more compact */
                .table-responsive {
                    max-width: 100%;
                    overflow-x: auto;
                }
                .table-sm th,
                .table-sm td {
                    padding: 0.5rem 0.75rem;
                    font-size: 0.875rem;
                }
                .table-sm td {
                    white-space: nowrap;
                }
                .table-sm th {
                    font-weight: 600;
                    white-space: normal;
                    word-wrap: break-word;
                    text-align: center;
                    vertical-align: bottom;
                    min-width: 50px;
                    line-height: 1.3;
                    padding-bottom: 0.75rem;
                }
                .table-sm th[data-sort] {
                    cursor: pointer;
                    position: relative;
                }
            </style>

            <script>
                // handle user input updates for enrollment increase
                function updateForecastGrowth(row, growthPercentage) {
                    const originalEnrollment = parseFloat(row.getAttribute('data-original-enrollment'));
                    const durationMinutes = parseFloat(row.getAttribute('data-duration-minutes'));
                    const sectionsCount = parseFloat(row.getAttribute('data-sections-count'));
                    const totalCapacity = parseFloat(row.getAttribute('data-total-capacity'));
                    const wschBenchmark = parseFloat(row.getAttribute('data-wsch-benchmark'));
                    
                    const growthEnrollment = Math.round(originalEnrollment * (1 + growthPercentage / 100));
                    
                    const wschGrowth = Math.ceil((growthEnrollment * durationMinutes) / 60);
                    const studentsPerSection = sectionsCount > 0 ? (growthEnrollment / sectionsCount).toFixed(2) : '0.00';
                    const seating75Util = Math.round(totalCapacity * 0.75);
                    const roomsNeeded = wschBenchmark > 0 ? (wschGrowth / wschBenchmark).toFixed(2) : '0.00';
                    
                    //As mentioned above this is pulled fom the xlsxx logic for seating range calculation
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


        </div>
        @else
        <div class="alert alert-info mt-4" role="alert">
            <strong>Please select all filters above and click "Filter" to view course data.</strong>
            <ul class="mt-2 mb-0">
                <li>Department</li>
                <li>Campus</li>
                <li>Facility Type</li>
            </ul>
        </div>
        @endif
    </div>


@endsection
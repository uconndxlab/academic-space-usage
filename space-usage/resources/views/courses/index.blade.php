@extends('layouts.app')
@section('title', 'Course List')
@section('content')
    <div class="container">
        <h1 class="mb-4">Course List</h1>

        <!-- filter by department -- select box of all the unique departments -->
        <div class="mb-4">
            <form method="GET" action="{{ route('courses.index') }}">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="termFilter" class="form-label">Term <span class="text-danger">*</span></label>
                            <select name="term_id" id="termFilter" class="form-select" required>
                                <option value="">-- Select Term --</option>
                                @foreach ($terms as $term)
                                    <option @selected($term->id == request('term_id')) value="{{ $term->id }}">
                                        {{ $term->term_descr }} ({{ $term->term_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="departmentFilter" class="form-label">Department</label>
                            <select name="department" id="departmentFilter" class="form-select">
                                <option value="">All Departments</option>
                                @foreach ($departments as $department)
                                    <option @selected($department == request('department')) value="{{ $department }}">{{ $department }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="campusFilter" class="form-label">Campus</label>
                            <select name="campus" id="campusFilter" class="form-select">
                                <option value="">All Campuses</option>
                                @foreach ($campuses as $campus)
                                    <option @selected($campus->id == request('campus')) value="{{ $campus->id }}">{{ $campus->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="facilityTypeFilter" class="form-label">Facility Type</label>
                            <select name="sa_facility_type" id="facilityTypeFilter" class="form-select">
                                <option value="">All Facility Types</option>
                                @foreach ($facilityTypes as $facilityType)
                                    <option @selected($facilityType == request('sa_facility_type')) value="{{ $facilityType }}">{{ $facilityType }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> Apply Filters
                    </button>
                    @if(request()->hasAny(['term_id', 'department', 'campus', 'sa_facility_type']))
                        <a href="{{ route('courses.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Clear Filters
                        </a>
                    @endif
                </div>
            </form>

        </div>

        @if($requiresFilter ?? false)
            <div class="alert alert-info">
                <h5><i class="bi bi-info-circle"></i> Please Select a Term</h5>
                <p class="mb-0">To view course data, please select a term from the filter above. You can also optionally filter by department, campus, or facility type to narrow down your results.</p>
            </div>
        @endif

        @if(!($requiresFilter ?? false))
        <div id="results">
            <!-- Tabs navigation -->
            <ul class="nav nav-tabs" id="courseTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="current-tab" data-bs-toggle="tab" data-bs-target="#current"
                        type="button" role="tab" aria-controls="current" aria-selected="true">Current
                        Information</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="forecast-tab" data-bs-toggle="tab" data-bs-target="#forecast"
                        type="button" role="tab" aria-controls="forecast" aria-selected="false">Forecast
                        Tools</button>
                </li>
            </ul>

            <!-- Tabs content -->
            <div class="tab-content" id="courseTabsContent">
                <!-- Current Information Tab -->
                <div class="tab-pane fade show active" id="current" role="tabpanel" aria-labelledby="current-tab">
                    <div class="card mt-4">
                        <div class="card-body">

                            @if ($courses->isEmpty())
                                <p>No courses available.</p>
                            @else
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                      Showing {{ $courses->count() }} Courses
                                    </div>

                                </div>
                            @endif

                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr class="table-primary">
                                        <th>Course Name</th>
                                        <th>Enrollment</th>
                                        <th>Sections</th>
                                        <th>Rooms</th>
                                        <th>Capacity (combined)</th>
                                        <th>CH</th>
                                        <th>Total WSCH</th>
                                        <th>Average per Section</th>
                                        <th>Enroll Growth 20%</th>
                                        <th>WSCH Growth</th>
                                        <th>Students per Section</th>
                                        <th>Seating Capacity 75% Utiliz</th>
                                        <th>WSCH Proposed Benchmark</th>
                                        <th>No of Rooms Needed</th>
                                        <th>Seating Range</th>
                                        <th>Delta</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($courses as $course)
                                        <tr>
                                            <td>
                                                <a href="{{ route('courses.show', $course->id) }}?campus_id={{ request('campus') }}&sa_facility_type={{ request('sa_facility_type') }}">
                                                    {{ $course->subject_code }} {{ $course->catalog_number }}
                                                </a>
                                            </td>
                                            <td>{{ $course->total_enrollment }}</td>
                                            <td>{{ $course->sections_count }}</td>
                                            <td>{{ $course->rooms_used }}</td>
                                            <td>{{ $course->total_capacity }}</td>
                                            <td>{{ round($course->contact_hours ?? 0, 2) }}</td>
                                            <td>{{ $course->total_wsch }}</td>
                                            <td>{{ $course->average_per_section }}</td>
                                            <td>{{ $course->enroll_growth_20 }}</td>
                                            <td>{{ $course->wsch_growth }}</td>
                                            <td>{{ $course->students_per_section }}</td>
                                            <td>{{ $course->seating_capacity_75_utiliz ?? 'N/A' }}</td>
                                            <td>{{ $course->wsch_benchmark }}</td>
                                            <td>{{ $course->rooms_needed }}</td>
                                            <td>{{ $course->seating_range }}</td>
                                            <td class="{{ $course->delta < 0 ? 'bg-danger' : '' }}">{{ round($course->delta, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>



                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="forecast" role="tabpanel" aria-labelledby="forecast-tab">
                <div class="card mt-4">
                    <div class="card-body">
                        <!-- Enrollment Increase Input -->
                        <div class="mb-3">
                            <label for="enrollmentIncrease" class="form-label">Enrollment Increase (%)</label>
                            <input type="number" id="enrollmentIncrease" class="form-control" value="0" min="0"
                                max="100" step="1">
                        </div>

                        <!-- Forecast Table -->
                        <table class="table table-striped table-hover table-sm">
                            <thead style="position: sticky; top: 0;">
                                <tr class="table-primary">
                                    <th scope="col">Course Name</th>
                                    <th scope="col">Students Enrolled (Input)</th>
                                    <th scope="col">Sections</th>
                                    <th scope="col">Rooms</th>
                                    <th scope="col">Total WSCH</th>
                                    <th scope="col">Students per Section</th>
                                    <th scope="col">Seating 75% Utiliz</th>
                                    <th scope="col">WSCH Benchmark</th>
                                    <th scope="col">Rooms Needed</th>
                                    <th scope="col">Seating Range</th>
                                    <th scope="col" data-sort="delta" style="cursor:pointer;">Delta</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($courses as $course)
                                    <tr class="course-row table-course">
                                        <td>
                                            <a href="{{ route('courses.show', $course->id) }}">
                                                {{ $course->subject_code }} {{ $course->catalog_number }}
                                            </a>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm forecast-students-input"
                                                value="{{ $course->total_enrollment }}"
                                                data-original-enrollment="{{ $course->total_enrollment }}"
                                                data-duration-minutes="{{ $course->duration_minutes }}"
                                                data-current-rooms="{{ $course->rooms_used }}"
                                                data-sections-count="{{ $course->sections_count }}"
                                                data-seating-75="{{ $course->seating_capacity_75_utiliz ?? 0 }}"
                                                data-weekly-contact-hours="{{ $course->total_wsch }}">
                                        </td>
                                        <td>{{ $course->sections_count }}</td>
                                        <td>{{ $course->rooms_used }}</td>
                                        <td class="forecast-wsch">{{ $course->total_wsch }}</td>
                                        <td class="forecast-students-per-section">{{ $course->students_per_section }}</td>
                                        <td>{{ $course->seating_capacity_75_utiliz ?? 'N/A' }}</td>
                                        <td class="wsch-benchmark">{{ $course->wsch_benchmark }}</td>
                                        <td class="forecast-rooms-needed">{{ $course->rooms_needed }}</td>
                                        <td>{{ $course->seating_range }}</td>
                                        <td class="forecast-delta {{ $course->delta < 0 ? 'bg-danger' : '' }}">
                                            {{ round($course->delta, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <script>
                // Forecast calculator with 75% utilization benchmark
                // Updated formula: WSCH Benchmark = 28 * (room_capacity * 0.75), rounded to nearest 10
                
                forecastInput = document.querySelector('#enrollmentIncrease');
                forecastInput.addEventListener('input', function() {
                    const increase = parseFloat(this.value) / 100;

                    // Update the forecast table
                    document.querySelectorAll('.course-row').forEach(row => {
                        const originalEnrollment = parseFloat(row.querySelector('.forecast-students-input')
                            .getAttribute('data-original-enrollment'));
                        const newEnrollment = Math.ceil(originalEnrollment + (originalEnrollment * increase));

                        row.querySelector('.forecast-students-input').value = newEnrollment;
                        updateRowCalculations(row, newEnrollment);
                    });
                });

                // Individual student input changes
                document.querySelectorAll('.forecast-students-input').forEach(input => {
                    input.addEventListener('input', function() {
                        const newEnrollment = parseFloat(this.value) || 0;
                        updateRowCalculations(this.closest('tr'), newEnrollment);
                    });
                });

                function updateRowCalculations(row, newEnrollment) {
                    const input = row.querySelector('.forecast-students-input');
                    const course_duration_minutes = parseFloat(input.getAttribute('data-duration-minutes')) || 0;
                    const sections_count = parseFloat(input.getAttribute('data-sections-count')) || 1;
                    const current_rooms = parseFloat(input.getAttribute('data-current-rooms')) || 0;
                    
                    // Calculate new WSCH: ceil((enrollment * duration_minutes) / 60)
                    const newWsch = Math.ceil((newEnrollment * course_duration_minutes) / 60);
                    row.querySelector('.forecast-wsch').textContent = newWsch;

                    // Calculate students per section
                    const studentsPerSection = (newEnrollment / sections_count).toFixed(2);
                    row.querySelector('.forecast-students-per-section').textContent = studentsPerSection;

                    // Get WSCH benchmark (using 75% utilization)
                    const wschBenchmark = parseFloat(row.querySelector('.wsch-benchmark').textContent) || 1;
                    
                    // Calculate rooms needed
                    const roomsNeeded = wschBenchmark > 0 ? (newWsch / wschBenchmark).toFixed(2) : 0;
                    row.querySelector('.forecast-rooms-needed').textContent = roomsNeeded;

                    // Calculate delta
                    const delta = (current_rooms - roomsNeeded).toFixed(2);
                    const deltaCell = row.querySelector('.forecast-delta');
                    deltaCell.textContent = delta;
                    deltaCell.classList.toggle('bg-danger', delta < 0);
                    deltaCell.classList.toggle('text-white', delta < 0);
                }
            </script>


            <script>
                // Function to sort the table rows based on column data
                function sortTableByColumn(table, columnIndex, isNumeric = false) {
                    const tbody = table.querySelector('tbody');
                    console.log('Table:', table);
                    console.log('Tbody:', tbody);

                    const rowsArray = Array.from(tbody.querySelectorAll('tr.table-course'));
                    console.log('Rows array:', rowsArray);

                    console.log('Sorting by column:', columnIndex);
                    console.log('Is numeric?', isNumeric);

                    rowsArray.sort((a, b) => {
                        const aColText = a.querySelector(`td:nth-child(${columnIndex + 1})`).textContent.trim();
                        const bColText = b.querySelector(`td:nth-child(${columnIndex + 1})`).textContent.trim();

                        console.log('Comparing:', aColText, bColText);

                        // Determine if we are sorting numerically or alphabetically
                        if (isNumeric) {
                            console.log('Sorting numerically');
                            return parseFloat(aColText) - parseFloat(bColText);
                        } else {
                            return aColText.localeCompare(bColText);
                        }
                    });

                    // Append sorted rows back to the table
                    rowsArray.forEach(row => tbody.appendChild(row));
                }

                // Add click event listener to the sortable table headers
                document.querySelectorAll('th[data-sort]').forEach(header => {
                    header.addEventListener('click', function() {
                        console.log('Sorting by column:', header.textContent);
                        const table = header.closest('table');
                        const columnIndex = Array.from(header.parentNode.children).indexOf(header);
                        const isNumeric = header.getAttribute('data-sort') ===
                            'delta'; // Add any numeric column names here

                        console.log('Column index:', columnIndex);
                        console.log('Is numeric?', isNumeric);

                        sortTableByColumn(table, columnIndex, isNumeric);
                    });
                });
            </script>


        </div>
        @endif
    </div>


@endsection

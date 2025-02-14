@extends('layouts.app')
@section('title', 'Course List')
@section('content')
    <div class="container">
        <h1 class="mb-4">Course List</h1>

        <!-- filter by department -- select box of all the unique departments -->
        <div class="mb-4">
            <form method="GET" action="{{ route('courses.index') }}">
                <div class="form-group">
                    <label for="departmentFilter" class="form-label">Filter by Department</label>
                    <select name="department" id="departmentFilter" class="form-select">
                        <option value="">All Departments</option>
                        @foreach ($departments as $department)
                            <option @selected($department == request('department')) value="{{ $department }}">{{ $department }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group mt-3">
                    <label for="campusFilter" class="form-label">Filter by Campus</label>
                    <select name="campus" id="campusFilter" class="form-select">
                        <option value="">All Campuses</option>
                        @foreach ($campuses as $campus)
                            <option @selected($campus->id == request('campus')) value="{{ $campus->id }}">{{ $campus->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- dropdown of all SA_Facility_Types --}}
                <div class="form-group mt-3">
                    <label for="facilityTypeFilter" class="form-label">Filter by Facility Type</label>
                    <select name="sa_facility_type" id="facilityTypeFilter" class="form-select">
                        <option value="">All Facility Types</option>
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

                            <table class="table table-hover">
                                <thead>
                                    <tr class="table-primary">
                                        <th>Course Name</th>
                                        <th>Enrollment</th>
                                        <th>Sections</th>
                                        <th>Rooms</th>
                                        <th>Capacity (combined)</th>
                                        <th>Total WSCH</th>
                                        <th>WSCH Benchmark</th>
                                        <th>Rooms Needed</th>
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
                                            <td>{{ $course->total_wsch }}</td>
                                            <td>{{ $course->wsch_benchmark }}</td>
                                            <td>{{ $course->rooms_needed }}</td>
                                            <td class="{{ $course->delta < 0 ? 'bg-danger' : '' }}">{{ $course->delta }}
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
                        <table class="table table-striped table-hover">
                            <thead style="position: sticky; top: 0;">
                                <tr class="table-primary">
                                    <th scope="col">Course Name</th>
                                    <th scope="col">Students Enrolled (Input)</th>
                                    <th scope="col">Rooms</th>
                                    <th scope="col">WSCH (Calculated)</th>
                                    <th scope="col">Total Capacity</th>
                                    <th scope="col">WSCH Benchmark</th>
                                    <th scope="col">Labs Needed (Calculated)</th>
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
                                            <input type="number" class="form-control forecast-students-input"
                                                value="{{ $course->total_enrollment }}"
                                                data-original-enrollment="{{ $course->total_enrollment }}"
                                                data-duration-minutes = "{{ $course->duration_minutes }}"
                                                data-current-rooms = "{{ $course->rooms_used }}"
                                                data-weekly-contact-hours="{{ $course->total_wsch }}">

                                        </td>
                                        <td>{{ $course->rooms_used }}</td>

                                        <td class="forecast-wsch">{{ $course->total_wsch }}</td>
                                        <td>{{ $course->total_capacity }}</td>
                                        <td class="wsch-benchmark">{{ $course->wsch_benchmark }}</td>
                                        <td class="forecast-labs-needed">{{ $course->rooms_needed }}</td>
                                        <td class="forecast-delta {{ $course->delta < 0 ? 'bg-danger' : '' }}">
                                            {{ $course->delta }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <script>
                // when the enrollment increase input changes, update the forecast table 
                // here's the php code that does the same thing
                // $roomCapacity = optional($sections - > first() - > room) - > capacity ?? 1; // Avoid division by zero
                // $course - > wsch_benchmark = round(28 * ($roomCapacity * 0.8), -1);

                // // Rooms Needed
                // $course - > rooms_needed = round($course - > total_wsch / $course - > wsch_benchmark, 2);

                // attach an event listener to the enrollment increase input
                forecastInput = document.querySelector('#enrollmentIncrease');
                forecastInput.addEventListener('input', function() {
                    const increase = parseFloat(this.value) / 100;
                    console.log('Increase:', increase);

                    // Update the forecast table
                    document.querySelectorAll('.course-row').forEach(row => {
                        const originalEnrollment = parseFloat(row.querySelector('.forecast-students-input')
                            .getAttribute('data-original-enrollment'));
                        const newEnrollment = originalEnrollment + (originalEnrollment * increase);
                        console.log('New enrollment:', newEnrollment);

                        row.querySelector('.forecast-students-input').value = newEnrollment;

                        // Update the WSCH
                        const weeklyContactHours = parseFloat(row.querySelector('.forecast-students-input')
                            .getAttribute('data-weekly-contact-hours'));
                        console.log('Weekly Contact Hours:', weeklyContactHours);

                        // ceil(($course->total_enrollment * $course->duration_minutes) / 60);
                        const course_duration_minutes = parseFloat(row.querySelector('.forecast-students-input')
                            .getAttribute('data-duration-minutes'));
                        const newWsch = Math.ceil((newEnrollment * course_duration_minutes) / 60);

                        console.log('New WSCH:', newWsch);
                        row.querySelector('.forecast-wsch').textContent = newWsch;

                        // Update the labs needed
                        const wschBenchmark = parseFloat(row.querySelector('.wsch-benchmark').textContent);
                        const newLabsNeeded = (newWsch / wschBenchmark).toFixed(2);

                        console.log('New labs needed:', newLabsNeeded);
                        row.querySelector('.forecast-labs-needed').textContent = newLabsNeeded;

                        // Update the delta
                        const current_rooms = parseFloat(row.querySelector('.forecast-students-input').getAttribute(
                            'data-current-rooms'));
                        const delta = (current_rooms - newLabsNeeded).toFixed(2);
                        console.log('Delta:', delta);
                        row.querySelector('.forecast-delta').textContent = delta;
                        row.querySelector('.forecast-delta').classList.toggle('bg-danger', delta < 0);
                    });
                });

                // do the same for each .forecast-students-input
                document.querySelectorAll('.forecast-students-input').forEach(input => {
                    input.addEventListener('input', function() {
                        const newEnrollment = parseFloat(this.value);
                        console.log('New enrollment:', newEnrollment);

                        // Update the WSCH
                        const weeklyContactHours = parseFloat(this.getAttribute('data-weekly-contact-hours'));
                        console.log('Weekly Contact Hours:', weeklyContactHours);

                        // ceil(($course->total_enrollment * $course->duration_minutes) / 60);
                        const course_duration_minutes = parseFloat(this.getAttribute('data-duration-minutes'));
                        const newWsch = Math.ceil((newEnrollment * course_duration_minutes) / 60);
                        console.log('New WSCH:', newWsch);
                        this.closest('tr').querySelector('.forecast-wsch').textContent = newWsch;

                        // Update the labs needed
                        const wschBenchmark = parseFloat(this.closest('tr').querySelector('.wsch-benchmark')
                            .textContent);
                        const newLabsNeeded = (newWsch / wschBenchmark).toFixed(2);
                        console.log('New labs needed:', newLabsNeeded);
                        this.closest('tr').querySelector('.forecast-labs-needed').textContent = newLabsNeeded;

                        // Update the delta
                        const current_rooms = parseFloat(this.getAttribute('data-current-rooms'));
                        const delta = (current_rooms - newLabsNeeded).toFixed(2);
                        console.log('Delta:', delta);
                        this.closest('tr').querySelector('.forecast-delta').textContent = delta;
                        this.closest('tr').querySelector('.forecast-delta').classList.toggle('bg-danger', delta <
                        0);
                    });
                });
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
    </div>


@endsection

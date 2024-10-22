@extends('layouts.app')
@section('title', 'Course List')
@section('content')
    <div class="container">
        <h1 class="mb-4">Course List</h1>

        <!-- filter by department -- select box of all the unique departments -->
        <div class="mb-4">
            <form>
                <label for="departmentFilter" class="form-label">Filter by Department</label>
                <select hx-get="{{ route('courses.index') }}" hx-target="#results" hx-select="#results" hx-swap="outerHTML"
                    name="department" hx-trigger="change" hx-push-url="true" id="departmentFilter" class="form-select">
                    <option value="">All Departments</option>
                    @foreach ($departments as $department)
                        <option @selected($department == request('department')) value="{{ $department }}">{{ $department }}</option>
                    @endforeach
                </select>
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
                            <table class="table table-hover">
                                <thead style="position: sticky; top: 0;">
                                    <tr class="table-primary">
                                        <th scope="col">Course Name</th>
                                        <th scope="col">Enrollment</th>
                                       
                                        <th scope="col">Sections</th>
                                        
                                        <th scope="col">Rooms</th>
                                        <th scope="col">Capacity (combined)</th>
                                        <th scope="col">Total WSCH</th>
                                        <th scope="col">WSCH Benchmark</th>
                                        <th scope="col">Rooms Needed</th>
                                        <th scope="col" data-sort="delta" style="cursor: pointer;">
                                           
                                                Delta
                                            
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($courses as $course)
                                        @php
                                            // Current WSCH calculation
                                            $totalWSCH = ceil(($course->sections->sum('day10_enrol') * $course->duration_minutes) / 60);

                                            // WSCH Benchmark (based on room size)
                                            $roomCapacity = $course->sections->first()->room->capacity;

                                            // calculate WSCH Bechmark like $capacity × 28 hours × 0.80,
                                            // where 28 hours is the maximum weekly schedule hours and 0.80 is the 80% utilization rate for all of the rooms
                                            $wschBenchmark = round(28 * ($roomCapacity * 0.8), -1); // Benchmark rounded up

                                            // Rooms Needed based on benchmark
                                            $roomsNeeded = round($totalWSCH / $wschBenchmark, 2);

                                            $delta =  $course->sections->unique('room_id')->count() - $roomsNeeded;

                                            // $totalCapacity is the sum of unique room capacities
                                            $totalCapacity = $course->sections->unique('room_id')->sum('room.capacity');
                                        @endphp

                                        <tr class="table-course" id="course-{{ $course->id }}">
                                            <td>
                                                <a href="{{ route('courses.show', $course->id) }}">
                                                {{ $course->subject_code }} {{ $course->catalog_number }}
                                            </a>
                                            </td>
                                            <td>{{ $course->sections->sum('day10_enrol') }}</td>
                                            <td>
                                                <a
                                                 class="sections-count"
                                                 href="javascript:void(0);">
                                                {{ $course->sections->count() }}
                                            </a>

                                            </td>
                                            <td>{{ $course->sections->unique('room_id')->count() }}</td>
                                            <td>{{ $totalCapacity }}</td>

                                            <td>{{ $totalWSCH }}</td>
                                            <td>{{ $wschBenchmark }}</td>
                                            <td>{{ $roomsNeeded }}</td>
                                            <td class="{{ $delta < 0 ? 'bg-danger' : '' }}">{{ $delta }}</td>
                                        </tr>
                                        <!-- Sub-rows for each room where the course is taught -->
                                        @foreach ($course->sections->sortBy('section_number') as $section)
                                            <!-- only show the unique rooms -->


                                            <tr class="table-secondary" data-course="course-{{ $course->id }}">
                                                <td colspan="1">{{ $section->room->building->building_code }}
                                                    {{ $section->room->room_number }} - {{ $section->section_number }}
                                                </td>
                                                <td colspan="4">{{ $section->day10_enrol }} /
                                                    {{ $section->room->capacity }} </td>
                                                <td colspan="1">
                                                    {{ number_format(($section->day10_enrol * $course->duration_minutes) / 60, 2) }}
                                                </td>
                                                <!-- calculate the WSCH benchmark for this room -->
                                                @php
                                                    $roomCapacity = $section->room->capacity;
                                                    $wschBenchmark = $roomCapacity * 28 * 0.8;
                                                @endphp
                                                <td colspan="3">{{ number_format($wschBenchmark, 2) }}</td>
                                            </tr>
                                        @endforeach
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
                            <input type="number" id="enrollmentIncrease" class="form-control" value="0" min="0" max="100" step="1">
                        </div>
            
                        <!-- Forecast Table -->
                        <table class="table table-striped table-hover">
                            <thead style="position: sticky; top: 0;">
                                <tr class="table-primary">
                                    <th scope="col">Course Name</th>
                                    <th scope="col">Students Enrolled (Input)</th>
                                    <th scope="col">Existing No of Labs</th>
                                    <th scope="col">Weekly Contact Hours</th>
                                    <th scope="col">WSCH (Calculated)</th>
                                    <th scope="col">Room Capacity</th>
                                    <th scope="col">WSCH Benchmark</th>
                                    <th scope="col">Labs Needed (Calculated)</th>
                                    <th scope="col" data-sort="delta" style="cursor:pointer;">Delta</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($courses as $course)
                                    @php
                                        // Initial calculations
                                        $totalEnrollment = $course->sections->sum('day10_enrol');

                                        $weeklyContactHours = round($course->duration_minutes / 60, 2); // assuming duration is in minutes
                                        // round to nearest half hour
                                        $weeklyContactHours = round($weeklyContactHours * 2) / 2;


                                        $totalWSCH = ceil($totalEnrollment * $weeklyContactHours);
                                        $roomCapacity = $course->sections->first()->room->capacity;
                                        $wschBenchmark = round(28 * ($roomCapacity * 0.80), -1); // Benchmark rounded up
                                        $existingLabs = $course->sections->unique('room_id')->count();
                                        $labsNeeded = round($totalWSCH / $wschBenchmark, 2);

                                        $delta =  $existingLabs - $labsNeeded;
                                    @endphp
            
                                    <tr class="course-row table-course">
                                        <td>
                                           <a href="{{ route('courses.show', $course->id) }}">
                                                {{ $course->subject_code }} {{ $course->catalog_number }}
                                            </a>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control forecast-students-input"
                                                   value="{{ $totalEnrollment }}"
                                                   data-original-enrollment="{{ $totalEnrollment }}"
                                                   data-weekly-contact-hours="{{ $weeklyContactHours }}">
                                        </td>
                                        <td>{{ $existingLabs }}</td>
                                        <td>{{ $weeklyContactHours }}</td>
                                        <td class="forecast-wsch">{{ $totalWSCH }}</td>
                                        <td>{{ $roomCapacity }}</td>
                                        <td class="wsch-benchmark">{{ $wschBenchmark }}</td>
                                        <td class="forecast-labs-needed">{{ $labsNeeded }}</td>
                                        <td class="forecast-delta {{ $delta < 0 ? 'bg-danger' : '' }}">
                                            {{ $delta }}
                                        </td>
                                    </tr>
            
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <script>
                function recalculateForRow(row) {
                    let studentsInput = row.querySelector('.forecast-students-input');
                    let newEnrollment = parseFloat(studentsInput.value);
                    let weeklyContactHours = parseFloat(studentsInput.dataset.weeklyContactHours);
            
                    // Recalculate WSCH
                    let newWSCH = newEnrollment * weeklyContactHours;
                    row.querySelector('.forecast-wsch').textContent = newWSCH.toFixed(2);
            
                    // Recalculate labs needed
                    let wschBenchmark = parseFloat(row.querySelector('.wsch-benchmark').textContent);
                    let labsNeeded = (newWSCH / wschBenchmark).toFixed(2);
                    row.querySelector('.forecast-labs-needed').textContent = labsNeeded;
            
                    // Recalculate delta
                    let existingLabs = parseInt(row.querySelector('td:nth-child(3)').textContent);
                    let delta = (existingLabs - labsNeeded).toFixed(2);
                    row.querySelector('.forecast-delta').textContent = delta;
            
                    // Highlight the row if delta is negative by applying class bg-danger
                    if (delta < 0) {
                        row.querySelector('.forecast-delta').classList.add('bg-danger');
                    } else {
                        row.querySelector('.forecast-delta').classList.remove('bg-danger');
                    }
                }
            
                document.getElementById('enrollmentIncrease').addEventListener('input', function () {
                    let increasePercentage = parseFloat(this.value) / 100;
            
                    document.querySelectorAll('.course-row').forEach(function (row) {
                        let studentsInput = row.querySelector('.forecast-students-input');
                        let originalEnrollment = parseFloat(studentsInput.dataset.originalEnrollment);
            
                        // Calculate new enrollment based on percentage
                        let newEnrollment = originalEnrollment + (originalEnrollment * increasePercentage);
                        studentsInput.value = Math.round(newEnrollment);
            
                        // Recalculate for the updated row
                        recalculateForRow(row);
                    });
                });
            
                document.querySelectorAll('.forecast-students-input').forEach(function (input) {
                    input.addEventListener('input', function () {
                        let row = this.closest('.course-row');
                        recalculateForRow(row);
                    });
                });
            
                // For any .table-secondary that has a data-course attribute, hide it until the sections count link is clicked
                document.querySelectorAll('.table-secondary').forEach(function (row) {
                    if (row.dataset.course) {
                        row.style.display = 'none';
                    }
                });
            
                // Add click event listener to each .sections-count link
                document.querySelectorAll('.sections-count').forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault(); // Prevent default link behavior
                        let courseRow  = this.closest('tr');
                        let courseId = courseRow.id.split('-')[1];
                        let courseRows = document.querySelectorAll(`.table-secondary[data-course="course-${courseId}"]`);
                        courseRows.forEach(function (row) {
                            row.style.display = row.style.display === 'none' ? '' : 'none';
                        });
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
        header.addEventListener('click', function () {
            console.log('Sorting by column:', header.textContent);
            const table = header.closest('table');
            const columnIndex = Array.from(header.parentNode.children).indexOf(header);
            const isNumeric = header.getAttribute('data-sort') === 'delta'; // Add any numeric column names here

            console.log('Column index:', columnIndex);
            console.log('Is numeric?', isNumeric);

            sortTableByColumn(table, columnIndex, isNumeric);
        });
    });
</script>

            
        </div>
    </div>


@endsection

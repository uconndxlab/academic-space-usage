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
                @if (request('department'))
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="forecast-tab" data-bs-toggle="tab" data-bs-target="#forecast"
                            type="button" role="tab" aria-controls="forecast" aria-selected="false">Forecast
                            Tools</button>
                    </li>
                @endif
            </ul>

            <!-- Tabs content -->
            <div class="tab-content" id="courseTabsContent">
                <!-- Current Information Tab -->
                <div class="tab-pane fade show active" id="current" role="tabpanel" aria-labelledby="current-tab">
                    <div class="card mt-4">
                        <div class="card-body">
                            <table class="table table-striped">
                                <thead style="position: sticky; top: 0;">
                                    <tr class="table-primary">
                                        <th scope="col">Course Name</th>
                                        <th scope="col">Enrollment</th>
                                        <th scope="col">Total Capacity</th>
                                        <th scope="col">Sections</th>
                                        <th scope="col">Rooms</th>
                                        <th scope="col">Total WSCH</th>
                                        <th scope="col">WSCH Benchmark</th>
                                        <th scope="col">Rooms Needed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($courses as $course)
                                        @php
                                            // Current WSCH calculation
                                            $totalWSCH =
                                                ($course->sections->sum('day10_enrol') * $course->duration_minutes) /
                                                60;

                                            // WSCH Benchmark (based on room size)
                                            $roomCapacity = $course->sections->first()->room->capacity;

                                            // calculate WSCH Bechmark like $capacity × 28 hours × 0.80,
                                            // where 28 hours is the maximum weekly schedule hours and 0.80 is the 80% utilization rate for all of the rooms
                                            $wschBenchmark = round(28 * ($roomCapacity * 0.8), -1); // Benchmark rounded up

                                            // Rooms Needed based on benchmark
                                            $roomsNeeded = ceil($totalWSCH / $wschBenchmark);
                                        @endphp

                                        <tr class="table-course" id="course-{{ $course->id }}">
                                            <td>{{ $course->subject_code }} {{ $course->catalog_number }}</td>
                                            <td>{{ $course->sections->sum('day10_enrol') }}</td>
                                            <td>{{ $course->sections->sum('room.capacity') }}</td>
                                            <td>{{ $course->sections->count() }}</td>
                                            <td>{{ $course->sections->unique('room_id')->count() }}</td>
                                            <td>{{ number_format($totalWSCH, 2) }}</td>
                                            <td>{{ number_format($wschBenchmark, 2) }}</td>
                                            <td>{{ $roomsNeeded }}</td>
                                        </tr>
                                        <!-- Sub-rows for each room where the course is taught -->
                                        @foreach ($course->sections->sortBy('section_number') as $section)
                                            <!-- only show the unique rooms -->


                                            <tr class="table-secondary" data-course="course-{{ $course->id }}">
                                                <td colspan="">{{ $section->room->building->building_code }}
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
                                                <td colspan="5">{{ number_format($wschBenchmark, 2) }}</td>
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
                        <table class="table table-striped">
                            <thead style="position: sticky; top: 0;">
                                <tr class="table-primary">
                                    <th scope="col">Course Name</th>
                                    <th scope="col">Students Enrolled (Input)</th>
                                    <th scope="col">Existing No of Labs</th>
                                    <th scope="col">Weekly Contact Hours per Student</th>
                                    <th scope="col">WSCH (Calculated)</th>
                                    <th scope="col">Room Capacity</th>
                                    <th scope="col">WSCH Benchmark</th>
                                    <th scope="col">Labs Needed (Calculated)</th>
                                    <th scope="col">Delta</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($courses as $course)
                                    @php
                                        // Initial calculations
                                        $totalEnrollment = $course->sections->sum('day10_enrol');
                                        $weeklyContactHours = round($course->duration_minutes / 60, 2); // assuming duration is in minutes
                                        $totalWSCH = $totalEnrollment * $weeklyContactHours;
                                        $roomCapacity = $course->sections->first()->room->capacity;
                                        $wschBenchmark = round(28 * ($roomCapacity * 0.80), -1); // Benchmark rounded up
                                        $existingLabs = $course->sections->unique('room_id')->count();
                                        $labsNeeded = ceil($totalWSCH / $wschBenchmark);
                                        $delta = $labsNeeded - $existingLabs;
                                    @endphp
            
                                    <tr class="course-row">
                                        <td>{{ $course->subject_code }} {{ $course->catalog_number }}</td>
                                        <td>
                                            <input type="number" class="form-control forecast-students-input"
                                                   value="{{ $totalEnrollment }}"
                                                   data-original-enrollment="{{ $totalEnrollment }}"
                                                   data-weekly-contact-hours="{{ $weeklyContactHours }}">
                                        </td>
                                        <td>{{ $existingLabs }}</td>
                                        <td>{{ $weeklyContactHours }}</td>
                                        <td class="forecast-wsch">{{ number_format($totalWSCH, 2) }}</td>
                                        <td>{{ $roomCapacity }}</td>
                                        <td class="wsch-benchmark">{{ number_format($wschBenchmark, 2) }}</td>
                                        <td class="forecast-labs-needed">{{ $labsNeeded }}</td>
                                        <td class="forecast-delta">{{ $delta }}</td>
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
                    let labsNeeded = Math.ceil(newWSCH / wschBenchmark);
                    row.querySelector('.forecast-labs-needed').textContent = labsNeeded;
            
                    // Recalculate delta
                    let existingLabs = parseInt(row.querySelector('td:nth-child(3)').textContent);
                    let delta = labsNeeded - existingLabs;
                    row.querySelector('.forecast-delta').textContent = delta;
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
            </script>
        </div>
    </div>


@endsection

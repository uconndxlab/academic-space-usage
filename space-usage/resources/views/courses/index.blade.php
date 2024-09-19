@extends('layouts.app')
@section('title', 'Course List')
@section('content')
<div class="container">
    <h1 class="mb-4">Course List</h1>

    <!-- Tabs navigation -->
    <ul class="nav nav-tabs" id="courseTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="current-tab" data-bs-toggle="tab" data-bs-target="#current" type="button" role="tab" aria-controls="current" aria-selected="true">Current Information</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="forecast-tab" data-bs-toggle="tab" data-bs-target="#forecast" type="button" role="tab" aria-controls="forecast" aria-selected="false">Forecast Tools</button>
        </li>
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
                            @foreach($courses as $course)
                                @php
                                    // Current WSCH calculation
                                    $totalWSCH = $course->sections->sum('day10_enrol') * $course->duration_minutes / 60;

                                    // WSCH Benchmark (based on room size)
                                    $roomCapacity = $course->sections->first()->room->capacity;

                                    // calculate WSCH Bechmark like $capacity × 28 hours × 0.80,
                                    // where 28 hours is the maximum weekly schedule hours and 0.80 is the 80% utilization rate for all of the rooms
                                    $wschBenchmark = $roomCapacity * 28 * 0.80;
                                    // multiply for all sections to get the total WSCH Benchmark for the course
                                    

                                    // cleanly round the WSCH Benchmark to 0 decimal places
                                    $wschBenchmark = round($wschBenchmark, 0);

                                    // Rooms Needed based on benchmark
                                    $roomsNeeded = ceil($totalWSCH / $wschBenchmark);
                                @endphp

                                <tr>
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
                                @foreach($course->sections as $section)
                                    <!-- only show the unique rooms -->
                                    
                                    
                                    <tr class="table-secondary">
                                        <td colspan="">{{$section->room->building->building_code}} {{ $section->room->room_number }} - {{ $section->section_number }} </td>
                                        <td colspan="4">{{ $section->day10_enrol }} / {{ $section->room->capacity }} </td>
                                        <td colspan="1">{{ number_format($section->day10_enrol * $course->duration_minutes / 60, 2) }}</td>
                                        <!-- calculate the WSCH benchmark for this room -->
                                        @php
                                            $roomCapacity = $section->room->capacity;
                                            $wschBenchmark = $roomCapacity * 28 * 0.80;
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

       
        <div class="tab-pane fade" id="forecast" role="tabpanel" aria-labelledby="forecast-tab">
            <div class="card mt-4">
                <div class="card-body">
                    <!-- Global Controls for Enrollment Increase -->
                    <div class="mb-4">
                        <label for="enrollmentIncrease" class="form-label">Enrollment Increase (%)</label>
                        <input type="number" id="enrollmentIncrease" class="form-control" value="10">
                    </div>
        
                    <!-- Forecast Table -->
                    <table class="table table-striped">
                        <thead style="position: sticky; top: 0;">
                            <tr class="table-primary">
                                <th scope="col">Course Name</th>
                                <th scope="col">Students (Input)</th>
                                <th scope="col">Total Capacity</th>
                                <th scope="col">Sections</th>
                                <th scope="col">Rooms</th>
                                <th scope="col">WSCH (Input)</th>
                                <th scope="col">WSCH Benchmark</th>
                                <th scope="col">Rooms Required</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($courses as $course)
                                @php
                                    // Current WSCH calculation
                                    $totalWSCH = $course->sections->sum('day10_enrol') * $course->duration_minutes / 60;
                                    $roomCapacity = $course->sections->first()->room->capacity;
                                    $wschBenchmark = round($roomCapacity * 28 * 0.80, 0);
                                    $roomsNeeded = ceil($totalWSCH / $wschBenchmark);
                                @endphp
        
                                <tr>
                                    <td>{{ $course->subject_code }} {{ $course->catalog_number }}</td>
                                    <td>
                                        <input type="number" class="form-control forecast-students-input" 
                                               value="{{ $course->sections->sum('day10_enrol') }}" 
                                               data-original-enrollment="{{ $course->sections->sum('day10_enrol') }}">
                                    </td>
                                    <td>{{ $course->sections->sum('room.capacity') }}</td>
                                    <td>{{ $course->sections->count() }}</td>
                                    <td>{{ $course->sections->unique('room_id')->count() }}</td>
                                    <td>
                                        <input type="number" class="form-control forecast-wsch-input" 
                                               value="{{ $totalWSCH }}" 
                                               data-original-wsch="{{ $totalWSCH }}">
                                    </td>
                                    <td class="wsch-benchmark">{{ number_format($wschBenchmark, 2) }}</td>
                                    <td class="forecast-rooms-required">{{ $roomsNeeded }}</td>
                                </tr>
        
                                <!-- Sub-rows for each room where the course is taught -->
                                @foreach($course->sections as $section)
                                    <tr class="table-secondary">
                                        <td colspan="">{{ $section->room->building->building_code }} {{ $section->room->room_number }} - {{ $section->section_number }}</td>
                                        <td colspan="4">{{ $section->day10_enrol }} / {{ $section->room->capacity }}</td>
                                        <td colspan="">
                                            {{ number_format($section->day10_enrol * $course->duration_minutes / 60, 2) }}
                                        </td>
                                        @php
                                            $roomCapacity = $section->room->capacity;
                                            $wschBenchmark = $roomCapacity * 28 * 0.80;
                                        @endphp
                                        <td id="wsch-benchmark-{{ $section->id }}" colspan="5"
                                        >{{ number_format($wschBenchmark, 2) }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <script>
            // JavaScript to handle forecast recalculations when enrollment or WSCH inputs change.
            // This script will recalculate the WSCH Benchmark and Rooms Required based on the inputs.

            // Get all the input elements for enrollment and WSCH
            const enrollmentInputs = document.querySelectorAll('.forecast-students-input');
            const wschInputs = document.querySelectorAll('.forecast-wsch-input');

            // Add event listeners to each input element
            enrollmentInputs.forEach(input => {
                input.addEventListener('input', recalculateForecast);
            });

            wschInputs.forEach(input => {
                input.addEventListener('input', recalculateForecast);
            });

            // Function to recalculate the forecast based on the inputs
            function recalculateForecast() {
                // Get the enrollment and WSCH inputs for the current row
                const enrollmentInput = this.parentElement.parentElement.querySelector('.forecast-students-input');
                const wschInput = this.parentElement.parentElement.querySelector('.forecast-wsch-input');

                // Get the original enrollment and WSCH values
                const originalEnrollment = enrollmentInput.getAttribute('data-original-enrollment');
                const originalWSCH = wschInput.getAttribute('data-original-wsch');

                // Get the WSCH Benchmark and Rooms Required elements for the current row
                const wschBenchmark = this.parentElement.parentElement.querySelector('.wsch-benchmark');
                const roomsRequired = this.parentElement.parentElement.querySelector('.forecast-rooms-required');

                // Calculate the new WSCH based on the input
                const newWSCH = enrollmentInput.value * 60 / 28;

                // Calculate the new Rooms Required based on the new WSCH
                const roomCapacity = enrollmentInput.parentElement.parentElement.querySelector('td:nth-child(3)').innerText;
                const wschBenchmarkValue = roomCapacity * 28 * 0.80;
                const newRoomsRequired = Math.ceil(newWSCH / wschBenchmarkValue);

                // aslo update the WSCH Benchmark for the current row

                // Update the WSCH and Rooms Required elements with the new values
                wschInput.value = newWSCH;
                wschBenchmark.innerText = wschBenchmarkValue.toFixed(2);
                roomsRequired.innerText = newRoomsRequired;
            }

            // Function to recalculate the forecast based on the inputs

            // Get all the input elements for enrollment and WSCH

            // Add event listeners to each input element

            // Function to recalculate the forecast based on the inputs



        </script>
            
    </div>
</div>


@endsection

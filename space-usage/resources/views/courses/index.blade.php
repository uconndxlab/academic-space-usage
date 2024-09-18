@extends('layouts.app')

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
                <div class="card-header">
                    <h5 class="card-title">Current Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">Course Name</th>
                                <th scope="col">Enrollment</th>
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

                                    if ($roomCapacity == 16) {
                                        $wschBenchmark = 360;
                                    } elseif ($roomCapacity == 20) {
                                        $wschBenchmark = 450;
                                    } elseif ($roomCapacity == 24) {
                                        $wschBenchmark = 540;
                                    } elseif ($roomCapacity == 30) {
                                        $wschBenchmark = 670;
                                    } else {
                                        $wschBenchmark = 1200; // for 54-seat lab
                                    }

                                    // Rooms Needed based on benchmark
                                    $roomsNeeded = ceil($totalWSCH / $wschBenchmark);
                                @endphp

                                <tr>
                                    <td>{{ $course->subject_code }} {{ $course->catalog_number }}</td>
                                    <td>{{ $course->sections->sum('day10_enrol') }}</td>
                                    <td>{{ $course->sections->count() }}</td>
                                    <td>{{ $course->sections->unique('room_id')->count() }}</td>
                                    <td>{{ number_format($totalWSCH, 2) }}</td>
                                    <td>{{ number_format($wschBenchmark, 2) }}</td>
                                    <td>{{ $roomsNeeded }}</td>
                                </tr>
                                <!-- Sub-rows for each room where the course is taught -->
                                @foreach($course->sections as $section)
                                    <tr class="table-secondary">
                                        <td colspan="">{{ $section->section_number }} {{$section->room->building->building_code}} {{ $section->room->room_number }} - {{ $section->room->capacity }} seats</td>
                                        <td colspan="3">{{ $section->day10_enrol }}</td>
                                        <td colspan="5">{{ number_format($section->day10_enrol * $course->duration_minutes / 60, 2) }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Forecast Tools Tab -->
        <div class="tab-pane fade" id="forecast" role="tabpanel" aria-labelledby="forecast-tab">
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title">Forecast Tools</h5>
                </div>
                <div class="card-body">
                    <!-- Global Controls for Enrollment Increase -->
                    <div class="mb-4">
                        <label for="enrollmentIncrease" class="form-label">Enrollment Increase (%)</label>
                        <input type="number" id="enrollmentIncrease" class="form-control" value="10">
                    </div>

                    <!-- Forecast Table -->
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">Course Name</th>
                                <th scope="col">Students (Input)</th>
                                <th scope="col">WSCH (Input)</th>
                                <th scope="col">WSCH Benchmark</th>
                                <th scope="col">Rooms Required</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($courses as $course)
                                @php
                                    // WSCH calculation based on inputs
                                    $totalWSCH = $course->sections->sum('day10_enrol') * $course->duration_minutes / 60;
                                @endphp

                                <tr>
                                    <td>{{ $course->subject_code }} {{ $course->catalog_number }}</td>
                                    <td>
                                        <input type="number" class="form-control forecast-students-input" 
                                               value="{{ $course->sections->sum('day10_enrol') }}" 
                                               data-original-enrollment="{{ $course->sections->sum('day10_enrol') }}">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control forecast-wsch-input" 
                                               value="{{ number_format($totalWSCH, 2) }}" 
                                               data-original-wsch="{{ $totalWSCH }}">
                                    </td>
                                    <td class="wsch-benchmark">{{ number_format($wschBenchmark, 2) }}</td>
                                    <td class="forecast-rooms-required">{{ ceil($totalWSCH / $wschBenchmark) }}</td>
                                </tr>

                                <!-- Sub-rows for rooms used -->
                                @foreach($course->sections as $section)
                                    <tr>
                                        <td colspan="5">Room: {{ $section->room->name }} - Capacity: {{ $section->room->capacity }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Add JavaScript to handle forecast recalculations when enrollment or WSCH inputs change.
    document.querySelectorAll('.forecast-students-input, .forecast-wsch-input').forEach(function(input) {
        input.addEventListener('input', function() {
            var row = this.closest('tr');
            var students = parseFloat(row.querySelector('.forecast-students-input').value);
            var wsch = parseFloat(row.querySelector('.forecast-wsch-input').value);

            // Use the original room capacity to determine the WSCH benchmark
            var roomCapacity = row.querySelector('.forecast-students-input').dataset.roomCapacity;

            var benchmark;
            if (roomCapacity == 16) {
                benchmark = 360;
            } else if (roomCapacity == 20) {
                benchmark = 450;
            } else if (roomCapacity == 24) {
                benchmark = 540;
            } else if (roomCapacity == 30) {
                benchmark = 670;
            } else {
                benchmark = 1200; // for 54-seat lab
            }

            row.querySelector('.wsch-benchmark').textContent = benchmark.toFixed(2);
            var roomsRequired = Math.ceil(wsch / benchmark);
            row.querySelector('.forecast-rooms-required').textContent = roomsRequired;
        });
    });
</script>
@endsection

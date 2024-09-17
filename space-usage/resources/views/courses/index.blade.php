@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Course List</h1>

    <!-- Controls for global simulation -->
    <div class="mb-4">
        <label for="enrollmentIncrease" class="form-label">Enrollment Increase (%)</label>
        <input type="number" id="enrollmentIncrease" class="form-control" value="10">
    </div>

    <!-- Table for courses -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Courses</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col">Course</th>
                        <th scope="col">Total WSCH</th>
                        <th scope="col">Number of Rooms Used</th>
                        <th scope="col">Forecasted WSCH</th>
                        <th scope="col">Calculated Labs Needed</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($courses as $course)
                        @php
                            // Total WSCH based on current data
                            $totalWSCH = $course->sections->sum('day10_enrol') * $course->duration_minutes / 60;

                            // Default WSCH benchmark (based on first section's room capacity)
                            $labSeats = $course->sections->first()->room->capacity ?? 24; // Default to 24 seats if unknown
                            $wschBenchmark = match($labSeats) {
                                16 => 360,
                                20 => 450,
                                24 => 540,
                                30 => 670,
                                54 => 1200,
                                default => 540, // Default WSCH benchmark
                            };

                            // Unique number of rooms currently used
                            $roomsUsed = $course->sections->unique('room_id')->count();
                        @endphp

                        <tr data-total-wsch="{{ $totalWSCH }}" data-wsch-benchmark="{{ $wschBenchmark }}">
                            <td>{{ $course->subject_code }} {{ $course->catalog_number }} - {{ $course->class_descr }}</td>
                            <td>{{ $totalWSCH }}</td>
                            <td>{{ $roomsUsed }}</td>

                            <!-- Forecasted WSCH as an input field -->
                            <td>
                                <input type="number" class="form-control forecasted-wsch-input" 
                                       value="{{ $totalWSCH * 1.1 }}" 
                                       data-original-wsch="{{ $totalWSCH }}"
                                       style="width: 100px;">
                            </td>

                            <!-- Calculated Labs Needed -->
                            <td class="labs-needed">{{ ceil($totalWSCH * 1.1 / $wschBenchmark) }}</td>

                            <td>
                                <a href="{{ route('courses.show', $course->id) }}" class="btn btn-primary btn-sm">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Update the forecasted WSCH globally when enrollment increase is changed
    document.getElementById('enrollmentIncrease').addEventListener('input', function() {
        var increasePercentage = parseFloat(this.value) / 100;
        
        document.querySelectorAll('.forecasted-wsch-input').forEach(function(input) {
            var originalWSCH = parseFloat(input.dataset.originalWsch);
            var newWSCH = originalWSCH * (1 + increasePercentage);

            // Update the input field with the new forecasted WSCH
            input.value = newWSCH.toFixed(2);

            // Recalculate labs needed
            updateLabsNeeded(input);
        });
    });

    // Update the calculated labs when forecasted WSCH is manually changed for a single course
    document.querySelectorAll('.forecasted-wsch-input').forEach(function(input) {
        input.addEventListener('input', function() {
            updateLabsNeeded(this);
        });
    });

    // Function to calculate and update the labs needed based on WSCH and WSCH benchmark
    function updateLabsNeeded(input) {
        var forecastedWSCH = parseFloat(input.value);

        // Find the parent row and WSCH benchmark value
        var row = input.closest('tr');
        var wschBenchmark = parseFloat(row.dataset.wschBenchmark);
        
        // Recalculate labs needed based on the forecasted WSCH value
        var labsNeeded = Math.ceil(forecastedWSCH / wschBenchmark);

        // Update the labs needed column in the row
        row.querySelector('.labs-needed').textContent = labsNeeded;
    }
</script>

@endsection

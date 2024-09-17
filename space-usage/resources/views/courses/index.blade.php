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
                        <th scope="col">WSCH Benchmark</th>
                        <th scope="col">Calculated Labs Needed</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($courses as $course)
                        @php
                            // Total WSCH based on current data
                            $totalWSCH = $course->sections->sum('day10_enrol') * $course->duration_minutes / 60;

                            // Unique number of rooms currently used
                            $roomsUsed = $course->sections->unique('room_id')->count();
                        @endphp

                        <tr data-total-wsch="{{ $totalWSCH }}">
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

                            <!-- WSCH Benchmark switcher -->
                            <td>
                                <select class="form-select wsch-benchmark-switcher">
                                    <option value="360">16 seat lab (360 WSCH)</option>
                                    <option value="450">20 seat lab (450 WSCH)</option>
                                    <option value="540" selected>24 seat lab (540 WSCH)</option>
                                    <option value="670">30 seat lab (670 WSCH)</option>
                                    <option value="1200">54 seat lab (1200 WSCH)</option>
                                </select>
                            </td>

                            <!-- Calculated Labs Needed -->
                            <td class="labs-needed">{{ ceil($totalWSCH * 1.1 / 540) }}</td>

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

    // Update the labs needed when the WSCH benchmark is changed
    document.querySelectorAll('.wsch-benchmark-switcher').forEach(function(select) {
        select.addEventListener('change', function() {
            var row = this.closest('tr');
            var input = row.querySelector('.forecasted-wsch-input');

            updateLabsNeeded(input);
        });
    });

    // Function to calculate and update the labs needed based on WSCH and WSCH benchmark
    function updateLabsNeeded(input) {
        var forecastedWSCH = parseFloat(input.value);

        // Find the parent row and WSCH benchmark value
        var row = input.closest('tr');
        var wschBenchmark = parseFloat(row.querySelector('.wsch-benchmark-switcher').value);
        
        // Recalculate labs needed based on the forecasted WSCH value
        var labsNeeded = Math.ceil(forecastedWSCH / wschBenchmark);

        // Update the labs needed column in the row
        row.querySelector('.labs-needed').textContent = labsNeeded;
    }
</script>
@endsection

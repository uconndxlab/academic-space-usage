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
                                        <th>Avg per Section</th>
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
                                            <td>{{ $course->sections_count > 0 ? number_format($course->total_enrollment / $course->sections_count, 2) : '0.00' }}</td>
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
                                    <th scope="col">Enrollment</th>
                                    <th scope="col">Sections</th>
                                    <th scope="col">Rooms</th>
                                    <th scope="col">Capacity (combined)</th>
                                    <th scope="col">CH</th>
                                    <th scope="col">Total WSCH</th>
                                    <th scope="col">Average per section</th>
                                    <th scope="col">Enroll growth</th>
                                    <th scope="col">WSCH growth</th>
                                    <th scope="col">Students per section</th>
                                    <th scope="col">Seating capacity 75% utiliz</th>
                                    <th scope="col">WSCH proposed, benchmark</th>
                                    <th scope="col">No of rooms needed</th>
                                    <th scope="col">Seating range</th>
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
                </div>
            </div>

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

                        if (isNumeric) {
                            console.log('Sorting numerically');
                            return parseFloat(aColText) - parseFloat(bColText);
                        } else {
                            return aColText.localeCompare(bColText);
                        }
                    });

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
@extends('layouts.app')
@section('title', 'Building Details')
@section('content')
    <div class="container">
        <h1>{{ $building->description }} ({{ $building->building_code }})</h1>
        <h3>10th Day Enrollments</h3>

        <div class="row my-4">
            <!-- Total Rooms -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Rooms</h5>
                        <p class="card-text">{{ $totalRooms }}</p>
                    </div>
                </div>
            </div>
            <!-- Total Capacity -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Capacity</h5>
                        <p class="card-text">{{ $totalCapacity }}</p>
                    </div>
                </div>
            </div>
            <!-- Total Sections -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Sections</h5>
                        <p class="card-text">{{ $totalSections }}</p>
                    </div>
                </div>
            </div>
            <!-- Total Enrollment -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Enrollment</h5>
                        <p class="card-text" id="totalEnrollment">{{ $totalEnrollment }}</p>
                    </div>
                </div>
            </div>

            <!-- Total WSCH -->
            <div class="col-md-3 my-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total WSCH</h5>
                        @php
                            $totalWeeklyStudentContactHours = 0;
                            foreach ($building->rooms as $room) {
                                foreach ($room->sections as $section) {
                                    $totalWeeklyStudentContactHours +=
                                        ($section->day10_enrol * $section->course->duration_minutes) / 60;
                                }
                            }
                        @endphp
                        <p class="card-text" id="totalWSCH">{{ number_format($totalWeeklyStudentContactHours, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Room and Section Tabs -->
        <ul class="nav nav-tabs" id="buildingTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="rooms-tab" data-bs-toggle="tab" href="#rooms" role="tab"
                    aria-controls="rooms" aria-selected="true">Rooms</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="sections-tab" data-bs-toggle="tab" href="#sections" role="tab"
                    aria-controls="sections" aria-selected="false">Sections</a>
            </li>
        </ul>

        <div class="tab-content" id="buildingTabsContent">
            <div class="tab-pane fade show active" id="rooms" role="tabpanel" aria-labelledby="rooms-tab">
                <div class="row">
                    @foreach ($building->rooms->sortBy('room_number') as $room)


                        @php
                           // $room->capacity needs to be an integer
                           // $room->capacity = (int) $room->capacity;
                           $roomCapacity = (int) $room->capacity;
                            $wschBenchmark = round(28 * ($roomCapacity * 0.8), -1); // Benchmark rounded up

                            $contact_hours_total = 0;
                            foreach ($room->sections as $section) {
                                $contact_hours_total += ($section->day10_enrol * $section->course->duration_minutes) / 60;
                            }


                        @endphp

                        <div class="col-md-4 my-2">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $room->room_description }} ({{ $room->room_number }})</h5>
                                    <dl class="row">
                                        <dt class="col-sm-4">Capacity:</dt>
                                        <dd class="col-sm-8">{{ $room->capacity }}</dd>

                                        <dt class="col-sm-4">Sections:</dt>
                                        <dd class="col-sm-8">{{ $room->sections->count() }}</dd>

                                        <dt class="col-sm-4">Enrollment:</dt>
                                        <dd class="col-sm-8">{{ $room->sections->sum('day10_enrol') }}</dd>

                                        <dt class="col-sm-4">WSCH Benchmark:</dt>
                                        <dd class="col-sm-8">{{ $wschBenchmark }}</dd>

                                        <dt class="col-sm-4">WSCH:</dt>
                                        <dd class="col-sm-8">{{ $contact_hours_total }}</dd>

                                        <dt class="col-sm-4">Utilization:</dt>
                                        <dd class="col-sm-8">{{ number_format(($contact_hours_total / $wschBenchmark) * 100, 0) }}%</dd>
                                    </dl>
                                    <a href="{{ route('rooms.show', $room->id) }}" class="btn btn-primary">View Room</a>

                                </div>
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>

            <!-- Sections Table -->
            <div class="tab-pane fade" id="sections" role="tabpanel" aria-labelledby="sections-tab">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Section Code</th>
                                <th>Component Code</th>
                                <th>Course</th>
                                <th>Room</th>
                                <th>Capacity</th>
                                <th>Enrollment</th>
                                <th>% Full</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($building->rooms as $room)
                                @foreach ($room->sections as $section)
                                    <tr>
                                        <td>{{ $section->course->subject_code }}</td>
                                        <td>{{ $section->component_code }}</td>
                                        <td>
                                            <a href="{{ route('courses.show', $section->course->id) }}">
                                                {{ $section->course->subject_code }}
                                                {{ $section->course->catalog_number }}
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('rooms.show', $room->id) }}">
                                                {{ $room->room_description }}
                                            </a>
                                        </td>
                                        <td>{{ $room->capacity }}</td>
                                        <td>
                                            <span class="section-enrollment"
                                                data-original-enrollment="{{ $section->day10_enrol }}">
                                                {{ $section->day10_enrol }}
                                            </span>
                                        </td>
                                        <td class="section-full-percent">
                                            {{ number_format(($section->day10_enrol / $room->capacity) * 100, 2) }}%</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
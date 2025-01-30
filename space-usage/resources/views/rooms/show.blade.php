@extends('layouts.app')
@section('title', 'Room Details - ' . $room->room_description)
@section('content')
<!-- show all the room details -->
<div class="container mt-5">
    <h1>{{ $room->room_description }} ({{ $room->room_number }})</h1>
    <h2>Building: {{ $room->building->description }} ({{ $room->building->building_code }})</h2>
    <h3>Term: Fall 2024</h3>
    <h5>Room Capacity: {{ $room->capacity }}</h5>
    <h5>Room Type: {{ $room->sa_facility_type }}</h5>
    <div class="row my-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Sections</h5>
                    <p class="card-text">{{ $totalSections }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Enrollment</h5>
                    <p class="card-text">{{ $totalEnrollment }}</p>
                </div>
            </div>
        </div>

        <!-- total weekly student contact hours -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total WSCH</h5>

                    @php 
                        $totalWeeklyStudentContactHours = 0;
                        foreach($room->sections as $section) {
                            $totalWeeklyStudentContactHours += $section->day10_enrol * $section->course->duration_minutes / 60;
                        }
                    @endphp

                    <p class="card-text">{{ $totalWeeklyStudentContactHours }}</p>
                </div>
            </div>
        </div>
    </div>
    <h2>Sections</h2>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Section</th>
                    <th>Component Code</th>
                    <th>Enrollment</th>
                    <th>Max Enrollment</th>
                    <th>% Full</th>
                </tr>
            </thead>
            <tbody>
                @foreach($room->sections as $section)
                    <tr>
                        <td>
                            <a href="{{ route('courses.show', $section->course->id) }}">
                                {{ $section->course->subject_code }} - {{ $section->course->catalog_number }}
                            </a>
                        </td>
                        <td>{{ $section->section_number }}</td>
                        <td>{{ $section->component_code }}</td>
                        <td>{{ $section->day10_enrol }}</td>
                        <td>{{ $section->room->capacity }}</td>
                        <td>{{ number_format(($section->day10_enrol / $section->room->capacity) * 100, 2) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
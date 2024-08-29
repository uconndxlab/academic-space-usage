@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Utilization by Department: {{ $department->name }}</h1>

    <!-- Courses Table -->
    <div class="table-responsive">
        <h3>Courses</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Subject Code</th>
                    <th>Catalog Number</th>
                    <th>Section</th>
                    <th>Class Description</th>
                    <th>Term</th>
                    <th>Class Duration Weekly</th>
                    <th>Duration Minutes</th>
                    <th>Capacity</th>
                    <th>Day 10 Enrollment</th>
                    <th>Room</th>
                </tr>
            </thead>
            <tbody>
                @foreach($department->courses as $course)
                        <tr>
                            <td>{{ $course->subject_code }}</td>
                            <td>{{ $course->catalog_number }}</td>
                            <td>{{ $course->section }}</td>
                            <td>{{ $course->class_descr }}</td>
                            <td>{{ $course->term }}</td>
                            <td>{{ $course->class_duration_weekly }}</td>
                            <td>{{ $course->duration_minutes }}</td>
                            <td>{{ $course->enrl_cap }}</td>
                            <td>{{ $course->day10_enroll }}</td>
                            <td>{{ $course->room->building_code }} {{ $course->room->room_number }}</td>
                        </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

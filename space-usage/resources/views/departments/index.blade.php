<!-- resources/views/departments/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Department Utilization Overview</h1>

    <!-- Table for department totals -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Department Totals</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col">Department</th>
                        <th scope="col">Total Courses</th>
                        <th scope="col">Total Enrollments</th>
                        <th scope="col">Total Room Capacity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($departments as $department)
                        @php
                            $totalCourses = $department->courses->count();
                            $totalEnrollments = $department->courses->flatMap->enrollments->count();
                            $totalCapacity = $department->courses->flatMap->room->sum('capacity');
                        @endphp
                        <tr>
                            <td>
                              <a href="{{ route('department.show', $department) }}">
                                {{ $department->name }}
                                </a>
                            </td>
                            <td>{{ $totalCourses }}</td>
                            <td>{{ $totalEnrollments }}</td>
                            <td>{{ $totalCapacity }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

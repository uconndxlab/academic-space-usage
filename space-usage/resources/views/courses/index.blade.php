<!-- resources/views/courses/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Section List</h1>

    <!-- Table for courses -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Sections</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col">Subject Code</th>
                        <th scope="col">Catalog Number</th>
                        <th scope="col">Section</th>
                        <th scope="col">Description</th>
                        <th scope="col">Term</th>
                        <th scope="col">Room</th>
                        <th scope="col">Enrollment Cap</th>
                        <th scope="col">Room Capacity</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($courses as $course)
                        <tr>
                            <td>
                                <a href="{{ route('department.byName', $course->subject_code) }}">
                                    {{ $course->subject_code }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('course.byCodeAndNumber', ['subject_code' => $course->subject_code, 'catalog_number' => $course->catalog_number]) }}">
                                    {{ $course->catalog_number }}
                                </a>
                                

                            </td>
                            <td>{{ $course->section }}</td>
                            <td>{{ $course->class_descr }}</td>
                            <td>{{ $course->term }}</td>
                            <td>{{ $course->room->building_code }} {{ $course->room->room_number }}</td>
                            <td>{{ $course->enrl_cap }}</td>
                            <td>{{ $course->room->capacity }}</td>
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
@endsection

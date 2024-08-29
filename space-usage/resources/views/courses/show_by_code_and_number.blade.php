<!-- resources/views/courses/show_by_code_and_number.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Courses for {{ $subject_code }} {{ $catalog_number }}</h1>

    @if($courses->isEmpty())
        <p>No courses found for {{ $subject_code }} {{ $catalog_number }}.</p>
    @else
        <table class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">Subject Code</th>
                    <th scope="col">Catalog Number</th>
                    <th scope="col">Section</th>
                    <th scope="col">Class Description</th>
                    <th scope="col">Enrollment Capacity</th>
                </tr>
            </thead>
            <tbody>
                @foreach($courses as $course)
                    <tr>
                        <td>{{ $course->subject_code }}</td>
                        <td>{{ $course->catalog_number }}</td>
                        <td>{{ $course->section }}</td>
                        <td>{{ $course->class_descr }}</td>
                        <td>{{ $course->enrl_cap }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection

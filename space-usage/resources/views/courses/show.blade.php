<!-- resources/views/courses/show.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Course Details</h1>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Course: {{ $course->subject_code }} - {{ $course->catalog_number }}</h5>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Subject Code</dt>
                <dd class="col-sm-9">{{ $course->subject_code }}</dd>

                <dt class="col-sm-3">Catalog Number</dt>
                <dd class="col-sm-9">
                    <a href="{{ route('courses.show_by_code_and_number', ['subject_code' => $course->subject_code, 'catalog_number' => $course->catalog_number]) }}">
                        {{ $course->catalog_number }}
                    </a>
                </dd>

                <dt class="col-sm-3">Section</dt>
                <dd class="col-sm-9">{{ $course->section }}</dd>

                <dt class="col-sm-3">Description</dt>
                <dd class="col-sm-9">{{ $course->class_descr }}</dd>

                <dt class="col-sm-3">Term</dt>
                <dd class="col-sm-9">{{ $course->term }}</dd>

                <dt class="col-sm-3">Class Duration Weekly</dt>
                <dd class="col-sm-9">{{ $course->class_duration_weekly }} hours</dd>

                <dt class="col-sm-3">Room Capacity</dt>
                <dd class="col-sm-9">
                    @foreach($course->rooms() as $room)
                        {{ $room->capacity }}<br>
                    @endforeach
                </dd>

                <dt class="col-sm-3">Total Enrollments</dt>
                <dd class="col-sm-9">{{ $course->enrollments->count() }}</dd>
            </dl>
        </div>
    </div>
</div>
@endsection

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
{{--                
            $table->string('subject_code');
            $table->string('class_descr');
            $table->string('catalog_number');
            $table->integer('wsch_max')->nullable();
            $table->text('class_duration_weekly')->nullable();
            $table->text('duration_minutes')->nullable();
--}}

                <dt class="col-sm-3">Subject Code</dt>
                <dd class="col-sm-9">{{ $course->subject_code }}</dd>

                <dt class="col-sm-3">Class Description</dt>
                <dd class="col-sm-9">{{ $course->class_descr }}</dd>

                <dt class="col-sm-3">Catalog Number</dt>
                <dd class="col-sm-9">{{ $course->catalog_number }}</dd>

                <dt class="col-sm-3">Max Weekly Schedule Hours</dt>
                <dd class="col-sm-9">{{ $course->wsch_max }}</dd>

                <dt class="col-sm-3">Class Duration Weekly</dt>
                <dd class="col-sm-9">{{ $course->class_duration_weekly }}</dd>

                <dt class="col-sm-3">Duration Minutes</dt>
                <dd class="col-sm-9">{{ $course->duration_minutes }}</dd>
            </dl>

            <h2> Sections </h2>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Section Code</th>
                            <th>Room</th>
                            <th>Capacity</th>
                            <th>Enrollment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($course->sections as $section)
                            <tr>
                                <td>{{ $section->section_code }}</td>
                                <td>
                                    <a href="{{ route('buildings.show', $section->room->building->id) }}">
                                        {{ $section->room->building->building_code }} {{ $section->room->room_number }}
                                    </a>
                                </td>
                                <td>{{ $section->room->capacity }}</td>
                                <td>{{ $section->day10_enrol }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

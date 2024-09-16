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



                <dt class="col-sm-3">Class Description</dt>
                <dd class="col-sm-9">{{ $course->class_descr }}</dd>

                <!-- total unique number of rooms (not sections) -->
                <dt class="col-sm-3">Total Rooms used by this Course</dt>
                <dd class="col-sm-9">{{ $course->sections->unique('room_id')->count() }}</dd>

                <!-- total number of weekly student contact hours -->
                <dt class="col-sm-3">Total Weekly Student Contact Hours</dt>
                <dd class="col-sm-9">{{ $course->sections->sum('day10_enrol') * $course->duration_minutes / 60 }}</dd>

                <dt class="col-sm-3">Max Weekly Schedule Hours</dt>
                <dd class="col-sm-9">{{ $course->wsch_max }}</dd>

                <dt class="col-sm-3">Class Duration Weekly</dt>
                <dd class="col-sm-9">{{ $course->class_duration_weekly }}</dd>

                <dt class="col-sm-3">Duration Minutes</dt>
                <dd class="col-sm-9">{{ $course->duration_minutes }}</dd>

                <dt class="col-sm-3">% Full</dt>
                <dd class="col-sm-9">{{ number_format(($course->sections->sum('day10_enrol') / $course->sections->sum('room.capacity')) * 100, 2) }}%</dd>
            </dl>

            <h2> Sections </h2>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Capacity</th>
                            <th>Enrollment</th>
                            <th>% Full</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($course->sections as $section)
                            <tr>
                                <td>
                                    <a href="{{ route('buildings.show', $section->room->building->id) }}">
                                        {{ $section->room->building->building_code }} {{ $section->room->room_number }}
                                    </a>
                                </td>
                                <td>{{ $section->room->capacity }}</td>
                                <td>{{ $section->day10_enrol }}</td>
                                <td>{{ number_format(($section->day10_enrol / $section->room->capacity) * 100, 2) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

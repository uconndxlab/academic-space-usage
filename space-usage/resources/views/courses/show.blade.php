@extends('layouts.app')
@section('title', 'Course Details')
@section('content')
    <div class="container">
        {{-- breadcrumbs --}}
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb item">
                    <a href="{{ route('courses.index') }}?campus={{ $selectedCampus->id }}&department={{ $course->subject_code }}">Courses</a> &raquo;
                </li>
                <li class="breadcrumb item active" aria-current="page">
                    {{ $course->catalog_number }}
                </li>
            </ol>
        </nav>
        
        {{-- course details --}}

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title">Course: {{ $course->subject_code }} - {{ $course->catalog_number }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('courses.show', $course->id) }}" method="get"
                    hx-get="{{ route('courses.show', $course->id) }}" hx-target="#courseInfo" hx-select="#courseInfo">
                <dl class="row">
                    <?php $course_duration_nearest_hour = ceil($course->duration_minutes / 60); ?>

                    <dt class="col-sm-3">Class Description</dt>
                    <dd class="col-sm-9">{{ $course->class_descr }}</dd>

                    {{-- select a campus --}}
                    <dt class="col-sm-3">Campus</dt>
                    
                    <dd class="col-sm-9">
                            <select 
                                hx-target="#courseInfo"
                                hx-select="#courseInfo" name="campus_id" id="campus_id" class="form-select"
                                onchange="this.form.submit()">
                                <option value="">Select a Campus</option>
                                @foreach ($campuses as $campus)
                                    <option value="{{ $campus->id }}" @if (isset($selectedCampus) && $campus->id == $selectedCampus->id) selected @endif>
                                        {{ $campus->name }}
                                    </option>
                                @endforeach
                            </select>

                    </dd>

                    <dt class="col-sm-3">Facility Type</dt>
                    <dd class="col-sm-9">
                        <select name="sa_facility_type" id="facility_type" class="form-select"
                            onchange="this.form.submit()">
                            <option value="">Select a Facility Type</option>
                            @foreach ($facilityTypes as $facilityType)
                                <option value="{{ $facilityType }}" @if (isset($selectedFacilityType) && $facilityType == $selectedFacilityType) selected @endif>
                                    {{ $facilityType }}
                                </option>
                            @endforeach
                        </select>
                    </dd>



                </dl>
            </div>
        </div>

        <div id="courseInfo">

            @if ($course->sections->count() > 0)
  
            <div class="card mb-3">
                <div class="card-body">
                    <dl>
                        <dt class="col-sm-3">Rooms used at {{ $selectedCampus->name }}</dt>
                        <dd class="col-sm-9">{{ $course->sections->unique('room_id')->count() }}</dd>

                        <dt class="col-sm-3">Total WSCH at {{ $selectedCampus->name }}</dt>
                        <dd class="col-sm-9">
                            {{ ceil($course->sections->sum('day10_enrol') * $course_duration_nearest_hour) }}
                        </dd>

                        <dt class="col-sm-3">Class Duration Weekly</dt>
                        <dd class="col-sm-9">{{ $course->class_duration_weekly }}</dd>

                        <dt class="col-sm-3">% Full</dt>
                        <dd class="col-sm-9">
                            {{ number_format(($course->sections->sum('day10_enrol') / $course->sections->sum('room.capacity')) * 100, 2) }}%
                        </dd>
                    </dl>
                </div>
            </div>


            <h2>Sections</h2>

            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                @foreach ($componentCodes as $componentCode)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link @if ($loop->first) active @endif" id="{{ $componentCode }}-tab"
                            data-bs-toggle="tab" href="#{{ $componentCode }}" role="tab"
                            aria-controls="{{ $componentCode }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                            {{ $componentCode }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <!-- Tabs Content -->
            <div class="tab-content mt-3" id="myTabContent">
                @foreach ($componentCodes as $componentCode)
                    <div class="tab-pane fade @if ($loop->first) show active @endif"
                        id="{{ $componentCode }}" role="tabpanel" aria-labelledby="{{ $componentCode }}-tab">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Component Code</th>
                                        <th>Section</th>
                                        <th>Room</th>
                                        <th>Capacity</th>
                                        <th>Enrollment</th>
                                        <th>% Full</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($course->sections->where('component_code', $componentCode) as $section)
                                        <tr>
                                            <td>{{ $section->component_code }}</td>
                                            <td>{{ $section->section_number }}</td>
                                            <td>
                                                <a href="{{ route('rooms.show', $section->room->id) }}">
                                                    {{ $section->room->room_description }}
                                                </a>
                                            </td>
                                            <td>{{ $section->room->capacity }}</td>
                                            <td>{{ $section->day10_enrol }}</td>
                                            <td>{{ number_format(($section->day10_enrol / $section->room->capacity) * 100, 2) }}%
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                                {{-- totals --}}
                                <tfoot>
                                    <tr>
                                        <td colspan="3">Total</td>
                                        <td>{{ $course->sections->where('component_code', $componentCode)->sum('room.capacity') }}
                                        </td>
                                        <td>{{ $course->sections->where('component_code', $componentCode)->sum('day10_enrol') }}
                                        </td>
                                        <td>
                                            {{ number_format(($course->sections->where('component_code', $componentCode)->sum('day10_enrol') / $course->sections->where('component_code', $componentCode)->sum('room.capacity')) * 100, 2) }}
                                            %
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
            @else
            <div class="alert alert-info" role="alert">
                No sections found for this course 
                {{-- if campus is set, point that out --}}
                @if (isset($selectedCampus))
                    at {{ $selectedCampus->name }}
                @endif
            </div>
            @endif
        </div>
    </div>
@endsection

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
                    <?php $course_duration_nearest_hour = ceil($course->duration_minutes / 60); ?>

                    <dt class="col-sm-3">Class Description</dt>
                    <dd class="col-sm-9">{{ $course->class_descr }}</dd>

                    <dt class="col-sm-3">Total Rooms used by this Course</dt>
                    <dd class="col-sm-9">{{ $course->sections->unique('room_id')->count() }}</dd>

                    <dt class="col-sm-3">Total Weekly Student Contact Hours</dt>
                    <dd class="col-sm-9">{{ ceil($course->sections->sum('day10_enrol') * $course_duration_nearest_hour) }}
                    </dd>

                    <dt class="col-sm-3">Max Weekly Schedule Hours</dt>
                    <dd class="col-sm-9">{{ $course->wsch_max }}</dd>

                    <dt class="col-sm-3">Class Duration Weekly</dt>
                    <dd class="col-sm-9">{{ $course->class_duration_weekly }}</dd>

                    <dt class="col-sm-3">% Full</dt>
                    <dd class="col-sm-9">
                        {{ number_format(($course->sections->sum('day10_enrol') / $course->sections->sum('room.capacity')) * 100, 2) }}%
                    </dd>
                </dl>

                <h2>Sections</h2>

                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    @foreach ($componentCodes as $componentCode)
                        <li class="nav-item" role="presentation">
                            <a class="nav-link @if ($loop->first) active @endif"
                                id="{{ $componentCode }}-tab" data-bs-toggle="tab" href="#{{ $componentCode }}" role="tab"
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
                                            <td>{{ $course->sections->where('component_code', $componentCode)->sum('room.capacity') }}</td>
                                            <td>{{ $course->sections->where('component_code', $componentCode)->sum('day10_enrol') }}</td>
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

            </div>
        </div>
    </div>
@endsection

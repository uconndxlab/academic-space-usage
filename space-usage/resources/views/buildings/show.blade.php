@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $building->description }} ({{ $building->building_code }})</h1>
    <h2>Term: Fall 2023</h2>
    <h3>10th Day Enrollments</h3>



    <div class="row my-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Rooms</h5>
                    <p class="card-text">{{ $totalRooms }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Capacity</h5>
                    <p class="card-text">{{ $totalCapacity }}</p>
                </div>
            </div>
        </div>
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
    </div>

    <h2>Rooms</h2>

    <!-- search form for room # -->
    <form action="{{ route('buildings.show', $building->id) }}" method="GET">
        <div class="input-group mb-3">
            <input type="text" class="form-control" placeholder="Search Room #" name="room_number" value="{{ request('room_number') }}">
            <button class="btn btn-primary" type="submit">Search</button>
        </div>

    <div class="row">
        @foreach($building->rooms as $room)
            <div class="col-md-4 my-2">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ $room->room_description }} ({{ $room->room_number }})</h5>
                        <p class="card-text">Capacity: {{ $room->capacity }}</p>
                        <p class="card-text">Sections: {{ $room->sections->count() }}</p>
                        <p class="card-text">Enrollment: {{ $room->sections->sum('day10_enrol') }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    

    <h2>Sections</h2>

    <!-- search form for subject code -->
    <form action="{{ route('buildings.show', $building->id) }}" method="GET">
        <div class="input-group mb-3">
            <input type="text" class="form-control" placeholder="Search Subject Code" name="subject_code" value="{{ request('subject_code') }}">
            <button class="btn btn-outline-secondary" type="submit">Search</button>
        </div>
    </form>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>

                <th>Section Code</th>
                <th>Course</th>
                <th>Room</th>
                <th>Capacity</th>
                <th>Enrollment</th>
            </tr>
        </thead>
        <tbody>
            @foreach($building->rooms as $room)
                @foreach($room->sections as $section)
                    <tr>
                        <td>{{ $section->course->subject_code }}</td>
                        <td>
                            <a href="{{ route('courses.show', $section->course->id) }}">
                                {{ $section->course->subject_code }} {{ $section->course->catalog_number }}
                            </a>
                        </td>
                        <td>
                            <a href="{{route('rooms.show', $room->id)}}">
                            {{ $building->building_code }} {{ $room->room_number }}
                            </a>
                        </td>
                        <td>{{ $room->capacity }}</td>
                        <td>{{ $section->day10_enrol }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>

</div>
@endsection

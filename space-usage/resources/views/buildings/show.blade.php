@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $building->description }} ({{ $building->building_code }})</h1>

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
    <ul class="list-group">
        @foreach($building->rooms as $room)
            <li class="list-group-item">
                <strong>{{ $room->room_descr }} ({{ $room->room_number }})</strong><br>
                Capacity: {{ $room->capacity }}<br>
                Sections: {{ $room->sections->count() }}<br>
                Enrollment: {{ $room->sections->sum('day10_enrol') }}
            </li>
        @endforeach
    </ul>
</div>
@endsection

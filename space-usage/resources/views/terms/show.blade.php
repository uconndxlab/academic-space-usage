@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $term->term_descr }} ({{ $term->term_code }})</h1>

    <div class="row my-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Courses</h5>
                    <p class="card-text">{{ $totalCourses }}</p>
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

    <h2>Buildings</h2>
    <!-- search for a room -->
    <form action="{{ route('terms.show', $term->id) }}" method="GET" class="form-inline my-4">
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Search building..." name="building_searh"
                value="{{ request('buidling_serach') }}">
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>
    <ul class="list-group">
        @foreach($buildings as $building)
        <li class="list-group-item">
            <a href="{{ route('buildings.show', $building->id) }}">
                {{ $building->description }} ({{ $building->building_code }})
            </a>
        </li>
        @endforeach
    </ul>
</div>
@endsection
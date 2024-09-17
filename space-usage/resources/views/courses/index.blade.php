<!-- resources/views/courses/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Course List</h1>

    <!-- Table for courses -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Courses</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col">Course</th>
                        <th scope="col">Total WSCH</th>
                        <th scope="col">Number of Rooms Used (Actual)</th>
                        <th scope="col">Forecasted WSCH</th>
                        <th scope="col">Number of Rooms Needed (Caclulated)</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($courses as $course)
                        <tr>
                            <td>
                               
                                    {{ $course->subject_code }}
                                

                                    {{ $course->catalog_number }}
                               
                                
                            - {{ $course->class_descr }}</td>

                            <td>{{ $course->sections->sum('day10_enrol') * $course->duration_minutes / 60 }}</td>

                            <td>{{ $course->sections->unique('room_id')->count() }}</td>

                            <td><input type="number" class="form-control" value="{{ $course->wsch_max }}"></td>
                            <td><input type="number" class="form-control" value="0"></td>


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

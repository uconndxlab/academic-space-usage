<?php

use Illuminate\Support\Facades\Route;
use App\Models\Room;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Department;
use App\Http\Controllers\DepartmentController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/departments', [DepartmentController::class, 'index'])->name('department.index');
Route::get('/department/{id}', [DepartmentController::class, 'show'])->name('department.show');



<?php

// app/Http/Controllers/CourseController.php
namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Support\Facades\DB;


class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::all()->sortBy('catalog_number');

        return view('courses.index', compact('courses'));
    }


    public function show($id)
    {
        $course = Course::find($id);

        return view('courses.show', compact('course'));
    }

    public function showByCodeAndNumber($subject_code, $catalog_number)
    {
        $subject_code = strtoupper($subject_code);
        $catalog_number = strtoupper($catalog_number);
        // Fetch courses based on subject_code and catalog_number
        $courses = Course::where('subject_code', $subject_code)
                         ->where('catalog_number', $catalog_number)
                         ->get();

        return view('courses.show_by_code_and_number', compact('courses', 'subject_code', 'catalog_number'));
    }
}

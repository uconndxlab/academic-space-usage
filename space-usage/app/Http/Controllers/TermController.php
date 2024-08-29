<?php

namespace App\Http\Controllers;

use App\Models\Term;
use Illuminate\Http\Request;
use App\Models\Building;

class TermController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $terms = Term::all(); // Fetch all available terms
        return view('terms.index', compact('terms'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Retrieve the term with its related courses and sections
        $term = Term::with('courses.sections')->findOrFail($id);
    
        // Summarize data
        $totalCourses = $term->courses->unique('catalog_number')->count();
        $totalSections = $term->courses->sum(function ($course) {
            return $course->sections->count();
        });
        $totalEnrollment = $term->courses->sum(function ($course) {
            return $course->sections->sum('day10_enrol');
        });
    
        // Get a list of buildings associated with the term
        $buildings = Building::whereHas('rooms.sections.course', function ($query) use ($term) {
            $query->where('term_id', $term->id);
        })->get();
    
        return view('terms.show', compact('term', 'totalCourses', 'totalSections', 'totalEnrollment', 'buildings'));
    }
    

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Term $term)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Term $term)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Term $term)
    {
        //
    }
}

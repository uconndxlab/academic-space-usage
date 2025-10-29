<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AdminController
{
    public function index()
    {
        if(!Auth::user()->isAdmin) {
            return redirect()->route('home');
        } else {
            $users = User::all();
            return view('admin.index', compact('users'));
        }
    }

    public function addUser(Request $request){
        if(!Auth::user()->isAdmin) {
            return redirect()->route('invalidLogin');
        }

        $request->validate([
            'netID' => 'required|string|max:255',
            'isAdmin' => 'nullable',
        ]);
        
        // Check if user already exists
        $existingUser = User::where('netID', $request->netID)->first();
        
        if($existingUser) {
            return redirect()->route('admin.index')->with('error', 'User already exists');
        }
        
        $isAdminValue = $request->input('isAdmin');
        $isAdmin = false;
        if (is_array($isAdminValue)) {
            $isAdmin = in_array('1', $isAdminValue);
        } else {
            $isAdmin = $request->boolean('isAdmin');
        }
        
        $user = User::create([
            'netID' => $request->netID,
            'isAdmin' => $isAdmin
        ]);
        
        return redirect()->route('admin.index')->with('success', 'User added successfully');
    }

    public function removeUser($id){
        if(!Auth::user()->isAdmin) {
            return redirect()->route('invalidLogin');
        }
        $user = User::find($id);
        if(!$user) {
            return redirect()->route('admin.index')->with('error', 'User not found');
        }
        $user->delete();
        return redirect()->route('admin.index')->with('success', 'User removed successfully');
    }

    public function makeAdmin($id){
        if(!Auth::user()->isAdmin) {
            return redirect()->route('invalidLogin');
        }
        $user = User::find($id);
        $user->isAdmin = true;
        $user->save();
        return redirect()->route('admin.index')->with('success', 'User made admin successfully');
    }

    public function removeAdmin($id){
        if(!Auth::user()->isAdmin) {
            return redirect()->route('invalidLogin');
        }
        $user = User::find($id);
        $user->isAdmin = false;
        $user->save();
        return redirect()->route('admin.index')->with('success', 'User removed admin successfully');
    }
}   
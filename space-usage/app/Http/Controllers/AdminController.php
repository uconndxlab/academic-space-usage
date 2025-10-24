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
            'isAdmin' => 'required|boolean',
        ]);
        
        // Create or update the user
        $user = User::updateOrCreate(
            ['netID' => $request->netID],
            ['isAdmin' => $request->isAdmin]
        );
        
        return redirect()->route('admin.index')->with('success', 'User added/updated successfully');
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
}   
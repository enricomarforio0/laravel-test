<?php

namespace App\Http\Controllers;

use App\Models\UserVander;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
        $users = DB::table('users')
                ->orderBy('nome')
                ->join('login', 'users.id', '=', 'login.userid')
                ->get();
        return view('app', ['users' => $users])->with(request()->input('page'));
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
    public function show(UserVander $userVander)
    {
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserVander $userVander)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserVander $userVander)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserVander $userVander)
    {
        //
    }
}

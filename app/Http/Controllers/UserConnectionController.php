<?php

namespace App\Http\Controllers;

use App\Models\UserConnection;
use App\Http\Requests\StoreUserConnectionRequest;
use App\Http\Requests\UpdateUserConnectionRequest;

class UserConnectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreUserConnectionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUserConnectionRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\UserConnection  $userConnection
     * @return \Illuminate\Http\Response
     */
    public function show(UserConnection $userConnection)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\UserConnection  $userConnection
     * @return \Illuminate\Http\Response
     */
    public function edit(UserConnection $userConnection)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateUserConnectionRequest  $request
     * @param  \App\Models\UserConnection  $userConnection
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUserConnectionRequest $request, UserConnection $userConnection)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UserConnection  $userConnection
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserConnection $userConnection)
    {
        //
    }
}

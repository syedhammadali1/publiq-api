<?php

namespace App\Http\Controllers;

use App\Models\TempImage;
use App\Http\Requests\StoreTempImageRequest;
use App\Http\Requests\UpdateTempImageRequest;

class TempImageController extends Controller
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
     * @param  \App\Http\Requests\StoreTempImageRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTempImageRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TempImage  $tempImage
     * @return \Illuminate\Http\Response
     */
    public function show(TempImage $tempImage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\TempImage  $tempImage
     * @return \Illuminate\Http\Response
     */
    public function edit(TempImage $tempImage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateTempImageRequest  $request
     * @param  \App\Models\TempImage  $tempImage
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateTempImageRequest $request, TempImage $tempImage)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TempImage  $tempImage
     * @return \Illuminate\Http\Response
     */
    public function destroy(TempImage $tempImage)
    {
        //
    }
}

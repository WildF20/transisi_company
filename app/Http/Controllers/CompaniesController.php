<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompaniesRequest;
use App\Http\Requests\UpdateCompaniesRequest;
use App\Repository\CompaniesRepository;
use Illuminate\Support\Facades\Storage;

class CompaniesController extends Controller
{
    private $companiesRepo;

    public function __construct(CompaniesRepository $companiesRepo)
    {
        $this->companiesRepo = $companiesRepo;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $companies = $this->companiesRepo->getAll(5);
        return response()->json($companies);
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompaniesRequest $request)
    {
        $data = $request->validated();
        
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $path = $file->store('company/logo', 'public');
            $data['logo'] = Storage::url($path);
        }
        
        try {
            $company = $this->companiesRepo->create($data);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
        
        return response()->json($company, 201);
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

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $data = $this->companiesRepo->getById($id);   
        } catch (\Exception $th) {
            return $th->getMessage();
        }

        return response()->json($data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id, UpdateCompaniesRequest $request)
    {
        $data = $request->validated();
        
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $path = $file->store('company/logo', 'public');
            $data['logo'] = Storage::url($path);
        }
        
        try {
            $companies = $this->companiesRepo->update($id, $data);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
        
        return response()->json($companies);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $this->companiesRepo->delete($id);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
        
        return response()->json(null, 204);
    }
}

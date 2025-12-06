<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use App\Http\Requests\StoreEmployeesRequest;
use App\Http\Requests\UpdateEmployeesRequest;
use App\Repository\EmployeesRepository;
use App\Repository\CompaniesRepository;
use Illuminate\Support\Facades\Storage;

class EmployeesController extends Controller
{
    private $employeesRepo;

    public function __construct(EmployeesRepository $employeesRepo)
    {
        $this->employeesRepo = $employeesRepo;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $employees = $this->employeesRepo->getAll(5);    
        } catch (\Exception $th) {
            return $th->getMessage();
        }
        
        return response()->json($employees);
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeesRequest $request)
    {
        $data = $request->validated();
        
        try {
            $employee = $this->employeesRepo->create($data);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
        
        return response()->json($employee, 201);
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
            $data = $this->employeesRepo->getById($id);   
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
    public function update($id, UpdateEmployeesRequest $request)
    {
        $data = $request->validated();
        
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $path = $file->store('employee/logo', 'public');
            $data['logo'] = Storage::url($path);
        }
        
        try {
            $employees = $this->employeesRepo->update($id, $data);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
        
        return response()->json($employees);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $this->employeesRepo->delete($id);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
        
        return response()->json(null, 204);
    }

    public function getList(Request $request)
    {
        if ($request->has('param')) {
            switch ($request->param) {
                case 'company':
                    $company = new CompaniesRepository();
                    $data = $company->getList();
                    break;
                
                default:
                    $data = null;
                    break;
            }

            return response()->json($data, 200);
        }

        return response()->json(null, 200);
    }
}

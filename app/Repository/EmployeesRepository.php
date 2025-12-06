<?php

namespace App\Repository;

use App\Models\Companies;
use App\Models\Employees;

class EmployeesRepository
{
    public function getAll($paginate = 5)
    {
        try {
            $employee = Employees::with([
                'company' => function ($query) {
                    return $query->select('id', 'name');
                }    
            ])->paginate($paginate);
        } catch (\Throwable $th) {
            throw $th;
        }
        
        return $employee;
    }

    public function getById(int $id): ?Employees
    {
        return Employees::find($id);
    }

    public function create(array $data): Employees
    {
        return Employees::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Employees::find($id)->update($data);
    }

    public function delete(int $id): ?bool
    {
        return Employees::find($id)->delete();
    }    
}

?>
<?php

namespace App\Repository;

use App\Models\Companies;

class CompaniesRepository
{
    public function getAll($paginate = 5)
    {
        return Companies::paginate($paginate);
    }

    public function getById(int $id): ?Companies
    {
        return Companies::find($id);
    }

    public function create(array $data): Companies
    {
        return Companies::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Companies::find($id)->update($data);
    }

    public function delete(int $id): ?bool
    {
        return Companies::find($id)->delete();
    }    
}

?>
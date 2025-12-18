<?php

namespace App\Kis;

class PaginationFormat
{

    const VALIDATION = [
        'page'      => 'required|numeric',
        'limit'     => 'required|numeric',
        'column'    => 'nullable|string',
        'ascending' => 'required|boolean',
        'search'    => 'nullable|string'
    ];
}

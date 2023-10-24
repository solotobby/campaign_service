<?php

namespace app\Services;

use App\Models\TransactionCategory;

class GetTransactionCategoryID
{
    protected $name;
    
    /**
     * __construct
     *
     * @param  mixed $name
     * @return void
     */
    public function __construct($name)
    {
        $this->name = $name;
    }
    
    /**
     * run
     *
     * @return String
     */
    public function run()
    {
        return TransactionCategory::where('name', $this->name)->firstOrFail()->id;
    }
}
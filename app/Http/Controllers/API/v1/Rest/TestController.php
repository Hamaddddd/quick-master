<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

set_time_limit(0);
ini_set('memory_limit', '4G');

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

class TestController extends Controller
{
    use ApiResponse;

    public function bosYaTest()
    {

    }

}

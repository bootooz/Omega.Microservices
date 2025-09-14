<?php

namespace App\Http\Controllers;

use App\Skiwatch\Api\Telegram;
use App\Skiwatch\Api\Table;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public static function finish(Request $request)
    {
        return Table::finish($request);
    }
}

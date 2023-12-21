<?php

namespace App\Http\Controllers\Admin;

use App\Log;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class LogController extends Controller
{



    /**
     * For how many minutes to cache the logs
     *
     *
     * @var int
     */
    private $logsCacheTimeMinutes = 0;
    /**
     * How many logs to display per page
     *
     * @var int
     */
    private $logsPerPage = 35;

    public function __construct(){
        $this -> middleware('admin_panel_access');
    }

    private function checkLogs(){
        if(Gate::denies('has-access', 'logs'))
            abort(403);
    }

    public function showLog(){
        $this -> checkLogs();

        $logs = Cache::remember('logs', $this->logsCacheTimeMinutes, function () {
            return Log::with('user')->orderBy('created_at','desc')->paginate($this->logsPerPage);
        });
        return view('admin.log')->with([
            'cacheMinutes' => $this->logsCacheTimeMinutes,
            'logs' => $logs
        ]);
    }
}

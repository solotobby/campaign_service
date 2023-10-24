<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Campaign;

class CampaignMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $campaign = Campaign::find($request->route('campaign_id'));
        if ($campaign == null) {
            return response()->json(['error'=> true, 'message' => 'campaign id does not exist'], 400);
        }
        return $next($request);
    }
}

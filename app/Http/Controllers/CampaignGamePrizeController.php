<?php

namespace App\Http\Controllers;

use App\Models\CampaignGamePrize;
use Illuminate\Http\Request;


class CampaignGamePrizeController extends Controller
{

    public function index()
    {
        try{
            $gamePrize = CampaignGamePrize::all();
        }catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'cannot fetch'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Game Prize List', 'data' => $gamePrize]);
    }

    public function store(Request $request)
    {
        $validated = $this->validate($request, [
            'name' => 'required|string',
            'amount' => 'required|numeric'
        ]);

        try{
            $chek = CampaignGamePrize::where('name', $validated['name'])->first();
            if($chek == null){
                $storeGamePrize = CampaignGamePrize::updateOrCreate(['name' => $validated['name'], 'amount' => $validated['amount']]);
            }else{
                 $chek->update(['amount' => $validated['amount']]);
                $storeGamePrize = CampaignGamePrize::find($chek->id);
            }

        }catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'cannot save game prize'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Game Prize Saved', 'data' => $storeGamePrize]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CampaignVoucherReward;
use App\Models\CampaignVoucherRedemption;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UploadPregeneratedVoucher;

class CampaignVoucherRewardController extends Controller
{
    /**
     * create
     *
     * @param  mixed $request
     * @param  mixed $campaign_id
     * @return \Illuminate\Http\Response
     */
    public function upload(Request $request, $campaign_id)
    {
        $validated = $this->validate($request, [
            'vouchers' => 'required|file|mimes:xls,xlsx|max:1024'
        ]);

        try {
            $import = new UploadPregeneratedVoucher($campaign_id);
            Excel::import($import, request()->file('vouchers'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            report($e);
            return response()->json($e->failures(), 400);
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => "unable to upload vouchers"], 500);
        }

        return response()->json(['error' => false, 'message' => "vouchers uploaded"]);
    }
}

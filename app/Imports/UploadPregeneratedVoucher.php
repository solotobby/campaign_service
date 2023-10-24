<?php

namespace App\Imports;

use App\Models\CampaignVoucherReward;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class UploadPregeneratedVoucher implements ToCollection, WithHeadingRow, WithValidation, WithChunkReading
{
    use Importable;

    protected $campaignID;
    
    public function __construct($campaignID) {
        $this->campaignID = $campaignID;
    }
        
    /**
     * collection
     *
     * @param  mixed $rows
     * @return void
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            CampaignVoucherReward::firstOrCreate([
                'campaign_id' => $this->campaignID,
                'voucher' => $row['voucher_code'],
            ],[
                'voucher_value' => $row['voucher_value']
            ]);
        }
    }

    public function rules(): array
    {
        return [
            '*.voucher_code' => 'required',
            '*.voucher_value' => 'required',
            '*.valid_from' => 'nullable',
            '*.valid_till' => 'nullable',
        ];
    }

    public function chunkSize(): int
    {
        return 50;
    }
}

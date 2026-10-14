<?php

namespace App\Services;

/**
 * MTN MoMo Rwanda USSD instruction builder (Ch.5.4.4).
 * There is no live Collections API integration yet: this generates the
 * manual USSD string the client dials on their phone. Once a merchant
 * account with the MTN MoMo Developer API is available, this service is
 * the seam where a real Collections API request/callback would replace
 * the manual "dial and wait for admin confirmation" flow.
 */
class MomoService
{
    public function merchantNumber(): ?string
    {
        return config('services.mtn_momo.merchant_number');
    }

    public function ussdCode(float $amount): string
    {
        $merchant = $this->merchantNumber() ?: '000000';

        return "*182*1*1*{$merchant}*" . number_format($amount, 0, '', '') . '#';
    }
}

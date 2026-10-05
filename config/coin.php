<?php
return [
    'recharge_fee_rate' => (float) env('POINTS_RECHARGE_FEE_RATE', 0.03),
    'seller_withdrawal_fee_rate' => (float) env('SELLER_WITHDRAWAL_FEE_RATE', 0.05),
];

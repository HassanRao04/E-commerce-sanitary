<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case BankTransfer = 'bank_transfer';
    case Stripe = 'stripe';
    case Jazzcash = 'jazzcash';
    case Easypaisa = 'easypaisa';
    case Payfast = 'payfast';
}

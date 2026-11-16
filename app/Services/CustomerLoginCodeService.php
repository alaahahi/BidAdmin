<?php

namespace App\Services;

use App\Models\Customer;

class CustomerLoginCodeService
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function generateUnique(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (Customer::where('login_code', $code)->exists());

        return $code;
    }

    public function ensure(Customer $customer): Customer
    {
        if (! $customer->login_code) {
            $customer->login_code = $this->generateUnique();
            $customer->save();
        }

        return $customer;
    }

    public function regenerate(Customer $customer): Customer
    {
        $customer->login_code = $this->generateUnique();
        $customer->save();

        return $customer;
    }
}

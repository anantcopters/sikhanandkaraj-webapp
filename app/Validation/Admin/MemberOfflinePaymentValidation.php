<?php

declare(strict_types=1);

namespace App\Validation\Admin;

final class MemberOfflinePaymentValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function rules(): array
    {
        return [
            'plan_code' => [
                'label' =>
                'Membership Plan',

                'rules' => [
                    'required',
                    'in_list[GO,PLUS,PRO]',
                ],

                'errors' => [
                    'required' =>
                    'Please select a membership plan.',

                    'in_list' =>
                    'Please select a valid membership plan.',
                ],
            ],

            'payment_date' => [
                'label' =>
                'Payment Date',

                'rules' => [
                    'required',
                    'valid_date[Y-m-d]',
                ],

                'errors' => [
                    'required' =>
                    'Please select the payment date.',

                    'valid_date' =>
                    'Please select a valid payment date.',
                ],
            ],

            'payment_method' => [
                'label' =>
                'Payment Source',

                'rules' => [
                    'required',
                    'in_list[BANK_TRANSFER,UPI,CASH,OTHER]',
                ],

                'errors' => [
                    'required' =>
                    'Please select the payment source.',

                    'in_list' =>
                    'Please select a valid payment source.',
                ],
            ],

            'amount' => [
                'label' =>
                'Amount',

                'rules' => [
                    'required',
                    'decimal',
                    'greater_than[0]',
                    'less_than_equal_to[999999.99]',
                ],

                'errors' => [
                    'required' =>
                    'Please enter the amount received.',

                    'decimal' =>
                    'Please enter a valid payment amount.',

                    'greater_than' =>
                    'Payment amount must be greater than zero.',

                    'less_than_equal_to' =>
                    'The payment amount is invalid.',
                ],
            ],

            'transaction_reference' => [
                'label' =>
                'Transaction / Reference Number',

                'rules' => [
                    'permit_empty',
                    'max_length[120]',
                ],

                'errors' => [
                    'max_length' =>
                    'Transaction reference cannot exceed 120 characters.',
                ],
            ],

            'payment_note' => [
                'label' =>
                'Payment Note',

                'rules' => [
                    'permit_empty',
                    'max_length[500]',
                ],

                'errors' => [
                    'max_length' =>
                    'Payment note cannot exceed 500 characters.',
                ],
            ],
        ];
    }
}

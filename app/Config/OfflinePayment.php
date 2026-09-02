<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

final class OfflinePayment extends BaseConfig
{
    public string $accountName = '';

    public string $bankName = '';

    public string $accountNumber = '';

    public string $ifscCode = '';

    public string $upiId = '';

    /**
     * @var list<string>
     */
    public array $whatsappNumbers = [];

    public function __construct()
    {
        parent::__construct();

        $this->accountName =
            trim(
                (string) env(
                    'offlinePayment.accountName',
                    ''
                )
            );

        $this->bankName =
            trim(
                (string) env(
                    'offlinePayment.bankName',
                    ''
                )
            );

        $this->accountNumber =
            trim(
                (string) env(
                    'offlinePayment.accountNumber',
                    ''
                )
            );

        $this->ifscCode =
            trim(
                (string) env(
                    'offlinePayment.ifscCode',
                    ''
                )
            );

        $this->upiId =
            trim(
                (string) env(
                    'offlinePayment.upiId',
                    ''
                )
            );

        $numbers = explode(
            ',',
            (string) env(
                'offlinePayment.whatsappNumbers',
                ''
            )
        );

        $this->whatsappNumbers =
            array_values(
                array_filter(
                    array_map(
                        static fn(string $number): string =>
                        trim($number),
                        $numbers
                    ),
                    static fn(string $number): bool =>
                    $number !== ''
                )
            );
    }
}

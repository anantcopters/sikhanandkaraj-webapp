<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;

final class CouponModel extends Model
{
    public const DISCOUNT_PERCENTAGE =
    'PERCENTAGE';

    public const DISCOUNT_FLAT =
    'FLAT';

    public const ELIGIBILITY_ALL =
    'ALL';

    public const ELIGIBILITY_SELECTED =
    'SELECTED';

    public const ELIGIBILITY_GENDER =
    'GENDER';

    public const GENDER_MALE =
    'MALE';

    public const GENDER_FEMALE =
    'FEMALE';

    protected $table =
    'coupons';

    protected $primaryKey =
    'id';

    protected $returnType =
    'array';

    protected $useTimestamps =
    true;

    protected $allowedFields = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'eligibility_type',
        'eligible_gender',
        'usage_limit',
        'starts_at',
        'expires_at',
        'country_id',
        'state_id',
        'city_id',
        'is_active',
        'created_by_admin_user_id',
        'updated_by_admin_user_id',
    ];

    public function __construct(
        ?ConnectionInterface $db = null
    ) {
        parent::__construct(
            $db
        );
    }

    public function findByCode(
        string $code
    ): ?array {
        $code =
            mb_strtoupper(
                trim($code)
            );

        if ($code === '') {
            return null;
        }

        $row =
            $this
            ->where(
                'code',
                $code
            )
            ->first();

        return is_array($row)
            ? $row
            : null;
    }

    public function lockById(
        int $couponId
    ): ?array {
        if ($couponId <= 0) {
            return null;
        }

        $query =
            $this->db->query(
                'SELECT *
                 FROM coupons
                 WHERE id = ?
                 LIMIT 1
                 FOR UPDATE',
                [
                    $couponId,
                ]
            );

        $row =
            $query->getRowArray();

        return is_array($row)
            ? $row
            : null;
    }
}

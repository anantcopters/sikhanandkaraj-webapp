<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Provides active physical-status master values.
 */
final class MasterPhysicalStatusModel extends AbstractActiveMasterModel
{
    protected $table = 'master_physical_statuses';
}

<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class EmailVerificationTokenModel extends Model
{
    protected $table = 'email_verification_tokens';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'user_id',
        'user_contact_id',
        'token_hash',
        'expires_at',
        'used_at',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * @return array<string, mixed>|null
     */
    public function findUsableToken(
        string $tokenHash
    ): ?array {
        $record = $this
            ->where('token_hash', $tokenHash)
            ->where('used_at', null)
            ->where(
                'expires_at >=',
                date('Y-m-d H:i:s')
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }

    public function invalidateForContact(
        int $contactId
    ): void {
        $this
            ->where('user_contact_id', $contactId)
            ->where('used_at', null)
            ->set([
                'used_at' => date('Y-m-d H:i:s'),
            ])
            ->update();
    }
}

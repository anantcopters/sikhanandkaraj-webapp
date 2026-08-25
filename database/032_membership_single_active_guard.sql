/*
|--------------------------------------------------------------------------
| Membership - one ACTIVE membership per member
|--------------------------------------------------------------------------
|
| Application activation is already serialized using row-level locking.
|
| This partial unique index is the final database invariant protecting
| against:
|
| - duplicate payment callbacks;
| - concurrent administrative activation;
| - future code accidentally bypassing MembershipPurchaseService.
|
| Historical EXPIRED / REPLACED / CANCELLED memberships remain unlimited.
|--------------------------------------------------------------------------
*/

CREATE UNIQUE INDEX IF NOT EXISTS
    uq_member_memberships_one_active_per_user
ON member_memberships (user_id)
WHERE status = 'ACTIVE';
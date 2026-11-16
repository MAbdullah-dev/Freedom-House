<?php

namespace App\Services\Microsoft;

use App\Models\Role;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MicrosoftIdentityStore
{
    public const AUTHENTICATED = 'authenticated';

    public const LINKED = 'linked';

    public const COLLISION = 'collision';

    public const DUPLICATE = 'duplicate';

    public const REASSIGNMENT = 'reassignment';

    public const MISSING_ROLE = 'missing_role';

    public const NO_EMAIL = 'no_email';

    public const LEGACY_UNCONFIRMED = 'legacy_unconfirmed';

    public static function identityKey(string $tenantId, string $objectId): string
    {
        return strtolower($tenantId).':'.strtolower($objectId);
    }

    public static function legacyKey(string $objectId): string
    {
        return 'legacy:'.strtolower($objectId);
    }

    public function authenticate(
        string $tenantId,
        string $objectId,
        ?string $email,
        ?string $refreshToken,
        ?CarbonInterface $expiresAt
    ): array {
        try {
            return DB::transaction(function () use ($tenantId, $objectId, $email, $refreshToken, $expiresAt) {
                $key = self::identityKey($tenantId, $objectId);
                $existing = User::query()->where('microsoft_identity_key', $key)->lockForUpdate()->first();
                if ($existing !== null) {
                    $this->storeTokens($existing, $refreshToken, $expiresAt);

                    return ['status' => self::AUTHENTICATED, 'user' => $existing];
                }

                $legacy = $this->legacyUsers($objectId);
                if ($legacy->count() > 1) {
                    return ['status' => self::DUPLICATE, 'user' => null];
                }
                if ($legacy->count() === 1) {
                    return ['status' => self::LEGACY_UNCONFIRMED, 'user' => null];
                }

                if ($email !== null && User::query()->where('email', $email)->exists()) {
                    return ['status' => self::COLLISION, 'user' => null];
                }

                if ($email === null) {
                    return ['status' => self::NO_EMAIL, 'user' => null];
                }

                $role = Role::findByName(Role::LEARNER);
                if ($role === null) {
                    return ['status' => self::MISSING_ROLE, 'user' => null];
                }

                $user = User::query()->create([
                    'email' => $email,
                    'password' => Hash::make(Str::random(40)),
                    'role_id' => $role->id,
                    'microsoft_id' => strtolower($objectId),
                    'microsoft_tenant_id' => strtolower($tenantId),
                    'microsoft_identity_key' => $key,
                    'microsoft_account' => true,
                    'refresh_token' => $refreshToken,
                    'token_expires_at' => $expiresAt,
                    'timezone' => 'UTC',
                ]);

                return ['status' => self::AUTHENTICATED, 'user' => $user];
            }, 3);
        } catch (UniqueConstraintViolationException) {
            if ($email !== null && User::query()->where('email', $email)->exists()) {
                return ['status' => self::COLLISION, 'user' => null];
            }

            return ['status' => self::DUPLICATE, 'user' => null];
        }
    }

    public function link(
        int $userId,
        string $tenantId,
        string $objectId,
        ?string $refreshToken,
        ?CarbonInterface $expiresAt
    ): string {
        try {
            return DB::transaction(function () use ($userId, $tenantId, $objectId, $refreshToken, $expiresAt) {
                $key = self::identityKey($tenantId, $objectId);
                $holder = User::query()->where('microsoft_identity_key', $key)->first();
                $ids = array_values(array_unique(array_filter([$holder?->id, $userId])));
                sort($ids);
                $locked = User::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $user = $locked->get($userId);
                if ($user === null) {
                    return self::DUPLICATE;
                }

                $holder = $locked->first(fn (User $row) => $row->microsoft_identity_key === $key);
                if ($holder !== null && $holder->id !== $user->id) {
                    return self::DUPLICATE;
                }

                $legacy = self::legacyKey($objectId);
                if ($this->legacyUsers($objectId)->where('id', '!=', $user->id)->isNotEmpty()) {
                    return self::DUPLICATE;
                }

                $current = $user->microsoft_identity_key;
                if ($current !== null && $current !== $key && $current !== $legacy) {
                    return self::REASSIGNMENT;
                }

                $user->microsoft_identity_key = $key;
                $user->microsoft_tenant_id = strtolower($tenantId);
                $user->microsoft_id = strtolower($objectId);
                $user->microsoft_account = true;
                $this->storeTokens($user, $refreshToken, $expiresAt);

                return self::LINKED;
            }, 3);
        } catch (UniqueConstraintViolationException) {
            return self::DUPLICATE;
        }
    }

    private function legacyUsers(string $objectId)
    {
        $objectId = strtolower($objectId);

        return User::query()
            ->where(function ($query) use ($objectId) {
                $query->where('microsoft_identity_key', self::legacyKey($objectId))
                    ->orWhere(function ($legacy) use ($objectId) {
                        $legacy->where('microsoft_id', $objectId)
                            ->whereNull('microsoft_tenant_id')
                            ->whereNull('microsoft_identity_key');
                    });
            })
            ->lockForUpdate()
            ->get();
    }

    private function storeTokens(User $user, ?string $refreshToken, ?CarbonInterface $expiresAt): void
    {
        if (is_string($refreshToken) && $refreshToken !== '') {
            $user->refresh_token = $refreshToken;
        }
        if ($expiresAt !== null) {
            $user->token_expires_at = $expiresAt;
        }
        $user->save();
    }
}

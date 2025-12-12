<?php

declare(strict_types=1);

namespace Src\Infrastructure\Repositories;

use App\Models\User as EloquentUser;
use Illuminate\Support\Facades\DB;
use Src\Domain\Entities\User;
use Src\Domain\Repositories\UserRepositoryInterface;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\UserId;
use Src\Domain\ValueObjects\UserName;

final class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(UserId $id): ?User
    {
        $eloquentUser = EloquentUser::find($id->getValue());

        if ($eloquentUser === null) {
            return null;
        }

        return $this->toDomainEntity($eloquentUser);
    }

    public function findByEmail(Email $email): ?User
    {
        $eloquentUser = EloquentUser::where('email', $email->getValue())->first();

        if ($eloquentUser === null) {
            return null;
        }

        return $this->toDomainEntity($eloquentUser);
    }

    /**
     * @return array<User>
     */
    public function findAll(int $page = 1, int $perPage = 15): array
    {
        $eloquentUsers = EloquentUser::skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $eloquentUsers->map(fn(EloquentUser $user) => $this->toDomainEntity($user))->toArray();
    }

    public function save(User $user): User
    {
        $eloquentUser = EloquentUser::updateOrCreate(
            ['id' => $user->getId()->getValue()],
            [
                'name' => $user->getName()->getValue(),
                'email' => $user->getEmail()->getValue(),
                'password' => $user->getPassword(),
                'email_verified_at' => $user->getEmailVerifiedAt(),
                'created_at' => $user->getCreatedAt(),
                'updated_at' => $user->getUpdatedAt(),
            ]
        );

        return $this->toDomainEntity($eloquentUser);
    }

    public function delete(UserId $id): void
    {
        EloquentUser::destroy($id->getValue());
    }

    public function getNextId(): int
    {
        $lastId = DB::table('users')->max('id');

        return ($lastId ?? 0) + 1;
    }

    private function toDomainEntity(EloquentUser $eloquentUser): User
    {
        return new User(
            id: new UserId($eloquentUser->id),
            name: new UserName($eloquentUser->name),
            email: new Email($eloquentUser->email),
            password: $eloquentUser->password,
            emailVerifiedAt: $eloquentUser->email_verified_at ? new \DateTimeImmutable($eloquentUser->email_verified_at) : null,
            createdAt: $eloquentUser->created_at ? new \DateTimeImmutable($eloquentUser->created_at) : null,
            updatedAt: $eloquentUser->updated_at ? new \DateTimeImmutable($eloquentUser->updated_at) : null,
        );
    }
}

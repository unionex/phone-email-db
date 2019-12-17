<?php
namespace App\Modules\Acl\Rules\User;

use App\Helpers\ArrayHelper;
use App\Models\User;
use App\Modules\Acl\Rules\RuleInterface;

class UpdateRule implements RuleInterface
{
    /**
     * @param User $user
     * @param array|null $params
     * @return bool
     * @throws \Exception
     */
    public function check(User $user, $params = null)
    {
        if ($user->hasRole(User::R_ADMIN)) {
            return true;
        }

        if (!ArrayHelper::keyExists('user', $params)) {
            throw new \Exception("User is required");
        }

        /** @var User $editUser */
        $editUser = $params['user'];

        if (!$editUser instanceof User) {
            throw new \Exception("User must be instance of " . User::class);
        }

        if ($user->id == $editUser->id) {
            return true;
        }

        return false;
    }
}
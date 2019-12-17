<?php
namespace App\Modules\Acl\Rules\User;

use App\Models\User;
use App\Modules\Acl\Rules\RuleInterface;

class ViewRule implements RuleInterface
{
    /**
     * @param User $user
     * @param array|null $params
     * @return bool
     */
    public function check(User $user, $params = null)
    {
        if ($user->hasRole(User::R_ADMIN)) {
            return true;
        }

        /** @var User $curUser */
        $curUser = $params['user'];

        if ($user->id == $curUser->id) {
            return true;
        }

        return false;
    }
}
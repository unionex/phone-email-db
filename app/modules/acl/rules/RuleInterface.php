<?php
namespace App\Modules\Acl\Rules;

use App\Models\User;

interface RuleInterface
{
    /**
     * @param User $user
     * @param array|null $params
     * @return bool
     */
    public function check(User $user, $params = null);
}
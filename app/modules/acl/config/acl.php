<?php

use App\Helpers\ArrayHelper;
use App\Models\User;
use App\Modules\Acl\Rules\RuleInterface;
use Phalcon\Acl;
use Phalcon\Acl\Adapter\Memory as AclList;
use Phalcon\Config\Adapter\Yaml;

$aclConfig = new Yaml(
    APP_DIR . "/app/config/acl.yaml"
);

$list = $aclConfig->toArray();

$roles = [];

foreach ($list['roles'] as $role) {
    $roles[$role['name']] = $role;
}

$resources = [];

foreach ($list['permissions'] as $resource => $permissions) {
    $resources[$resource] = array_keys($permissions);
}

$allows = [];

foreach ($list['permissions'] as $resource => $permissions) {
    foreach ($permissions as $allow => $info) {
        $allows[] = array_merge($info, [
            'resource' => $resource,
            'allow' => $allow
        ]);
    }
}

$acl = new AclList();

$acl->setDefaultAction(Acl::DENY);

foreach ($roles as $role) {
    $acl->addRole(new Acl\Role($role['name'], $role['description']));
}

foreach ($resources as $name => $allow) {
    $acl->addResource(new Acl\Resource($name), $allow);
}

foreach ($allows as $allow) {
    $roles = $allow['roles'] ?? null;

    foreach ($roles as $role) {
        if (ArrayHelper::keyExists('rule', $allow)) {
            $rule = $allow['rule'];
            $acl->allow(
                $role,
                $allow['resource'],
                $allow['allow'],
                function (User $user, $params = null) use ($allow) {
                    /** @var RuleInterface $ruleInstance */
                    $ruleInstance = new $allow['rule'];

                    return $ruleInstance->check($user, $params);
                }
            );
        } else {
            $acl->allow($role, $allow['resource'], $allow['allow']);
        }
    }
}

return $acl;
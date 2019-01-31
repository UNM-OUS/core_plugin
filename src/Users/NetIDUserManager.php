<?php
namespace Digraph\Modules\ous_digraph_module\Users;

use Digraph\Users\Managers\Null\NullUserManager;

class NetIDUserManager extends NullUserManager
{
    const USERCLASS = NetIDUser::class;
}

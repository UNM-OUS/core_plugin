<?php
namespace Digraph\Modules\ous_digraph_module\Users;

use Digraph\Users\Managers\Null\NullUser;

class NetIDUser extends NullUser
{
    public function name(string $set = null) : string
    {
        return $this->identifier();
    }

    public function email() : ?string
    {
        return $this->identifier().'@unm.edu';
    }
}

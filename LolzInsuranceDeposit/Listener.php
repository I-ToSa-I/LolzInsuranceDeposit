<?php

namespace DCS\LolzInsuranceDeposit;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Manager;
use XF\Mvc\Entity\Structure;

class Listener
{
    public static function userEntityStructure(Manager $em, Structure &$structure)
    {
        $structure->columns['dcs_lolz_deposit_amount'] = ['type' => Entity::INT, 'default' => 0];
    }
}
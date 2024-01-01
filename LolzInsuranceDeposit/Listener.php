<?php

namespace DCS\LolzInsuranceDeposit;

use XF\Mvc\Entity\Entity;

class Listener
{
    public static function userEntityStructure(\XF\Mvc\Entity\Manager $em, \XF\Mvc\Entity\Structure &$structure)
    {

        $structure->columns['dcs_lolz_deposit_amount'] = ['type' => Entity::INT, 'default' => 0];

    }
}
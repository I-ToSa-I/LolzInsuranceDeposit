<?php

namespace DCS\LolzInsuranceDeposit\Option;

use XF;
use XF\Entity\Option;
use XF\Option\AbstractOption;

class MinDep extends AbstractOption
{
    /**
     * @param array  $value
     * @param Option $option
     *
     * @return bool
     */
    public static function verifyOption(array &$value, Option $option): bool
    {
        if ($value['minDep'] <= 0)
        {
            $option->error(XF::phrase('dcs_lid_err_please_enter_value_greater_than_zero'), 'minDep');

            return false;
        }

        return true;
    }
}
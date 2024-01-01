<?php

namespace DCS\LolzInsuranceDeposit\Alert;

use XF\Alert\AbstractHandler;

class TakeOffRequest extends AbstractHandler
{
    /**
     * @return array
     */
    public function getEntityWith()
    {
        return ['User'];
    }
}
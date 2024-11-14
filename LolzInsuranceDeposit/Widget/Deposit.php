<?php

namespace DCS\LolzInsuranceDeposit\Widget;

use XF;
use XF\Entity\User;
use XF\Http\Request;
use XF\Widget\AbstractWidget;
use XF\Widget\WidgetRenderer;

class Deposit extends AbstractWidget
{

    /**
     * @return string|WidgetRenderer
     */
    public function render()
    {
        $visitor = XF::visitor();
        if (!$visitor->canViewMemberList())
        {
            return '';
        }

        $hasUserContext = (
            isset($this->contextParams['user'])
            && $this->contextParams['user'] instanceof User
        );
        $user = $hasUserContext ? $this->contextParams['user'] : $visitor;
        $options = XF::options();
        return $this->renderer('dcs_deposit_widget', [
            'user'              => $user,
            'deposit_amount'    => number_format($user->dcs_lolz_deposit_amount, 0, ',', ' '),
            'suffix'            => $options->dcs_lid_suffixDepositSum,
            'users'             => $options->dcs_lid_usersLzt,
        ]);
    }

    public function verifyOptions(Request $request, array &$options, &$error = null)
    {
        $options = $request->filter([
            'limit' => 'uint'
        ]);

        return true;
    }
}
<?php

namespace DCS\LolzInsuranceDeposit\Widget;

use XF;
use XF\Entity\User;
use XF\Repository\UserFollow;
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
            && $this->contextParams['user'] instanceof \XF\Entity\User
        );

        $user = $hasUserContext ? $this->contextParams['user'] : $visitor;

        $db = XF::db();


        $amount = $db->fetchOne("SELECT dcs_lolz_deposit_amount FROM xf_user WHERE user_id = ?", $user->user_id);

        return $this->renderer('dcs_deposit_widget', [
            'user'              => $user,
            'deposit_amount'    => number_format($amount, 0, ',', ' '),
            'suffix'            => XF::options()->dcs_lid_suffixDepositSum,
            'users'             => XF::options()->dcs_lid_usersLzt,
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
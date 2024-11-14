<?php
namespace DCS\LolzInsuranceDeposit\MemberStat;

use XF;
use XF\Entity\MemberStat;
use XF\Finder\User;

class MostDeposit
{
    public static function getDepositUsers(MemberStat $memberStat, User $finder)
    {
        $finder->order('dcs_lolz_deposit_amount', 'DESC');
        $users = $finder->where('dcs_lolz_deposit_amount', '>', 0)->limit($memberStat->user_limit)->fetch();

        return $users->pluck(function (XF\Entity\User $user) {
            return [$user->user_id, XF::language()->numberFormat($user->dcs_lolz_deposit_amount)];
        });
    }
}
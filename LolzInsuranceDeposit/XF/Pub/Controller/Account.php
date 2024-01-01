<?php

namespace DCS\LolzInsuranceDeposit\XF\Pub\Controller;

use DCS\LolzInsuranceDeposit\Entity\TakeOffRequest;
use XF;

class Account extends XFCP_Account
{

    public function actionDeposit() {

        $visitor = \XF::visitor();

        $paymentRepo = $this->repository('XF:Payment');
        $options = $this->options();

        if (in_array($visitor->user_id, $options->dcs_lid_usersLzt))
        {
            return $this->error(XF::phrase("dcs_you_cannot_deposit_withdraw_from_deposit_because_deposit_tied_to_lzt"));
        }

        $allowedProfiles = $options->dcs_lid_paymentProfiles;
        $minDep = $options->dcs_lid_minDep;
        $profiles = $paymentRepo->findPaymentProfilesForList()->fetch();
        foreach ($profiles as $profile)
        {
            if ($profile->active && in_array($profile->payment_profile_id, $allowedProfiles))
            {
                $availableProfiles[] = [
                    'id'    => $profile->payment_profile_id,
                    'title' => $profile->display_title ?: $profile->title
                ];
            }

        }

        $viewParams = [
            'user'          => $visitor,
            'profiles'      => $availableProfiles,
            'minDep'        => $minDep['minDep'],
            'suffix'        => $options->dcs_lid_suffixDepositSum,
            'currency'      => $minDep['minDep_currency']
        ];

        return $this->view('DCS/LolzInsuranceDeposit:Deposit', 'deposit_view', $viewParams);

    }

    public function actionDepositPurchaseComplete()
    {
        return $this->view('DCS/LolzInsuranceDeposit:Deposit', 'dcs_account_deposit_purchase_complete', []);
    }

    public  function actionDeposittakeoff()
    {
        $visitor = XF::visitor();
        $options = XF::options();

        if ($visitor->dcs_lolz_deposit_amount <= 0)
        {
            return $this->error(XF::phrase("dcs_you_cannot_withdraw_from_deposit_because_balance_is_zero"));
        }

        if (in_array($visitor->user_id, $options->dcs_lid_usersLzt))
        {
            return $this->error(XF::phrase("dcs_you_cannot_deposit_withdraw_from_deposit_because_deposit_tied_to_lzt"));
        }


        return $this->view('DCS/LolzInsuranceDeposit:Deposit', 'dcs_takeoff_create_yes_no', []);
    }

    public  function actionDeposittakeoffCreate()
    {


        $visitor = XF::visitor();
        $options = XF::options();
        $amount = $visitor->dcs_lolz_deposit_amount;

        if ($visitor->dcs_lolz_deposit_amount <= 0)
        {
            return $this->error(XF::phrase("dcs_you_cannot_withdraw_from_deposit_because_balance_is_zero"));
        }


        if (in_array($visitor->user_id, $options->dcs_lid_usersLzt))
        {
            return $this->error(XF::phrase("dcs_you_cannot_deposit_withdraw_from_deposit_because_deposit_tied_to_lzt"));
        }

        /** @var TakeOffRequest $takeOffRequest */
        $takeOffRequest = $this->em()->create('DCS\LolzInsuranceDeposit:TakeOffRequest');
        $takeOffRequest->bulkSet([
            'user_id'              => $visitor->user_id,
            'request_amount'       => $amount
        ]);
        $takeOffRequest->save();

        return $this->view('DCS/LolzInsuranceDeposit:Deposit', 'dcs_takeoff_create', []);
    }

}
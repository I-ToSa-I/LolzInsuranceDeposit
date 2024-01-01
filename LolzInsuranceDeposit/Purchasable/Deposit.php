<?php

namespace DCS\LolzInsuranceDeposit\Purchasable;

use DCS\LolzInsuranceDeposit\Entity\DepositLog;
use DCS\LolzInsuranceDeposit\Entity\TakeOffRequest;
use XF;
use XF\Entity\PaymentProfile;
use XF\Entity\User;
use XF\Http\Request;
use XF\Mvc\Entity\ArrayCollection;
use XF\Payment\CallbackState;
use XF\Phrase;
use XF\Purchasable\AbstractPurchasable;
use XF\Purchasable\Purchase;

class Deposit extends AbstractPurchasable
{
    public function getTitle(): string
    {
        return "Пополнение депозита";
    }

    /**
     * @param PaymentProfile $paymentProfile
     * @param                $purchasable
     * @param User           $purchaser
     *
     * @return Purchase
     */
    public function getPurchaseFromRequest(Request $request, User $purchaser, &$error = null)
    {
        $profileId = $request->filter("payment_profile_id", 'uint');
        $paymentAmount = $request->filter("amount", "unum");
        $min = XF::options()->dcs_lid_minDep;
        if (empty($paymentAmount) || $paymentAmount < $min['minDep']) {
            $error = \XF::phrase('please_enter_number_that_is_at_least_x', ['min' => $min['minDep']]);

            return false;
        }

        /** @var PaymentProfile $paymentProfile */
        $paymentProfile = XF::em()->find('XF:PaymentProfile', $profileId);
        if (!$paymentProfile || !$paymentProfile->active)
        {
            $error = XF::phrase('please_choose_valid_payment_profile_to_continue_with_your_purchase');

            return false;
        }

        $purchasable = new ArrayCollection([
            'amount' => $paymentAmount,
            'title'  => "Пополнение депозита",
            'payment_profile_id' => $profileId,
        ]);

        return $this->getPurchaseObject($paymentProfile, $purchasable, $purchaser);
    }

    public function getPurchasableFromExtraData(array $extraData)
    {
        $output = [
            'amount' => ''
        ];

        $paymentAmount = $extraData['amount'];

        $output['amount'] = $paymentAmount;

        return $output;
    }

    public function getPurchaseFromExtraData(array $extraData, \XF\Entity\PaymentProfile $paymentProfile, \XF\Entity\User $purchaser, &$error = null)
    {
        $purchasable = $this->getPurchasableFromExtraData($extraData);

        $paymentAmount = $purchasable['amount'] ?: null;


        $min = XF::options()->dcs_lid_minDep;
        if (empty($paymentAmount) || $paymentAmount < $min['minDep']) {
            $error = \XF::phrase('please_enter_number_that_is_at_least_x', ['min' => $min['minDep']]);

            return false;
        }

        return $this->getPurchaseObject($paymentProfile, $purchasable, $purchaser);
    }

    /**
     * @param \XF\Entity\PaymentProfile $paymentProfile
     * @param ArrayCollection           $purchasable
     * @param \XF\Entity\User           $purchaser
     *
     * @return Purchase
     */
    public function getPurchaseObject(\XF\Entity\PaymentProfile $paymentProfile, $purchasable, \XF\Entity\User $purchaser)
    {
        $purchase = new Purchase();

        $paymentAmount = $purchasable['amount'];

        $purchase->title = "Пополнение депозита";
        $purchase->currency = $min = XF::options()->dcs_lid_minDep['minDep_currency'];
        $purchase->cost = $paymentAmount;
        $purchase->purchaser = $purchaser;
        $purchase->paymentProfile = $paymentProfile;
        $purchase->purchasableTypeId = $this->purchasableTypeId;
        $purchase->purchasableId = "Пополнение депозита";
        $purchase->purchasableTitle = "Пополнение депозита";
        $purchase->extraData = [
            'amount' => $paymentAmount,
        ];

        $router = \XF::app()->router('public');

        $purchase->returnUrl = $router->buildLink('canonical:account/deposit/purchase-complete');
        $purchase->cancelUrl = $router->buildLink('canonical:account/deposit');

        return $purchase;
    }

    public function completePurchase(CallbackState $state)
    {
        if ($state->legacy) {
            return;
        }

        $purchaseRequest = $state->getPurchaseRequest();
        $paymentResult = $state->paymentResult;
        $purchaser = $state->getPurchaser();


        if ($paymentResult == CallbackState::PAYMENT_RECEIVED) {
            $db = XF::db();


            $db->query("UPDATE xf_user SET dcs_lolz_deposit_amount = ? WHERE user_id = ?",
                [$purchaser->dcs_lolz_deposit_amount + $purchaseRequest->cost_amount, $purchaser->user_id]);

            /** @var DepositLog $deposit_log */
            $deposit_log = XF::em()->create("DCS\LolzInsuranceDeposit:DepositLog");
            $deposit_log->bulkSet([
               'user_id'    => $purchaseRequest->user_id,
               'amount'     => $purchaseRequest->cost_amount,
               'type'       => 1
            ]);
            $deposit_log->save();

            $state->logType = 'payment';
            $state->logMessage = 'Новое пополнение депозита.';
        }

        if ($purchaseRequest)
        {
            $extraData = $purchaseRequest->extra_data;
            $extraData['purchase_request_key'] = $state->requestKey;
            $purchaseRequest->extra_data = $extraData;
            $purchaseRequest->save();
        }
    }

    public function reversePurchase(CallbackState $state): void
    {
    }

    public function getPurchasablesByProfileId($profileId): array
    {
        return [];
    }
}
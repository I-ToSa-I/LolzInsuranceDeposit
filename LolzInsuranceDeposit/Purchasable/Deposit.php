<?php

namespace DCS\LolzInsuranceDeposit\Purchasable;

use XF;
use XF\Entity\PaymentProfile;
use XF\Entity\User;
use XF\Http\Request;
use XF\Payment\CallbackState;
use XF\Purchasable\AbstractPurchasable;
use XF\Purchasable\Purchase;

class Deposit extends AbstractPurchasable
{
    public function getTitle(): string
    {
        return XF::phrase("deposit_replenish");
    }

    public function getPurchaseFromRequest(Request $request, User $purchaser, &$error = null)
    {
        $profileId = $request->filter("payment_profile_id", 'uint');
        $paymentAmount = $request->filter("amount", "unum");
        $min = XF::options()->dcs_lid_minDep;
        if (empty($paymentAmount) || $paymentAmount < $min['minDep']) {
            $error = XF::phrase('please_enter_number_that_is_at_least_x', ['min' => $min['minDep']]);
            return false;
        }

        /** @var PaymentProfile $paymentProfile */
        $paymentProfile = XF::em()->find('XF:PaymentProfile', $profileId);
        if (!$paymentProfile || !$paymentProfile->active)
        {
            $error = XF::phrase('please_choose_valid_payment_profile_to_continue_with_your_purchase');
            return false;
        }

        $purchasable = [
            'amount' => $paymentAmount,
            'title'  => $this->getTitle(),
            'payment_profile_id' => $profileId,
        ];

        return $this->getPurchaseObject($paymentProfile, $purchasable, $purchaser);
    }

    public function getPurchasableFromExtraData(array $extraData)
    {
        $output = array();
        $output['amount'] = $extraData['amount'];
        return $output;
    }

    public function getPurchaseFromExtraData(array $extraData, PaymentProfile $paymentProfile, User $purchaser, &$error = null)
    {
        $purchasable = $this->getPurchasableFromExtraData($extraData);
        $paymentAmount = $purchasable['amount'] ?: null;
        $min = XF::options()->dcs_lid_minDep;

        if (empty($paymentAmount) || $paymentAmount < $min['minDep']) {
            $error = XF::phrase('please_enter_number_that_is_at_least_x', ['min' => $min['minDep']]);
            return false;
        }

        return $this->getPurchaseObject($paymentProfile, $purchasable, $purchaser);
    }

    public function getPurchaseObject(PaymentProfile $paymentProfile, $purchasable, User $purchaser)
    {
        $purchase = new Purchase();
        $paymentAmount = $purchasable['amount'];
        $purchase->title = $this->getTitle();
        $purchase->currency = XF::options()->dcs_lid_minDep['minDep_currency'];
        $purchase->cost = $paymentAmount;
        $purchase->purchaser = $purchaser;
        $purchase->paymentProfile = $paymentProfile;
        $purchase->purchasableTypeId = $this->purchasableTypeId;
        $purchase->purchasableId = XF::generateRandomString(10);;
        $purchase->purchasableTitle = $this->getTitle();
        $purchase->extraData = [
            'amount' => $paymentAmount
        ];

        $router = XF::app()->router('public');

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
            $purchaser->dcs_lolz_deposit_amount = $purchaser->dcs_lolz_deposit_amount + $purchaseRequest->cost_amount;
            $purchaser->save();

            $deposit_log = XF::em()->create("DCS\LolzInsuranceDeposit:DepositLog");
            $deposit_log->bulkSet([
               'user_id'    => $purchaseRequest->user_id,
               'amount'     => $purchaseRequest->cost_amount,
               'type'       => 1
            ]);
            $deposit_log->save();

            $state->logType = 'payment';
            $state->logMessage = XF::phrase("dcs_new_deposit_replenish");
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
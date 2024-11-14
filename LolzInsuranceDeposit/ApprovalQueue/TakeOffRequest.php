<?php

namespace DCS\LolzInsuranceDeposit\ApprovalQueue;

use DCS\LolzInsuranceDeposit\Entity\DepositLog;
use XF;
use XF\ApprovalQueue\AbstractHandler;
use XF\Mvc\Entity\Entity;
use XF\Repository\UserAlert;

class TakeOffRequest extends AbstractHandler
{
    /**
     * @param Entity $content
     * @param null   $error
     *
     * @return bool
     */
    protected function canActionContent(Entity $content, &$error = null)
    {
        return XF::visitor()->is_super_admin;
    }

    protected function canViewContent(Entity $content, &$error = null)
    {
        return XF::visitor()->is_super_admin;
    }


    public function actionApprove(\DCS\LolzInsuranceDeposit\Entity\TakeOffRequest $request)
    {
        $req = XF::finder('DCS\LolzInsuranceDeposit:TakeOffRequest')->where("takeoff_request_id", '=',
            $request->takeoff_request_id)->fetchOne();
        $req->fastUpdate('status', 'completed');


        /** @var DepositLog $deposit_log */
        $deposit_log = XF::em()->create("DCS\LolzInsuranceDeposit:DepositLog");
        $deposit_log->bulkSet([
            'user_id'    => $request->user_id,
            'amount'     => $request->request_amount,
            'type'       => 0
        ]);
        $deposit_log->save();

        /** @var UserAlert $alertRepo */
        $alertRepo = XF::repository('XF:UserAlert');
        $request->ApprovalQueue->delete();

        $alertRepo->alertFromUser($request->User, $request->User, 'takeoff_request', $request->takeoff_request_id, 'completed');
    }


    public function actionDelete(\DCS\LolzInsuranceDeposit\Entity\TakeOffRequest $request)
    {
        $finder = XF::finder('XF:User');
        $user = $finder->where('user_id', $request->user_id)->fetchOne();

        $user->dcs_lolz_deposit_amount = $user->dcs_lolz_deposit_amount + $request->request_amount;
        $user->save();

        $req = XF::finder('DCS\LolzInsuranceDeposit:TakeOffRequest')->where("takeoff_request_id", '=',
            $request->takeoff_request_id)->fetchOne();
        $req->fastUpdate('status', 'rejected');

        /** @var UserAlert $alertRepo */
        $alertRepo = XF::repository('XF:UserAlert');
        $request->ApprovalQueue->delete();
        $alertRepo->alertFromUser($request->User, $request->User, 'takeoff_request', $request->takeoff_request_id, 'rejected');
    }
}
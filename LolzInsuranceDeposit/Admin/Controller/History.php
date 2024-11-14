<?php

namespace DCS\LolzInsuranceDeposit\Admin\Controller;

use DCS\LolzInsuranceDeposit\Entity\DepositLog;
use XF;
use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class History extends AbstractController
{
    public function actionIndex()
    {
        $historyFinder = XF::finder("DCS\LolzInsuranceDeposit:DepositLog");
        $history = $historyFinder->order('log_id', 'DESC')->fetch();
        $viewParams = [
            'history' => $history,
        ];

        return $this->view('DCS\LolzInsuranceDeposit:History', 'dcs_deposit_history_index', $viewParams);
    }

    public function actionView(ParameterBag $params)
    {
        $log_finder = XF::finder("DCS\LolzInsuranceDeposit:DepositLog");
        /** @var DepositLog $log */
        $log = $log_finder->where("log_id", $params->log_id)->fetchOne();
        /** @var XF\Entity\User  $user */
        $user = $log->User;
        $viewParams = [
            'user'      => $user,
            'log'   => $log,
        ];
        return $this->view('DCS\LolzInsuranceDeposit:History', 'dcs_deposit_history_view', $viewParams);
    }
}
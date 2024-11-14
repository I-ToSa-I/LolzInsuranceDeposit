<?php

namespace DCS\LolzInsuranceDeposit\Admin\Controller;

use XF;
use XF\Admin\Controller\AbstractController;
use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;
use XF\PrintableException;
use XF\Entity\User;

class Users extends AbstractController
{
    public function actionIndex()
    {
        $users_finder = XF::finder('XF:User');
        $users = $users_finder->where("dcs_lolz_deposit_amount", ">", 0)->order('dcs_lolz_deposit_amount', "DESC")->fetch();
        $viewParams = [
            'users' => $users
        ];
        return $this->view('DCS\LolzInsuranceDeposit:Users', 'dcs_deposit_users_index', $viewParams);
    }

    public function actionView(ParameterBag $params)
    {
        $users_finder = XF::finder('XF:User');
        /** @var XF\Entity\User  $user */
        $user = $users_finder->where("user_id", $params->user_id)->fetchOne();
        $history = XF::finder("DCS\LolzInsuranceDeposit:DepositLog")->where('user_id', $user->user_id)->fetch();
        $viewParams = [
            'user'      => $user,
            'history'   => $history
        ];
        return $this->view('DCS\LolzInsuranceDeposit:Users', 'dcs_deposit_users_view', $viewParams);
    }

    public function actionSave(ParameterBag $params)
    {
        $users_finder = XF::finder('XF:User');
        /** @var User  $user */
        $user = $users_finder->where("user_id", $params->user_id)->fetchOne();
        $form = $this->formAction();
        $input['dcs_lolz_deposit_amount'] = $this->filter("amount", 'unum');
        $form->basicEntitySave($user, $input);
        return $this->redirect($this->buildLink('dcs-deposit/users') . $this->buildLinkHash($user->user_id));
    }

    public function actionAdd()
    {
        return $this->view("DCS\LolzInsuranceDeposit:Users", "dcs_deposit_users_add", []);
    }

    public function actionAdduser()
    {
        $username = $this->filter("username", 'str');
        $amount = $this->filter("amount", 'unum');
        $add_to_history = $this->filter("add-history", 'uint');
        $users_finder = XF::finder('XF:User');

        if (empty($username))
        {
            return $this->error("1фп");
        }

        if (empty($amount))
        {
            return $this->error("2фп");
        }

        if (!empty($username) && !empty($amount))
        {
            /** @var XF\Entity\User  $user */
            $user = $users_finder->where("username", "=", $username)->fetchOne();

            if (!$user)
            {
                return $this->error("asf123");
            }

            $user->dcs_lolz_deposit_amount = $user->dcs_lolz_deposit_amount + $amount;
            $user->save();

            if ($add_to_history)
            {
                $deposit_log = $this->em()->create("DCS\LolzInsuranceDeposit:DepositLog");
                $deposit_log->bulkSet([
                    'user_id'   => $user->user_id,
                    'amount'    => $amount,
                    'type'      => 1
                ]);
                $deposit_log->save();
                return $this->redirect($this->buildLink('dcs-deposit/users'));
            }
        }
        return $this->redirect($this->buildLink('dcs-deposit/users'));
    }
}
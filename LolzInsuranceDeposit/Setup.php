<?php

declare(strict_types = 1);

namespace DCS\LolzInsuranceDeposit;

use XF;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;


class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1(): void
    {
        $this->schemaManager()->alterTable('xf_user', function(Alter $table)
        {
            $table->addColumn('dcs_lolz_deposit_amount', 'int')->setDefault(0);
        });

        $this->schemaManager()->createTable('xf_dcs_lolz_deposits_logs', function(Create $table)
        {
            $table->addColumn("log_id", 'int')->autoIncrement();
            $table->addColumn('user_id', 'int');
            $table->addColumn('amount', 'int');
            $table->addColumn('type', 'int');
            $table->addColumn("date", 'int')->setDefault(0);
        });
    }

    public function installStep2(): void
    {
        $this->db()->insert('xf_purchasable', [
            'purchasable_type_id' => 'deposit',
            'purchasable_class'   => 'DCS\\LolzInsuranceDeposit:Deposit',
            'addon_id'            => 'DCS/LolzInsuranceDeposit'
        ]);
    }

    public function installStep3(): void
    {
        $this->schemaManager()->createTable('xf_dcs_deposit_takeOff_requests', function (Create $table)
        {
            $table->addColumn('takeoff_request_id', 'int')->autoIncrement();
            $table->addColumn('user_id', 'int');
            $table->addColumn('request_amount', 'int');
            $table->addColumn('status', 'enum')->values(['created', 'rejected', 'completed']);
            $table->addColumn("creation_date", 'int')->setDefault(0);
        });
    }

    public function installStep4(): void
    {
        $this->db()->insert('xf_member_stat', [
            'member_stat_key'           => 'most_deposit_amount',
            'criteria'                  => '[]',
            'callback_class'            => '',
            'callback_method'           => '',
            'visibility_class'          => '',
            'visibility_method'         => '',
            'sort_order'                => 'dcs_lolz_deposit_amount',
            'sort_direction'            => 'desc',
            'permission_limit'          => '',
            'show_value'                => 1,
            'user_limit'                => 20,
            'display_order'             => 510,
            'addon_id'                  => 'DCS/LolzInsuranceDeposit',
            'overview_display'          => 1,
            'active'                    => 1,
            'cache_lifetime'            => 0,
            'cache_expiry'              => 0
        ]);
    }

    public function uninstallStep1()
    {
        $this->schemaManager()->dropTable('xf_dcs_deposit_takeOff_requests');
        $this->schemaManager()->dropTable('xf_dcs_lolz_deposits');

        $this->schemaManager()->alterTable('xf_user', function (Alter $table)
        {
            $table->dropColumns('deposit');
        });

        $this->db()->delete('xf_purchasable', "purchasable_type_id = 'deposit'");
        $this->db()->delete('xf_member_stat', "member_stat_key = 'most_deposit_amount'");
    }

    public function upgrade2000000Step1()
    {
        $this->schemaManager()->alterTable('xf_dcs_lolz_deposits', function (Alter $table)
        {
            $table->addColumn("log_id", 'int')->autoIncrement();
            $table->renameTo("xf_dcs_lolz_deposits_logs");
            $table->addColumn("type", 'int');
            $table->addColumn("date", 'int')->setDefault(0);
        });
    }
}
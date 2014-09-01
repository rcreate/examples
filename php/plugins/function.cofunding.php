<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     block.cofunding.php
 * Type:     block
 * Name:     cofunding
 * Purpose:  output cofunding amounts
 * -------------------------------------------------------------
 */
function smarty_function_cofunding($params, Smarty_Internal_Template $template) {
	include_once($GLOBALS['mytyInstallPath'].'/modules/crowdfunding/mvc/model/project/cofundingMgr.class.php');

	$currentSubscription = cofundingMgr::getCurrentSubscription();
	if( $currentSubscription instanceof ModuleSubscription ) {
		$moduleSettings = ModuleSettingMgr::getAll($currentSubscription);
	}
	if( count($moduleSettings) > 0 && (float)$moduleSettings['amount_total'] > 0 ) {
		$cofundingMgr = new cofundingMgr();
		$cofundingMgr->setSubscriptionId($currentSubscription->id);
		$totalCofundedAmount = $cofundingMgr->getSum();
		$template->assign('cofundedSum', (float)$totalCofundedAmount);
		$template->assign('cofundingTotal', (float)$moduleSettings['amount_total']);
	}
}
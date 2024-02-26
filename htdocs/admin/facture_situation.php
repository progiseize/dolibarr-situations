<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne					<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2012-2013  Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2014		Teddy Andreotti				<125155@supinfo.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/admin/facture.php
 *		\ingroup    facture
 *		\brief      Page to setup invoice module
 */

// Load Dolibarr environment
require '../main.inc.php';

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'errors', 'other', 'bills'));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('situationinvoicesetup', 'globalsetup'));

// Access control
if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$scandir = GETPOST('scan_dir', 'alpha');
$type = 'invoice';

$form = new Form($db);
$formSetup = new FormSetup($db);

$arrayYesNo = array(
	0 => $langs->trans('No'),
	1 => $langs->trans('Yes')
);


// INVOICE_USE_SITUATION
$arrayAvailableMethod = array(
	0 => $langs->trans('No'),
	1 => $langs->trans('Yes'),
	2 => $langs->trans('UseSituationInvoicesMethod2'),
);

// MODULE FACTURESITUATION JAMAIS INSTALLE // MODULE NON ACTIVE ET MIGRATION NON FAITE
if($conf->global->FACTURESITUATIONMIGRATION_ISDONE):
	unset($arrayAvailableMethod[1]);
endif;

$item =	$formSetup->newItem('INVOICE_USE_SITUATION');
$item->nameText = $langs->trans('UseSituationInvoices');

if ($action == 'edit'){
	$item->fieldInputOverride = $form->selectarray('INVOICE_USE_SITUATION', $arrayAvailableMethod, $conf->global->INVOICE_USE_SITUATION,0);
}

// INVOICE_USE_SITUATION_CREDIT_NOTE
$item = $formSetup->newItem('INVOICE_USE_SITUATION_CREDIT_NOTE');
$item->nameText = $langs->trans('UseSituationInvoicesCreditNote');
if ($action == 'edit'){
	$item->fieldInputOverride = $form->selectarray('INVOICE_USE_SITUATION_CREDIT_NOTE', $arrayYesNo, $conf->global->INVOICE_USE_SITUATION_CREDIT_NOTE,0);
}

// INVOICE_USE_RETAINED_WARRANTY
$arrayAvailableType = array(
	Facture::TYPE_SITUATION => $langs->trans("InvoiceSituation"),
	Facture::TYPE_STANDARD.'+'.Facture::TYPE_SITUATION => $langs->trans("InvoiceSituation").' + '.$langs->trans("InvoiceStandard"),
);


$item = $formSetup->newItem('INVOICE_USE_RETAINED_WARRANTY');
$item->nameText = $langs->trans('AllowedInvoiceForRetainedWarranty');
if ($action == 'edit') {
	$item->fieldInputOverride = $form->selectarray('INVOICE_USE_RETAINED_WARRANTY', $arrayAvailableType, $conf->global->INVOICE_USE_RETAINED_WARRANTY, 1);
}

// INVOICE_RETAINED_WARRANTY_LIMITED_TO_FINAL_SITUATION
$item = $formSetup->newItem('INVOICE_RETAINED_WARRANTY_LIMITED_TO_FINAL_SITUATION');
$item->nameText = $langs->trans('RetainedwarrantyOnlyForSituationFinal');
if ($action == 'edit') {
	$item->fieldInputOverride = $form->selectarray('INVOICE_RETAINED_WARRANTY_LIMITED_TO_FINAL_SITUATION', $arrayYesNo, $conf->global->INVOICE_RETAINED_WARRANTY_LIMITED_TO_FINAL_SITUATION,0);
}

$item = $formSetup->newItem('INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_PERCENT');
$item->nameText = $langs->trans('RetainedwarrantyDefaultPercent');
$item->fieldAttr = array(
	'type' => 'number',
	'step' => '0.01',
	'min' => 0,
	'max' => 100
);


// Conditions paiements
$item = $formSetup->newItem('INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_COND_ID');
$item->nameText = $langs->trans('PaymentConditionsShortRetainedWarranty');
$form->load_cache_conditions_paiements();
$item->fieldInputOverride = $form->getSelectConditionsPaiements($conf->global->INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_COND_ID, 'INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_COND_ID', -1, 1);


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if($action == 'confirm_migration_done' && GETPOST('confirm','alphanohtml') == 'yes'):	
	$result = dolibarr_set_const($db, 'FACTURESITUATIONMIGRATION_ISDONE', '1', 'chaine', 0, '', 0);
endif;

/*
 * View
 */

// On réattribue les bonnes valeurs après les actions
if($action != 'edit'):
	$formSetup->items['INVOICE_USE_SITUATION']->fieldOutputOverride = isset($arrayAvailableMethod[$conf->global->INVOICE_USE_SITUATION])?$arrayAvailableMethod[$conf->global->INVOICE_USE_SITUATION]:'';
	$formSetup->items['INVOICE_USE_SITUATION_CREDIT_NOTE']->fieldOutputOverride = isset($arrayYesNo[$conf->global->INVOICE_USE_SITUATION_CREDIT_NOTE])?$arrayYesNo[$conf->global->INVOICE_USE_SITUATION_CREDIT_NOTE]:'';
	$formSetup->items['INVOICE_RETAINED_WARRANTY_LIMITED_TO_FINAL_SITUATION']->fieldOutputOverride = isset($arrayYesNo[$conf->global->INVOICE_RETAINED_WARRANTY_LIMITED_TO_FINAL_SITUATION])?$arrayYesNo[$conf->global->INVOICE_RETAINED_WARRANTY_LIMITED_TO_FINAL_SITUATION]:'';
	$formSetup->items['INVOICE_USE_RETAINED_WARRANTY']->fieldOutputOverride= isset($arrayAvailableType[$conf->global->INVOICE_USE_RETAINED_WARRANTY])?$arrayAvailableType[$conf->global->INVOICE_USE_RETAINED_WARRANTY]:'';
	if (!empty($conf->global->INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_COND_ID) && isset($form->cache_conditions_paiements[$conf->global->INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_COND_ID]['label'])) {
		$formSetup->items['INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_COND_ID']->fieldOutputOverride = $form->cache_conditions_paiements[$conf->global->INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_COND_ID]['label'];
	}
endif;


// WARNING SI INVOICE_USE_SITUATION = 2 && MIGRATION NON DETECTEE
if(getDolGlobalInt('INVOICE_USE_SITUATION') == 2 && (!isset($conf->global->FACTURESITUATIONMIGRATION_ISDONE) OR !$conf->global->FACTURESITUATIONMIGRATION_ISDONE) ):
	
	$item = $formSetup->newItem('SITUATION_MIGRATION_ALERT');
	$item->nameText = '<span class="fieldrequired">';
	$item->nameText.= $langs->trans('SituationInvoiceNewMethodAlert');

	if(isModEnabled('multicompany')):
		$item->nameText.= '<br>'.$langs->trans('SituationInvoiceNewMethodAlertMulticompany');
	endif;

	$item->nameText.= '</span>';

	$link_confirm_migration = $_SERVER['PHP_SELF'].'?action=confirm_migration&token='.newtoken();

	$item->fieldInputOverride = '&nbsp;';
	$item->fieldOutputOverride = '<a class="button small smallpaddingimp" href="'.$link_confirm_migration.'">'.$langs->trans('Confirm').'</a>';
	$item->rank = 2;

	// On pousse le rang des autres items
	foreach($formSetup->items as $item_key => $item_infos):
		if(!in_array($item_key, array('INVOICE_USE_SITUATION','SITUATION_MIGRATION_ALERT'))):
			$formSetup->items[$item_key]->rank = $item_infos->rank + 1;
		endif;
	endforeach;

endif;

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$help_yrl = 'EN:Invoice_Configuration|FR:Configuration_module_facture|ES:ConfiguracionFactura';

llxHeader("", $langs->trans("BillsSetup"), $help_url);


// ACTIONS CONFIRM
if($action == 'confirm_migration'):
	echo $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('SituationInvoiceConfirmMigrationTitle'), $langs->trans('SituationInvoiceConfirmMigration'), 'confirm_migration_done', '', 0, 1);
endif;


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("BillsSetup"), $linkback, 'title_setup');

$head = invoice_admin_prepare_head();
print dol_get_fiche_head($head, 'situation', $langs->trans("InvoiceSituation"), -1, 'invoice');


print '<span class="opacitymedium">'.$langs->trans("InvoiceFirstSituationDesc").'</span><br><br>';


/*
 *  Numbering module
 */

if ($action == 'edit') {
	print $formSetup->generateOutput(true);
} else {
	print $formSetup->generateOutput();
}

if (count($formSetup->items) > 0) {
	if ($action != 'edit') {
		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
		print '</div>';
	}
} else {
	print '<br>'.$langs->trans("NothingToSetup");
}


print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();

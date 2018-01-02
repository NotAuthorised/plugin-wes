<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class wes extends eqLogic {
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */

	public static function daemon() {
		$starttime = microtime (true);
		log::add('wes','debug','cron start');
		foreach (eqLogic::byTypeAndSearhConfiguration('wes', '"type":"carte"') as $eqLogic)
		{
			$eqLogic->pull();
		}
		log::add('wes','debug','cron stop');
		$endtime = microtime (true);
		if ( $endtime - $starttime < config::byKey('temporisation_lecture', 'wes', 60, true) )
		{
			usleep(floor((config::byKey('temporisation_lecture', 'wes') + $starttime - $endtime)*1000000));
		}
	}

	public static function deamon_info() {
		$return = array();
		$return['log'] = '';
		$return['state'] = 'nok';
		$cron = cron::byClassAndFunction('wes', 'daemon');
		if (is_object($cron) && $cron->running()) {
			$return['state'] = 'ok';
		}
		$return['launchable'] = 'ok';
		return $return;
	}

	public static function deamon_start($_debug = false) {
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		$cron = cron::byClassAndFunction('wes', 'daemon');
		if (!is_object($cron)) {
			throw new Exception(__('Tâche cron introuvable', __FILE__));
		}
		$cron->run();
	}

	public static function deamon_stop() {
		$cron = cron::byClassAndFunction('wes', 'daemon');
		if (!is_object($cron)) {
			throw new Exception(__('Tâche cron introuvable', __FILE__));
		}
		$cron->halt();
	}

	public static function deamon_changeAutoMode($_mode) {
		$cron = cron::byClassAndFunction('wes', 'daemon');
		if (!is_object($cron)) {
			throw new Exception(__('Tâche cron introuvable', __FILE__));
		}
		$cron->setEnable($_mode);
		$cron->save();
	}

	public function getUrl($file, $postarg = "") {
		if ( $this->getConfiguration('type', '') == 'carte' )
		{
			$url = 'http://';
			$url .= $this->getConfiguration('ip');
			if ( $this->getConfiguration('port') != '' )
			{
				$url .= ':'.$this->getConfiguration('port');
			}
			$process = curl_init();
			curl_setopt($process, CURLOPT_URL, $url.'/'.$file);
			curl_setopt($process, CURLOPT_USERPWD, $this->getConfiguration('username') . ":" . $this->getConfiguration('password'));
			curl_setopt($process, CURLOPT_TIMEOUT, 30);
			curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
			log::add('wes','debug','Url '.$url.'/'.$file);
			if ( $postarg != "" ) {
				log::add('wes','debug','Post '.$postarg);
				curl_setopt($process, CURLOPT_POST, 1);
				curl_setopt($process, CURLOPT_POSTFIELDS, $postarg);
			}
			$return = curl_exec($process);
			curl_close($process);
			if ( $return === false )
				throw new Exception(__('Le wes ne repond pas.',__FILE__));
		}
		else
		{
			$eqLogic = eqLogic::byId(substr ($this->getLogicalId(), 0, strpos($this->getLogicalId(),"_")));
			$return = $eqLogic->getUrl($file, $postarg);
		}
		usleep (50);
		return $return;
	}

	public function preUpdate()
	{
		switch ($this->getConfiguration('type', '')) {
			case "carte":
				if ( $this->getIsEnable() )
				{
					log::add('wes','debug','get data.cgx');
					$this->xmlstatus = simplexml_load_string($this->getUrl('data.cgx'));
					if ( $this->xmlstatus === false )
						throw new Exception(__('Le wes ne repond pas.',__FILE__));
				}
				break;
			case "bouton":
				break;
			case "temperature":
				break;
			case "relai":
				break;
			case "compteur":
				break;
			case "teleinfo":
				break;
			case "pince":
				break;
			case "analogique":
				break;
			case "vswitch":
				break;
			case "variable":
				break;
		}
	}

	public function preInsert()
	{
		switch ($this->getConfiguration('type', '')) {
			case "":
			case "carte":
				$this->setConfiguration('type', 'carte');
				$this->setIsVisible(0);
				break;
			case "bouton":
				$this->setIsEnable(0);
				$this->setIsVisible(0);
				break;
			case "temperature":
				$this->setIsEnable(0);
				$this->setIsVisible(0);
				break;
			case "relai":
				$this->setIsEnable(0);
				$this->setIsVisible(0);
				break;
			case "compteur":
				$this->setIsEnable(0);
				$this->setIsVisible(0);
				break;
			case "teleinfo":
				$this->setIsEnable(0);
				$this->setIsVisible(0);
				break;
			case "pince":
				$this->setIsEnable(0);
				$this->setIsVisible(0);
				break;
			case "analogique":
				$this->setIsEnable(0);
				$this->setIsVisible(0);
				break;
			case "vswitch":
				$this->setIsEnable(0);
				$this->setIsVisible(0);
				break;
			case "variable":
				$this->setIsEnable(0);
				$this->setIsVisible(0);
				break;
		}
	}

	public function postInsert()
	{
		switch ($this->getConfiguration('type', '')) {
			case "carte":
				$cmd = $this->getCmd(null, 'status');
				if ( ! is_object($cmd) ) {
					$cmd = new wesCmd();
					$cmd->setName('Etat');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setType('info');
					$cmd->setSubType('binary');
					$cmd->setLogicalId('status');
					$cmd->setIsVisible(1);
					$cmd->setEventOnly(1);
					$cmd->save();
				}
				break;
			case "bouton":
				$state = $this->getCmd(null, 'state');
				if ( ! is_object($state) ) {
					$state = new wesCmd();
					$state->setName('Etat');
					$state->setEqLogic_id($this->getId());
					$state->setType('info');
					$state->setSubType('binary');
					$state->setLogicalId('state');
					$state->setEventOnly(1);
					$state->save();
				}
				break;
			case "temperature":
				$reel = $this->getCmd(null, 'reel');
				if ( ! is_object($reel) ) {
					$reel = new wesCmd();
					$reel->setName('Réel');
					$reel->setEqLogic_id($this->getId());
					$reel->setType('info');
					$reel->setSubType('numeric');
					$reel->setLogicalId('reel');
					$reel->setEventOnly(1);
					$reel->save();
				}
				break;
			case "relai":
				$state = $this->getCmd(null, 'state');
				if ( ! is_object($state) ) {
					$state = new wesCmd();
					$state->setName('Etat');
					$state->setEqLogic_id($this->getId());
					$state->setType('info');
					$state->setSubType('binary');
					$state->setLogicalId('state');
					$state->setEventOnly(1);
					$state->save();
				}
				$btn_on = $this->getCmd(null, 'btn_on');
				if ( ! is_object($btn_on) ) {
					$btn_on = new wesCmd();
					$btn_on->setName('On');
					$btn_on->setEqLogic_id($this->getId());
					$btn_on->setType('action');
					$btn_on->setSubType('other');
					$btn_on->setLogicalId('btn_on');
					$btn_on->setEventOnly(1);
					$btn_on->save();
				}
				$btn_off = $this->getCmd(null, 'btn_off');
				if ( ! is_object($btn_off) ) {
					$btn_off = new wesCmd();
					$btn_off->setName('Off');
					$btn_off->setEqLogic_id($this->getId());
					$btn_off->setType('action');
					$btn_off->setSubType('other');
					$btn_off->setLogicalId('btn_off');
					$btn_off->setEventOnly(1);
					$btn_off->save();
				}
				$commute = $this->getCmd(null, 'commute');
				if ( ! is_object($commute) ) {
					$commute = new wesCmd();
					$commute->setName('Commute');
					$commute->setEqLogic_id($this->getId());
					$commute->setType('action');
					$commute->setSubType('other');
					$commute->setLogicalId('commute');
					$commute->setEventOnly(1);
					$commute->save();
				}
				break;
			case "compteur":
				$nbimpulsion = $this->getCmd(null, 'nbimpulsion');
				if ( ! is_object($nbimpulsion) ) {
					$nbimpulsion = new wesCmd();
					$nbimpulsion->setName('Nombre d impulsion');
					$nbimpulsion->setEqLogic_id($this->getId());
					$nbimpulsion->setType('info');
					$nbimpulsion->setSubType('numeric');
					$nbimpulsion->setLogicalId('nbimpulsion');
					$nbimpulsion->setEventOnly(1);
					$nbimpulsion->save();
				}
				$nbimpulsionminute = $this->getCmd(null, 'nbimpulsionminute');
				if ( ! is_object($nbimpulsionminute) ) {
					$nbimpulsionminute = new wesCmd();
					$nbimpulsionminute->setName('Nombre d impulsion par minute');
					$nbimpulsionminute->setEqLogic_id($this->getId());
					$nbimpulsionminute->setType('info');
					$nbimpulsionminute->setSubType('numeric');
					$nbimpulsionminute->setLogicalId('nbimpulsionminute');
					$nbimpulsionminute->setUnite("Imp/min");
					$nbimpulsionminute->setEventOnly(1);
					$nbimpulsionminute->setConfiguration('calcul', '#brut#');
					$nbimpulsionminute->save();
				}
				break;
			case "teleinfo":
				foreach( $this->getListeDefaultCommandesTeleinfo() as $label => $data)
				{
					if ( $this->getConfiguration('tarification') == $data[4] || $data[4] == "" ) {
						$cmd = $this->getCmd(null, $label);
						if ( ! is_object($cmd) ) {
							$cmd = new wesCmd();
							$cmd->setName($data[0]);
							$cmd->setEqLogic_id($this->getId());
							$cmd->setType('info');
							$cmd->setSubType($data[1]);
							$cmd->setLogicalId($label);
							$cmd->setUnite($data[2]);
							$cmd->setIsVisible($data[3]);
							$cmd->setEventOnly(1);
							$cmd->save();
						}
					} else {
						$cmd = $this->getCmd(null, $label);
						if ( is_object($cmd) ) {
							$cmd->remove();
						}
					}
				}
				break;
			case "pince":
				$intensite = $this->getCmd(null, 'intensite');
				if ( ! is_object($intensite) ) {
					$intensite = new wesCmd();
					$intensite->setName('Intensité');
					$intensite->setEqLogic_id($this->getId());
					$intensite->setType('info');
					$intensite->setSubType('numeric');
					$intensite->setUnite("A");
					$intensite->setLogicalId('intensite');
					$intensite->setEventOnly(1);
					$intensite->save();
				}
				$puissance = $this->getCmd(null, 'puissance');
				if ( ! is_object($puissance) ) {
					$puissance = new wesCmd();
					$puissance->setName('Puissance');
					$puissance->setEqLogic_id($this->getId());
					$puissance->setType('info');
					$puissance->setSubType('numeric');
					$puissance->setLogicalId('puissance');
					$puissance->setUnite("Wh");
					$puissance->setEventOnly(1);
					$puissance->save();
				}
				break;
			case "analogique":
				$brut = $this->getCmd(null, 'brut');
				if ( ! is_object($brut) ) {
					$brut = new wesCmd();
					$brut->setName('Brut');
					$brut->setEqLogic_id($this->getId());
					$brut->setType('info');
					$brut->setSubType('numeric');
					$brut->setLogicalId('brut');
					$brut->setIsVisible(false);
					$brut->setEventOnly(1);
					$brut->save();
				}
				$reel = $this->getCmd(null, 'reel');
				if ( ! is_object($reel) ) {
					$reel = new wesCmd();
					$reel->setName('Réel');
					$reel->setEqLogic_id($this->getId());
					$reel->setType('info');
					$reel->setSubType('numeric');
					$reel->setLogicalId('reel');
					$reel->setEventOnly(1);
					$reel->setConfiguration('calcul', '#' . $brut->getId() . '#');
					$reel->save();
				}
				break;
			case "vswitch":
				$state = $this->getCmd(null, 'state');
				if ( ! is_object($state) ) {
					$state = new wesCmd();
					$state->setName('Etat');
					$state->setEqLogic_id($this->getId());
					$state->setType('info');
					$state->setSubType('binary');
					$state->setLogicalId('state');
					$state->setEventOnly(1);
					$state->save();
				}
				$btn_on = $this->getCmd(null, 'btn_on');
				if ( ! is_object($btn_on) ) {
					$btn_on = new wesCmd();
					$btn_on->setName('On');
					$btn_on->setEqLogic_id($this->getId());
					$btn_on->setType('action');
					$btn_on->setSubType('other');
					$btn_on->setLogicalId('btn_on');
					$btn_on->setEventOnly(1);
					$btn_on->save();
				}
				$btn_off = $this->getCmd(null, 'btn_off');
				if ( ! is_object($btn_off) ) {
					$btn_off = new wesCmd();
					$btn_off->setName('Off');
					$btn_off->setEqLogic_id($this->getId());
					$btn_off->setType('action');
					$btn_off->setSubType('other');
					$btn_off->setLogicalId('btn_off');
					$btn_off->setEventOnly(1);
					$btn_off->save();
				}
				$commute = $this->getCmd(null, 'commute');
				if ( ! is_object($commute) ) {
					$commute = new wesCmd();
					$commute->setName('Commute');
					$commute->setEqLogic_id($this->getId());
					$commute->setType('action');
					$commute->setSubType('other');
					$commute->setLogicalId('commute');
					$commute->setEventOnly(1);
					$commute->save();
				}
				break;
			case "variable":
				$state = $this->getCmd(null, 'valeur');
				if ( ! is_object($state) ) {
					$state = new wesCmd();
					$state->setName('Valeur');
					$state->setEqLogic_id($this->getId());
					$state->setType('info');
					$state->setSubType('numeric');
					$state->setLogicalId('valeur');
					$state->setEventOnly(1);
					$state->save();
				}
				$change = $this->getCmd(null, 'change');
				if ( ! is_object($change) ) {
					$change = new wesCmd();
					$change->setName('Change');
					$change->setEqLogic_id($this->getId());
					$change->setType('action');
					$change->setSubType('slider');
					$change->setLogicalId('change');
					$change->setEventOnly(1);
					$change->save();
				}
				break;
		}
	}

	private function getListeDefaultCommandesTeleinfo()
	{
		return array("ADCO" => array('Numero compteur', 'numeric', '', 0, "", ""),
		"OPTARIF" => array('Option tarif', 'string', '', 1, "", ""),
		"ISOUSC" => array('Intensité souscrite', 'numeric', 'A', 0, "", ""),
		"PTEC" => array('Tarif en cours', 'string', '', 1, "", ""),
		"PAP" => array('Puissance Apparente', 'numeric', 'Va', 1, "", ""),
		"IINST" => array('Intensité instantanée', 'numeric', 'A', 1, "Mono"),
		"IINST1" => array('Intensité instantanée 1', 'numeric', 'A', 0, "Tri"),
		"IINST2" => array('Intensité instantanée 2', 'numeric', 'A', 0, "Tri"),
		"IINST3" => array('Intensité instantanée 3', 'numeric', 'A', 0, "Tri"),
		"IMAX" => array('Intensité maximum', 'numeric', 'A', 1, "Mono"),
		"IMAX1" => array('Intensité maximum 1', 'numeric', 'A', 0, "Tri"),
		"IMAX2" => array('Intensité maximum 2', 'numeric', 'A', 0, "Tri"),
		"IMAX3" => array('Intensité maximum 3', 'numeric', 'A', 0, "Tri"),
		"PEJP" => array('Préavis EJP', 'binary', '', 0, "EJP", ""),
		"DEMAIN" => array('Couleur demain', 'string', '', 0, "BBRH", ""),
		"BASE" => array('Index (base)', 'numeric', 'Wh', 1, "BASE", ""),
		"HCHC" => array('Index (heures creuses)', 'numeric', 'Wh', 1, "HC", ""),
		"HCHP" => array('Index (heures pleines)', 'numeric', 'Wh', 1, "HC", ""),
		"EJPHN" => array('Index (normal EJP)', 'numeric', 'Wh', 0, "EJP", ""),
		"EJPHPM" => array('Index (pointe mobile EJP)', 'numeric', 'Wh', 0, "EJP", ""),
		"BBRHCJB" => array('Index (heures creuses jours bleus Tempo)', 'numeric', 'Wh', 0, "BBRH", ""),
		"BBRHPJB" => array('Index (heures pleines jours bleus Tempo)', 'numeric', 'Wh', 0, "BBRH", ""),
		"BBRHCJW" => array('Index (heures creuses jours blancs Tempo)', 'numeric', 'Wh', 0, "BBRH", ""),
		"BBRHPJW" => array('Index (heures pleines jours blancs Tempo)', 'numeric', 'Wh', 0, "BBRH", ""),
		"BBRHCJR" => array('Index (heures creuses jours rouges Tempo)', 'numeric', 'Wh', 0, "BBRH", ""),
		"BBRHPJR" => array('Index (heures pleines jours rouges Tempo)', 'numeric', 'Wh', 0, "BBRH", ""),
		"BASE_evolution" => array('Evolution index (base)', 'numeric', 'W/min', 1, "BASE", ""),
		"HCHC_evolution" => array('Evolution index (heures creuses)', 'numeric', 'W/min', 1, "HC", ""),
		"HCHP_evolution" => array('Evolution index (heures pleines)', 'numeric', 'W/min', 1, "HC", ""),
		"BBRHCJB_evolution" => array('Evolution index (heures creuses jours bleus Tempo)', 'numeric', 'W/min', 0, "BBRH", ""),
		"BBRHPJB_evolution" => array('Evolution index (heures pleines jours bleus Tempo)', 'numeric', 'W/min', 0, "BBRH", ""),
		"BBRHCJW_evolution" => array('Evolution index (heures creuses jours blancs Tempo)', 'numeric', 'W/min', 0, "BBRH", ""),
		"BBRHPJW_evolution" => array('Evolution index (heures pleines jours blancs Tempo)', 'numeric', 'W/min', 0, "BBRH", ""),
		"BBRHCJR_evolution" => array('Evolution index (heures creuses jours rouges Tempo)', 'numeric', 'W/min', 0, "BBRH", ""),
		"BBRHPJR_evolution" => array('Evolution index (heures pleines jours rouges Tempo)', 'numeric', 'W/min', 0, "BBRH", ""),
		"EJPHN_evolution" => array('Evolution index (normal EJP)', 'numeric', 'W', 0, "EJP", ""),
		"EJPHPM_evolution" => array('Evolution index (pointe mobile EJP)', 'numeric', 'W', 0, "EJP", ""));
	}

	public function postUpdate()
	{
		switch ($this->getConfiguration('type', '')) {
			case "carte":
				$this->xmlstatus = simplexml_load_string($this->getUrl('data.cgx'));
				$compteurId = 1;
				$status = $this->xmlstatus->xpath('//temp/SONDE'.$compteurId);
				while ( count($status) != 0 ) {
					if ( ! is_object(self::byLogicalId($this->getId()."_A".$compteurId, 'wes')) ) {
						log::add('wes','debug','Creation temperature : '.$this->getId().'_A'.$compteurId);
						$eqLogic = new wes();
						$eqLogic->setEqType_name('wes');
						$eqLogic->setLogicalId($this->getId().'_A'.$compteurId);
						$eqLogic->setConfiguration('type', 'temperature');
						$eqLogic->setName('Temperature ' . $compteurId);
						$eqLogic->save();
					}
					$compteurId ++;
					$status = $this->xmlstatus->xpath('//temp/SONDE'.$compteurId);
				}
				for ($compteurId = 1; $compteurId <= 9; $compteurId++) {
					$status = $this->xmlstatus->xpath('//relais1W/RELAIS'.$compteurId."01");
					if ( count($status) != 0 ) {
						for ($souscompteurId = 1; $souscompteurId <= 8; $souscompteurId++) {
							if ( ! is_object(self::byLogicalId($this->getId()."_R".$compteurId.sprintf("%02d", $souscompteurId), 'wes')) ) {
								log::add('wes','debug','Creation relai : '.$this->getId().'_R'.$compteurId.sprintf("%02d", $souscompteurId));
								$eqLogic = new wes();
								$eqLogic->setEqType_name('wes');
								$eqLogic->setConfiguration('type', 'relai');
								$eqLogic->setLogicalId($this->getId().'_R'.$compteurId.sprintf("%02d", $souscompteurId));
								$eqLogic->setName('Relai ' . $compteurId.sprintf("%02d", $souscompteurId));
								$eqLogic->save();
							}
						}
					}
					else {
						for ($souscompteurId = 1; $souscompteurId <= 8; $souscompteurId++) {
							$eqLogic = self::byLogicalId($this->getId()."_R".$compteurId.sprintf("%02d", $souscompteurId), 'wes');
							if ( is_object($eqLogic) ) {
								log::add('wes','debug','Suppression relai : '.$this->getId().'_R'.$compteurId.sprintf("%02d", $souscompteurId));
								$eqLogic->remove();
							}
						}
					}
				}

				for ($compteurId = 1; $compteurId <= 2; $compteurId++) {
					if ( ! is_object(self::byLogicalId($this->getId()."_R".$compteurId, 'wes')) ) {
						log::add('wes','debug','Creation relai : '.$this->getId().'_R'.$compteurId);
						$eqLogic = new wes();
						$eqLogic->setEqType_name('wes');
						$eqLogic->setConfiguration('type', 'relai');
						$eqLogic->setLogicalId($this->getId().'_R'.$compteurId);
						$eqLogic->setName('Relai ' . $compteurId);
						$eqLogic->save();
					}
				}
				for ($compteurId = 1; $compteurId <= 2; $compteurId++) {
					if ( ! is_object(self::byLogicalId($this->getId()."_B".$compteurId, 'wes')) ) {
						log::add('wes','debug','Creation bouton : '.$this->getId().'_B'.$compteurId);
						$eqLogic = new wes();
						$eqLogic->setEqType_name('wes');
						$eqLogic->setConfiguration('type', 'bouton');
						$eqLogic->setLogicalId($this->getId().'_B'.$compteurId);
						$eqLogic->setName('Bouton ' . $compteurId);
						$eqLogic->save();
					}
				}
				for ($compteurId = 1; $compteurId <= 8; $compteurId++) {
					if ( ! is_object(self::byLogicalId($this->getId()."_V".$compteurId, 'wes')) ) {
						log::add('wes','debug','Creation variable : '.$this->getId().'_V'.$compteurId);
						$eqLogic = new wes();
						$eqLogic->setEqType_name('wes');
						$eqLogic->setConfiguration('type', 'variable');
						$eqLogic->setLogicalId($this->getId().'_V'.$compteurId);
						$eqLogic->setName('Variable ' . $compteurId);
						$eqLogic->save();
					}
				}
				for ($compteurId = 1; $compteurId <= 8; $compteurId++) {
					if ( ! is_object(self::byLogicalId($this->getId()."_S".$compteurId, 'wes')) ) {
						log::add('wes','debug','Creation vswitch : '.$this->getId().'_S'.$compteurId);
						$eqLogic = new wes();
						$eqLogic->setEqType_name('wes');
						$eqLogic->setConfiguration('type', 'vswitch');
						$eqLogic->setLogicalId($this->getId().'_S'.$compteurId);
						$eqLogic->setName('Vswitch ' . $compteurId);
						$eqLogic->save();
					}
				}
				$compteurId = 1;
				$status = $this->xmlstatus->xpath('//impulsion/INDEX'.$compteurId);
				while ( count($status) != 0 ) {
					if ( ! is_object(self::byLogicalId($this->getId()."_C".$compteurId, 'wes')) ) {
						log::add('wes','debug','Creation compteur : '.$this->getId().'_C'.$compteurId);
						$eqLogic = new wes();
						$eqLogic->setEqType_name('wes');
						$eqLogic->setConfiguration('type', 'compteur');
						$eqLogic->setLogicalId($this->getId().'_C'.$compteurId);
						$eqLogic->setName('Compteur ' . $compteurId);
						$eqLogic->save();
					}
					$compteurId ++;
					$status = $this->xmlstatus->xpath('//impulsion/INDEX'.$compteurId);
				}

				for ($compteurId = 1; $compteurId <= 2; $compteurId++) {
					if ( ! is_object(self::byLogicalId($this->getId()."_T".$compteurId, 'wes')) ) {
						log::add('wes','debug','Creation teleinfo : '.$this->getId().'_T'.$compteurId);
						$eqLogic = new wes();
						$eqLogic->setEqType_name('wes');
						$eqLogic->setConfiguration('type', 'teleinfo');
						$eqLogic->setLogicalId($this->getId().'_T'.$compteurId);
						$eqLogic->setName('Teleinfo ' . $compteurId);
						$eqLogic->save();
					}
				}

				$compteurId = 1;
				$status = $this->xmlstatus->xpath('//pince/I'.$compteurId);
				while ( count($status) != 0 ) {
					if ( ! is_object(self::byLogicalId($this->getId()."_P".$compteurId, 'wes')) ) {
						log::add('wes','debug','Creation pince : '.$this->getId().'_P'.$compteurId);
						$eqLogic = new wes();
						$eqLogic->setEqType_name('wes');
						$eqLogic->setConfiguration('type', 'pince');
						$eqLogic->setLogicalId($this->getId().'_P'.$compteurId);
						$eqLogic->setName('Pince ' . $compteurId);
						$eqLogic->save();
					}
					$compteurId ++;
					$status = $this->xmlstatus->xpath('//pince/I'.$compteurId);
				}

				$compteurId = 1;
				$status = $this->xmlstatus->xpath('//analogique/AD'.$compteurId);
				while ( count($status) != 0 ) {
					if ( ! is_object(self::byLogicalId($this->getId()."_N".$compteurId, 'wes')) ) {
						log::add('wes','debug','Creation analogique : '.$this->getId().'_N'.$compteurId);
						$eqLogic = new wes();
						$eqLogic->setEqType_name('wes');
						$eqLogic->setConfiguration('type', 'analogique');
						$eqLogic->setLogicalId($this->getId().'_N'.$compteurId);
						$eqLogic->setName('Analogique ' . $compteurId);
						$eqLogic->save();
					}
					$compteurId ++;
					$status = $this->xmlstatus->xpath('//analogique/AD'.$compteurId);
				}

				$cmd = $this->getCmd(null, 'status');
				if ( ! is_object($cmd) ) {
					$cmd = new wesCmd();
					$cmd->setName('Etat');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setType('info');
					$cmd->setSubType('binary');
					$cmd->setLogicalId('status');
					$cmd->setIsVisible(1);
					$cmd->setEventOnly(1);
					$cmd->save();
				}
				break;
			case "bouton":
				$nbimpulsion = $this->getCmd(null, 'nbimpulsion');
				if ( is_object($nbimpulsion) ) {
					$nbimpulsion->remove();
				}
				$state = $this->getCmd(null, 'etat');
				if ( is_object($state) ) {
					$state->setLogicalId('state');
					$state->save();
				}
				break;
			case "temperature":
				$reel = $this->getCmd(null, 'reel');
				if ( ! is_object($reel) ) {
					$reel = new wesCmd();
					$reel->setName('Réel');
					$reel->setEqLogic_id($this->getId());
					$reel->setType('info');
					$reel->setSubType('numeric');
					$reel->setLogicalId('reel');
					$reel->setEventOnly(1);
					$reel->save();
				}
				break;
			case "relai":
				$state = $this->getCmd(null, 'state');
				if ( ! is_object($state) ) {
					$state = new wesCmd();
					$state->setName('Etat');
					$state->setEqLogic_id($this->getId());
					$state->setType('info');
					$state->setSubType('binary');
					$state->setLogicalId('state');
					$state->setEventOnly(1);
					$state->save();
				}
				$btn_on = $this->getCmd(null, 'btn_on');
				if ( ! is_object($btn_on) ) {
					$btn_on = new wesCmd();
					$btn_on->setName('On');
					$btn_on->setEqLogic_id($this->getId());
					$btn_on->setType('action');
					$btn_on->setSubType('other');
					$btn_on->setLogicalId('btn_on');
					$btn_on->setEventOnly(1);
					$btn_on->save();
				}
				$btn_off = $this->getCmd(null, 'btn_off');
				if ( ! is_object($btn_off) ) {
					$btn_off = new wesCmd();
					$btn_off->setName('Off');
					$btn_off->setEqLogic_id($this->getId());
					$btn_off->setType('action');
					$btn_off->setSubType('other');
					$btn_off->setLogicalId('btn_off');
					$btn_off->setEventOnly(1);
					$btn_off->save();
				}
				$commute = $this->getCmd(null, 'commute');
				if ( ! is_object($commute) ) {
					$commute = new wesCmd();
					$commute->setName('Commute');
					$commute->setEqLogic_id($this->getId());
					$commute->setType('action');
					$commute->setSubType('other');
					$commute->setLogicalId('commute');
					$commute->setEventOnly(1);
					$commute->save();
				}
				break;
			case "compteur":
				$nbimpulsion = $this->getCmd(null, 'nbimpulsion');
				if ( ! is_object($nbimpulsion) ) {
					$nbimpulsion = new wesCmd();
					$nbimpulsion->setName('Nombre d impulsion');
					$nbimpulsion->setEqLogic_id($this->getId());
					$nbimpulsion->setType('info');
					$nbimpulsion->setSubType('numeric');
					$nbimpulsion->setLogicalId('nbimpulsion');
					$nbimpulsion->setEventOnly(1);
					$nbimpulsion->save();
				}
				$nbimpulsionminute = $this->getCmd(null, 'nbimpulsionminute');
				if ( ! is_object($nbimpulsionminute) ) {
					$nbimpulsionminute = new wesCmd();
					$nbimpulsionminute->setName('Nombre d impulsion par minute');
					$nbimpulsionminute->setEqLogic_id($this->getId());
					$nbimpulsionminute->setType('info');
					$nbimpulsionminute->setSubType('numeric');
					$nbimpulsionminute->setLogicalId('nbimpulsionminute');
					$nbimpulsionminute->setUnite("Imp/min");
					$nbimpulsionminute->setConfiguration('calcul', '#brut#');
					$nbimpulsionminute->setEventOnly(1);
					$nbimpulsionminute->save();
				}
				break;
			case "teleinfo":
				foreach (eqLogic::byTypeAndSearhConfiguration('wes', '"type":"carte"') as $eqLogic) {
					if ( substr($this->getLogicalId(), 0, strpos($this->getLogicalId(),"_")) == $eqLogic->getId() ) {
						$phase = $eqLogic->GetPhase(substr($this->getLogicalId(), strpos($this->getLogicalId(),"_")+2, 1));
						log::add('wes','debug','Detection phase : '.$phase);
					}
				}
				foreach( $this->getListeDefaultCommandesTeleinfo() as $label => $data)
				{
					if ( $this->getConfiguration('tarification') == "" || $this->getConfiguration('tarification') == $data[4] || $data[4] == "" && ( $phase == $data[7] || $data[7] == "" ) ) {
						$cmd = $this->getCmd(null, $label);
						if ( ! is_object($cmd) ) {
							$cmd = new wesCmd();
							$cmd->setName($data[0]);
							$cmd->setEqLogic_id($this->getId());
							$cmd->setType('info');
							$cmd->setSubType($data[1]);
							$cmd->setLogicalId($label);
							$cmd->setUnite($data[2]);
							$cmd->setIsVisible($data[3]);
							$cmd->setEventOnly(1);
							$cmd->save();
						}
					} else {
						$cmd = $this->getCmd(null, $label);
						if ( is_object($cmd) ) {
							$cmd->remove();
						}
					}
				}
				break;
			case "pince":
				$intensite = $this->getCmd(null, 'intensite');
				if ( ! is_object($intensite) ) {
					$intensite = new wesCmd();
					$intensite->setName('Intensité');
					$intensite->setEqLogic_id($this->getId());
					$intensite->setType('info');
					$intensite->setSubType('numeric');
					$intensite->setUnite("A");
					$intensite->setLogicalId('intensite');
					$intensite->setEventOnly(1);
					$intensite->save();
				}
				$puissance = $this->getCmd(null, 'puissance');
				if ( ! is_object($puissance) ) {
					$puissance = new wesCmd();
					$puissance->setName('Puissance');
					$puissance->setEqLogic_id($this->getId());
					$puissance->setType('info');
					$puissance->setSubType('numeric');
					$puissance->setLogicalId('puissance');
					$puissance->setUnite("Wh");
					$puissance->setEventOnly(1);
					$puissance->save();
				}
				break;
			case "analogique":
				$brut = $this->getCmd(null, 'voltage');
				if ( is_object($brut) ) {
					$brut->setLogicalId('brut');
					$brut->save();
				} else {
					$brut = $this->getCmd(null, 'brut');
				}
				$reel = $this->getCmd(null, 'reel');
				if ( ! is_object($reel) ) {
					$reel = new wesCmd();
					$reel->setName('Réel');
					$reel->setEqLogic_id($this->getId());
					$reel->setType('info');
					$reel->setSubType('numeric');
					$reel->setLogicalId('reel');
					$reel->setEventOnly(1);
					$reel->setConfiguration('calcul', '#' . $brut->getId() . '#');
					$reel->save();
				}
				break;
			case "vswitch":
				$state = $this->getCmd(null, 'state');
				if ( ! is_object($state) ) {
					$state = new wesCmd();
					$state->setName('Etat');
					$state->setEqLogic_id($this->getId());
					$state->setType('info');
					$state->setSubType('binary');
					$state->setLogicalId('state');
					$state->setEventOnly(1);
					$state->save();
				}
				$btn_on = $this->getCmd(null, 'btn_on');
				if ( ! is_object($btn_on) ) {
					$btn_on = new wesCmd();
					$btn_on->setName('On');
					$btn_on->setEqLogic_id($this->getId());
					$btn_on->setType('action');
					$btn_on->setSubType('other');
					$btn_on->setLogicalId('btn_on');
					$btn_on->setEventOnly(1);
					$btn_on->save();
				}
				$btn_off = $this->getCmd(null, 'btn_off');
				if ( ! is_object($btn_off) ) {
					$btn_off = new wesCmd();
					$btn_off->setName('Off');
					$btn_off->setEqLogic_id($this->getId());
					$btn_off->setType('action');
					$btn_off->setSubType('other');
					$btn_off->setLogicalId('btn_off');
					$btn_off->setEventOnly(1);
					$btn_off->save();
				}
				$commute = $this->getCmd(null, 'commute');
				if ( ! is_object($commute) ) {
					$commute = new wesCmd();
					$commute->setName('Commute');
					$commute->setEqLogic_id($this->getId());
					$commute->setType('action');
					$commute->setSubType('other');
					$commute->setLogicalId('commute');
					$commute->setEventOnly(1);
					$commute->save();
				}
			case "variable":
				$state = $this->getCmd(null, 'valeur');
				if ( ! is_object($state) ) {
					$state = new wesCmd();
					$state->setName('Valeur');
					$state->setEqLogic_id($this->getId());
					$state->setType('info');
					$state->setSubType('numeric');
					$state->setLogicalId('valeur');
					$state->setEventOnly(1);
					$state->save();
				}
				$change = $this->getCmd(null, 'change');
				if ( ! is_object($change) ) {
					$change = new wesCmd();
					$change->setName('Change');
					$change->setEqLogic_id($this->getId());
					$change->setType('action');
					$change->setSubType('Curseur');
					$change->setLogicalId('change');
					$change->setEventOnly(1);
					$change->save();
				}
				break;
		}
	}

	public function preRemove()
	{
		if ( $this->getConfiguration('type', '') == "carte" )
		{
			foreach (self::byType('wes') as $eqLogic) {
				if ( substr($eqLogic->getLogicalId(), 0, strpos($eqLogic->getLogicalId(),"_")) == $this->getId() ) {
					log::add('wes','debug','Suppression des sous equipements : '.$eqLogic->getName());
					$eqLogic->remove();
				}
			}
		}
	}

	public function configPush($parent = null, $compteurId = null, $pathjeedom = null) {
		if ( ! isset($pathjeedom) )
		{
			if ( config::byKey("internalAddr") == "" || config::byKey("internalPort") == "" )
			{
				throw new Exception(__('L\'adresse IP ou le port local de jeedom ne sont pas définit (Administration => Configuration réseaux => Accès interne).', __FILE__));
			}
			$pathjeedom = config::byKey("internalComplement", "/");
			if ( strlen($pathjeedom) == 0 ) {
				$pathjeedom = "/";
			}
			if ( substr($pathjeedom, 0, 1) != "/" ) {
				$pathjeedom = "/".$pathjeedom;
			}
			if ( substr($pathjeedom, -1) != "/" ) {
				$pathjeedom = $pathjeedom."/";
			}
		}
		switch ($this->getConfiguration('type', '')) {
			case "carte":
				if ( $this->getIsEnable() ) {
					$this->getUrl('rqthttp.cgi', 'RQd5='.config::byKey("internalAddr").'&RQp5='.config::byKey("internalPort"));
					$compteurId=0;
					foreach (explode(',', init('eqLogicPush_id')) as $_eqLogic_id) {
						$eqLogic = eqLogic::byId($_eqLogic_id);
						if (!is_object($eqLogic)) {
							throw new Exception(__('Impossible de trouver l\'équipement : ', __FILE__) . $_eqLogic_id);
						}
						if ( method_exists($eqLogic, "configPush" ) ) {
							$compteurId = $eqLogic->configPush($this, $compteurId, $pathjeedom);
							$compteurId++;
						}
					}
				}
				break;
			case "bouton":
				$wesid = substr($this->getLogicalId(), strpos($this->getLogicalId(),"_")+2);
				$cmd = $this->getCmd(null, 'state');
				$parent->getUrl('program.cgi?PRG='.$compteurId.','.($wesid+30).',0,0,1,0,1,2,0,1,4,0000,0000,9,0');
				$parent->getUrl('program.cgi?RQT'.$compteurId.'='.$pathjeedom.'core/api/jeeApi.php?api='.jeedom::getApiKey('wes').'%26plugin=wes%26type=wes%26id='.$cmd->getId().'%26value=$E00'.$wesid);
				$compteurId++;
				$parent->getUrl('program.cgi?PRG='.$compteurId.','.($wesid+30).',0,0,0,0,1,2,0,1,4,0000,0000,9,0');
				$parent->getUrl('program.cgi?RQT'.$compteurId.'='.$pathjeedom.'core/api/jeeApi.php?api='.jeedom::getApiKey('wes').'%26plugin=wes%26type=wes%26id='.$cmd->getId().'%26value=$E00'.$wesid);
				return $compteurId;
				break;
			case "temperature":
				break;
			case "relai":
				$wesid = substr($this->getLogicalId(), strpos($this->getLogicalId(),"_")+2);
				$cmd = $this->getCmd(null, 'state');
				$parent->getUrl('program.cgi?PRG='.$compteurId.','.($wesid+100).',0,0,1,0,1,2,0,1,4,0000,0000,9,0');
				$wesid = sprintf("%03d", $wesid);
				$parent->getUrl('program.cgi?RQT'.$compteurId.'='.$pathjeedom.'core/api/jeeApi.php?api='.jeedom::getApiKey('wes').'%26plugin=wes%26type=wes%26id='.$cmd->getId().'%26value=$R'.$wesid);
				$compteurId++;
				$parent->getUrl('program.cgi?PRG='.$compteurId.','.($wesid+100).',0,0,0,0,1,2,0,1,4,0000,0000,9,0');
				$wesid = sprintf("%03d", $wesid);
				$parent->getUrl('program.cgi?RQT'.$compteurId.'='.$pathjeedom.'core/api/jeeApi.php?api='.jeedom::getApiKey('wes').'%26plugin=wes%26type=wes%26id='.$cmd->getId().'%26value=$R'.$wesid);
				return $compteurId;
				break;
			case "compteur":
				break;
			case "teleinfo":
				break;
			case "pince":
				break;
			case "analogique":
				break;
			case "vswitch":
				$wesid = substr($this->getLogicalId(), strpos($this->getLogicalId(),"_")+2);
				$cmd = $this->getCmd(null, 'state');
				$parent->getUrl('program.cgi?PRG='.$compteurId.','.($wesid+50).',0,0,0,0.00,1,0,0,1,4,0000,0000,9,0');
				$wesid = sprintf("%03d", $wesid);
				$parent->getUrl('program.cgi?RQT'.$compteurId.'='.$pathjeedom.'core/api/jeeApi.php?api='.jeedom::getApiKey('wes').'%26plugin=wes%26type=wes%26id='.$cmd->getId().'%26value=$v00'.$wesid);
				$compteurId++;
				$parent->getUrl('program.cgi?PRG='.$compteurId.','.($wesid+50).',0,0,1,0.00,1,0,0,1,4,0000,0000,9,0');
				$wesid = sprintf("%03d", $wesid);
				$parent->getUrl('program.cgi?RQT'.$compteurId.'='.$pathjeedom.'core/api/jeeApi.php?api='.jeedom::getApiKey('wes').'%26plugin=wes%26type=wes%26id='.$cmd->getId().'%26value=$v00'.$wesid);
				return $compteurId;
				break;
			case "variable":
				# Todo
				break;
		}
	}

	public static function event() {
        $cmd = wesCmd::byId(init('id'));
        if (!is_object($cmd)) {
            throw new Exception('Commande ID inconnu : ' . init('id'));
        }
		log::add('wes','debug','recieve '.init('value').' for commande id '.init('id'));
		if ($cmd->execCmd() != $cmd->formatValue(init('value'))) {
			$cmd->setCollectDate('');
			$cmd->event(init('value'));
		}
	}

	public function GetPhase($wesid)
	{
		if ( $this->getIsEnable() )
		{
			log::add('wes','debug','get data.cgx');
			$this->xmlstatus = simplexml_load_string($this->getUrl('data.cgx'));
			if ( $this->xmlstatus === false )
				throw new Exception(__('Le wes ne repond pas.',__FILE__)." ".$this->getName());
			$xpathModele = '//tic'.$wesid.'/IINST1';
			$status = $this->xmlstatus->xpath($xpathModele);

			if ( count($status) != 0 )
			{
				if ( $status[0] != "0" )
				{
					return "Tri";
				}
			}
			$xpathModele = '//tic'.$wesid.'/IINST2';
			$status = $this->xmlstatus->xpath($xpathModele);

			if ( count($status) != 0 )
			{
				if ( $status[0] != "0" )
				{
					return "Tri";
				}
			}
			$xpathModele = '//tic'.$wesid.'/IINST3';
			$status = $this->xmlstatus->xpath($xpathModele);

			if ( count($status) != 0 )
			{
				if ( $status[0] != "0" )
				{
					return "Tri";
				}
			}
			$xpathModele = '//tic'.$wesid.'/IINST';
			$status = $this->xmlstatus->xpath($xpathModele);

			if ( count($status) != 0 )
			{
				if ( $status[0] != "0" )
				{
					return "Mono";
				}
			}
		}
		return "";
	}

	public function pull() {
		if ( $this->getIsEnable() ) {
			log::add('wes','debug','pull '.$this->getName());
			$statuscmd = $this->getCmd(null, 'status');
			log::add('wes','debug','get data.cgx');
			$this->xmlstatus = simplexml_load_string($this->getUrl('data.cgx'));
			$count = 0;
			while ( $this->xmlstatus === false && $count < 3 ) {
				log::add('wes','debug','reget data.cgx');
				$this->xmlstatus = simplexml_load_string($this->getUrl('data.cgx'));
				$count++;
			}
			if ( $this->xmlstatus === false ) {
				if ($statuscmd->execCmd() != 0) {
					$statuscmd->setCollectDate('');
					$statuscmd->event(0);
				}
				log::add('wes','error',__('Le wes ne repond pas.',__FILE__)." ".$this->getName()." get data.cgx");
				return false;
			}
			if ($statuscmd->execCmd() != 1) {
				$statuscmd->setCollectDate('');
				$statuscmd->event(1);
			}
			foreach (eqLogic::byTypeAndSearhConfiguration('wes', '"type":"relai"') as $eqLogicRelai) {
				if ( $eqLogicRelai->getIsEnable() && substr($eqLogicRelai->getLogicalId(), 0, strpos($eqLogicRelai->getLogicalId(),"_")) == $this->getId() ) {
					$wesid = substr($eqLogicRelai->getLogicalId(), strpos($eqLogicRelai->getLogicalId(),"_")+2);
					if ( $wesid < 10 ) {
						$xpathModele = '//relais/RELAIS'.$wesid;
						$status = $this->xmlstatus->xpath($xpathModele);
						
						if ( count($status) != 0 )
						{
							$eqLogic_cmd = $eqLogicRelai->getCmd(null, 'state');
							if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($status[0])) {
								log::add('wes','debug',"Change state off ".$eqLogicRelai->getName());
								$eqLogic_cmd->setCollectDate('');
								$eqLogic_cmd->event($status[0]);
							}
						}
					} else {
						$xpathModele = '//relais1W/RELAIS'.$wesid;
						$status = $this->xmlstatus->xpath($xpathModele);
						
						if ( count($status) != 0 )
						{
							$eqLogic_cmd = $eqLogicRelai->getCmd(null, 'state');
							if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($status[0])) {
								log::add('wes','debug',"Change state off ".$eqLogicRelai->getName());
								$eqLogic_cmd->setCollectDate('');
								$eqLogic_cmd->event($status[0]);
							}
						}
					}
				}
			}
			foreach (eqLogic::byTypeAndSearhConfiguration('wes', '"type":"bouton"') as $eqLogicBouton) {
				if ( $eqLogicBouton->getIsEnable() && substr($eqLogicBouton->getLogicalId(), 0, strpos($eqLogicBouton->getLogicalId(),"_")) == $this->getId() ) {
					$wesid = substr($eqLogicBouton->getLogicalId(), strpos($eqLogicBouton->getLogicalId(),"_")+2);
					$xpathModele = '//entree/ENTREE'.$wesid;
					$status = $this->xmlstatus->xpath($xpathModele);
					
					if ( count($status) != 0 )
					{
						$eqLogic_cmd = $eqLogicBouton->getCmd(null, 'state');
						if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($status[0])) {
							log::add('wes','debug',"Change state off ".$eqLogicBouton->getName());
							$eqLogic_cmd->setCollectDate('');
							$eqLogic_cmd->event($status[0]);
						}
					}
				}
			}
			foreach (eqLogic::byTypeAndSearhConfiguration('wes', '"type":"temperature"') as $eqLogictemperature) {
				if ( $eqLogictemperature->getIsEnable() && substr($eqLogictemperature->getLogicalId(), 0, strpos($eqLogictemperature->getLogicalId(),"_")) == $this->getId() ) {
					$wesid = substr($eqLogictemperature->getLogicalId(), strpos($eqLogictemperature->getLogicalId(),"_")+2);
					$xpathModele = '//temp/SONDE'.$wesid;
					$status = $this->xmlstatus->xpath($xpathModele);
					
					if ( count($status) != 0 )
					{
						$value = intval($status[0]);
						$eqLogic_cmd = $eqLogictemperature->getCmd(null, 'reel');
						if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($value)) {
							log::add('wes','debug',"Change reel ".$eqLogictemperature->getName());
						}
						$eqLogic_cmd->setCollectDate('');
						$eqLogic_cmd->event($status[0]);
					}
				}
			}
			foreach (eqLogic::byTypeAndSearhConfiguration('wes', '"type":"compteur"') as $eqLogicCompteur) {
				if ( $eqLogicCompteur->getIsEnable() && substr($eqLogicCompteur->getLogicalId(), 0, strpos($eqLogicCompteur->getLogicalId(),"_")) == $this->getId() ) {
					$wesid = substr($eqLogicCompteur->getLogicalId(), strpos($eqLogicCompteur->getLogicalId(),"_")+2);
					$xpathModele = '//impulsion/INDEX'.$wesid;
					$status = $this->xmlstatus->xpath($xpathModele);
					
					if ( count($status) != 0 )
					{
						$value = intval($status[0]);
						$nbimpulsion_cmd = $eqLogicCompteur->getCmd(null, 'nbimpulsion');
						$nbimpulsion = $nbimpulsion_cmd->execCmd();
						$nbimpulsionminute_cmd = $eqLogicCompteur->getCmd(null, 'nbimpulsionminute');
						if ( $nbimpulsion != $value ) {
							log::add('wes','debug',"Change nbimpulsion off ".$eqLogicCompteur->getName());
							$lastCollectDate = $nbimpulsion_cmd->getCollectDate();
							if ( $lastCollectDate == '' ) {
								log::add('wes','debug',"Change nbimpulsionminute 0");
								$nbimpulsionminute = 0;
							} else {
								$DeltaSeconde = (time() - strtotime($lastCollectDate))*60;
								if ( $DeltaSeconde != 0 )
								{
									if ( $value > $nbimpulsion ) {
										$DeltaValeur = $value - $nbimpulsion;
									} else {
										$DeltaValeur = $value;
									}
									$nbimpulsionminute = round (($status[0] - $nbimpulsion)/(time() - strtotime($lastCollectDate))*60, 6);
								} else {
									$nbimpulsionminute = 0;
								}
							}
							log::add('wes','debug',"Change nbimpulsionminute ".$nbimpulsionminute);
							$nbimpulsionminute_cmd->setCollectDate(date('Y-m-d H:i:s'));
							$nbimpulsionminute_cmd->event($nbimpulsionminute);
						} else {
							$nbimpulsionminute_cmd->setCollectDate(date('Y-m-d H:i:s'));
							$nbimpulsionminute_cmd->event(0);
						}
						$nbimpulsion_cmd->setCollectDate(date('Y-m-d H:i:s'));
						$nbimpulsion_cmd->event($value);
					}
				}
			}
			foreach (eqLogic::byTypeAndSearhConfiguration('wes', '"type":"pince"') as $eqLogicPince) {
				if ( $eqLogicPince->getIsEnable() && substr($eqLogicPince->getLogicalId(), 0, strpos($eqLogicPince->getLogicalId(),"_")) == $this->getId() ) {
					$wesid = substr($eqLogicPince->getLogicalId(), strpos($eqLogicPince->getLogicalId(),"_")+2);
					$xpathModele = '//pince/I'.$wesid;
					$status = $this->xmlstatus->xpath($xpathModele);
					
					if ( count($status) != 0 )
					{
						$value = intval($status[0]);
						$eqLogic_cmd = $eqLogicPince->getCmd(null, 'intensite');
						if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($value)) {
							log::add('wes','debug',"Change intensite ".$eqLogicPince->getName());
						}
						$eqLogic_cmd->setCollectDate('');
						$eqLogic_cmd->event($value);
					}
					$xpathModele = '//pince/INDEX'.$wesid;
					$status = $this->xmlstatus->xpath($xpathModele);
					
					if ( count($status) != 0 )
					{
						$value = intval($status[0]);
						$eqLogic_cmd = $eqLogicPince->getCmd(null, 'puissance');
						if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($value)) {
							log::add('wes','debug',"Change puissance ".$eqLogicPince->getName());
						}
						$eqLogic_cmd->setCollectDate('');
						$eqLogic_cmd->event($value);
					}
				}
			}
			foreach (eqLogic::byTypeAndSearhConfiguration('wes', '"type":"teleinfo"') as $eqLogicTeleinfo) {
				if ( $eqLogicTeleinfo->getIsEnable() && substr($eqLogicTeleinfo->getLogicalId(), 0, strpos($eqLogicTeleinfo->getLogicalId(),"_")) == $this->getId() ) {
					$wesid = substr($eqLogicTeleinfo->getLogicalId(), strpos($eqLogicTeleinfo->getLogicalId(),"_")+2, 1);
					$xpathModele = '//tic'.$wesid;
					$status = $this->xmlstatus->xpath($xpathModele);
					
					if ( count($status) != 0 )
					{
						foreach($status[0] as $item => $data) {
						log::add('wes','debug',"Trouve ".$item." => ".$data);
							$eqLogic_cmd = $eqLogicTeleinfo->getCmd(null, $item);
							if ( is_object($eqLogic_cmd) ) {
								$eqLogic_cmd_evol = $eqLogicTeleinfo->getCmd(null, $item."_evolution");
								if ( is_object($eqLogic_cmd_evol) ) {
									$ancien_data = $eqLogic_cmd->execCmd();
									if ($ancien_data != $data) {
										log::add('wes', 'debug', $eqLogic_cmd->getName().' Change '.$data);
										if ( $eqLogic_cmd->getCollectDate() == '' ) {
											$nbimpulsionminute = 0;
										} else {
											if ( $data > $ancien_data ) {
												$nbimpulsionminute = round (($data - $ancien_data)/(time() - strtotime($eqLogic_cmd->getCollectDate()))*60);
											} else {
												$nbimpulsionminute = round ($data/(time() - strtotime($eqLogic_cmd_evol->getCollectDate())*60));
											}
										}
										$eqLogic_cmd_evol->setCollectDate(date('Y-m-d H:i:s'));
										$eqLogic_cmd_evol->event($nbimpulsionminute);
									} else {
										$eqLogic_cmd_evol->setCollectDate(date('Y-m-d H:i:s'));
										$eqLogic_cmd_evol->event(0);
									}
									$eqLogic_cmd->setCollectDate(date('Y-m-d H:i:s'));
									$eqLogic_cmd->event($data);
								} else {
									$eqLogic_cmd->setCollectDate(date('Y-m-d H:i:s'));
									$eqLogic_cmd->event($data);
								}
							}
						}
					}
				}
			}
			foreach (eqLogic::byTypeAndSearhConfiguration('wes', '"type":"analogique"') as $eqLogicAnalogique) {
				if ( $eqLogicAnalogique->getIsEnable() && substr($eqLogicAnalogique->getLogicalId(), 0, strpos($eqLogicAnalogique->getLogicalId(),"_")) == $this->getId() ) {
					$wesid = substr($eqLogicAnalogique->getLogicalId(), strpos($eqLogicAnalogique->getLogicalId(),"_")+2);
					$xpathModele = '//analogique/AD'.$wesid;
					$status = $this->xmlstatus->xpath($xpathModele);
					
					if ( count($status) != 0 )
					{
						$value = intval($status[0]);
						$eqLogic_cmd = $eqLogicAnalogique->getCmd(null, 'brut');
						if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($value)) {
							log::add('wes','debug',"Change brut ".$eqLogicAnalogique->getName());
						}
						$eqLogic_cmd->setCollectDate('');
						$eqLogic_cmd->event($value);
					}
				}
			}
			foreach (eqLogic::byTypeAndSearhConfiguration('wes', '"type":"vswitch"') as $eqLogic) {
				if ( $eqLogic->getIsEnable() && substr($eqLogic->getLogicalId(), 0, strpos($eqLogic->getLogicalId(),"_")) == $this->getId() ) {
					$wesid = substr($eqLogic->getLogicalId(), strpos($eqLogic->getLogicalId(),"_")+2);
					$xpathModele = '//switch_virtuel/SWITCH'.$wesid;
					$status = $this->xmlstatus->xpath($xpathModele);
					
					if ( count($status) != 0 )
					{
						$eqLogic_cmd = $eqLogic->getCmd(null, 'state');
						if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($status[0])) {
							log::add('wes','debug',"Change state off ".$eqLogic->getName());
							$eqLogic_cmd->setCollectDate('');
							$eqLogic_cmd->event($status[0]);
						}
					}
				}
			}
			foreach (eqLogic::byTypeAndSearhConfiguration('wes', '"type":"variable"') as $eqLogic) {
				if ( $eqLogic->getIsEnable() && substr($eqLogic->getLogicalId(), 0, strpos($eqLogic->getLogicalId(),"_")) == $this->getId() ) {
					$wesid = substr($eqLogic->getLogicalId(), strpos($eqLogic->getLogicalId(),"_")+2);
					$xpathModele = '//variables/VARIABLE'.$wesid;
					$status = $this->xmlstatus->xpath($xpathModele);
					
					if ( count($status) != 0 )
					{
						$eqLogic_cmd = $eqLogic->getCmd(null, 'valeur');
						if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($status[0])) {
							log::add('wes','debug',"Change valeur off ".$eqLogic->getName());
							$eqLogic_cmd->setCollectDate('');
							$eqLogic_cmd->event($status[0]);
						}
					}
				}
			}
			log::add('wes','debug','pull end '.$this->getName());
		}
	}

	public function getImage()
	{
		if (file_exists(dirname(__FILE__) . '/../../plugin_info/' . $this->getConfiguration('type', '') . '_icon.png')) {
			return 'plugins/' . $this->getEqType_name() . '/plugin_info/' . $this->getConfiguration('type', '') . '_icon.png';
		}
		return parent::getImage();
	}

	/*     * **********************Getteur Setteur*************************** */
}

class wesCmd extends cmd 
{
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*     * **********************Getteur Setteur*************************** */
	public function getUrlPush() {
		if ( config::byKey('internalAddr') == "" ) {
			throw new Exception(__('L\'adresse IP du serveur Jeedom doit être renseignée.',__FILE__));
		}
		$pathjeedom = preg_replace("/plugins.*$/", "", $_SERVER['PHP_SELF']);
		if ( substr($pathjeedom, 0, 1) != "/" ) {
			$pathjeedom = "/".$pathjeedom;
		}
		if ( substr($pathjeedom, -1) != "/" ) {
			$pathjeedom = $pathjeedom."/";
		}
		$eqLogic = $this->getEqLogic();
		$wesid = substr($eqLogic->getLogicalId(), strpos($eqLogic->getLogicalId(),"_")+2);
		$url = 'http';
		if (  isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on" )
			$url .= 's';
		$url .= '://'.config::byKey('internalAddr').$pathjeedom.'core/api/jeeApi.php?api='.jeedom::getApiKey('wes').'&type=wes&id='.$this->getId().'&value=';
		switch ($this->getConfiguration('type', '')) {
			case "carte":
				break;
			case "bouton":
				if ( $this->getLogicalId() == 'state' ) {
					$url .= '$I'.$wesid.'00';
				}
				break;
			case "temperature":
				if ( $this->getLogicalId() == 'reel' ) {
					$url .= '$W0'.$wesid;
				}
				break;
			case "relai":
				if ( $this->getLogicalId() == 'state' ) {
					$url .= '$R'.$wesid.'00';
				}
				break;
			case "compteur":
				if ( $this->getLogicalId() == 'debit' ) {
					$url .= '$P'.$wesid.'00';
				}
				if ( $this->getLogicalId() == 'index' ) {
					$url .= '$P'.$wesid.'01';
				}
				break;
			case "teleinfo":
				break;
			case "pince":
				if ( $this->getLogicalId() == 'puissance' ) {
					$url .= '$A'.$wesid.'00';
				}
				if ( $this->getLogicalId() == 'intensite' ) {
					$url .= '$A'.$wesid.'01';
				}
				break;
			case "analogique":
				break;
			case "vswitch":
				if ( $this->getLogicalId() == 'state' ) {
					$url .= '$v00'.$wesid;
				}
				break;
			case "variable":
				if ( $this->getLogicalId() == 'valeur' ) {
					$url .= '$V10'.$wesid;
				}
				break;
		}
		
		return $url;
	}

	public function imperihomeGenerate($ISSStructure) {
		$eqLogic = $this->getEqLogic(); // Récupération de l'équipement de la commande
		$object = $eqLogic->getObject(); // Récupération de l'objet de l'équipement
		// Construction de la structure de base
		$info_device = array(
		'id' => $this->getId(), // ID de la commande, ne pas mettre autre chose!
		'name' => $eqLogic->getName()." - ".$this->getName(), // Nom de l'équipement que sera affiché par Imperihome: mettre quelque chose de parlant...
		'room' => (is_object($object)) ? $object->getId() : 99999, // Numéro de la pièce: ne pas mettre autre chose que ce code
		'params' => array(), // Le tableau des paramètres liés à ce type (qui sera complété aprés.
		);
		switch ($eqLogic->getConfiguration('type', '')) {
			case "carte":
				break;
			case "bouton":
				if ( $this->getLogicalId() == 'state' ) { // Sauf si on est entrain de traiter la commande "Mode", à ce moment là on indique un autre type
					$info_device['type'] = 'DevDoor'; // Le type Imperihome qui correspond le mieux à la commande
				}

				array_push ($info_device['params'], array("value" =>  '#' . $eqLogic->getCmd(null, 'state')->getId() . '#', "key" => "tripped", "type" => "infoBinary", "Description" => "Is the sensor tripped ? (0 = No / 1 = Tripped)"));
				array_push ($info_device['params'], array("value" =>  '0', "key" => "armable", "type" => "infoBinary", "Description" => "Ability to arm the device : 1 = Yes / 0 = No"));
				array_push ($info_device['params'], array("value" =>  '0', "key" => "ackable", "type" => "infoBinary", "Description" => "Ability to acknowledge alerts : 1 = Yes / 0 = No"));
				break;
			case "temperature":
				break;
			case "relai":
				if ( $this->getLogicalId() == 'state' ) { // Sauf si on est entrain de traiter la commande "Mode", à ce moment là on indique un autre type
					$type = 'DevSwitch'; // Le type Imperihome qui correspond le mieux à la commande
				}
				array_push ($info_device['params'], array("value" =>  '#' . $eqLogic->getCmd(null, 'state')->getId() . '#', "key" => "status", "type" => "infoBinary", "Description" => "Current status : 1 = On / 0 = Off"));
				$info_device['actions']["setStatus"]["item"]["0"] = $eqLogic->getCmd(null, 'btn_off')->getId();
				$info_device['actions']["setStatus"]["item"]["1"] = $eqLogic->getCmd(null, 'btn_on')->getId();
				break;
			case "compteur":
				break;
			case "teleinfo":
				break;
			case "pince":
				break;
			case "analogique":
				break;
		}
		if ( defined($info_device['type']) )
		{
			return $info_device;
		}
	}

    public function execute($_options = null) {
		log::add('wes','debug','execute '.print_r ($_options,true));
		$eqLogic = $this->getEqLogic();
        if (!is_object($eqLogic) || $eqLogic->getIsEnable() != 1) {
            throw new Exception(__('Equipement desactivé impossible d\éxecuter la commande : ' . $this->getHumanName(), __FILE__));
        }
		log::add('wes','debug','getEqType_name '.$eqLogic->getConfiguration('type', ''));
		switch ($eqLogic->getConfiguration('type', '')) {
			case "carte":
				if ( $this->getLogicalId() == 'all_on' )
				{
					$url .= 'preset.htm';
					for ($wesid = 0; $wesid <= 7; $wesid++) {
						$data['led'.($wesid+1)] =1;
					}
				}
				else if ( $this->getLogicalId() == 'all_off' )
				{
					$url .= 'preset.htm';
					for ($wesid = 0; $wesid <= 7; $wesid++) {
						$data['led'.($wesid+1)] =0;
					}
				}
				else if ( $this->getLogicalId() == 'reboot' )
				{
					$url = "protect/settings/reboot.htm";
				}
				else
					return false;
				log::add('wes','debug','get '.$url.'?'.http_build_query($data));
				$result = $eqLogic->getUrl($url.'?'.http_build_query($data));
				return false;
				break;
			case "bouton":
				break;
			case "temperature":
				break;
			case "relai":
				$weseqLogic = eqLogic::byId(substr ($eqLogic->getLogicalId(), 0, strpos($eqLogic->getLogicalId(),"_")));
				$wesid = substr($eqLogic->getLogicalId(), strpos($eqLogic->getLogicalId(),"_")+2);
				if ( $this->getLogicalId() == 'btn_on' )
					$url = 'RL.cgi?rl'.($wesid).'=ON';
				else if ( $this->getLogicalId() == 'btn_off' )
					$url = 'RL.cgi?rl'.($wesid).'=OFF';
		/*		else if ( $this->getLogicalId() == 'impulsion' )
					$url = 'preset.htm?RLY'.($wesid+1).'=1';*/
				else if ( $this->getLogicalId() == 'commute' )
					$url = 'RL.cgi?frl='.$wesid;
				else
					return false;
				$weseqLogic->getUrl($url);
				return false;
				break;
			case "compteur":
				break;
			case "teleinfo":
				break;
			case "pince":
				break;
			case "analogique":
				if ($this->getLogicalId() == 'reel') {
					try {
						$calcul = $this->getConfiguration('calcul');
						if ( preg_match("/#brut#/", $calcul) ) {
							$EqLogic = $this->getEqLogic();
							$brut = $EqLogic->getCmd(null, 'brut');
							$calcul = preg_replace("/#brut#/", "#".$brut->getId()."#", $calcul);
						}
						$result = jeedom::evaluateExpression($calcul);
						return $result;
					} catch (Exception $e) {
						$EqLogic = $this->getEqLogic();
						log::add('wes', 'error', $EqLogic->getName()." error in ".$this->getConfiguration('calcul')." : ".$e->getMessage());
						return scenarioExpression::setTags(str_replace('"', '', cmd::cmdToValue($this->getConfiguration('calcul'))));
					}
				} else {
					return $this->getConfiguration('value');
				}
				break;
			case "vswitch":
				$weseqLogic = eqLogic::byId(substr ($eqLogic->getLogicalId(), 0, strpos($eqLogic->getLogicalId(),"_")));
				$wesid = substr($eqLogic->getLogicalId(), strpos($eqLogic->getLogicalId(),"_")+2);
				if ( $this->getLogicalId() == 'btn_on' )
					$url = 'VS.cgi?vs'.($wesid).'=ON';
				else if ( $this->getLogicalId() == 'btn_off' )
					$url = 'VS.cgi?vs'.($wesid).'=OFF';
		/*		else if ( $this->getLogicalId() == 'impulsion' )
					$url = 'preset.htm?RLY'.($wesid+1).'=1';*/
				else if ( $this->getLogicalId() == 'commute' )
					$url = 'VS.cgi?fvs='.$wesid;
				else
					return false;
				$weseqLogic->getUrl($url);
				return false;
				break;
			case "variable":
				$weseqLogic = eqLogic::byId(substr ($eqLogic->getLogicalId(), 0, strpos($eqLogic->getLogicalId(),"_")));
				$wesid = substr($eqLogic->getLogicalId(), strpos($eqLogic->getLogicalId(),"_")+2);
				if ( $this->getLogicalId() == 'change' )
					$url = '?varv'.($wesid).'='.$_options['slider'];
				else
					return false;
				$weseqLogic->getUrl($url);
				return false;
				break;
		}
	}

    public function preSave() {
		$eqLogic = $this->getEqLogic();
		switch ($eqLogic->getConfiguration('type', '')) {
			case "carte":
				break;
			case "bouton":
				break;
			case "temperature":
				break;
			case "relai":
				break;
			case "compteur":
				break;
			case "teleinfo":
				break;
			case "pince":
				break;
			case "analogique":
				if ( $this->getLogicalId() == 'reel' ) {
					$this->setValue('');
					$calcul = $this->getConfiguration('calcul');
					preg_match_all("/#([0-9]*)#/", $calcul, $matches);
					$value = '';
					foreach ($matches[1] as $cmd_id) {
						if (is_numeric($cmd_id)) {
							$cmd = self::byId($cmd_id);
							if (is_object($cmd) && $cmd->getType() == 'info') {
								$value .= '#' . $cmd_id . '#';
								break;
							}
						}
					}
					$this->setConfiguration('calcul', $calcul);
					
					$this->setValue($value);
				}
				break;
		}
    }

    public function imperihomeCmd() {
 		if ( $this->getLogicalId() == 'reel' ) {
			return true;
		}
 		if ( $this->getLogicalId() == 'state' ) {
			return true;
		}
		elseif ( $this->getLogicalId() == 'impulsion' ) {
			return true;
		}
		elseif ( $this->getLogicalId() == 'commute' ) {
			return true;
		}
		else {
			return false;
		}
    }
}
?>

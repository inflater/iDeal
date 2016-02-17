<?php
/*
   Vmachine - 아이템 판매기
*/

namespace inflater\iDeal\event;

use pocketmine\plugin\PluginBase; //used
use pocketmine\event\Listener; //used
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;
use inflater\iDeal\iDeal;

class VmachineEvent implements Listener {
	private $plugin;

	public function __construct(iDeal $plugin) {
		$this->plugin = $plugin;
	}

	public function install($sender, Array $args) {
		// <---- Command-Part ---->
		if(count($args)==1) {
			$this->plugin->sMsg($sender, 'vmachine-install-0', 2);
			$this->plugin->sMsg($sender, 'vmachine-install-0_1', 2);
			$this->plugin->sMsg($sender, 'vmachine-install-1', 2, null, [$this->plugin->setting['vmachine_install-price']]);
			return;
		}
		// <---- Work-Part ---->
		$senderN = strtolower($sender->getName());
		if($args[1]!='y') { return; }
		if($this->plugin->EconomyAPI->myMoney($sender) < $this->plugin->setting['vmachine_install-price']) {
			$this->plugin->sMsg($sender, 'vmachine-install-5', 2, TextFormat::RED); return;
		}
		$this->plugin->sMsg($sender, 'vmachine-install-2', 2);
		$this->plugin->sMsg($sender, 'vmachine-install-2_'.$this->plugin->setting['vmachine_create-type'], 2);
		$this->plugin->data[$senderN]['vmachine']['installing'] = true;
	}

	public function replace($sender, Array $args) {
		// <---- Command-Part ---->
		if(count($args)!=3) { $this->plugin->sMsg($sender, 'vmachine-replace-usage', 2); return; }
		// <---- Work-Part ---->
		$senderN = strtolower($sender->getName());		
		$cloudid = (int) $args[1];
		$price = (int) $args[2];
		if(!isset($this->plugin->itemCloud['upload'][$senderN][$cloudid])) {
			$this->plugin->sMsg($sender, 'vmachine-replace-0', 2, TextFormat::RED);
			return;
		}
		$cloudItemCode = $this->plugin->itemCloud['upload'][$senderN][$cloudid]['item'];
		$banItem = explode(',', str_replace(" ", "", $this->plugin->setting['vmachine_ban-item']));
		foreach($banItem as $item) {
			if(!strpos($item, ':')) { $item .= ":0"; }
			if($item==$cloudItemCode) { $this->plugin->sMsg($sender, 'vmachine-replace-fail-0', 2, TextFormat::RED); return; }
		}
		$this->plugin->data[$senderN]['vmachine']['replace']['cloudID'] = $cloudid;
		$this->plugin->data[$senderN]['vmachine']['replace']['price'] = $price;
		$this->plugin->sMsg($sender, 'vmachine-replace-1', 2, TextFormat::GREEN);
		$this->plugin->sMsg($sender, 'vmachine-replace-1_1', 2, TextFormat::GREEN);
	}

	public function activation($sender, Array $args) {
		// <---- Command-Part ---->
		if(count($args)!=2) { $this->plugin->sMsg($sender, 'vmachine-activation-usage', 2); return; }
		// <---- Work-Part ---->
		$senderN = strtolower($sender->getName());
		if($args[1]=='on') {
			$this->plugin->data[$senderN]['vmachine']['activation']['on'] = true;
			$this->plugin->sMsg($sender, 'vmachine-activation-0', 2, TextFormat::GREEN);
		}else{
			$this->plugin->data[$senderN]['vmachine']['activation']['off'] = true;
			$this->plugin->sMsg($sender, 'vmachine-deactivated-0', 2, TextFormat::GREEN);
		}
	}

	public function withdrawal($sender) {
		$senderN = strtolower($sender->getName());
		$this->plugin->data[$senderN]['vmachine']['withdrawal'] = true;
		$this->plugin->sMsg($sender, 'vmachine-withdrawal-0', 2);
	}

	public function remove($sender) {
		$senderN = strtolower($sender->getName());
		$this->plugin->sMsg($sender, 'vmachine-remove-1', 2);
		$this->plugin->sMsg($sender, 'vmachine-remove-1_1', 2);
		$this->plugin->sMsg($sender, 'vmachine-remove-2', 2);
		$this->plugin->data[$senderN]['vmachine']['remove'] = true;
	}

	public function buy($sender, Array $args) {
		// <---- Command-Part ---->
		if(count($args)!=2) { $this->plugin->sMsg($sender, 'vmachine-buy-usage', 2); return; }
		// <---- Work-Part ---->
		
		$senderN = strtolower($sender->getName());
		$levelN = $sender->getLevel()->getName();
		if(!isset($this->plugin->data[$senderN]['vmachine']['buy'])) { $this->plugin->sMsg($sender, 'vmachine-buy-fail-2', 2, TextFormat::RED); return; }
		$vpos = $this->plugin->data[$senderN]['vmachine']['buy']['vpos'];
		$time = $this->plugin->data[$senderN]['vmachine']['buy']['time'];
		if($time < time()-15) {
			$this->plugin->sMsg($sender, 'vmachine-buy-fail-3', 2, TextFormat::RED);
			unset($this->plugin->data[$senderN]['vmachine']); return;
		}
		$owner = $this->plugin->data[$senderN]['vmachine']['buy']['owner'];
		$cloudid = $this->plugin->data[$senderN]['vmachine']['buy']['cloudid'];
		$count = $this->plugin->itemCloud['upload'][$owner][$cloudid]['count'];
		$price = $this->plugin->VMachine['vmachine'][$levelN][$vpos]['price'];

		if($args[1]>$count) { // 매물이 충분한지 확인
			$this->plugin->sMsg($sender, 'vmachine-buy-fail-4', 2, TextFormat::RED, [$count]); return;
		}
		if($this->plugin->EconomyAPI->myMoney($sender) < $price*$args[1]) { // 돈이 충분한지 확인
			$this->plugin->sMsg($sender, 'vmachine-buy-fail-5', 2, TextFormat::RED); return;
		}
		$cloudItemCode = $this->plugin->itemCloud['upload'][$owner][$cloudid]['item'];
		$cloudItem = explode(':', $cloudItemCode);
		$banItem = explode(',', str_replace(" ", "", $this->plugin->setting['vmachine_ban-item']));
		foreach($banItem as $item) {
			if(!strpos($item, ':')) { $item .= ":0"; }
			if($item==$cloudItemCode) { $this->plugin->sMsg($sender, 'vmachine-buy-fail-6', 2, TextFormat::RED); return; }
		}
		$this->plugin->EconomyAPI->reduceMoney($sender, $price*$args[1]);
		$this->plugin->VMachine['vmachine'][$levelN][$vpos]['profits'] += $price*$args[1];
		$sender->getInventory()->addItem(Item::get($cloudItem[0], $cloudItem[1], $args[1]));
		$this->plugin->itemCloud['upload'][$owner][$cloudid]['count'] -= $args[1];

		if($this->plugin->itemCloud['upload'][$owner][$cloudid]['count']<1) { // 매물이 0개일때 판매기 비활성화후, 엔티티를 제거
			$this->plugin->event->removeItemEntity($vpos);
			$this->plugin->VMachine['vmachine']["{$sender->getLevel()->getName()}"][$vpos]['sale'] = false;
			$this->plugin->VMachine['vmachine']["{$sender->getLevel()->getName()}"][$vpos]['cloudID'] = false;
			unset($this->plugin->itemCloud['upload'][$owner][$cloudid]);
			unset($this->plugin->itemCloud['upload'][$owner]['items'][$cloudid]);
			$this->plugin->itemCloud['data'][$owner]['uploads']--;
		}	
		$this->saveData();
		$this->plugin->itemCloudConfig->setAll($this->plugin->itemCloud);
		$this->plugin->itemCloudConfig->save();
		unset($this->plugin->data[$senderN]['vmachine']);
		$this->plugin->sMsg($sender, 'vmachine-buy-3', 2, null, [$this->plugin->getItemName($cloudItemCode), $args[1], $price*$args[1]]);
		$this->plugin->sMsg($sender, 'vmachine-buy-4', 2, null, [$this->plugin->EconomyAPI->myMoney($sender)]);
	}

	public function workCancel($sender) {
		$senderN = strtolower($sender->getName());
		unset($this->plugin->data[$senderN]['vmachine']);
		$this->plugin->sMsg($sender, 'vmachine-work-cancelled', 2, TextFormat::RED);
	}

	public function onSignChange(SignChangeEvent $ev) {
		$block = $ev->getBlock();
		$level = $block->getLevel();
		$sender = $ev->getPlayer();
		$senderN = strtolower($sender->getName());
		if(isset($this->plugin->data[$senderN]['vmachine']['installing'])&&$this->plugin->setting['vmachine_create-type']==0) {
			if($ev->getLine(0) == "판매기설치"){
				$level->setBlock(new Vector3($block->x, $block->y+1, $block->z), Block::get(20), true);
				$this->plugin->sMsg($sender, 'vmachine-install-4', 2);
				unset($this->plugin->data[$senderN]['vmachine']);
				$this->saveYML($sender, $block->x.'.'.($block->y+1).'.'.$block->z);
				$this->plugin->EconomyAPI->reduceMoney($sender, $this->plugin->setting['vmachine_install-price']);
				$ev->setLine(0, TextFormat::DARK_GREEN.'<판매기설치완료>');
				$ev->setLine(1, TextFormat::DARK_GREEN.'본 표지판은 파괴하');
				$ev->setLine(2, TextFormat::DARK_GREEN.'셔도 됩니다.');
			}
		}
	}

	public function saveYML($sender, $pos, $set = [false, false, false]) {
		$levelN = $sender->getLevel()->getName();
		$this->plugin->VMachine['vmachine'][$levelN][$pos]['location'] = $pos;
		$this->plugin->VMachine['vmachine'][$levelN][$pos]['owner'] = strtolower($sender->getName());
		$this->plugin->VMachine['vmachine'][$levelN][$pos]['cloudID'] = $set[0];
		$this->plugin->VMachine['vmachine'][$levelN][$pos]['sale'] = $set[1];
		$this->plugin->VMachine['vmachine'][$levelN][$pos]['price'] = $set[2];
		$this->plugin->VMachine['vmachine'][$levelN][$pos]['profits'] = 0;
		$this->plugin->VMachine['vmachine'][$levelN][$pos]['createdate'] = date('Y-m-d H:i:s');
		$this->plugin->VMachine['vmachines'][$levelN][$pos] = false;
		$this->saveData();
	}

	public function onPlayerInteract(PlayerInteractEvent $ev) {
		$sender = $ev->getPlayer();
		$senderN = strtolower($sender->getName());
		$block = $ev->getBlock();
		$bpos = $block->x.'.'.$block->y.'.'.$block->z;
		$levelN = $block->getLevel()->getName();
		if(isset($this->plugin->data[$senderN]['vmachine']['installing'])) { // 판매기 설치
			if($this->plugin->setting['vmachine_create-type'] === 1) {
				$block = $block;
				$block = $block->getSide(1);
				$block->getLevel()->setBlock($block, Block::get(Item::GLASS), true);
			}elseif($this->plugin->setting['vmachine_create-type'] === 2 && $block->getId() === Item::GLASS) {
				$this->plugin->installVmachine($sender, $block);
			}else{ return; }
			$this->plugin->sMsg($sender, 'vmachine-install-4', 2);
			unset($this->plugin->data[$senderN]['vmachine']);
			$this->plugin->installVmachine($sender, $block);
			$this->plugin->EconomyAPI->reduceMoney($sender, $this->plugin->setting['vmachine_install-price']);
			$ev->setCancelled();
			return;
		}elseif(isset($this->plugin->data[$senderN]['vmachine']['replace'])) { // 판매기 매물변경
			$ev->setCancelled();
			if(!isset($this->plugin->VMachine['vmachine'][$levelN][$bpos])) { return; }
			if($senderN==$this->plugin->VMachine['vmachine'][$levelN][$bpos]['owner'] || $sender->isOp()) {
				$this->plugin->VMachine['vmachine'][$levelN][$bpos]['cloudID'] = $this->plugin->data[$senderN]['vmachine']['replace']['cloudID'];
				$this->plugin->VMachine['vmachine'][$levelN][$bpos]['price'] = $this->plugin->data[$senderN]['vmachine']['replace']['price'];
				$this->plugin->VMachine['vmachines'][$levelN][$bpos] = $this->plugin->itemCloud['upload'][$senderN][$this->plugin->data[$senderN]['vmachine']['replace']['cloudID']]['item'];
				$this->saveData();

				$vpos = $this->plugin->VMachine['vmachine'][$levelN][$bpos]['location'];
				$icpos = explode(".", $vpos);
				$item = explode(":", $this->plugin->itemCloud['upload'][$senderN][$this->plugin->data[$senderN]['vmachine']['replace']['cloudID']]['item']);
				if(isset($item[1])) { $item = Item::get($item[0], $item[1]); }else{ $item = Item::get($item); }
				unset($this->plugin->data[$senderN]['vmachine']);
				
				$this->plugin->event->removeItemEntity($vpos);
				$this->plugin->event->createVmachineEntity($this->plugin->getServer()->getInstance()->getOnlinePlayers());
				$this->plugin->sMsg($sender, 'vmachine-replace-2', 2);
				return;
			}else{
				$this->plugin->sMsg($sender, 'vmachine-please-touch-own-vmachine', 2, TextFormat::RED);
				return;
			}
		}elseif(isset($this->plugin->data[$senderN]['vmachine']['activation'])) { // 판매기 활성화
			if(!isset($this->plugin->VMachine['vmachine'][$levelN][$bpos])) { return; }
			$ev->setCancelled();
			if($senderN!=$this->plugin->VMachine['vmachine'][$levelN][$bpos]['owner']&&!$sender->isOp()) {
				$this->plugin->sMsg($sender, 'vmachine-please-touch-own-vmachine', 2, TextFormat::RED);
				return;
			}
			if(isset($this->plugin->data[$senderN]['vmachine']['activation']['on'])) {
				if($this->plugin->VMachine['vmachine'][$levelN][$bpos]['sale']===true) {
					if($this->plugin->VMachine['vmachine'][$levelN][$bpos]['cloudID']===false) {
						$this->plugin->sMsg($sender, 'vmachine-activation-1_2', 2, TextFormat::RED);
						return;
					}
					$this->plugin->sMsg($sender, 'vmachine-activation-1', 2, TextFormat::RED);
					return;
				}
				$this->plugin->VMachine['vmachine'][$levelN][$bpos]['sale']=true;
				$this->plugin->sMsg($sender, 'vmachine-activation-2', 2);
			}else{
				if($this->plugin->VMachine['vmachine'][$levelN][$bpos]['sale']===false) {
					$this->plugin->sMsg($sender, 'vmachine-deactivated-1', 2, TextFormat::RED);
					return;
				}
				$this->plugin->VMachine['vmachine'][$levelN][$bpos]['sale']=false;
				$this->plugin->sMsg($sender, 'vmachine-deactivated-2', 2);
			}
			unset($this->plugin->data[$senderN]['vmachine']);
			$this->saveData();
			return;
		}elseif(isset($this->plugin->VMachine['vmachine'][$levelN][$bpos])) { // 판매기 터치
			$ev->setCancelled();
			if($senderN==$this->plugin->VMachine['vmachine'][$levelN][$bpos]['owner']) {
				if(isset($this->plugin->data[$senderN]['vmachine']['withdrawal'])) {
					if($this->plugin->VMachine['vmachine'][$levelN][$bpos]['profits']<1) {
						$this->plugin->sMsg($sender, 'vmachine-withdrawal-fail-0', 2, TextFormat::RED);
						unset($this->plugin->data[$senderN]['vmachine']);
						return;
					}
					$this->plugin->sMsg($sender, 'vmachine-withdrawal-1', 2);
					$this->plugin->sMsg($sender, 'vmachine-withdrawal-2', 2, null, array($this->plugin->VMachine['vmachine'][$levelN][$bpos]['profits']));
					$this->plugin->EconomyAPI->addMoney($sender, $this->plugin->VMachine['vmachine'][$levelN][$bpos]['profits']);
					$this->plugin->VMachine['vmachine'][$levelN][$bpos]['profits'] = 0;
					$this->saveData();
					unset($this->plugin->data[$senderN]['vmachine']);
					return;
				}
				if($this->plugin->VMachine['vmachine'][$levelN][$bpos]['cloudID']===false) {
					$this->plugin->sMsg($sender, 'vmachine-info-owner-4', 2, TextFormat::RED);
					$this->plugin->sMsg($sender, 'vmachine-info-owner-4_1', 2, TextFormat::RED);
					return;
				}
				if($this->plugin->VMachine['vmachine'][$levelN][$bpos]['sale']===false) {
					$this->plugin->sMsg($sender, 'vmachine-info-owner-3', 2, TextFormat::RED);
					$this->plugin->sMsg($sender, 'vmachine-info-owner-3_1', 2, TextFormat::RED);
					$this->plugin->event->removeItemEntity($bpos);
					return;
				}
				if(!isset( $this->plugin->itemCloud['upload'][$senderN][$this->plugin->VMachine['vmachine'][$levelN][$bpos]['cloudID']])) {
					$this->plugin->VMachine['vmachine'][$levelN][$bpos]['cloudID'] = false;
					$this->saveData();
					$this->plugin->event->removeItemEntity($bpos);
					return;
				}

				$this->plugin->sMsg($sender, 'vmachine-info-owner-0', 2, null, array($this->plugin->getItemName($this->plugin->itemCloud['upload'][$senderN][$this->plugin->VMachine['vmachine'][$levelN][$bpos]['cloudID']]['item']),$this->plugin->VMachine['vmachine'][$levelN][$bpos]['price']));
				$this->plugin->sMsg($sender, 'vmachine-info-owner-1', 2, null, array($this->plugin->VMachine['vmachine'][$levelN][$bpos]['profits']));
				$this->plugin->sMsg($sender, 'vmachine-info-owner-2', 2, null, array($this->plugin->itemCloud['upload'][$senderN][$this->plugin->VMachine['vmachine'][$levelN][$bpos]['cloudID']]['count']));
				return;
			}else{
				if($this->plugin->VMachine['vmachine']["{$block->getLevel()->getName()}"][$bpos]['sale']===false) {
					$this->plugin->sMsg($sender, 'vmachine-buy-0', 2, TextFormat::RED);
					return;
				}
				if($this->plugin->VMachine['vmachine']["{$block->getLevel()->getName()}"][$bpos]['cloudID']===false) {
					$this->plugin->sMsg($sender, 'vmachine-buy-1', 2, TextFormat::RED);
					return;
				}
				$cloudid = $this->plugin->VMachine['vmachine'][$levelN][$bpos]['cloudID'];
				$price = $this->plugin->VMachine['vmachine'][$levelN][$bpos]['price'];
				$owner = $this->plugin->VMachine['vmachine'][$levelN][$bpos]['owner'];
				if(!isset($this->plugin->itemCloud['upload'][$owner][$cloudid])) {
					$this->plugin->VMachine['vmachine'][$levelN][$bpos]['cloudID'] = false;
					$this->plugin->VMachine['vmachine'][$levelN][$bpos]['sale'] = false;
					$this->plugin->VMachine['vmachine'][$levelN][$bpos]['price'] = false;
					$this->plugin->event->removeItemEntity($bpos);
					$this->saveData();
					return;
				}
				$count = $this->plugin->itemCloud['upload'][$owner][$cloudid]['count'];
				
				$this->plugin->sMsg($sender, 'vmachine-buy-2', 2, null, array($owner));
				$this->plugin->sMsg($sender, 'vmachine-buy-2_1', 2, null, array($this->plugin->getItemName($this->plugin->itemCloud['upload'][$owner][$cloudid]['item'])));
				$this->plugin->sMsg($sender, 'vmachine-buy-2_2', 2, null, array($price, $count));
				$this->plugin->sMsg($sender, 'vmachine-buy-2_3', 2);

				$this->plugin->data[$senderN]['vmachine']['buy']['vpos'] = $bpos;
				$this->plugin->data[$senderN]['vmachine']['buy']['owner'] = $owner;
				$this->plugin->data[$senderN]['vmachine']['buy']['cloudid'] = $cloudid;
				$this->plugin->data[$senderN]['vmachine']['buy']['time'] = time();
				return;
			}
			return;
		}
	}

	public function onBlockBreak(BlockBreakEvent $ev) {
		$sender = $ev->getPlayer();
		$senderN = strtolower($sender->getName());
		$block = $ev->getBlock();
		$levelN = $block->getLevel()->getName();
		$pos = $block->x.'.'.$block->y.'.'.$block->z;
		if(isset($this->plugin->VMachine['vmachine'][$levelN][$pos])) {
			if($senderN==$this->plugin->VMachine['vmachine'][$levelN][$pos]['owner'] || $sender->isOp()) {
				if(!isset($this->plugin->data[$senderN]['vmachine']['remove'])) {
					$this->plugin->sMsg($sender, 'vmachine-remove-0', 2, TextFormat::RED);
					$ev->setCancelled();
					return;
				}
				$vpos = $this->plugin->VMachine['vmachine'][$levelN][$pos]['location'];
				unset($this->plugin->VMachine['vmachine'][$levelN][$pos]);
				unset($this->plugin->VMachine['vmachines'][$levelN][$pos]);
				$this->saveData();
				$this->plugin->event->removeItemEntity($vpos);
				$this->plugin->sMsg($sender, 'vmachine-remove-3', 2);
			}else{
				$this->plugin->sMsg($sender, 'vmachine-remove-0_1', 2, TextFormat::RED);
				$ev->setCancelled();
			}
		}
	}

	public function saveData() {
		$this->plugin->VMachineConfig->setAll($this->plugin->VMachine);
		$this->plugin->VMachineConfig->save();
	}
}
?>
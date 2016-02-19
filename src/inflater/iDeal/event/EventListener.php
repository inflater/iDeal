<?php

namespace inflater\iDeal\event;

use pocketmine\plugin\PluginBase; //used
use pocketmine\event\Listener; //used
use pocketmine\utils\Utils;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\Server;
use pocketmine\command\PluginCommand;
use inflater\iDeal\iDeal;

class EventListener implements Listener {
	private $plugin;

	public function __construct(iDeal $plugin) {
		$this->plugin = $plugin;
	}

	public function createVmachineEntity(array $onlinePlayers) {
		if(!isset($this->plugin->VMachine['vmachines'])) { return; };
		foreach($onlinePlayers as $player) {
			$playerN = strtolower($player->getName());
			foreach($this->plugin->VMachine['vmachines']["{$player->getLevel()->getName()}"] as $vpos => $item) {
				if($this->plugin->VMachine['vmachine']["{$player->getLevel()->getName()}"][$vpos]['cloudID']===false) { continue; }
				if(!isset($this->plugin->ItemPacket[$playerN][$vpos])) {
					$icpos = explode(".", $vpos);
					$item = explode(":", $item);
					if(isset($item[1])) { $item = Item::get((int) $item[0], (int) $item[1]); }else{ $item = Item::get((int) $item[0]); }
					$this->plugin->ItemPacket[$playerN][$vpos] = Entity::$entityCount++;
					$this->plugin->packet["AddItemEntity"]->eid = $this->plugin->ItemPacket[$playerN][$vpos];
					$this->plugin->packet["AddItemEntity"]->item = $item;
					$this->plugin->packet["AddItemEntity"]->x = $icpos[0] + 0.5;
					$this->plugin->packet["AddItemEntity"]->y = $icpos[1];
					$this->plugin->packet["AddItemEntity"]->z = $icpos[2] + 0.5;
					$player->directDataPacket($this->plugin->packet["AddItemEntity"]);
				}
			}
		}
	}

	public function createShopEntity(array $players) {
		if(!isset($this->plugin->shop)) { return; };
		foreach($players as $player) {
			$playerN = strtolower($player->getName());
			if(!isset($this->plugin->shop["{$player->getLevel()->getName()}"])) { return; }
			foreach($this->plugin->shop["{$player->getLevel()->getName()}"] as $pos => $item) {
				if(!isset($this->plugin->ItemPacket[$playerN][$pos])) {
					$icpos = explode(".", $pos);
					$item = $this->plugin->shop["{$player->getLevel()->getName()}"][$pos]['item'];
					$item = explode(":", $item);
					if(isset($item[1])) { $item = Item::get((int) $item[0], (int) $item[1]); }else{ $item = Item::get((int) $item[0]); }
					$this->plugin->ItemPacket[$playerN][$pos] = Entity::$entityCount++;
					$this->plugin->packet["AddItemEntity"]->eid = $this->plugin->ItemPacket[$playerN][$pos];
					$this->plugin->packet["AddItemEntity"]->item = $item;
					$this->plugin->packet["AddItemEntity"]->x = $icpos[0] + 0.5;
					$this->plugin->packet["AddItemEntity"]->y = $icpos[1];
					$this->plugin->packet["AddItemEntity"]->z = $icpos[2] + 0.5;
					$player->directDataPacket($this->plugin->packet["AddItemEntity"]);
				}
			}
		}
	}

	public function removeItemEntity($pos) {
		foreach(Server::getInstance()->getOnlinePlayers() as $player) {
			if(isset($this->plugin->ItemPacket[strtolower($player->getName())][$pos])) {
				$this->plugin->packet["RemoveEntity"]->eid = $this->plugin->ItemPacket[strtolower($player->getName())][$pos];
				$player->directDataPacket($this->plugin->packet["RemoveEntity"]);
				unset($this->plugin->ItemPacket[strtolower($player->getName())][$pos]);
			}
		}
	}

	public function InitYML() {
		$this->plugin->message = (new Config($this->plugin->getDataFolder()."message.yml", Config::YAML, array(
			"safedeal-default-mark" => "[ 안전거래 ]",
			"safedeal-command-usage" => "/거래 <요청/상태/수락/거절/취소/수신>",
			"safedeal-request-usage" => "/거래 요청 <대상닉네임> <아이템개수> <총가격>",
			"safedeal-request-description" => "들고있는 아이템으로 거래(판매)를 요청합니다.",
			"safedeal-request-fail-0" => "이미 진행중인 거래가 있습니다 ! ( /거래 취소 로 거래취소 가능 )",
			"safedeal-request-fail-1" => "본인에게 거래를 요청할 수 없습니다.",
			"safedeal-request-fail-2" => "존재하지 않는 플레이어이거나 오프라인 유저입니다.",
			"safedeal-request-fail-3" => "거래요청을 차단중인 유저입니다.",
			"safedeal-request-fail-4" => "이미 거래중인 유저입니다.",
			"safedeal-request-fail-5" => "거래하실 아이템을 들어주세요 !",
			"safedeal-request-fail-6" => "거래가 불가능한 아이템입니다.",
			"safedeal-request-fail-7" => "소유하신 아이템 개수가 부족합니다. ( 소유중인 개수 : [[0]]개 )",
			"safedeal-request-fail-8" => "가격은 0[[Monetary-Unit]] 이상으로 정해주셔야합니다.",
			"safedeal-request-0" => "[[0]]님께 [[1]]아이템 [[2]]개를 [[3]][[Monetary-Unit]](으)로 거래를 요청하였습니다.",
			"safedeal-request-1" => "[[0]]님이 [[1]]아이템 [[2]]개를 [[3]][[Monetary-Unit]](으)로 당신에게 판매하시길원합니다.",
			"safedeal-request-2" => "/거래 수락 및 /거래 거절 을 통해 거래를 진행해주세요.",
			"safedeal-state-0" => "====== 거래상태 ======",
			"safedeal-state-1" => "========= =========",
			"safedeal-state-2" => "[[0]]님께 거래를 요청",
			"safedeal-state-3" => "아이템 : [[0]]",
			"safedeal-state-4" => "개수 : [[0]]개",
			"safedeal-state-5" => "가격 : [[0]][[Monetary-Unit]]",
			"safedeal-state-6" => "받거나 보낸 거래요청이 없습니다.",
			"safedeal-accept-0" => "거래가 무사히 성사되었습니다.",
			"safedeal-accept-1" => "인벤토리창을 확인해보세요 !",
			"safedeal-accept-2" => "자신의 돈을 확인해보세요 !",
			"safedeal-accept-fail-0" => "받은 거래요청이 없습니다.",
			"safedeal-accept-fail-1" => "거래요청자가 서버에서 나가, 거래를 진행할 수 없습니다.",
			"safedeal-accept-fail-2" => "아이템이 부족하여 거래를 진행할 수 없습니다.",
			"safedeal-accept-fail-3" => "돈이 부족하여 거래를 진행할 수 없습니다.",
			"safedeal-accept-fail-4" => "상대방의 돈이 부족하여 거래를 진행할 수 없습니다.",
			"safedeal-accept-fail-5" => "상대방의 아이템이 부족하여 거래를 진행할 수 없습니다.",
			"safedeal-cancelled-fail-0" => "받은 거래요청이 없습니다.",
			"safedeal-cancelled-0" => "거래를 취소하였습니다.",
			"safedeal-cancelled-1" => "상대방이 거래를 취소하였습니다.",
			"safedeal-rejected-fail-0" => "받은 거래요청이 없습니다.",
			"safedeal-rejected-0" => "거래를 거절하였습니다.",
			"safedeal-rejected-1" => "[[0]]님이 거래를 거절하였습니다.",
			"safedeal-receive-0" => "수신을 차단하였습니다.",
			"safedeal-receive-1" => "수신차단을 해제하였습니다.",
			"safedeal-deal-cancelled" => "거래가 취소되었습니다.",
			
			"itemcloud-default-mark" => "[ 클라우드 ]",
			"itemcloud-command-usage" => "/클라우드 <업로드/다운로드/추가/제거/목록/확장>",
			"itemcloud-upload-usage" => "/클라우드 업로드 <개수>",
			"itemcloud-upload-description" => "현재 들고있는 아이템을 클라우드에 업로드합니다.",
			"itemcloud-upload-0" => "업로드하실 아이템을 들어주세요 !",
			"itemcloud-upload-1" => "소유하신 아이템 개수가 부족합니다. ( [[0]]개 )",
			"itemcloud-upload-2" => "클라우드에 아이템을 정상적으로 업로드하였습니다 ! ( [[0]] , [[1]]개 , 항목번호 : [[2]] )",
			"itemcloud-upload-fail-0" => "업로드가 불가능한 아이템입니다.",
			"itemcloud-upload-fail-1" => "클라우드의 용량이 부족합니다. ( /클라우드 확장 )",
			"itemcloud-download-usage" => "/클라우드 다운로드 <항목번호>",
			"itemcloud-download-description" => "클라우드에 업로드한 아이템을 다운로드합니다.",
			"itemcloud-download-fail-0" => "존재하지 않는 항목입니다.",
			"itemcloud-download-0" => "클라우드에서 아이템을 성공적으로 다운로드하였습니다 ! ( [[0]] , [[1]]개 )",
			"itemcloud-download-1" => "인벤토리창을 확인해보세요 !",
			"itemcloud-add-usage" => "/클라우드 추가 <유저명> <아이템[:데미지]> [개수]",
			"itemcloud-remove-fail-0" => "존재하지 않는 항목입니다.",
			"itemcloud-remove-0" => "클라우드에서 해당 아이디의 아이템을 삭제하였습니다.",
			"itemcloud-list" => "=======클라우드 리스트 ( [[0]] / [[1]] )=======",
			"itemcloud-list2" => "[ID]  아이템    업로드 날짜",
			"itemcloud-list-item" => "[ [[0]] ] [[1]] [[2]]개   ( [[3]] )",
			"itemcloud-list-0" => "업로드된 아이템이 없습니다.",
			"itemcloud-list-1" => "대상 유저를 찾을 수 없습니다.",
			"itemcloud-list-2" => "본 페이지에는 업로드된 아이템이 없습니다.",
			"itemcloud-expand-0" => "돈이 부족하여, 확장을 진행할 수 없습니다. ( 확장비 : [[0]][[Monetary-Unit]] )",
			"itemcloud-expand-1" => "클라우드 확장을 진행하시겠습니까?",
			"itemcloud-expand-2" => "확장을 진행하시려면 /클라우드 확장 을, 취소하시려면 /클라우드 작업취소 를 입력해주세요.",
			"itemcloud-expand-3" => "확장비 : [[0]] , 확장용량 : [[1]]",
			"itemcloud-expand-4" => "클라우드 용량 확장을 성공하였습니다. ( [[0]] => [[1]] )",
			"itemcloud-work-cancelled" => "진행하시려던 모든 클라우드 작업을 취소하였습니다.",
			
			"vmachine-default-mark" => "[ 판매기 ]",
			"vmachine-command-usage" => "/판매기 <설치/매물변경/활성화/수익출금/제거>",
			"vmachine-command-usage2" => "<매물구매>",
			"vmachine-install-0" => "판매기를 설치하시겠습니까?",
			"vmachine-install-0_1" => "설치를 계속하시려면 '/판매기 설치 y'를 입력해주세요.",
			"vmachine-install-1" => "판매기를 설치하는데 [[0]][[Monetary-Unit]]가 필요합니다.",
			"vmachine-install-2" => "판매기 설치를 진행합니다.",
			"vmachine-install-2_0" => "표지판 첫번째줄에 '판매기설치' 입력시 표지판 위에 판매기가 자동으로 설치됩니다.",
			"vmachine-install-2_1" => "판매기를 설치하고싶은곳을 터치해주세요.",
			"vmachine-install-2_2" => "판매기로 설정하고싶은 유리를 터치해주세요.",
			"vmachine-install-3" => "설치를 중단하시려면 '/판매기 작업취소'를 입력해주세요.",
			"vmachine-install-3_1" => "설치를 중단하시려면 '/판매기 작업취소'를 입력해주세요.",
			"vmachine-install-4" => "판매기를 설치하였습니다.",
			"vmachine-install-5" => "판매기를 설치하는데에 필요한 돈이 부족합니다.",
			"vmachine-remove-0" => "판매기를 제거하시려면 '/판매기 제거'를 입력해주세요.",
			"vmachine-remove-0_1" => "판매기는 주인만이 제거할 수 있습니다.",
			"vmachine-remove-1" => "판매기를 제거를 진행해주세요.",
			"vmachine-remove-1_1" => "판매기 제거시 설치비용은 돌려받지 않습니다.",
			"vmachine-remove-2" => "제거를 중단하시려면 '/판매기 작업취소'를 입력해주세요.",
			"vmachine-remove-3" => "판매기를 제거하였습니다.",
			"vmachine-withdrawal-0" => "수익을 출금시킬 판매기를 터치해주세요.",
			"vmachine-withdrawal-1" => "수익을 출금하였습니다.",
			"vmachine-withdrawal-2" => "출금액 : [[0]][[Monetary-Unit]]",
			"vmachine-withdrawal-fail-0" => "수익이 나지 않은 판매기입니다.",
			"vmachine-info-owner-0" => "[[0]]을(를) [[1]][[Monetary-Unit]]으로 판매중..",
			"vmachine-info-owner-1" => "수익 : [[0]][[Monetary-Unit]]",
			"vmachine-info-owner-2" => "남은수량 : [[0]]개",
			"vmachine-info-owner-3" => "판매기가 활성화되어있지 않습니다. 비활성시 판매기 이용이 불가능합니다.",
			"vmachine-info-owner-3_1" => "'/판매기 활성화'로 활성화를 시킬 수 있습니다.",
			"vmachine-info-owner-4" => "현재 판매기에 매물이 없습니다.",
			"vmachine-info-owner-4_1" => "'/판매기 매물변경'으로 매물을 변경할 수 있습니다.",
			"vmachine-buy-usage" => "/판매기 매물구매 <수량>",
			"vmachine-buy-0" => "본 판매기는 현재 이용이 불가능합니다.",
			"vmachine-buy-1" => "본 판매기는 아직 매물이 없습니다.",
			"vmachine-buy-2" => "판매자 : [[0]]",
			"vmachine-buy-2_1" => "아이템명 : [[0]]",
			"vmachine-buy-2_2" => "개당가격 : [[0]] , 남은 개수 : [[1]]",
			"vmachine-buy-2_3" => "구매하시려면 15초내로 /판매기 매물구매 <수량> 를 입력해주세요.",
			"vmachine-buy-3" => "[[0]] [[1]]개를 [[2]][[Monetary-Unit]]로 구매하였습니다.",
			"vmachine-buy-4" => "구매후 남은돈 : [[0]]",
			"vmachine-buy-fail-0" => "본 판매기는 현재 이용이 불가능합니다.",
			"vmachine-buy-fail-1" => "본 판매기는 아직 매물이 없습니다.",
			"vmachine-buy-fail-2" => "판매기를 선택해주세요 !",
			"vmachine-buy-fail-3" => "시간이 초과되었습니다. 명령어를 다시 입력해주세요.",
			"vmachine-buy-fail-4" => "판매기의 매물이 부족합니다. ( 매물 수 : [[0]] )",
			"vmachine-buy-fail-5" => "돈이 부족하여, 구매를 진행할 수 없습니다.",
			"vmachine-buy-fail-6" => "구매가 불가능한 아이템입니다.",
			"vmachine-replace-usage" => "/판매기 매물변경 <클라우드ID> <개당가격>",
			"vmachine-replace-0" => "존재하지 않는 클라우드ID입니다.",
			"vmachine-replace-1" => "매물을 변경할 판매기를 터치해주세요.",
			"vmachine-replace-1_1" => "매물변경을 중단하시려면 '/판매기 작업취소'를 입력해주세요.",
			"vmachine-replace-2" => "매물을 변경하였습니다.",
			"vmachine-replace-fail-0" => "매물변경이 불가능한 아이템입니다.",
			"vmachine-activation-usage" => "/판매기 활성화 <on/off>",
			"vmachine-activation-0" => "활성화할 판매기를 터치해주세요.",
			"vmachine-activation-1" => "이미 활성화되어있는 판매기입니다.",
			"vmachine-activation-1_2" => "활성화하려면 매물이 있어야합니다. ( /판매기 매물변경 )",
			"vmachine-activation-2" => "판매기를 활성화하였습니다.",
			"vmachine-deactivated-0" => "비활성화할 판매기를 터치해주세요.",
			"vmachine-deactivated-1" => "이미 비활성화되어있는 판매기입니다.",
			"vmachine-deactivated-2" => "판매기를 비활성화하였습니다.",
			"vmachine-please-touch-own-vmachine" => "본인의 판매기를 터치해주세요 !",
			"vmachine-work-cancelled" => "진행하시려던 모든 판매기 작업을 취소하였습니다.",
			"vmachine-work-already-exists" => "이미 진행중인 작업이 있습니다. ( /판매기 작업취소 )",

			"shop-default-mark" => "[ 상점 ]",
			"shop-command-usage" => "/상점 <생성/정보>",
			"shop-mode-0" => "상점 생성모드로 전환하였습니다.",
			"shop-mode-1" => "매물로 둘 아이템을 들고 유리를터치해주세요.",
			"shop-mode-2" => "일반모드로 전환하였습니다.",
			"shop-setting-0" => "구매가를 채팅창에 명령어형식으로 입력하여 설정해주세요. ( false = 구매불가능 )",
			"shop-setting-1" => "판매가를 채팅창에 명령어형식으로 입력하여 설정해주세요. ( false = 판매불가능 )",
			"shop-setting-2" => "상점을 정상적으로 등록하였습니다.",
			"shop-remove-0" => "상점을 제거하였습니다.",
			"shop-deal-0" => "[[0]]을(를) 구매/판매 하시겠습니까? ( /구매, /판매 )",
			"shop-deal-1" => "구매가 : [[0]], 판매가 : [[1]]",
			"shop-deal-2" => "[[0]]을(를) [[1]][[Monetary-Unit]] 으로 구매 하시겠습니까? ( /구매 )",
			"shop-deal-3" => "[[0]]을(를) [[1]][[Monetary-Unit]] 으로 판매 하시겠습니까? ( /판매 )",
			"shop-deal-fail-0" => "개수를 0개 이상으로 적어주세요.",
			"shop-info-0" => "상점 위치 : [[0]]",
			"shop-info-1" => "구매 횟수 : [[0]]",
			"shop-info-2" => "판매 횟수 : [[0]]",
			"shop-info-fail-0" => "상점을 선택해주세요.",
			"buy-command-usage" => "/구매 <수량>",
			"buy-0" => "[[0]]을(를) [[1]]개를 [[2]][[Monetary-Unit]] 으로 구매하였습니다.",
			"buy-fail-0" => "본 상점에서는 구매가 불가능한 아이템 입니다.",
			"buy-fail-1" => "돈이 부족하여 구매가 불가능합니다.",
			"sell-command-usage" => "/판매 <수량>",
			"sell-0" => "[[0]]을(를) [[1]]개를 [[2]][[Monetary-Unit]] 으로 판매하였습니다.",
			"sell-fail-0" => "본 상점에서는 판매가 불가능한 아이템 입니다.",
			"sell-fail-1" => "아이템이 부족하여 판매가 불가능합니다.",
			
			"auction-default-mark" => "[ 경매 ]",
			"auction-command-usage" => "/경매 <시작> <입찰>",
			"auction-start-usage" => "/경매 시작 <개수> <최저입찰가>",
			"auction-start-0" => "들고있는 아이템으로 경매를 시작합니다. 수수료로 낙찰가의 1/10가 회수됩니다.",
			"auction-start-1" => "경매를 진행하시려면 '/경매 시작 <개수> <최저입찰가> y' 를 입력해주세요.",
			"auction-start-2" => "한번 시작한 경매는 취소할 수 없습니다.",
			"auction-start-3" => "[[0]]님이 [[1]] [[2]]개를 [[3]][[Monetary-Unit]]을 최저입찰가로 경매를 시작하였습니다.",
			"auction-start-4" => "입찰하시려면 '/경매 입찰 <가격>' 을 입력해주세요.",
			"auction-start-fail-0" => "이미 진행중인 경매가 있습니다.",
			"auction-start-fail-1" => "경매가 불가능한 아이템입니다.",
			"auction-start-fail-2" => "갖고계신 아이템개수가 부족합니다.",
			"auction-start-fail-3" => "1개 이상으로 입력해주세요 !",
			"auction-bidding-usage" => "/경매 입찰 <가격>",
			"auction-bidding-0" => "[[0]]님이 [[1]][[Monetary-Unit]]으로 입찰하였습니다. ( 이전 입찰가 : [[2]][[Monetary-Unit]] )",
			"auction-bidding-1" => "입찰하시려면 '/경매 입찰 <가격>' 을 입력해주세요.",
			"auction-successfulBid-0" => "[[0]]초내로 입찰되지않으면 [[1]]님께 [[2]] [[3]]개가 [[4]][[Monetary-Unit]](으)로 낙찰됩니다. ( /경매 입찰 <가격> )",
			"auction-successfulBid-1" => "[[0]]초내로 입찰되지않으면 경매가 취소됩니다. ( /경매 입찰 <가격> )",
			"auction-successfulBid-2" => "[[0]]님께 [[1]] [[2]]개가 [[3]][[Monetary-Unit]](으)로 낙찰되었습니다.",
			"auction-successfulBid-3" => "인벤토리에서 [[0]] [[1]]개를 확인하세요!",
			"auction-successfulBid-4" => "[[0]][[Monetary-Unit]]이 지급되었습니다. 확인해주세요!",
			"auction-successfulBid-fail-0" => "경매 진행자 또는 낙찰자가 오프라인이여서 경매가 취소되었습니다.",
			"auction-successfulBid-fail-1" => "경매 진행자의 아이템개수가 부족하여 경매가 취소되었습니다.",
			"auction-successfulBid-fail-2" => "낙찰자의 돈이 부족하여 경매가 취소되었습니다.",
			"auction-successfulBid-fail-3" => "입찰자가 없어 경매가 취소되었습니다.",
			"auction-bidding-fail-0" => "현재 진행중인 경매가 없습니다.",
			"auction-bidding-fail-1" => "입찰가가 낮습니다. [[0]][[Monetary-Unit]] 이상으로 입찰해주세요. ( 현재 입찰가 : [[1]][[Monetary-Unit]] )",
			"auction-bidding-fail-2" => "돈이 부족합니다. ( 현재 입찰가 : [[0]] / 현재 내돈 : [[1]][[Monetary-Unit]] )",

			"cant-use-command" => "당신은 이 명령어를 사용할 권한이 없습니다",
			"must-use-in-game" => "본 명령어는 인게임 내에서만 사용이 가능합니다."
		)))->getAll();

		if(file_exists($this->plugin->getDataFolder() . "iDeal.yml")) {
			$this->plugin->getLogger()->warning("설정파일이 'iDeal.yml' 입니다. 1.0.7v 이후로 'config.yml' 으로 변경되었습니다.");
			$this->plugin->getLogger()->warning("곧 'iDeal.yml' 파일 로드는 지원이 중단됩니다. 'config.yml' 으로 변경바랍니다.");
			$this->plugin->setting = (new Config($this->plugin->getDataFolder() . "iDeal.yml", Config::YAML))->getAll();
		}else{
			$this->plugin->saveResource("config.yml", false);
			$this->plugin->setting = (new Config ( $this->plugin->getDataFolder() . "config.yml", Config::YAML))->getAll();
		}

		if(!isset(Config::$formats[$this->plugin->setting['saveFormat']])) {
			$this->plugin->getLogger()->error(TextFormat::RED."'{$this->plugin->setting['saveFormat']}' 은(는) 지원하지 않는 저장형식입니다.");
		}else{
			$saveFormat = Config::$formats[$this->plugin->setting['saveFormat']];
			$this->plugin->saveResource("itemName.yml", false);
			$this->plugin->itemName = (new Config ( $this->plugin->getDataFolder () . "itemName.yml", Config::YAML))->getAll ();
			$this->plugin->itemCloudConfig = (new Config ( $this->plugin->getDataFolder () . "itemCloud.".strtolower($this->plugin->setting['saveFormat']), $saveFormat));
			$this->plugin->VMachineConfig = (new Config ( $this->plugin->getDataFolder () . "VendingMachine.".strtolower($this->plugin->setting['saveFormat']), $saveFormat));
			$this->plugin->shopConfig = (new Config ( $this->plugin->getDataFolder () . "shop.".strtolower($this->plugin->setting['saveFormat']), $saveFormat));
			$this->plugin->itemCloud = $this->plugin->itemCloudConfig->getAll();
			$this->plugin->VMachine = $this->plugin->VMachineConfig->getAll();
			$this->plugin->shop = $this->plugin->shopConfig->getAll();
		}
	}

	public function registerCommand($name, $permission, $description = "", $usage = "") {
		$command = new PluginCommand($name, $this->plugin);
		$command->setDescription($description);
		$command->setPermission($permission);
		$command->setUsage($usage);
		$this->plugin->getServer()->getCommandMap()->register($name, $command);
	}

	public function checkUpdate($version) {
		$plugin = json_decode(Utils::getUrl("http://hn.pe.kr/plugin/plugins/iDeal/plugin.php?version={$version}"), true);
		if($plugin['update']) { $this->plugin->getLogger()->notice("iDeal 플러그인의 최신버전이 있습니다. (v{$plugin['latest-version']})"); }
		else{ $this->plugin->getLogger()->notice("현재 최신버전의 iDeal 플러그인을 사용중입니다."); }
	}
}
?>
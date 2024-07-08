<?php

namespace NurAzliYT\PocketEconomy\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;

use NurAzliYT\PocketEconomy\PocketEconomy;

class SetMoneyCommand extends Command
{

    public function __construct(private PocketEconomy $plugin)
    {
        $desc = $plugin->getCommandMessage("setmoney");
        parent::__construct("setmoney", $desc["description"], $desc["usage"]);

        $this->setPermission("pocketeconomy.command.setmoney");

        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $label, array $params): bool
    {
        if (!$this->plugin->isEnabled()) return false;
        if (!$this->testPermission($sender)) {
            return false;
        }

        $player = array_shift($params);
        $amount = array_shift($params);

        if (!is_numeric($amount)) {
            $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
            return true;
        }

        if (($p = $this->plugin->getServer()->getPlayerByPrefix($player)) instanceof Player) {
            $player = $p->getName();
        }

        $result = $this->plugin->setMoney($player, $amount, false, 'pocketeconomy.command.set');
        switch ($result) {
            case PocketEconomy::RET_INVALID:
                $sender->sendMessage($this->plugin->getMessage("setmoney-invalid-number", [$amount], $sender->getName()));
                break;
            case PocketEconomy::RET_NO_ACCOUNT:
                $sender->sendMessage($this->plugin->getMessage("player-never-connected", [$player], $sender->getName()));
                break;
            case PocketEconomy::RET_CANCELLED:
                $sender->sendMessage($this->plugin->getMessage("setmoney-failed", [], $sender->getName()));
                break;
            case PocketEconomy::RET_SUCCESS:
                $sender->sendMessage($this->plugin->getMessage("setmoney-setmoney", [$player, $amount], $sender->getName()));

                if ($p instanceof Player) {
                    $p->sendMessage($this->plugin->getMessage("setmoney-set", [$amount], $p->getName()));
                }
                break;
            default:
                $sender->sendMessage("WTF");
        }
        return true;
    }
}
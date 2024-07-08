<?php

namespace NurAzliYT\PocketEconomy\provider;

use NurAzliYT\PocketEconomy\PocketEconomy;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class YamlProvider implements Provider {
    /**
     * @var Config
     */
    private $config;

    /** @var PocketEconomy */
    private $plugin;

    private $money = [];

    public function __construct(PocketEconomy $plugin) {
        $this->plugin = $plugin;
    }

    public function open() {
        $this->config = new Config($this->plugin->getDataFolder() . "Money.yml", Config::YAML, ["version" => 2, "money" => []]);
        $this->money = $this->config->getAll();
    }

    private function getPlayerName($player): string {
        return strtolower($player instanceof Player ? $player->getName() : $player);
    }

    public function accountExists($player): bool {
        $player = $this->getPlayerName($player);
        return isset($this->money["money"][$player]);
    }

    public function createAccount($player, $defaultMoney = 1000): bool {
        $player = $this->getPlayerName($player);
        if (!isset($this->money["money"][$player])) {
            $this->money["money"][$player] = $defaultMoney;
            return true;
        }
        return false;
    }

    public function removeAccount($player): bool {
        $player = $this->getPlayerName($player);
        if (isset($this->money["money"][$player])) {
            unset($this->money["money"][$player]);
            return true;
        }
        return false;
    }

    public function getMoney($player): float {
        $player = $this->getPlayerName($player);
        return isset($this->money["money"][$player]) ? $this->money["money"][$player] : 0.0;
    }

    public function setMoney($player, $amount): bool {
        $player = $this->getPlayerName($player);
        if (isset($this->money["money"][$player])) {
            $this->money["money"][$player] = round((float)$amount, 2);
            return true;
        }
        return false;
    }

    public function addMoney($player, $amount): bool {
        $player = $this->getPlayerName($player);
        if (isset($this->money["money"][$player])) {
            $this->money["money"][$player] += (float)$amount;
            $this->money["money"][$player] = round($this->money["money"][$player], 2);
            return true;
        }
        return false;
    }

    public function reduceMoney($player, $amount): bool {
        $player = $this->getPlayerName($player);
        if (isset($this->money["money"][$player])) {
            $this->money["money"][$player] -= (float)$amount;
            $this->money["money"][$player] = round($this->money["money"][$player], 2);
            return true;
        }
        return false;
    }

    public function getAll(): array {
        return isset($this->money["money"]) ? $this->money["money"] : [];
    }

    public function save() {
        $this->config->setAll($this->money);
        $this->config->save();
    }

    public function close() {
        $this->save();
    }

    public function getName(): string {
        return "Yaml";
    }
}

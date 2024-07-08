<?php

namespace NurAzliYT\PocketEconomy\provider;

use NurAzliYT\PocketEconomy\PocketEconomy;
use NurAzliYT\PocketEconomy\task\MySQLPingTask;
use NurAzliYT\PocketEconomy\PocketEconomy;
use pocketmine\player\Player;

class MySQLProvider implements Provider {
    
    private $db;

    public function __construct(private PocketEconomy $plugin) {
        $this->plugin = $plugin;
    }

    public function open() {
        $config = $this->plugin->getConfig()->get("provider-settings", []);

        $this->db = new \mysqli(
            $config["host"] ?? "127.0.0.1",
            $config["user"] ?? "onebone",
            $config["password"] ?? "hello_world",
            $config["db"] ?? "PocketEconomy",
            $config["port"] ?? 3306
        );
        if ($this->db->connect_error) {
            $this->plugin->getLogger()->critical("Could not connect to MySQL server: ".$this->db->connect_error);
            return;
        }
        if (!$this->db->query("CREATE TABLE IF NOT EXISTS user_money(
            username VARCHAR(20) PRIMARY KEY,
            money FLOAT
        );")) {
            $this->plugin->getLogger()->critical("Error creating table: " . $this->db->error);
            return;
        }

        $this->plugin->getScheduler()->scheduleRepeatingTask(new MySQLPingTask($this->plugin, $this->db), 600);
    }

    private function getPlayerName($player): string {
        return strtolower($player instanceof Player ? $player->getName() : $player);
    }

    public function accountExists($player): bool {
        $player = $this->getPlayerName($player);

        $result = $this->db->query("SELECT * FROM user_money WHERE username='".$this->db->real_escape_string($player)."'");
        return $result->num_rows > 0;
    }

    public function createAccount($player, float $defaultMoney = 1000.0): bool {
        $player = $this->getPlayerName($player);

        if (!$this->accountExists($player)) {
            $this->db->query("INSERT INTO user_money (username, money) VALUES ('".$this->db->real_escape_string($player)."', $defaultMoney);");
            return true;
        }
        return false;
    }

    public function removeAccount($player): bool {
        $player = $this->getPlayerName($player);

        return $this->db->query("DELETE FROM user_money WHERE username='".$this->db->real_escape_string($player)."'") === true;
    }

    public function getMoney($player): float {
        $player = $this->getPlayerName($player);

        $res = $this->db->query("SELECT money FROM user_money WHERE username='".$this->db->real_escape_string($player)."'");
        $ret = $res->fetch_array()[0] ?? false;
        $res->free();

        return $ret !== false ? (float) $ret : 0.0;
    }

    public function setMoney($player, $amount): bool {
        $player = $this->getPlayerName($player);
        $amount = (float) $amount;

        return $this->db->query("UPDATE user_money SET money = $amount WHERE username='".$this->db->real_escape_string($player)."'") === true;
    }

    public function addMoney($player, $amount): bool {
        $player = $this->getPlayerName($player);
        $amount = (float) $amount;

        return $this->db->query("UPDATE user_money SET money = money + $amount WHERE username='".$this->db->real_escape_string($player)."'") === true;
    }

    public function reduceMoney($player, $amount): bool {
        $player = $this->getPlayerName($player);
        $amount = (float) $amount;

        return $this->db->query("UPDATE user_money SET money = money - $amount WHERE username='".$this->db->real_escape_string($player)."'") === true;
    }

    public function getAll(): array {
        $res = $this->db->query("SELECT * FROM user_money");

        $ret = [];
        foreach ($res->fetch_all() as $val) {
            $ret[$val[0]] = $val[1];
        }

        $res->free();

        return $ret;
    }

    public function getName(): string {
        return "MySQL";
    }

    public function save() {}

    public function close() {
        if ($this->db instanceof \mysqli) {
            $this->db->close();
        }
    }
}

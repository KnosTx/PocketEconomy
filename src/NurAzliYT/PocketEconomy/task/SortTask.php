<?php

namespace NurAzliYT\PocketEconomy\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\player\Player;

use NurAzliYT\PocketEconomy\PocketEconomy;

class SortTask extends AsyncTask
{
    private $max = 0;
    private $topList;

    public function __construct(
        private string $sender, 
        private array $moneyData, 
        private bool $addOp, 
        private int $page, 
        private array $ops, 
        private array $banList
    ) {}

    public function onRun(): void
    {
        $this->topList = serialize((array)$this->getTopList());
    }

    private function getTopList()
    {
        $money = (array)$this->moneyData;
        $banList = (array)$this->banList;
        $ops = (array)$this->ops;
        arsort($money);

        $ret = [];

        $n = 1;
        $this->max = ceil((count($money) - count($banList) - ($this->addOp ? 0 : count($ops))) / 5);
        $this->page = (int)min($this->max, max(1, $this->page));

        foreach ($money as $p => $m) {
            $p = strtolower($p);
            if (isset($banList[$p])) continue;
            if (isset($this->ops[$p]) and $this->addOp === false) continue;
            $current = (int)ceil($n / 5);
            if ($current === $this->page) {
                $ret[$n] = [$p, $m];
            } elseif ($current > $this->page) {
                break;
            }
            ++$n;
        }
        return $ret;
    }

    public function onCompletion(): void
    {
        $server = Server::getInstance();
        $player = null;
        if ($this->sender === "CONSOLE" || ($player = $server->getPlayerExact($this->sender)) instanceof Player) {
            $plugin = PocketEconomy::getInstance();

            $output = ($plugin->getMessage("topmoney-tag", [$this->page, $this->max], $this->sender) . "\n");
            $message = ($plugin->getMessage("topmoney-format", [], $this->sender) . "\n");

            foreach (unserialize($this->topList) as $n => $list) {
                $output .= str_replace(["%1", "%2", "%3"], [$n, $list[0], $list[1]], $message);
            }
            $output = substr($output, 0, -1);

            if ($this->sender === "CONSOLE") {
                $plugin->getLogger()->info($output);
            } elseif ($player !== null) {
                $player->sendMessage($output);
            }
        }
    }
}

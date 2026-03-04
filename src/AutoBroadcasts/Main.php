<?php

declare(strict_types=1);

namespace AutoBroadcasts;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class Main extends PluginBase {

    private int $interval;
    private string $prefix;
    private array $broadcastMessages = [];
    private array $broadcastCommands = [];
    private int $currentIndex = 0;

    protected function onEnable(): void {
        $this->saveDefaultConfig();
        $this->loadConfig();
        $this->startTask();
    }

    private function loadConfig(): void {
        $this->reloadConfig();

        $this->interval = (int)$this->getConfig()->get("internal", 120);
        $this->prefix = (string)$this->getConfig()->get("prefix", "");
        $this->broadcastMessages = (array)$this->getConfig()->get("broadcast-message", []);
        $this->broadcastCommands = (array)$this->getConfig()->get("broadcast-commands", []);
    }

    private function startTask(): void {
        $this->getScheduler()->scheduleRepeatingTask(
            new ClosureTask(function(): void {
                $this->executeCycle();
            }),
            $this->interval * 20
        );
    }

    private function executeCycle(): void {

        // Broadcast rotating message
        if(!empty($this->broadcastMessages)){
            if(!isset($this->broadcastMessages[$this->currentIndex])){
                $this->currentIndex = 0;
            }

            $message = $this->broadcastMessages[$this->currentIndex];
            $this->getServer()->broadcastMessage(
                TextFormat::colorize($this->prefix . $message)
            );

            $this->currentIndex++;
        }

        // Execute commands with custom messages
        foreach($this->broadcastCommands as $data){

            if(!isset($data["command"])) continue;

            $command = (string)$data["command"];
            $customMessage = $data["message"] ?? null;

            // Execute as console
            $this->getServer()->dispatchCommand(
                $this->getServer()->getCommandMap()->getConsoleSender(),
                $command
            );

            // Send custom broadcast message if set
            if($customMessage !== null){
                $this->getServer()->broadcastMessage(
                    TextFormat::colorize($this->prefix . $customMessage)
                );
            }
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {

        if($command->getName() === "broadcast"){

            $messages = $this->getConfig()->get("messages");

            if(!$sender->hasPermission("autobroadcast.use")){
                $sender->sendMessage($messages["no_permission"]);
                return true;
            }

            if(empty($args)){
                $sender->sendMessage($messages["usage"]);
                return true;
            }

            $message = implode(" ", $args);

            $this->getServer()->broadcastMessage(
                TextFormat::colorize($this->prefix . $message)
            );

            return true;
        }

        return false;
    }
}

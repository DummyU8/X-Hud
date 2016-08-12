<?php

namespace hud;


use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\scheduler\PluginTask;
use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener{
    
    public $money;
    public $factions;
    public $count;
    
    public function onEnable(){
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this), 20);
        $this->economy();
        if($this->getConfig()->get("enable-faction-support") == false){
            $this->factions = null;
        }
        else{
            if(strtolower($this->getConfig()->get("faction-plugin")) == "factionspro"){
                $this->factions = $this->getServer()->getPluginManager()->getPlugin("FactionsPro");
            }
            elseif(strtolower($this->getConfig()->get("faction-plugin")) == "factionsunlimited"){
                $this->factions = $this->getServer()->getPluginManager()->getPlugin("FactionsUnlimited");
            }
            else{
                $this->factions = null;
            }
        }
        $this->count = count($this->getConfig()->get("messages"));
        $this->hudOff = new Config($this->getDataFolder() . "hudOff.yml",Config::YAML);
    }
    
    public function economy(){
        if($this->getConfig()->get("economy-plugin") == "economyapi"){
            $this->money = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
            $this->getLogger()->info(TextFormat::GREEN . "Selected Economy Plugin: EconomyAPI");
            return true;
        }
        else if($this->getConfig()->get("economy-plugin") == "economyplus"){
            $this->money = $this->getServer()->getPluginManager()->getPlugin("EconomyPlus");
            $this->getLogger()->info(TextFormat::GREEN . "Selected Economy Plugin: EconomyPlus");
            return true;
        }
        $this->getLogger()->error(TextFormat::RED . "Unknown Economy Plugin! Disabling economy support!");
        $this->getConfig()->set("enable-economy-support", false);
        $this->getConfig()->save();
        $this->money = null;
        return false;
    }
    
    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
        if($cmd->getName() === "hud"){
            $this->toggleHud($sender);
        }
    }
        
    public function getMessage($current, Player $player){
        $messages = $this->getConfig()->get("messages");
        return $this->formatMessage($messages[$current], $player);
    }
    
    public function formatMessage($message, Player $player){
        $message = str_replace("{X}", round($player->getX()), $message);
        $message = str_replace("{Y}", round($player->getY()), $message);
        $message = str_replace("{Z}", round($player->getZ()), $message);
        $message = str_replace("{NAME}", $player->getName(), $message);
        $message = str_replace("{WORLD}", $player->getLevel()->getName(), $message);
        $message = str_replace("{NEXTLINE}", "\n", $message);
        $message = str_replace("{N}", "\n", $message);
        $message = str_replace("{LINE}", "\n", $message);
        $message = str_replace("{PLAYERS}", count($this->getServer()->getOnlinePlayers()), $message);
        $message = str_replace("{MAXPLAYERS}", $this->getServer()->getMaxPlayers(), $message);
        $message = str_replace("{HEALTH}", $player->getHealth(), $message);
        $message = str_replace("{MAXHEALTH}", $player->getMaxHealth(), $message);
        if($this->money != null){
            $message = str_replace("{MONEY}", $this->money->myMoney($player), $message);
        }
        if($this->factions != null){
            if(strtolower($this->getConfig()->get("faction-plugin")) == "factionsunlimited"){
                $message = str_replace("{FACNAME}", $this->factions->getPlayerFaction($player), $message);
                $message = str_replace("{FACPOWER}", $this->factions->getFactionPower($this->factions->getPlayerFaction($player)), $message);
            }
            elseif(strtolower($this->getConfig->get("faction-plugin")) == "factionspro"){
                $message = str_replace("{FACNAME}", $this->factions->getPlayerFaction($player->getName()), $message);
                $message = str_replace("{FACPOWER}", $this->factions->getFactionPower($this->factions->getPlayerFaction($player->getName())), $message);
            }
        }
        return $message;
        
    }
    
    public function isHudOn(Player $player){
        if($this->hudOff->exists($player->getName())){
            return false;
        }else{
            return true;
        }
    }
    
    public function toggleHud(Player $player){
        if($this->isHudOn($player) == true){
            $this->hudOff->set($player->getName(), "1");
            $player->sendMessage(TextFormat::AQUA."Disabled HUD!");
            $this->hudOff->save();
            $this->hudOff->reload();
        }else{
            $this->hudOff->remove($player->getName());
            $player->sendMessage(TextFormat::AQUA."Enabled HUD!");
            $this->hudOff->save();
            $this->hudOff->reload();
        }
    }
}

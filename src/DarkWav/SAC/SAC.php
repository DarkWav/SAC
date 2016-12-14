<?php

namespace DarkWav\SAC;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\Plugin;
use pocketmine\plugin\PluginLoader;
use DarkWav\SAC\Observer;
use DarkWav\SAC\SACTick;

class SAC extends PluginBase
{
  public $Config;
  public $Logger;
  public $PlayerObservers = array();
  public $PlayersToKick   = array();

  public function onEnable()
  {
    $this->getServer()->getScheduler()->scheduleRepeatingTask(new SACTick($this), 1);
    @mkdir($this->getDataFolder());
    $this->saveDefaultConfig();
    $this->saveResource("AntiForceOP.txt");
    $this->saveResource("AntiForceGM.txt");
  
    $Config = $this->getConfig();
    $Logger = $this->getServer()->getLogger();
    $Server = $this->getServer();
    
    $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    $Logger->info(TextFormat::BLUE . "[SAC] > ShadowAntiCheat Activated"            );
    $Logger->info(TextFormat::BLUE . "[SAC] > ShadowAntiCheat v3.1.1 [Shadow]");
  
    if($Config->get("ForceOP"    )) $Logger->info(TextFormat::BLUE."[SAC] > Enabling AntiForceOP"    );
    if($Config->get("NoClip"     )) $Logger->info(TextFormat::BLUE."[SAC] > Enabling AntiNoClip"     );
    if($Config->get("Fly"        )) $Logger->info(TextFormat::BLUE."[SAC] > Enabling AntiFly"        );
    if($Config->get("Glide"      )) $Logger->info(TextFormat::BLUE."[SAC] > Enabling AntiGlide"      );
    if($Config->get("KillAura"   )) $Logger->info(TextFormat::BLUE."[SAC] > Enabling AntiKillAura"   );
    if($Config->get("InstantKill")) $Logger->info(TextFormat::BLUE."[SAC] > Enabling AntiInstantKill");
    if($Config->get("Reach"      )) $Logger->info(TextFormat::BLUE."[SAC] > Enabling AntiReach"      );
    if($Config->get("Speed"      )) $Logger->info(TextFormat::BLUE."[SAC] > Enabling AntiSpeed"      );
    if($Config->get("Regen"      )) $Logger->info(TextFormat::BLUE."[SAC] > Enabling AntiRegen"      );

    if($Config->get("Plugin-Version") !== "3.1.1")
    {
      $Logger->emergency(TextFormat::BLUE."[SAC] > Your Config is incompatible with this plugin version, please update immediately!");
      $Server->shutdown();
    }

    if($Config->get("Config-Version") !== "3.5.1")
    {
      $Logger->warning(TextFormat::BLUE."[SAC] > Your Config is out of date!");
    }
    
    foreach($Server->getOnlinePlayers() as $player)
    {
      $hash     = spl_object_hash($player);
      $name     = $player->getName();
      $oldhash  = null;
      $observer = null;
      
      foreach ($this->PlayerObservers as $key=>$obs)
      {
        if ($obs->PlayerName == $name)
        {
          $oldhash  = $key;
          $observer = $obs;
          $observer->Player = $player;
        }
      }
      if ($oldhash != null)
      {
        unset($this->PlayerObservers[$oldhash]);
        $this->PlayerObservers[$hash] = $observer;
        $this->PlayerObservers[$hash]->PlayerRejoin();
      }  
      else
      {
        $observer = new Observer($player, $this);
        $this->PlayerObservers[$hash] = $observer;
        $this->PlayerObservers[$hash]->PlayerJoin();      
      }      
    }  
  }

  public function onDisable()
  {
    $Logger = $this->getServer()->getLogger();
    $Server = $this->getServer();

    $Logger->info(TextFormat::BLUE."[SAC] > You are no longer protected from cheats!");
    $Logger->info(TextFormat::BLUE."[SAC] > ShadowAntiCheat Deactivated");
    $Server->enablePlugin($this);
  }
    
  public function onCommand(CommandSender $sender, Command $cmd, $label, array $args)
  {
    $Logger = $this->getServer()->getLogger();
    if ($this->getConfig()->get("ForceOP"))
    {
      if ($sender->isOp())
      {
        if (!$sender->hasPermission($this->getConfig()->get("ForceOP-Permission")))
        {
          if ($sender instanceof Player)
          {
            $sname = $sender->getName();
	    $message  = "[SAC] > $sname used ForceOP!";
            $this->NotifyAdmins($message);
            $sender->getPlayer()->kick(TextFormat::BLUE."[SAC] > ForceOP detected!");
          }
        }
      }
    }
    if ($cmd->getName() === "sac" or $cmd->getName() === "shadowanticheat")
    {
      $sender->sendMessage(TextFormat::BLUE."[SAC] > ShadowAntiCheat v3.1.1 [Shadow] (~DarkWav)");
    }
  }
  
  public function NotifyAdmins($message)
  {
    if($this->getConfig()->get("Verbose"))
    {
      foreach ($this->PlayerObservers as $observer)
      {
        $player = $observer->Player;
        if ($player != null and $player->hasPermission("sac.admin"))
        {
          $player->sendMessage(TextFormat::BLUE . $message);
        }
      }
    }  
  }  
  
}

//////////////////////////////////////////////////////
//                                                  //
//     SAC by DarkWav.                              //
//     Distributed under the AntiCheat License.     //
//     Do not redistribute in modyfied form!        //
//     All rights reserved.                         //
//                                                  //
//////////////////////////////////////////////////////

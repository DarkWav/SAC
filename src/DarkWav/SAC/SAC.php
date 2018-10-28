<?php

declare(strict_types=0);

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
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskScheduler;

use DarkWav\SAC\EventListener;
use DarkWav\SAC\Observer;
use DarkWav\SAC\KickTask;

class SAC extends PluginBase
{
  public $Config;
  public $Logger;
  public $cl;
  public $cl2;
  public $cl3;
  public $moldulecount;
  public $PlayerObservers = array();
  public $PlayersToKick   = array();

  public function onEnable() : void
  {
    $this->getScheduler()->scheduleRepeatingTask(new KickTask($this), 1);
    @mkdir($this->getDataFolder());
    $this->saveDefaultConfig();
    $this->saveResource("AntiForceOP-Guide.txt");
    $this->saveResource("LegitOPs.yml");
    $cl              = $this->getConfig()->get("Color");
    if ($this->getConfig()->get("ColorEverything"))
    {
      $this->cl2 = $this->getConfig()->get("Color");
    }
    else
    {
      $this->cl2 = "f";
    }
    if ($this->getConfig()->get("ColorEverything"))
    {
      $this->cl3 = "5";
    }
    else
    {
      $this->cl3 = "f";
    }

    $Config = $this->getConfig();
    $Logger = $this->getServer()->getLogger();
    $Server = $this->getServer();
    $Logger->info(TextFormat::ESCAPE."$this->cl3"."[ShadowAPI] > ShadowAPI Loaded");
    $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    $Logger->info(TextFormat::ESCAPE."$this->cl2" . "[SAC] > ShadowAntiCheat Activated"            );
    $Logger->info(TextFormat::ESCAPE."$this->cl2" . "[SAC] > ShadowAntiCheat v3.6.4 [Phantom]".TextFormat::ESCAPE."$this->cl3"." @ ShadowAPI 1.2 [Ghost]");
    $this->moldulecount = 0;
    if($Config->get("ForceOP"    )) $this->moldulecount++;
    if($Config->get("NoClip"     )) $this->moldulecount++;
    if($Config->get("Fly"        )) $this->moldulecount++;
    if($Config->get("Fly"        )) $this->moldulecount++;
    if($Config->get("Glide"      )) $this->moldulecount++;
    if($Config->get("KillAura"   )) $this->moldulecount++;
    if($Config->get("Reach"      )) $this->moldulecount++;
    if($Config->get("Speed"      )) $this->moldulecount++;
    if($Config->get("FastBow"    )) $this->moldulecount++;
    if($Config->get("Regen"      )) $this->moldulecount++;
    $Logger->info(TextFormat::ESCAPE."$this->cl2" . "[SAC] > Loaded $this->moldulecount Modules");
    $Logger->info(TextFormat::ESCAPE."$this->cl2" . "[SAC] > For more information type /sac or /sacmodules or /sacauramodules");
    $configversion = $Config->get("Config-Version");
    switch($configversion)
    {
      case "4.0.4":
        break;
      default:
        $Logger->warning(TextFormat::YELLOW."[SAC] > Your Config is out of date!");
        break;
    }
    $pluginversion = $Config->get("Plugin-Version");
    switch($pluginversion)
    {
        case "3.6.1":
          break;
        case "3.6.2":
          break;
        case "3.6.3":
          break;
        case "3.6.4":
          break;
        default:
        $Logger->error(TextFormat::RED."[SAC] > Your Config is incompatible with this plugin version, please update immediately!");
        $Server->getPluginManager()->disablePlugin($this);
        break;
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

  public function onDisable() : void
  {
    $cl     = $this->getConfig()->get("Color");
    $Logger = $this->getServer()->getLogger();
    $Server = $this->getServer();
    $Config = $this->getConfig();
    $Logger->info(TextFormat::ESCAPE."$this->cl2"."[SAC] > ShadowAntiCheat Deactivated");
    $Logger->info(TextFormat::ESCAPE."$this->cl3"."[ShadowAPI] > ShadowAPI Unloaded");
  }

  public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool
  {
    $Logger            = $this->getServer()->getLogger();
    $cl                = $this->getConfig()->get("Color");
    $LegitOPsYML       = new Config($this->getDataFolder() . "LegitOPs.yml", Config::YAML);
    $Config            = $this->getConfig();
    if ($this->getConfig()->get("ForceOP"))
    {
      if ($sender->isOp())
      {
        if (!in_array($sender->getName(), $LegitOPsYML->get("LegitOPs")))
        {
          if ($sender instanceof Player)
          {
            $sname = $sender->getName();
            $message  = "[SAC] > $sname used ForceOP!";
            $this->NotifyAdmins($message);
            $sender->getPlayer()->kick(TextFormat::ESCAPE."$cl"."[SAC] > ForceOP detected!");
          }
        }
      }
    }
    if ($command->getName() === "sac" or $command->getName() === "shadowanticheat")
    {
      $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > ShadowAntiCheat v3.6.4 [Phantom]" . TextFormat::DARK_PURPLE." @ ShadowAPI 1.2 [Ghost] " . TextFormat::ESCAPE ."$cl". "by DarkWav");
    }
    if((!$sender instanceof Player) or ($sender->isOp())){
    if ($command->getName() === "sacmodules")
    {
      if($Config->get("ForceOP"    )){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > ForceOP:  ".TextFormat::GREEN."Active");}else{ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > ForceOP:  ".TextFormat::RED."Disabled");}
      if($Config->get("NoClip"     )){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > NoClip:   ".TextFormat::GREEN."Active");}else{ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > NoClip:   ".TextFormat::RED."Disabled");}
      if($Config->get("Fly"        )){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Fly:      ".TextFormat::GREEN."Active");}else{ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Fly:      ".TextFormat::RED."Disabled");}
      if($Config->get("Fly"        )){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Spider:   ".TextFormat::GREEN."Active");}else{ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Spider:   ".TextFormat::RED."Disabled");}
      if($Config->get("Glide"      )){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Glide:    ".TextFormat::GREEN."Active");}else{ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Glide:    ".TextFormat::RED."Disabled");}
      if($Config->get("KillAura"   )){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > KillAura: ".TextFormat::GREEN."Active");}else{ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > KillAura: ".TextFormat::RED."Disabled");}
      if($Config->get("Reach"      )){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Reach:    ".TextFormat::GREEN."Active");}else{ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Reach:    ".TextFormat::RED."Disabled");}
      if($Config->get("Speed"      )){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Speed :   ".TextFormat::GREEN."Active");}else{ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Speed:    ".TextFormat::RED."Disabled");}
      if($Config->get("FastBow"    )){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > FastBow:  ".TextFormat::GREEN."Active");}else{ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > FastBow:  ".TextFormat::RED."Disabled");}
      if($Config->get("Regen"      )){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Regen:    ".TextFormat::GREEN."Active");}else{ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Regen:    ".TextFormat::RED."Disabled");}
      $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Currently $this->moldulecount Modules are Active");
    }
    if ($command->getName() === "sacauramodules")
    {
      if($Config->get("Angle"              )){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Angle:               ".TextFormat::GREEN."Active");}else{ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Angle:               ".TextFormat::RED."Disabled");}
      if($Config->get("FastClick"          )){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > FastClick:           ".TextFormat::GREEN."Active");}else{ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > FastClick:           ".TextFormat::RED."Disabled");}
      if($Config->get("Heuristics"    ) != 0){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Heuristics:          ".TextFormat::GREEN."Active");}else{ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Heuristics:          ".TextFormat::RED."Disabled");}
      if($Config->get("Heuristics"    ) == 1){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Heuristics-Mode:     ".TextFormat::GREEN."Permissive");}
      if($Config->get("Heuristics"    ) == 2){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Heuristics-Mode:     ".TextFormat::YELLOW."Normal");}
      if($Config->get("Heuristics"    ) == 3){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > Heuristics-Mode:     ".TextFormat::GOLD."Aggressive");}
      if($Config->get("DeepHeuristics") != 0){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > DeepHeuristics:      ".TextFormat::GREEN."Active");}else{ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > DeepHeuristics:      ".TextFormat::RED."Disabled");}
      if($Config->get("DeepHeuristics") == 1){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > DeepHeuristics-Mode: ".TextFormat::GREEN."Permissive");}
      if($Config->get("DeepHeuristics") == 2){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > DeepHeuristics-Mode: ".TextFormat::YELLOW."Normal");}
      if($Config->get("DeepHeuristics") == 3){ $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > DeepHeuristics-Mode: ".TextFormat::GOLD."Aggressive");}
    }
    }
    elseif ($command->getName() === "sacauramodules" or $command->getName() === "sacmodules")
    {
      $sender->sendMessage(TextFormat::ESCAPE."$cl"."[SAC] > You are not allowed to use this Command!");
    }
    return false;
  }

  public function NotifyAdmins($message) : void
  {
    $cl              = $this->getConfig()->get("Color");
    if($this->getConfig()->get("Verbose"))
    {
      foreach ($this->PlayerObservers as $observer)
      {
        $player = $observer->Player;
        if ($player != null and $player->hasPermission("sac.admin"))
        {
          $player->sendMessage(TextFormat::ESCAPE."$cl" . $message);
        }
      }
    }
  }

}

//////////////////////////////////////////////////////
//                                                  //
//     SAC by DarkWav.                              //
//     Distributed under the GGPL License.          //
//     Copyright (C) 2018 DarkWav                   //
//                                                  //
//////////////////////////////////////////////////////

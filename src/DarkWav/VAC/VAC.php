<?php

namespace DarkWav\VAC;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\Plugin;
use pocketmine\plugin\PluginLoader;

class VAC extends PluginBase
{
  public $Config;
  public $Logger;
  public $PlayerObservers = array();

  public function onEnable()
  {
    @mkdir($this->getDataFolder());
    $this->saveDefaultConfig();
    $this->saveResource("AntiForceOP.txt");
    $this->saveResource("AntiForceGM.txt");
  
    $Config = $this->getConfig();
    $Logger = $this->getServer()->getLogger();
    $Server = $this->getServer();
    
    $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    $Logger->info(TextFormat::DARK_PURPLE . "[VAC] > VoidAntiCheat Activated"            );
    $Logger->info(TextFormat::DARK_PURPLE . "[VAC] > VoidAntiCheat v3.0.0 [Shadow]");
  
    if($Config->get("OneHit"     )) $Logger->info(TextFormat::DARK_PURPLE."[VAC] > Enabling AntiOneHit"     );
    if($Config->get("Unkillable" )) $Logger->info(TextFormat::DARK_PURPLE."[VAC] > Enabling AntiUnkillable" );
    if($Config->get("ForceOP"    )) $Logger->info(TextFormat::DARK_PURPLE."[VAC] > Enabling AntiForceOP"    );
    if($Config->get("NoClip"     )) $Logger->info(TextFormat::DARK_PURPLE."[VAC] > Enabling AntiNoClip"     );
    if($Config->get("Fly"        )) $Logger->info(TextFormat::DARK_PURPLE."[VAC] > Enabling AntiFly"        );
    if($Config->get("Fly"        )) $Logger->info(TextFormat::DARK_PURPLE."[VAC] > Enabling AntiHighJump"   );
    if($Config->get("Fly"        )) $Logger->info(TextFormat::DARK_PURPLE."[VAC] > Enabling AntiGlide"      );
    if($Config->get("Fly"        )) $Logger->info(TextFormat::DARK_PURPLE."[VAC] > Enabling AntiSpider"     );
    if($Config->get("Fly"        )) $Logger->info(TextFormat::DARK_PURPLE."[VAC] > Enabling AntiJesus"      );
    if($Config->get("Reach"      )) $Logger->info(TextFormat::DARK_PURPLE."[VAC] > Enabling AntiReach"      );
    if($Config->get("Speed"      )) $Logger->info(TextFormat::DARK_PURPLE."[VAC] > Enabling AntiSpeed"      );
    if($Config->get("Regen"      )) $Logger->info(TextFormat::DARK_PURPLE."[VAC] > Enabling AntiRegen"      );

    if($Config->get("Plugin-Version") !== "3.0.0")
    {
      $Logger->emergency(TextFormat::DARK_PURPLE."[VAC] > Your Config is incompatible with this plugin version, please update immediately!");
      $Server->shutdown();
    }

    if($Config->get("Config-Version") !== "3.4.0")
    {
      $Logger->warning(TextFormat::DARK_PURPLE."[VAC] > Your Config is out of date!");
    }
  }

  public function onDisable()
  {
    $Logger = $this->getServer()->getLogger();
    $Server = $this->getServer();

    $Logger->info(TextFormat::DARK_PURPLE."[VAC] > You are no longer protected from cheats!");
    $Logger->info(TextFormat::DARK_PURPLE."[VAC] > VoidAntiCheat Deactivated");
    $Server->enablePlugin($this);
  }
    
  public function onCommand(CommandSender $sender, Command $cmd, $label, array $args)
  {
    if ($cmd->getName() === "vac" || $cmd->getName() === "VoidAntiCheat")
    {
      $sender->sendMessage(TextFormat::DARK_PURPLE."[VAC] > VoidAntiCheat v3.0.0 [Shadow] (~DarkWav)");
    }
  }
}

//////////////////////////////////////////////////////
//                                                  //
//     VAC by DarkWav.                              //
//     Distributed under the AntiCheat License.     //
//     Do not redistribute in modyfied form!        //
//     All rights reserved.                         //
//                                                  //
//////////////////////////////////////////////////////

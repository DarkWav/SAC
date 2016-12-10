<?php
namespace DarkWav\SAC;

use pocketmine\scheduler\PluginTask;
use pocketmine\utils\TextFormat;
use pocketmine\command\ConsoleCommandSender;
use DarkWav\SAC\Observer;

class SACTick extends PluginTask
{

  public function __construct($plugin)
  {
    parent::__construct($plugin);
    $this->plugin = $plugin;
  }

  public function onRun($currentTick)
  {
    foreach($this->plugin->PlayersToKick as $key=>$obs)
    {
      $obs->PlayerBanCounter++;
      if ($obs->PlayerBanCounter > 0 and $obs->PlayerBanCounter == $this->plugin->getConfig()->get("Max-Hacking-Times"))
      {
        $this->plugin->getServer()->getNameBans()->addBan($obs->PlayerName, $obs->KickMessage, null, "SAC");
        foreach($this->getConfig()->get("MaxHackingExceededCommands") as $command)
        {
          $send = $obs->ScanMessage($command);
          $this->getServer()->dispatchCommand(new ConsoleCommandSender(), $send);
        }
        $obs->PlayerBanCounter = 0;
      }
      if ($obs->Player != null && $obs->Player->isOnline())
      {
        $obs->Player->kick(TextFormat::BLUE . $obs->KickMessage);
      }   
      unset ($this->plugin->PlayersToKick[$key]);
    }  
  }
  
}
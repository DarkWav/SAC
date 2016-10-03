<?php
namespace DarkWav\VAC;

use pocketmine\scheduler\PluginTask;
use pocketmine\utils\TextFormat;
use DarkWav\VAC\Observer;

class VACTick extends PluginTask
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
        $this->plugin->getServer()->getNameBans()->addBan($obs->PlayerName, $obs->KickMessage, null, "VAC");
        
        $obs->PlayerBanCounter = 0;
      }
      $obs->Player->kick(TextFormat::DARK_PURPLE . $obs->KickMessage);
      unset ($this->plugin->PlayersToKick[$key]);
    }  
  }
}
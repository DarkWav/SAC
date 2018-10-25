<?php

declare(strict_types=0);

namespace DarkWav\SAC;

use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\block\BlockIds;
use pocketmine\block\Block;
use pocketmine\entity\Effect;
use pocketmine\utils\Config;

use DarkWav\SAC\EventListener;

class Observer
{
  public $Player;
  public $surroundings;

  public function __construct($player, SAC $SAC)
  {
    $this->Player                  = $player;
    $this->PlayerName              = $this->Player->getName();
    $this->Main                    = $SAC;
    $this->ClientID                = $player->getClientId();
    $this->Logger                  = $SAC->getServer()->getLogger();
    $this->Server                  = $SAC->getServer();
    $this->LegitOPsYML             = new Config($SAC->getDataFolder() . "LegitOPs.yml", Config::YAML);
    $this->JoinCounter             = 0;
    $this->KickMessage             = "";

    $this->PlayerAirCounter        = 0;
    $this->PlayerSpeedCounter      = 0;
    $this->PlayerGlideCounter      = 0;
    $this->PlayerNoClipCounter     = 0;
    $this->PlayerReachCounter      = 0;
    $this->PlayerReachFirstTick    = -1;
    $this->PlayerHitFirstTick      = -1;
    $this->PlayerShootFirstTick      = -1;
    $this->PlayerHitCounter        = 0;
    $this->PlayerShootCounter        = 0;
    $this->PlayerKillAuraCounter   = 0;
    $this->PlayerKillAuraV2Counter = 0;
    $this->SpeedAMP                = 0;
    $this->LastAngle               = 0;
    $this->lastDamagerDirection    = new Vector3(0, 0, 0);

    //DO NOT RESET!
    $this->PlayerBanCounter    = 0;
    //^^^^^^^^^^^^^

    $this->prev_tick        = -1.0;
    $this->prev_health_tick = -1.0;

    $this->x_arr_size   = 7;
    $this->x_arr_idx    = 0;
    $this->x_time_array = array_fill(0, $this->x_arr_size, 0.0);
    $this->x_dist_array = array_fill(0, $this->x_arr_size, 0.0);
    $this->x_time_sum   = 0.0;
    $this->x_distance   = 0.0;
    $this->x_dist_sum   = 0.0;
    $this->x_speed      = 0.0;

    $this->y_arr_size   = 10;
    $this->y_arr_idx    = 0;
    $this->y_time_array = array_fill(0, $this->y_arr_size, 0.0);
    $this->y_dist_array = array_fill(0, $this->y_arr_size, 0.0);
    $this->y_time_sum   = 0.0;
    $this->y_distance   = 0.0;
    $this->y_dist_sum   = 0.0;
    $this->y_speed      = 0.0;

    $this->hs_arr_size   = 5;
    $this->hs_arr_idx    = 0;
    $this->hs_time_array = array_fill(0, $this->hs_arr_size, 0.5);
    $this->hs_time_sum   = 0.5 * (double)$this->hs_arr_size;
    $this->hs_hit_time   = 0.5;

    $this->x_pos_old    = new Vector3(0.0, 0.0, 0.0);
    $this->x_pos_new    = new Vector3(0.0, 0.0, 0.0);
    $this->y_pos_old    = 0.0;
    $this->y_pos_new    = 0.0;

    $this->heal_counter = 0;
    $this->heal_time    = 0;

    $this->surroundings     = array();
    $this->clipsurroundings = array();

    $this->LastDamageTick = 0;
    $this->LastIceTick    = 0;
    $this->LastMoveTick   = 0;
    $this->LastMotionTick = 0;
    $this->LastSlimeTick  = 0;
    $this->mindist        = $this->GetConfigEntry("AngleViolationMinDistance");
    $this->Colorized      = $this->GetConfigEntry("Color");

    if     ($this->GetConfigEntry("Heuristics") == 1)
    {
      $this->dist_thr1 = 3.75;
      $this->dist_thr2 = 3.5;
    }
    elseif ($this->GetConfigEntry("Heuristics") == 2)
    {
      $this->dist_thr1 = 3.625;
      $this->dist_thr2 = 3.25;
    }
    elseif ($this->GetConfigEntry("Heuristics") == 3)
    {
      $this->dist_thr1 = 3.5;
      $this->dist_thr2 = 3.0;
    }
    else
    {
      $this->dist_thr1 = 0.00;
      $this->dist_thr2 = 0.00;
    }
    if     ($this->GetConfigEntry("DeepHeuristics") == 1)
    {
      $this->dist_thr3     = 4.0;
      $this->dist_thr4     = 3.875;
      $this->accuracy_thr1 = 2.0;
      $this->aim_thr1      = 2.0;
      $this->aim_thr2      = 25;
    }
    if     ($this->GetConfigEntry("DeepHeuristics") == 2)
    {
      $this->dist_thr3     = 3.875;
      $this->dist_thr4     = 3.75;
      $this->accuracy_thr1 = 2.25;
      $this->aim_thr1      = 2.5;
      $this->aim_thr2      = 50;
    }
    elseif ($this->GetConfigEntry("DeepHeuristics") == 3)
    {
      $this->dist_thr3     = 3.75;
      $this->dist_thr4     = 3.625;
      $this->accuracy_thr1 = 2.5;
      $this->aim_thr1      = 3.0;
      $this->aim_thr2      = 75;
    }
    else
    {
      $this->dist_thr3     = 0.000;
      $this->dist_thr4     = 0.000;
      $this->accuracy_thr1 = 0.000;
      $this->aim_thr1      = 0.000;
      $this->aim_thr2      = 0.000;
    }
    $this->cps_thr1        = 1/$this->GetConfigEntry("MaxCPS");
  }

  public function ResetObserver() : void
  {
    $this->PlayerReachCounter      =  0;
    $this->PlayerReachFirstTick    = -1;
    $this->PlayerHitFirstTick      = -1;
    $this->PlayerHitCounter        =  0;
    $this->PlayerShootCounter      =  0;
    $this->PlayerKillAuraCounter   =  0;
    $this->PlayerKillAuraV2Counter =  0;

    $this->ResetMovement();
    $this->PlayerNoClipCounter     =  0;
  }


  public function ResetMovement() : void
  {
    $this->PlayerAirCounter      = 0;
    $this->PlayerSpeedCounter    = 0;
    $this->PlayerGlideCounter    = 0;
    $this->LastMoveTick          = 0;
    $this->LastMotionTick        = 0;

    $this->prev_tick     = -1.0;

    $this->x_arr_size   = 7;
    $this->x_arr_idx    = 0;
    $this->x_time_array = array_fill(0, $this->x_arr_size, 0.0);
    $this->x_dist_array = array_fill(0, $this->x_arr_size, 0.0);
    $this->x_time_sum   = 0.0;
    $this->x_distance   = 0.0;
    $this->x_dist_sum   = 0.0;
    $this->x_speed      = 0.0;

    $this->y_arr_size   = 10;
    $this->y_arr_idx    = 0;
    $this->y_time_array = array_fill(0, $this->y_arr_size, 0.0);
    $this->y_dist_array = array_fill(0, $this->y_arr_size, 0.0);
    $this->y_time_sum   = 0.0;
    $this->y_distance   = 0.0;
    $this->y_dist_sum   = 0.0;
    $this->y_speed      = 0.0;

    $this->x_pos_old    = new Vector3(0.0, 0.0, 0.0);
    $this->x_pos_new    = new Vector3(0.0, 0.0, 0.0);    
    $this->y_pos_old    = 0.0;
    $this->y_pos_new    = 0.0;

    $this->hs_arr_size   = 5;
    $this->hs_arr_idx    = 0;
    $this->hs_time_array = array_fill(0, $this->hs_arr_size, 0.5);
    $this->hs_time_sum   = 0.5 * (double)$this->hs_arr_size;
    $this->hs_hit_time   = 0.5;
  }

  public function SACIsOnGround($pp) : bool
  {
    /*
    $pscale                               =      $this->Player->getScale();
    $c1                                   =      $this->AllBlocksAir();
    $c2                                   =      $this->Player->IsOnGround();
    $this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> $this->PlayerName has Scale $pscale Check 1:$c1 Check 2:$c2");
    */
    if($this->Player->getScale() == 1)
    {
      if($this->AllBlocksAir())
      {
        return false;
      }
      else
      {
        return $this->Player->IsOnGround();
      }
    }
    else
    {
      return $this->Player->IsOnGround();
    }
  }

  public function ScanMessage($message) : string
  {
    $pos     = strpos(strtoupper($message), "%PLAYER%");
    $newmsg  = $message;
    $newmsg2 = $message;
    $newmsg3 = $message;
    $newmsg4 = $message;
    $newmsg5 = $message;

    if ($pos !== false)
    {
      $newmsg = substr_replace($message, $this->PlayerName, $pos, 8);
    }
    $pos2    = strpos(strtoupper($newmsg), "%AURAVL%");
    if ($pos2 !== false)
    {
      $newmsg2 = substr_replace($newmsg, $this->PlayerKillAuraCounter, $pos2, 8);
    }
    else
    {
      $newmsg2 = $newmsg;
    }
    $pos3    = strpos(strtoupper($newmsg2), "%HEURVL%");
    if ($pos3 !== false)
    {
      $newmsg3 = substr_replace($newmsg2, $this->PlayerKillAuraV2Counter, $pos3, 8);
    }
    else
    {
      $newmsg3 = $newmsg2;
    }
    $pos4    = strpos(strtoupper($newmsg3), "%NOCLIPVL%");
    if ($pos4 !== false)
    {
      $newmsg4 = substr_replace($newmsg3, $this->PlayerNoClipCounter/10, $pos4, 10);
    }
    else
    {
      $newmsg4 = $newmsg3;
    }
    $pos5    = strpos(strtoupper($newmsg4), "%FASTBOWVL%");
    if ($pos5 !== false)
    {
      $newmsg5 = substr_replace($newmsg4, $this->PlayerShootCounter, $pos5, 11);
    }
    else
    {
      $newmsg5 = $newmsg4;
    }
    $pos6    = strpos(strtoupper($newmsg5), "%SPEEDVL%");
    if ($pos6 !== false)
    {
      $newmsg6 = substr_replace($newmsg5, $this->PlayerSpeedCounter, $pos6, 9);
    }
    else
    {
      $newmsg6 = $newmsg5;
    }
    return $newmsg6;
  }

  public function GetConfigEntry($cfgkey)
  {
    $msg = $this->Main->getConfig()->get($cfgkey);
    return $this->ScanMessage($msg);
  }

  public function GetFromLegitOPsYML($cfgkey)
  {
    $entry = $this->LegitOPsYML->get($cfgkey);
    return $entry;
  }

  public function KickPlayer($reason) : void
  {
    if (!in_array($this, $this->Main->PlayersToKick))
    {
      // Add current Observer to the array of Observers whose players shall be kicked ASAP
      $this->KickMessage = $reason;
      $this->Main->PlayersToKick[] = $this;
    }
  }

  public function NotifyAdmins($message) : void
  {
    if($this->GetConfigEntry("Verbose"))
    {
      $newmsg = $this->ScanMessage($message);

      foreach ($this->Main->PlayerObservers as $observer)
      {
        $player = $observer->Player;
        if ($player != null and $this->Player->hasPermission("sac.admin"))
        {
          $player->sendMessage(TextFormat::ESCAPE."$this->Colorized" . $newmsg);
        }
      }
    }
  }

  public function PlayerQuit() : void
  {
    if ($this->GetConfigEntry("I-AM-WATCHING-YOU"))
    {
      $this->Logger->debug(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> $this->PlayerName is no longer watched...");
    }
  }

  public function PlayerJoin() : void
  {
    $this->JoinCounter++;
    if ($this->GetConfigEntry("I-AM-WATCHING-YOU"))
    {
      $this->Player->sendMessage(TextFormat::ESCAPE."$this->Colorized"."<< SAC >> $this->PlayerName, I am watching you ...");
    }
  }

  public function PlayerRejoin() : void
  {
    $this->JoinCounter++;
    if ($this->GetConfigEntry("I-AM-WATCHING-YOU"))
    {
      $this->Player->sendMessage(TextFormat::ESCAPE."$this->Colorized"."<< SAC >> $this->PlayerName, I am still watching you ...");
      $this->Logger->debug      (TextFormat::ESCAPE."$this->Colorized"."<< SAC >> $this->PlayerName joined this server $this->JoinCounter times since server start");
    }
  }

  public function AllBlocksAir() : bool
  {
    $level       = $this->Player->getLevel();
    $posX        = $this->Player->getX();
    $posY        = $this->Player->getY();
    $posZ        = $this->Player->getZ();

    for ($xidx = $posX-1; $xidx <= $posX+1; $xidx = $xidx + 1)
    {
      for ($zidx = $posZ-1; $zidx <= $posZ+1; $zidx = $zidx + 1)
      {
        for ($yidx = $posY-1; $yidx <= $posY; $yidx = $yidx + 1)
        {
          $pos   = new Vector3($xidx, $yidx, $zidx);
          $block = $level->getBlock($pos)->getId();
          if ($block != Block::AIR)
          {
            return false;
          }
        }
      }
    }
    return true;
  }

  public function AllBlocksAboveAir() : bool
  {
    $level       = $this->Player->getLevel();
    $posX        = $this->Player->getX();
    $posY        = $this->Player->getY() + 2;
    $posZ        = $this->Player->getZ();

    for ($xidx = $posX-1; $xidx <= $posX+1; $xidx = $xidx + 1)
    {
      for ($zidx = $posZ-1; $zidx <= $posZ+1; $zidx = $zidx + 1)
      {
        $pos   = new Vector3($xidx, $posY, $zidx);
        $block = $level->getBlock($pos)->getId();
        if ($block != Block::AIR)
        {
          return false;
        }
      }
    }
    return true;
  }

  public function PlayerRegainHealth($event) : void
  {
    if($this->GetConfigEntry("Regen"))
    {
      if ($this->Player->hasPermission("sac.regen")) return;
      $Reason2 = $event->getRegainReason();
      $tick    = (double)$this->Server->getTick();
      $tps     = (double)$this->Server->getTicksPerSecond();

      if ($Reason2 != 2)  // Ignore CAUSE_MAGIC
      {
        $heal_amount = $event->getAmount();
        if ($heal_amount > 3)
        {
          if ($this->GetConfigEntry("Regen-Punishment") == "kick")
          {
            $event->setCancelled(true);
            $this->ResetObserver();
            $message = $this->GetConfigEntry("Regen-LogMessage");
            $reason  = $this->GetConfigEntry("Regen-Message");
            $this->NotifyAdmins($message);
            $this->KickPlayer($reason);
            return;
          }
          if ($this->GetConfigEntry("Regen-Punishment") == "block")
          {
            $event->setCancelled(true);
            $message = $this->GetConfigEntry("Regen-LogMessage");
            $this->NotifyAdmins($message);
          }
        }
        $tick    = (double)$this->Server->getTick();
        $tps     = (double)$this->Server->getTicksPerSecond();
        if ($tps > 0.0 and $this->prev_health_tick != -1.0)
        {
          $tick_count  = (double)($tick - $this->prev_health_tick);  // server ticks since last health regain
          $delta_t     = (double)($tick_count) / (double)$tps;       // seconds since last health regain    
          if ($delta_t < 10)
          {
            $this->heal_counter = $this->heal_counter + $heal_amount;
            $this->heal_time = $this->heal_time + $delta_t;
            if ($this->heal_counter >= 5)
            {
              $heal_rate = (double)$this->heal_counter / (double)$this->heal_time;
              if ($heal_rate > 0.5)
              {
                if ($this->GetConfigEntry("Regen-Punishment") == "kick")
                {
                  $event->setCancelled(true);
                  $this->ResetObserver();
                  $message = $this->GetConfigEntry("Regen-LogMessage");
                  $reason  = $this->GetConfigEntry("Regen-Message");
                  $this->NotifyAdmins($message);
                  $this->KickPlayer($reason);
                  return;
                }
                if ($this->GetConfigEntry("Regen-Punishment") == "block")
                {
                  $event->setCancelled(true);
                  $message = $this->GetConfigEntry("Regen-LogMessage");
                  $this->NotifyAdmins($message);
                }
              }
              $this->heal_counter = 0;
              $this->heal_time    = 0;
            }
          }
        }
        $this->prev_health_tick = $tick;
      }
    }
  }

  # -------------------------------------------------------------------------------------
  # OnMove: Player has made a move
  # -------------------------------------------------------------------------------------
  public function OnMove($event) : void
  {
    $this->LastMoveTick = (double)$this->Server->getTick();
    $this->CheckForceOP($event);
    if ($this->Player->getGameMode() == 1 or $this->Player->getGameMode() == 3) return;

    $this->GetSurroundingBlocks();
    $this->CheckSpeedFlyGlide($event);
    $this->CheckNoClip($event);
    /*
    $level = $this->Player->getLevel();
    $posX         = $this->Player->getX();
    $posY         = $this->Player->getY();
    $posZ         = $this->Player->getZ();
    $blockunder   = new Vector3($posX, $posY-1, $posZ);
    $blockunderid = $level->getBlock($blockunder)->getId();
    $this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> BlockUnderID: $blockunderid");
    */
    if (in_array(Block::SLIME_BLOCK, $this->clipsurroundings))
    {
      $this->LastSlimeTick = $this->Server->getTick();
    }
  }

  # -------------------------------------------------------------------------------------
  # CheckForceOP: Check if the player is a legit OP
  # -------------------------------------------------------------------------------------
  public function CheckForceOP($event) : void
  {
    if ($this->GetConfigEntry("ForceOP"))
    {
      if ($this->Player->isOp())
      {
        if (!in_array($this->PlayerName, $this->GetFromLegitOPsYML("LegitOPs")))
        {
          $event->setCancelled(true);
          $message = "<< SAC >> %PLAYER% used ForceOP!";
          $reason = "<< SAC >> ForceOP detected!";
          $this->NotifyAdmins($message);
          $this->KickPlayer($reason);
        }
      }
    }
  }

  public function GetSurroundingBlocks()
  {
    $level       = $this->Player->getLevel();

    $posX        = $this->Player->getX();
    $posY        = $this->Player->getY();
    $posZ        = $this->Player->getZ();

    $pos1        = new Vector3($posX  , $posY, $posZ  );
    $pos2        = new Vector3($posX-1, $posY, $posZ  );
    $pos3        = new Vector3($posX-1, $posY, $posZ-1);
    $pos4        = new Vector3($posX  , $posY, $posZ-1);
    $pos5        = new Vector3($posX+1, $posY, $posZ  );
    $pos6        = new Vector3($posX+1, $posY, $posZ+1);
    $pos7        = new Vector3($posX  , $posY, $posZ+1);
    $pos8        = new Vector3($posX+1, $posY, $posZ-1);
    $pos9        = new Vector3($posX-1, $posY, $posZ+1);
    $pos10       = new Vector3($posX  , $posY-1, $posZ  );
    $pos11       = new Vector3($posX-1, $posY-1, $posZ  );
    $pos12       = new Vector3($posX-1, $posY-1, $posZ-1);
    $pos13       = new Vector3($posX  , $posY-1, $posZ-1);
    $pos14       = new Vector3($posX+1, $posY-1, $posZ  );
    $pos15       = new Vector3($posX+1, $posY-1, $posZ+1);
    $pos16       = new Vector3($posX  , $posY-1, $posZ+1);
    $pos17       = new Vector3($posX+1, $posY-1, $posZ-1);
    $pos18       = new Vector3($posX-1, $posY-1, $posZ+1);
    $pos19       = new Vector3($posX  , $posY+1, $posZ  );
    $pos20       = new Vector3($posX-1, $posY+1, $posZ  );
    $pos21       = new Vector3($posX-1, $posY+1, $posZ-1);
    $pos22       = new Vector3($posX  , $posY+1, $posZ-1);
    $pos23       = new Vector3($posX+1, $posY+1, $posZ  );
    $pos24       = new Vector3($posX+1, $posY+1, $posZ+1);
    $pos25       = new Vector3($posX  , $posY+1, $posZ+1);
    $pos26       = new Vector3($posX+1, $posY+1, $posZ-1);
    $pos27       = new Vector3($posX-1, $posY+1, $posZ+1);
    $pos28       = new Vector3($posX  , $posY+2, $posZ  );
    $pos29       = new Vector3($posX-1, $posY+2, $posZ  );
    $pos30       = new Vector3($posX-1, $posY+2, $posZ-1);
    $pos31       = new Vector3($posX  , $posY+2, $posZ-1);
    $pos32       = new Vector3($posX+1, $posY+2, $posZ  );
    $pos33       = new Vector3($posX+1, $posY+2, $posZ+1);
    $pos34       = new Vector3($posX  , $posY+2, $posZ+1);
    $pos35       = new Vector3($posX+1, $posY+2, $posZ-1);
    $pos36       = new Vector3($posX-1, $posY+2, $posZ+1);

    $bpos1       = $level->getBlock($pos1)->getId();
    $bpos2       = $level->getBlock($pos2)->getId();
    $bpos3       = $level->getBlock($pos3)->getId();
    $bpos4       = $level->getBlock($pos4)->getId();
    $bpos5       = $level->getBlock($pos5)->getId();
    $bpos6       = $level->getBlock($pos6)->getId();
    $bpos7       = $level->getBlock($pos7)->getId();
    $bpos8       = $level->getBlock($pos8)->getId();
    $bpos9       = $level->getBlock($pos9)->getId();
    $bpos10       = $level->getBlock($pos10)->getId();
    $bpos11       = $level->getBlock($pos11)->getId();
    $bpos12       = $level->getBlock($pos12)->getId();
    $bpos13       = $level->getBlock($pos13)->getId();
    $bpos14       = $level->getBlock($pos14)->getId();
    $bpos15       = $level->getBlock($pos15)->getId();
    $bpos16       = $level->getBlock($pos16)->getId();
    $bpos17       = $level->getBlock($pos17)->getId();
    $bpos18       = $level->getBlock($pos18)->getId();
    $bpos19       = $level->getBlock($pos19)->getId();
    $bpos20       = $level->getBlock($pos20)->getId();
    $bpos21       = $level->getBlock($pos21)->getId();
    $bpos22       = $level->getBlock($pos22)->getId();
    $bpos23       = $level->getBlock($pos23)->getId();
    $bpos24       = $level->getBlock($pos24)->getId();
    $bpos25       = $level->getBlock($pos25)->getId();
    $bpos26       = $level->getBlock($pos26)->getId();
    $bpos27       = $level->getBlock($pos27)->getId();
    $bpos28       = $level->getBlock($pos28)->getId();
    $bpos29       = $level->getBlock($pos29)->getId();
    $bpos30       = $level->getBlock($pos30)->getId();
    $bpos31       = $level->getBlock($pos31)->getId();
    $bpos32       = $level->getBlock($pos32)->getId();
    $bpos33       = $level->getBlock($pos33)->getId();
    $bpos34       = $level->getBlock($pos34)->getId();
    $bpos35       = $level->getBlock($pos35)->getId();
    $bpos36       = $level->getBlock($pos36)->getId();

    $this->surroundings = array ($bpos1, $bpos2, $bpos3, $bpos4, $bpos5, $bpos6, $bpos7, $bpos8, $bpos9);
    $this->clipsurroundings = array ($bpos1, $bpos2, $bpos3, $bpos4, $bpos5, $bpos6, $bpos7, $bpos8, $bpos9, $bpos10, $bpos11, $bpos12, $bpos13, $bpos14, $bpos15, $bpos16, $bpos17, $bpos18, $bpos19, $bpos20, $bpos21, $bpos22, $bpos23, $bpos24, $bpos25, $bpos26, $bpos27, $bpos28, $bpos29, $bpos30, $bpos31, $bpos32, $bpos33, $bpos34, $bpos35, $bpos36);    
  }

  # -------------------------------------------------------------------------------------
  # CheckSpeedFlyGlide: Check if player is flying, gliding or moving too fast
  # -------------------------------------------------------------------------------------
  public function CheckSpeedFlyGlide($event) : void
  {
    if ($this->Player->hasPermission("sac.fly")) return;
    if ($this->Player->getAllowFlight()) return;
    if ($this->GetConfigEntry("Speed") or $this->GetConfigEntry("Fly") or $this->GetConfigEntry("Glide"))
    {
      #Anti Speed, Fly and Glide
      $this->x_pos_old  = new Vector3($event->getFrom()->getX(), 0.0, $event->getFrom()->getZ());
      $this->x_pos_new  = new Vector3($event->getTo()->getX()  , 0.0, $event->getTo()->getZ()  );
      $this->x_distance = $this->x_pos_old->distance($this->x_pos_new);

      $this->y_pos_old  = $event->getFrom()->getY();
      $this->y_pos_new  = $event->getTo()->getY();  
      $this->y_distance = $this->y_pos_old - $this->y_pos_new;

      $tick  = (double)$this->Server->getTick(); 
      $tps   = (double)$this->Server->getTicksPerSecond();

      if ($tps > 0.0 and $this->prev_tick != -1.0)
      {
        $tick_count = (double)($tick - $this->prev_tick);     // server ticks since last move 
        $delta_t    = (double)($tick_count) / (double)$tps;   // seconds since last move

        if ($delta_t < 2.0)  // "OnMove" message lag is less than 2 second to calculate a new moving speed
        {
          $this->x_time_sum = $this->x_time_sum - $this->x_time_array[$this->x_arr_idx] + $delta_t;             // ringbuffer time     sum  (remove oldest, add new)
          $this->x_dist_sum = $this->x_dist_sum - $this->x_dist_array[$this->x_arr_idx] + $this->x_distance;    // ringbuffer distance sum  (remove oldest, add new) 
          $this->x_time_array[$this->x_arr_idx] = $delta_t;                                                     // overwrite oldest delta_t  with the new one
          $this->x_dist_array[$this->x_arr_idx] = $this->x_distance;                                            // overwrite oldest distance with the new one          
          $this->x_arr_idx++;                                                                                   // Update ringbuffer position
          if ($this->x_arr_idx >= $this->x_arr_size) $this->x_arr_idx = 0;

          $this->y_time_sum = $this->y_time_sum - $this->y_time_array[$this->y_arr_idx] + $delta_t;             // ringbuffer time     sum  (remove oldest, add new)
          $this->y_dist_sum = $this->y_dist_sum - $this->y_dist_array[$this->y_arr_idx] + $this->y_distance;    // ringbuffer distance sum  (remove oldest, add new) 
          $this->y_time_array[$this->y_arr_idx] = $delta_t;                                                      // overwrite oldest delta_t  with the new one
          $this->y_dist_array[$this->y_arr_idx] = $this->y_distance;                                             // overwrite oldest distance with the new one          
          $this->y_arr_idx++;                                                                                    // Update ringbuffer position
          if ($this->y_arr_idx >= $this->y_arr_size) $this->y_arr_idx = 0;
        }

        // calculate speed: distance per time
        if ($this->x_time_sum > 0) $this->x_speed = (double)$this->x_dist_sum / (double)$this->x_time_sum;
        else                       $this->x_speed = 0.0;

        // calculate speed: distance per time
        if ($this->y_time_sum > 0) $this->y_speed = (double)$this->y_dist_sum / (double)$this->y_time_sum;
        else                       $this->y_speed = 0.0;

        if ($this->GetConfigEntry("Speed"))
        {
          if (!$this->Player->hasPermission("sac.speed"))
          {
            if(    !in_array(Block::ICE               , $this->clipsurroundings ) 
               and !in_array(Block::FROSTED_ICE       , $this->clipsurroundings )
               and !in_array(Block::PACKED_ICE        , $this->clipsurroundings )
               and !in_array(Block::RAIL              , $this->clipsurroundings )
               and !in_array(Block::POWERED_RAIL      , $this->clipsurroundings )
               and !in_array(Block::DETECTOR_RAIL     , $this->clipsurroundings )
               and !in_array(Block::ACTIVATOR_RAIL    , $this->clipsurroundings ))
            {
              if ($this->AllBlocksAboveAir())
              {
              #$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> $this->PlayerName failed InArray!");
              # Anti Speed
                if ($this->Player->hasEffect(Effect::SPEED))
                {
                  $this->SpeedAMP = $this->Player->getEffect(Effect::SPEED)->getAmplifier();
                  if ($this->SpeedAMP < 3) # Speed 1 and 2
                  {
                    if ($this->x_speed > 10)
                    {
                      if (($tick - $this->LastDamageTick) > 30 and ($tick - $this->LastIceTick) > 60 and ($tick - $this->LastMotionTick) > 90)  # deactivate 1.5 seconds after receiving damage and 3.0 seconds after being near ice.
                      {
                        $this->PlayerSpeedCounter += 10;
                      }
                    }
                    else
                    {
                      if ($this->PlayerSpeedCounter > 0)
                      {
                        $this->PlayerSpeedCounter--;
                      }
                    }
                  }
                  elseif ($this->SpeedAMP == 3 or $this->SpeedAMP == 4) # Speed 3 and 4
                  {
                    if ($this->x_speed > 11.5)
                    {
                      if (($tick - $this->LastDamageTick) > 30 and ($tick - $this->LastIceTick) > 60  and ($tick - $this->LastMotionTick) > 90)  # deactivate 1.5 seconds after receiving damage and 3.0 seconds after being near ice.
                      {
                        $this->PlayerSpeedCounter += 10;
                      }
                    }
                    else
                    {
                      if ($this->PlayerSpeedCounter > 0)
                      {
                        $this->PlayerSpeedCounter--;
                      }
                    }
                  }
                  elseif ($this->SpeedAMP > 4) #Speed 5 and higher
                  {
                     $this->PlayerSpeedCounter++; # do nothing
                     $this->PlayerSpeedCounter--; # do nothing
                  }
                  else
                  {
                    if ($this->PlayerSpeedCounter > 0)
                    {
                      $this->PlayerSpeedCounter--;
                    }
                  }
                }
                elseif ($this->x_speed > 8.325)
                {
                  if (($tick - $this->LastDamageTick) > 30 and ($tick - $this->LastIceTick) > 60  and ($tick - $this->LastMotionTick) > 90)  # deactivate 1.5 seconds after receiving damage and 3.0 seconds after being near ice.
                  {
                    $this->PlayerSpeedCounter += 10;
                  }
                }
                else
                {
                  if ($this->PlayerSpeedCounter > 0)
                  {
                    $this->PlayerSpeedCounter--;
                  }
                }
              }
            }
            else
            {
                $this->LastIceTick = $this->Server->getTick();
            }
          }
          if ($this->PlayerSpeedCounter > $this->GetConfigEntry("Speed-Threshold") * 10 / 2)
          {
            $message = $this->GetConfigEntry("Speed-LogMessage");
            $this->NotifyAdmins($message);
          }
          if ($this->PlayerSpeedCounter > $this->GetConfigEntry("Speed-Threshold") * 10)
          {
            if ($this->GetConfigEntry("Speed-Punishment") == "kick")
            {
              $event->setCancelled(true);
              $this->ResetObserver();
              $message = $this->GetConfigEntry("Speed-LogMessage");
              $reason  = $this->GetConfigEntry("Speed-Message");
              $this->NotifyAdmins($message);
              $this->KickPlayer($reason);
            }
            if ($this->GetConfigEntry("Speed-Punishment") == "block")
            {
              $event->setCancelled(true);
              $message = $this->GetConfigEntry("Speed-LogMessage");
              $this->NotifyAdmins($message);
              $this->PlayerSpeedCounter = ($this->GetConfigEntry("Speed-Threshold") * 10) - 10;
            }
          }
        }
      }
      $this->prev_tick = $tick;
    }

    # No Fly, No Glide and Anti Speed
    if (!$this->SACIsOnGround($this->Player))
    {
      if(    !in_array(Block::WATER               , $this->clipsurroundings ) 
         and !in_array(Block::FLOWING_WATER       , $this->clipsurroundings )
         and !in_array(Block::STILL_WATER         , $this->clipsurroundings )
         and !in_array(Block::LAVA                , $this->clipsurroundings )
         and !in_array(Block::FLOWING_LAVA        , $this->clipsurroundings )
         and !in_array(Block::STILL_LAVA          , $this->clipsurroundings )
         and !in_array(Block::LADDER              , $this->clipsurroundings )
         and !in_array(Block::VINE                , $this->clipsurroundings )
         and !in_array(Block::COBWEB              , $this->clipsurroundings ))
      {
        if ($this->y_pos_old > $this->y_pos_new)
        {
          # Player moves down. Check Glide Hack
          if ($this->GetConfigEntry("Glide"))
          {
            if (!$this->Player->hasPermission("sac.glide"))
            {
              if ($this->y_speed < $this->GetConfigEntry("MinDownfallSpeed"))
              {
                if (($tick - $this->LastMotionTick) > 90)
                {
                  if(!$this->Player->hasEffect(Effect::JUMP_BOOST) and !$this->Player->hasEffect(Effect::LEVITATION))
                  {
                    $this->PlayerGlideCounter+=3;
                  }
                }
              }
            }
          }
        }
        elseif ($this->y_pos_old <= $this->y_pos_new)
        {
          # Player moves up or horizontal
          if ($this->GetConfigEntry("Fly"))
          {
            if (($tick - $this->LastMotionTick) > 90)
            {
              if (($tick - $this->LastSlimeTick) > $this->GetConfigEntry("SlimeSeconds") * 20)
              {
                //$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> $this->PlayerName: outSlime");
                if(!$this->Player->hasEffect(Effect::JUMP_BOOST) and !$this->Player->hasEffect(Effect::LEVITATION))
                {
                  $this->PlayerAirCounter++;
                }
              }
              else
              {
                //$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> $this->PlayerName: inSlime");
              }
            }
          }
        }
      }
    }
    else
    {
      $this->PlayerAirCounter   = 0;
      $this->PlayerGlideCounter = 0;
    }

    if ($this->PlayerGlideCounter > 75)
    {
      if ($this->GetConfigEntry("Glide-Punishment") == "kick")
      {
        $event->setCancelled(true);
        $this->ResetObserver();
        $message = $this->GetConfigEntry("Glide-LogMessage");
        $reason  = $this->GetConfigEntry("Glide-Message");
        $this->NotifyAdmins($message);
        $this->KickPlayer($reason);
      }
      if ($this->GetConfigEntry("Glide-Punishment") == "block")
      {
        $event->setCancelled(true);
        $message = $this->GetConfigEntry("Glide-LogMessage");
        $this->NotifyAdmins($message);
      }
    }

    if ($this->PlayerAirCounter > $this->GetConfigEntry("Fly-Threshold"))
    {
      if ($this->GetConfigEntry("Fly-Punishment") == "kick")
      {
        $event->setCancelled(true);
        $this->ResetObserver();
        $message = $this->GetConfigEntry("Fly-LogMessage");
        $reason  = $this->GetConfigEntry("Fly-Message");
        $this->NotifyAdmins($message);
        $this->KickPlayer($reason);
      }
      if ($this->GetConfigEntry("Fly-Punishment") == "block")
      {
        $event->setCancelled(true);
        $message = $this->GetConfigEntry("Fly-LogMessage");
        $this->NotifyAdmins($message);
      }
    }
  }

  public function CheckNoClip($event)
  {
    # No Clip
    if ($this->GetConfigEntry("NoClip"))
    {
      if ($this->Player->hasPermission("sac.noclip")) return;
      $level    = $this->Player->getLevel();
      $pos      = new Vector3($this->Player->getX(), $this->Player->getY(), $this->Player->getZ());
      $BlockID  = $level->getBlock($pos)->getId();
      $pos2     = new Vector3($this->Player->getX(), $this->Player->getY()+1, $this->Player->getZ());
      $BlockID2 = $level->getBlock($pos2)->getId();

      //ANTI-FALSE-POSITIVES
      if ((

      //BUILDING MATERIAL

         $BlockID == 1
      or $BlockID == 2
      or $BlockID == 3
      or $BlockID == 4
      or $BlockID == 5
      or $BlockID == 7
      or $BlockID == 17
      or $BlockID == 18
      or $BlockID == 20
      or $BlockID == 43
      or $BlockID == 45
      or $BlockID == 47
      or $BlockID == 48
      or $BlockID == 49
      or $BlockID == 79
      or $BlockID == 80
      or $BlockID == 87
      or $BlockID == 89
      or $BlockID == 97
      or $BlockID == 98
      or $BlockID == 110
      or $BlockID == 112
      or $BlockID == 121
      or $BlockID == 155
      or $BlockID == 157
      or $BlockID == 159
      or $BlockID == 161
      or $BlockID == 162
      or $BlockID == 170
      or $BlockID == 172
      or $BlockID == 174
      or $BlockID == 243

      //ORES (for Prison mines)

      or $BlockID == 14  //GOLD     (-)
      or $BlockID == 15  //IRON     (-)
      or $BlockID == 16  //COAL     (-)
      or $BlockID == 21  //LAPIS    (-)
      or $BlockID == 56  //DIAMOND  (-)
      or $BlockID == 73  //REDSTONE (DARK)
      or $BlockID == 73  //REDSTONE (GLOWING)
      or $BlockID == 129 //EMERALD  (-)
      )
      and
      (

      //BUILDING MATERIAL

         $BlockID2 == 1
      or $BlockID2 == 2
      or $BlockID2 == 3
      or $BlockID2 == 4
      or $BlockID2 == 5
      or $BlockID2 == 7
      or $BlockID2 == 17
      or $BlockID2 == 18
      or $BlockID2 == 20
      or $BlockID2 == 43
      or $BlockID2 == 45
      or $BlockID2 == 47
      or $BlockID2 == 48
      or $BlockID2 == 49
      or $BlockID2 == 79
      or $BlockID2 == 80
      or $BlockID2 == 87
      or $BlockID2 == 89
      or $BlockID2 == 97
      or $BlockID2 == 98
      or $BlockID2 == 110
      or $BlockID2 == 112
      or $BlockID2 == 121
      or $BlockID2 == 155
      or $BlockID2 == 157
      or $BlockID2 == 159
      or $BlockID2 == 161
      or $BlockID2 == 162
      or $BlockID2 == 170
      or $BlockID2 == 172
      or $BlockID2 == 174
      or $BlockID2 == 243

      //ORES (for Prison mines)

      or $BlockID2 == 14  //GOLD     (-)
      or $BlockID2 == 15  //IRON     (-)
      or $BlockID2 == 16  //COAL     (-)
      or $BlockID2 == 21  //LAPIS    (-)
      or $BlockID2 == 56  //DIAMOND  (-)
      or $BlockID2 == 73  //REDSTONE (DARK)
      or $BlockID2 == 73  //REDSTONE (GLOWING)
      or $BlockID2 == 129 //EMERALD  (-)
      ))
      {
        #$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> Not_In_Array, VL: $this->PlayerNoClipCounter");
        if ($this->GetConfigEntry("NoClip-Punishment") == "kick")
        {
          $this->PlayerNoClipCounter += 10;
          $event->setCancelled(true);
          if ($this->PlayerNoClipCounter > $this->GetConfigEntry("NoClip-Threshold") * 10)
          {
            $reason = $this->GetConfigEntry("NoClip-Message");
            $this->ResetObserver();
            $this->KickPlayer($reason);
          }
          if ($this->PlayerNoClipCounter > $this->GetConfigEntry("NoClip-Threshold") * 5)
          {
            $message = $this->GetConfigEntry("NoClip-LogMessage");
            $this->NotifyAdmins($message);
          }
        }
        if ($this->GetConfigEntry("NoClip-Punishment") == "block")
        {
            $event->setCancelled(true);
            $message = $this->GetConfigEntry("NoClip-LogMessage");
            $this->NotifyAdmins($message);
        }
      }
      else
      {
        if($this->PlayerNoClipCounter > 0)
        {
          $this->PlayerNoClipCounter--;
        }
      }
    }
  }

  public function CheckTPNoClip($event) : void
  {
    # No Clip
    if ($this->GetConfigEntry("NoClip"))
    {
      if ($this->Player->hasPermission("sac.noclip")) return;
      $level    = $this->Player->getLevel();
      $pos      = new Vector3($event->getTo()->getX(), $event->getTo()->getY(), $event->getTo()->getZ());
      $BlockID  = $level->getBlock($pos)->getId();
      $pos2     = new Vector3($event->getTo()->getX(), $event->getTo()->getY()+1, $event->getTo()->getZ());
      $BlockID2 = $level->getBlock($pos2)->getId();

      //ANTI-FALSE-POSITIVES
      if ((

      //BUILDING MATERIAL

         $BlockID == 1
      or $BlockID == 2
      or $BlockID == 3
      or $BlockID == 4
      or $BlockID == 5
      or $BlockID == 7
      or $BlockID == 17
      or $BlockID == 18
      or $BlockID == 20
      or $BlockID == 43
      or $BlockID == 45
      or $BlockID == 47
      or $BlockID == 48
      or $BlockID == 49
      or $BlockID == 79
      or $BlockID == 80
      or $BlockID == 87
      or $BlockID == 89
      or $BlockID == 97
      or $BlockID == 98
      or $BlockID == 110
      or $BlockID == 112
      or $BlockID == 121
      or $BlockID == 155
      or $BlockID == 157
      or $BlockID == 159
      or $BlockID == 161
      or $BlockID == 162
      or $BlockID == 170
      or $BlockID == 172
      or $BlockID == 174
      or $BlockID == 243

      //ORES (for Prison mines)

      or $BlockID == 14  //GOLD     (-)
      or $BlockID == 15  //IRON     (-)
      or $BlockID == 16  //COAL     (-)
      or $BlockID == 21  //LAPIS    (-)
      or $BlockID == 56  //DIAMOND  (-)
      or $BlockID == 73  //REDSTONE (DARK)
      or $BlockID == 73  //REDSTONE (GLOWING)
      or $BlockID == 129 //EMERALD  (-)
      )
      and
      (

      //BUILDING MATERIAL

         $BlockID2 == 1
      or $BlockID2 == 2
      or $BlockID2 == 3
      or $BlockID2 == 4
      or $BlockID2 == 5
      or $BlockID2 == 7
      or $BlockID2 == 17
      or $BlockID2 == 18
      or $BlockID2 == 20
      or $BlockID2 == 43
      or $BlockID2 == 45
      or $BlockID2 == 47
      or $BlockID2 == 48
      or $BlockID2 == 49
      or $BlockID2 == 79
      or $BlockID2 == 80
      or $BlockID2 == 87
      or $BlockID2 == 89
      or $BlockID2 == 97
      or $BlockID2 == 98
      or $BlockID2 == 110
      or $BlockID2 == 112
      or $BlockID2 == 121
      or $BlockID2 == 155
      or $BlockID2 == 157
      or $BlockID2 == 159
      or $BlockID2 == 161
      or $BlockID2 == 162
      or $BlockID2 == 170
      or $BlockID2 == 172
      or $BlockID2 == 174
      or $BlockID2 == 243

      //ORES (for Prison mines)

      or $BlockID2 == 14  //GOLD     (-)
      or $BlockID2 == 15  //IRON     (-)
      or $BlockID2 == 16  //COAL     (-)
      or $BlockID2 == 21  //LAPIS    (-)
      or $BlockID2 == 56  //DIAMOND  (-)
      or $BlockID2 == 73  //REDSTONE (DARK)
      or $BlockID2 == 73  //REDSTONE (GLOWING)
      or $BlockID2 == 129 //EMERALD  (-)
      ))
      {
        if ($this->GetConfigEntry("NoClip-Punishment") == "kick")
        {
          $this->PlayerNoClipCounter += 10;
          $event->setTo($event->getFrom());
          if ($this->PlayerNoClipCounter > $this->GetConfigEntry("NoClip-Threshold") * 10)
          {
            $reason = $this->GetConfigEntry("NoClip-Message");
            $this->ResetObserver();
            $event->setCancelled(true);
            $this->KickPlayer($reason);
          }
          if ($this->PlayerNoClipCounter > $this->GetConfigEntry("NoClip-Threshold") * 5)
          {
            $message = $this->GetConfigEntry("NoClip-LogMessage");
            $this->NotifyAdmins($message);
            $event->setCancelled(true);
          }
        }
        if ($this->GetConfigEntry("NoClip-Punishment") == "block")
        {
            $message = $this->GetConfigEntry("NoClip-LogMessage");
            $this->NotifyAdmins($message);
            $event->setTo($event->getFrom());
            $event->setCancelled(true);
        }
      }
      else
      {
        if($this->PlayerNoClipCounter > 0)
        {
          $this->PlayerNoClipCounter--;
        }
      }
    }
  }

  public function PlayerHasDamaged($event) : void
  {
    $damaged_entity             = $event->getEntity();
    $is_damaged_entity_a_player = $damaged_entity instanceof Player;
    $damaged_entity_position    = new Vector3($damaged_entity->getX(), $damaged_entity->getY(), $damaged_entity->getZ());
    $damaged_xz_entity_position = new Vector3($damaged_entity->getX(), 0                      , $damaged_entity->getZ());

    $damager                    = $this->Player;    
    $damager_position           = new Vector3($damager->getX()       , $damager->getY()       , $damager->getZ()       );
    $damager_xz_position        = new Vector3($damager->getX()       , 0                      , $damager->getZ()       );

    $damager_direction          = $damager->getDirectionVector();
    $damager_direction          = $damager_direction->normalize();
    $headmove                   = $damager_direction->distance($this->lastDamagerDirection);

    $damager_xz_direction       = $damager->getDirectionVector();
    $damager_xz_direction->y    = 0;
    $damager_xz_direction       = $damager_xz_direction->normalize();

    $entity_xz_direction        = $damaged_xz_entity_position->subtract($damager_xz_position)->normalize();
    $entity_direction           = $damaged_entity_position->subtract($damager_position)->normalize();

    $distance_xz                = $damager_xz_position->distance($damaged_xz_entity_position);
    $distance                   = $damager_position->distance($damaged_entity_position);

    $dot_product_xz = $damager_xz_direction->dot($entity_xz_direction);
    $angle_xz       = rad2deg(acos($dot_product_xz));

    $dot_product    = $damager_direction->dot($entity_direction);
    $angle          = rad2deg(acos($dot_product));

    $tick_count = (double)$this->Server->getTick() - $this->LastMoveTick;
    $tps        = (double)$this->Server->getTicksPerSecond();
    if ($tps != 0) $delta_t    = (double)($tick_count) / (double)$tps;
    else           $delta_t    = 0;

    $orient = ($damager_xz_direction->x * $entity_xz_direction->z) - ($damager_xz_direction->z * $entity_xz_direction->x);
    if ($orient > 0)
    {
      $trueangle = $angle_xz;
    }
    else
    {
      $trueangle = -$angle_xz;
    }
    $aimconsistency = abs($trueangle - $this->LastAngle);

    #$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> Kill Aura Counter: $this->PlayerKillAuraCounter V2: $this->PlayerKillAuraV2Counter Speed: $this->x_speed Hit: $this->PlayerHitCounter HeadMove: $headmove");
    #$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> $this->PlayerName : consistency = $aimconsistency, VL= $this->PlayerKillAuraV2Counter");
    #$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> $this->PlayerName : Trueangle = $trueangle");
    if ($this->Player->getGameMode() == 1 or $this->Player->getGameMode() == 3) return;
    // Kill Aura
    if ($this->GetConfigEntry("KillAura"))
    {
      if (!$this->Player->hasPermission("sac.killaura"))
      {
        if ($is_damaged_entity_a_player)
        {
          $tick = (double)$this->Server->getTick(); 
          $tps  = (double)$this->Server->getTicksPerSecond();
          if ($this->PlayerHitFirstTick == -1)
          {
            $this->PlayerHitFirstTick = $tick;
          }

          $tick_count = (double)($tick - $this->PlayerHitFirstTick);   // server ticks since last hit
          $delta_t    = (double)($tick_count) / (double)$tps;          // seconds since last hit

          $this->hs_time_sum = $this->hs_time_sum - $this->hs_time_array[$this->hs_arr_idx] + $delta_t;      // ringbuffer time sum  (remove oldest, add new)
          $this->hs_time_array[$this->hs_arr_idx] = $delta_t;                                                // overwrite oldest delta_t  with the new one
          $this->hs_arr_idx++;                                                                               // Update ringbuffer position
          if ($this->hs_arr_idx >= $this->hs_arr_size) $this->hs_arr_idx = 0;
          $this->hs_hit_time = $this->hs_time_sum / $this->hs_arr_size;
          #$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> THD $this->PlayerName : hittime = $this->hs_hit_time");
          if ($this->GetConfigEntry("FastClick"))
          {
            if ($this->hs_hit_time < $this->cps_thr1)
            {
              $this->PlayerHitCounter += 2;
            }
            else
            {
              if($this->PlayerHitCounter > 0)
              {
                $this->PlayerHitCounter--;
              }
            }
            //Allow a maximum of 10 Unlegit hits, couter derceases x2 slower
            if($this->PlayerHitCounter > 40)
            {
              $event->setCancelled(true);
              $this->ResetObserver();
              $message = $this->GetConfigEntry("KillAura-LogMessage");
              $reason  = $this->GetConfigEntry("KillAura-Message");
              $this->NotifyAdmins($message);
              $this->KickPlayer($reason);
              return;
            }
          }
          $this->PlayerHitFirstTick = $tick;
          if ($distance_xz >= 0.5)
          {
            # Killaura Heuristics
            if ($this->dist_thr1 != 0.00)
            {
              # AKAHAD
              if (($distance >= $this->dist_thr2) and 
                  ($delta_t  <  0.5             ) and
                  ($angle_xz >  45.0            ) and
                  (
                   (($this->x_speed > 1.25) and ($this->hs_hit_time < 0.75)) or ($this->x_speed > 4.0)
                  ))
              {
                //$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> AKAHAD L1: FAIL");
                $this->PlayerKillAuraV2Counter+=6;
              }
              elseif (($distance >= $this->dist_thr1) and 
                      ($delta_t  <  0.5             ) and
                      ($angle_xz >  22.5            ) and
                      (
                       (($this->x_speed > 1.25) and ($this->hs_hit_time < 0.75)) or ($this->x_speed > 4.5)
                      ))
              {
                //$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> AKAHAD L2: FAIL");
                $this->PlayerKillAuraV2Counter+=6;
              }
              elseif (($distance >= $this->dist_thr4) and 
                      ($delta_t  <  0.5             ) and
                      ($angle_xz >  11.25           ) and
                      (
                       (($this->x_speed > 1.25) and ($this->hs_hit_time < 0.75)) or ($this->x_speed > 4.5)
                      ))
              {
                //$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> AKAHAD L3: FAIL");
                $this->PlayerKillAuraV2Counter+=4;
              }                         
              elseif (($distance >= $this->dist_thr3) and 
                      ($delta_t  <  0.5             ) and
                      (
                       (($this->x_speed > 1.25) and ($this->hs_hit_time < 0.75)) or ($this->x_speed > 4.5)
                      ))
              {
                if ($this->dist_thr3 != 0.000)
                {
                  //$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> AKAHAD L4: FAIL");
                  $this->PlayerKillAuraV2Counter+=4;
                }
              }
              # AimConsistency
              elseif (($aimconsistency <= $this->accuracy_thr1) and
                      ($headmove >= 0.25                      ) and
                      ($delta_t  <  0.5                       ) and
                      (
                        ($this->x_speed > 4.0)
                      ))
              {
                if ($this->dist_thr3 != 0.000)
                {
                  $this->PlayerKillAuraV2Counter+=4;
                  //$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> Consistency: FAIL > Consistency: $aimconsistency");
                }
              }
              # AimAcurracy
              elseif (($angle_xz < $this->aim_thr1) and
                      ($angle < $this->aim_thr2   ) and
                      ($headmove > 0.25           ) and
                      ($delta_t < 0.5             ) and
                      (
                        ($this->x_speed > 4.0)
                      ))
              {
                if ($this->dist_thr3 != 0.000)
                {
                  //$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> Accuracy: FAIL");
                  $this->PlayerKillAuraV2Counter+=6;
                }
              }
              else
              {
                if ($this->PlayerKillAuraV2Counter > 0)
                {
                  $this->PlayerKillAuraV2Counter--;
                }
              }
            }
            # Normal Killaura Detection
            if ($distance_xz >= $this->mindist)
            {
              if ($angle_xz > 90)
              {
                if ($this->dist_thr1 != 0.00)
                {
                  $this->PlayerKillAuraV2Counter+=6;
                }
                if ($this->GetConfigEntry("Angle"))
                {
                  $this->PlayerKillAuraCounter+=8;
                }
              }
            }
            # Reach detection
            if ($distance_xz >= $this->GetConfigEntry("ViolationRange"))
            {
              $this->PlayerKillAuraCounter+=6;
              if ($this->dist_thr1 != 0.00)
              {
                $this->PlayerKillAuraV2Counter+=6;
              }
            }
            if ($angle_xz > 45)
            {
              if ($this->GetConfigEntry("Angle"))
              {
                $event->setCancelled(true);
              }
            }
            else
            {
              if ($this->PlayerKillAuraCounter > 0)
              {
                if ($angle_xz < 45)
                {
                  $this->PlayerKillAuraCounter--;
                }
              }
            }
          }

          if (($this->PlayerKillAuraCounter >= $this->GetConfigEntry("KillAura-Threshold")))
          {
            $event->setCancelled(true);
            $reason = $this->GetConfigEntry("KillAura-Message");
            $this->ResetObserver();
            $this->KickPlayer($reason);
          }
          if (($this->PlayerKillAuraCounter > $this->GetConfigEntry("KillAura-Threshold") * 0.25))
          {
            $message = $this->GetConfigEntry("KillAura-LogMessage");
            $this->NotifyAdmins($message);
            //$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "$message");
          }
          if (($this->PlayerKillAuraV2Counter >= $this->GetConfigEntry("KillAura-Threshold")))
          {
            $event->setCancelled(true);
            $reason = $this->GetConfigEntry("KillAura-HEUR-Message");
            $this->ResetObserver();
            $this->KickPlayer($reason);
          }
          if (($this->PlayerKillAuraV2Counter > $this->GetConfigEntry("KillAura-Threshold") * 0.25))
          {
            $message = $this->GetConfigEntry("KillAura-HEUR-LogMessage");
            $this->NotifyAdmins($message);
            //$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "$message");
          }
        }
      }
    }

    //Reach Check
    if ($this->GetConfigEntry("Reach"))
    {
      if (!$this->Player->hasPermission("sac.reach"))
      {
        #$this->Logger->debug(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> Reach distance $this->PlayerName : distance");
        if ($distance > $this->GetConfigEntry("MaxRange"))
        {
          $event->setCancelled(true);
        }
      }
      /*
      if ($reach_distance > $this->GetConfigEntry("KickRange"))
      {
        $this->PlayerReachCounter++;
        #$this->Logger->debug(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> $this->PlayerName  ReachCounter: $this->PlayerReachCounter");
        $tick = (double)$this->Server->getTick(); 
        $tps  = (double)$this->Server->getTicksPerSecond();

        if ($this->PlayerReachFirstTick == -1)
        {
          $this->PlayerReachFirstTick = $tick;
        }
        if ($this->PlayerReachCounter > 4 and $tps > 0)
        {
          $tick_count = (double)($tick - $this->PlayerReachFirstTick); // server ticks since last reach hack
          $delta_t    = (double)($tick_count) / (double)$tps;          // seconds since first reach hack

          if ($delta_t < 60)
          {
            if ($this->GetConfigEntry("Reach-Punishment") == "kick")
            {
              $event->setCancelled(true);
              $this->ResetObserver();
              $message = $this->GetConfigEntry("Reach-LogMessage");
              $reason  = $this->GetConfigEntry("Reach-Message");
              $this->NotifyAdmins($message);
              $this->KickPlayer($reason);
              return;
            }
            if ($this->GetConfigEntry("Reach-Punishment") == "block")
            {
              $event->setCancelled(true);
              $message = $this->GetConfigEntry("Reach-LogMessage");
              $this->NotifyAdmins($message);
            }
          }
          else
          {
            $this->PlayerReachFirstTick = $tick;
            $this->PlayerReachCounter   = 0;
          }
        }
      }
      */
    }
    $this->LastAngle = $trueangle;
    $this->lastDamagerDirection = $damager_direction;
  }


  public function PlayerWasDamaged($event) : void
  {
    if ($event->getOriginalBaseDamage() >= 1)
    {
      //$this->Logger->info(TextFormat::ESCAPE. "$this->Colorized" . "PWD-success");
      $this->LastDamageTick = $this->Server->getTick();  // remember time of last damage
      $this->PlayerSpeedCounter -= 8;
      $this->PlayerAirCounter -= 2;
      $this->PlayerGlideCounter -= 2;
    }
  }

  public function OnMotion($event) : void
  {
    //$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> $this->PlayerName : OnMotion");
    $this->PlayerSpeedCounter = 0;
    $this->PlayerAirCounter   = 0;
    $this->PlayerGlideCounter = 0;
    $this->LastMotionTick = $this->Server->getTick();
  }


  public function PlayerShotArrow($event) : void
  { 
    $damager                    = $this->Player;
    $damager_position           = new Vector3($damager->getX()       , $damager->getY()       , $damager->getZ()       );
    $damager_xz_position        = new Vector3($damager->getX()       , 0                      , $damager->getZ()       );
    $tick_count = (double)$this->Server->getTick() - $this->LastMoveTick;
    $tps        = (double)$this->Server->getTicksPerSecond();
    if ($tps != 0) $delta_t    = (double)($tick_count) / (double)$tps;
    else           $delta_t    = 0; 

    if ($this->Player->getGameMode() == 1 or $this->Player->getGameMode() == 3) return;
    if ($this->GetConfigEntry("FastBow"))
    {
      $tick = (double)$this->Server->getTick(); 
      $tps  = (double)$this->Server->getTicksPerSecond();
      if ($this->PlayerShootFirstTick == -1)
      {
        $this->PlayerShootFirstTick = $tick - 30;
      }
      $tick_count = (double)($tick - $this->PlayerShootFirstTick);   // server ticks since last hit
      $delta_t    = (double)($tick_count) / (double)$tps;          // seconds since last hit

      $this->hs_time_sum = $this->hs_time_sum - $this->hs_time_array[$this->hs_arr_idx] + $delta_t;      // ringbuffer time sum  (remove oldest, add new)
      $this->hs_time_array[$this->hs_arr_idx] = $delta_t;                                                // overwrite oldest delta_t  with the new one
      $this->hs_arr_idx++;                                                                               // Update ringbuffer position
      if ($this->hs_arr_idx >= $this->hs_arr_size) $this->hs_arr_idx = 0;
      $this->hs_hit_time = $this->hs_time_sum / $this->hs_arr_size;
      #$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> $this->PlayerName : Shottime = $this->hs_hit_time");

      if ($this->hs_hit_time < 0.65)
      {
        if ($this->GetConfigEntry("FastBow-Punishment") == "kick")
        {
          $this->PlayerShootCounter += 3;
        }
        $message = $this->GetConfigEntry("FastBow-LogMessage");
        $this->NotifyAdmins($message);
        $event->setCancelled(true);

      }
      else
      {
        if($this->PlayerShootCounter > 0)
        {
          $this->PlayerShootCounter--;
        }
      }
      //Allow a maximum of 6 Unlegit shots, couter derceases x3 slower
      if ($this->GetConfigEntry("FastBow-Punishment") == "kick")
      {
        if($this->PlayerShootCounter > 20)
        {
          $event->setCancelled(true);
          $this->ResetObserver();
          $reason  = $this->GetConfigEntry("FastBow-Message");
          $this->KickPlayer($reason);
          return;
        }
      }
      $this->PlayerShootFirstTick = $tick;
    }
  }


  public function onDeath($event) : void
  {
    $this->ResetMovement();
    $this->LastDamageTick = $this->Server->getTick();  // remember time of last damage
  }


  public function onRespawn($event) : void
  {
    $this->ResetMovement();
    $this->LastDamageTick = $this->Server->getTick();  // remember time of last damage
  }

  public function onTeleport($event) : void
  {
    $this->CheckForceOP($event);
    if ($event->getFrom()->getLevel() != null)
    {
      $fromworldname = $event->getFrom()->getLevel()->getName();
      //$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> From world is LOADED");
    }
    else
    {
      //$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "<< SAC >> From world is NOT LOADED");
      return;
    }
    if ($event->getTo()->getLevel() != null)
    {
      $toworldname   = $event->getTo()->getLevel()->getName();
      //$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "To world is LOADED");
    }
    else
    {
      //$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "To world is NOT LOADED");
      return;
    }
    if ($this->Player->getGameMode() != null)
    {
      if ($this->Player->getGameMode() == 1 or $this->Player->getGameMode() == 3) return;
    }
    else
    {
      return;
    }
    if ($fromworldname == $toworldname)
    {
      $this->CheckTPNoClip($event);
      //$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "1");
    }
    $this->ResetMovement();
    $this->LastDamageTick = $this->Server->getTick();  // remember time of last damage
  }
}

//////////////////////////////////////////////////////
//                                                  //
//     SAC by DarkWav.                              //
//     Distributed under the GGPL License.          //
//     Copyright (C) 2018 DarkWav                   //
//                                                  //
//////////////////////////////////////////////////////

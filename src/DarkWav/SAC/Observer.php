<?php

namespace DarkWav\SAC;

use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\block\BlockIds;
use pocketmine\block\Block;
use DarkWav\SAC\EventListener;
use pocketmine\entity\Effect;

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
    $this->JoinCounter             = 0;
    $this->KickMessage             = "";

    $this->PlayerAirCounter        = 0;
    $this->PlayerSpeedCounter      = 0;
    $this->PlayerGlideCounter      = 0;
    $this->PlayerNoClipCounter     = 0;
    $this->PlayerReachCounter      = 0;
    $this->PlayerReachFirstTick    = -1;
    $this->PlayerHitFirstTick      = -1;
    $this->PlayerHitCounter        = 0;
    $this->PlayerKillAuraCounter   = 0;
    $this->PlayerKillAuraV2Counter = 0;
    $this->SpeedAMP                = 0;
    
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
    
    $this->surroundings = array();
   
    $this->LastDamageTick = 0;
    $this->LastMoveTick   = 0;
    $this->Colorized      = $this->GetConfigEntry("Color");
    
    if     ($this->GetConfigEntry("AKAHAD") == 1)
    {
      $this->dist_thr1 = 4.00;
      $this->dist_thr2 = 3.75;
    }
    elseif ($this->GetConfigEntry("AKAHAD") == 2)
    {
      $this->dist_thr1 = 3.75;
      $this->dist_thr2 = 3.50;
    }
    elseif ($this->GetConfigEntry("AKAHAD") == 3)
    {
      $this->dist_thr1 = 3.50;
      $this->dist_thr2 = 3.25;
    }
    else
    {
      $this->dist_thr1 = 0.00;
      $this->dist_thr2 = 0.00;
    }
    if     ($this->GetConfigEntry("AKAHAD-MacroCapture") == 1)
    {
      $this->dist_thr3 = 4.125;
    }
    elseif ($this->GetConfigEntry("AKAHAD-MacroCapture") == 2)
    {
      $this->dist_thr3 = 3.825;
    }
    else
    {
      $this->dist_thr3 = 0.000;
    }
  }  
  
  public function ResetObserver()
  {
    $this->PlayerReachCounter      =  0;
    $this->PlayerReachFirstTick    = -1;
    $this->PlayerHitFirstTick      = -1;
    $this->PlayerHitCounter        =  0;
    $this->PlayerKillAuraCounter   =  0;
    $this->PlayerKillAuraV2Counter =  0;

    $this->ResetMovement();
  }

  
  public function ResetMovement()
  {
    $this->PlayerAirCounter      = 0;
    $this->PlayerSpeedCounter    = 0;
    $this->PlayerGlideCounter    = 0;
    $this->PlayerNoClipCounter   = 0;
    $this->LastMoveTick          = 0;

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

  public function SACIsOnGround($pp)
  {
    if     ( $this->AllBlocksAir()      ) return false;
    else                                  return $this->Player->IsOnGround();
  }

  public function ScanMessage($message)
  {
    $pos    = strpos(strtoupper($message), "%PLAYER%");
    $newmsg = $message;
    if ($pos !== false)
    {
      $newmsg = substr_replace($message, $this->PlayerName, $pos, 8);
    }    
    return $newmsg;
  }

  public function GetConfigEntry($cfgkey)
  {
    $msg = $this->Main->getConfig()->get($cfgkey);
    return $this->ScanMessage($msg);    
  }

  public function KickPlayer($reason)
  {
    if (!in_array($this, $this->Main->PlayersToKick))
    {
      // Add current Observer to the array of Observers whose players shall be kicked ASAP
      $this->KickMessage = $reason;
      $this->Main->PlayersToKick[] = $this;
    }
  }

  public function NotifyAdmins($message)
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
  
  public function PlayerQuit()
  {
    if ($this->GetConfigEntry("I-AM-WATCHING-YOU"))
    {
      $this->Logger->debug(TextFormat::ESCAPE."$this->Colorized" . "[SAC] > $this->PlayerName is no longer watched...");
    }
  }

  public function PlayerJoin()
  {
    $this->JoinCounter++;
    if ($this->GetConfigEntry("I-AM-WATCHING-YOU"))
    {
      $this->Player->sendMessage(TextFormat::ESCAPE."$this->Colorized"."[SAC] > $this->PlayerName, I am watching you ...");
    }
  }
  
  public function PlayerRejoin()
  {
    $this->JoinCounter++;
    if ($this->GetConfigEntry("I-AM-WATCHING-YOU"))
    {
      $this->Player->sendMessage(TextFormat::ESCAPE."$this->Colorized"."[SAC] > $this->PlayerName, I am still watching you ...");
      $this->Logger->debug      (TextFormat::ESCAPE."$this->Colorized"."[SAC] > $this->PlayerName joined this server $this->JoinCounter times since server start");
    }
  }

  public function AllBlocksAir()
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


  public function OnBlockPlaceEvent($event)
  {
    $this->PlayerAirCounter--;
  }


  public function OnBlockBreakEvent($event)
  {
  }


  public function PlayerRegainHealth($event)
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
  public function OnMove($event)
  {
    $this->LastMoveTick = (double)$this->Server->getTick();
    $this->CheckForceOP($event);
    if ($this->Player->getGameMode() == 1 or $this->Player->getGameMode() == 3) return;
    
    $this->GetSurroundingBlocks();
    $this->CheckSpeedFlyGlide($event);
    $this->CheckNoClip($event);
  }

  # -------------------------------------------------------------------------------------
  # CheckForceOP: Check if the player is a legit OP
  # -------------------------------------------------------------------------------------
  public function CheckForceOP($event)
  {
    if ($this->GetConfigEntry("ForceOP"))
    {
      if ($this->Player->isOp())
      {
        if (!$this->Player->hasPermission($this->GetConfigEntry("ForceOP-Permission")))
        {
          $event->setCancelled(true);
          $message = "[SAC] > %PLAYER% used ForceOP!";
          $reason = "[SAC] > ForceOP detected!";
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
    
    $bpos1       = $level->getBlock($pos1)->getId();
    $bpos2       = $level->getBlock($pos2)->getId();
    $bpos3       = $level->getBlock($pos3)->getId();
    $bpos4       = $level->getBlock($pos4)->getId();
    $bpos5       = $level->getBlock($pos5)->getId();
    $bpos6       = $level->getBlock($pos6)->getId();
    $bpos7       = $level->getBlock($pos7)->getId();
    $bpos8       = $level->getBlock($pos8)->getId();
    $bpos9       = $level->getBlock($pos9)->getId();
    
    $this->surroundings = array ($bpos1, $bpos2, $bpos3, $bpos4, $bpos5, $bpos6, $bpos7, $bpos8, $bpos9);    
  }
  
  # -------------------------------------------------------------------------------------
  # CheckSpeedFlyGlide: Check if player is flying, gliding or moving too fast
  # -------------------------------------------------------------------------------------
  public function CheckSpeedFlyGlide($event)
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

      $tick = (double)$this->Server->getTick(); 
      $tps  = (double)$this->Server->getTicksPerSecond();

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
            # Anti Speed
            if ($this->Player->hasEffect(Effect::SPEED))
            {
              $this->SpeedAMP = $this->Player->getEffect(Effect::SPEED)->getAmplifier();
              if ($this->SpeedAMP < 3)
              {
                if ($this->x_speed > 10)
                {
                  if (($tick - $this->LastDamageTick) > 30)  # deactivate 1.5 seconds after receiving damage
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
              else
              {
                $this->PlayerSpeedCounter--;
              }
            }
            elseif ($this->x_speed > 8.5)
            {
              if (($tick - $this->LastDamageTick) > 30)  # deactivate 1.5 seconds after receiving damage
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
      if(    !in_array(Block::WATER               , $this->surroundings ) 
         and !in_array(Block::STILL_WATER         , $this->surroundings )
         and !in_array(Block::LAVA                , $this->surroundings )
         and !in_array(Block::STILL_LAVA          , $this->surroundings )
         and !in_array(Block::LADDER              , $this->surroundings )
         and !in_array(Block::VINE                , $this->surroundings )
         and !in_array(Block::COBWEB              , $this->surroundings ))
      {
        if ($this->y_pos_old > $this->y_pos_new)
        {
          # Player moves down. Check Glide Hack
          if ($this->GetConfigEntry("Glide"))
          {
            if (!$this->Player->hasPermission("sac.glide"))
            {
              $this->PlayerGlideCounter++;
            }
          }
        }
        elseif ($this->y_pos_old <= $this->y_pos_new)
        {
          # Player moves up or horizontal
          if ($this->GetConfigEntry("Fly"))
          {
            $this->PlayerAirCounter++;
            if ($this->PlayerGlideCounter > 0)
            {
              $this->PlayerGlideCounter--;
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
    
    if ($this->PlayerGlideCounter > 25 and $this->y_speed < 20)
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
      $level   = $this->Player->getLevel();
      $pos     = new Vector3($this->Player->getX(), $this->Player->getY(), $this->Player->getZ());
      $BlockID = $level->getBlock($pos)->getId();

      //ANTI-FALSE-POSITIVES
      if (

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
      {
        if(    !in_array(Block::SANDSTONE_STAIRS    , $this->surroundings )
           and !in_array(Block::DARK_OAK_STAIRS     , $this->surroundings )
           and !in_array(Block::ACACIA_STAIRS       , $this->surroundings )
           and !in_array(Block::NETHER_BRICK_STAIRS , $this->surroundings )
           and !in_array(Block::SPRUCE_STAIRS       ,$this->surroundings )
           and !in_array(Block::BIRCH_STAIRS        , $this->surroundings )
           and !in_array(Block::JUNGLE_STAIRS       , $this->surroundings )
           and !in_array(Block::QUARTZ_STAIRS       , $this->surroundings )
           and !in_array(Block::OAK_STAIRS          , $this->surroundings )
           and !in_array(Block::SNOW                , $this->surroundings ))
        {        
          if ($this->GetConfigEntry("NoClip-Punishment") == "kick")
          {
            $this->PlayerNoClipCounter += 10;
            $event->setCancelled(true);
            $message = $this->GetConfigEntry("NoClip-LogMessage");
            $this->NotifyAdmins($message);
            if ($this->PlayerNoClipCounter > $this->GetConfigEntry("NoClip-Threshold") * 10)
            {
              $event->setCancelled(true);
              $reason = $this->GetConfigEntry("NoClip-Message");
              $this->ResetObserver();
              $this->KickPlayer($reason);
            }
          }
          if ($this->GetConfigEntry("NoClip-Punishment") == "block")
          {
            $event->setCancelled(true);
            $message = $this->GetConfigEntry("NoClip-LogMessage");
            $this->NotifyAdmins($message);
          }
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
  
  public function OnPlayerGameModeChangeEvent($event)
  {
    if ($this->GetConfigEntry("ForceGameMode"))
    {
      if ($this->Player->hasPermission("sac.forcegamemode")) return;
      if(!$event->getPlayer()->isOp())
      {
        $message = $this->GetConfigEntry("ForceGameMode-LogMessage");
        $this->NotifyAdmins($message);
        $reason  = $this->GetConfigEntry("ForceGameMode-Message");
        $this->KickPlayer($reason);
        $event->$event->setCancelled(true);
      }
      else
      {
        return;
      }
    }
    else
    {
      return;
    }
  }
  
  public function PlayerHasDamaged($event)
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
    
    #$this->Logger->debug(TextFormat::ESCAPE."$this->Colorized" . "[SAC] > Kill Aura Counter: $this->PlayerKillAuraCounter     V2: $this->PlayerKillAuraV2Counter  Speed: $this->x_speed");
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
          #$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "[SAC] > THD $this->PlayerName : hittime = $this->hs_hit_time");
        
          if ($this->hs_hit_time < 0.0825)
          {
            $this->PlayerHitCounter += 3;
          }
          else
          {
            if($this->PlayerHitCounter > 0)
            {
              $this->PlayerHitCounter--;
            }
          }
          //Allow a maximum of 3 Unlegit hits, couter derceases x3 slower
          if($this->PlayerHitCounter > 10)
          {
            $event->setCancelled(true);
            $this->ResetObserver();
            $message = $this->GetConfigEntry("KillAura-LogMessage");
            $reason  = $this->GetConfigEntry("KillAura-Message");
            $this->NotifyAdmins($message);
            $this->KickPlayer($reason);
            return;
          }
          $this->PlayerHitFirstTick = $tick;
          if ($distance_xz >= 0.5)
          {
            # V2 
            if ($this->dist_thr1 != 0.00)
            {
              if (($distance >= $this->dist_thr1) and 
                  ($delta_t  <  0.50            ) and
                  ($angle_xz >  22.5            ) and
                  (
                    (($this->x_speed > 1.5) and ($this->hs_hit_time < 0.5)) or ($this->x_speed > 4.75)
                  ))
              {
                $this->PlayerKillAuraV2Counter+=2;
              }
              elseif (($distance >= $this->dist_thr2) and 
                      ($delta_t  <  0.50            ) and
                      ($angle_xz >  45.0            ) and
                      (
                        (($this->x_speed > 1.5) and ($this->hs_hit_time < 0.5)) or ($this->x_speed > 4.75)
                      ))
              {
                $this->PlayerKillAuraV2Counter+=2;
              }
              elseif (($distance >= $this->dist_thr3) and 
                      ($delta_t  <  0.50            ) and
                      (
                       (($this->x_speed > 1.5) and ($this->hs_hit_time < 0.5)) or ($this->x_speed > 4.75)
                      ))
              {
                if ($this->dist_thr3 != 0.000)
                {
                  $this->PlayerKillAuraV2Counter+=2;
                }
              }
              elseif (!$this->SACIsOnGround($damager))
              {
                $this->PlayerKillAuraV2Counter+=4;
              }
              else
              {
                if ($this->PlayerKillAuraV2Counter > 0)
                {
                  $this->PlayerKillAuraV2Counter--;
                }
              }
            }
            
            if ($angle_xz > 45)
            {
              if ($this->GetConfigEntry("Angle"))
              {
                $event->setCancelled(true);
              }
              if ($angle_xz > 90)
              {
                if ($this->dist_thr1 != 0.00)
                {
                  $this->PlayerKillAuraV2Counter+=8;
                }
                if ($this->GetConfigEntry("Angle"))
                {
                  $this->PlayerKillAuraCounter+=8;
                }
              }
            }            
            if ($this->GetConfigEntry("AimbotCatcher"))
            {
              #$this->Logger->debug(TextFormat::ESCAPE."$this->Colorized" . "[SAC] > counter V2: $this->PlayerKillAuraV2Counter");
              # V1
              if (($angle_xz < 1.5) and ($angle < 20) and ($delta_t < 0.5) and ($this->x_speed > 4.75))
              {
                $this->PlayerKillAuraCounter+=2;
              }
              if (($angle_xz >= 1.5) or ($angle >= 20) or ($delta_t > 2.0))
              {
                if ($this->PlayerKillAuraCounter > 0)
                {
                  if (!$angle_xz > 90)
                  {
                    $this->PlayerKillAuraCounter--;
                  }
                }   
              }      
              #$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "[SAC] > counter V1: $this->PlayerKillAuraCounter  V2: $this->PlayerKillAuraV2Counter distance: $distance_xz  deltat: $delta_t  speedx: $this->x_speed anglexz: $angle_xz");
            }
            #$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "AAA[SAC] > counter V1: $this->PlayerKillAuraCounter  V2: $this->PlayerKillAuraV2Counter distance: $distance_xz  deltat: $delta_t  speedx: $this->x_speed anglexz: $angle_xz");
          }  
      
          if (($this->PlayerKillAuraCounter >= $this->GetConfigEntry("KillAura-Threshold")) or ($this->PlayerKillAuraV2Counter >= $this->GetConfigEntry("KillAura-Threshold")))
          {
            $event->setCancelled(true);
            $message = $this->GetConfigEntry("KillAura-LogMessage");
            $this->NotifyAdmins($message);
            $reason = $this->GetConfigEntry("KillAura-Message");
            $this->ResetObserver();
            $this->KickPlayer($reason);
          }
        }
      }
    }

    //Reach Check
    if ($this->GetConfigEntry("Reach"))
    {
      if (!$this->Player->hasPermission("sac.reach"))
      {
        $reach_distance = $damager_position->distance($damaged_entity_position); 
        #$this->Logger->debug(TextFormat::ESCAPE."$this->Colorized" . "[SAC] > Reach distance $this->PlayerName : $reach_distance");
      
        if ($reach_distance > $this->GetConfigEntry("MaxRange"))
        {
          $event->setCancelled(true);
        }
      }
      /*
      if ($reach_distance > $this->GetConfigEntry("KickRange"))
      {
        $this->PlayerReachCounter++;
        #$this->Logger->debug(TextFormat::ESCAPE."$this->Colorized" . "[SAC] > $this->PlayerName  ReachCounter: $this->PlayerReachCounter");
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
  }


  public function PlayerWasDamaged($event)
  {
    if ($event->getDamage() >= 1)
    {
      $this->LastDamageTick = $this->Server->getTick();  // remember time of last damage
      $this->PlayerSpeedCounter -= 8;
    }
  }
  

  public function PlayerShotArrow($event)
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
    
    if ($this->Player->getGameMode() == 1 or $this->Player->getGameMode() == 3) return;
    if ($this->GetConfigEntry("FastBow"))
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
        #$this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "[SAC] > THD $this->PlayerName : hittime = $this->hs_hit_time");
    
        if ($this->hs_hit_time < 0.8)
        {
          $this->PlayerHitCounter += 3;
        }
        else
        {
          if($this->PlayerHitCounter > 0)
          {
            $this->PlayerHitCounter--;
          }
        }
        //Allow a maximum of 3 Unlegit shots, couter derceases x3 slower
        if($this->PlayerHitCounter > 10)
        {
          $event->setCancelled(true);
          $this->ResetObserver();
          $message = $this->GetConfigEntry("FastBow-LogMessage");
          $reason  = $this->GetConfigEntry("FastBow-Message");
          $this->NotifyAdmins($message);
          $this->KickPlayer($reason);
          return;
        }
        $this->PlayerHitFirstTick = $tick;
      }
    }
  }


  public function onDeath($event)
  {
    $this->ResetMovement();
    $this->LastDamageTick = $this->Server->getTick();  // remember time of last damage
  }  


  public function onRespawn($event)
  {
    $this->ResetMovement();
    $this->LastDamageTick = $this->Server->getTick();  // remember time of last damage
  }  
  

  public function onTeleport($event)
  {
    $this->ResetMovement();
    $this->LastDamageTick = $this->Server->getTick();  // remember time of last damage
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
<?php

namespace DarkWav\VAC;

use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\block\Block;
use DarkWav\VAC\EventListener;
use pocketmine\entity\Effect;

class Observer
{
  public $Player;
  public $surroundings;
  
  public function __construct($player, VAC $VAC)
  {
    $this->Player                = $player;
    $this->PlayerName            = $this->Player->getName();
    $this->Main                  = $VAC;
    $this->ClientID              = $player->getClientId();
    $this->Logger                = $VAC->getServer()->getLogger();
    $this->Server                = $VAC->getServer();
    $this->JoinCounter           = 0;
    $this->KickMessage           = "";

    $this->PlayerAirCounter     = 0;
    $this->PlayerSpeedCounter   = 0;
    $this->PlayerGlideCounter   = 0;
    $this->PlayerNoClipCounter  = 0;
    $this->PlayerReachCounter   = 0;
    $this->PlayerReachFirstTick = -1;
    $this->PlayerHitFirstTick   = -1;
    $this->PlayerHitCounter     = 0;
    
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
    
    $this->x_pos_old    = new Vector3(0.0, 0.0, 0.0);
    $this->x_pos_new    = new Vector3(0.0, 0.0, 0.0);
    $this->y_pos_old    = 0.0;
    $this->y_pos_new    = 0.0;
   
    $this->heal_counter = 0;
    $this->heal_time    = 0;    
    
    $this->surroundings = array();
    
    $this->LastDamageTick = 0;
  }  
  
  public function ResetObserver()
  {
    $this->PlayerAirCounter     = 0;
    $this->PlayerSpeedCounter   = 0;
    $this->PlayerGlideCounter   = 0;
    $this->PlayerNoClipCounter  = 0;
    $this->PlayerReachCounter   = 0;
    $this->PlayerReachFirstTick = -1;
    $this->PlayerHitFirstTick   = -1;
    $this->PlayerHitCounter     = 0;
    
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
        if ($player != null and $this->Player->hasPermission("vac.admin"))
        {
          $player->sendMessage(TextFormat::DARK_PURPLE . $newmsg);
        }
      }
    }  
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
  
  public function PlayerQuit()
  {
    if ($this->GetConfigEntry("I-AM-WATCHING-YOU"))
    {
      $this->Logger->debug(TextFormat::DARK_PURPLE . "[VAC] > $this->PlayerName is no longer watched...");
    }
  }

  public function PlayerJoin()
  {
    $this->JoinCounter++;
    if ($this->GetConfigEntry("I-AM-WATCHING-YOU"))
    {
      $this->Player->sendMessage(TextFormat::DARK_PURPLE."[VAC] > $this->PlayerName, I am watching you ...");
    }
  }
  
  public function PlayerRejoin()
  {
    $this->JoinCounter++;
    if ($this->GetConfigEntry("I-AM-WATCHING-YOU"))
    {
      $this->Player->sendMessage(TextFormat::DARK_PURPLE."[VAC] > $this->PlayerName, I am still watching you ...");
      $this->Logger->debug      (TextFormat::DARK_PURPLE."[VAC] > $this->PlayerName joined this server $this->JoinCounter times since server start");
    }
  }

  public function PlayerRegainHealth($event)
  {
    if($this->GetConfigEntry("Regen"))
    {
      if ($this->Player->hasPermission("vac.regen")) return;
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
  
  /*
  //THIS IS IN-DEV AND NOT USEABLE!
  public function getRealKnockBack($event)
  {
    $pos1        = new Vector3($this->Player->getX(), $this->Player->getY(), $this->Player->getZ());
    $pos2        = new Vector3($this->Player->getX()-1, $this->Player->getY(), $this->Player->getZ());
    $pos3        = new Vector3($this->Player->getX()-1, $this->Player->getY(), $this->Player->getZ()-1);
    $pos4        = new Vector3($this->Player->getX(), $this->Player->getY(), $this->Player->getZ()-1);
    $pos5        = new Vector3($this->Player->getX()+1, $this->Player->getY(), $this->Player->getZ());
    $pos6        = new Vector3($this->Player->getX()+1, $this->Player->getY(), $this->Player->getZ()+1);
    $pos7        = new Vector3($this->Player->getX(), $this->Player->getY(), $this->Player->getZ()+1);
    $pos8        = new Vector3($this->Player->getX()+1, $this->Player->getY(), $this->Player->getZ()-1);
    $pos9        = new Vector3($this->Player->getX()-1, $this->Player->getY(), $this->Player->getZ()+1);
    $level   = $this->Player->getLevel();
    $bpos1 = $level->getBlock($pos1)->getId();
    $bpos2 = $level->getBlock($pos2)->getId();
    $bpos3 = $level->getBlock($pos3)->getId();
    $bpos4 = $level->getBlock($pos4)->getId();
    $bpos5 = $level->getBlock($pos5)->getId();
    $bpos6 = $level->getBlock($pos6)->getId();
    $bpos7 = $level->getBlock($pos7)->getId();
    $bpos8 = $level->getBlock($pos8)->getId();
    $bpos9 = $level->getBlock($pos9)->getId();
    if ($bpos1 == 0 and $bpos2 == 0 and $bpos3 == 0 and $bpos4 == 0 and $bpos5 == 0 and $bpos6 == 0 and $bpos7 == 0 and $bpos8 == 0 and $bpos9 == 0)
    {
      return $currentdist;
    }
    else
    {
      return -1;
    }
  }
  */

  # -------------------------------------------------------------------------------------
  # OnMove: Player has made a move
  # -------------------------------------------------------------------------------------
  public function OnMove($event)
  {
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
          $message = "[VAC] > %PLAYER% used ForceOP!";
          $reason = "[VAC] > ForceOP detected!";
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

    #$pos         = new Vector3($posX, $posY, $posZ);
    #$BlockID     = $level->getBlock($pos)->getId();

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
    if ($this->Player->hasPermission("vac.fly")) return;
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
          if (!$this->Player->hasPermission("vac.speed"))
          {
            # Anti Speed
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
            }
          }  
        }
      }
      $this->prev_tick = $tick;
    }

    # No Fly, No Glide and Anti Speed
    /*
    $level       = $this->Player->getLevel();
    
    $posX        = $this->Player->getX();
    $posY        = $this->Player->getY();
    $posZ        = $this->Player->getZ();    
    
    $pos         = new Vector3($posX, $posY, $posZ);
    $BlockID     = $level->getBlock($pos)->getId();

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
        
    $surroundings = array ($bpos1, $bpos2, $bpos3, $bpos4, $bpos5, $bpos6, $bpos7, $bpos8, $bpos9);
    */
    
    if (!$this->Player->isOnGround())
    {
      if(    !in_array(Block::WATER               , $this->surroundings ) 
         and !in_array(Block::STILL_WATER         , $this->surroundings )
         and !in_array(Block::LAVA                , $this->surroundings )
         and !in_array(Block::STILL_LAVA          , $this->surroundings )
         and !in_array(Block::LADDER              , $this->surroundings )
         and !in_array(Block::VINE                , $this->surroundings )
         and !in_array(Block::SLAB                , $this->surroundings ) 
         and !in_array(Block::WOOD_STAIRS         , $this->surroundings )
         and !in_array(Block::COBBLE_STAIRS       , $this->surroundings )
         and !in_array(Block::BRICK_STAIRS        , $this->surroundings )
         and !in_array(Block::STONE_BRICK_STAIRS  , $this->surroundings )
         and !in_array(Block::NETHER_BRICKS_STAIRS, $this->surroundings )
         and !in_array(Block::SPRUCE_WOOD_STAIRS  , $this->surroundings )
         and !in_array(Block::BIRCH_WOODEN_STAIRS , $this->surroundings )
         and !in_array(Block::JUNGLE_WOOD_STAIRS  , $this->surroundings )
         and !in_array(Block::QUARTZ_STAIRS       , $this->surroundings )
         and !in_array(Block::WOOD_SLAB           , $this->surroundings )
         and !in_array(Block::ACACIA_WOOD_STAIRS  , $this->surroundings )
         and !in_array(Block::DARK_OAK_WOOD_STAIRS, $this->surroundings )
         and !in_array(Block::SNOW                , $this->surroundings )
         and !in_array(Block::COBWEB              , $this->surroundings ))
      {
        if ($this->y_pos_old > $this->y_pos_new)
        {
          if ($this->GetConfigEntry("Glide"))
          {
            if (!$this->Player->hasPermission("vac.glide"))
            {
              $this->PlayerGlideCounter++;
              # Player moves down. Check Glide Hack
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
      if ($this->Player->hasPermission("vac.noclip")) return;
      $level   = $this->Player->getLevel();
      $pos     = new Vector3($this->Player->getX(), $this->Player->getY(), $this->Player->getZ());
      $BlockID = $level->getBlock($pos)->getId();

      //ANTI-FALSE-POSITIVES
      if ($BlockID == 1
      or $BlockID == 2
      or $BlockID == 3
      or $BlockID == 4
      or $BlockID == 5
      or $BlockID == 7
      or $BlockID == 14
      or $BlockID == 15
      or $BlockID == 16
      or $BlockID == 17
      or $BlockID == 18
      or $BlockID == 19
      or $BlockID == 20
      or $BlockID == 21
      or $BlockID == 22
      or $BlockID == 24
      or $BlockID == 25
      or $BlockID == 35
      or $BlockID == 41
      or $BlockID == 42
      or $BlockID == 43
      or $BlockID == 45
      or $BlockID == 47
      or $BlockID == 48
      or $BlockID == 49
      or $BlockID == 52
      or $BlockID == 56
      or $BlockID == 57
      or $BlockID == 58
      or $BlockID == 61
      or $BlockID == 62
      or $BlockID == 73
      or $BlockID == 74
      or $BlockID == 79
      or $BlockID == 80
      or $BlockID == 82
      or $BlockID == 86
      or $BlockID == 87
      or $BlockID == 89
      or $BlockID == 91
      or $BlockID == 97
      or $BlockID == 98
      or $BlockID == 99
      or $BlockID == 100
      or $BlockID == 103
      or $BlockID == 110
      or $BlockID == 112
      or $BlockID == 121
      or $BlockID == 122
      or $BlockID == 123
      or $BlockID == 129
      or $BlockID == 133
      or $BlockID == 151
      or $BlockID == 152
      or $BlockID == 153
      or $BlockID == 155
      or $BlockID == 157
      or $BlockID == 159
      or $BlockID == 161
      or $BlockID == 162
      or $BlockID == 170
      or $BlockID == 172
      or $BlockID == 173
      or $BlockID == 174
      or $BlockID == 243
      or $BlockID == 245
      or $BlockID == 246
      or $BlockID == 247
      or $BlockID == 248
      or $BlockID == 249
      or $BlockID == 255)
      {
        if(    !in_array(Block::SLAB                , $this->surroundings ) 
           and !in_array(Block::WOOD_STAIRS         , $this->surroundings )
           and !in_array(Block::COBBLE_STAIRS       , $this->surroundings )
           and !in_array(Block::BRICK_STAIRS        , $this->surroundings )
           and !in_array(Block::STONE_BRICK_STAIRS  , $this->surroundings )
           and !in_array(Block::NETHER_BRICKS_STAIRS, $this->surroundings )
           and !in_array(Block::SPRUCE_WOOD_STAIRS  , $this->surroundings )
           and !in_array(Block::BIRCH_WOODEN_STAIRS , $this->surroundings )
           and !in_array(Block::JUNGLE_WOOD_STAIRS  , $this->surroundings )
           and !in_array(Block::QUARTZ_STAIRS       , $this->surroundings )
           and !in_array(Block::WOOD_SLAB           , $this->surroundings )
           and !in_array(Block::ACACIA_WOOD_STAIRS  , $this->surroundings )
           and !in_array(Block::DARK_OAK_WOOD_STAIRS, $this->surroundings )
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
      if ($this->Player->hasPermission("vac.forcegamemode")) return;
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
    $damager                    = $this->Player;
    $is_damaged_entity_a_player = $damaged_entity instanceof Player;

    $damaged_entity_position  = new Vector3($damaged_entity->getX(), $damaged_entity->getY(), $damaged_entity->getZ());
    $damager_position         = new Vector3($damager->getX()       , $damager->getY()       , $damager->getZ()       );

    //Reach Check
    if ($this->GetConfigEntry("Reach"))
    {
      if (!$this->Player->hasPermission("vac.reach"))
      {
        $reach_distance = $damager_position->distance($damaged_entity_position); 
        #$this->Logger->debug(TextFormat::DARK_PURPLE . "[VAC] > Reach distance $this->PlayerName : $reach_distance");
      
        if ($reach_distance > $this->GetConfigEntry("MaxRange"))
        {
          $event->setCancelled(true);
        }
      }
      /*
      if ($reach_distance > $this->GetConfigEntry("KickRange"))
      {
        $this->PlayerReachCounter++;
        #$this->Logger->debug(TextFormat::DARK_PURPLE . "[VAC] > $this->PlayerName  ReachCounter: $this->PlayerReachCounter");
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
    if ($this->GetConfigEntry("InstantKill"))
    {
      if (!$this->Player->hasPermission("vac.instantkill"))
      {
        $tick = (double)$this->Server->getTick(); 
        $tps  = (double)$this->Server->getTicksPerSecond();
        $tick_count = (double)($tick - $this->PlayerHitFirstTick); // server ticks since last hit
        $delta_t    = (double)($tick_count) / (double)$tps;          // seconds since first reach hack
        if ($this->PlayerHitFirstTick == -1)
        {
          $this->PlayerHitFirstTick = $tick;
        }
        // $this->Logger->info(TextFormat::DARK_PURPLE . "[VAC] > THD $this->PlayerName : $tick_count : $delta_t");
        if ($delta_t < 0.1)
        {
          $this->PlayerHitCounter += 5;
        }
        else
        {
          if($this->PlayerHitCounter > 0)
          {
            $this->PlayerHitCounter--;
          }
        }
        //Allow a maximum of 5 Unlegit hits, couter derceases x5 slower
        if($this->PlayerHitCounter > 25)
        {
          if ($this->GetConfigEntry("InstantKill-Punishment") == "kick")
          {
            $event->setCancelled(true);
            $this->ResetObserver();
            $message = $this->GetConfigEntry("InstantKill-LogMessage");
            $reason  = $this->GetConfigEntry("InstantKill-Message");
            $this->NotifyAdmins($message);
            $this->KickPlayer($reason);
            return;
          }
          if ($this->GetConfigEntry("InstantKill-Punishment") == "block")
          {
            $event->setCancelled(true);
            $message = $this->GetConfigEntry("InstantKill-LogMessage");
            $this->NotifyAdmins($message);
          }
        }
        $this->PlayerHitFirstTick = $tick;
      }
    }
  }

  public function PlayerWasDamaged($event)
  {
    if ($event->getDamage() >= 1)
    {
      $this->LastDamageTick = $this->Server->getTick();  // remember time of last damage
    }
    //THIS IS IN-DEV AND NOT USEABLE
    /* 
    // NoNnockBack Detection

    // $this->Logger->debug(TextFormat::DARK_PURPLE . "[VAC] $this->PlayerName has been damaged by another player.");

    if ($this->GetConfigEntry("NoKnockBack"))
    {
      $kb = $this->getRealKnockBack();
      if ($kb > -1 and $kb < $this->GetConfigEntry("MinKnockBack"))
      {
      }
      $this->Logger->debug(TextFormat::DARK_PURPLE . "[VAC] KnockBack: $kb");
      }
    }   
    */      
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
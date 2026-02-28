<?php

namespace AutoXP;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;
use pocketmine\entity\Living;
use pocketmine\block\CoalOre;
use pocketmine\block\IronOre;
use pocketmine\block\GoldOre;
use pocketmine\block\DiamondOre;
use pocketmine\block\EmeraldOre;
use pocketmine\block\LapisOre;
use pocketmine\block\NetherQuartzOre;
use pocketmine\world\sound\XpOrbPickupSound;

class Main extends PluginBase implements Listener {

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /* =============================
       MINING XP
    ============================== */
    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        $xpConfig = $this->getConfig()->get("xp.ores", []);

        $xp = 0;

        // Use instanceof for API 5 compatibility
        if($block instanceof CoalOre) $xp = $xpConfig["coal"] ?? 1;
        elseif($block instanceof IronOre) $xp = $xpConfig["iron"] ?? 2;
        elseif($block instanceof GoldOre) $xp = $xpConfig["gold"] ?? 3;
        elseif($block instanceof DiamondOre) $xp = $xpConfig["diamond"] ?? 5;
        elseif($block instanceof EmeraldOre) $xp = $xpConfig["emerald"] ?? 5;
        elseif($block instanceof LapisOre) $xp = $xpConfig["lapis"] ?? 3;
        elseif($block instanceof NetherQuartzOre) $xp = $xpConfig["nether_quartz"] ?? 2;

        if($xp > 0){
            $player->addXp($xp);
            $this->playXpSound($player);
        }
    }

    /* =============================
       MOB & PLAYER DEATH
    ============================== */
    public function onEntityDeath(EntityDeathEvent $event): void {
        $entity = $event->getEntity();
        $cause = $entity->getLastDamageCause();

        if(!$cause instanceof EntityDamageByEntityEvent) return;

        $damager = $cause->getDamager();

        // -------- Player killed by another player --------
        if($entity instanceof Player && $damager instanceof Player && $this->getConfig()->getNested("player-kill.enabled", true)){
            $xp = $entity->getXp();
            if($this->getConfig()->getNested("player-kill.transfer-all-xp", true)){
                $damager->addXp($xp);
                $this->playXpSound($damager);
                $entity->setXp(0);
            }
        } 
        // -------- Player killed by environment --------
        elseif($entity instanceof Player){
            if($this->getConfig()->getNested("death-xp.drop-on-ground", true)){
                $entity->getWorld()->dropExperience($entity->getPosition(), $entity->getXp());
                $entity->setXp(0);
            }
        } 
        // -------- Mob killed by player --------
        elseif($damager instanceof Player && $entity instanceof Living){
            $mobXpConfig = $this->getConfig()->get("mob-xp", []);
            $mobName = strtolower($entity->getName());
            $xp = $mobXpConfig[$mobName] ?? 2;
            $damager->addXp($xp);
            $this->playXpSound($damager);
        }
    }

    /* =============================
       XP SOUND
    ============================== */
    private function playXpSound(Player $player): void {
        if(!$this->getConfig()->getNested("sound.enabled", true)) return;
        $player->getWorld()->addSound($player->getPosition(), new XpOrbPickupSound());
    }
}

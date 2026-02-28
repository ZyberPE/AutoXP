<?php

namespace AutoXP;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;
use pocketmine\entity\Living;
use pocketmine\block\BlockLegacyIds;
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

        switch($block->getId()){
            case BlockLegacyIds::COAL_ORE: $xp = $xpConfig["coal"] ?? 1; break;
            case BlockLegacyIds::IRON_ORE: $xp = $xpConfig["iron"] ?? 2; break;
            case BlockLegacyIds::GOLD_ORE: $xp = $xpConfig["gold"] ?? 3; break;
            case BlockLegacyIds::DIAMOND_ORE: $xp = $xpConfig["diamond"] ?? 5; break;
            case BlockLegacyIds::EMERALD_ORE: $xp = $xpConfig["emerald"] ?? 5; break;
            case BlockLegacyIds::LAPIS_ORE: $xp = $xpConfig["lapis"] ?? 3; break;
            case BlockLegacyIds::NETHER_QUARTZ_ORE: $xp = $xpConfig["nether_quartz"] ?? 2; break;
        }

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

        if($entity instanceof Player){
            // -------- Player killed by player --------
            if($damager instanceof Player && $this->getConfig()->getNested("player-kill.enabled", true)){
                $xp = $entity->getXp();
                if($this->getConfig()->getNested("player-kill.transfer-all-xp", true)){
                    $damager->addXp($xp);
                    $this->playXpSound($damager);
                    $entity->setXp(0);
                }
            } 
            // -------- Player killed by TNT/other --------
            else {
                if($this->getConfig()->getNested("death-xp.drop-on-ground", true)){
                    $entity->getWorld()->dropExperience($entity->getPosition(), $entity->getXp());
                    $entity->setXp(0);
                }
            }
        } 
        // -------- Mob killed by player --------
        else if($damager instanceof Player && $entity instanceof Living){
            $mobXpConfig = $this->getConfig()->get("mob-xp", []);
            $mobName = $entity->getName();
            $xp = $mobXpConfig[strtolower($mobName)] ?? 2;
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

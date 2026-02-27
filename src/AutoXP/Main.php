<?php

namespace AutoXP;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\player\Player;

class Main extends PluginBase implements Listener {

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info("AutoXP enabled!");
    }

    /**
     * Handle XP from block breaking (ores etc.)
     */
    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();

        if (!$player->hasPermission("xp.auto")) {
            return;
        }

        $xp = $event->getXpDropAmount();

        if ($xp > 0) {
            $event->setXpDropAmount(0); // Prevent XP orbs
            $player->getXpManager()->addXp($xp); // Give directly
        }
    }

    /**
     * Handle XP from mob kills
     */
    public function onEntityDeath(EntityDeathEvent $event): void {
        $entity = $event->getEntity();
        $cause = $entity->getLastDamageCause();

        if ($cause === null) {
            return;
        }

        $damager = $cause->getAttacker() ?? null;

        if (!$damager instanceof Player) {
            return;
        }

        if (!$damager->hasPermission("xp.auto")) {
            return;
        }

        $xp = $event->getXpDropAmount();

        if ($xp > 0) {
            $event->setXpDropAmount(0); // Prevent XP orbs
            $damager->getXpManager()->addXp($xp); // Give directly
        }
    }
}

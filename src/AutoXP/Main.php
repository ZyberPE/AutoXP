<?php

declare(strict_types=1);

namespace AutoXP;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener {

    public function onEnable() : void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info("AutoXP Enabled (API 5.0.0)");
    }

    /**
     * Auto collect block XP directly into player
     */
    public function onBreak(BlockBreakEvent $event) : void {
        if ($event->isCancelled()) {
            return;
        }

        $player = $event->getPlayer();
        $xp = $event->getXpDropAmount();

        if ($xp > 0) {
            $player->getXpManager()->addXp($xp);
            $event->setXpDropAmount(0);
        }
    }

    /**
     * Give killer the victim's XP directly
     */
    public function onPlayerDeath(PlayerDeathEvent $event) : void {
        $victim = $event->getPlayer();
        $cause = $victim->getLastDamageCause();

        if ($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();

            if ($damager instanceof Player) {
                $xp = $victim->getXpManager()->getCurrentTotalXp();

                if ($xp > 0) {
                    $damager->getXpManager()->addXp($xp);
                    $victim->getXpManager()->setCurrentTotalXp(0);
                }
            }
        }
    }
}

<?php

namespace AutoXP;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;
use pocketmine\world\sound\XpOrbPickupSound;
use pocketmine\block\VanillaBlocks;

class Main extends PluginBase implements Listener {

    private array $xpBlocks;
    private array $mobXp;
    private array $configData;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->configData = $this->getConfig()->getAll();
        $this->xpBlocks = $this->configData["xp"]["ores"] ?? [];
        $this->mobXp = $this->configData["mob-xp"] ?? [];
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $xp = $this->xpBlocks[$block->getType()->getName()] ?? 0;
        if($xp > 0){
            $player->addXp($xp);
            if($this->configData["sound"]["enabled"] ?? false){
                $player->getWorld()->addSound($player->getPosition(), new XpOrbPickupSound());
            }
        }
    }

    public function onEntityDeath(EntityDeathEvent $event): void {
        $entity = $event->getEntity();
        $lastDamage = $entity->getLastDamageCause();
        $xp = $this->mobXp[$entity::class] ?? 0;

        if($entity instanceof Player) return; // Handled in PlayerDeathEvent

        if($xp > 0){
            $killer = null;
            if($lastDamage instanceof EntityDamageByEntityEvent){
                $killer = $lastDamage->getDamager();
            }

            if($killer instanceof Player){
                $killer->addXp($xp);
                if($this->configData["sound"]["enabled"] ?? false){
                    $killer->getWorld()->addSound($killer->getPosition(), new XpOrbPickupSound());
                }
            } else {
                $entity->getWorld()->dropExperience($entity->getPosition(), $xp);
            }
        }
    }

    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $cause = $player->getLastDamageCause();
        $xp = $player->getXp();

        if($cause instanceof EntityDamageByEntityEvent){
            $damager = $cause->getDamager();
            if($damager instanceof Player){
                $damager->addXp($xp);
                $player->setXp(0);
                if($this->configData["sound"]["enabled"] ?? false){
                    $player->getWorld()->addSound($damager->getPosition(), new XpOrbPickupSound());
                }
            } else {
                // Died by mob or TNT etc.
                if($this->configData["death-xp"]["drop-on-ground"] ?? false){
                    $player->getWorld()->dropExperience($player->getPosition(), $xp);
                    $player->setXp(0);
                }
            }
        } else {
            // Environmental death (lava, void, fall, suffocation)
            if($this->configData["death-xp"]["drop-on-ground"] ?? false){
                $player->getWorld()->dropExperience($player->getPosition(), $xp);
                $player->setXp(0);
            }
        }
    }
}

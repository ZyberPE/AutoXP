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

class Main extends PluginBase implements Listener {

    private array $xpBlocks;
    private array $mobXp;
    private array $playerXp = [];
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
            $this->addPlayerXp($player, $xp);
            $this->playXpSound($player);
        }
    }

    public function onEntityDeath(EntityDeathEvent $event): void {
        $entity = $event->getEntity();
        $lastDamage = $entity->getLastDamageCause();
        $xp = $this->mobXp[$entity::class] ?? 0;

        if($entity instanceof Player) return;

        if($xp > 0){
            $killer = null;
            if($lastDamage instanceof EntityDamageByEntityEvent){
                $killer = $lastDamage->getDamager();
            }

            if($killer instanceof Player){
                $this->addPlayerXp($killer, $xp);
                $this->playXpSound($killer);
            } else {
                $entity->getWorld()->dropExperience($entity->getPosition(), $xp);
            }
        }
    }

    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $xp = $this->playerXp[$player->getUniqueId()->toString()] ?? 0;

        $cause = $player->getLastDamageCause();
        if($cause instanceof EntityDamageByEntityEvent){
            $damager = $cause->getDamager();
            if($damager instanceof Player && ($this->configData["player-kill"]["enabled"] ?? true)){
                if($this->configData["player-kill"]["transfer-all-xp"] ?? true){
                    $this->addPlayerXp($damager, $xp);
                    $this->playXpSound($damager);
                    $xp = 0;
                }
            }
        }

        // Drop remaining XP on ground if any
        if(($this->configData["death-xp"]["drop-on-ground"] ?? true) && $xp > 0){
            $player->getWorld()->dropExperience($player->getPosition(), $xp);
        }

        // Reset player's stored XP
        $this->playerXp[$player->getUniqueId()->toString()] = 0;
    }

    private function addPlayerXp(Player $player, int $xp): void {
        $id = $player->getUniqueId()->toString();
        if(!isset($this->playerXp[$id])) $this->playerXp[$id] = 0;
        $this->playerXp[$id] += $xp;
        $player->getXpManager()->addXp($xp);
    }

    private function playXpSound(Player $player): void {
        if($this->configData["sound"]["enabled"] ?? true){
            $player->getWorld()->addSound($player->getPosition(), new XpOrbPickupSound());
        }
    }
}

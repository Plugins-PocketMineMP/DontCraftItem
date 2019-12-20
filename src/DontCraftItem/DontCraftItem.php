<?php
declare(strict_types=1);
namespace DontCraftItem;

use pocketmine\block\BlockFactory;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class DontCraftItem extends PluginBase implements Listener{

    /** @var Config */
    protected $config;

    public $db;

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        $this->config = new Config($this->getDataFolder() . 'Config.yml', Config::YAML, [
                'ban-item' => []
        ]);
        $this->db = $this->config->getAll();
        $this->getServer()->getCommandMap()->register('craftban', new AddCraftBanCommand($this));
    }

    public function onCraft(CraftItemEvent $event){
        $result = $event->getOutputs();
        $id = array_pop($result)->getId();
        if(isset($this->db['ban-item'] [$id])){
            if($event->getPlayer()->isOp()){
                return;
            }
            $event->setCancelled(true);
            $event->getPlayer()->sendTip(TextFormat::RED . 'This Item was banned by admin!');
        }
    }

    public function onDisable(){
        $this->config->setAll($this->db);
        $this->config->save();
    }
}
class AddCraftBanCommand extends Command{

    protected $plugin;

    public function __construct(DontCraftItem $plugin){
        $this->plugin = $plugin;
        parent::__construct('craftban', 'Manage CraftBan', '/craftban [add | remove | list]');
    }
    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
        if(!$sender instanceof Player) return true;
        if(!$sender->isOp()){
            $sender->sendMessage(TextFormat::RED . 'You are not admin!');
            return true;
        }
        if(!isset($args[0])){
            $args[0] = 'x';
        }
        $inv = $sender->getInventory();
        switch($args[0]){
            case 'add':
                if($inv->getItemInHand()->getId() === 0){
                    $sender->sendMessage(TextFormat::YELLOW . 'The id of the item must not be Air!');
                    return true;
                }
                $this->plugin->db['ban-item'] [$inv->getItemInHand()->getId()] = true;
                $sender->sendMessage(TextFormat::YELLOW . 'Success');
                break;
            case 'remove':
                if($inv->getItemInHand()->getId() === 0){
                    $sender->sendMessage(TextFormat::YELLOW . 'The id of the item must not be Air!');
                    return true;
                }
                if(!isset($this->plugin->db['ban-item'] [$inv->getItemInHand()->getId()])){
                    $sender->sendMessage(TextFormat::YELLOW . 'This item is not registered in db');
                    return true;
                }
                unset($this->plugin->db['ban-item'] [$inv->getItemInHand()->getId()]);
                $sender->sendMessage(TextFormat::YELLOW . 'Success');
                break;
            case 'list':
                if(empty($this->plugin->db['ban-item'])){
                    $sender->sendMessage(TextFormat::YELLOW . 'The database is empty.');
                    break;
                }
                $sender->sendMessage(TextFormat::YELLOW . 'Ban list: ' . implode(", ", array_map(function(int $id) : string{ return (BlockFactory::get($id))->getName(); }, array_keys($this->plugin->db['ban-item']))));
                break;
            default:
                $sender->sendMessage(TextFormat::YELLOW . $this->getUsage());
        }
        return true;
    }
}
